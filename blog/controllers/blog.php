<?php

/**
 * This class renders all the blog pages
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

//  Include _blog.php; executes common functionality
require_once '_blog.php';

use Nails\Factory;

class NAILS_Blog extends NAILS_Blog_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->data['isIndex']    = false;
        $this->data['isSingle']   = false;
        $this->data['isCategory'] = false;
        $this->data['isTag']      = false;
        $this->data['isRss']      = false;

        // --------------------------------------------------------------------------

        $this->data['skinLoadView'] = function ($sSkin, $aData = array()) {

            $this->loadView($sSkin, $aData);
        };
    }

    // --------------------------------------------------------------------------

    /**
     * Browse all posts
     * @return void
     */
    public function index()
    {
        //  Meta & Breadcrumbs
        $this->data['page']->title            = APP_NAME . ' Blog';
        $this->data['page']->seo->description = '';
        $this->data['page']->seo->keywords    = '';

        // --------------------------------------------------------------------------

        //  Handle pagination
        $page      = $this->uri->rsegment(3);
        $perPage  = appSetting('home_per_page', 'blog-' . $this->oBlog->id);
        $perPage  = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !appSetting('use_excerpts', 'blog-' . $this->oBlog->id);
        $data['include_gallery'] = appSetting('home_show_gallery', 'blog-' . $this->oBlog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'blog_id',      'value' => $this->oBlog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->getAll($page, $perPage, $data);
        $this->data['pagination']->total = $this->blog_post_model->countAll($data);

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        $this->data['isIndex'] = true;

        // --------------------------------------------------------------------------

        //  Load views
        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $this->loadView('browse', $this->data);
        $oView->load('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    public function preview()
    {
        $this->blog_post_model->usePreviewTables(true);
        return $this->single($this->uri->rsegment(4), true);
    }

    // --------------------------------------------------------------------------

    /**
     * View a single post
     * @return void
     */
    public function single($id = null, $bIsPreview = false)
    {
        //  Get the single post by its slug
        if ($id) {

            $this->data['post'] = $this->blog_post_model->getById($id);

        } else {

            $this->data['post'] = $this->blog_post_model->getBySlug($this->uri->rsegment(3));
        }

        // --------------------------------------------------------------------------

        //  Check we have something to show, otherwise, bail out
        if (!$this->data['post']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        /**
         * If this post's status is not published then 404, unless logged in as a user
         * with post managing permissions.
         */
        if (!$this->data['post']->is_published || strtotime($this->data['post']->published) > time()) {

            /**
             * This post hasn't been published, or is scheduled. However, check to see
             * if the user has post management permissions.
             */
            if (!userHasPermission('admin:blog:post:' . $this->oBlog->id . ':manage')) {

                show_404();
            }
        }

        // --------------------------------------------------------------------------

        //  Get associations
        $this->blog_model->getAssociations($this->data['post']->id);

        // --------------------------------------------------------------------------

        //  Correct URL?
        if (!$bIsPreview && site_url(uri_string()) !== $this->data['post']->url) {

            redirect($this->data['post']->url, 301);
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title  = $this->oBlog->label . ': ';
        $this->data['page']->title .= $this->data['post']->seo_title ? $this->data['post']->seo_title : $this->data['post']->title;
        $this->data['page']->seo->description = $this->data['post']->seo_description;
        $this->data['page']->seo->keywords    = $this->data['post']->seo_keywords;

        // --------------------------------------------------------------------------

        //  Assets
        if (appSetting('social_enabled', 'blog-' . $this->oBlog->id)) {

            $oAsset = Factory::service('Asset');
            $oAsset->load('social-likes/social-likes.min.js', 'NAILS-BOWER');

            switch (appSetting('social_skin', 'blog-' . $this->oBlog->id)) {

                case 'FLAT':
                    $oAsset->load('social-likes/social-likes_flat.css', 'NAILS-BOWER');
                    break;

                case 'BIRMAN':
                    $oAsset->load('social-likes/social-likes_birman.css', 'NAILS-BOWER');
                    break;

                case 'CLASSIC':
                default:
                    $oAsset->load('social-likes/social-likes_classic.css', 'NAILS-BOWER');
                    break;
            }
        }

        // --------------------------------------------------------------------------

        $this->data['isSingle'] = true;

        // --------------------------------------------------------------------------

        //  Load views
        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $this->loadView('single', $this->data);
        $oView->load('structure/footer', $this->data);

        // --------------------------------------------------------------------------

        //  Register a hit
        $data             = array();
        $data['user_id']  = activeUser('id');
        $data['referrer'] = $this->input->server('HTTP_REFERER');

        $this->blog_post_model->addHit($this->data['post']->id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse a categry
     * @return void
     */
    public function category()
    {
        if (!appSetting('categories_enabled', 'blog-' . $this->oBlog->id) || !$this->uri->rsegment(4)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get category
        $this->data['category'] = $this->blog_category_model->getBySlug($this->uri->rsegment(4));

        if (!$this->data['category']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title = $this->oBlog->label . ': ' . ucfirst($this->data['postNamePlural']) . ' in category "' . $this->data['category']->label . '"';
        $this->data['page']->seo->description = 'All ' . $this->data['postNamePlural'] . ' posted in the  ' . $this->data['category']->label . ' category ';
        $this->data['page']->seo->keywords    = '';

        // --------------------------------------------------------------------------

        //  Handle pagination
        $page     = $this->uri->rsegment(5);
        $perPage = appSetting('home_per_page', 'blog-' . $this->oBlog->id);
        $perPage = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !appSetting('use_excerpts', 'blog-' . $this->oBlog->id);
        $data['include_gallery'] = appSetting('home_show_gallery', 'blog-' . $this->oBlog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'bp.blog_id',   'value' => $this->oBlog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->getWithCategory(
            $this->data['category']->id,
            $page,
            $perPage,
            $data
        );
        $this->data['pagination']->total = $this->blog_post_model->countWithCategory(
            $this->data['category']->id,
            $data
        );

        // --------------------------------------------------------------------------

        //  Any SEO data?
        if (!empty($this->data['category']->seo_title)) {

            $this->data['page']->title = $this->data['category']->seo_title;
        }

        if (!empty($this->data['category']->seo_description)) {

            $this->data['page']->seo->description = $this->data['category']->seo_description;
        }

        if (!empty($this->data['category']->seo_keywords)) {

            $this->data['page']->seo->keywords = $this->data['category']->seo_keywords;
        }

        // --------------------------------------------------------------------------

        //  Finally, let the views know this is an 'archive' type page
        $this->data['archive_title']       = ucfirst($this->data['postNamePlural']) . ' in category "' . $this->data['category']->label . '"';
        $this->data['archive_description'] = $this->data['category']->description;
        $this->data['isCategory']          = true;

        // --------------------------------------------------------------------------

        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $this->loadView('browse', $this->data);
        $oView->load('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse a tag
     * @return void
     */
    public function tag()
    {
        if (!appSetting('tags_enabled', 'blog-' . $this->oBlog->id) || !$this->uri->rsegment(4)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get tag
        $this->data['tag'] = $this->blog_tag_model->getBySlug($this->uri->rsegment(4));

        if (!$this->data['tag']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title = $this->oBlog->label . ': ' . ucfirst($this->data['postNamePlural']) . ' tagged with "' . $this->data['tag']->label . '"';
        $this->data['page']->seo->description = 'All ' . $this->data['postNamePlural'] . ' tagged with  ' . $this->data['tag']->label . ' ';
        $this->data['page']->seo->keywords    = '';

        // --------------------------------------------------------------------------

        //  Handle pagination
        $page     = $this->uri->rsegment(5);
        $perPage = appSetting('home_per_page', 'blog-' . $this->oBlog->id);
        $perPage = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !appSetting('use_excerpts', 'blog-' . $this->oBlog->id);
        $data['include_gallery'] = appSetting('home_show_gallery', 'blog-' . $this->oBlog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'bp.blog_id',   'value' => $this->oBlog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->getWithTag($this->data['tag']->id, $page, $perPage, $data);
        $this->data['pagination']->total = $this->blog_post_model->countAll($data);

        // --------------------------------------------------------------------------

        //  Any SEO data?
        if (!empty($this->data['tag']->seo_title)) {

            $this->data['page']->title = $this->data['tag']->seo_title;
        }

        if (!empty($this->data['tag']->seo_description)) {

            $this->data['page']->seo->description = $this->data['tag']->seo_description;
        }

        if (!empty($this->data['tag']->seo_keywords)) {

            $this->data['page']->seo->keywords = $this->data['tag']->seo_keywords;
        }

        // --------------------------------------------------------------------------

        //  Finally, let the views know this is an 'archive' type page
        $this->data['archive_title']       = ucfirst($this->data['postNamePlural']) . ' in tag "' . $this->data['tag']->label . '"';
        $this->data['archive_description'] = $this->data['tag']->description;
        $this->data['isTag']               = true;

        // --------------------------------------------------------------------------

        $oView = Factory::service('View');
        $oView->load('structure/header', $this->data);
        $this->loadView('browse', $this->data);
        $oView->load('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * RSS Feed for the blog
     * @return void
     */
    public function rss()
    {
        if (!appSetting('rss_enabled', 'blog-' . $this->oBlog->id)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get posts
        $data                    = array();
        $data['include_body']    = true;
        $data['include_gallery'] = appSetting('home_show_gallery', 'blog-' . $this->oBlog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'blog_id',      'value' => $this->oBlog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        $this->data['posts'] = $this->blog_post_model->getAll(null, null, $data);
        $this->data['isRss'] = true;

        //  Set Output
        $oOutput = Factory::service('Output');
        $oOutput->set_content_type('text/xml; charset=UTF-8');

        $this->loadView('rss', $this->data);
    }

    // --------------------------------------------------------------------------


    /**
     * Trackback endpoint
     * @return void
     */
    public function trackback()
    {
        // @TODO: Implement trackback support, maybe.
    }

    // --------------------------------------------------------------------------

    /**
     * Pingback endpoint
     * @return void
     */
    public function pingback()
    {
        // @TODO: Implement pingback support, maybe.
    }

    // --------------------------------------------------------------------------

    /**
     * Loads all the enabled sidebar widgets
     * @return void
     */
    protected function fetchSidebarWidgets()
    {
        $this->data['widget'] = new stdClass();

        if (appSetting('sidebar_latest_posts', 'blog-' . $this->oBlog->id)) {

            $this->data['widget']->latest_posts = $this->blog_widget_model->latestPosts($this->oBlog->id);
        }

        if (appSetting('sidebar_categories', 'blog-' . $this->oBlog->id)) {

            $this->data['widget']->categories = $this->blog_widget_model->categories($this->oBlog->id);
        }

        if (appSetting('sidebar_tags', 'blog-' . $this->oBlog->id)) {

            $this->data['widget']->tags = $this->blog_widget_model->tags($this->oBlog->id);
        }

        if (appSetting('sidebar_popular_posts', 'blog-' . $this->oBlog->id)) {

            $this->data['widget']->popular_posts = $this->blog_widget_model->popularPosts($this->oBlog->id);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads a view from the skin, falls back tot he parent view if there is one.
     * @param  string $sView The view to load
     * @return void
     */
    private function loadView($sView, $aData = array())
    {
        $oView = Factory::service('View');
        $sFile = $this->oSkin->path . 'views/' . $sView;

        if (is_file($sFile . '.php')) {

            $oView->load($sFile, $aData);

        } elseif (!empty($this->oSkinParent)) {

            $sFile = $this->oSkinParent->path . 'views/' . $sView;

            if (is_file($sFile . '.php')) {

                $oView->load($sFile, $aData);

            } else {

                $sSubject = 'Failed to load blog view "' . $sView . '"';
                $sMessage = 'Failed to load blog view "' . $sView . '" (parent skin) at ' . APP_NAME;

                showFatalError($sSubject, $sMessage);
            }

        } else {

            $sSubject = 'Failed to load blog view "' . $sView . '"';
            $sMessage = 'Failed to load blog view "' . $sView . '" at ' . APP_NAME;

            showFatalError($sSubject, $sMessage);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Routes the URL
     * @return void
     */
    public function _remap()
    {
        $method = $this->uri->rsegment(3) ? $this->uri->rsegment(3) : 'index';

        if (method_exists($this, $method) && substr($method, 0, 1) != '_' && $this->input->get('id')) {

            //  Permalink
            $this->single($this->input->get('id'));

        } elseif (method_exists($this, $method) && substr($method, 0, 1) != '_') {

            //  Method exists, execute it
            $this->{$method}();

        } elseif (is_numeric($method)) {

            //  Paginating the main blog page
            $this->index();

        } else {

            //  Doesn't exist, consider rsegment(3) a slug
            $this->single();
        }
    }
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' BLOG MODULE
 *
 * The following block of code makes it simple to extend one of the core blog
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
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

if (!defined('NAILS_ALLOW_EXTENSION_BLOG')) {

    class Blog extends NAILS_Blog
    {
    }
}
