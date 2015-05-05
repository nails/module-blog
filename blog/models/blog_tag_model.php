<?php

/**
 * This model handles blog tags
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog_tag_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->table        = NAILS_DB_PREFIX . 'blog_tag';
        $this->tablePrefix  = 'bc';
    }

    // --------------------------------------------------------------------------

    /**
     * Set some common data
     * @param  array  $data    Data passed from the calling method
     * @param  string $_caller The name of the calling method
     * @return void
     */
    protected function _getcount_common($data = array(), $_caller = null)
    {
        parent::_getcount_common($data, $_caller);

        // --------------------------------------------------------------------------

        $this->db->select($this->tablePrefix . '.*');

        if (!empty($data['include_count'])) {

            $subQuery = '
                SELECT
                    COUNT(DISTINCT post_id)
                FROM ' . NAILS_DB_PREFIX . 'blog_post_tag
                WHERE
                tag_id = ' . $this->tablePrefix . '.id';

            $this->db->select('(' . $subQuery . ') post_count');
        }

        //  Default sort
        if (empty($data['sort'])) {

            $this->db->order_by($this->tablePrefix . '.label');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new tag
     * @param  stdClass $data The data to create the tag with
     * @return mixed
     */
    public function create($data, $returnObject = false)
    {
        $tagData = new stdClass();

        // --------------------------------------------------------------------------

        //  Some basic sanity testing
        if (empty($data->label)) {

            $this->_set_error('"label" is a required field.');
            return false;

        } else {

            $tagData->label = trim($data->label);
        }

        if (empty($data->blog_id)) {

            $this->_set_error('"blog_id" is a required field.');
            return false;

        } else {

            $tagData->blog_id = $data->blog_id;
        }

        // --------------------------------------------------------------------------

        $tagData->slug = $this->_generate_slug($data->label);

        if (isset($data->description)) {

            $tagData->description = $data->description;
        }

        if (isset($data->seo_title)) {

            $tagData->seo_title = strip_tags($data->seo_title);
        }

        if (isset($data->seo_description)) {

            $tagData->seo_description = strip_tags($data->seo_description);
        }

        if (isset($data->seo_keywords)) {

            $tagData->seo_keywords = strip_tags($data->seo_keywords);
        }

        return parent::create($tagData, $returnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing tag
     * @param  int      $id   The tag's ID
     * @param  stdClass $data The data to update the tag with
     * @return boolean
     */
    public function update($id, $data)
    {
        $tagData = new stdClass();

        // --------------------------------------------------------------------------

        //  Some basic sanity testing
        if (empty($data->label)) {

            $this->_set_error('"label" is a required field.');
            return false;

        } else {

            $tagData->label = trim($data->label);
        }

        // --------------------------------------------------------------------------

        $tagData->slug = $this->_generate_slug($data->label, '', '', null, null, $id);

        if (isset($data->description)) {

            $tagData->description = $data->description;
        }

        if (isset($data->seo_title)) {

            $data->seo_title = strip_tags($data->seo_title);
        }

        if (isset($data->seo_description)) {

            $tagData->seo_description = strip_tags($data->seo_description);
        }

        if (isset($data->seo_keywords)) {

            $tagData->seo_keywords = strip_tags($data->seo_keywords);
        }

        return parent::update($id, $tagData);
    }


    // --------------------------------------------------------------------------

    /**
     * Formats a tag's URL
     * @param  string $slug   The tag's slug
     * @param  int    $blogId The blog ID to which the tag belongs
     * @return string
     */
    public function format_url($slug, $blogId)
    {
        $this->load->model('blog/blog_model');
        return $this->blog_model->getBlogUrl($blogId) . '/tag/' . $slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a tag object
     * @param  stdClass &$tag The tag object to format
     * @return void
     */
    protected function _format_object(&$tag)
    {
        parent::_format_object($tag);

        $tag->url  = $this->format_url($tag->slug, $tag->blog_id);

        if (isset($tag->post_count)) {

            $tag->post_count = (int) $tag->post_count;
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

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_TAG_MODEL')) {

    class Blog_tag_model extends NAILS_Blog_tag_model
    {
    }
}
