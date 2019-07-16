<?php

/**
 * This model allows for the management of blogs.
 * @todo: Move the logic from here into a Factory loaded models
 * @todo: On deletion of blog, wipe blog settings
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Blog_model extends NAILS_Model
{
    protected $blogUrl;

    // --------------------------------------------------------------------------

    /**
     * Constructs the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $oConfig = Factory::service('Config');
        $oConfig->load('blog/blog');

        // --------------------------------------------------------------------------

        $this->table       = NAILS_DB_PREFIX . 'blog';
        $this->tableAlias = 'b';
        $this->blogUrl     = array();
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = array(),
        $aIntegers = array(),
        $aBools = array(),
        $aFloats = array()
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        $oObj->url = $this->getBlogUrl($oObj->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch all the associations for a particular post
     * @param  int   $post_id The ID f the post
     * @return array
     */
    public function getAssociations($post_id = null)
    {
        $oConfig       = Factory::service('Config');
        $oDb           = Factory::service('Database');
        $_associations = $oConfig->item('blog_post_associations');

        if (!$_associations) {
            return array();
        }

        // --------------------------------------------------------------------------

        foreach ($_associations as &$assoc) {

            /**
             * Fetch the association data from the source, fail ungracefully - the dev
             * should have this configured correctly.
             *
             * Fetch current associations if a post_id has been supplied
             */

            if ($post_id) {

                $oDb->where('post_id', $post_id);
                $assoc->current = $oDb->get($assoc->target)->result();

            } else {

                $assoc->current = array();
            }

            //  Fetch the raw data
            $oDb->select($assoc->source->id . ' id, ' . $assoc->source->label . ' label');
            $oDb->order_by('label');

            if (isset($assoc->source->where) && $assoc->source->where) {

                $oDb->where($assoc->source->where);
            }

            $assoc->data = $oDb->get($assoc->source->table)->result();
        }

        return $_associations;
    }

    // --------------------------------------------------------------------------

    /**
     * Get the URL of a blog
     * @param  int    $blogId The ID of the blog who's URL to get
     * @return string
     */
    public function getBlogUrl($blogId)
    {
        if (isset($this->blogUrl[$blogId])) {

            return $this->blogUrl[$blogId];

        } else {

            $url = appSetting('url', 'blog-' . $blogId);
            $url = $url ? $url : 'blog/';
            $url = siteUrl($url);

            $this->blogUrl[$blogId] = $url;

            return $this->blogUrl[$blogId];
        }
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_MODEL')) {

    class Blog_model extends NAILS_Blog_model
    {
    }
}
