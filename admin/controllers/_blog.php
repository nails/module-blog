<?php

//  Include NAILS_Admin_Controller; executes common admin functionality.
require_once '_admin.php';

/**
 * Manage the blog
 *
 * @package     Nails
 * @subpackage  module-admin
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog extends NAILS_Admin_Controller
{
    protected $blogId;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroupings
     * @return stdClass
     */
    public static function announce()
    {
        if (!isModuleEnabled('nailsapp/module-blog')) {

            return false;
        }

        // --------------------------------------------------------------------------

        //  Fetch the blogs, each blog should have it's own admin section
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                $d = new stdClass();

                // --------------------------------------------------------------------------

                //  Configurations
                $d->name = count($blogs) > 1 ? 'Blog: ' . $blog->label : 'Blog';
                $d->icon = 'fa-pencil-square-o';

                // --------------------------------------------------------------------------

                //  Navigation options
                $d->funcs                           = array();
                $d->funcs[$blog->id . '/index']     = 'Manage Posts';

                $hasPermission = user_has_permission('admin.blog:' . $blog->id . '.category_manage');
                $enabled       = app_setting('categories_enabled', 'blog-' . $blog->id);

                if ($hasPermission && $enabled) {

                    $d->funcs[$blog->id . '/manage/category'] = 'Manage Categories';
                }

                $hasPermission = user_has_permission('admin.blog:' . $blog->id . '.tag_manage');
                $enabled       = app_setting('tags_enabled', 'blog-' . $blog->id);

                if ($hasPermission && $enabled) {

                    $d->funcs[$blog->id . '/manage/tag'] = 'Manage Tags';
                }

                // --------------------------------------------------------------------------

                $out[$blog->id] = $d;
            }

        } else {

            $d                       = new stdClass();
            $d->name                 = 'Blog';
            $d->icon                 = 'fa-pencil-square-o';
            $d->funcs                = array();
            $d->funcs['create_blog'] = 'Create New Blog';

            $out = $d;
        }

        // --------------------------------------------------------------------------

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @param  string $classIndex The class_index value, used when multiple admin instances are available
     * @return array
     */
    public static function permissions($classIndex = null)
    {
        $permissions = parent::permissions($classIndex);

        // --------------------------------------------------------------------------

        //  Posts
        $permissions['post_create']  = 'Can create posts';
        $permissions['post_edit']    = 'Can edit posts';
        $permissions['post_delete']  = 'Can delete posts';
        $permissions['post_restore'] = 'Can restore posts';

        //  Categories
        $permissions['category_manage'] = 'Can manage categories';
        $permissions['category_create'] = 'Can create categories';
        $permissions['category_edit']   = 'Can edit categories';
        $permissions['category_delete'] = 'Can delete categories';

        //  Tags
        $permissions['tag_manage'] = 'Can manage tags';
        $permissions['tag_create'] = 'Can create tags';
        $permissions['tag_edit']   = 'Can edit tags';
        $permissions['tag_delete'] = 'Can delete tags';

        // --------------------------------------------------------------------------

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Constructs the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $this->load->model('blog/blog_model');
        $this->load->model('blog/blog_post_model');
        $this->load->model('blog/blog_category_model');
        $this->load->model('blog/blog_tag_model');

        // --------------------------------------------------------------------------

        $this->data['blogs'] = $this->blog_model->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Browse posts
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage Posts';

        // --------------------------------------------------------------------------

        //  Define the $data variable, this'll be passed to the get_all() and count_all() methods
        $data = array('where' => array(), 'sort' => array());

        // --------------------------------------------------------------------------

        //  Restricting to appropriate blog
        $data['where'][] = array('column' => 'blog_id', 'value' => $this->blogId);

        // --------------------------------------------------------------------------

        //  Set useful vars
        $page       = $this->input->get('page')     ? $this->input->get('page')     : 0;
        $per_page   = $this->input->get('per_page') ? $this->input->get('per_page') : 50;
        $sort_on    = $this->input->get('sort_on')  ? $this->input->get('sort_on')  : 'bp.published';
        $sort_order = $this->input->get('order')    ? $this->input->get('order')    : 'desc';
        $search     = $this->input->get('search')   ? $this->input->get('search')   : '';

        //  Set sort variables for view and for $data
        $this->data['sort_on']      = $data['sort']['column']  = $sort_on;
        $this->data['sort_order']   = $data['sort']['order']   = $sort_order;
        $this->data['search']       = $data['search']          = $search;

        //  Define and populate the pagination object
        $this->data['pagination']             = new stdClass();
        $this->data['pagination']->page       = $page;
        $this->data['pagination']->per_page   = $per_page;
        $this->data['pagination']->total_rows = $this->blog_post_model->count_all($data);

        //  Fetch all the items for this page
        $this->data['posts'] = $this->blog_post_model->get_all($page, $per_page, $data);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a blog post
     * @return void
     **/
    public function create()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.post_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Create New Post';

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('is_published',    '', 'xss_clean');
            $this->form_validation->set_rules('published',       '', 'xss_clean');
            $this->form_validation->set_rules('title',           '', 'xss_clean|required');
            $this->form_validation->set_rules('excerpt',         '', 'xss_clean');
            $this->form_validation->set_rules('image_id',        '', 'xss_clean');
            $this->form_validation->set_rules('body',            '', 'required');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_keywords',    '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                //  Prepare data
                $data                    = array();
                $data['blog_id']         = $this->blogId;
                $data['title']           = $this->input->post('title');
                $data['excerpt']         = $this->input->post('excerpt');
                $data['image_id']        = $this->input->post('image_id');
                $data['body']            = $this->input->post('body');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_published']    = (bool) $this->input->post('is_published');
                $data['published']       = $this->input->post('published');
                $data['associations']    = $this->input->post('associations');
                $data['gallery']         = $this->input->post('gallery');

                if (app_setting('categories_enabled', 'blog-' . $this->blogId)) {

                    $data['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blogId)) {

                    $data['tags'] = $this->input->post('tags');
                }

                $post_id = $this->blog_post_model->create($data);

                if ($post_id) {

                    //  Update admin changelog
                    $this->admin_changelog_model->add('created', 'a', 'blog post', $post_id, $data['title'], 'admin/blog/edit/' . $post_id);

                    // --------------------------------------------------------------------------

                    //  Set flashdata and redirect
                    $this->session->set_flashdata('success', '<strong>Success!</strong> Post was created.');
                    redirect('admin/blog/' . $this->blogId);

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (app_setting('categories_enabled', 'blog-' . $this->blogId)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blogId);

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blogId)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blogId);

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);
        }

        // --------------------------------------------------------------------------

        //  Load associations
        $this->data['associations'] = $this->blog_model->get_associations();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.create_edit.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog post
     * @return void
     **/
    public function edit()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.post_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(5);

        $this->data['post'] = $this->blog_post_model->get_by_id($post_id);

        if (!$this->data['post']) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I could\'t find a post by that ID.');
            redirect('admin/blog/' . $this->blogId);
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit Post &rsaquo; ' . $this->data['post']->title;

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('is_published', '', 'xss_clean');
            $this->form_validation->set_rules('published', '', 'xss_clean');
            $this->form_validation->set_rules('title', '', 'xss_clean|required');
            $this->form_validation->set_rules('excerpt', '', 'xss_clean');
            $this->form_validation->set_rules('image_id', '', 'xss_clean');
            $this->form_validation->set_rules('body', '', 'required');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean');

            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                //  Prepare data
                $data                    = array();
                $data['title']           = $this->input->post('title');
                $data['excerpt']         = $this->input->post('excerpt');
                $data['image_id']        = $this->input->post('image_id');
                $data['body']            = $this->input->post('body');
                $data['seo_description'] = $this->input->post('seo_description');
                $data['seo_keywords']    = $this->input->post('seo_keywords');
                $data['is_published']    = (bool) $this->input->post('is_published');
                $data['published']       = $this->input->post('published');
                $data['associations']    = $this->input->post('associations');
                $data['gallery']         = $this->input->post('gallery');

                if (app_setting('categories_enabled', 'blog-' . $this->blogId)) {

                    $data['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blogId)) {

                    $data['tags'] = $this->input->post('tags');
                }

                if ($this->blog_post_model->update($post_id, $data)) {

                    //  Update admin change log
                    foreach ($data as $field => $value) {

                        if (isset($this->data['post']->$field)) {

                            switch ($field) {

                                case 'associations':

                                    //  @TODO: changelog associations
                                    break;

                                case 'categories':

                                    $old_categories = array();
                                    $new_categories = array();

                                    foreach ($this->data['post']->$field as $v) {

                                        $old_categories[] = $v->label;
                                    }

                                    if (is_array($value)) {

                                        foreach ($value as $v) {

                                            $temp = $this->blog_category_model->get_by_id($v);

                                            if ($temp) {

                                                $new_categories[] = $temp->label;
                                            }
                                        }
                                    }

                                    asort($old_categories);
                                    asort($new_categories);

                                    $old_categories = implode(',', $old_categories);
                                    $new_categories = implode(',', $new_categories);

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/accounts/edit/' . $post_id, $field, $old_categories, $new_categories, false);
                                    break;

                                case 'tags':

                                    $old_tags = array();
                                    $new_tags = array();

                                    foreach ($this->data['post']->$field as $v) {

                                        $old_tags[] = $v->label;
                                    }

                                    if (is_array($value)) {

                                        foreach ($value as $v) {

                                            $temp = $this->blog_tag_model->get_by_id($v);

                                            if ($temp) {

                                                $new_tags[] = $temp->label;
                                            }
                                        }
                                    }

                                    asort($old_tags);
                                    asort($new_tags);

                                    $old_tags = implode(',', $old_tags);
                                    $new_tags = implode(',', $new_tags);

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/accounts/edit/' . $post_id, $field, $old_tags, $new_tags, false);
                                    break;

                                default :

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/accounts/edit/' . $post_id, $field, $this->data['post']->$field, $value, false);
                                    break;
                            }
                        }
                    }

                    // --------------------------------------------------------------------------

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Post was updated.');
                    redirect('admin/blog/' . $this->blogId);

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (app_setting('categories_enabled', 'blog-' . $this->blogId)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blogId);

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blogId)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blogId);

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);
        }

        // --------------------------------------------------------------------------

        //  Load associations
        $this->data['associations'] = $this->blog_model->get_associations($this->data['post']->id);

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.create_edit.js', true);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog post
     * @return void
     */
    public function delete()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.post_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(5);
        $post    = $this->blog_post_model->get_by_id($post_id);

        if (!$post || $post->blog->id != $this->blogId) {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I could\'t find a post by that ID.');
            redirect('admin/blog/' . $this->blogId);
        }

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->delete($post_id)) {

            $flashdata  = '<strong>Success!</strong> Post was deleted successfully.';
            $flashdata .=  user_has_permission('admin.blog:' . $this->blogId . '.post_restore') ? ' ' . anchor('admin/blog/' . $this->blogId . '/restore/' . $post_id, 'Undo?') : '';

            $this->session->set_flashdata('success', $flashdata);

            //  Update admin changelog
            $this->admin_changelog_model->add('deleted', 'a', 'blog post', $post_id, $post->title);

        } else {

            $this->session->set_flashdata('error', '<strong>Sorry,</strong> I failed to delete that post.');
        }

        redirect('admin/blog/' . $this->blogId);
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a blog post
     * @return void
     */
    public function restore()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.post_restore')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(5);

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->restore($post_id)) {

            $post = $this->blog_post_model->get_by_id($post_id);

            $this->session->set_flashdata('success', '<strong>Success!</strong> Post was restored successfully.');

            //  Update admin changelog
            $this->admin_changelog_model->add('restored', 'a', 'blog post', $post_id, $post->title, 'admin/blog/edit/' . $post_id);

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> I failed to restore that post. ';
            $message .= $this->blog_post_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/' . $this->blogId);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage various aspects of the blog
     * @return void
     */
    public function manage()
    {
        $method = $this->uri->segment(5) ? $this->uri->segment(5) : 'index';
        $method = ucfirst(strtolower($method));

        if (method_exists($this, 'manage' . $method)) {

            //  Is fancybox?
            $this->data['is_fancybox'] = $this->input->get('is_fancybox') ? '?is_fancybox=1' : '';

            //  Override the header and footer
            if ($this->data['is_fancybox']) {

                $this->data['headerOverride'] = 'structure/headerBlank';
                $this->data['footerOverride'] = 'structure/footerBlank';
            }

            //  Start the page title
            $this->data['page']->title = 'Manage &rsaquo; ';

            //  Call method
            $this->{'manage' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Manage blog categories
     * @return void
     */
    protected function manageCategory()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.category_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('blog/blog_category_model');

        $method = $this->uri->segment(6) ? $this->uri->segment(6) : 'index';
        $method = ucfirst(strtolower($method));

        if (method_exists($this, 'manageCategory' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Categories ';

            $this->{'manageCategory' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse blog categories
     * @return void
     */
    protected function manageCategoryIndex()
    {
        $data                  = array();
        $data['include_count'] = true;
        $data['where']         = array();
        $data['where'][]       = array('column' => 'blog_id', 'value' => $this->blogId);

        $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/category/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }


    // --------------------------------------------------------------------------

    /**
     * Create a new blog category
     * @return void
     */
    protected function manageCategoryCreate()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.category_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length', lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                  = new stdClass();
                $data->blog_id         = $this->blogId;
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_category_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Category created successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/category' . $this->data['is_fancybox']);

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there was a problem creating the Category. ';
                    $this->data['error'] .= $this->blog_category_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->blog_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/category/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog category
     * @return void
     */
    protected function manageCategoryEdit()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.category_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['category'] = $this->blog_category_model->get_by_id($this->uri->segment(7));

        if (empty($this->data['category'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length', lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                  = new stdClass();
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_category_model->update($this->data['category']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Category saved successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/category' . $this->data['is_fancybox']);

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there was a problem saving the Category. ';
                    $this->data['error'] .= $this->blog_category_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Edit &rsaquo; ' . $this->data['category']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->blog_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/category/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog category
     * @return void
     */
    protected function manageCategoryDelete()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.category_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(7);

        if ($this->blog_category_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Category was deleted successfully.');

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem deleting the Category. ';
            $message .= $this->blog_category_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/' . $this->blogId . '/manage/category' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Manage blog tags
     * @return void
     */
    protected function manageTag()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.tag_manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Load model
        $this->load->model('blog/blog_tag_model');

        $method = $this->uri->segment(6) ? $this->uri->segment(6) : 'index';
        $method = ucfirst(strtolower($method));

        if (method_exists($this, 'manageTag' . $method)) {

            //  Extend the title
            $this->data['page']->title .= 'Tags ';

            $this->{'manageTag' . $method}();

        } else {

            show_404();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse blog tags
     * @return void
     */
    protected function manageTagindex()
    {
        $data                  = array();
        $data['include_count'] = true;
        $data['where']         = array();
        $data['where'][]       = array('column' => 'blog_id', 'value' => $this->blogId);

        $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/tag/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new blog tag
     * @return void
     */
    protected function manageTagCreate()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.tag_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length',   lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                  = new stdClass();
                $data->blog_id         = $this->blogId;
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag created successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['is_fancybox']);

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there was a problem creating the Tag. ';
                    $this->data['error'] .= $this->blog_tag_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title .= '&rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->blog_tag_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/tag/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog tag
     * @return void
     */
    protected function manageTagEdit()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.tag_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['tag'] = $this->blog_tag_model->get_by_id($this->uri->segment(7));

        if (empty($this->data['tag'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $this->load->library('form_validation');

            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $this->form_validation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $this->form_validation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $this->form_validation->set_message('required', lang('fv_required'));
            $this->form_validation->set_message('max_length', lang('fv_max_length'));

            if ($this->form_validation->run()) {

                $data                  = new stdClass();
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->update($this->data['tag']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag saved successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['is_fancybox']);

                } else {

                    $this->data['error']  = '<strong>Sorry,</strong> there was a problem saving the Tag. ';
                    $this->data['error'] .= $this->blog_tag_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Edit &rsaquo; ' . $this->data['tag']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->blog_tag_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/blog/manage/tag/edit', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog tag
     * @return void
     */
    protected function manageTagDelete()
    {
        if (!user_has_permission('admin.blog:' . $this->blogId . '.tag_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(7);

        if ($this->blog_tag_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Tag was deleted successfully.');

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem deleting the Tag. ';
            $message .= $this->blog_tag_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['is_fancybox']);
    }

    // --------------------------------------------------------------------------

    /**
     * Checks whether any blogs exist, if not redirect suer to create a blog in
     * settings. Otherwise, verify the blog ID and allow the call through
     * @param  string $method The method called
     * @return void
     */
    public function _remap($method)
    {
        //  Creating a new blog?
        if ($method == 'create_blog') {

            redirect('admin/settings/blog/create');
        }

        //  We got blogs?
        if (empty($this->data['blogs'])) {

            if ($this->user_model->is_superuser()) {

                $status  = 'message';
                $message = '<strong>You don\'t have a blog!</strong> Create a new blog in order to manage posts.';
                $this->session->set_flashdata($status, $message);

                redirect('admin/settings/blog/create');

            } else {

                show_404();
            }
        }

        // --------------------------------------------------------------------------

        $this->blogId = $this->uri->segment(3);
        $this->data['blog_id'] = $this->blogId;

        $found = false;
        foreach ($this->data['blogs'] as $blog) {

            if ($blog->id == $this->blogId) {

                $found = true;
                break;
            }
        }

        // --------------------------------------------------------------------------

        $method = $this->uri->segment(4) ? $this->uri->segment(4) : 'index';

        if ($found && method_exists($this, $method) && substr($method, 0, 1) != '_') {

            $this->{$method}();

        } else {

            show_404();
        }
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' ADMIN MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
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
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_BLOG')) {

    /**
     * Proxy class for NAILS_Blog
     */
    class Blog extends NAILS_Blog
    {
    }
}
