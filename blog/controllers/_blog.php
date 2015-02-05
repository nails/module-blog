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

    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Check this module is enabled in settings
        if (! isModuleEnabled('nailsapp/module-blog')) {

            //  Cancel execution, module isn't enabled
            show_404();
        }

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

        //  Set view data
        $this->data['blog'] = $this->blog;
    }
}
