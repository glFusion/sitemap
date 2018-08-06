<?php
/**
*   Sitemap driver for the Mediagallery Plugin.
*
*   @author     Mark R. Evans <mark@glfusion.org>
*   @copyright  Copyright (c) 2009-2015 Mark R. Evans <mark@glfusion.org>
*   @copyright  Copyright (c) 2007-2008 Mystral-kk <geeklog@mystral-kk.net>
*   @package    glfusion
*   @version    2.0.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Sitemap\Drivers;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

class mediagallery extends BaseDriver
{
    protected $name = 'mediagallery';

    /**
    *   Determine if the current user has access to this plugin's sitemap
    *
    *   @return boolean     True if access is allowed, False if not
    */
    private function hasAccess()
    {
        global $_CONF, $_TABLES, $_MG_CONF;

        static $retval = null;

        static $loginRequired;

        if (is_null($retval)) {
            $loginrequired = (int)DB_getItem($_TABLES['mg_config'], 'config_value',
                        "config_name = 'loginrequired'");
            $retval = !($loginRequired && COM_isAnonUser());
        }
        return $retval;
    }


    /**
    *   Get the friendly display name for this plugin
    *
    *   @return string  Plugin Display Name
    */
    public function getDisplayName()
    {
        global $LANG_MG00;
        return $LANG_MG00['plugin'];
    }


    /**
    *   Returns the location of index.php of each plugin
    *
    *   @return string      Base URL to the plugin
    */
    public function getEntryPoint()
    {
        global $_MG_CONF;
        return $_MG_CONF['site_url'] . '/index.php';
    }


    /**
    * @param $pid int/string/boolean id of the parent category.  False means
    *        the top category (with no parent)
    * @return array(
    *   'id'        => $id (string),
    *   'pid'       => $pid (string: id of its parent)
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string)
    *  )
    */
    public function getChildCategories($pid = false)
    {
        global $_CONF, $_TABLES, $_MG_CONF;

        $entries = array();

        if (!$this->hasAccess()) {
            return $entries;
        }

        if ($pid === false) {
            $pid = 0;
        }

        $sql = "SELECT album_id, album_title, album_parent, last_update
                FROM {$_TABLES['mg_albums']}
                WHERE (album_parent = '" . DB_escapeString($pid) . "') ";
        if ($this->uid > 0) {
            $sql .= COM_getPermSQL('AND ', $this->uid)
                 .  " AND (hidden = '0') ";
        }
        $sql .= " ORDER BY album_order";
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_mediagallery::getChildCategories() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['album_id'],
                'pid'       => $A['album_parent'],
                'title'     => $A['album_title'],
                'uri'       => $_MG_CONF['site_url'] . '/album.php?aid='
                                . $A['album_id'],
                'date'      => $A['last_update'],
                'image_uri' => false,
            );
        }
        return $entries;
    }


    /**
    *   Get the items under a given category ID
    *
    * Returns an array of (
    *   'id'        => $id (string),
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string)
    * )
    */
    public function getItems($category = false)
    {
        global $_CONF, $_TABLES, $_MG_CONF, $LANG_SMAP;

        $entries = array();
        $category = (int)$category;

        if (!$this->hasAccess()) {
            return $entries;
        }

        $sql = "SELECT a.media_id, m.media_title, m.media_time
                FROM {$_TABLES['mg_media_albums']} a
                LEFT JOIN {$_TABLES['mg_media']} m
                    ON a.media_id = m.media_id ";
        if ($category > 0) {
            $sql .= " WHERE a.album_id = '" . DB_escapeString($category) . "'";
        }
        $sql .= " ORDER BY a.media_order";
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_mediagallery::getItems() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            if (empty($A['media_title'])) {
                $A['media_title'] = $LANG_SMAP['untitled'];
            }
            $entries[] = array(
                'id'        => $A['media_id'],
                'title'     => $A['media_title'],
                'uri'       => $_MG_CONF['site_url'] . '/media.php?s='
                                . urlencode($A['media_id']),
                'date'      => $A['media_time'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
