const fs = require('node:fs');
const path = require('node:path');
const root = __dirname;
const port = Number(process.env.PORT || 8000);
const pageNames = new Set(['index.html','news.html','spotlight.html','privacy.html','terms.html','cookies.html','videos.html','riders.html','about.html']);
const aliases = new Map([...pageNames].map(name => [`/${name}`, `/Pages/${name}`]));
const mimeTypes = {'.html':'text/html; charset=utf-8','.css':'text/css; charset=utf-8','.js':'text/javascript; charset=utf-8','.png':'image/png','.jpg':'image/jpeg','.jpeg':'image/jpeg','.webp':'image/webp','.svg':'image/svg+xml'};
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
    const isRootFile = ['styles.css','script.js'].includes(relativePath);
    if (!(isPage || isArticle || isAsset || isRootFile)) { response.writeHead(404).end('Not found'); return; }
    fs.readFile(filePath,(error,contents)=>{
      if (error) { response.writeHead(404).end('Not found'); return; }
      response.writeHead(200,{'Content-Type':mimeTypes[path.extname(filePath).toLowerCase()] || 'application/octet-stream','Cache-Control':'no-store'}).end(contents);
    });
  }).listen(port,'127.0.0.1',()=>console.log(`RideWireBMX preview: http://127.0.0.1:${port}`));
}
