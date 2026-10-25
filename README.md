# RideWireBMX

Responsive static website for RideWireBMX, with dedicated Home, News, Spotlight, Videos, Riders and About pages.

## Project structure

- `Pages/` contains the main site pages and indexes. The root `index.html` forwards visitors to `Pages/index.html` for standard IONOS hosting.
- `articles/news/` contains individual news stories.
- `articles/spotlight/` contains individual rider spotlight profiles.
- `assets/` contains the site logo, rider, news and throwback images.
- `ChangeLog.YAML` records site changes and locations.
- `preview-server.js` serves the local preview only.

## Run locally

Run `node preview-server.js` and open `http://127.0.0.1:8000`. The site has no build step or package dependencies.

## Publish on IONOS

Upload the root `index.html`, the complete `Pages`, `articles`, and `assets` folders, plus `styles.css` and `script.js`, to the website's document root in IONOS Webspace. Preserve the folder structure so navigation, article links and image paths resolve. The root `index.html` forwards visitors to `Pages/index.html`. The local-only `preview-server.js` is not needed on IONOS.

The current site is frontend-only. It does not require a backend, database, or server-side runtime.
