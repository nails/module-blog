<?php

/**
 * Name:        Blog
 *
 * Description: This controller handles the front page of the blog
 *
 **/

/**
 * OVERLOADING NAILS' BLOG MODULE
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

//  Include _blog.php; executes common functionality
require_once '_blog.php';

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
        $perPage  = app_setting('home_per_page', 'blog-' . $this->blog->id);
        $perPage  = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !app_setting('use_excerpts', 'blog-' . $this->blog->id);
        $data['include_gallery'] = app_setting('home_show_gallery', 'blog-' . $this->blog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'blog_id',      'value' => $this->blog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->get_all($page, $perPage, $data);
        $this->data['pagination']->total = $this->blog_post_model->count_all($data);

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        $this->data['isIndex'] = true;

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->_skin->path . 'views/browse', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * View a single post
     * @return void
     */
    public function single($id = null)
    {
        //  Get the single post by its slug
        if ($id) {

            $this->data['post'] = $this->blog_post_model->get_by_id($id);

        } else {

            $this->data['post'] = $this->blog_post_model->get_by_slug($this->uri->rsegment(3));
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
            if (!userHasPermission('admin:blog:post:' . $this->blog->id . ':manage')) {

                show_404();
            }
        }

        // --------------------------------------------------------------------------

        //  Correct URL?
        if (site_url(uri_string()) !== $this->data['post']->url) {

            redirect($this->data['post']->url, 301);
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title            = $this->blog->label . ': ';
        $this->data['page']->title           .= $this->data['post']->seo_title ? $this->data['post']->seo_title : $this->data['post']->title;
        $this->data['page']->seo->description = $this->data['post']->seo_description;
        $this->data['page']->seo->keywords    = $this->data['post']->seo_keywords;

        // --------------------------------------------------------------------------

        //  Assets
        if (app_setting('social_enabled', 'blog-' . $this->blog->id)) {

            $this->asset->load('social-likes/social-likes.min.js', 'NAILS-BOWER');

            switch (app_setting('social_skin', 'blog-' . $this->blog->id)) {

                case 'FLAT':

                    $this->asset->load('social-likes/social-likes_flat.css', 'NAILS-BOWER');
                    break;

                case 'BIRMAN':

                    $this->asset->load('social-likes/social-likes_birman.css', 'NAILS-BOWER');
                    break;

                case 'CLASSIC':
                default:

                    $this->asset->load('social-likes/social-likes_classic.css', 'NAILS-BOWER');
                    break;
            }
        }

        // --------------------------------------------------------------------------

        $this->data['isSingle'] = true;

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view($this->_skin->path . 'views/single', $this->data);
        $this->load->view('structure/footer', $this->data);

        // --------------------------------------------------------------------------

        //  Register a hit
        $data             = array();
        $data['user_id']  = activeUser('id');
        $data['referrer'] = $this->input->server('HTTP_REFERER');

        $this->blog_post_model->add_hit($this->data['post']->id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse a categry
     * @return void
     */
    public function category()
    {
        if (!app_setting('categories_enabled', 'blog-' . $this->blog->id) || !$this->uri->rsegment(4)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get category
        $this->data['category'] = $this->blog_category_model->get_by_slug($this->uri->rsegment(4));

        if (!$this->data['category']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title            = $this->blog->label . ': Posts in category "' . $this->data['category']->label . '"';
        $this->data['page']->seo->description = 'All posts on ' . APP_NAME . ' posted in the  ' . $this->data['category']->label . ' category ';
        $this->data['page']->seo->keywords    = '';

        // --------------------------------------------------------------------------

        //  Handle pagination
        $page     = $this->uri->rsegment(5);
        $perPage = app_setting('home_per_page', 'blog-' . $this->blog->id);
        $perPage = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !app_setting('use_excerpts', 'blog-' . $this->blog->id);
        $data['include_gallery'] = app_setting('home_show_gallery', 'blog-' . $this->blog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'bp.blog_id',   'value' => $this->blog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->get_with_category($this->data['category']->id, $page, $perPage, $data);
        $this->data['pagination']->total = $this->blog_post_model->count_with_category($this->data['category']->id, $data);

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
        $this->data['archive_title']       = 'Posts in category "' . $this->data['category']->label . '"';
        $this->data['archive_description'] = $this->data['category']->description;
        $this->data['isCategory']          = true;

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->_skin->path . 'views/browse', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Browse a tag
     * @return void
     */
    public function tag()
    {
        if (!app_setting('tags_enabled', 'blog-' . $this->blog->id) || !$this->uri->rsegment(4)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get tag
        $this->data['tag'] = $this->blog_tag_model->get_by_slug($this->uri->rsegment(4));

        if (!$this->data['tag']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Widgets
        $this->fetchSidebarWidgets();

        // --------------------------------------------------------------------------

        //  Meta
        $this->data['page']->title            = $this->blog->label . ': Posts tagged with "' . $this->data['tag']->label . '"';
        $this->data['page']->seo->description = 'All posts on ' . APP_NAME . ' tagged with  ' . $this->data['tag']->label . ' ';
        $this->data['page']->seo->keywords    = '';

        // --------------------------------------------------------------------------

        //  Handle pagination
        $page     = $this->uri->rsegment(5);
        $perPage = app_setting('home_per_page', 'blog-' . $this->blog->id);
        $perPage = $perPage ? $perPage : 10;

        $this->data['pagination']           = new stdClass();
        $this->data['pagination']->page     = $page;
        $this->data['pagination']->per_page = $perPage;

        // --------------------------------------------------------------------------

        //  Send any additional data
        $data                    = array();
        $data['include_body']    = !app_setting('use_excerpts', 'blog-' . $this->blog->id);
        $data['include_gallery'] = app_setting('home_show_gallery', 'blog-' . $this->blog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'bp.blog_id',   'value' => $this->blog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        // --------------------------------------------------------------------------

        //  Load posts and count
        $this->data['posts'] = $this->blog_post_model->get_with_tag($this->data['tag']->id, $page, $perPage, $data);
        $this->data['pagination']->total = $this->blog_post_model->count_all($data);

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
        $this->data['archive_title']       = 'Posts in tag "' . $this->data['tag']->label . '"';
        $this->data['archive_description'] = $this->data['tag']->description;
        $this->data['isTag']               = true;

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view($this->_skin->path . 'views/browse', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * RSS Feed for the blog
     * @return void
     */
    public function rss()
    {
        if (!app_setting('rss_enabled', 'blog-' . $this->blog->id)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Get posts
        $data                    = array();
        $data['include_body']    = true;
        $data['include_gallery'] = app_setting('home_show_gallery', 'blog-' . $this->blog->id);
        $data['sort']            = array('bp.published', 'desc');

        //  Only published items which are not schduled for the future
        $data['where']   = array();
        $data['where'][] = array('column' => 'blog_id',      'value' => $this->blog->id);
        $data['where'][] = array('column' => 'is_published', 'value' => true);
        $data['where'][] = array('column' => 'published <=', 'value' => 'NOW()', 'escape' => false);

        $this->data['posts'] = $this->blog_post_model->get_all(null, null, $data);
        $this->data['isRss'] = true;

        //  Set Output
        $this->output->set_content_type('text/xml; charset=UTF-8');
        $this->load->view($this->_skin->path . 'views/rss', $this->data);
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

        if (app_setting('sidebar_latest_posts', 'blog-' . $this->blog->id)) {

            $this->data['widget']->latest_posts = $this->blog_widget_model->latest_posts($this->blog->id);
        }

        if (app_setting('sidebar_categories', 'blog-' . $this->blog->id)) {

            $this->data['widget']->categories = $this->blog_widget_model->categories($this->blog->id);
        }

        if (app_setting('sidebar_tags', 'blog-' . $this->blog->id)) {

            $this->data['widget']->tags = $this->blog_widget_model->tags($this->blog->id);
        }

        if (app_setting('sidebar_popular_posts', 'blog-' . $this->blog->id)) {

            $this->data['widget']->popular_posts = $this->blog_widget_model->popular_posts($this->blog->id);
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
