<?php

/**
 * This model allows for the management of blogs.
 *
 * @TODO: On deletion of blog, wipe blog settings
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

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

        $this->config->load('blog/blog');

        // --------------------------------------------------------------------------

        $this->table       = NAILS_DB_PREFIX . 'blog';
        $this->tablePrefix = 'b';
        $this->blogUrl     = array();
    }

    // --------------------------------------------------------------------------

    protected function _format_object(&$object)
    {
        parent::_format_object($object);

        $object->url = $this->getBlogUrl($object->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch all the associations for a particular post
     * @param  int   $post_id The ID f the post
     * @return array
     */
    public function get_associations($post_id = NULL)
    {
        $this->config->load('blog/blog');
        $_associations  = $this->config->item('blog_post_associations');

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

                $this->db->where('post_id', $post_id);
                $assoc->current = $this->db->get($assoc->target)->result();

            } else {

                $assoc->current = array();
            }

            //  Fetch the raw data
            $this->db->select($assoc->source->id . ' id, ' . $assoc->source->label . ' label');
            $this->db->order_by('label');

            if (isset($assoc->source->where) && $assoc->source->where) {

                $this->db->where($assoc->source->where );
            }

            $assoc->data = $this->db->get($assoc->source->table)->result();
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

            $url = app_setting('url', 'blog-' . $blogId);
            $url = $url ? $url : 'blog/';
            $url = site_url($url);

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
