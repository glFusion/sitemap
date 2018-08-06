# SiteMap Plugin for glFusion
Version: 2.0.1

For the latest documentation, please see

https://www.glfusion.org/wiki/glfusion:plugins:sitemap:start

## License
This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 2 of the License, or (at your option) any later
version.

## Description
The SiteMap plugin creates a Google compatible sitemap.xml file of your
site's content. It also provides an interactive Site Map for your site's
users. A mobile-compatible sitemap named "mobile.xml" can also be created.

SiteMap honors content permissions, only showing those items which the
user has permissions to view.

## System Requirements
SiteMap has the following system requirements:

    * PHP 5.6.0 and higher.
    * glFusion v1.6.0 or newer

## Installation
The SiteMap  Plugin uses the glFusion automated plugin installer.
Simply upload the distribtuion using the glFusion plugin installer located in
the Plugin Administration page.

## Upgrading
The upgrade process is identical to the installation process, simply upload
the distribution from the Plugin Administration page.

## Configuration
Global sitemap configuration is done through the glFusion Configuration panel.
There are currently only three configuration options:
1. Sitemap Name
    * A semicolon-delimited list of filenames to use for the XML sitemap files.
    * A filename beginning with "mobile" creates a mobile version of the sitemap.
    * Default: sitemap.xml;mobile.xml
1. Who can view the online sitemap
    * Determines whether anonymous users can view the HTML sitemap page at
    {site_url}/sitemap/.
    * Has no effect on access to the XML sitemap files.
    * Options:
      * All Users (Default)
      * Logged-In Users Only
      * No Access (Also disables the plugin menu)
1. Automatically add new plugins?
    * If "Yes", then newly-installed plugins which provide a sitemap driver will be added to the sitemaps.
    * Default: Yes
1. When to generate XML sitemaps
    * Always - regenerate with every scheduled task run
    * If Content Changes - regenerate during scheduled task runs but only if content has changed (recommended)
    * Manually - sitemaps are only regenerated when the Administrator does so via the admin panel

## Plugin Integration
A collection of sitemap drivers for the bundled plugins is included in the
Sitemap Plugin distribution.

A plugin may provide it's own driver to be used instead of the default driver
by including a class file named "plugin_name.class.php"
in the "sitemap" subdirectory under the plugin's private directory. Example:

    private/plugins/myplugin/sitemap/myplugin.class.php

The class must be named "<plugin_name>" and should extend
the BaseDriver class provided by this plugin. Each supported function
is provided in the base clase and returns a reasonable default to prevent errors.

Newly installed or removed plugins that include a sitemap drivers are
automatically added to or removed from the configuration each time the admin
page is accessed. New plugins are enabled and placed at the end of the sitemap.

Example `plugins/myplugin/sitemap/myplugin.class.php`:
```php
namespace Sitemap\Drivers;
class myplugin extends BaseDriver
{
    // Required. Define the plugin name.
    protected $name = 'myplugin';

    // Optional default value overrides. Sets the values when installed,
    // the site admin can change them via Command and Control
    public $xml_enabled = 1;    // Default enabled, 0 to disable
    public $html_enabled = 1;   // Default enabled, 0 to disable
    public $priority = '0.5';   // Default, set a different priority if desired

    /**
    *   Get the entry point for the plugin. Typically this is
    *   the index.php under public_html/plugin_name.
    *
    *   @return string      URL to plugin, default {site_url}/{myplugin}/index.php
    */
    public function getEntryPoint()
    {
        return 'http://mysite.com/myplugin/index.php';
    }


    /**
    *   Get the name of this sitemap driver. This is normally just the
    *   plugin name.
    *
    *   @return string      Name of driver (plugin), default is the plugin name
    */
    public function getName()
    {
        return $this->name;     // See static $name variable above.
    }


    /**
    *   Get the friendly name for this plugin. This could be taken from a
    *   languange file.
    *   This function should be overridden.
    *
    *   @return string      Plugin friendly name, default is the plugin name
    */
    public function getDisplayName()
    {
        return 'My Plugin Name';
    }


    /**
    *   Get all the items for this plugin under the given category ID.
    *   This function should be overridden. For an XML sitemap to work,
    *   all items should be returned when $cat_id == 0.
    *
    *   @param  mixed   $cat_id     Category ID
    *   @return array       Array of items, default is an empty array
    */
    public function getItems($cat_id = 0)
    {
        return array(
            array(
                'id'    => Item 1 ID,
                'title' => Item title,
                'uri'   => URL to item
                'date'  => Last update timestamp,
                'image_url => URL to item's image,
            ),
            array(
                'id'    => Item 2 ID,
                'title' => Item title,
                'uri'   => URL to item
                'date'  => Last update timestamp,
                'image_url => URL to item's image,
            ),
            // ... etc.
        );
    }


    /**
    *   Get all the categories under the given base category.
    *   Default: empty array
    *   This should be overridden unless there is no support for categories.
    *
    *   @param  mixed   $base   Base category
    *   @return array       Array of categories
    */
    public function getChildCategories($base = false)
    {
        return array(
            array(
                'id'    => Category 1 ID,
                'title' => Category title,
                'uri'   => URL to category display page,
                'date'  => Last updated date (False if not used),
                'image_uri' => URL to category image,
            ),
            array(
                'id'    => Category 2 ID,
                'title' => Category title,
                'uri'   => URL to category display page,
                'date'  => Last updated date (False if not used),
                'image_uri' => URL to category image,
            ),
            // ... etc.
        );
    }
}
```
