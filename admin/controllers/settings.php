<?php

/**
 * This class registers some handlers for blog settings
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Settings extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Settings');
        $navGroup->addMethod('Blog');

        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Blog settings
     * @return void
     */
    public function index()
    {
        //  Load models
        $this->load->model('blog/blog_model');
        $this->load->model('blog/blog_skin_model');

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Blog';

        // --------------------------------------------------------------------------

        $this->data['blogs'] = $this->blog_model->get_all_flat();

        if (empty($this->data['blogs'])) {

            if ($this->user_model->is_superuser()) {

                $status   = 'message';
                $message  = '<strong>You don\'t have a blog!</strong> Create a new blog ';
                $message .= 'in order to configure blog settings.';
                $this->session->set_flashdata($status, $message);
                redirect('admin/blog/blog/create');

            } else {

                show_404();
            }
        }

        if (count($this->data['blogs']) == 1) {

            reset($this->data['blogs']);
            $this->data['selectedBlogId'] = key($this->data['blogs']);

        } elseif ($this->input->get('blog_id')) {

            if (!empty($this->data['blogs'][$this->input->get('blog_id')])) {

                $this->data['selectedBlogId'] = $this->input->get('blog_id');
            }

            if (empty($this->data['selectedBlogId'])) {

                $this->data['error'] = 'There is no blog by that ID.';
            }
        }

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $method = $this->input->post('update');

            if (method_exists($this, '_blog_update_' . $method)) {

                $this->{'_blog_update_' . $method}();

            } else {

                $this->data['error']  = 'I can\'t determine what ';
                $this->data['error'] .= 'type of update you are trying to perform.';
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['skins'] = $this->blog_skin_model->get_available();

        if (!empty($this->data['selectedBlogId'])) {

            $this->data['settings'] = app_setting(null, 'blog-' . $this->data['selectedBlogId'], true);
        }

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->load('nails.admin.blog.settings.min.js', true);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Blog settings
     * @return void
     */
    protected function _blog_update_settings()
    {
        //  Prepare update
        $settings                       = array();
        $settings['name']               = $this->input->post('name');
        $settings['url']                = $this->input->post('url');
        $settings['use_excerpts']       = (bool) $this->input->post('use_excerpts');
        $settings['gallery_enabled']    = (bool) $this->input->post('gallery_enabled');
        $settings['categories_enabled'] = (bool) $this->input->post('categories_enabled');
        $settings['tags_enabled']       = (bool) $this->input->post('tags_enabled');
        $settings['rss_enabled']        = (bool) $this->input->post('rss_enabled');

        // --------------------------------------------------------------------------

        //  Sanitize blog url
        $settings['url'] .= substr($settings['url'], -1) != '/' ? '/' : '';

        // --------------------------------------------------------------------------

        //  Save
        if ($this->app_setting_model->set($settings, 'blog-' . $this->input->get('blog_id'))) {

            $this->data['success'] = 'Blog settings have been saved.';

            $this->load->model('routes_model');

            if (!$this->routes_model->update('shop')) {

                $this->data['warning']  = '<strong>Warning:</strong> while the blog settings were updated, the routes ';
                $this->data['warning'] .= 'file could not be updated. The blog may not behave as expected,';
            }

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Blog Skin settings
     * @return void
     */
    protected function _blog_update_skin()
    {
        //  Prepare update
        $settings         = array();
        $settings['skin'] = $this->input->post('skin');

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'blog-' . $this->input->get('blog_id'))) {

            $this->data['success'] = 'Skin settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Blog Commenting settings
     * @return void
     */
    protected function _blog_update_commenting()
    {
        //  Prepare update
        $settings                              = array();
        $settings['comments_enabled']          = $this->input->post('comments_enabled');
        $settings['comments_engine']           = $this->input->post('comments_engine');
        $settings['comments_disqus_shortname'] = $this->input->post('comments_disqus_shortname');

        // --------------------------------------------------------------------------

        //  Save
        if ($this->app_setting_model->set($settings, 'blog-' . $this->input->get('blog_id'))) {

            $this->data['success'] = 'Blog commenting settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving commenting settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Blog Social settings
     * @return void
     */
    protected function _blog_update_social()
    {
        //  Prepare update
        $settings                              = array();
        $settings['social_facebook_enabled']   = (bool) $this->input->post('social_facebook_enabled');
        $settings['social_twitter_enabled']    = (bool) $this->input->post('social_twitter_enabled');
        $settings['social_twitter_via']        = $this->input->post('social_twitter_via');
        $settings['social_googleplus_enabled'] = (bool) $this->input->post('social_googleplus_enabled');
        $settings['social_pinterest_enabled']  = (bool) $this->input->post('social_pinterest_enabled');
        $settings['social_skin']               = $this->input->post('social_skin');
        $settings['social_layout']             = $this->input->post('social_layout');
        $settings['social_layout_single_text'] = $this->input->post('social_layout_single_text');
        $settings['social_counters']           = (bool) $this->input->post('social_counters');

        //  If any of the above are enabled, then social is enabled.
        $settings['social_enabled'] = $settings['social_facebook_enabled'] || $settings['social_twitter_enabled'] || $settings['social_googleplus_enabled'] || $settings['social_pinterest_enabled'];

        // --------------------------------------------------------------------------

        //  Save
        if ($this->app_setting_model->set($settings, 'blog-' . $this->input->get('blog_id'))) {

            $this->data['success'] = 'Blog social settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving social settings.';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Set Blog Sidebar settings
     * @return void
     */
    protected function _blog_update_sidebar()
    {
        //  Prepare update
        $settings                          = array();
        $settings['sidebar_latest_posts']  = (bool) $this->input->post('sidebar_latest_posts');
        $settings['sidebar_categories']    = (bool) $this->input->post('sidebar_categories');
        $settings['sidebar_tags']          = (bool) $this->input->post('sidebar_tags');
        $settings['sidebar_popular_posts'] = (bool) $this->input->post('sidebar_popular_posts');

        //  @TODO: Associations

        // --------------------------------------------------------------------------

        //  Save
        if ($this->app_setting_model->set($settings, 'blog-' . $this->input->get('blog_id'))) {

            $this->data['success'] = 'Blog sidebar settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving sidebar settings.';
        }
    }
}
