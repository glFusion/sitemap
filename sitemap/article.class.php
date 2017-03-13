<?php

// this file can't be used on its own
if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

class sitemap_article extends sitemap_base
{

    public function getName()
    {
        return 'article';
    }

    public function getDisplayName()
    {
        global $LANG33;
        return $LANG33[55];
    }

    public function getChildCategories($pid = false, $all_langs = false)
    {
        global $_CONF, $_TABLES;

        $retval = array();
        if ($pid !== false) {
            return $retval;     // Only one level of categories supported
        }

        $where = false;

        $sql = "SELECT tid, topic, imageurl FROM {$_TABLES['topics']} ";
        if (!COM_isAnonUser()) {
            $tids = DB_getItem(
                $_TABLES['userindex'], 'tids', "uid = '" . $this->uid . "'"
            );
            if (!empty($tids)) {
                $sql .= ($where === true) ? ' AND' : ' WHERE';
                $sql .= "(tid NOT IN ('"
                     . str_replace(' ', "','", DB_escapeString($tids)) . "'))";
                $where = true;
            }
        }

        // Adds permission check.  When uid is 0, then it means access as Root
        if ($this->uid > 0) {
            if ($where === true) {
                $sql .= COM_getPermSQL('AND', $this->uid);
            } else {
                $sql .= COM_getPermSQL('WHERE', $this->uid);
            }
        }

        // Adds lang id.  When uid is 0, then it means access as Root
        if (($this->uid > 0) AND function_exists('COM_getLangSQL')
         AND $all_langs === false) {
            $where = (strpos($sql, 'WHERE') !== false) ? true : false;
            if ($where === true) {
                $sql .= COM_getLangSQL('tid', 'AND');
            } else {
                $sql .= COM_getLangSQL('tid', 'WHERE');
            }
        }

        if ($_CONF['sortmethod'] == 'alpha') {
            $sql .= ' ORDER BY topic ASC';
        } else {
            $sql .= ' ORDER BY sortnum';
        }
        $result = DB_query($sql);
        if (DB_error()) {
            return $retval;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $entry = array();
            $entry['id']        = $A['tid'];
            $entry['title']     = $A['topic'];
            $entry['uri']       = $_CONF['site_url'] . '/index.php?topic=' . $entry['id'];
            $entry['date']      = false;
            $entry['image_uri'] = $A['imageurl'];
            $retval[] = $entry;
        }

        return $retval;
    }

    /**
    * @param $all_langs boolean: true = all languages, true = current language
    * Returns array of (
    *   'id'        => $id (string),
    *   'title'     => $title (string),
    *   'uri'       => $uri (string),
    *   'date'      => $date (int: Unix timestamp),
    *   'image_uri' => $image_uri (string),
    *   'raw_data'  => raw data of the item (stripslashed)
    * )
    */
    public function getItemById($id, $all_langs = false)
    {
        global $_CONF, $_TABLES;

        $retval = array();

        $sql = "SELECT * "
             . "FROM {$_TABLES['stories']} "
             . "WHERE (sid ='" . DB_escapeString($id) . "') "
             . "AND (draft_flag = 0) AND (date <= NOW()) ";
        if ($this->uid > 0) {
            $sql .= COM_getTopicSql('AND', $this->uid);
            $sql .= COM_getPermSql('AND', $this->uid);
            if (function_exists('COM_getLangSQL') AND ($all_langs === false)) {
                $sql .= COM_getLangSQL('sid', 'AND');
            }
        }
        $result = DB_query($sql);
        if (DB_error()) {
            return $retval;
        }

        if (DB_numRows($result) == 1) {
            $A = DB_fetchArray($result, false);

            $retval['id']        = $id;
            $retval['title']     = $A['title'];
            $retval['uri']       = COM_buildUrl(
                $_CONF['site_url'] . '/article.php?story=' . $id
            );
            $retval['date']      = strtotime($A['date']);
            $retval['image_uri'] = false;
            $retval['raw_data']  = $A;
        }

        return $retval;
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
    public function getItems($tid = false, $all_langs = false)
    {
        global $_CONF, $_TABLES;

        $retval = array();

        $sql = "SELECT sid, title, UNIX_TIMESTAMP(date) AS day
                FROM {$_TABLES['stories']}
                WHERE (draft_flag = 0) AND (date <= NOW())";
        if ($tid !== false) {
             $sql .= " AND (tid = '" . DB_escapeString($tid) . "')";
        }
        if ($this->uid > 0) {
            $sql .= COM_getTopicSql('AND', $this->uid)
                 .  COM_getPermSql('AND', $this->uid);
            if (function_exists('COM_getLangSQL') AND ($all_langs === false)) {
                $sql .= COM_getLangSQL('sid', 'AND');
            }
        }
        $sql .= " ORDER BY date DESC";
        $result = DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("sitemap_article::getItems error: $sql");
            return $retval;
        }

        while (($A = DB_fetchArray($result, false)) !== FALSE) {
            $retval[] = array(
                'id'        => $A['sid'],
                'title'     => $A['title'],
                'uri'       => COM_buildUrl(
                    $_CONF['site_url'] . '/article.php?story=' . $A['sid']
                ),
                'date'      => $A['day'],
                'imageurl'  => false,
            );
        }
        return $retval;
    }

}

?>
