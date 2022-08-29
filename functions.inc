<?php
// +--------------------------------------------------------------------------+
// | Site Map Plugin for glFusion                                             |
// +--------------------------------------------------------------------------+
// | functions.inc                                                            |
// |                                                                          |
// | glFusion Plugin API functions and support functions                      |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2018 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// | Lee Garner             lee AT leegarner DOT com                          |
// |                                                                          |
// | Based on the Site Map Plugin                                             |
// | Copyright (C) 2007-2008 by the following authors:                        |
// |                                                                          |
// | Authors: mystral-kk        - geeklog AT mystral-kk DOT net               |
// +--------------------------------------------------------------------------+
// |                                                                          |
// | This program is free software; you can redistribute it and/or            |
// | modify it under the terms of the GNU General Public License              |
// | as published by the Free Software Foundation; either version 2           |
// | of the License, or (at your option) any later version.                   |
// |                                                                          |
// | This program is distributed in the hope that it will be useful,          |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            |
// | GNU General Public License for more details.                             |
// |                                                                          |
// | You should have received a copy of the GNU General Public License        |
// | along with this program; if not, write to the Free Software Foundation,  |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.          |
// |                                                                          |
// +--------------------------------------------------------------------------+

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
* Language file Include
*/
$langfile = __DIR__ . '/language/'.$_CONF['language'].'.php';
clearstatcache();
if (file_exists($langfile)) {
    include_once $langfile;
} else {
    include_once __DIR__ . '/language/english.php';
}

if (version_compare(GVERSION,'2.0.0','lt')) {
    if (isset($LANG_configSelect['sitemap']) && !isset($LANG_configselects['sitemap'])) {
        foreach ($LANG_configSelect['sitemap'] AS $cfgItem) {
            $LANG_configselects['sitemap'][] = array_flip($cfgItem);
        }
    }
}

