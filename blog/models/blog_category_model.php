<?php

/**
 * This model handles blog categories
 * @todo: Move the logic from here into a Factory loaded models
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog_category_model extends NAILS_Model
{
    /**
     * Construct the model
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->table        = NAILS_DB_PREFIX . 'blog_category';
        $this->tableAlias  = 'bc';
    }

    // --------------------------------------------------------------------------

    /**
     * Set some common data
     * @param  array $data Data passed from the calling method
     * @return void
     */
    protected function getCountCommon($data = array())
    {
        parent::getCountCommon($data);

        // --------------------------------------------------------------------------

        $this->db->select($this->tableAlias . '.*');

        if (!empty($data['include_count'])) {

            $subQuery = '
                SELECT
                    COUNT(DISTINCT post_id)
                FROM ' . NAILS_DB_PREFIX . 'blog_post_category
                WHERE
                category_id = ' . $this->tableAlias . '.id';

            $this->db->select('(' . $subQuery . ') post_count');
        }

        //  Default sort
        if (empty($data['sort'])) {

            $this->db->order_by($this->tableAlias . '.label');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new category
     * @param  array   $aData         The data to create the category with
     * @param  boolean $bReturnObject Whether to return the full category object (or just the ID)
     * @return mixed
     */
    public function create($aData, $bReturnObject = false)
    {
        $aCategoryData = array();

        // --------------------------------------------------------------------------

        //  Some basic sanity testing
        if (empty($aData['label'])) {

            $this->setError('"label" is a required field.');
            return false;

        } else {

            $aCategoryData['label'] = trim($aData['label']);
        }

        if (empty($aData['blog_id'])) {

            $this->setError('"blog_id" is a required field.');
            return false;

        } else {

            $aCategoryData['blog_id'] = $aData['blog_id'];
        }

        // --------------------------------------------------------------------------

        $aCategoryData['slug'] = $this->generateSlug($aData['label']);

        if (isset($aData['description'])) {

            $aCategoryData['description'] = $aData['description'];
        }

        if (isset($aData['seo_title'])) {

            $aCategoryData['seo_title'] = strip_tags($aData['seo_title']);
        }

        if (isset($aData['seo_description'])) {

            $aCategoryData['seo_description'] = strip_tags($aData['seo_description']);
        }

        if (isset($aData['seo_keywords'])) {

            $aCategoryData['seo_keywords'] = strip_tags($aData['seo_keywords']);
        }

        return parent::create($aCategoryData, $bReturnObject);
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing category
     * @param  integer $iId   The category's ID
     * @param  array   $aData The data to update the category with
     * @return boolean
     */
    public function update($iId, $aData)
    {
        $aCategoryData = array();

        // --------------------------------------------------------------------------

        //  Some basic sanity testing
        if (empty($aData['label'])) {

            $this->setError('"label" is a required field.');
            return false;

        } else {

            $aCategoryData['label'] = trim($aData['label']);
        }

        // --------------------------------------------------------------------------

        $aCategoryData['slug'] = $this->generateSlug($aData['label'], '', '', null, null, $iId);

        if (isset($aData['description'])) {

            $aCategoryData['description'] = $aData['description'];
        }

        if (isset($aData['seo_title'])) {

            $aCategoryData['seo_title'] = strip_tags($aData['seo_title']);
        }

        if (isset($aData['seo_description'])) {

            $aCategoryData['seo_description'] = strip_tags($aData['seo_description']);
        }

        if (isset($aData['seo_keywords'])) {

            $aCategoryData['seo_keywords'] = strip_tags($aData['seo_keywords']);
        }

        return parent::update($iId, $aCategoryData);
    }


    // --------------------------------------------------------------------------

    /**
     * Formats a category's URL
     * @param  string $slug   The category's slug
     * @param  int    $blogId The blog ID to which the category belongs
     * @return string
     */
    public function formatUrl($slug, $blogId)
    {
        $this->load->model('blog/blog_model');
        return $this->blog_model->getBlogUrl($blogId) . '/category/' . $slug;
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

        $oObj->url = $this->formatUrl($oObj->slug, $oObj->blog_id);

        if (isset($oObj->post_count)) {
            $oObj->post_count = (int) $oObj->post_count;
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

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_CATEGORY_MODEL')) {

    class Blog_category_model extends NAILS_Blog_category_model
    {
    }
}
