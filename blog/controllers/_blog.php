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

        $blogId     = $this->uri->rsegment(2);
        $this->blog = $this->blog_model->get_by_id($blogId);

        if (empty($this->blog)) {

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

        $settingStr = 'blog-' . $this->blog->id;

        if (app_setting('categories_enabled', $settingStr)) {

            $this->load->model('blog/blog_category_model');
        }


        if (app_setting('tags_enabled', $settingStr)) {

            $this->load->model('blog/blog_tag_model');
        }

        // --------------------------------------------------------------------------

        //  Load up the blog's skin
        $skin = app_setting('skin', $settingStr) ? app_setting('skin', $settingStr) : 'blog-skin-classic';

        $this->_skin = $this->blog_skin_model->get($skin);

        if (!$this->_skin) {

            $subject  = 'Failed to load blog skin "' . $skin . '"';
            $message  = 'Blog skin "' . $skin . '" failed to load at ' . APP_NAME;
            $message .= ', the following reason was given: ' . $this->blog_skin_model->last_error();

            showFatalError($subject, $message);
        }

        // --------------------------------------------------------------------------

        //  Pass to $this->data, for the views
        $this->data['skin'] = $this->_skin;

        // --------------------------------------------------------------------------

        //  Load skin assets
        $assets    = !empty($this->_skin->assets)     ? $this->_skin->assets     : array();
        $cssInline = !empty($this->_skin->css_inline) ? $this->_skin->css_inline : array();
        $jsInline  = !empty($this->_skin->js_inline)  ? $this->_skin->js_inline  : array();

        $this->loadSkinAssets($assets, $cssInline, $jsInline, $this->_skin->url);

        // --------------------------------------------------------------------------

        //  Set view data
        $this->data['blog'] = $this->blog;
    }

    // --------------------------------------------------------------------------

    /**
     * Loads any assets required by the skin
     * @param  array  $assets    An array of skin assets
     * @param  array  $cssInline An array of inline CSS
     * @param  array  $jsInline  An array of inline JS
     * @param  string $url       The URL to the skin's root directory
     * @return void
     */
    protected function loadSkinAssets($assets, $cssInline, $jsInline, $url)
    {
        //  CSS and JS
        if (!empty($assets) && is_array($assets)) {

            foreach ($assets as $asset) {

                if (is_string($asset)) {

                    $this->asset->load($url . 'assets/' . $asset);

                } else {

                    $this->asset->load($asset[0], $asset[1]);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  CSS - Inline
        if (!empty($cssInline) && is_array($cssInline)) {

            foreach ($cssInline as $asset) {

                $this->asset->inline($asset, 'CSS-INLINE');
            }
        }

        // --------------------------------------------------------------------------

        //  JS - Inline
        if (!empty($jsInline) && is_array($jsInline)) {

            foreach ($jsInline as $asset) {

                $this->asset->inline($asset, 'JS-INLINE');
            }
        }
    }
}
