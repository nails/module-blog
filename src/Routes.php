<?php

/**
 * Generates blog routes
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Blog;

use Nails\Common\Interfaces\RouteGenerator;
use Nails\Factory;

class Routes implements RouteGenerator
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public static function generate()
    {
        $oDb            = Factory::service('PDODatabase');
        $oModel         = Factory::model('Blog', 'nailsapp/module-blog');
        $oSettingsModel = Factory::model('AppSetting');
        $aRoutes        = [];

        $oRows = $oDb->query('SELECT id FROM ' . $oModel->getTableName());
        if (!$oRows->rowCount()) {
            return [];
        }

        while ($oRow = $oRows->fetch(PDO::FETCH_OBJ)) {

            //  Look up the setting
            $oSettings = $oDb->query('
              SELECT * FROM ' . $oSettingsModel->getTableName() . '
              WHERE `grouping` = "blog-' . $oRow->id . '"
              AND `key` = "url"

            ');

            $sUrl = json_decode($oSettings->fetch(PDO::FETCH_OBJ)->value) ?: 'blog';
            $sUrl = preg_replace('/^\//', '', $sUrl);
            $sUrl = preg_replace('/\/$/', '', $sUrl);

            $aRoutes[$sUrl . '(/(.+))?'] = 'blog/' . $oRow->id . '/$2';
        }

        return $aRoutes;
    }
}
