# UM Directory Dual Sort
Extension to Ultimate Member for a second sort key in the Directory and an option for local language (non-english UTF8) characters collation. The plugin only supports UM using "Custom usermeta table"

## UM Member Directories -> Select your Directory
1. Primary and secondary meta keys and sorting order - Enter the primary (UM enabled for sorting) and plugin secondary meta keys. Add the Sort order (ASC/DESC) and Type  (CHAR/DATE) of the secondary meta key. All parameters colon separated and one settings pair per line.
2. Primary and secondary meta keys non-english UTF8 character collation - Character Sets and Collations in MySQL Current database setting:
3. Example: <code>city:birth_date:ASC:DATE</code>

## Known issues
1. Sorting order changes by the browser when there are Profile cards with different height sizes in grid mode, use list mode for verifying sorting.

## Translations or Text changes
1. Use the "Say What?" plugin with text domain ultimate-member
2. https://wordpress.org/plugins/say-what/

## References
1. Character Sets and Collations in MySQL: https://dev.mysql.com/doc/refman/8.4/en/charset-mysql.html
2. Custom usermeta table: https://docs.ultimatemember.com/article/1902-advanced-tab#Features

## Updates
None

## Installation & Updates
1. Install and update by downloading the plugin ZIP file via green "Code" button
2. Install as a new Plugin, which you upload in WordPress -> Plugins -> Add New -> Upload Plugin.
3. Activate the Plugin
4. At first installation tick the UM Settings -> Advanced -> Features -> "Custom usermeta table" and "Enable custom table for usermeta"
