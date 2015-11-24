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

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        get_instance()->load->model('blog/blog_model');
        $blogs  = get_instance()->blog_model->getAll();
        $routes = array();

        foreach ($blogs as $blog) {

            $blogUrl = str_replace(site_url(), '', $blog->url);
            $routes[$blogUrl . '(/(.+))?'] = 'blog/' . $blog->id . '/$2';
        }

        return $routes;
    }
}
