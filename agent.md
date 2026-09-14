# Simple File List — Agent & Developer Guide

This file helps AI coding agents and developers understand the structure, conventions, and boundaries of the **Simple File List Free** WordPress plugin.

---

## Plugin Identity

| Key | Value |
|-----|-------|
| Plugin slug | `simple-file-list` |
| Text domain | `simple-file-list` |
| Main file | `simple-file-list.php` |
| Version constant | `eeSFL_Version` |
| DB version option | `eeSFL_Version` (was `eeSFL_BASE_Version` in 6.1.18) |
| Default upload dir | `wp-content/uploads/simple-file-list/` |
| Shortcode | `[eeSFL]` |

---

## What This Plugin Is (and Is Not)

**This is the Free version.** Standalone — no Pro license or extension dependencies.

Features **only in Pro** — do NOT add here:
- Multi-list support (SFLA / Access extension)
- Background tasks / WP-Cron scanning
- Folder management (eeSFLF)
- Search extension (eeSFLS)
- Registration / licensing checks

Features **included in Free**:
- Single file list (List ID = 1 always)
- File upload with type/size restrictions
- File editing: rename, describe, date, delete
- Email file sharing (via `ee-class-send.php`)
- Media player (audio/video inline)
- Image thumbnails
- Front-end management (configurable by role)
- Light/dark themes; table, tiles, flex display styles
- Bulk file operations for deleting, downloading and applying descriptions

---

## File Structure

```
simple-file-list.php              Main plugin file — constants, globals, WP hook registrations
AGENTS.md                         This file

includes/
  ee-class.php                    eeSFL_MainClass — core methods: GetEnv, GetSettings, UpdateFileListArray,
                                  UpdateFileDetail, CheckThumbnail, GetRootPath, GetThisURL, etc.
  ee-class-uploads.php            eeSFL_UploadClass — handles file upload processing and validation
  ee-class-send.php               eeSFLE_class — email file sharing (integrated into Free core)
  ee-functions.php                Standalone functions: eeSFL_FileSystem wrapper, eeSFL_VersionCheck,
                                  eeSFL_FileEditor, AJAX job functions, asset registration, admin menu
  ee-front-end.php                eeSFL_FrontEnd() — [eeSFL] shortcode handler, front-end rendering
  ee-admin-page.php               Admin page router — tab/subtab dispatcher
  ee-admin-header.php             Admin page header and tab navigation
  ee-admin-footer.php             Admin page footer
  ee-admin-tools.php              Admin Tools tab (log viewer, re-scan, debug info)
  ee-list-display.php             List display router — selects table/flex/tiles based on settings
  ee-list-display-table.php       Table layout renderer
  ee-list-display-flex.php        Flex layout renderer
  ee-list-display-tiles.php       Tiles layout renderer
  ee-list-ops-bar-display.php     Operations bar HTML (sort controls, search, folder breadcrumb)
  ee-list-ops-bar-process.php     Operations bar form processing (sort, filter, folder nav)
  ee-list-settings.php            List Settings tab — display, access, sort, date options
  ee-upload-settings.php          Upload Settings tab — allowed types, size limits, notifications
  ee-email-settings.php           Email Settings tab — notify addresses, message template
  ee-index-template.html          Template copied as index.html into new upload directories (security)
  htaccess-template.txt           Template for .htaccess hotlink protection (Apache/LiteSpeed only)

css/
  admin.css                       Admin panel styles
  styles.css                      Base front-end styles
  styles-theme-dark.css           Dark theme
  styles-theme-light.css          Light theme
  styles-table.css                Table layout styles
  styles-flex.css                 Flex layout styles
  styles-tiles.css                Tiles layout styles
  styles-upload-form.css          Upload form styles
  styles-upload-theme-light.css   Upload form light theme
  ee-media-styles.css             Media player styles

js/
  ee-head.js                      Scripts loaded in <head> (early init)
  ee-footer.js                    Main front-end scripts (file actions, copy link, UI)
  ee-back.js                      Admin-only scripts
  ee-edit-file.js                 File editor modal (rename, describe, date, delete)
  ee-uploader.js                  Drag-and-drop / multi-file uploader
  ee-email.js                     Email file sharing UI
  ee-media-scripts-footer.js      Audio/video media player

languages/
  simple-file-list.pot            Source strings template (regenerate with WP-CLI make-pot)
  simple-file-list-{locale}.po    Translator source files
  simple-file-list-{locale}.mo    Compiled binary translations
  simple-file-list-{locale}.l10n.php  PHP-native translation cache (WP 6.5+)

images/
  thumbnails/                     Generated image/video/PDF thumbnail cache
```

---

## Architecture Overview

The plugin uses a **hybrid class + functions** approach:

1. **`eeSFL_Setup()`** runs on `init`, loads all includes, instantiates classes, and bootstraps the environment
2. **`eeSFL_MainClass`** (`ee-class.php`) holds stateful methods and the core file-list logic
3. **`ee-functions.php`** holds stateless utility functions and all WordPress AJAX handlers
4. **Admin pages** are tab/subtab routed through `ee-admin-page.php` using `$_GET['tab']` and `$_GET['subtab']`
5. **Front-end** rendering is triggered by the `[eeSFL]` shortcode via `eeSFL_FrontEnd()`

