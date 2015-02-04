<?php

class NAILS_Blog extends NAILS_Admin_Controller
{
    /**
     * Returns an array of extra permissions for this controller
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    public static function permissions($classIndex = null)
    {
        $permissions = parent::permissions($classIndex);

        // --------------------------------------------------------------------------

        //  Blogs
        $permissions['blog_manage']  = 'Can manage blogs';
        $permissions['blog_create']  = 'Can create blogs';
        $permissions['blog_edit']    = 'Can edit blogs';
        $permissions['blog_delete']  = 'Can delete blogs';

        //  Posts
        $permissions['post_manage']  = 'Can manage posts';
        $permissions['post_create']  = 'Can create posts';
        $permissions['post_edit']    = 'Can edit posts';
        $permissions['post_delete']  = 'Can delete posts';
        $permissions['post_restore'] = 'Can restore posts';

        //  Categories
        $permissions['category_manage'] = 'Can manage categories';
        $permissions['category_create'] = 'Can create categories';
        $permissions['category_edit']   = 'Can edit categories';
        $permissions['category_delete'] = 'Can delete categories';

        //  Tags
        $permissions['tag_manage'] = 'Can manage tags';
        $permissions['tag_create'] = 'Can create tags';
        $permissions['tag_edit']   = 'Can edit tags';
        $permissions['tag_delete'] = 'Can delete tags';

        // --------------------------------------------------------------------------

        return $permissions;
    }
}
