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
use glFusion\Database\Database;
use glFusion\Log\Log;
use Sitemap\Models\Item;

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
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('*')
            ->from($_TABLES['linkcategories']);
        //$sql = "SELECT * FROM {$_TABLES['linkcategories']}";
        if ($pid === false) {
            $pid = 'root';
        }
        $qb->where('pid = :pid')
           ->setParameter('pid', $pid, Database::STRING);

        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSQL('', $this->uid));
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $Item = new Item;
                $Item->withItemId($A['cid'])
                     ->withParentId($A['pid'])
                     ->withTitle($A['category'])
                     ->withUrl(self::getEntryPoint() . '?category='.urlencode($A['cid']))
                     ->withDate(strtotime($A['modified']));
                $entries[] = $Item->toArray();
            }
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
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('lid', 'title', 'UNIX_TIMESTAMP(date) AS date_u')
           ->from($_TABLES['links'])
           ->where('1=1')
           ->orderBy('date_u', 'DESC');

        if (!empty($category)) {
            $qb->andWhere('cid = :category')
               ->setParameter('category', $category, Database::STRING);
        }
        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSQL('', $this->uid));
        }

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $Item = new Item;
                $Item->withItemId($A['lid'])
                     ->withTitle($A['title'])
                     ->withUrl(COM_buildURL(
                         $_CONF['site_url'] . '/links/portal.php?what=link&amp;item='
                         . urlencode($A['lid'])
                     ) )
                     ->withDate($A['date_u']);
                $entries[] = $Item->toArray();
            }
        }
        return $entries;
    }

}

