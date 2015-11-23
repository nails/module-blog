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
        $blogs = $ci->blog_model->get_all();

        $out = array();

        if (!empty($blogs)) {

            foreach ($blogs as $blog) {

                //  Categories enabled for this blog?
                if (!app_setting('tags_enabled', 'blog-' . $blog->id)) {

                    continue;
                }

                if (!userHasPermission('admin:blog:tag:' . $blog->id . ':manage')) {

                    continue;
                }

                //  Clear group naming
                $groupLabel = count($blogs) > 1 ? 'Blog: ' . $blog->label : $blog->label;

                //  Create the navGrouping
                $navGroup = Factory::factory('Nav', 'nailsapp/module-admin');
                $navGroup->setLabel($sGroupLabel);
                $navGroup->setIcon('fa-pencil-square-o');
                $navGroup->addAction('Manage Tags', 'index/' . $blog->id);

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
        $blogId = $this->uri->segment(5);
        $this->blog = $this->blog_model->get_by_id($blogId);

        if (empty($this->blog)) {

            show_404();
        }

        $this->data['blog'] = $this->blog;

        // --------------------------------------------------------------------------

        //  Tags enabled?
        if (!app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

            show_404();
        }

        // --------------------------------------------------------------------------

        $this->isModal = $this->input->get('isModal') ? '?isModal=1' : '';
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

        $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $data);

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

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('label', '', 'xss_clean|required');
            $oFormValidation->set_rules('description', '', 'xss_clean');
            $oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($oFormValidation->run()) {

                $aInsertData                    = array();
                $aInsertData['blog_id']         = $this->blog->id;
                $aInsertData['label']           = $this->input->post('label');
                $aInsertData['description']     = $this->input->post('description');
                $aInsertData['seo_title']       = $this->input->post('seo_title');
                $aInsertData['seo_description'] = $this->input->post('seo_description');
                $aInsertData['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->create($aInsertData)) {

                    $status  = 'success';
                    $message = 'Tag created successfully.';
                    $this->session->set_flashdata($status, $message);
                    redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem creating the Tag. ';
                    $this->data['error'] .= $this->blog_tag_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Tags &rsaquo; Create';

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->blog_tag_model->get_all();

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

        $this->data['tag'] = $this->blog_tag_model->get_by_id($this->uri->segment(6));

        if (empty($this->data['tag'])) {

            show_404();
        }

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');

            $oFormValidation->set_rules('label', '', 'xss_clean|required');
            $oFormValidation->set_rules('description', '', 'xss_clean');
            $oFormValidation->set_rules('seo_title', '', 'xss_clean|max_length[150]');
            $oFormValidation->set_rules('seo_description', '', 'xss_clean|max_length[300]');
            $oFormValidation->set_rules('seo_keywords', '', 'xss_clean|max_length[150]');

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('max_length', lang('fv_max_length'));

            if ($oFormValidation->run()) {

                $aUpdateData                    = array();
                $aUpdateData['label']           = $this->input->post('label');
                $aUpdateData['description']     = $this->input->post('description');
                $aUpdateData['seo_title']       = $this->input->post('seo_title');
                $aUpdateData['seo_description'] = $this->input->post('seo_description');
                $aUpdateData['seo_keywords']    = $this->input->post('seo_keywords');

                if ($this->blog_tag_model->update($this->data['tag']->id, $aUpdateData)) {

                    $this->session->set_flashdata('success', 'Tag saved successfully.');
                    redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);

                } else {

                    $this->data['error']  = 'There was a problem saving the Tag. ';
                    $this->data['error'] .= $this->blog_tag_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Page data
        $this->data['page']->title = 'Blog &rsaquo; Catrgories &rsaquo; Edit &rsaquo; ' . $this->data['tag']->label;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['tags'] = $this->blog_tag_model->get_all();

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

        $id = $this->uri->segment(6);

        if ($this->blog_tag_model->delete($id)) {

            $this->session->set_flashdata('success', 'Tag was deleted successfully.');

        } else {

            $status   = 'error';
            $message  = 'There was a problem deleting the Tag. ';
            $message .= $this->blog_tag_model->last_error();
            $this->session->set_flashdata($status, $message);
        }

        redirect('admin/blog/tag/index/' . $this->blog->id . $this->isModal);
    }
}
