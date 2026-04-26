Place jquery.Thailand.js assets here for full-country Thai address autocomplete.

Required files:
1) db.json
2) jquery.Thailand.min.js
3) JQL.min.js
4) typeahead.bundle.js
5) zip.js
6) jquery.Thailand.min.css
Optional:
7) raw_database.json (from raw_database/raw_database.json in the upstream repo)

Current page uses:
- Database URL: /vendor/jquery-thailand/db.json
- JS/CSS local-first with CDN fallback in resources/views/book.blade.php
- Fallback autocomplete will auto-load /vendor/jquery-thailand/raw_database.json when present

If you want full offline mode, also switch JS/CSS script/link tags to local files in this folder.
