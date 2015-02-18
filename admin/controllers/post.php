<?php

/**
 * Manage Blog posts
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Post extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        //  Fetch the blogs, each blog should have its own admin nav grouping
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                if (!userHasPermission('admin:blog:post:' . $blog->id . ':manage')) {

                    continue;
                }

                //  Clear group naming
                $groupLabel = count($blogs) > 1 ? 'Blog: ' . $blog->label : 'Blog';

                //  Create the navGrouping
                $navGroup = new \Nails\Admin\Nav($groupLabel);
                $navGroup->addMethod('Manage Posts', 'index/' . $blog->id);

                $out[] = $navGroup;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        //  Fetch the blogs, each blog should have its own admin nav grouping
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                $permissions[$blog->id . ':manage']  = $blog->label . ': Can manage posts';
                $permissions[$blog->id . ':create']  = $blog->label . ': Can create posts';
                $permissions[$blog->id . ':edit']    = $blog->label . ': Can edit posts';
                $permissions[$blog->id . ':delete']  = $blog->label . ': Can delete posts';
                $permissions[$blog->id . ':restore'] = $blog->label . ': Can restore posts';

            }
        }

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

        //  Load models
        $this->load->model('blog/blog_model');
        $this->load->model('blog/blog_post_model');
        $this->load->model('blog/blog_category_model');
        $this->load->model('blog/blog_tag_model');

        // --------------------------------------------------------------------------

        //  Are we working with a valid blog?
        $blogId = $this->uri->segment(5);
        $this->blog = $this->blog_model->get_by_id($blogId);

        if (empty($this->blog)) {

            show_404();
        }

        $this->data['blog'] = $this->blog;
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

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : 'bp.published';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            'bp.published' => 'Published Date',
            'bp.modified'  => 'Modified Date',
            'bp.title'     => 'Title'
        );

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'where' => array(
                array(
                    'column' => 'blog_id',
                    'value' => $this->blog->id
                )
            ),
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords' => $keywords
        );

        //  Get the items for the page
        $totalRows           = $this->blog_post_model->count_all($data);
        $this->data['posts'] = $this->blog_post_model->get_all($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = \Nails\Admin\Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords);
        $this->data['pagination'] = \Nails\Admin\Helper::paginationObject($page, $perPage, $totalRows);

        //  Add a header button
        if (userHasPermission('admin.blog:' . $this->blog->id . '.post_create')) {

             \Nails\Admin\Helper::addHeaderButton('admin/blog/post/create/' . $this->blog->id, 'New Blog Post');
        }

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a blog post
     * @return void
     **/
    public function create()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.post_create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Create New Post';

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
                $data['blog_id']         = $this->blog->id;
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

                if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $data['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

                    $data['tags'] = $this->input->post('tags');
                }

                $post_id = $this->blog_post_model->create($data);

                if ($post_id) {

                    //  Update admin changelog
                    $this->admin_changelog_model->add('created', 'a', 'blog post', $post_id, $data['title'], 'admin/blog/post/edit/' . $this->blog->id . '/' . $post_id);

                    // --------------------------------------------------------------------------

                    //  Set flashdata and redirect
                    $this->session->set_flashdata('success', 'Post was created.');
                    redirect('admin/blog/post/index/' . $this->blog->id);

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);
        }

        // --------------------------------------------------------------------------

        //  Load associations
        $this->data['associations'] = $this->blog_model->get_associations();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.createEdit.min.js', 'NAILS');

        // --------------------------------------------------------------------------

        $inlineJs  = 'var _EDIT;';
        $inlineJs .= '$(function()';
        $inlineJs .= '{';
        $inlineJs .= '    _EDIT = new NAILS_Admin_Blog_Create_Edit();';
        $inlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $this->cdn->generate_api_upload_token() . '");';
        $inlineJs .= '});';

        $this->asset->inline($inlineJs, 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog post
     * @return void
     **/
    public function edit()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.post_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(6);

        $this->data['post'] = $this->blog_post_model->get_by_id($post_id);

        if (!$this->data['post']) {

            show_404();
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

                if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $data['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

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

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/blog/post/create/' . $blog->id . '/' . $post_id, $field, $old_categories, $new_categories, false);
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

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/blog/post/create/' . $blog->id . '/' . $post_id, $field, $old_tags, $new_tags, false);
                                    break;

                                default :

                                    $this->admin_changelog_model->add('updated', 'a', 'blog post', $post_id,  $data['title'], 'admin/blog/post/create/' . $blog->id . '/' . $post_id, $field, $this->data['post']->$field, $value, false);
                                    break;
                            }
                        }
                    }

                    // --------------------------------------------------------------------------

                    $this->session->set_flashdata('success', 'Post was updated.');
                    redirect('admin/blog/post/index/' . $this->blog->id);

                } else {

                    $this->data['error'] = lang('fv_there_were_errors');
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

            $data            = array();
            $data['where']   = array();
            $data['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);
        }

        // --------------------------------------------------------------------------

        //  Load associations
        $this->data['associations'] = $this->blog_model->get_associations($this->data['post']->id);

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.createEdit.min.js', 'NAILS');

        $inlineJs  = 'var _EDIT;';
        $inlineJs .= '$(function()';
        $inlineJs .= '{';
        $inlineJs .= '    _EDIT = new NAILS_Admin_Blog_Create_Edit();';
        $inlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $this->cdn->generate_api_upload_token() . '");';
        $inlineJs .= '});';

        $this->asset->inline($inlineJs, 'JS');

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog post
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.post_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(5);
        $post    = $this->blog_post_model->get_by_id($post_id);

        if (!$post || $post->blog->id != $this->blog->id) {

            $this->session->set_flashdata('error', 'I could\'t find a post by that ID.');
            redirect('admin/blog/post/index/' . $this->blog->id);
        }

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->delete($post_id)) {

            $flashdata  = 'Post was deleted successfully.';
            $flashdata .=  userHasPermission('admin.blog:' . $this->blog->id . '.post_restore') ? ' ' . anchor('admin/blog/post/restore/' . $blog->id . '/' . $post_id, 'Undo?') : '';

            $this->session->set_flashdata('success', $flashdata);

            //  Update admin changelog
            $this->admin_changelog_model->add('deleted', 'a', 'blog post', $post_id, $post->title);

        } else {

            $this->session->set_flashdata('error', 'I failed to delete that post.');
        }

        redirect('admin/blog/post/index/' . $this->blog->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a blog post
     * @return void
     */
    public function restore()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.post_restore')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $post_id = $this->uri->segment(5);

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->restore($post_id)) {

            $post = $this->blog_post_model->get_by_id($post_id);

            $this->session->set_flashdata('success', 'Post was restored successfully.');

            //  Update admin changelog
            $this->admin_changelog_model->add('restored', 'a', 'blog post', $post_id, $post->title, 'admin/blog/post/create/' . $blog->id . '/' . $post_id);

        } else {

            $status   = 'error';
            $message  = 'I failed to restore that post. ';
            $message .= $this->blog_post_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/post/index/' . $this->blog->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Renders a preview of the post
     * @return void
     */
    public function preview()
    {

    }
}
