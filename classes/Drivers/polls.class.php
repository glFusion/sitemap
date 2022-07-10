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
use glFusion\Database\Database;
use glFusion\Log\Log;
use Sitemap\Models\Item;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}


/**
 * Sitemap driver for the Polls plugin.
 * @package sitemap
 */
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

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('pid', 'topic', 'UNIX_TIMESTAMP(date) AS day')
           ->from($_TABLES['polltopics'])
           ->orderBy('pid');
        if ($this->uid > 0) {
            $qb->where($db->getPermSQL('', $this->uid));
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $item = new Item;
                $item['id'] = $A['pid'];
                $item['title'] = $A['topic'];
                $item['uri'] = $_CONF['site_url'] . '/polls/index.php?pid=' . urlencode($A['pid']);
                $item['date'] = $A['day'];
                $entries[] = $item->toArray();
            }
        }
        return $entries;
    }

}

