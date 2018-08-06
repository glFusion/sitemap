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

/** Import the configuration class, if needed */
global $_SMAP_CONF;
require_once $_CONF['path_system'] . 'classes/config.class.php';
$config = config::get_instance();
$_SMAP_CONF = $config->get_config('sitemap');
require_once __DIR__ . '/sitemap.php';

/** Import static config values */
if (file_exists(__DIR__ . '/config.php')) {
    /** Import local configuration overrides, if any */
    include_once __DIR__ . '/config.php';
}

spl_autoload_register(function ($class)
{
    $path = '';
    if (strpos($class, 'Sitemap\\Drivers\\') === 0) {
        // Loading a driver class. Could be bundled with this plugin
        // or provided by another, so get the first path found.
        $class = str_replace('Sitemap\\Drivers\\', '', $class);
        $path = Sitemap\Config::getDriverPath($class, false);   // no legacy
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

    if (SMAP_canView() ) {
        return array(
            SITEMAP_str('menu_label') => $_CONF['site_url'] . '/sitemap/index.php',
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
            SITEMAP_str('menu_label'),
            $_CONF['site_admin_url'] . '/plugins/sitemap/index.php',
            $_CONF['site_url'] . '/sitemap/images/sitemap.png',
        );
    } else {
        return array();
    }
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
            SITEMAP_str('menu_label'),
            $_CONF['site_admin_url'] . '/plugins/sitemap/index.php',
            $num_items = '',
        );
    } else {
        return array();
    }
}


/**
*   Returns the current code version for this plugin
*
*   @return string VersionNo
*/
function plugin_chkVersion_sitemap()
{
    global $_SMAP_CONF;
    return $_SMAP_CONF['pi_version'];
}


/**
*   Returns the user option for this plugin. There is none.
*
*   @return   array   Array containing (plugin name, plugin user url, 0)
*/
function plugin_getuseroption_sitemap()
{
    return false;
}


