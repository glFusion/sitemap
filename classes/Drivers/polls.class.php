<?php
// +--------------------------------------------------------------------------+
// | Data Proxy Plugin for glFusion                                           |
// +--------------------------------------------------------------------------+
// | polls.class.php                                                          |
// |                                                                          |
// | Polls Plugin interface                                                   |
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

class polls extends BaseDriver
{
    protected $name = 'polls';

    public function getDisplayName()
    {
        global $LANG_POLLS;
        return $LANG_POLLS['polls'];
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

        $sql = "SELECT pid, topic, UNIX_TIMESTAMP(date) AS day
                FROM {$_TABLES['polltopics']} ";
        if ($this->uid > 0) {
            $sql .= COM_getPermSQL('WHERE', $this->uid);
        }
        $sql .= ' ORDER BY pid';
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_polls::getItems error: $sql");
            return $entries;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entries[] = array(
                'id'        => $A['pid'],
                'title'     => $A['topic'],
                'uri'       => $_CONF['site_url'] . '/polls/index.php?pid='
                                . urlencode($A['pid']),
                'date'      => $A['day'],
                'image_uri' => false,
            );
        }
        return $entries;
    }

}

?>
