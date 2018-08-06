<?php
// +--------------------------------------------------------------------------+
// | Data Proxy Plugin for glFusion                                           |
// +--------------------------------------------------------------------------+
// | filemgmt.class.php                                                       |
// |                                                                          |
// | FileMgmt Plugin interface                                                |
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

class filemgmt extends BaseDriver
{
    protected $name = 'filemgmt';

    public function getDisplayName()
    {
        global $LANG_FILEMGMT;
        return $LANG_FILEMGMT['plugin_name'];
    }


    /**
    * @param $pid int/string/boolean id of the parent category
    * @param $current_groups array ids of groups the current user belongs to
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
        global $_CONF, $_TABLES, $_TABLES;

        $entries = array();

        if ($pid === false) {
            $pid = 0;
        }

        $sql = "SELECT * FROM {$_TABLES['filemgmt_cat']}
                WHERE (pid = '" . DB_escapeString($pid) . "') ";
        if ($this->uid > 0) {
            $sql .= SEC_buildAccessSql();
        }
        $sql .= ' ORDER BY cid';
        //echo $sql;die;
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_filemgmt::getChildCategories error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => (int)$A['cid'],
                'pid'       => (int)$A['pid'],
                'title'     => $A['title'],
                'uri'       => $_CONF['site_url'] . '/filemgmt/viewcat.php?cid='
                                . $A['cid'],
                'date'      => false,
                'image_uri' => $A['imgurl'],
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
    public function getItems($cid = false)
    {
        global $_CONF, $_TABLES, $_TABLES;

        if ($cid === false) $cid = 0;
        
        if ($cid == 0) {
            $where = "WHERE 1=1 ";
        } else {
            $where = "WHERE (f.cid = '".DB_escapeString($cid)."') ";
        }

        $entries = array();

        $sql = "SELECT lid, f.title, logourl, date
                FROM {$_TABLES['filemgmt_filedetail']} AS f
                LEFT JOIN {$_TABLES['filemgmt_cat']} AS c
                ON f.cid = c.cid $where ";
        if ($this->uid > 0) {
            $sql .= SEC_buildAccessSql('AND', 'c.grp_access');
        }
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_filemgmt::getItems() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['lid'],
                'title'     => $A['title'],
                'uri'       => $_CONF['site_url'] . '/filemgmt/index.php?id='
                                . $A['lid'],
                'date'      => $A['date'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
