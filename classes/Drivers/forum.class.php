<?php
/**
 * Forum driver for the Sitemap plugin.
 * Derived from the Dataproxy plugin.
 *
 * @author      Mark R. Evans  <mark AT glfusion DOT org
 * @copyright   Copyright (c) 2009-2015 Mark R. Evans <mark@glfusion.org>
 * @package     sitemap
 * @version     v2.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
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
 * Forum sitemap driver class.
 * @package sitemap
 */
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

        $db = Database::getInstance();
        $sql = "SELECT forum_id, forum_name FROM {$_TABLES['ff_forums']}
                WHERE (is_hidden = '0') ";
        if ($this->uid > 0) {
            $sql .= $this->buildAccessSql('AND', 'grp_id');
        }
        $sql .= ' ORDER BY forum_order';
        try {
            $stmt = $db->conn->executeQuery($sql);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $Item = new Item;
                $Item->withItemId($A['forum_id'])
                     ->withTitle($A['forum_name'])
                     ->withUrl(self::getEntryPoint() . '?forum=' . $A['forum_id']);
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
    public function getItems($forum_id = false)
    {
        global $_CONF, $_TABLES;

        $entries = array();

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('t.id', 't.subject', 't.lastupdated')
           ->from($_TABLES['ff_topic'], 't')
           ->where('t.pid = 0')
           ->orderBy('t.lastupdated', 'DESC');
        if ($forum_id === false) {
            $qb->leftJoin('t', $_TABLES['ff_forums'], 'f', 't.forum = f.forum_id')
               ->andWhere('f.grp_id IN (:groups)')
               ->setParameter('groups', $this->groups, Database::PARAM_INT_ARRAY);
        } else {
            $qb->andWhere('forum = :forum_id')
               ->setParameter('forum_id', $forum_id, Database::INTEGER);
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
                $Item->withItemId($A['id'])
                     ->withTitle($A['subject'])
                     ->withUrl($_CONF['site_url'] . '/forum/viewtopic.php?showtopic='.$A['id'])
                 ->withDate($A['lastupdated']);
                $entries[] = $Item->toArray();
            }
        }
        return $entries;
    }

}