### Request Flow

```
WordPress init
  └── eeSFL_Setup()
        ├── Load includes (ee-functions.php, ee-class.php, ee-class-uploads.php, ee-class-send.php)
        ├── $eeSFL = new eeSFL_MainClass()
        ├── $eeSFLU = new eeSFL_UploadClass()
        ├── $eeSFLE = new eeSFLE_class()
        ├── eeSFL_GetEnv() — detect OS, web server, PHP extensions, paths
        ├── eeSFL_GetRootPath() — resolve site root (handles managed host path offsets)
        ├── eeSFL_GetSettings(1) — load list settings from DB
        └── eeSFL_VersionCheck() — install/upgrade routine (admin only)

Shortcode [eeSFL]
  └── eeSFL_FrontEnd() in ee-front-end.php
        ├── Load file list array from DB
        ├── Apply access/display rules
        ├── eeSFL_ListDisplay() → table/flex/tiles renderer
        └── eeSFL_UploadForm() — if uploads enabled

AJAX: simplefilelist_edit_job
  └── eeSFL_FileEditor() in ee-functions.php
        ├── Verify nonce (eeSFL_ActionNonce)
        ├── Dispatch to eeSFL_DeleteFile() / eeSFL_RenameFile() / eeSFL_UpdateFile*()
        └── Update DB file array

AJAX: simplefilelist_upload_job
  └── simplefilelist_upload_job() → $eeSFLU->eeSFL_FileUploader()
```

---

## Naming Conventions

- **Functions**: `eeSFL_PascalCase()` — e.g. `eeSFL_VersionCheck()`, `eeSFL_FileEditor()`
- **Globals**: `$eeSFL`, `$eeSFLU`, `$eeSFLE`, `$eeSFLF` (always FALSE in Free)
- **DB options**: `eeSFL_Settings_1`, `eeSFL_FileList_1`, `eeSFL_Version`
- **AJAX actions**: `simplefilelist_upload_job`, `simplefilelist_edit_job`, `simplefilelist_sendfile_job`
- **WP action hooks**: `eeSFL_Hook_Uploaded`, `eeSFL_Hook_Deleted`, `eeSFL_Hook_Edited`, `eeSFL_Hook_Listed`
- **CSS IDs**: `#eeSFL` (wrapper), `#eeSFL_AdminMain`, `#eeSFL_ListOpsBarGo`, `#eeSFL_UploadForm`

---

## Key Patterns

### Filesystem Operations
All file I/O goes through `eeSFL_FileSystem($mode, $params)` in `ee-functions.php`. This wraps `WP_Filesystem` with a native PHP fallback for managed hosts (Pressable, Kinsta, WP Engine) where FTP credentials are unavailable at runtime. Never bypass with raw `file_get_contents()`, `rename()`, `unlink()`, etc.

```php
eeSFL_FileSystem('get_contents', array('file' => '/path/to/file'));
eeSFL_FileSystem('put_contents', array('file' => '/path', 'data' => $content));
eeSFL_FileSystem('move', array('from' => $old, 'to' => $new));
eeSFL_FileSystem('delete', array('file' => $path));
eeSFL_FileSystem('is_dir', array('path' => $dir));
eeSFL_FileSystem('mkdir', array('path' => $dir));
eeSFL_FileSystem('filemtime', array('file' => $path));
eeSFL_FileSystem('filesize', array('file' => $path));
eeSFL_FileSystem('dirlist', array('path' => $dir, 'include_hidden' => false, 'recursive' => false));
```

All return `array('success' => bool, 'data' => mixed)`.

### Settings
Stored in `get_option('eeSFL_Settings_1')`. Access via `$eeSFL->eeListSettings['Key']`. Defaults are defined in `$eeSFL->eeDefaultListSettings` inside `ee-class.php`. Always merge new defaults over existing settings on upgrade — never overwrite blindly.

### File List Array
Stored in `get_option('eeSFL_FileList_1')`. Each entry is an array with: `FilePath`, `FileName`, `FileExt`, `FileSize`, `FileDateAdded`, `FileDateChanged`, `FileNiceName`, `FileDescription`, `ItemCount` (folders only).

### Version / Update Routine
`eeSFL_VersionCheck()` in `ee-functions.php`:
1. Checks `eeSFL_Version` option first
2. Falls back to `eeSFL_BASE_Version` (used by 6.1.18 Free release)
3. If neither found → New Install path
4. If found but older → Update path (merge settings, clean up old options)
5. Deletes `eeSFL_BASE_Version` after migration

---

## Globals Always FALSE in Free

| Global | Purpose (Pro/extension only) |
|--------|------------------------------|
| `$eeSFLF` | Folder management |
| `$eeSFLS` | Search extension |
| `$eeSFLA` | Access / multi-list extension |

Code checks `if($eeSFLF)` etc. before calling extension methods — safe and intentional.

---

## Translations

```bash
wp i18n make-pot . languages/simple-file-list.pot \
  --domain=simple-file-list \
  --exclude=testing-guides
```

Then update each `.po` in Poedit: **Translation > Update from POT file**, save, and Poedit regenerates the `.mo`.
