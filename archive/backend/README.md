# Paused Staff backend

This code is shelved for a later backend phase. The frontend launch does not depend on these files. The root `.htaccess` blocks public access to `/archive/`.

## Contents

- `api/`, `staff/`, `database/`, `bin/`: PHP portal, content API, schema, import and owner tools.
- `article.php`, `content-render.js`, `article-content.css`: database-backed article and content rendering.
- `private/`: example database configurations and content-store code. The ignored local `private/config.php` was intentionally left in the repository's protected local-only folder and was not moved or read.
- `start-local-backend.ps1`: local PHP server launcher.
- `assets/uploads/`: PHP upload execution protection rule.

To resume backend work, restore these files to their original root paths, then review deployment configuration, database setup and the first Owner setup flow before enabling staff access.