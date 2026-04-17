=== CSS Organizer ===
Contributors: apos37
Tags: css, customizer, organization, styles, preview
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Organize Customizer CSS into separate sections for a cleaner, more manageable editing experience.

== Description ==
**CSS Organizer** adds structured CSS sections to the WordPress Customizer so you can keep your styles organized while still using the built-in live preview.

Instead of placing everything inside the single **Additional CSS** field, this plugin lets you separate your styles into multiple labeled sections. This makes it easier to maintain and locate specific CSS as your site grows.

The plugin adds its own CSS sections underneath the default **Additional CSS** panel. It does not modify the native field. If you prefer a simpler interface, you can optionally disable the default Additional CSS panel from the plugin settings.

The plugin ships with several default sections to get started, but you can remove them, reorder them, or create your own sections from the settings page.

**Features:**
- Multiple organized CSS sections inside the Customizer (or Site Editor for block themes)
- Option to force adding the WP Customizer on block themes
- Default sections included, fully customizable
- Create, remove, and reorder sections from the settings page
- CSS stored separately per section for better organization
- Optional ability to disable the default Additional CSS panel
- Expand the Customizer editor width to 30%, 50%, or 80% of the screen
- Quickly add @media snippets for various screen sizes
- Body class selector tool
- Variable picker for pasting variables you've set in other sections
- Export/import all settings and CSS from one site to another

This plugin is useful for web designers and site builders who want better CSS organization while still using the WordPress Customizer’s live preview.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/css-organizer/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Appearance > CSS Organizer to set up sections

== Frequently Asked Questions ==
= How does this plugin help with CSS management? =
By allowing you to create separate sections for your CSS, you can easily organize your styles according to specific categories, which reduces clutter and enhances usability.

= Is this plugin compatible with all themes? =
CSS Organizer will be added to any theme that supports the WordPress Customizer. Block themes have the option of adding sections to the Site Editor's template editor or force adding the WordPress Customizer if you prefer.

== Demo ==
https://youtu.be/ySmYpACb8W8

== Screenshots ==
1. Easily add and sort sections from the settings page under Appearance > CSS Organizer
2. Customizer options with CSS Organizer at the bottom
3. Customizer CSS Organizer sections
4. Customizer CSS Organizer section
5. Expand the customizer to 30%, 50% or 80% of the screen for easier editing
6. Body class selector
7. Variable picker allows you to quickly paste variables you set in other sections
8. CSS sections in Site Editor template editor in block themes without forcing WP Customizer

== Changelog ==
= 1.2.0 =
* Update: Added sourceURL to all styles so you can easily back-track to which section the css is in from the DevConsole (ie. CSS-Organizer/section_name.css)
* Update: Added support for block themes on Site Editor, with option to still force the Customizer
* Update: Added mobile screen size buttons to WP Customizer
* Update: Added a body tags menu to WP Customizer sections that lets you add body classes from a dropdown
* Update: Added a variable selector to WP Customizer sections that finds all variables in your css sections and lets you add them to your current section
* Update: Added an uninstall cleanup option
* Update: Restyled the settings page
* Update: Added theme info to settings page
* Update: Added an export/import option on the settings page
* Update: Transitioned plugin from premium distribution to a free release on the WordPress.org repository.
* Update: Revised plugin descriptions and documentation for the WordPress.org release.

= 1.1.0 =
* Update: New support links
* Update: Added a hook to allow changing the expand width percentages

= 1.0.2 =
* Update: Updated author name and website per WordPress trademark policy

= 1.0.1 =
* Initial Release