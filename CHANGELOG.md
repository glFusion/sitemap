# SiteMap Plugin ChangeLog

## v2.0.5

- glFusion LTS and v2 support
- Upgrade referenced incorrect class name
- Minor code cleanup
- Czech translation

## v2.0.4

- PHP v8 compatibility fixes

## v2.0.3

- Fixed install / uninstall error where Cache class may not be available via auto loader

## v2.0.2

- Use glFusion date class
- Fixed error in Calendar driver calculating date
- Implement caching for glFusion 2.0.0+
- When an item is saved, trigger sitemap recreation only if enabled for that item type
- Remove global variable for configurations
- Use PHP namespace. Plugin drivers may still be loaded from the global namespace.

## v2.0.1

- PHP v7.x compatibility fixes
- Allow drivers to set default xml_enabled, html_enabled and priority values
- Fix SQL query for Links XML sitemap
- Upgrade cleanup
- Add config option to create sitemaps manually or only if content changes

## v2.0.0

- Remove dependency on the Dataproxy plugin.
- Move global configuration items to the glFusion configuration system
- Use standard admin lists with AJAX for sitemap element configurations
- Dynamically add and remove plugin sitemap configurations as plugins are added or removed.
- Include drivers for bundled plugins which can be overridden by plugin-supplied ones.
- Access to the online sitemap can be given to all users or logged-in only, or disabled completely.
