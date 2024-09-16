<?php
/**
 * Sitemap driver for the Calendar Plugin.
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
 * Calendar plugin sitemap driver.
 * @package sitemap
 */
class calendar extends BaseDriver
{
    protected $name = 'calendar';

    /**
     * Get the friendly display name.
     *
     * @return  string   Display Name
     */
    public function getDisplayName()
    {
        global $LANG_CAL_1;
        return $LANG_CAL_1[16];
    }


    /**
     * Get the child categories under the supplied category ID.
     * Only primary categories (event types) are supported.
     *
     * @param   mixed   $pid    Parent category, must be false.
     * @return  array   Array of category information.
     */
    public function getChildCategories($pid = false)
    {
        global $_CONF, $_TABLES, $LANG_SMAP;

        $entries = array();

        if ($pid !== false) {
            return $entries;        // sub-categories not supported
        }

        $db = Database::getInstance();
        try {
            $data = $db->conn->executeQuery(
                "SELECT DISTINCT event_type FROM {$_TABLES['events']}
                ORDER BY event_type"
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = false;
        }

        if (is_array($data)) {
            foreach ($data as $A) {
                // Replace empty title string with "uncategorized"
                $title = $A['event_type'];
                if (empty($title)) $title = $LANG_SMAP['uncategorized'];
                $Item = new Item;
                $Item->withItemId($A['event_type'])
                     ->withTitle($title);
                $entries[] = $Item->toArray();
            }
        }
        return $entries;
    }


    /**
     * Get all the items under the given category.
     *
     * @param  string  $category   Category (event type)
     * @return array   Array of (
     *      'id'        => $id (string),
     *      'title'     => $title (string),
     *      'uri'       => $uri (string),
     *      'date'      => $date (int: Unix timestamp),
     *      'image_uri' => $image_uri (string)
     * )
     */
    public function getItems($category = '*')
    {
        global $_CONF, $_TABLES;

        $entries = array();

        $db = Database::getInstance();
        $qb = $db->conn->createQueryBuilder();
        $qb->select('eid', 'title', 'datestart', 'timestart')
           ->from($_TABLES['events'])
           ->where('status = 1')
           ->orderBy('datestart', 'DESC')
           ->addOrderBy('timestart', 'DESC');

        if ($category != '*') {
            $qb->andWhere('event_type = :category')
               ->setParameter('category', $category, Database::STRING);
        }

        /*$sql = "SELECT eid, title, datestart,timestart
                FROM {$_TABLES['events']}
                WHERE (status=1 " . $categorySQL . ") ";*/

        if ($this->uid > 0) {
            $qb->andWhere($db->getPermSql('', $this->uid));
            //$sql .= COM_getPermSql('AND', $this->uid);
        }
        //$sql .= ' ORDER BY datestart DESC, timestart DESC';

        try {
            $stmt = $qb->execute();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $stmt = false;
        }

        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                if ($A['timestart'] == null) $A['timestart'] = '00:00:00';
                $datetime = strtotime($A['datestart'].'T'.$A['timestart']);
                $Item = new Item;
                $Item->withItemId($A['eid'])
                     ->withTitle($A['title'])
                     ->withUrl($_CONF['site_url'].'/calendar/event.php?eid='.$A['eid'])
                     ->withDate($datetime);
                $entries[] = $Item->toArray();
            }
        }
        return $entries;
    }
}

