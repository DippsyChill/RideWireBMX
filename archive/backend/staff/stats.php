<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
$user=staff_user();
if(!$user){header('Location: index.php');exit;}
if((int)$user['must_change_password']){header('Location: index.php?view=password');exit;}
if(!staff_can($user,'site.stats.view')){http_response_code(403);exit('You do not have access to website statistics.');}
function stats_h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function svg_num(float $value):string{return number_format($value,2,'.','');}

$days=max(7,min(90,(int)($_GET['days']??30)));
$today=new DateTimeImmutable('today',new DateTimeZone('UTC'));
$start=$today->modify('-'.($days-1).' days');
$startDay=$start->format('Y-m-d');
$totalQuery=$pdo->prepare('SELECT COALESCE(SUM(view_count),0) FROM rw_page_views WHERE view_day>=?');
$totalQuery->execute([$startDay]);
$totalViews=(int)$totalQuery->fetchColumn();
$pageQuery=$pdo->prepare('SELECT page_path,SUM(view_count) AS views FROM rw_page_views WHERE view_day>=? GROUP BY page_path ORDER BY views DESC,page_path LIMIT 30');
$pageQuery->execute([$startDay]);
$pages=$pageQuery->fetchAll();
$dailyQuery=$pdo->prepare('SELECT view_day,SUM(view_count) AS views FROM rw_page_views WHERE view_day>=? GROUP BY view_day');
$dailyQuery->execute([$startDay]);
$dailyMap=[];
foreach($dailyQuery->fetchAll() as $row)$dailyMap[$row['view_day']]=(int)$row['views'];
$daily=[];
for($i=0;$i<$days;$i++){$date=$start->modify('+'.$i.' days')->format('Y-m-d');$daily[]=['day'=>$date,'views'=>$dailyMap[$date]??0];}
$dailyMax=max(1,max(array_column($daily,'views')));
$topPages=array_slice($pages,0,10);
$pageMax=$topPages?(int)$topPages[0]['views']:0;
$average=round($totalViews/$days,1);
$left=66.0;$right=986.0;$top=22.0;$bottom=252.0;$plotWidth=$right-$left;$plotHeight=$bottom-$top;
$points=[];
foreach($daily as $index=>$row){$x=$left+($days>1?$index*$plotWidth/($days-1):$plotWidth/2);$y=$bottom-($row['views']/$dailyMax)*$plotHeight;$points[]=['x'=>$x,'y'=>$y,'row'=>$row];}
$linePath='';foreach($points as $index=>$point)$linePath.=($index===0?'M':'L').' '.svg_num($point['x']).' '.svg_num($point['y']).' ';
$areaPath=$linePath.'L '.svg_num($right).' '.svg_num($bottom).' L '.svg_num($left).' '.svg_num($bottom).' Z';
$labelStep=$days<=7?1:($days<=30?5:15);
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Website Statistics | RideWire BMX</title>
  <link rel="stylesheet" href="staff.css">
  <link rel="stylesheet" href="stats.css">
