<?php

/**
 * This class registers some handlers for blog settings
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Blog\Controller\BaseAdmin;

class Settings extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->getAll();

        if (!empty($blogs)) {

            $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
            $oNavGroup->setLabel('Settings');
            $oNavGroup->setIcon('fa-wrench');

            if (userHasPermission('admin:blog:settings:\d+:update')) {

                $oNavGroup->addAction('Blog');
            }

            return $oNavGroup;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        //  Fetch the blogs, each blog should have its own permission
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->getAll();

        $out = array();

        if (!empty($blogs)) {
            foreach ($blogs as $blog) {
                $permissions[$blog->id . ':update']  = $blog->label . ': Can update settings';
            }
        }

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Blog settings
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:blog:settings:\d+:update')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load models
        $this->load->model('blog/blog_model');
        $oSkinModel = Factory::model('Skin', 'nails/module-blog');

        // --------------------------------------------------------------------------

        $this->data['blogs'] = $this->blog_model->getAllFlat();

        if (empty($this->data['blogs'])) {

            if (userHasPermission('admin:blog:blog:create')) {

                $status   = 'message';
                $message  = '<strong>You don\'t have a blog!</strong> Create a new blog ';
                $message .= 'in order to configure blog settings.';
                $oSession = Factory::service('Session', 'nails/module-auth');
                $oSession->setFlashData($status, $message);
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

        //  Check user has permission
        if (!empty($this->data['selectedBlogId'])) {

            if (!userHasPermission('admin:blog:settings:' . $this->data['selectedBlogId'] . ':update')) {

                unauthorised();
            }
        }

        //  Set up the skin model for this blog
        //  @todo; work out a cleaner way of handling this
        $oSkinModel->init($this->data['selectedBlogId']);

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  Prepare update
            $settings                       = array();
            $settings['name']               = $this->input->post('name');
            $settings['url']                = $this->input->post('url');
            $settings['postName']           = strtolower($this->input->post('postName'));
            $settings['postNamePlural']     = strtolower($this->input->post('postNamePlural'));
            $settings['use_excerpts']       = (bool) $this->input->post('use_excerpts');
            $settings['gallery_enabled']    = (bool) $this->input->post('gallery_enabled');
            $settings['categories_enabled'] = (bool) $this->input->post('categories_enabled');
            $settings['tags_enabled']       = (bool) $this->input->post('tags_enabled');
            $settings['rss_enabled']        = (bool) $this->input->post('rss_enabled');

            //  Skin settings
            $settings['skin'] = $this->input->post('skin');

            //  Commenting settings
            $settings['comments_enabled']          = $this->input->post('comments_enabled');
            $settings['comments_engine']           = $this->input->post('comments_engine');
            $settings['comments_disqus_shortname'] = $this->input->post('comments_disqus_shortname');

            //  Social settings
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

            //  Sidebar
            $settings['sidebar_latest_posts']  = (bool) $this->input->post('sidebar_latest_posts');
            $settings['sidebar_categories']    = (bool) $this->input->post('sidebar_categories');
            $settings['sidebar_tags']          = (bool) $this->input->post('sidebar_tags');
            $settings['sidebar_popular_posts'] = (bool) $this->input->post('sidebar_popular_posts');

            //  @todo: Associations

            // --------------------------------------------------------------------------

            //  Sanitize blog url
            $settings['url'] .= substr($settings['url'], -1) != '/' ? '/' : '';

            // --------------------------------------------------------------------------

            //  Save
            $oAppSettingModel = Factory::model('AppSetting');

            if ($oAppSettingModel->set($settings, 'blog-' . $this->input->get('blog_id'))) {

                $this->data['success'] = 'Blog settings have been saved.';

                $oRoutesService = Factory::service('Routes');

                if (!$oRoutesService->update()) {

                    $this->data['warning']  = '<strong>Warning:</strong> while the blog settings were updated, the ';
                    $this->data['warning'] .= 'routes file could not be updated. The blog may not behave as expected,';
                }

            } else {

                $this->data['error'] = 'There was a problem saving settings.';
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['skins']        = $oSkinModel->getAll();
        $this->data['skinSelected'] = $oSkinModel->getEnabled();

        if (!empty($this->data['selectedBlogId'])) {

            $this->data['settings'] = appSetting(null, 'blog-' . $this->data['selectedBlogId'], true);
        }

        // --------------------------------------------------------------------------

        //  Load assets
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.settings.min.js', 'nails/module-blog');

        // --------------------------------------------------------------------------

        //  Set page title
        $this->data['page']->title = 'Settings &rsaquo; Blog';

        if (!empty($this->data['blogs'][$this->input->get('blog_id')])) {

            $this->data['page']->title .= ' &rsaquo; ' . $this->data['blogs'][$this->input->get('blog_id')];
        }

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:blog:blog:manage')) {
            Helper::addHeaderButton(
                'admin/blog/blog/index',
                'Manage Blogs'
            );
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }
}
