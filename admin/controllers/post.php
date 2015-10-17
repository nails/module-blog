<?php

/**
 * Manage Blog posts
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Blog;

use Nails\Admin\Helper;
use Nails\Blog\Controller\BaseAdmin;

class Post extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return array
     */
    public static function announce()
    {
        //  Fetch the blogs, each blog should have its own admin nav grouping
        $oCi =& get_instance();
        $oCi->load->model('blog/blog_model');
        $oCi->load->model('blog/blog_post_model');
        $aBlogs = $oCi->blog_model->get_all();

        $aOut = array();

        if (!empty($aBlogs)) {

            foreach ($aBlogs as $oBlog) {

                if (!userHasPermission('admin:blog:post:' . $oBlog->id . ':manage')) {

                    continue;
                }

                //  Clear group naming
                $sGroupLabel = count($aBlogs) > 1 ? 'Blog: ' . $oBlog->label : $oBlog->label;

                //  Any draft posts?
                $iNumDrafts = $oCi->blog_post_model->countDrafts($oBlog->id);
                $aAlerts    = array(\Nails\Admin\Nav::alertObject($iNumDrafts, '', 'Drafts'));

                //  Post name
                $postNamePlural = app_setting('postNamePlural', 'blog-' . $oBlog->id);
                if (empty($postNamePlural)) {
                    $postNamePlural = 'posts';
                }

                //  Create the navGrouping
                $oNavGroup = new \Nails\Admin\Nav($sGroupLabel, 'fa-pencil-square-o');
                $oNavGroup->addAction('Manage ' . ucFirst($postNamePlural), 'index/' . $oBlog->id, $aAlerts, 0);

                $aOut[] = $oNavGroup;
            }
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of extra permissions for this controller
     * @return array
     */
    public static function permissions()
    {
        $aPermissions = parent::permissions();

        //  Fetch the blogs, each blog should have its own admin nav grouping
        $oCi =& get_instance();
        $oCi->load->model('blog/blog_model');
        $aBlogs = $oCi->blog_model->get_all();

        $aOut = array();

        if (!empty($aBlogs)) {

            foreach ($aBlogs as $oBlog) {

                $aPermissions[$oBlog->id . ':manage']  = $oBlog->label . ': Can manage posts';
                $aPermissions[$oBlog->id . ':create']  = $oBlog->label . ': Can create posts';
                $aPermissions[$oBlog->id . ':edit']    = $oBlog->label . ': Can edit posts';
                $aPermissions[$oBlog->id . ':delete']  = $oBlog->label . ': Can delete posts';
                $aPermissions[$oBlog->id . ':restore'] = $oBlog->label . ': Can restore posts';

            }
        }

        return $aPermissions;
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
        $iBlogId = (int) $this->uri->segment(5);
        $this->blog = $this->blog_model->get_by_id($iBlogId);

        if (empty($this->blog)) {

            show_404();
        }

        $this->data['blog'] = $this->blog;

        // --------------------------------------------------------------------------

        //  Blog post types
        $this->data['postTypes'] = $this->blog_post_model->getTypes();

        // --------------------------------------------------------------------------

        //  Customisations
        $this->data['postName'] = app_setting('postName', 'blog-' . $this->blog->id);
        if (empty($this->data['postName'])) {
            $this->data['postName'] = 'post';
        }
        $this->data['postNamePlural'] = app_setting('postNamePlural', 'blog-' . $this->blog->id);
        if (empty($this->data['postNamePlural'])) {
            $this->data['postNamePlural'] = 'posts';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Browse posts
     * @return void
     */
    public function index()
    {
        //  Set method info
        $this->data['page']->title = 'Manage ' . ucfirst($this->data['postNamePlural']);

        // --------------------------------------------------------------------------

        $sTablePrefix = $this->blog_post_model->getTablePrefix();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $iPage      = (int) $this->input->get('page') ? (int) $this->input->get('page') : 0;
        $iPerPage   = (int) $this->input->get('perPage') ? (int) $this->input->get('perPage') : 50;
        $sSortOn    = $this->input->get('sortOn') ? $this->input->get('sortOn') : $sTablePrefix . '.published';
        $sSortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $sKeywords  = $this->input->get('keywords') ? $this->input->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = array(
            $sTablePrefix . '.published' => 'Published Date',
            $sTablePrefix . '.modified'  => 'Modified Date',
            $sTablePrefix . '.title'     => 'Title'
        );

        // --------------------------------------------------------------------------

        //  Checkbox filters
        $aCbFilters   = array();
        $aCbFilters[] = Helper::searchFilterObject(
            $sTablePrefix . '.is_published',
            'State',
            array(
                array('Published', true, true),
                array('Unpublished', false, true)
            )
        );

        //  Generate options
        $filterOpts = array();
        foreach ($this->data['postTypes'] as $sValue => $sLabel) {
            $filterOpts[] = array($sLabel, $sValue, true);
        }

        $aCbFilters[] = Helper::searchFilterObject(
            $sTablePrefix . '.type',
            'Type',
            $filterOpts
        );

        // --------------------------------------------------------------------------

        //  Define the $aData variable for the queries
        $aData = array(
            'where' => array(
                array(
                    'column' => 'blog_id',
                    'value' => $this->blog->id
                )
            ),
            'sort' => array(
                array($sSortOn, $sSortOrder)
            ),
            'keywords' => $sKeywords,
            'cbFilters' => $aCbFilters
        );

        //  Get the items for the page
        $iTotalRows          = $this->blog_post_model->count_all($aData);
        $this->data['posts'] = $this->blog_post_model->get_all($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $aSortColumns, $sSortOn, $sSortOrder, $iPerPage, $sKeywords, $aCbFilters);
        $this->data['pagination'] = Helper::paginationObject($iPage, $iPerPage, $iTotalRows);

        //  Add a header button
        if (userHasPermission('admin:blog:post:' . $this->blog->id . ':create')) {

             Helper::addHeaderButton(
                'admin/blog/post/create/' . $this->blog->id,
                'New ' . ucfirst($this->data['postName'])
            );
        }

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a blog post
     * @return void
     **/
    public function create()
    {
        if (!userHasPermission('admin:blog:post:' . $this->blog->id . ':create')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Create New ' . ucfirst($this->data['postName']);

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  Are we running in preview mode?
            $bIsPreview = (bool) $this->input->post('isPreview');

            //  Only validate non-previews
            if (empty($bIsPreview)) {

                $this->load->library('form_validation');

                $this->form_validation->set_rules('is_published', '', 'xss_clean');
                $this->form_validation->set_rules('published', '', 'xss_clean');
                $this->form_validation->set_rules('title', '', 'xss_clean|required');
                $this->form_validation->set_rules('type', '', 'xss_clean|required');
                $this->form_validation->set_rules('excerpt', '', 'xss_clean');
                $this->form_validation->set_rules('image_id', '', 'xss_clean');
                $this->form_validation->set_rules('video_url', '', 'xss_clean');
                $this->form_validation->set_rules('audio_url', '', 'xss_clean');
                $this->form_validation->set_rules('seo_description', '', 'xss_clean');
                $this->form_validation->set_rules('seo_keywords', '', 'xss_clean');

                if ($this->input->post('slug')) {

                    $sTable = $this->blog_post_model->getTableName();
                    $this->form_validation->set_rules('slug', '', 'xss_clean|alpha_dash|is_unique[' . $sTable . '.slug]');
                }

                if ($this->input->post('type') === 'PHOTO') {

                    $this->form_validation->set_rules('image_id', '', 'xss_clean|required');

                } elseif ($this->input->post('type') === 'VIDEO') {

                    $this->form_validation->set_rules('video_url', '', 'xss_clean|required');

                } elseif ($this->input->post('type') === 'AUDIO') {

                    $this->form_validation->set_rules('audio_url', '', 'xss_clean|required');
                }

                $this->form_validation->set_message('required', lang('fv_required'));
                $this->form_validation->set_message('alpha_dash', lang('fv_alpha_dash'));
                $this->form_validation->set_message('is_unique', 'A post using this slug already exists.');

            }

            if (!empty($bIsPreview) || $this->form_validation->run($this)) {

                //  Prepare data
                $aData                     = array();
                $aData['blog_id']          = $this->blog->id;
                $aData['title']            = $this->input->post('title');
                $aData['type']             = $this->input->post('type');
                $aData['slug']             = $this->input->post('slug');
                $aData['excerpt']          = $this->input->post('excerpt');
                $aData['image_id']         = (int) $this->input->post('image_id');
                $aData['image_id']         = $aData['image_id'] ? $aData['image_id'] : null;
                $aData['video_url']        = trim($this->input->post('video_url'));
                $aData['video_url']        = $aData['video_url'] ? $aData['video_url'] : null;
                $aData['audio_url']        = trim($this->input->post('audio_url'));
                $aData['audio_url']        = $aData['audio_url'] ? $aData['audio_url'] : null;
                $aData['body']             = $this->input->post('body');
                $aData['seo_description']  = $this->input->post('seo_description');
                $aData['seo_keywords']     = $this->input->post('seo_keywords');
                $aData['is_published']     = (bool) $this->input->post('is_published');
                $aData['published']        = $this->input->post('published');
                $aData['associations']     = $this->input->post('associations');
                $aData['gallery']          = $this->input->post('gallery');
                $aData['comments_enabled'] = $this->input->post('comments_enabled');
                $aData['comments_expire']  = $this->input->post('comments_expire');

                if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $aData['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

                    $aData['tags'] = $this->input->post('tags');
                }

                //  Are we running in preview mode?
                if (!empty($bIsPreview)) {

                    //  Previewing, set the preview tables and create a new one
                    $aData['blog_id'] = $this->blog->id;
                    $this->blog_post_model->usePreviewTables(true);
                    $iPostId = $this->blog_post_model->create($aData);

                } else {

                    //  Normal behaviour
                    $iPostId = $this->blog_post_model->create($aData);
                }

                if (!empty($iPostId)) {

                    //  Behave slightly differently depending on whether we're previewing or not
                    if (empty($bIsPreview)) {

                        //  Update admin changelog
                        $this->admin_changelog_model->add(
                            'created',
                            'a',
                            'blog post',
                            $iPostId,
                            $aData['title'],
                            'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId
                        );

                        $this->session->set_flashdata('success', ucfirst($this->data['postName']) . ' was created.');
                        $sRedirectUrl = 'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId;

                    } else {

                        $sRedirectUrl = $this->blog->url . '/preview/' . $iPostId;
                    }

                    redirect($sRedirectUrl);

                } else {

                    $this->data['error']  = 'An error occurred and the post could not be created. ';
                    $this->data['error'] .= $this->blog_post_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

            $aData            = array();
            $aData['where']   = array();
            $aData['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $aData);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

            $aData            = array();
            $aData['where']   = array();
            $aData['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $aData);
        }

        // --------------------------------------------------------------------------

        //  Load other data
        $this->data['associations']      = $this->blog_model->get_associations();
        $this->data['cdnUrlScaleScheme'] = $this->cdn->url_scale_scheme();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('moment/moment.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.createEdit.min.js', 'NAILS');

        // --------------------------------------------------------------------------

        $sInlineJs  = 'var _EDIT;';
        $sInlineJs .= '$(function()';
        $sInlineJs .= '{';
        $sInlineJs .= '    _EDIT = new NAILS_Admin_Blog_Create_Edit(\'CREATE\');';
        $sInlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $this->cdn->generate_api_upload_token() . '");';
        $sInlineJs .= '});';

        $this->asset->inline($sInlineJs, 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Edit a blog post
     * @return void
     **/
    public function edit()
    {
        if (!userHasPermission('admin:blog:post:' . $this->blog->id . ':edit')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $iPostId = (int) $this->uri->segment(6);

        $this->data['post'] = $this->blog_post_model->get_by_id($iPostId);

        if (!$this->data['post']) {

            show_404();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit ' . $this->data['postName'] . ' &rsaquo; ' . $this->data['post']->title;

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            //  Are we running in preview mode?
            $bIsPreview = (bool) $this->input->post('isPreview');

            //  Only validate non-previews
            if (empty($bIsPreview)) {

                $this->load->library('form_validation');

                $this->form_validation->set_rules('is_published', '', 'xss_clean');
                $this->form_validation->set_rules('published', '', 'xss_clean');
                $this->form_validation->set_rules('title', '', 'xss_clean|required');
                $this->form_validation->set_rules('type', '', 'xss_clean|required');
                $this->form_validation->set_rules('excerpt', '', 'xss_clean');
                $this->form_validation->set_rules('image_id', '', 'xss_clean');
                $this->form_validation->set_rules('video_url', '', 'xss_clean');
                $this->form_validation->set_rules('audio_url', '', 'xss_clean');
                $this->form_validation->set_rules('seo_keywords', '', 'xss_clean');

                if ($this->input->post('slug')) {

                    $sTable = $this->blog_post_model->getTableName();
                    $this->form_validation->set_rules('slug', '', 'xss_clean|alpha_dash|unique_if_diff[' . $sTable . '.slug.' . $this->data['post']->slug . ']');
                }

                if ($this->input->post('type') === 'PHOTO') {

                    $this->form_validation->set_rules('image_id', '', 'xss_clean|required');

                } elseif ($this->input->post('type') === 'VIDEO') {

                    $this->form_validation->set_rules('video_url', '', 'xss_clean|required|callback__callbackValidVideoUrl');

                } elseif ($this->input->post('type') === 'AUDIO') {

                    $this->form_validation->set_rules('audio_url', '', 'xss_clean|required|callback__callbackValidAudioUrl');
                }

                $this->form_validation->set_message('required', lang('fv_required'));
                $this->form_validation->set_message('alpha_dash', lang('fv_alpha_dash'));
                $this->form_validation->set_message('unique_if_diff', 'A post using this slug already exists.');
            }

            if (!empty($bIsPreview) || $this->form_validation->run($this)) {

                //  Prepare data
                $aData                     = array();
                $aData['title']            = $this->input->post('title');
                $aData['type']             = $this->input->post('type');
                $aData['slug']             = $this->input->post('slug');
                $aData['excerpt']          = $this->input->post('excerpt');
                $aData['image_id']         = (int) $this->input->post('image_id');
                $aData['image_id']         = $aData['image_id'] ? $aData['image_id'] : null;
                $aData['video_url']        = trim($this->input->post('video_url'));
                $aData['video_url']        = $aData['video_url'] ? $aData['video_url'] : null;
                $aData['audio_url']        = trim($this->input->post('audio_url'));
                $aData['audio_url']        = $aData['audio_url'] ? $aData['audio_url'] : null;
                $aData['body']             = $this->input->post('body');
                $aData['seo_description']  = $this->input->post('seo_description');
                $aData['seo_keywords']     = $this->input->post('seo_keywords');
                $aData['is_published']     = (bool) $this->input->post('is_published');
                $aData['published']        = $this->input->post('published');
                $aData['associations']     = $this->input->post('associations');
                $aData['gallery']          = $this->input->post('gallery');
                $aData['comments_enabled'] = $this->input->post('comments_enabled');
                $aData['comments_expire']  = $this->input->post('comments_expire');

                if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $aData['categories'] = $this->input->post('categories');
                }

                if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

                    $aData['tags'] = $this->input->post('tags');
                }

                //  Are we running in preview mode?
                if (!empty($bIsPreview)) {

                    //  Previewing, set the preview tables and create a new one
                    $aData['blog_id'] = $this->blog->id;
                    $this->blog_post_model->usePreviewTables(true);
                    $mResult = $this->blog_post_model->create($aData);

                } else {

                    //  Normal behaviour
                    $mResult = $this->blog_post_model->update($iPostId, $aData);
                }

                if (!empty($mResult)) {

                    //  Behave slightly differently depending on whether we're previewing or not
                    if (empty($bIsPreview)) {

                        //  Update admin change log
                        foreach ($aData as $field => $value) {

                            if (isset($this->data['post']->$field)) {

                                switch ($field) {

                                    case 'associations':

                                        //  @TODO: changelog associations
                                        break;

                                    case 'categories':

                                        $aOldCategories = array();
                                        $aNewCategories = array();

                                        foreach ($this->data['post']->$field as $v) {

                                            $aOldCategories[] = $v->label;
                                        }

                                        if (is_array($value)) {

                                            foreach ($value as $v) {

                                                $temp = $this->blog_category_model->get_by_id($v);

                                                if ($temp) {

                                                    $aNewCategories[] = $temp->label;
                                                }
                                            }
                                        }

                                        asort($aOldCategories);
                                        asort($aNewCategories);

                                        $aOldCategories = implode(',', $aOldCategories);
                                        $aNewCategories = implode(',', $aNewCategories);

                                        $this->admin_changelog_model->add(
                                            'updated',
                                            'a',
                                            'blog post',
                                            $iPostId,
                                            $aData['title'],
                                            'admin/blog/post/create/' . $this->blog->id . '/' . $iPostId,
                                            $field,
                                            $aOldCategories,
                                            $aNewCategories,
                                            false
                                        );
                                        break;

                                    case 'tags':

                                        $aOldTags = array();
                                        $aNewTags = array();

                                        foreach ($this->data['post']->$field as $v) {

                                            $aOldTags[] = $v->label;
                                        }

                                        if (is_array($value)) {

                                            foreach ($value as $v) {

                                                $temp = $this->blog_tag_model->get_by_id($v);

                                                if ($temp) {

                                                    $aNewTags[] = $temp->label;
                                                }
                                            }
                                        }

                                        asort($aOldTags);
                                        asort($aNewTags);

                                        $aOldTags = implode(',', $aOldTags);
                                        $aNewTags = implode(',', $aNewTags);

                                        $this->admin_changelog_model->add(
                                            'updated',
                                            'a',
                                            'blog post',
                                            $iPostId,
                                            $aData['title'],
                                            'admin/blog/post/create/' . $this->blog->id . '/' . $iPostId,
                                            $field,
                                            $aOldTags,
                                            $aNewTags,
                                            false
                                        );
                                        break;

                                    default :

                                        $this->admin_changelog_model->add(
                                            'updated',
                                            'a',
                                            'blog post',
                                            $iPostId,
                                            $aData['title'],
                                            'admin/blog/post/create/' . $this->blog->id . '/' . $iPostId,
                                            $field,
                                            $this->data['post']->$field,
                                            $value,
                                            false
                                        );
                                        break;
                                }
                            }
                        }

                        $this->session->set_flashdata('success', ucfirst($this->data['postName']) . ' was updated.');
                        $sRedirectUrl = 'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId;

                    } else {

                        $sRedirectUrl = $this->blog->url . '/preview/' . $mResult;
                    }

                    redirect($sRedirectUrl);

                } else {

                    $this->data['error']  = 'An error occurred and the post could not be updated. ';
                    $this->data['error'] .= $this->blog_post_model->last_error();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        $aData = array(
            'where' => array(
                array('column' => 'blog_id', 'value' => $this->blog->id)
            )
        );

        if (app_setting('categories_enabled', 'blog-' . $this->blog->id)) {

            $this->data['categories'] = $this->blog_category_model->get_all(null, null, $aData);
        }

        if (app_setting('tags_enabled', 'blog-' . $this->blog->id)) {

            $this->data['tags'] = $this->blog_tag_model->get_all(null, null, $aData);
        }

        // --------------------------------------------------------------------------

        //  Load other data
        $this->data['associations']      = $this->blog_model->get_associations($this->data['post']->id);
        $this->data['cdnUrlScaleScheme'] = $this->cdn->url_scale_scheme();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('uploadify');
        $this->asset->load('mustache.js/mustache.js', 'NAILS-BOWER');
        $this->asset->load('moment/moment.js', 'NAILS-BOWER');
        $this->asset->load('nails.admin.blog.createEdit.min.js', 'NAILS');

        if ($this->data['post']->is_published) {

            $oNow = new \DateTime();
            $oPublished = new \DateTime($this->data['post']->published);

            if ($oPublished > $oNow) {
                $sInitalPublishState = 'SCHEDULED';
            } else {
                $sInitalPublishState = 'PUBLISHED';
            }

        } else {
            $sInitalPublishState = 'DRAFT';
        }

        $sInlineJs  = 'var _EDIT;';
        $sInlineJs .= '$(function()';
        $sInlineJs .= '{';
        $sInlineJs .= '    _EDIT = new NAILS_Admin_Blog_Create_Edit(\'EDIT\', \'' . $sInitalPublishState . '\');';
        $sInlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $this->cdn->generate_api_upload_token() . '");';
        $sInlineJs .= '});';

        $this->asset->inline($sInlineJs, 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Delete a blog post
     * @return void
     */
    public function delete()
    {
        if (!userHasPermission('admin:blog:post:' . $this->blog->id . ':delete')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $iPostId = (int) $this->uri->segment(6);
        $oPost   = $this->blog_post_model->get_by_id($iPostId);

        if (!$oPost || $oPost->blog->id != $this->blog->id) {

            $this->session->set_flashdata('error', 'I could\'t find a post by that ID.');
            redirect('admin/blog/post/index/' . $this->blog->id);
        }

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->delete($iPostId)) {

            $sStatus  = 'success';
            $sMessage = ucfirst($this->data['postName']) . ' was deleted successfully. ';
            if (userHasPermission('admin:blog:post:' . $this->blog->id . ':restore')) {
                $sMessage .= anchor('admin/blog/post/restore/' . $this->blog->id . '/' . $iPostId, 'Undo?');
            }

            //  Update admin changelog
            $this->admin_changelog_model->add('deleted', 'a', 'blog post', $iPostId, $oPost->title);

        } else {

            $sStatus  = 'error';
            $sMessage = 'I failed to delete that post. ' . $this->blog_post_model->last_error();
        }

        $this->session->set_flashdata($sStatus, $sMessage);

        redirect('admin/blog/post/index/' . $this->blog->id);
    }

    // --------------------------------------------------------------------------

    /**
     * Restore a blog post
     * @return void
     */
    public function restore()
    {
        if (!userHasPermission('admin:blog:post:' . $this->blog->id . ':restore')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Fetch and check post
        $iPostId = (int) $this->uri->segment(6);

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->restore($iPostId)) {

            $oPost = $this->blog_post_model->get_by_id($iPostId);

            $this->session->set_flashdata('success', ucfirst($this->data['postName']) . ' was restored successfully.');

            //  Update admin changelog
            $this->admin_changelog_model->add(
                'restored',
                'a',
                'blog post',
                $iPostId,
                $oPost->title,
                'admin/blog/post/create/' . $this->blog->id . '/' . $iPostId
            );

        } else {

            $sStatus   = 'error';
            $sMessage  = 'I failed to restore that ' . $this->data['postName'] . '. ';
            $sMessage .= $this->blog_post_model->last_error();
            $this->session->set_flashdata($sStatus, $sMessage);
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
        dumpanddie($_POST);
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation callback, checks that a Spotify ID can be extracted from the string
     * @param  string  $sUrl The URL to check
     * @return boolean
     */
    public function _callbackValidAudioUrl($sUrl)
    {
        $sId = $this->blog_post_model->extractSpotifyId($sUrl);

        if (!empty($sId)) {

            return true;

        } else {

            $this->form_validation->set_message('_callbackValidAudioUrl', 'Not a valid Spotify Track URL.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation callback, checks that a YouTube or Vimeo ID can be extracted from the string
     * @param  string  $sUrl The URL to check
     * @return boolean
     */
    public function _callbackValidVideoUrl($sUrl)
    {
        $sId = $this->blog_post_model->extractYoutubeId($sUrl);

        if (!empty($sId)) {

            return true;

        } else {

            $sId = $this->blog_post_model->extractVimeoId($sUrl);

            if (!empty($sId)) {

                return true;

            } else {

                $this->form_validation->set_message('_callbackValidVideoUrl', 'Not a valid YouTube or Vimeo URL.');
                return false;
            }
        }
    }
}
