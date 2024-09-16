<?php
/**
 * Handle creating and writing sitemap files.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2022 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

namespace Sitemap;
use glFusion\Database\Database;
use glFusion\Log\Log;


class Sitemap
{
    /**
     * Write out the XML Sitemap.
     *
     * @param  string  $filename   Filename to write
     * @param  string  $sitemap    Sitemap content
     * @return boolean     True on success, False on failure
     */
    public static function write(string $filename, string $sitemap) : bool
    {
        global $_CONF;

        $retval = false;
        $filename = trim($filename);
        if (empty($filename)) {
            return false;
        }

        // Alterations for Google Mobile Sitemap
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

        // todo - set path to data subdirectory
        $path = $_CONF['path_rss'] . basename($filename);
        clearstatcache();
        if (!file_exists($path)) {
            $fh = @fopen($path, 'wb');
            if ($fh === false) {
                Log::write(
                    'system',
                    Log::ERROR, 
                    'Sitemap: cannot create Google sitemap file.  Please create the file "' . $path . '" manually.'
                );
                return false;
            }
        } else {
            $fh = @fopen($path, 'r+b');
            if ($fh === false) {
                Log::write('system', Log::ERROR, 'Sitemap: Google sitemap file is NOT writable: ' . $path);
                return false;
            }
        }

        if (flock($fh, LOCK_EX) === true) {
            ftruncate($fh, 0);
            rewind($fh);
            $result = @fwrite($fh, $sitemap);
            if (($result === false) || ($result != strlen($sitemap))) {
                Log::write('system', Log::ERROR, 'Sitemap: cannot write into Google sitemap file fully: ' . $path);
            } else {
                $retval = true;
            }
            @flock($fh, LOCK_UN);
        } else {
            Log::write('system', Log::ERROR, 'Sitemap: cannot write-lock Google sitemap file: ' . $path);
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
    public static function createGoogle() : bool
    {
        global $_CONF;

        $st = ini_get('short_open_tag');
        if( $st ) {
            Log::write(
                'system',
                Log::ERROR, 
                "SITEMAP: Unable to create XML sitemap due to PHP configuration - 'short_open_tag' must be set to Off"
            );
            return false;
        }

        $drivers = Plugin::getDrivers();

        // Reset the counter in gl_vars. This is done first to handle the unlikely
        // case where other content is saved while the sitemap is running.
        // This is done regardless of the config setting so that forced runs will
        // clear the counters also.
        self::updateCounter(0);

        $retval = false;
        $T = new \Template($_CONF['path'] . 'plugins/sitemap/templates');
        $T->set_file('smap', 'smap_google.thtml');

        $T->set_block('smap', 'smap_item', 'item');

        foreach ($drivers as $driver) {
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
                    $date = self::convertDate($item['date']);
                } else {
                    $date = self::convertDate(time());
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
        foreach (explode(';', Config::get('xml_filenames')) as $filename) {
            $retval = ($retval || !self::write($filename, $sitemap));
        }
        return !$retval;
    }

    
    /**
    *   Converts a timestamp into ISO8601 format
    *
    *   @param  integer $timestamp  Unix timestamp value
    *   @return string      Formatted timestamp
    */
    public static function convertDate(int $timestamp) : string
    {
        global $_CONF;

        static $D = NULL;   // save some effort during repeat calls

        if ($D === NULL) {
            $D = new \Date($timestamp, $_CONF['timezone']);
        } else {
            $D->setTimeStamp($timestamp);
        }
        return $D->format('c', true);
    }

    
    /**
     * Escapes a string for HTML output.
     *
     * @param   string  $str    Original string
     * @return  string      Escaped string
     */
    public static function escape(string $str) : string
    {
        $str = str_replace(
            array('&lt;', '&gt;', '&amp;', '&quot;', '&#039;'),
            array(   '<',    '>',     '&',      '"',      "'"),
            $str
        );
        return htmlspecialchars($str, ENT_QUOTES, COM_getEncodingt());
    }


    /**
     *   Check whether a visitor has access to view the online sitemap
     *   Also will return false if there are no sitemaps enabled.
     *
     *   @return boolean     True if access is allowed, False if not
     */
    public static function canView() : bool
    {
        global $_CONF, $_TABLES;
        static $retval = NULL;

        if ($retval === NULL) {
            $retval = true;
            switch (Config::get('view_access')) {
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
                $db = Database::getInstance();
                $active = $db->getCount($_TABLES['smap_maps'], 'html_enabled', 1, Database::INTEGER);
                if ($active < 1) {
                    $retval = false;
                }
            }
        }
        return $retval;
    }

    
    /**
     *   Returns options list for a frequency selection
     *
     *   @param  string  $selected   Optional value to mark selected
     *   @return string      Options to be placed between <select> tags
     */
    public static function getFreqOptions($selected='') : string
    {
        global $LANG_SMAP;

        $retval = '';

        foreach ($LANG_SMAP['freqs'] as $key=>$text) {
            $sel = $key == $selected ? 'selected="selected"' : '';
            $retval .= '<option value="' . $key . '" ' . $sel . '>' .
                $text . '</option>' . LB;
        }
        return $retval;
    }

    /**
     *   Returns options list for a priority selection
     *
     *   @param  string  $selected   Optional value to mark selected
     *   @return string      Options to be placed between <select> tags
     */
    public static function getPriorityOptions($selected='') : string
    {
        $retval = '';

        foreach (Config::get('priorities') as $value) {
            $sel = $value == $selected ? 'selected="selected"' : '';
            $retval .= '<option value="' . $value. '" ' . $sel . '>' .
                $value . '</option>' . LB;
        }
        return $retval;
    }


    /**
     *   Update the vars table with the number of changes, or 0 to clear
     *
     *   @param  integer $cnt    Update count, either 1 to add or 0 to clear
     */
    public static function updateCounter(int $cnt = 1) : void
    {
        global $_TABLES;

        $change = $cnt == 0 ? '0' : 'value + 1';
        try {
            Database::getInstance()->conn->update(
                $_TABLES['vars'],
                array('value' => $change),
                array('name' => 'sitemap_changes'),
                array(Database::STRING, Database::STRING)   // to match schema
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
        }
    }

}

