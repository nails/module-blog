<?php

/**
 * Manage Blog categories
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Category extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        //  Fetch the blogs, each blog should have its own admin section
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                //  Clear group naming
                $groupLabel = count($blogs) > 1 ? 'Blog: ' . $blog->label : 'Blog';

                //  Create the navGrouping
                //  @todo respect permissions for managing categories
                $navGroup = new \Nails\Admin\Nav($groupLabel);
                $navGroup->addMethod('Manage Categories', 'index/' . $blog->id);

                $out[] = $navGroup;
            }
        }

        return $out;
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
        $this->load->model('blog/blog_category_model');

        // --------------------------------------------------------------------------

        //  Are we working with a valid blog?
        $blogId = $this->uri->segment(5);
        $this->blog = $this->blog_model->get_by_id($blogId);

        if (empty($this->blog)) {

            show_404();
        }

        $this->data['blog'] = $this->blog;

        // --------------------------------------------------------------------------

        $this->isFancybox = $this->input->get('isFancybox') ? '?isFancybox=1' : '';
        $this->data['isFancybox'] = $this->isFancybox;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse blog categories
     * @return void
     */
    public function index()
    {
        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Categories';

        // --------------------------------------------------------------------------

        $data                  = array();
        $data['include_count'] = true;
        $data['where']         = array();
        $data['where'][]       = array('column' => 'blog_id', 'value' => $this->blog->id);

        $this->data['categories'] = $this->blog_category_model->get_all(null, null, $data);

        // --------------------------------------------------------------------------

        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new blog category
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.category_create')) {

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

                $data                  = new \stdClass();
                $data->blog_id         = $this->blog->id;
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_category_model->create($data)) {

                    $status  = 'success';
                    $message = '<strong>Success!</strong> Category created successfully.';
                    $this->session->set_flashdata($status, $message);
                    redirect('admin/blog/category/index/' . $this->blog->id . $this->isFancybox);

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
        $this->data['page']->title = 'Blog &rsaquo; Categories &rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->blog_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog category
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.category_edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['category'] = $this->blog_category_model->get_by_id($this->uri->segment(6));

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

                $data                  = new \stdClass();
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_category_model->update($this->data['category']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Category saved successfully.');
                    redirect('admin/blog/category/index/' . $this->blog->id . $this->isFancybox);

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
        $this->data['page']->title = 'Blog &rsaquo; Catrgories &rsaquo; Edit &rsaquo; ' . $this->data['category']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['categories'] = $this->blog_category_model->get_all();

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog category
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin.blog:' . $this->blog->id . '.category_delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $id = $this->uri->segment(6);

        if ($this->blog_category_model->delete($id)) {

            $this->session->set_flashdata('success', '<strong>Success!</strong> Category was deleted successfully.');

        } else {

            $status   = 'error';
            $message  = '<strong>Sorry,</strong> there was a problem deleting the Category. ';
            $message .= $this->blog_category_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/category/index/' . $this->blog->id . $this->isFancybox);
    }
}
