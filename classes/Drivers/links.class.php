<?php
// +--------------------------------------------------------------------------+
// | Data Proxy Plugin for glFusion                                           |
// +--------------------------------------------------------------------------+
// | links.class.php                                                          |
// |                                                                          |
// | Links Plugin interface                                                   |
// +--------------------------------------------------------------------------+
// | Copyright (C) 2009-2015 by the following authors:                        |
// |                                                                          |
// | Mark R. Evans          mark AT glfusion DOT org                          |
// |                                                                          |
// | Based on the Data Proxy Plugin                                           |
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
namespace Sitemap\Drivers;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**
* Links plugin supports URL rwrite in individual links but doesn't do so in
* categories, e.g.:
*
* link     (off) http://www.example.com/links/portal.php?what=link&amp;item=glfusion.org
*          (on)  http://www.example.com/links/portal.php/link/glfusion.org
* category (off) http://www.example.com/links/index.php?category=glfusion-site
*          (on)  http://www.example.com/links/index.php?category=glfusion-site
*/

class links extends BaseDriver
{
    protected $name = 'links';

    public function getDisplayName()
    {
        global $LANG_LINKS;
        return $LANG_LINKS[14];
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
        global $_CONF, $_TABLES;

        $entries = array();
        $sql = "SELECT * FROM {$_TABLES['linkcategories']}";
        if ($pid === false) {
            $pid = 'root';
        }
        $sql .= " WHERE (pid = '" . DB_escapeString($pid) . "') ";

        if ($this->uid > 0) {
            $sql .= COM_getPermSQL('AND', $this->uid);
        }

        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_links::getChildCategories() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['cid'],
                'pid'       => $A['pid'],
                'title'     => $A['category'],
                'uri'       => self::getEntryPoint() . '?category='.urlencode($A['cid']),
                'date'      => strtotime($A['modified']),
                'image_uri' => false,
            );
        }
        return $entries;
    }


    /**
    * Returns an array of (
    *   'id'        => $id (string),
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string)
    * )
    */
    public function getItems($category = 0)
    {
        global $_CONF, $_TABLES;

        $entries = array();

        $sql  = "SELECT lid, title, UNIX_TIMESTAMP(date) AS date_u
                FROM {$_TABLES['links']} WHERE 1=1 ";
        if (!empty($category)) {
            $sql .= "AND (cid ='".DB_escapeString($category)."') ";
        }

        if ($this->uid > 0) {
            $sql .= COM_getPermSQL('AND', $this->uid);
        }
        $sql .= "ORDER BY date_u DESC";
        $result  = DB_query($sql);
        if (DB_error()) {
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['lid'],
                'title'     => $A['title'],
                'uri'       => COM_buildURL(
                    $_CONF['site_url'] . '/links/portal.php?what=link&amp;item='
                    . urlencode($A['lid'])
                ),
                'date'      => $A['date_u'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
