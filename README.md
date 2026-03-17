# phpMyProfiler

phpMyProfiler is a PHP web application for publishing a DVDProfiler collection on a website. It imports exported collection XML files, stores the data in MySQL, and renders a browsable catalog with film detail pages, people search, images, reviews, guestbook entries, and collection statistics.

This repository currently identifies itself as `1.2 dev`.

## What It Does

- Imports DVDProfiler collection exports from XML, ZIP, or BZ2 files.
- Builds a public-facing catalog with title pages, cover lists, pictures, screenshots, people listings, watched items, and search.
- Supports visitor reviews, a guestbook, contact form, and site news.
- Generates collection statistics and graphs for ratings, genres, studios, regions, purchase history, origins, and more.
- Includes an admin area for parsing collections, moderating reviews/guestbook entries, managing news, pictures, screenshots, awards, and preferences.
- Supports multiple themes (`default`, `slate`, `sunrise`) and bundled translations (`en`, `de`, `dk`, `nl`, `no`).
- Includes custom media icon support via `custom_media.inc.php`.

## Project Layout

- [`index.php`](/index.php) is the front controller for the public site.
- [`admin/`](/admin) contains the administration UI and import tools.
- [`installation/`](/installation) contains the browser-based installer.
- [`include/`](/include) contains shared runtime code, Smarty, graphing, and the MySQL compatibility layer.
- [`themes/`](/themes) contains templates, CSS, translations, and theme assets.
- [`xml/`](/xml) is the upload/import workspace for collection exports.
- [`cover/`](/cover), [`pictures/`](/pictures), [`screenshots/`](/screenshots), [`cache/`](/cache), and [`templates_c/`](/templates_c) store generated or uploaded assets.

## Requirements

The installer checks for these server capabilities:

- PHP `>= 5.3.7` according to the historical installer check.
- MySQL connectivity.
- PHP extensions: `session`, `SPL`, `pcre`, `xml`, `iconv`.
- Optional but useful extensions: `gd`, `zip`, `bz2`, `zlib`, `curl`.

Practical note for modern deployments:

- The application is still a legacy PHP app architecturally, but this repo includes a MySQL compatibility layer in [`include/mysql_compat.php`](/include/mysql_compat.php) and other modernization work.
- The codebase still relies heavily on `mysql_*` calls through that shim, so compatibility should be validated against your target PHP and MySQL/MariaDB versions before production use.

## Installation

1. Deploy the project to a PHP-enabled web server.
2. Create or prepare a MySQL/MariaDB database and database user.
3. Ensure these paths are writable by the web server before starting the installer:
   - `awards/`
   - `cache/`
   - `cover/`
   - `pictures/`
   - `screenshots/`
   - `screenshots/thumbs/`
   - `templates_c/`
   - `xml/`
   - `xml/split/`
   - `config.inc.php`
   - `passwd.inc.php`
4. Open `/installation/` in a browser.
5. Enter database connection details and the public base URL.
6. Let the installer create/populate the schema from [`installation/sql/phpmyprofiler.sql`](/installation/sql/phpmyprofiler.sql) and [`installation/sql/phpmyprofiler_data.sql`](/installation/sql/phpmyprofiler_data.sql).
7. Create the administrator account.
8. Delete the [`installation/`](/installation) directory after setup. The app explicitly warns that leaving it in place is unsafe.

If `installation/` still exists, both the public site and admin area redirect back into the installer.

## Importing a Collection

Typical workflow:

1. Export your collection from DVDProfiler as XML.
2. Log into the admin area.
3. Upload the export through the XML upload tool. Supported upload types are `.xml`, `.zip`, and `.bz2`.
4. Run the parser from the admin panel.
5. Choose the parser mode in preferences:
   - `Build from scratch`
   - `Update with delete`
   - `Update without delete`
6. If your collection is large, the parser can split the XML into smaller files in `xml/split/`.

Parsing also refreshes derived data such as collection statistics and cached assets.

## Admin Features

The admin area includes tools for:

- Uploading and parsing collection exports.
- Editing site preferences stored in [`config.inc.php`](/config.inc.php).
- Moderating guestbook entries and visitor reviews.
- Managing news posts.
- Uploading pictures and screenshots.
- Fetching or managing covers.
- Updating external ratings and awards data.
- Generating collection reports.
- Updating passwords and checking runtime/php info.

## Public Features

The public site routes through [`index.php`](/index.php) and exposes pages such as:

- Start page / latest additions
- Film profile pages
- Cover list
- Search and person search
- People list
- Picture list
- Watched items
- Statistics and detailed statistics views
- News
- Guestbook
- Contact form
- Review submission

## Configuration

Most settings are stored in [`config.inc.php`](/config.inc.php) and are also editable from the admin preferences screen. Configuration areas include:

- Database connection and table prefix
- Base URL and page title
- Administrator/site metadata
- Language, date, timezone, and currency settings
- Parser behavior
- Caching and thumbnail generation
- Graphics/statistics behavior
- Theme and display settings
- Review/guestbook behavior
- Update checks and external rating refresh behavior

Admin credentials are stored separately in [`passwd.inc.php`](/passwd.inc.php).

## Development Notes

- PHP formatting support is configured through [`package.json`](/package.json) with Prettier and `@prettier/plugin-php`.
- The app uses bundled Smarty templates rather than a framework.
- There is no obvious automated test suite in this repository.
- The code mixes legacy application structure with newer hardening work such as CSRF checks, stricter session settings, and HTTP security headers.

## Operational Caveats

- This is an older application with a long-lived codebase. Read the source before assuming modern framework conventions.
- Some remote integrations reference external services that may have changed behavior over time.
- Update checking in the admin UI still points at `www.genesworld.net`.
- File permissions matter. Many runtime features write to disk for uploads, cached templates, thumbnails, screenshots, and XML processing.

## License

This project is licensed under the GNU General Public License, version 2 or later. See [`LICENSE`](/LICENSE).