spl_autoload_register(function ($class)
{
    $path = '';
    if (strpos($class, 'Sitemap\\Drivers\\') === 0) {
        // Loading a driver class. Could be bundled with this plugin
        // or provided by another, so get the first path found.
        $class = str_replace('Sitemap\\Drivers\\', '', $class);
        $path = Sitemap\Plugin::getDriverPath($class, false);   // no legacy
    } elseif (strpos($class, 'Sitemap\\Models\\') === 0) {
        // Loading an internal model class.
        $class = str_replace('Sitemap\\Models\\', '', $class);
        $path = __DIR__ . '/classes/Models/' . $class . '.class.php';
    } elseif (strpos($class, 'Sitemap\\') === 0) {
        // Loading an internal class. Eventually all classes should be
        // namespaced.
        $class = str_replace('Sitemap\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
    } elseif ($class == 'sitemap_base') {
        // Legacy mode. Loading the base driver for plugins that supply
        // their own driver and use "extends sitemap_base"
        $path = __DIR__ . '/classes/' . $class . '.class.php';
    } elseif (strpos($class, 'sitemap_') === 0) {
        // Legacy mode. Load the sitemap_pluginname driver.
        $class = str_replace('sitemap_', '', $class);
        $path = $_CONF['path'] . 'plugins/' . $class . '/sitemap/' . $class . '.class.php';
    } else {
        // Not a sitemap class, so just return.
        return;
    }

    if (is_file($path)) {
        include $path;
    }
});
use glFusion\Database\Database;
use glFusion\Log\Log;
use Sitemap\Config;
use Sitemap\Sitemap;
Config::getInstance();
require_once __DIR__ . '/sitemap.php';

/** Import static config values */
if (file_exists(__DIR__ . '/config.php')) {
    /** Import local configuration overrides, if any */
    include_once __DIR__ . '/config.php';
}


// +---------------------------------------------------------------------------+
// | glFusion Plugin API Implementations                                       |
// +---------------------------------------------------------------------------+

/**
*   Returns the items for this plugin that should appear on the main menu
*
*   @return    array   Array of text=>url values.
*/
function plugin_getmenuitems_sitemap()
{
    global $_CONF;

    if (Sitemap::canView() ) {
        return array(
            Sitemap::str('menu_label') => $_CONF['site_url'] . '/sitemap/index.php',
        );
    }
    return array();
}


/**
*   Check to see if this Plugin uses comments. It doesn't.
*
*   @return   boolean true or false
*/
function plugin_commentsupport_sitemap()
{
    return false;
}


/**
*   This will put an option for the plugin in the command and control block on
*   moderation.php
*
*   @return array   Array of (plugin name, admin url, url of plugin icon)
*/
function plugin_cclabel_sitemap() {
    global $_CONF;

    if (SEC_hasRights('sitemap.admin')) {
        return array(
            Sitemap::str('menu_label'),
            $_CONF['site_admin_url'] . '/plugins/sitemap/index.php',
            $_CONF['site_url'] . '/sitemap/images/sitemap.png',
        );
    } else {
        return array();
    }
}


/**
 * Get the URL to the plugin icon.
 */
function plugin_geticon_sitemap() : string
{
    global $_CONF;
    return $_CONF['site_url'] . '/sitemap/images/sitemap.png';
}


/**
*   Returns the administrative menu option for this plugin
*
*   @return array   Array of (plugin name, plugin admin url, # of items
*                   in plugin or '')
*/
function plugin_getadminoption_sitemap()
{
    global $_CONF, $_TABLES;

    if (SEC_hasRights('sitemap.admin')) {
        return array(
            Sitemap::str('menu_label'),
            $_CONF['site_admin_url'] . '/plugins/sitemap/index.php',
            $num_items = '',
        );
    } else {
        return array();
    }
}


/**
 * Returns the current code version for this plugin.
 *
 * @return  string  Plugin version
 */
function plugin_chkVersion_sitemap()
{
    return Config::get('pi_version');
}


/**
*   Upgrade the plugin
*
*   @return   boolean true (= success)
*/
function plugin_upgrade_sitemap()
{
    require_once __DIR__ . '/upgrade.php';
    return sitemap_upgrade();
}


/**
*   Get the URL to the configuration help file
*
*   @param  string  $doclang    Document language
*   @return string              URL to document
*/
function plugin_getconfigelementhelp_sitemap($option, $doclang='english')
{
    global $_CONF;

    $doc_path = $_CONF['path_html'] . "/docs/$doclang/config.html";
    if (!is_file($doc_path )) {
        $doclang = 'english';
    }
    $doc_url = $_CONF['site_url'] . '/' . 'sitemap' .
        '/docs/' . $doclang . '/config.html';

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
*   Create the sitemap as part of scheduled tasks, if so configured.
*/
function plugin_runScheduledTask_sitemap()
{
    global $_VARS;

    switch (Config::get('schedule')) {
    case 0:     // run with every schedule task call
        Sitemap::createGoogleSitemap();
        break;
    case 1:     // run only if there are changes
        if ($_VARS['sitemap_changes'] > 0) {
            Sitemap::createGoogleSitemap();
        }
        break;
    default:
        break;
    }
}


/**
*   Update the counter in gl_vars when content is changed.
*
*   @param  string  $id     Item ID (unused)
*   @param  string  $type   Item Type (unused)
*   @param  string  $old_id Old Item ID (unused)
*/
function plugin_itemsaved_sitemap($id, $type, $old_id = '')
{
    $configs = Sitemap\Plugin::getAll();
    if (
        Config::get('schedule') == 1 &&
        isset($configs[$type]) && $configs[$type]['xml_enabled']
    ) {
        Sitemap::updateCounter();
    }
    Sitemap\Cache::clear($type);
}


/**
*   Handle deletion of content items.
*   Clears the cache for the type of item deleted so the sitemap will be
*   rebuilt with current data.
*
*   @param  string  $id     Item ID (not used)
*   @param  string  $type   Type of item, e.g. plugin name
*   @param  boolean $children   Not used
*/
function plugin_itemdeleted_sitemap($id, $type, $children)
{
    Sitemap\Cache::clear($type);
    if (Config::get('schedule') == 1) {
        Sitemap::updateCounter();    // To force regeneration
    }
    Sitemap\Cache::clear($type);
}

