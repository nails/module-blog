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

namespace Nails\Routes\Blog;

use Nails\Common\Model\BaseRoutes;

class Routes extends BaseRoutes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        get_instance()->load->model('blog/blog_model');
        $aBlogs  = get_instance()->blog_model->getAll();
        $aRoutes = [];

        foreach ($aBlogs as $oBlog) {

            $sBlogUrl                        = str_replace(site_url(), '', $oBlog->url);
            $aRoutes[$sBlogUrl . '(/(.+))?'] = 'blog/' . $oBlog->id . '/$2';
        }

        return $aRoutes;
    }
}
