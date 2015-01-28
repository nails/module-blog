<?php

namespace Nails\Admin\Blog;

class Blog implements \Nails\Admin\Interfaces\AdminController
{
    /**
     * Basic definition of the announce() static method
     * @return null
     */
    public static function announce()
    {
        $d                       = new \stdClass();
        $d->name                 = 'Blog';
        $d->icon                 = 'fa-pencil-square-o';
        $d->funcs                = array();
        $d->funcs['create_blog'] = 'Create New Blog';

        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Basic definition of the notifications() static method
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    public static function notifications($classIndex = null)
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Basic definition of the permissions() static method
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    public static function permissions($classIndex = null)
    {
        return array();
    }

    // --------------------------------------------------------------------------

    /**
     * Does something
     * @return void
     */
    public function index()
    {
        dump('here!');
    }
}