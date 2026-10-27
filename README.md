# RideWireBMX

Responsive static website for RideWireBMX, with dedicated Home, News, Spotlight, Videos, Riders and About pages. A compact PHP staff portal foundation is included for Admin and Editor access.

## Project structure

- `Pages/` contains the main pages; `articles/` contains News and Spotlight stories; `assets/` contains site images.
- `staff/` contains the staff sign-in and permission setup.
- `private/` holds the local IONOS configuration. Never commit the real `config.php`.
- `database/staff-schema.sql` creates the production MariaDB tables.
- `bin/create-owner.php` creates the first Owner from the command line.
- `preview-server.js` serves the static-only preview.

## Local preview

The public static preview remains `node preview-server.js` on port 8000. To test the PHP backend and public pages together:

1. Copy `private/config.local.example.php` to `private/config.php` (replace the local security key).
2. In a PowerShell terminal, create the local Owner account by running the PHP command-line tool: `& "$env:LOCALAPPDATA\RideWireTools\php-8.5.12\php.exe" bin/create-owner.php`. Enter the Owner details and a temporary local password when prompted.
3. Start PHP with `./scripts/start-local-backend.ps1`.
4. Open `http://127.0.0.1:8001/staff/`. The SQLite test database is created in the operating system temp folder, outside the repository.

The local server uses PHP CLI with PDO_SQLite enabled. It does not connect to IONOS or send project data anywhere.

## IONOS staff portal setup

The portal uses PHP, PDO and MariaDB with no framework or package dependencies. No photos or videos are stored.

1. Create a MariaDB database in IONOS and import `database/staff-schema.sql`.
2. Configure `private/config.php` for the IONOS database and a random security key.
3. Upload the files over SFTP. Enable HTTPS and a current supported PHP version before allowing staff sign-in.
4. Use an SSH session and the PHP CLI path for your contract to run `bin/create-owner.php`; it is CLI-only and creates the first Owner.
5. Sign in at `/staff/`. The Owner can create Admin and Editor accounts and adjust capabilities. New staff change their temporary password at first sign-in.

IONOS PHP CLI paths vary by contract date and PHP version; see [IONOS PHP CLI instructions](https://www.ionos.com/help/hosting/using-php-for-web-projects/executing-php-files-via-the-command-line-php-cli/). Shared-hosting databases must be accessed by code running on IONOS, not from a local computer.

News, Spotlight, Videos and site statistics capabilities are reserved for later connected tools. Visitor accounts, analytics and public video submissions are not active.