/**
*   Upgrade the plugin
*
*   @return   boolean true (= success)
*/
function plugin_upgrade_sitemap()
{
    global $_CONF, $_TABLES, $_SMAP_CONF;
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
    global $_CONF, $_SMAP_CONF;

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
* Escapes a string for HTML output
*/
function SITEMAP_escape($str) {
    $str = str_replace(
        array('&lt;', '&gt;', '&amp;', '&quot;', '&#039;'),
        array(   '<',    '>',     '&',      '"',      "'"),
        $str
    );
    return htmlspecialchars($str, ENT_QUOTES, COM_getEncodingt());
}

function SITEMAP_str($index, $noesc = false) {
    global $LANG_SMAP;

    if (isset($LANG_SMAP[$index])) {
        if ($noesc) {
            return $LANG_SMAP[$index];
        } else {
            return SITEMAP_escape($LANG_SMAP[$index]);
        }
    } else {
        COM_errorLog('SITEMAP_str: undefined index "' . $index . '".');
        return '(undefined)';
    }
}


/**
*   Converts a timestamp into ISO8601 format
*
*   @param  integer $timestamp  Unix timestamp value
*   @return string      Formatted timestamp
*/
function SITEMAP_convertDate($timestamp)
{
    global $_CONF;

    static $D = NULL;   // save some effort during repeat calls

    if ($D === NULL) {
        $D = new Date($timestamp, $_CONF['timezone']);
    } else {
        $D->setTimeStamp($timestamp);
    }
    return $D->format('c', true);
}


/**
*   Write out the XML Sitemap
*
*   @param  string  $filename   Filename to write
*   @param  string  $sitemap    Sitemap content
*   @return boolean     True on success, False on failure
*/
function SITEMAP_write($filename, $sitemap)
{
    global $_CONF, $_SMAP_CONF;

    $retval = false;
    $filename = trim($filename);
    if (empty($filename)) return false;

    /**
    *   Alterations for Google Mobile Sitemap
    */
    if (preg_match("/mobile/i", $filename)) {
        $sitemap = str_replace(
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
            '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84" xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0">',
            $sitemap
        );
        $sitemap = str_replace(
            '</url>',
            '  <mobile:mobile/>' . "\n" . '  </url>',
            $sitemap
        );
    }

    $path = $_CONF['path_html'] . basename($filename);
    clearstatcache();
    if (!file_exists($path)) {
        $fh = @fopen($path, 'wb');
        if ($fh === false) {
            COM_errorLog('Sitemap: cannot create Google sitemap file.  Please create the file "' . $path . '" manually.');
            return false;
        }
    } else {
        $fh = @fopen($path, 'r+b');
        if ($fh === false) {
            COM_errorLog('Sitemap: Google sitemap file is NOT writable: ' . $path);
            return false;
        }
    }

    if (flock($fh, LOCK_EX) === true) {
        ftruncate($fh, 0);
        rewind($fh);
        $result = @fwrite($fh, $sitemap);
        if (($result === false) OR ($result != strlen($sitemap))) {
            COM_errorLog('Sitemap: cannot write into Google sitemap file fully: ' . $path);
        } else {
            $retval = true;
        }
        @flock($fh, LOCK_UN);
    } else {
        COM_errorLog('Sitemap: cannot write-lock Google sitemap file: ' . $path);
    }
    @fclose($fh);
    return $retval;
}


/**
*   Creates the Google Sitemap files.
*   No return value.
*   Does not use the Cache class since the XML sitemap file is effectively
*   the item cache.
*
*   @uses   SITEMAP_write()
*/
function SITEMAP_createGoogleSitemap()
{
    global $_CONF, $_SMAP_CONF;

    $st = ini_get('short_open_tag');
    if( $st ) {
        COM_errorLog("SITEMAP: Unable to create XML sitemap due to PHP configuration - 'short_open_tag' must be set to Off");
        return false;
    }

    $drivers = Sitemap\Config::getDrivers();

    // Reset the counter in gl_vars. This is done first to handle the unlikely
    // case where other content is saved while the sitemap is running.
    // This is done regardless of the config setting so that forced runs will
    // clear the counters also.
    SITEMAP_updateCounter(0);

    $retval = false;
    $T = new Template(__DIR__ . '/templates');
    $T->set_file('smap', 'smap_google.thtml');

    $T->set_block('smap', 'smap_item', 'item');

    foreach ($drivers as $driver) {
        $key = $driver->getName() . '_xml';
        if ($driver->xml_enabled) {
            // Only add XML-enabled items
            $driver->setXML();   // Mark as an XML sitemap
            $items = $driver->getItems();
        } else {
            // Delete the cached XML sitemap in case xml was disabled
            $items = NULL;
        }

        // No driver found, or no items returned
        if (empty($items)) continue;

        foreach ($items as $item) {
            if ($item['date'] !== false) {
                $date = SITEMAP_convertDate($item['date']);
            } else {
                $date = SITEMAP_convertDate(time());
            }
            $T->set_var(array(
                'url'   => $item['uri'],
                'date'  => $date,
                'freq'  => $driver->freq,
            ) );
            if ($driver->priority != '0.5') {
                $T->set_var('priority', $driver->priority);
            }
            $T->parse('item', 'smap_item', true);
        }
    }
    $T->parse('output', 'smap');
    $sitemap = $T->finish($T->get_var('output'));

    // Writes the Google Sitemap
    foreach (explode(';', $_SMAP_CONF['xml_filenames']) as $filename) {
        $retval = ($retval || !SITEMAP_write($filename, $sitemap));
    }
    return !$retval;
}


/**
*   Check whether a visitor has access to view the online sitemap
*   Also will return false if there are no sitemaps enabled.
*
*   @return boolean     True if access is allowed, False if not
*/
function SMAP_canView()
{
    global $_CONF, $_SMAP_CONF, $_TABLES;
    static $retval = NULL;

    if ($retval === NULL) {
        $retval = true;
        switch ($_SMAP_CONF['view_access']) {
        case 0:     // No user access to the URL
            $retval = false;
            break;
        case 1:     // Logged-in users only
            if (COM_isAnonUser()) {
                $retval  = false;
            }
            break;
        case 2:     // All users allowed, but check for loginrequred
        default:
            if ( COM_isAnonUser() && $_CONF['loginrequired'] ) {
                $retval = false;
            }
        }

        if ($retval === true) {
            // If the guest has access, check if there are any items to show
            $active = DB_count($_TABLES['smap_maps'],'html_enabled',1);
            if ($active < 1) {
                $retval = false;
            }
        }
    }
    return $retval;
}


/**
*   Create the sitemap as part of scheduled tasks, if so configured.
*/
function plugin_runScheduledTask_sitemap()
{
    global $_SMAP_CONF, $_VARS;

    switch ($_SMAP_CONF['schedule']) {
    case 0:     // run with every schedule task call
        SITEMAP_createGoogleSitemap();
        break;
    case 1:     // run only if there are changes
        if ($_VARS['sitemap_changes'] > 0) {
            SITEMAP_createGoogleSitemap();
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
    global $_SMAP_CONF, $_SMAP_CONF;

    $configs = Sitemap\Config::getAll();
    if ($_SMAP_CONF['schedule'] == 1 &&
        isset($configs[$type]) && $configs[$type]['xml_enabled']) {
        SITEMAP_updateCounter();
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
    global $_SMAP_CONF;

    Sitemap\Cache::clear($type);
    if ($_SMAP_CONF['schedule'] == 1) {
         SITEMAP_updateCounter();    // To force regeneration
    }
    Sitemap\Cache::clear($type);
}


/**
*   Update the vars table with the number of changes, or 0 to clear
*
*   @param  integer $cnt    Update count, either 1 to add or 0 to clear
*/
function SITEMAP_updateCounter($cnt = 1)
{
    global $_TABLES;

    $change = $cnt == 0 ? '0' : 'value + 1';
    DB_query("UPDATE {$_TABLES['vars']}
            SET value = $change
            WHERE name = 'sitemap_changes'");
}

?>
