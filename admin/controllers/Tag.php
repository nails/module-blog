<?php

/**
 * Manage Blog tags
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

class Tag extends BaseAdmin
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
        $blogs = $ci->blog_model->getAll();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                //  Categories enabled for this blog?
                if (!appSetting('tags_enabled', 'blog-' . $blog->id)) {
                    continue;
                }

                if (!userHasPermission('admin:blog:tag:' . $blog->id . ':manage')) {
                    continue;
                }

                //  Clear group naming
                $sGroupLabel = count($blogs) > 1 ? 'Blog: ' . $blog->label : $blog->label;

                //  Create the navGrouping
                $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
                $oNavGroup->setLabel($sGroupLabel);
                $oNavGroup->setIcon('fa-pencil-square-o');
                $oNavGroup->addAction('Manage Tags', 'index/' . $blog->id);

                $out[] = $oNavGroup;
            }
        }

        return $out;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions(): array
    {
        $permissions = parent::permissions();

        //  Fetch the blogs, each blog should have its own admin nav grouping
        $ci =& get_instance();
        $ci->load->model('blog/blog_model');
        $blogs = $ci->blog_model->getAll();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                $permissions[$blog->id . ':manage']  = $blog->label . ': Can manage tags';
                $permissions[$blog->id . ':create']  = $blog->label . ': Can create tags';
                $permissions[$blog->id . ':edit']    = $blog->label . ': Can edit tags';
                $permissions[$blog->id . ':delete']  = $blog->label . ': Can delete tags';
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
        $this->load->model('blog/blog_tag_model');

        // --------------------------------------------------------------------------

        //  Are we working with a valid blog?
        $oUri       = Factory::service('Uri');
        $blogId     = $oUri->segment(5);
        $this->blog = $this->blog_model->getById($blogId);

        if (empty($this->blog)) {

            show404();
        }

        $this->data['blog'] = $this->blog;

        // --------------------------------------------------------------------------

        //  Tags enabled?
        if (!appSetting('tags_enabled', 'blog-' . $this->blog->id)) {

            show404();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');

        $this->isModal = $oInput->get('isModal') ? '?isModal=1' : '';
        $this->data['isModal'] = $this->isModal;

        // --------------------------------------------------------------------------

        //  Customisations
        $this->data['postName'] = appSetting('postName', 'blog-' . $this->blog->id);
        if (empty($this->data['postName'])) {
            $this->data['postName'] = 'post';
        }
        $this->data['postNamePlural'] = appSetting('postNamePlural', 'blog-' . $this->blog->id);
        if (empty($this->data['postNamePlural'])) {
            $this->data['postNamePlural'] = 'posts';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse blog tags
     * @return void
     */
    public function index()
    {
        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Tags';

        // --------------------------------------------------------------------------

        $data                  = array();
        $data['include_count'] = true;
        $data['where']         = array();
        $data['where'][]       = array('column' => 'blog_id', 'value' => $this->blog->id);

        $this->data['tags'] = $this->blog_tag_model->getAll(null, null, $data);

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new blog tag
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:blog:tag:' . $this->blog->id . ':create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_rules('seo_title', '', 'max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'max_length[150]');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($oFormValidation->run()) {

                $aInsertData                    = array();
                $aInsertData['blog_id']         = $this->blog->id;
                $aInsertData['label']           = $oInput->post('label');
                $aInsertData['description']     = $oInput->post('description');
                $aInsertData['seo_title']       = $oInput->post('seo_title');
                $aInsertData['seo_description'] = $oInput->post('seo_description');
                $aInsertData['seo_keywords']    = $oInput->post('seo_keywords');

                if ($this->blog_tag_model->create($aInsertData)) {

                    $this->oUserFeedback->success('Tag created successfully.');

                    redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);

                } else {

                    $this->oUserFeedback->error('There was a problem creating the Tag. ' . $this->blog_tag_model->lastError());
                }

            } else {

                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Tags &rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->blog_tag_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog tag
     * @return void
     */
    public function edit()
    {
        if (!userHasPermission('admin:blog:tag:' . $this->blog->id . ':edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri = Factory::service('Uri');

        $this->data['tag'] = $this->blog_tag_model->getById($oUri->segment(6));

        if (empty($this->data['tag'])) {

            show404();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('description', '', '');
            $oFormValidation->set_rules('seo_title', '', 'max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'max_length[150]');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($oFormValidation->run()) {

                $aUpdateData                    = array();
                $aUpdateData['label']           = $oInput->post('label');
                $aUpdateData['description']     = $oInput->post('description');
                $aUpdateData['seo_title']       = $oInput->post('seo_title');
                $aUpdateData['seo_description'] = $oInput->post('seo_description');
                $aUpdateData['seo_keywords']    = $oInput->post('seo_keywords');

                if ($this->blog_tag_model->update($this->data['tag']->id, $aUpdateData)) {

                    $this->oUserFeedback->success('Tag saved successfully.');

                    redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);

                } else {

                    $this->oUserFeedback->error('There was a problem saving the Tag. ' . $this->blog_tag_model->lastError());
                }

            } else {

                $this->oUserFeedback->error(lang('fv_there_were_errors'));
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Catrgories &rsaquo; Edit &rsaquo; ' . $this->data['tag']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->blog_tag_model->getAll();

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog tag
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:blog:tag:' . $this->blog->id . ':delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oUri = Factory::service('Uri');

        $id = $oUri->segment(6);

        if ($this->blog_tag_model->delete($id)) {
            $this->oUserFeedback->success('Tag was deleted successfully.');

        } else {
            $this->oUserFeedback->error('There was a problem deleting the Tag. ' . $this->blog_tag_model->lastError());
        }

        redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);
    }
}
