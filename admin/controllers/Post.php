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

use Nails\Auth;
use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Blog\Controller\BaseAdmin;
use Nails\Cdn;

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
        $aBlogs = $oCi->blog_model->getAll();

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
                $oAlert = Factory::factory('NavAlert', 'nails/module-admin');
                $oAlert->setValue($iNumDrafts);
                $oAlert->setLabel('Drafts');

                //  Post name
                $postNamePlural = appSetting('postNamePlural', 'blog-' . $oBlog->id);
                if (empty($postNamePlural)) {
                    $postNamePlural = 'posts';
                }

                //  Create the navGrouping
                $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
                $oNavGroup->setLabel($sGroupLabel);
                $oNavGroup->setIcon('fa-pencil-square-o');
                $oNavGroup->addAction(
                    'Manage ' . ucFirst($postNamePlural),
                    'index/' . $oBlog->id,
                    array($oAlert),
                    0
                );

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
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        //  Fetch the blogs, each blog should have its own admin nav grouping
        $oCi =& get_instance();
        $oCi->load->model('blog/blog_model');
        $aBlogs = $oCi->blog_model->getAll();

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

        $this->oChangeLogModel = Factory::model('ChangeLog', 'nails/module-admin');

        // --------------------------------------------------------------------------

        //  Are we working with a valid blog?
        $oUri       = Factory::service('Uri');
        $iBlogId    = (int) $oUri->segment(5);
        $this->blog = $this->blog_model->getById($iBlogId);

        if (empty($this->blog)) {

            show404();
        }

        $this->data['blog'] = $this->blog;

        // --------------------------------------------------------------------------

        //  Blog post types
        $this->data['postTypes'] = $this->blog_post_model->getTypes();

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
     * Browse posts
     * @return void
     */
    public function index()
    {
        $oInput = Factory::service('Input');

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Manage ' . ucfirst($this->data['postNamePlural']);

        // --------------------------------------------------------------------------

        $sTableAlias = $this->blog_post_model->getTableAlias();

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $iPage      = (int) $oInput->get('page') ? (int) $oInput->get('page') : 0;
        $iPerPage   = (int) $oInput->get('perPage') ? (int) $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sTableAlias . '.published';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = array(
            $sTableAlias . '.published' => 'Published Date',
            $sTableAlias . '.modified'  => 'Modified Date',
            $sTableAlias . '.title'     => 'Title'
        );

        // --------------------------------------------------------------------------

        //  Checkbox filters
        $aCbFilters   = array();
        $aCbFilters[] = Helper::searchFilterObject(
            $sTableAlias . '.is_published',
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
            $sTableAlias . '.type',
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
        $iTotalRows          = $this->blog_post_model->countAll($aData);
        $this->data['posts'] = $this->blog_post_model->getAll($iPage, $iPerPage, $aData);

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
        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Are we running in preview mode?
            $bIsPreview = (bool) $oInput->post('isPreview');

            //  Only validate non-previews
            if (empty($bIsPreview)) {

                $oFormValidation = Factory::service('FormValidation');

                $oFormValidation->set_rules('is_published', '', '');
                $oFormValidation->set_rules('published', '', '');
                $oFormValidation->set_rules('title', '', 'required');
                $oFormValidation->set_rules('type', '', 'required');
                $oFormValidation->set_rules('body', '', 'required');
                $oFormValidation->set_rules('excerpt', '', '');
                $oFormValidation->set_rules('image_id', '', '');
                $oFormValidation->set_rules('video_url', '', '');
                $oFormValidation->set_rules('audio_url', '', '');
                $oFormValidation->set_rules('seo_description', '', '');
                $oFormValidation->set_rules('seo_keywords', '', '');

                if ($oInput->post('slug')) {

                    $sTable = $this->blog_post_model->getTableName();
                    $oFormValidation->set_rules(
                        'slug',
                        '',
                        'trim|alpha_dash|is_unique[' . $sTable . '.slug]'
                    );
                }

                if ($oInput->post('type') === 'PHOTO') {

                    $oFormValidation->set_rules('image_id', '', 'required');

                } elseif ($oInput->post('type') === 'VIDEO') {

                    $oFormValidation->set_rules('video_url', '', 'required');

                } elseif ($oInput->post('type') === 'AUDIO') {

                    $oFormValidation->set_rules('audio_url', '', 'required');
                }

                $oFormValidation->set_message('required', lang('fv_required'));
                $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
                $oFormValidation->set_message('is_unique', 'A post using this slug already exists.');

            }

            if (!empty($bIsPreview) || $oFormValidation->run($this)) {

                //  Prepare data
                $aData                     = array();
                $aData['blog_id']          = $this->blog->id;
                $aData['title']            = $oInput->post('title');
                $aData['type']             = $oInput->post('type');
                $aData['slug']             = $oInput->post('slug');
                $aData['excerpt']          = $oInput->post('excerpt');
                $aData['image_id']         = (int) $oInput->post('image_id');
                $aData['image_id']         = $aData['image_id'] ? $aData['image_id'] : null;
                $aData['video_url']        = trim($oInput->post('video_url'));
                $aData['video_url']        = $aData['video_url'] ? $aData['video_url'] : null;
                $aData['audio_url']        = trim($oInput->post('audio_url'));
                $aData['audio_url']        = $aData['audio_url'] ? $aData['audio_url'] : null;
                $aData['body']             = $oInput->post('body');
                $aData['seo_description']  = $oInput->post('seo_description');
                $aData['seo_keywords']     = $oInput->post('seo_keywords');
                $aData['is_published']     = (bool) $oInput->post('is_published');
                $aData['published']        = $oInput->post('published');
                $aData['associations']     = $oInput->post('associations');
                $aData['gallery']          = $oInput->post('gallery');
                $aData['comments_enabled'] = $oInput->post('comments_enabled');
                $aData['comments_expire']  = $oInput->post('comments_expire');

                if (appSetting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $aData['categories'] = $oInput->post('categories');
                }

                if (appSetting('tags_enabled', 'blog-' . $this->blog->id)) {

                    $aData['tags'] = $oInput->post('tags');
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
                        $this->oChangeLogModel->add(
                            'created',
                            'a',
                            'blog post',
                            $iPostId,
                            $aData['title'],
                            'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId
                        );

                        $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);
                        $oSession->setFlashData('success', ucfirst($this->data['postName']) . ' was created.');

                        $sRedirectUrl = 'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId;

                    } else {

                        $sRedirectUrl = $this->blog->url . '/preview/' . $iPostId;
                    }

                    redirect($sRedirectUrl);

                } else {

                    $this->data['error']  = 'An error occurred and the post could not be created. ';
                    $this->data['error'] .= $this->blog_post_model->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        //  Load Categories and Tags
        if (appSetting('categories_enabled', 'blog-' . $this->blog->id)) {

            $aData            = array();
            $aData['where']   = array();
            $aData['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['categories'] = $this->blog_category_model->getAll(null, null, $aData);
        }

        if (appSetting('tags_enabled', 'blog-' . $this->blog->id)) {

            $aData            = array();
            $aData['where']   = array();
            $aData['where'][] = array('column' => 'blog_id', 'value' => $this->blog->id);

            $this->data['tags'] = $this->blog_tag_model->getAll(null, null, $aData);
        }

        // --------------------------------------------------------------------------

        //  Load other data
        $oCdn                            = Factory::service('Cdn', Cdn\Constants::MODULE_SLUG);
        $this->data['associations']      = $this->blog_model->getAssociations();
        $this->data['cdnUrlScaleScheme'] = $oCdn->urlScaleScheme();

        // --------------------------------------------------------------------------

        //  Load assets
        $oAsset = Factory::service('Asset');
        $oAsset->library('uploadify');
        $oAsset->library('MUSTACHE');
        $oAsset->load('moment/moment.js', 'NAILS-BOWER');
        //  @todo (Pablo - 2019-09-12) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.post.edit.js', 'nails/module-blog');

        // --------------------------------------------------------------------------

        $sInlineJs  = 'var _EDIT;';
        $sInlineJs .= '$(function()';
        $sInlineJs .= '{';
        $sInlineJs .= '    _EDIT = new NAILS_Admin_Blog_Create_Edit(\'CREATE\');';
        $sInlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $oCdn->generateApiUploadToken() . '");';
        $sInlineJs .= '});';

        $oAsset->inline($sInlineJs, 'JS');

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
        $oUri    = Factory::service('Uri');
        $iPostId = (int) $oUri->segment(6);

        $this->data['post'] = $this->blog_post_model->getById($iPostId);

        if (!$this->data['post']) {

            show404();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Edit ' . $this->data['postName'] . ' &rsaquo; ' . $this->data['post']->title;

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Are we running in preview mode?
            $bIsPreview = (bool) $oInput->post('isPreview');

            //  Only validate non-previews
            if (empty($bIsPreview)) {

                $oFormValidation = Factory::service('FormValidation');

                $oFormValidation->set_rules('is_published', '', '');
                $oFormValidation->set_rules('published', '', '');
                $oFormValidation->set_rules('title', '', 'required');
                $oFormValidation->set_rules('type', '', 'required');
                $oFormValidation->set_rules('body', '', 'required');
                $oFormValidation->set_rules('excerpt', '', '');
                $oFormValidation->set_rules('image_id', '', '');
                $oFormValidation->set_rules('video_url', '', '');
                $oFormValidation->set_rules('audio_url', '', '');
                $oFormValidation->set_rules('seo_keywords', '', '');

                if ($oInput->post('slug')) {

                    $sTable = $this->blog_post_model->getTableName();
                    $oFormValidation->set_rules(
                        'slug',
                        '',
                        'alpha_dash|unique_if_diff[' . $sTable . '.slug.' . $this->data['post']->slug . ']'
                    );
                }

                if ($oInput->post('type') === 'PHOTO') {

                    $oFormValidation->set_rules('image_id', '', 'required');

                } elseif ($oInput->post('type') === 'VIDEO') {

                    $oFormValidation->set_rules('video_url', '', 'required|callback_callbackValidVideoUrl');

                } elseif ($oInput->post('type') === 'AUDIO') {

                    $oFormValidation->set_rules('audio_url', '', 'required|callback_callbackValidAudioUrl');
                }

                $oFormValidation->set_message('required', lang('fv_required'));
                $oFormValidation->set_message('alpha_dash', lang('fv_alpha_dash'));
                $oFormValidation->set_message('unique_if_diff', 'A post using this slug already exists.');
            }

            if (!empty($bIsPreview) || $oFormValidation->run($this)) {

                //  Prepare data
                $aData                     = array();
                $aData['title']            = $oInput->post('title');
                $aData['type']             = $oInput->post('type');
                $aData['slug']             = $oInput->post('slug');
                $aData['excerpt']          = $oInput->post('excerpt');
                $aData['image_id']         = (int) $oInput->post('image_id');
                $aData['image_id']         = $aData['image_id'] ? $aData['image_id'] : null;
                $aData['video_url']        = trim($oInput->post('video_url'));
                $aData['video_url']        = $aData['video_url'] ? $aData['video_url'] : null;
                $aData['audio_url']        = trim($oInput->post('audio_url'));
                $aData['audio_url']        = $aData['audio_url'] ? $aData['audio_url'] : null;
                $aData['body']             = $oInput->post('body');
                $aData['seo_description']  = $oInput->post('seo_description');
                $aData['seo_keywords']     = $oInput->post('seo_keywords');
                $aData['is_published']     = (bool) $oInput->post('is_published');
                $aData['published']        = $oInput->post('published');
                $aData['associations']     = $oInput->post('associations');
                $aData['gallery']          = $oInput->post('gallery');
                $aData['comments_enabled'] = $oInput->post('comments_enabled');
                $aData['comments_expire']  = $oInput->post('comments_expire');

                if (appSetting('categories_enabled', 'blog-' . $this->blog->id)) {

                    $aData['categories'] = $oInput->post('categories');
                }

                if (appSetting('tags_enabled', 'blog-' . $this->blog->id)) {

                    $aData['tags'] = $oInput->post('tags');
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

                                                $temp = $this->blog_category_model->getById($v);

                                                if ($temp) {

                                                    $aNewCategories[] = $temp->label;
                                                }
                                            }
                                        }

                                        asort($aOldCategories);
                                        asort($aNewCategories);

                                        $aOldCategories = implode(',', $aOldCategories);
                                        $aNewCategories = implode(',', $aNewCategories);

                                        $this->oChangeLogModel->add(
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

                                                $temp = $this->blog_tag_model->getById($v);

                                                if ($temp) {

                                                    $aNewTags[] = $temp->label;
                                                }
                                            }
                                        }

                                        asort($aOldTags);
                                        asort($aNewTags);

                                        $aOldTags = implode(',', $aOldTags);
                                        $aNewTags = implode(',', $aNewTags);

                                        $this->oChangeLogModel->add(
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

                                        $this->oChangeLogModel->add(
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

                        $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);
                        $oSession->setFlashData('success', ucfirst($this->data['postName']) . ' was updated.');

                        $sRedirectUrl = 'admin/blog/post/edit/' . $this->blog->id . '/' . $iPostId;

                    } else {

                        $sRedirectUrl = $this->blog->url . '/preview/' . $mResult;
                    }

                    redirect($sRedirectUrl);

                } else {

                    $this->data['error']  = 'An error occurred and the post could not be updated. ';
                    $this->data['error'] .= $this->blog_post_model->lastError();
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

        if (appSetting('categories_enabled', 'blog-' . $this->blog->id)) {

            $this->data['categories'] = $this->blog_category_model->getAll(null, null, $aData);
        }

        if (appSetting('tags_enabled', 'blog-' . $this->blog->id)) {

            $this->data['tags'] = $this->blog_tag_model->getAll(null, null, $aData);
        }

        // --------------------------------------------------------------------------

        //  Load other data
        $oCdn                            = Factory::service('Cdn', Cdn\Constants::MODULE_SLUG);
        $this->data['associations']      = $this->blog_model->getAssociations($this->data['post']->id);
        $this->data['cdnUrlScaleScheme'] = $oCdn->urlScaleScheme();

        // --------------------------------------------------------------------------

        //  Load assets
        $oAsset = Factory::service('Asset');
        $oAsset->library('uploadify');
        $oAsset->library('MUSTACHE');
        $oAsset->load('moment/moment.js', 'NAILS-BOWER');
        //  @todo (Pablo - 2019-09-12) - Update/Remove/Use minified once JS is refactored to be a module
        $oAsset->load('admin.post.edit.js', 'nails/module-blog');

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
        $sInlineJs .= '    _EDIT.init(' . $this->blog->id . ', "' . $oCdn->generateApiUploadToken() . '");';
        $sInlineJs .= '});';

        $oAsset->inline($sInlineJs, 'JS');

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
        $oUri     = Factory::service('Uri');
        $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);

        $iPostId = (int) $oUri->segment(6);
        $oPost   = $this->blog_post_model->getById($iPostId);

        if (!$oPost || $oPost->blog->id != $this->blog->id) {

            $oSession->setFlashData('error', 'I could\'t find a post by that ID.');
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
            $this->oChangeLogModel->add('deleted', 'a', 'blog post', $iPostId, $oPost->title);

        } else {

            $sStatus  = 'error';
            $sMessage = 'I failed to delete that post. ' . $this->blog_post_model->lastError();
        }

        $oSession->setFlashData($sStatus, $sMessage);

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
        $oUri     = Factory::service('Uri');
        $oSession = Factory::service('Session', Auth\Constants::MODULE_SLUG);

        $iPostId = (int) $oUri->segment(6);

        // --------------------------------------------------------------------------

        if ($this->blog_post_model->restore($iPostId)) {

            $oPost = $this->blog_post_model->getById($iPostId);

            $oSession->setFlashData('success', ucfirst($this->data['postName']) . ' was restored successfully.');

            //  Update admin changelog
            $this->oChangeLogModel->add(
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
            $sMessage .= $this->blog_post_model->lastError();
            $oSession->setFlashData($sStatus, $sMessage);
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
    public function callbackValidAudioUrl($sUrl)
    {
        $sId = $this->blog_post_model->extractSpotifyId($sUrl);

        if (!empty($sId)) {

            return true;

        } else {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_message('callbackValidAudioUrl', 'Not a valid Spotify Track URL.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation callback, checks that a YouTube or Vimeo ID can be extracted from the string
     * @param  string  $sUrl The URL to check
     * @return boolean
     */
    public function callbackValidVideoUrl($sUrl)
    {
        $sId = $this->blog_post_model->extractYoutubeId($sUrl);

        if (!empty($sId)) {

            return true;

        } else {

            $sId = $this->blog_post_model->extractVimeoId($sUrl);

            if (!empty($sId)) {

                return true;

            } else {

                $oFormValidation = Factory::service('FormValidation');
                $oFormValidation->set_message('_callbackValidVideoUrl', 'Not a valid YouTube or Vimeo URL.');
                return false;
            }
        }
    }
}
