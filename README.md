# RideWireBMX

Responsive static website for RideWireBMX, with dedicated Home, News, Spotlight, Videos, Riders and About pages. The launch build is frontend-only and does not require PHP or a database.

## Project structure

- `Pages/` contains the public pages; `articles/` contains static News and Spotlight stories; `assets/` contains public site images.
- `archive/backend/` holds the paused PHP Staff portal, database schema, content API and local backend tools. These files are not part of the public launch and are blocked from web access by `.htaccess`.
- `private/config.php` is an ignored local-only file and is not included in Git. Do not upload it with the static site.
- `preview-server.js` serves the frontend locally without PHP.

## Local preview

Run `node preview-server.js` from the repository folder, then open `http://127.0.0.1:8000/`. The preview is bound to loopback by default. Use Ctrl+C to stop it.

## Hosting

Upload the frontend pages, articles, assets, CSS, JavaScript, `.htaccess`, `404.html`, `robots.txt`, and `sitemap.xml` to the IONOS webspace. No database or PHP configuration is needed for the frontend-only launch. Keep `archive/`, `.git/`, `.codex-checkpoints/`, and the local `private/` folder off the public webspace; the root access rules also deny requests for these paths.