</head>
<body class="stats-page">
  <header><a href="index.php"><img src="../assets/ridewire-logo-v5.png" alt="RideWire BMX"></a><span>STAFF PORTAL</span></header>
  <main class="stats-main">
    <section class="panel stats-panel">
      <a class="back" href="index.php">← Dashboard</a>
      <p class="eyebrow">REPORTING</p>
      <div class="stats-title-row"><div><h1>Website Statistics</h1><p>Page views counted by day and page.</p></div>
        <nav class="range-tabs" aria-label="Choose reporting range">
          <?php foreach([7,30,90] as $range):?><a href="?days=<?=$range?>" <?=($days===$range?'aria-current="page"':'')?>><?=$range?> days</a><?php endforeach;?>
        </nav>
      </div>

      <div class="stats-summary" aria-label="Summary for the selected date range">
        <article><span>PAGE VIEWS</span><strong><?=number_format($totalViews)?></strong><small>Last <?=$days?> days</small></article>
        <article><span>DAILY AVERAGE</span><strong><?=number_format($average,1)?></strong><small>Views per day</small></article>
        <article><span>TOP PAGE</span><strong class="top-page-value"><?=$topPages?stats_h($topPages[0]['page_path']):'—'?></strong><small><?=$topPages?number_format((int)$topPages[0]['views']).' views':'No views yet'?></small></article>
      </div>

      <section class="chart-card" aria-labelledby="daily-heading">
        <div class="chart-heading"><div><h2 id="daily-heading">Daily page views</h2><p>Traffic over the last <?=$days?> days (UTC)</p></div><span class="chart-key"><i aria-hidden="true"></i> Page views</span></div>
        <div class="chart-scroll">
          <svg class="line-chart" viewBox="0 0 1000 300" role="img" aria-labelledby="daily-chart-title daily-chart-description">
            <title id="daily-chart-title">Daily RideWire page views over <?=$days?> days</title>
            <desc id="daily-chart-description">A line chart showing total page views for each UTC date from <?=stats_h($startDay)?> to <?=stats_h($today->format('Y-m-d'))?>. The maximum daily count is <?=number_format($dailyMax)?>.</desc>
            <?php for($tick=0;$tick<=4;$tick++):$value=$dailyMax*(4-$tick)/4;$y=$top+$tick*$plotHeight/4;?>
              <line class="chart-gridline" x1="<?=$left?>" y1="<?=svg_num($y)?>" x2="<?=$right?>" y2="<?=svg_num($y)?>"></line>
              <text class="chart-y-label" x="<?=$left-12?>" y="<?=svg_num($y+4)?>" text-anchor="end"><?=number_format((int)round($value))?></text>
            <?php endfor;?>
            <path class="chart-area" d="<?=$areaPath?>"></path>
            <path class="chart-line" d="<?=$linePath?>"></path>
            <?php foreach($points as $index=>$point):?><circle class="chart-point" cx="<?=svg_num($point['x'])?>" cy="<?=svg_num($point['y'])?>" r="<?=($days<=30?3.6:2.8)?>"><title><?=stats_h(date('d M Y',strtotime($point['row']['day'].' UTC')))?>: <?=number_format($point['row']['views'])?> views</title></circle><?php if($index%$labelStep===0||$index===$days-1):?><text class="chart-x-label" x="<?=svg_num($point['x'])?>" y="278" text-anchor="middle"><?=stats_h(date('d M',strtotime($point['row']['day'].' UTC')))?></text><?php endif;endforeach;?>
          </svg>
        </div>
      </section>

      <section class="chart-card pages-chart-card" aria-labelledby="pages-heading">
        <div class="chart-heading"><div><h2 id="pages-heading">Most viewed pages</h2><p>Top 10 pages in this period</p></div></div>
        <?php if($topPages):?><ol class="page-bar-chart"><?php foreach($topPages as $page):$views=(int)$page['views'];$width=$pageMax>0?round($views/$pageMax*100,1):0;?>
          <li><div class="bar-label-row"><span><?=stats_h($page['page_path'])?></span><strong><?=number_format($views)?></strong></div><div class="bar-track" role="img" aria-label="<?=stats_h($page['page_path'])?>: <?=number_format($views)?> views"><span style="width:<?=$width?>%"></span></div></li>
        <?php endforeach;?></ol><?php else:?><p class="empty-stats">No page views have been recorded yet.</p><?php endif;?>
      </section>

      <details class="data-details"><summary>View the numbers in tables</summary>
        <div class="stat-table-wrap"><table class="stat-table"><caption>Daily page views (UTC)</caption><thead><tr><th scope="col">Date</th><th scope="col">Views</th></tr></thead><tbody><?php foreach(array_reverse($daily) as $row):?><tr><td><?=stats_h($row['day'])?></td><td><?=number_format($row['views'])?></td></tr><?php endforeach;?></tbody></table></div>
        <div class="stat-table-wrap"><table class="stat-table"><caption>Top pages</caption><thead><tr><th scope="col">Page</th><th scope="col">Views</th></tr></thead><tbody><?php foreach($pages as $page):?><tr><td><?=stats_h($page['page_path'])?></td><td><?=number_format((int)$page['views'])?></td></tr><?php endforeach;?><?php if(!$pages):?><tr><td colspan="2">No recorded views yet.</td></tr><?php endif;?></tbody></table></div>
      </details>
      <p class="stats-privacy">Only the date, public page path and aggregate count are stored. RideWire’s statistics feature does not use analytics cookies or store app-level IP addresses.</p>
    </section>
  </main>
  <footer>RideWire BMX · Staff access</footer>
</body>
</html>
