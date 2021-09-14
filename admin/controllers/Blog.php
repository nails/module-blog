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

        $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
        $oNavGroup->setLabel('Settings');

        if (!empty($blogs)) {

            if (userHasPermission('admin:blog:blog:manage')) {
                $oNavGroup->addAction('Blog: Manage');
            }

        } else {

            if (userHasPermission('admin:blog:blog:create')) {
                $oNavGroup->addAction('Blog: Create New', 'create');
            }
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions(): array
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

                $oSession = Factory::service('Session');
                $oSession->warning('<strong>You don\'t have a blog!</strong> Create a new blog in order to configure blog settings.');

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
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aInsertData                = array();
                $aInsertData['label']       = $oInput->post('label');
                $aInsertData['description'] = $oInput->post('description');

                $iId = $this->blog_model->create($aInsertData);

                if ($iId) {

                    $oSession = Factory::service('Session');
                    $oSession->success('Blog was created successfully, now please confirm blog settings.');

                    redirect('admin/blog/settings?blog_id=' . $iId);

                } else {
                    $this->oUserFeedback->error('Failed to create blog. ' . $this->blog_model->lastError());
                }

            } else {
                $this->oUserFeedback->error(lang('fv_there_were_errors'));
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

        $oUri = Factory::service('Uri');

        $this->data['blog'] = $this->blog_model->getById($oUri->segment(5));

        if (empty($this->data['blog'])) {
            show404();
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Manage Blogs &rsaquo; Edit "' . $this->data['blog']->label . '"';

        // --------------------------------------------------------------------------

        //  Handle POST
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_message('required', lang('fv_required'));

            if ($oFormValidation->run()) {

                $aUpdateData                = array();
                $aUpdateData['label']       = $oInput->post('label');
                $aUpdateData['description'] = $oInput->post('description');

                if ($this->blog_model->update($oUri->segment(5), $aUpdateData)) {

                    $oSession = Factory::service('Session');
                    $oSession->success('Blog was updated successfully.');

                    redirect('admin/blog/blog/index');

                } else {

                    $this->oUserFeedback->error('Failed to create blog. ' . $this->blog_model->lastError());
                }

            } else {

                $this->oUserFeedback->error(lang('fv_there_were_errors'));
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

        $oSession = Factory::service('Session');
        $oUri     = Factory::service('Uri');
        $blog     = $this->blog_model->getById($oUri->segment(5));

        if (empty($blog)) {
            $oSession->error('You specified an invalid Blog ID.');
            redirect('admin/blog/blog/index');
        }

        // --------------------------------------------------------------------------

        if ($this->blog_model->delete($blog->id)) {

            $oSession->success('Blog was deleted successfully.');

        } else {

            $oSession->error('Failed to delete blog. ' . $this->blog_model->lastError());
        }

        redirect('admin/blog/blog/index');
    }
}
