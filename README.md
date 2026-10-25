# RideWireBMX

Responsive static website for RideWireBMX, with dedicated Home, News, Spotlight, Videos, Riders and About pages.

## Project structure

- Root HTML files are the main site pages and indexes.
- `articles/news/` contains individual news stories.
- `articles/spotlight/` contains individual rider spotlight profiles.
- `assets/` contains the site logo, rider, news and throwback images.
- `ChangeLog.YAML` records site changes and locations.
- `preview-server.js` serves the local preview only.

## Run locally

Run `node preview-server.js` and open `http://127.0.0.1:8000`. The site has no build step or package dependencies.

## Publish on IONOS

Upload the root site pages and shared files (`index.html`, `news.html`, `spotlight.html`, `videos.html`, `riders.html`, `about.html`, `privacy.html`, `terms.html`, `cookies.html`, `styles.css`, and `script.js`), along with the complete `articles` and `assets` folders, to the website's document root in IONOS Webspace. Preserve the folder structure so article links and image paths resolve. The site's entry page is `index.html`. The local-only `preview-server.js` is not needed on IONOS.

The current site is frontend-only. It does not require a backend, database, or server-side runtime.
