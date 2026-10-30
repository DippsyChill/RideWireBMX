const fs = require('node:fs');
const path = require('node:path');
const root = __dirname;
const port = Number(process.env.PORT || 8000);
const host = process.env.HOST || '127.0.0.1';
const pageNames = new Set(['index.html','news.html','spotlight.html','privacy.html','terms.html','cookies.html','videos.html','riders.html','about.html']);
const aliases = new Map([...pageNames].map(name => [`/${name}`, `/Pages/${name}`]));
const mimeTypes = {'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.svg':'image/svg+xml','.xml':'application/xml; charset=utf-8','.txt':'text/plain; charset=utf-8'};
httpServer();
function httpServer() {
  require('node:http').createServer((request,response)=>{
    let pathname;
    try { pathname = decodeURIComponent(new URL(request.url,'http://localhost').pathname); }
    catch { response.writeHead(400).end('Bad request'); return; }
    if (pathname === '/') pathname = '/Pages/index.html';
    else if (pathname === '/index.html') pathname = '/Pages/index.html';
    else if (aliases.has(pathname)) { response.writeHead(302,{Location:aliases.get(pathname),'Cache-Control':'no-store'}).end(); return; }
    const filePath = path.resolve(root, `.${pathname}`);
    const relativePath = path.relative(root,filePath);
    if (relativePath.startsWith('..') || path.isAbsolute(relativePath)) { response.writeHead(404).end('Not found'); return; }
    const isPage = /^Pages[\\/][^\\/]+\.html$/i.test(relativePath) && pageNames.has(path.basename(relativePath));
    const isArticle = /^articles[\\/](news|spotlight)[\\/][^\\/]+\.html$/i.test(relativePath);
    const isAsset = /^assets[\\/]/i.test(relativePath);
    const isRootFile = ['styles.css','script.js','robots.txt','sitemap.xml'].includes(relativePath);
    if (relativePath === '404.html') {
      fs.readFile(path.join(root,'404.html'),(error,contents)=>{
        if (error) { response.writeHead(404).end('Not found'); return; }
        response.writeHead(200,{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}).end(contents);
      });
      return;
    }
    if (!(isPage || isArticle || isAsset || isRootFile)) {
      fs.readFile(path.join(root,'404.html'),(error,contents)=>{
        if (error) { response.writeHead(404).end('Not found'); return; }
        response.writeHead(404,{'Content-Type':'text/html; charset=utf-8','Cache-Control':'no-store'}).end(contents);
      });
      return;
    }
    fs.readFile(filePath,(error,contents)=>{
      if (error) { response.writeHead(404).end('Not found'); return; }
      response.writeHead(200,{'Content-Type':mimeTypes[path.extname(filePath).toLowerCase()] || 'application/octet-stream','Cache-Control':'no-store'}).end(contents);
    });
  }).listen(port,host,()=>console.log(`RideWireBMX preview: http://${host}:${port}`));
}
