<?php
// +--------------------------------------------------------------------------+
// | Data Proxy Plugin for glFusion                                           |
// +--------------------------------------------------------------------------+
// | forum.class.php                                                          |
// |                                                                          |
// | Forum Plugin interface                                                   |
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

class forum extends BaseDriver
{
    protected $name = 'forum';

    public function getDisplayName()
    {
        global $LANG_GF01;
        return $LANG_GF01['FORUM'];
    }


    public function getChildCategories($pid = false)
    {
        global $_CONF, $_TABLES;

        $entries = array();

        if ($pid !== false) {   // no subcategory support
            return $entries;
        }

        $sql = "SELECT forum_id, forum_name FROM {$_TABLES['ff_forums']}
                WHERE (is_hidden = '0') ";
        if ($this->uid > 0) {
            $sql .= SEC_buildAccessSql('AND', 'grp_id');
        }
        $sql .= ' ORDER BY forum_order';
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_forum::getChildCategories() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => (int)$A['forum_id'],
                'pid'       => false,
                'title'     => $A['forum_name'],
                'uri'       => self::getEntryPoint() . '?forum=' . $A['forum_id'],
                'date'      => false,
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
    public function getItems($forum_id = false)
    {
        global $_CONF, $_TABLES;

        $entries = array();

        if ($forum_id === false) {
            $groups = array ();
            $usergroups = SEC_getUserGroups(1);
            foreach ($usergroups as $group) {
                $groups[] = $group;
            }
            $grouplist = implode(',',$groups);
             $sql  = "SELECT a.id, a.subject, a.lastupdated, b.grp_id
                    FROM {$_TABLES['ff_topic']} a
                    LEFT JOIN {$_TABLES['ff_forums']} b ON a.forum = b.forum_id ";
            $sql .= "WHERE (pid=0) AND b.grp_id IN ($grouplist) ORDER BY a.lastupdated DESC";
        } else {
            $sql = "SELECT id, subject, lastupdated FROM {$_TABLES['ff_topic']}
                    WHERE (pid = 0) AND (forum = '" . DB_escapeString($forum_id) ."')
                    ORDER BY lastupdated DESC";
        }
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_forum::getItems() error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['id'],
                'title'     => $A['subject'],
                'uri'       => $_CONF['site_url'] . '/forum/viewtopic.php?showtopic='.$A['id'],
                'date'      => $A['lastupdated'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
