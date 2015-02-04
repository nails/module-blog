<?php

/**
 * Manage Blog categories
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Category extends \AdminController
{
   /**
     * Announces this controller's navGroupings
     * @return stdClass
     */
    public static function announce()
    {
        //  Fetch the blogs, each blog should have its own admin section
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                //  Clear group naming
                $groupLabel = count($blogs) > 1 ? 'Blog: ' . $blog->label : 'Blog';

                //  Create the navGrouping
                //  @todo respect permissions for managing categories
                $navGroup = new \Nails\Admin\Nav($groupLabel);
                $navGroup->addMethod('Manage Categories', 'index/' . $blog->id);

                $out[] = $navGroup;
            }
        }

        return $out;
    }
}
