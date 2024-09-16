<?php
/**
 * Class to create custom admin list fields.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021-2022 Lee Garner <lee@leegarner.com>
 * @package     sitemap
 * @version     v2.1.0
 * @since       v2.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Sitemap;


/**
 * Class to handle custom fields.
 * @package membership
 */
class FieldList extends \glFusion\FieldList
{
    private static $t = NULL;

    protected static function init()
    {
        global $_CONF;

        static $t = NULL;
        if (self::$t === NULL) {
            $t = new \Template($_CONF['path'] . '/plugins/sitemap/templates');
            $t->set_file('field','fieldlist.thtml');
        }
        return $t;
    }


    /**
     * Create a blank icon to help with spacing.
     */
    public static function blank($args=array())
    {
        $t = self::init();
        $t->set_block('up','field-blank');

        if (isset($args['attr']) && is_array($args['attr'])) {
            $t->set_block('field-blank','attr','attributes');
            foreach($args['attr'] AS $name => $value) {
                $t->set_var(array(
                    'name' => $name,
                    'value' => $value)
                );
                $t->parse('attributes','attr',true);
            }
        }
        $t->parse('output','field-blank');
        return $t->finish($t->get_var('output'));
    }


}
