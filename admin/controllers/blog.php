<?php

/**
 * Manage Blogs
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Blog extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->get_all();

        $navGroup = new \Nails\Admin\Nav('Settings');

        if (!empty($blogs)) {

            if (userHasPermission('admin:blog:blog:manage')) {

                $navGroup->addAction('Blog: Manage');
            }

        } else {

            if (userHasPermission('admin:blog:blog:create')) {

                $navGroup->addAction('Blog: Create New', 'create');
            }
        }

        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage blogs';
        $permissions['create'] = 'Can create blogs';
        $permissions['edit']   = 'Can edit blogs';
        $permissions['delete'] = 'Can delete blogs';

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

        // --------------------------------------------------------------------------

        //  Overall permissions?
        if (!userHasPermission('admin:blog:blog:manage')) {

            unauthorised();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse existing blogs
     * @return void
     */
    public function index()
    {
        $this->data['page']->title = 'Manage Blogs';

        // --------------------------------------------------------------------------

        //  Get blogs
        $this->data['blogs'] = $this->blog_model->get_all();

        if (empty($this->data['blogs'])) {

            if (!userHasPermission('admin:blog:blog:create')) {

                $status   = 'message';
                $message  = '<strong>You don\'t have a blog!</strong> Create a new blog ';
                $message .= 'in order to configure blog settings.';

                $this->session->set_flashdata($status, $message);

                redirect('admin/blog/blog/create');

            }
        }

        // --------------------------------------------------------------------------

        //  Add a header button
        if (userHasPermission('admin:blog:blog:create')) {

             \Nails\Admin\Helper::addHeaderButton('admin/blog/blog/create', 'Create Blog');
        }

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new blog
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:blog:blog:create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage Blogs &rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Handle POST
        if ($this->input->post()) {

            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data              = new \stdClass();
                $data->label       = $this->input->post('label');
                $data->description = $this->input->post('description');

                $id = $this->blog_model->create($data);

                if ($id) {

                    $status   = 'success';
                    $message  = 'Blog was created successfully, ';
                    $message .= 'now please confirm blog settings.';
                    $this->session->set_flashdata($status, $message);
                    redirect('admin/blog/settings?blog_id=' . $id);

                } else {

                    $this->data['error']  = 'Failed to create blog. ';
                    $this->data['error'] .= $this->blog_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit an existing blog
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:blog:blog:edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $this->data['blog'] = $this->blog_model->get_by_id($this->uri->segment(5));

        if (empty($this->data['blog'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage Blogs &rsaquo; Edit "' . $this->data['blog']->label . '"';

        // --------------------------------------------------------------------------

        //  Handle POST
        if ($this->input->post()) {

            $this->load->library('form_validation');
            $this->form_validation->set_rules('label', '', 'xss_clean|required');
            $this->form_validation->set_rules('description', '', 'xss_clean');
            $this->form_validation->set_message('required', lang('fv_required'));

            if ($this->form_validation->run()) {

                $data              = new \stdClass();
                $data->label       = $this->input->post('label');
                $data->description = $this->input->post('description');

                if ($this->blog_model->update($this->uri->Segment(5), $data)) {

                    $status  = 'success';
                    $message = 'Blog was updated successfully.';
                    $this->session->set_flashdata($status, $message);
                    redirect('admin/blog/blog/index');

                } else {

                    $this->data['error']  = 'Failed to create blog. ';
                    $this->data['error'] .= $this->blog_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete an existing blog
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:blog:blog:delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $blog = $this->blog_model->get_by_id($this->uri->segment(5));

        if (empty($blog)) {

            $this->session->set_flashdata('error', 'You specified an invalid Blog ID.');
            redirect('admin/blog/blog/index');
        }

        // --------------------------------------------------------------------------

        if ($this->blog_model->delete($blog->id)) {

            $this->session->set_flashdata('success', 'Blog was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'Failed to delete blog. ' . $this->blog_model->last_error());
        }

        redirect('admin/blog/blog/index');
    }
}
