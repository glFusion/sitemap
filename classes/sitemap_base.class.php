<?php
/**
*   Base class for sitemaps.
*   Derived from the Dataproxy plugin. Each plugin that wishes to
*   contribute a sitemap should supply a class in its "classes"
*   directory named "<plugin_name>.class.php". The class name should be
*   "sitemap_<plugin_name>" and extend this sitemap_base class.
*
*   @deprecated 2.0.2
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2017 Lee Garner <lee@leegarner.com>
*   @package    sitemap
*   @version    2.0.2
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/**
 *  Class for sitemap items
 *  @deprecated
 *
 *  This class is deprecated and will be removed in a future version.
 *  Begining with Sitemap version 2.0.2 plugin drivers should declare
 *      namespace Sitemap\Drivers;
 *      class <plugin_name> extends BaseDriver {}
 *
 *  This class is provides a a shim until plugins are updated.
 */
class sitemap_base extends \Sitemap\Drivers\BaseDriver
{
}

?>
