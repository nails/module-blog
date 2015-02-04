<?php

/**
 * Manage Blog tags
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

class Tag extends \AdminController
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
                //  @todo respect permissions for managing tags
                $navGroup = new \Nails\Admin\Nav($groupLabel);
                $navGroup->addMethod('Manage Tags', 'index/' . $blog->id);

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
     * Browse blog tags
     * @return void
     */
    public function index()
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
    public function create()
    {
        if (!userHasPermission('admin.blog:' . $this->blogId . '.tag_create')) {

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

                $data                  = new \stdClass();
                $data->blog_id         = $this->blogId;
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->create($data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag created successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['isFancybox']);

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
    public function edit()
    {
        if (!userHasPermission('admin.blog:' . $this->blogId . '.tag_edit')) {

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

                $data                  = new \stdClass();
                $data->label           = $this->input->post('label');
                $data->description     = $this->input->post('description');
                $data->seo_title       = $this->input->post('seo_title');
                $data->seo_description = $this->input->post('seo_description');
                $data->seo_keywords    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->update($this->data['tag']->id, $data)) {

                    $this->session->set_flashdata('success', '<strong>Success!</strong> Tag saved successfully.');
                    redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['isFancybox']);

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
    public function delete()
    {
        if (!userHasPermission('admin.blog:' . $this->blogId . '.tag_delete')) {

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

        redirect('admin/blog/' . $this->blogId . '/manage/tag' . $this->data['isFancybox']);
    }
}
