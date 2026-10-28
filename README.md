# RideWireBMX

Responsive static website for RideWireBMX, with dedicated Home, News, Spotlight, Videos, Riders and About pages. A compact PHP staff portal foundation is included for Admin and Editor access.

## Project structure

- `Pages/` contains the main pages; `articles/` contains News and Spotlight stories; `assets/` contains site images.
- `staff/` contains the Dashboard, Staff Permissions, Statistics and News, Spotlight and Rider editors.
- `api/` serves published content and records aggregate page views; `article.php` displays database-backed stories.
- `bin/import-static-content.php` imports existing static News, Spotlight and Rider entries into the configured database.
- `private/` holds the local IONOS configuration. Never commit the real `config.php`.
- `database/staff-schema.sql` creates the production MariaDB tables.
- `bin/create-owner.php` creates the first Owner from the command line.
- `preview-server.js` serves the static-only preview.

## Local preview

The public static preview remains `node preview-server.js` on port 8000. To test the PHP backend and public pages together:

1. Copy `private/config.local.example.php` to `private/config.php` (replace the local security key).
2. In a PowerShell terminal, create the local Owner account by running the PHP command-line tool: `& "$env:LOCALAPPDATA\RideWireTools\php-8.5.12\php.exe" bin/create-owner.php`. Enter the Owner details and a temporary local password when prompted.
3. Start PHP with `./scripts/start-local-backend.ps1`.
4. Open `http://127.0.0.1:8001/staff/` (Staff Login). The SQLite test database is created in the operating system temp folder, outside the repository.

The local server uses PHP CLI with PDO_SQLite enabled. Import the existing static entries locally with `& "$env:LOCALAPPDATA\RideWireTools\php-8.5.12\php.exe" bin/import-static-content.php`; it skips slugs already in the database. Local testing uses SQLite and does not connect to IONOS.

The Node preview on port 8000 serves static pages and proxies `/staff/`, `/api/` and `/article.php` to the PHP backend on port 8001 when it is running. Open `http://127.0.0.1:8000/staff/` or use port 8001 directly. If the backend is stopped, start it with `./scripts/start-local-backend.ps1`.

## IONOS staff portal setup

The portal uses PHP, PDO and MariaDB with no framework or package dependencies. Story images uploaded by staff are resized and converted to WebP in the browser, then stored as public files under `assets/uploads/`; the database stores only their paths in article markup. Uploads are limited to 8 MB per image and 1 GB total. The upload directory must be writable by PHP. Set `upload_max_filesize` to at least 9M and `post_max_size` to at least 10M for IONOS PHP.

1. Create a MariaDB database in IONOS and import `database/staff-schema.sql`.
2. Configure `private/config.php` for the IONOS database and a random security key.
3. Upload the files over SFTP. Enable HTTPS and a current supported PHP version before allowing staff sign-in.
4. Upload the project files, then run `bin/import-static-content.php` through IONOS PHP CLI once to import current static News, Spotlight and Rider entries. Images remain in the existing `assets/` folder or referenced HTTPS URLs.
5. Use an SSH session and the PHP CLI path for your contract to run `bin/create-owner.php`; it is CLI-only and creates the first Owner.
6. Sign in at `/staff/`. The dashboard links to Website Statistics and content editors according to granted permissions. The Owner can create Admin and Editor accounts and adjust capabilities. New staff change their temporary password at first sign-in.

IONOS PHP CLI paths vary by contract date and PHP version; see [IONOS PHP CLI instructions](https://www.ionos.com/help/hosting/using-php-for-web-projects/executing-php-files-via-the-command-line-php-cli/). Shared-hosting databases must be accessed by code running on IONOS, not from a local computer.

Website Statistics stores only UTC date, page path and aggregate view count. It does not add analytics cookies, retain app-level IP addresses, or contact an external analytics provider. Detail pages are counted by section and slug. Visitor accounts and public video submissions are not active.
