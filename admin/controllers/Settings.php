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

            $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
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
    public static function permissions(): array
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

        $oInput = Factory::service('Input');

        // --------------------------------------------------------------------------

        //  Load models
        $this->load->model('blog/blog_model');
        $oSkinModel = Factory::model('Skin', 'nails/module-blog');

        // --------------------------------------------------------------------------

        $this->data['blogs'] = $this->blog_model->getAllFlat();

        if (empty($this->data['blogs'])) {

            if (userHasPermission('admin:blog:blog:create')) {

                $oUserFeedback = Factory::service('UserFeedback');
                $oUserFeedback->warning('<strong>You don\'t have a blog!</strong> Create a new blog in order to configure blog settings.');
                redirect('admin/blog/blog/create');

            } else {

                show404();
            }
        }

        if (count($this->data['blogs']) == 1) {

            reset($this->data['blogs']);
            $this->data['selectedBlogId'] = key($this->data['blogs']);

        } elseif ($oInput->get('blog_id')) {

            if (!empty($this->data['blogs'][$oInput->get('blog_id')])) {

                $this->data['selectedBlogId'] = $oInput->get('blog_id');
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
        if ($oInput->post()) {

            //  Prepare update
            $settings                       = array();
            $settings['name']               = $oInput->post('name');
            $settings['url']                = $oInput->post('url');
            $settings['postName']           = strtolower($oInput->post('postName'));
            $settings['postNamePlural']     = strtolower($oInput->post('postNamePlural'));
            $settings['use_excerpts']       = (bool) $oInput->post('use_excerpts');
            $settings['gallery_enabled']    = (bool) $oInput->post('gallery_enabled');
            $settings['categories_enabled'] = (bool) $oInput->post('categories_enabled');
            $settings['tags_enabled']       = (bool) $oInput->post('tags_enabled');
            $settings['rss_enabled']        = (bool) $oInput->post('rss_enabled');

            //  Skin settings
            $settings['skin'] = $oInput->post('skin');

            //  Commenting settings
            $settings['comments_enabled']          = $oInput->post('comments_enabled');
            $settings['comments_engine']           = $oInput->post('comments_engine');
            $settings['comments_disqus_shortname'] = $oInput->post('comments_disqus_shortname');

            //  Social settings
            $settings['social_facebook_enabled']   = (bool) $oInput->post('social_facebook_enabled');
            $settings['social_twitter_enabled']    = (bool) $oInput->post('social_twitter_enabled');
            $settings['social_twitter_via']        = $oInput->post('social_twitter_via');
            $settings['social_googleplus_enabled'] = (bool) $oInput->post('social_googleplus_enabled');
            $settings['social_pinterest_enabled']  = (bool) $oInput->post('social_pinterest_enabled');
            $settings['social_skin']               = $oInput->post('social_skin');
            $settings['social_layout']             = $oInput->post('social_layout');
            $settings['social_layout_single_text'] = $oInput->post('social_layout_single_text');
            $settings['social_counters']           = (bool) $oInput->post('social_counters');

            //  If any of the above are enabled, then social is enabled.
            $settings['social_enabled'] = $settings['social_facebook_enabled'] || $settings['social_twitter_enabled'] || $settings['social_googleplus_enabled'] || $settings['social_pinterest_enabled'];

            //  Sidebar
            $settings['sidebar_latest_posts']  = (bool) $oInput->post('sidebar_latest_posts');
            $settings['sidebar_categories']    = (bool) $oInput->post('sidebar_categories');
            $settings['sidebar_tags']          = (bool) $oInput->post('sidebar_tags');
            $settings['sidebar_popular_posts'] = (bool) $oInput->post('sidebar_popular_posts');

            //  @todo: Associations

            // --------------------------------------------------------------------------

            //  Sanitize blog url
            $settings['url'] .= substr($settings['url'], -1) != '/' ? '/' : '';

            // --------------------------------------------------------------------------

            //  Save
            $oAppSettingService = Factory::service('AppSetting');

            if ($oAppSettingService->set($settings, 'blog-' . $oInput->get('blog_id'))) {

                $this->data['success'] = 'Blog settings have been saved.';

                try {

                    /** @var \Nails\Common\Service\Event $oEventService */
                    $oEventService = Factory::service('Event');
                    $oEventService->trigger(\Nails\Common\Events::ROUTES_UPDATE);

                } catch (\Exception $e) {
                    $this->data['warning']  = '<strong>Warning:</strong> while the blog settings were updated, the ';
                    $this->data['warning'] .= 'routes file could not be updated. The blog may not behave as expected,';
                    $this->data['warning'] .= 'The following reason was given: ' . $e->getMessage();
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

            $this->data['settings'] = appSetting(null, 'blog-' . $this->data['selectedBlogId'], null, true);
        }

        // --------------------------------------------------------------------------

        //  Load assets
        $oAsset = Factory::service('Asset');
        //  @todo (Pablo - 2019-09-12) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.settings.js', 'nails/module-blog');

        // --------------------------------------------------------------------------

        //  Set page title
        $this->data['page']->title = 'Settings &rsaquo; Blog';

        if (!empty($this->data['blogs'][$oInput->get('blog_id')])) {

            $this->data['page']->title .= ' &rsaquo; ' . $this->data['blogs'][$oInput->get('blog_id')];
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
