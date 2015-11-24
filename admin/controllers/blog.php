<?php

/**
 * Manage Blogs
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

class Blog extends BaseAdmin
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

        $navGroup = Factory::factory('Nav', 'nailsapp/module-admin');
        $navGroup->setLabel('Settings');

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
        $this->data['blogs'] = $this->blog_model->getAll();

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

             Helper::addHeaderButton('admin/blog/blog/create', 'Create Blog');
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
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

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'xss_clean|required');
            $oFormValidation->set_rules('description', '', 'xss_clean');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aInsertData                = array();
                $aInsertData['label']       = $this->input->post('label');
                $aInsertData['description'] = $this->input->post('description');

                $iId = $this->blog_model->create($aInsertData);

                if ($iId) {

                    $sStatus   = 'success';
                    $sMessage  = 'Blog was created successfully, ';
                    $sMessage .= 'now please confirm blog settings.';
                    $this->session->set_flashdata($sStatus, $sMessage);
                    redirect('admin/blog/settings?blog_id=' . $iId);

                } else {

                    $this->data['error']  = 'Failed to create blog. ';
                    $this->data['error'] .= $this->blog_model->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
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

        $this->data['blog'] = $this->blog_model->getById($this->uri->segment(5));

        if (empty($this->data['blog'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage Blogs &rsaquo; Edit "' . $this->data['blog']->label . '"';

        // --------------------------------------------------------------------------

        //  Handle POST
        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'xss_clean|required');
            $oFormValidation->set_rules('description', '', 'xss_clean');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aUpdateData                = array();
                $aUpdateData['label']       = $this->input->post('label');
                $aUpdateData['description'] = $this->input->post('description');

                if ($this->blog_model->update($this->uri->segment(5), $aUpdateData)) {

                    $status  = 'success';
                    $message = 'Blog was updated successfully.';
                    $this->session->set_flashdata($status, $message);
                    redirect('admin/blog/blog/index');

                } else {

                    $this->data['error']  = 'Failed to create blog. ';
                    $this->data['error'] .= $this->blog_model->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
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

        $blog = $this->blog_model->getById($this->uri->segment(5));

        if (empty($blog)) {

            $this->session->set_flashdata('error', 'You specified an invalid Blog ID.');
            redirect('admin/blog/blog/index');
        }

        // --------------------------------------------------------------------------

        if ($this->blog_model->delete($blog->id)) {

            $this->session->set_flashdata('success', 'Blog was deleted successfully.');

        } else {

            $this->session->set_flashdata('error', 'Failed to delete blog. ' . $this->blog_model->lastError());
        }

        redirect('admin/blog/blog/index');
    }
}
