<?php
if(!defined('RIDEWIRE_STAFF_EDITOR')){http_response_code(404);exit('Not found');}
$v = static fn(string $key, string $default = ''): string => e((string)($item[$key] ?? $default));
$safeBody = clean_body((string)($item['body_html'] ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=e($label)?> Editor | RideWire BMX</title>
  <link rel="stylesheet" href="staff.css">
  <link rel="stylesheet" href="editor.css">
  <script src="editor.js" defer></script>
</head>
<body class="content-editor-page" data-editor-section="<?=e($section)?>">
  <header>
    <a href="index.php"><img src="../assets/ridewire-logo-v5.png" alt="RideWire BMX"></a>
    <span>STAFF PORTAL</span>
  </header>
  <main class="editor-main">
    <section class="panel editor-panel">
      <div class="editor-topline">
        <a class="back-link" href="index.php">← Dashboard</a>
        <p class="eyebrow">CONTENT MANAGEMENT</p>
      </div>
      <h1><?=e($label)?> Editor</h1>
      <p class="editor-intro">Write and update <?=strtolower(e($label))?> here. Use the formatting buttons for the story; you don’t need to know HTML.</p>
      <?php if($notice):?><p class="notice success" role="status"><?=e($notice)?></p><?php endif;?>
      <?php if($error):?><p class="notice error" role="alert"><?=e($error)?></p><?php endif;?>

      <div class="editor-layout">
        <nav class="entry-list" aria-label="<?=e($label)?> entries">
          <a class="add-entry" href="editor.php?section=<?=e($section)?>&amp;new=1">＋ Add new</a>
          <?php foreach($items as $entry):?>
            <a class="entry-link" href="editor.php?section=<?=e($section)?>&amp;id=<?=(int)$entry['id']?>" <?=((int)$entry['id']===$id?'aria-current="page"':'')?>>
              <strong><?=e($entry['title'])?></strong>
              <span class="entry-status <?=e($entry['status'])?>"><?=e(ucfirst($entry['status']))?><?=!empty($entry['published_at'])?' · '.e($entry['published_at']):''?></span>
            </a>
          <?php endforeach;?>
        </nav>

        <div class="editor-workspace">
          <?php if($item || isset($_GET['new'])):?>
          <form method="post" class="content-form">
            <?=csrf()?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?=$id?>">

            <div class="form-section">
              <label for="title">Title</label>
              <input id="title" name="title" required maxlength="220" value="<?=$v('title')?>" placeholder="Give this story a clear title">
              <div class="form-grid">
                <div>
                  <label for="slug">Page link</label>
                  <input id="slug" name="slug" required value="<?=$v('slug')?>" placeholder="Auto-fills from the title">
                  <small>Short, lowercase words separated by hyphens.</small>
                </div>
                <div>
                  <label for="published_at">Article date</label>
                  <input id="published_at" name="published_at" type="date" value="<?=$v('published_at')?>">
                </div>
              </div>
            </div>

            <div class="form-section">
              <label for="summary">Short introduction</label>
              <textarea id="summary" name="summary" rows="3" placeholder="A short summary shown on the listing page."><?=$v('summary')?></textarea>
            </div>

            <div class="form-section story-section">
              <div class="story-label-row">
                <div><label for="body_html">Story or rider profile</label><p>Write normally. Select text and use a button to format it.</p></div>
                <details class="format-help">
                  <summary>Formatting help</summary>
                  <div class="help-content">
                    <p>For everyday editing, use the toolbar. The HTML examples are optional reference.</p>
                    <table>
                      <thead><tr><th scope="col">What you want</th><th scope="col">Toolbar</th><th scope="col">HTML example</th></tr></thead>
                      <tbody>
                        <tr><th scope="row">A paragraph</th><td>Paragraph</td><td><code>&lt;p&gt;Your text&lt;/p&gt;</code></td></tr>
                        <tr><th scope="row">A section heading</th><td>Heading</td><td><code>&lt;h2&gt;Heading&lt;/h2&gt;</code></td></tr>
                        <tr><th scope="row">Bold or italic words</th><td>Bold / Italic</td><td><code>&lt;strong&gt;bold&lt;/strong&gt;</code></td></tr>
                        <tr><th scope="row">A bullet list</th><td>Bullets</td><td><code>&lt;ul&gt;&lt;li&gt;Item&lt;/li&gt;&lt;/ul&gt;</code></td></tr>
                        <tr><th scope="row">A web link</th><td>Add link</td><td><code>&lt;a href="https://…"&gt;Link text&lt;/a&gt;</code></td></tr>
                      </tbody>
                    </table>
                  </div>
                </details>
              </div>
              <div class="visual-editor" data-visual-editor hidden>
                <div class="editor-toolbar" role="toolbar" aria-label="Story formatting">
                  <button type="button" data-command="formatBlock" data-value="p" aria-label="Paragraph">Paragraph</button>
                  <button type="button" data-command="formatBlock" data-value="h2" aria-label="Section heading">Heading</button>
                  <span aria-hidden="true"></span>
                  <button type="button" data-command="bold" aria-label="Bold"><strong>B</strong></button>
                  <button type="button" data-command="italic" aria-label="Italic"><em>I</em></button>
                  <span aria-hidden="true"></span>
                  <button type="button" data-command="insertUnorderedList" aria-label="Bullet list">• List</button>
                  <button type="button" data-command="createLink" aria-label="Add a link">Add link</button>
                  <button type="button" class="image-tool" data-upload-image aria-label="Add an image from this computer">＋ Add image</button>
                </div>
                <div class="story-canvas" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Story text" data-rich-content spellcheck="true"></div>
              </div>
              <input type="file" data-image-file accept="image/jpeg,image/png,image/webp" hidden>
              <p class="upload-status" data-upload-status role="status" aria-live="polite"></p>
              <noscript><p class="notice">JavaScript is off, so the basic HTML text field is shown.</p></noscript>
              <textarea id="body_html" name="body_html" class="story-source" rows="16" placeholder="Write the story here. Use a blank line between paragraphs."><?=e((string)($item['body_html'] ?? ''))?></textarea>
              <small class="editor-note">Your text is saved as a normal article. The public page keeps the RideWire layout.</small>
            </div>

            <details class="more-details">
              <summary>Images and extra details</summary>
              <div class="form-grid">
                <div class="wide"><label for="image">Main image path or HTTPS link</label><input id="image" name="image" value="<?=$v('image')?>" placeholder="Example: /assets/news/photo.jpg"></div>
                <div><label for="image_alt">Describe the image</label><input id="image_alt" name="image_alt" value="<?=$v('image_alt')?>"></div>
                <div><label for="image_caption">Image credit / caption</label><input id="image_caption" name="image_caption" value="<?=$v('image_caption')?>"></div>
                <div><label for="category">Category / kicker</label><input id="category" name="category" value="<?=$v('category')?>"></div>
                <div><label for="source_url">Source link</label><input id="source_url" name="source_url" type="url" value="<?=$v('source_url')?>" placeholder="https://…"></div>
                <?php if($section==='rider'):?>
                <div><label for="country">Country</label><input id="country" name="country" value="<?=$v('country')?>"></div>
                <div><label for="discipline">Discipline</label><input id="discipline" name="discipline" value="<?=$v('discipline')?>"></div>
                <div class="wide"><label for="highlight">Rider highlight</label><input id="highlight" name="highlight" value="<?=$v('highlight')?>"></div>
                <?php else:?>
                <input type="hidden" name="country" value="<?=$v('country')?>">
                <input type="hidden" name="discipline" value="<?=$v('discipline')?>">
                <input type="hidden" name="highlight" value="<?=$v('highlight')?>">
                <?php endif;?>
                <div><label for="sort_order">Display order</label><input id="sort_order" name="sort_order" type="number" value="<?=$v('sort_order','0')?>"></div>
                <div><label for="status">Status</label><select id="status" name="status"><option value="draft" <?=($v('status','draft')==='draft'?'selected':'')?>>Draft</option><?php if(staff_can($user,$publishCap)||$v('status')==='published'):?><option value="published" <?=($v('status')==='published'?'selected':'')?>>Published</option><?php endif;?></select></div>
              </div>
            </details>

            <div class="editor-actions"><button class="button" type="submit">Save changes</button></div>
          </form>
          <?php if($id&&staff_can($user,$deleteCap)):?>
            <form method="post" class="archive-form" onsubmit="return confirm('Archive this entry from the public site?')">
              <?=csrf()?>
              <input type="hidden" name="action" value="archive">
              <input type="hidden" name="id" value="<?=$id?>">
              <button class="button danger" type="submit">Archive entry</button>
            </form>
          <?php endif;?>
          <?php else:?>
            <div class="empty-editor"><h2>Choose an entry to edit</h2><p>Or add a new <?=strtolower(e($label))?> entry to get started.</p></div>
          <?php endif;?>
        </div>
      </div>

      <form method="post" class="logout"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="link" type="submit">Sign out</button></form>
    </section>
  </main>
  <dialog class="image-details-dialog" data-image-dialog aria-labelledby="image-dialog-title">
    <form method="dialog" data-image-details-form>
      <h2 id="image-dialog-title">Add an image to this story</h2>
      <p class="image-upload-note">The image will be public on RideWire. Your browser resizes it to 2200 pixels, converts it to WebP and removes the original file metadata. Each image can be up to 8 MB; the shared upload budget is 1 GB.</p>
      <label for="inline-image-alt">Image description (alt text)</label>
      <input id="inline-image-alt" name="alt" maxlength="500" required placeholder="Describe what readers can see">
      <label for="inline-image-caption">Caption or photo credit (optional)</label>
      <input id="inline-image-caption" name="caption" maxlength="1000" placeholder="Photographer, source or short caption">
      <p class="upload-status" data-dialog-status role="status" aria-live="polite"></p>
      <div class="dialog-actions"><button type="button" class="cancel-image" data-cancel-image>Cancel</button><button type="button" class="button" data-insert-image>Upload and insert</button></div>
    </form>
  </dialog>
  <footer>RideWire BMX · Staff access</footer>
</body>
</html>
