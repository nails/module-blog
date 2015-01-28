<?php

namespace Nails\Admin\Blog;

class Post implements \Nails\Admin\Interfaces\AdminController
{
    /**
     * Basic definition of the announce() static method
     * @return null
     */
    public static function announce()
    {
        return null;
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