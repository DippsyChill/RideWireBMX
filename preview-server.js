const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");

const root = __dirname;
const port = Number(process.env.PORT || 8000);
const allowedRootFiles = new Set(["index.html"]);
const allowedPageFiles = new Set(["index.html", "news.html", "spotlight.html", "privacy.html", "terms.html", "cookies.html", "videos.html", "riders.html", "about.html"]);
const mimeTypes = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
};

http.createServer((request, response) => {
  let pathname;
  try {
    pathname = decodeURIComponent(new URL(request.url, "http://localhost").pathname);
  } catch {
    response.writeHead(400).end("Bad request");
    return;
  }

  if (pathname === "/") pathname = "/index.html";
  const filePath = path.resolve(root, `.${pathname}`);
  const relativePath = path.relative(root, filePath);
  const isArticleFile = /^articles[\\/](news|spotlight)[\\/][^\\/]+\.html$/i.test(relativePath);
  const isPageFile = relativePath.startsWith(`Pages${path.sep}`) && allowedPageFiles.has(path.basename(relativePath));
  const isAllowed = allowedRootFiles.has(relativePath) || isPageFile || isArticleFile || relativePath.startsWith(`assets${path.sep}`);

  if (!isAllowed || relativePath.startsWith("..")) {
    response.writeHead(404).end("Not found");
    return;
  }

  fs.readFile(filePath, (error, contents) => {
    if (error) {
      response.writeHead(404).end("Not found");
      return;
    }
    response.writeHead(200, {
      "Content-Type": mimeTypes[path.extname(filePath)] || "application/octet-stream",
      "Cache-Control": "no-store",
    });
    response.end(contents);
  });
}).listen(port, "127.0.0.1", () => {
  console.log(`RideWireBMX preview: http://127.0.0.1:${port}`);
});

