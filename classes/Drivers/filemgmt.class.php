<?php
/**
 * Sitemap driver for the Filemgmt Plugin.
 *
 * @author     Mark R. Evans <mark@glfusion.org>
 * @copyright  Copyright (c) 2008-2018 Mark R. Evans <mark AT glfusion DOT org>
 * @copyright  Copyright (c) 2007-2008 Mystral-kk <geeklog@mystral-kk.net>
 * @package    glfusion
 * @version    2.1.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap\Drivers;
use glFusion\Database\Database;
use glFusion\Log\Log;
use Sitemap\Models\Item;

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}


/**
 * Sitemap driver for the Filemgmt plugin.
 * @package sitemap
 */
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

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('*')
           ->from($_TABLES['filemgmt_cat'])
           ->where('pid = :pid')
           ->setParameter('pid', $pid, database::STRING)
           ->orderBy('cid');
        if ($this->uid > 0) {
            $qb->andWhere($db->getAccessSQL(''));
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
                     ->withTitle($A['title'])
                     ->withUrl($_CONF['site_url'] . '/filemgmt/viewcat.php?cid=' . $A['cid'])
                     ->withImageUrl($A['imgurl']);
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
    public function getItems($cid = false)
    {
        global $_CONF, $_TABLES, $_TABLES;

        $entries = array();
        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('lid', 'f.title', 'logourl', 'date')
           ->from($_TABLES['filemgmt_filedetail'], 'f')
           ->leftJoin('f', $_TABLES['filemgmt_cat'], 'c', 'f.cid = c.cid');
        if ($cid === false) {
            $qb->where('1=1');
        } else {
            $qb->where('f.cid = :cid')
               ->setParameter('cid', $cid, Database::STRING);
        }

        if ($this->uid > 0) {
            $qb->andWhere($db->getAccessSQL('', 'c.grp_access'));
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
                     ->withUrl($_CONF['site_url'] . '/filemgmt/index.php?id=' . $A['lid'])
                     ->withDate($A['date']);
                $entries[] = $Item->toArray();
            }
        }
        return $entries;
    }

}

