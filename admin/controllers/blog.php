<?php

/**
 * Manage Blogs
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Blog extends \AdminController
{
   /**
     * Announces this controller's navGroupings
     * @return stdClass
     */
    public static function announce()
    {
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        //  Clear group naming
        $groupLabel = count($blogs) > 1 ? 'Blog: All' : 'Blog';
        $navGroup   = new \Nails\Admin\Nav($groupLabel);

        if (!empty($blogs)) {

            $navGroup->addMethod('Manage Blogs');

        } else {

            $navGroup->addMethod('Create New Blog', 'create');
        }

        return $navGroup;
    }
}
