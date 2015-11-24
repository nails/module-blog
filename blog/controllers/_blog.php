<?php

/**
 * This class provides some common blog controller functionality
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog_Controller extends NAILS_Controller
{
    protected $blog;
    protected $oSkin;
    protected $oSkinParent;

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check the blog is valid
        $this->load->model('blog/blog_model');

        $iBlogId = (int) $this->uri->rsegment(2);
        $this->oBlog = $this->blog_model->get_by_id($iBlogId);

        if (empty($this->oBlog)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load language file
        $this->lang->load('blog/blog');

        // --------------------------------------------------------------------------

        //  Load the other models
        $this->load->model('blog/blog_post_model');
        $this->load->model('blog/blog_widget_model');
        $this->load->model('blog/blog_skin_model');

        // --------------------------------------------------------------------------

        $sSettingBlogName = 'blog-' . $this->oBlog->id;

        if (appSetting('categories_enabled', $sSettingBlogName)) {

            $this->load->model('blog/blog_category_model');
        }


        if (appSetting('tags_enabled', $sSettingBlogName)) {

            $this->load->model('blog/blog_tag_model');
        }

        // --------------------------------------------------------------------------

        //  Load up the blog's skin
        $sSkinSlug = appSetting('skin', $sSettingBlogName) ? appSetting('skin', $sSettingBlogName) : 'blog-skin-classic';

        $this->oSkin = $this->blog_skin_model->get($sSkinSlug);

        if (!$this->oSkin) {

            $sSubject  = 'Failed to load blog skin "' . $sSkinSlug . '"';
            $sMessage  = 'Blog skin "' . $sSkinSlug . '" failed to load at ' . APP_NAME;
            $sMessage .= ', the following reason was given: ' . $this->blog_skin_model->lastError();

            showFatalError($sSubject, $sMessage);
        }

        //  Load the skin's parent, if it has one
        if (!empty($this->oSkin->parent)) {

            $this->oSkinParent = $this->blog_skin_model->get($this->oSkin->parent);

            if (!$this->oSkinParent) {

                $sSubject  = 'Failed to load blog skin "' . $this->oSkin->parent . '"';
                $sMessage  = 'Blog skin "' . $sSkinSlug . '" has defined a parent ("' . $this->oSkin->parent . '")" ';
                $sMessage .= 'but the parent skin could not be loaded at ' . APP_NAME . ', ';
                $sMessage .= 'the following reason was given: ' . $this->blog_skin_model->lastError();

                showFatalError($sSubject, $sMessage);
            }
        }

        // --------------------------------------------------------------------------

        //  Pass to $this->data, for the views
        $this->data['skin'] = $this->oSkin;
        $this->data['skinParent'] = $this->oSkinParent;

        // --------------------------------------------------------------------------

        //  Load skin assets
        $aAssets = array();
        $aCssInline = array();
        $aJsInline = array();

        if (!empty($this->oSkinParent)) {

            if (!empty($this->oSkinParent->assets)) {

                $aAssetsWalked = array_map(array($this, 'prependUrlParent'), $this->oSkinParent->assets);
                $aAssets = array_merge($aAssets, $aAssetsWalked);
            }

            if (!empty($this->oSkinParent->css_inline)) {
                $aCssInline = array_merge($aCssInline, $this->oSkinParent->css_inline);
            }

            if (!empty($this->oSkinParent->js_inline)) {
                $aJsInline = array_merge($aJsInline, $this->oSkinParent->js_inline);
            }
        }

        if (!empty($this->oSkin->assets)) {

            $aAssetsWalked = array_map(array($this, 'prependUrl'), $this->oSkin->assets);
            $aAssets = array_merge($aAssets, $aAssetsWalked);
        }

        if (!empty($this->oSkin->css_inline)) {
            $aCssInline = array_merge($aCssInline, $this->oSkin->css_inline);
        }

        if (!empty($this->oSkin->js_inline)) {
            $aJsInline = array_merge($aJsInline, $this->oSkin->js_inline);
        }

        $this->loadSkinAssets($aAssets, $aCssInline, $aJsInline, $this->oSkin->url);

        // --------------------------------------------------------------------------

        $this->data['postName'] = appSetting('postName', 'blog-' . $this->oBlog->id);
        if (empty($this->data['postName'])) {
            $this->data['postName'] = 'post';
        }
        $this->data['postNamePlural'] = appSetting('postNamePlural', 'blog-' . $this->oBlog->id);
        if (empty($this->data['postNamePlural'])) {
            $this->data['postNamePlural'] = 'posts';
        }

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['blog'] = $this->oBlog;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads any assets required by the skin
     * @param  array  $aAssets    An array of skin assets
     * @param  array  $aCssInline An array of inline CSS
     * @param  array  $aJsInline  An array of inline JS
     * @param  string $sUrl       The URL to the skin's root directory
     * @return void
     */
    protected function loadSkinAssets($aAssets, $aCssInline, $aJsInline, $sUrl)
    {
        //  CSS and JS
        if (!empty($aAssets) && is_array($aAssets)) {

            foreach ($aAssets as $asset) {

                if (is_string($asset)) {

                    $this->asset->load($asset);

                } else {

                    $this->asset->load($asset[0], $asset[1]);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  CSS - Inline
        if (!empty($aCssInline) && is_array($aCssInline)) {

            foreach ($aCssInline as $asset) {

                $this->asset->inline($asset, 'CSS-INLINE');
            }
        }

        // --------------------------------------------------------------------------

        //  JS - Inline
        if (!empty($aJsInline) && is_array($aJsInline)) {

            foreach ($aJsInline as $asset) {

                $this->asset->inline($asset, 'JS-INLINE');
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Adds the skin url to the input string
     * @param String $sInput the input string
     */
    private function prependUrl($sInput)
    {
        return $this->oSkin->url . 'assets/' . $sInput;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds the parent skin url to the input string
     * @param String $sInput the input string
     */
    private function prependUrlParent($sInput)
    {
        return $this->oSkinParent->url . 'assets/' . $sInput;
    }
}
