<?php

/**
 * This model handles everything to do with blog posts.
 * @todo        : Move the logic from here into a Factory loaded models
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;

class NAILS_Blog_post_model extends NAILS_Model
{
    protected $reservedWords;

    // --------------------------------------------------------------------------

    /**
     * Constructs the model, setting defaults
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Define tables and prefixes
        $this->usePreviewTables(false);
        $this->tableHit       = NAILS_DB_PREFIX . 'blog_post_hit';
        $this->tableHitPrefix = 'bph';

        $this->tableLabelColumn  = 'title';
        $this->destructiveDelete = false;

        $this->defaultSortColumn = 'published';
        $this->defaultSortOrder  = 'DESC';

        // --------------------------------------------------------------------------

        //  Define reserved words (for slugs, basically just controller methods)
        $this->reservedWords = ['index', 'single', 'category', 'tag', 'archive', 'preview'];
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the table names to use, either the normal tables, or the preview tables.
     *
     * @param  boolean $bEnabled Whether to use the preview tables or not
     *
     * @return void
     */
    public function usePreviewTables($bEnabled)
    {
        if (!empty($bEnabled)) {

            $this->isPreviewMode = true;

            $this->table      = NAILS_DB_PREFIX . 'blog_post_preview';
            $this->tableAlias = 'bp';

            $this->tableCat       = NAILS_DB_PREFIX . 'blog_post_preview_category';
            $this->tableCatPrefix = 'bpc';

            $this->tableTag       = NAILS_DB_PREFIX . 'blog_post_preview_tag';
            $this->tableTagPrefix = 'bpt';

            $this->tableImg       = NAILS_DB_PREFIX . 'blog_post_preview_image';
            $this->tableImgPrefix = 'bpi';

        } else {

            $this->isPreviewMode = false;

            $this->table      = NAILS_DB_PREFIX . 'blog_post';
            $this->tableAlias = 'bp';

            $this->tableCat       = NAILS_DB_PREFIX . 'blog_post_category';
            $this->tableCatPrefix = 'bpc';

            $this->tableTag       = NAILS_DB_PREFIX . 'blog_post_tag';
            $this->tableTagPrefix = 'bpt';

            $this->tableImg       = NAILS_DB_PREFIX . 'blog_post_image';
            $this->tableImgPrefix = 'bpi';
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     *
     * @param  array $aData The data to create the object with
     *
     * @return mixed
     **/
    public function create($aData = [], $bReturnObject = false)
    {
        //  Prepare slug
        $counter = 0;

        if (!$this->isPreviewMode && empty($aData['title'])) {
            $this->setError('Title missing');
            return false;
        }

        if (empty($aData['blog_id'])) {
            $this->setError('Blog ID missing');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Validate or generate a slug
         */
        $sSlug  = !empty($aData['slug']) ? $aData['slug'] : '';
        $sTitle = !empty($aData['title']) ? $aData['title'] : '';

        $aData['slug'] = $this->validateSlug($sSlug, $sTitle);

        if (!$aData['slug']) {

            return false;
        }

        // --------------------------------------------------------------------------

        //  Set data
        $oDb = Factory::service('Database');
        $oDb->set('blog_id', $aData['blog_id']);
        $oDb->set('title', $aData['title']);
        $oDb->set('slug', $aData['slug']);

        if (isset($aData['type'])) {
            $oDb->set('type', $aData['type']);
        }

        if (isset($aData['body'])) {
            $oDb->set('body', $aData['body']);
        }

        if (isset($aData['seo_title'])) {
            $oDb->set('seo_title', $aData['title']);
        }

        if (isset($aData['seo_description'])) {
            $oDb->set('seo_description', $aData['seo_description']);
        }

        if (isset($aData['seo_keywords'])) {
            $oDb->set('seo_keywords', $aData['seo_keywords']);
        }

        if (isset($aData['is_published'])) {
            $oDb->set('is_published', $aData['is_published']);
        }

        //  Safety first!
        if (array_key_exists('image_id', $aData)) {

            $imageId = (int) $aData['image_id'];
            $imageId = !$imageId ? null : $imageId;

            $oDb->set('image_id', $imageId);
        }

        if (isset($aData['video_url'])) {
            $oDb->set('video_url', $aData['video_url']);
        }

        if (isset($aData['audio_url'])) {
            $oDb->set('audio_url', $aData['audio_url']);
        }

        //  Excerpt
        if (!empty($aData['excerpt'])) {
            $oDb->set('excerpt', trim($aData['excerpt']));
        } elseif (!empty($aData['body'])) {
            $oDb->set('excerpt', word_limiter(trim(strip_tags($aData['body']))), 50);
        }

        //  Publish date
        if (!empty($aData['is_published']) && isset($aData['published'])) {

            //  Published with date set
            $published = strtotime($aData['published']);

            if ($published) {

                $published = toNailsDatetime($aData['published']);
                $oDb->set('published', $published);

            } else {

                //  Failed, use NOW();
                $oDb->set('published', 'NOW()', false);
            }

        } else {
            //  No date set, use NOW()
            $oDb->set('published', 'NOW()', false);
        }

        if (isset($aData['comments_enabled'])) {
            $oDb->set('comments_enabled', (bool) $aData['comments_enabled']);
        }

        if (isset($aData['comments_expire'])) {
            if (empty($aData['comments_expire'])) {
                $oDb->set('comments_expire', null);
            } else {
                $oDb->set('comments_expire', $aData['comments_expire']);
            }
        }

        $oDb->set('created', 'NOW()', false);
        $oDb->set('modified', 'NOW()', false);
        $oDb->set('created_by', activeUser('id'));
        $oDb->set('modified_by', activeUser('id'));

        $oDb->insert($this->table);

        if ($oDb->affected_rows()) {

            $id = $oDb->insert_id();

            //  Add Gallery items, if any
            if (!empty($aData['gallery'])) {

                $galleryData = [];

                foreach ($aData['gallery'] as $order => $imageId) {
                    if ((int) $imageId) {
                        $galleryData[] = ['post_id' => $id, 'image_id' => $imageId, 'order' => $order];
                    }
                }

                if ($galleryData) {
                    $oDb->insert_batch($this->tableImg, $galleryData);
                }
            }

            // --------------------------------------------------------------------------

            //  Add Categories and tags, if any
            if (!empty($aData['categories'])) {

                $categoryData = [];

                foreach ($aData['categories'] as $catId) {
                    $categoryData[] = ['post_id' => $id, 'category_id' => $catId];
                }

                $oDb->insert_batch($this->tableCat, $categoryData);
            }

            if (!empty($aData['tags'])) {

                $tagData = [];

                foreach ($aData['tags'] as $tagId) {
                    $tagData[] = ['post_id' => $id, 'tag_id' => $tagId];
                }

                $oDb->insert_batch($this->tableTag, $tagData);
            }

            // --------------------------------------------------------------------------

            //  Add associations, if any
            if (!empty($aData['associations'])) {

                //  Fetch associations config
                $oConfig      = Factory::service('Config');
                $associations = $oConfig->item('blog_post_associations');

                foreach ($aData['associations'] as $index => $association) {

                    if (!isset($associations[$index])) {
                        continue;
                    }

                    $associationData = [];

                    foreach ($association as $associationId) {
                        $associationData[] = ['post_id' => $id, 'associated_id' => $associationId];
                    }

                    if ($associationData) {
                        $oDb->insert_batch($associations[$index]->target, $associationData);
                    }
                }
            }

            // --------------------------------------------------------------------------

            return $bReturnObject ? $this->getById($id) : $id;

        } else {
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     *
     * @param  int   $id   The ID of the object to update
     * @param  array $data The data to update the object with
     *
     * @return bool
     **/
    public function update($id, $data = [])
    {
        //  If we're deleting a post, skip all the rest
        if (!empty($data['is_deleted'])) {
            return parent::update($id, $data);
        }

        /**
         * Validate or generate a slug
         */
        $sSlug  = !empty($data['slug']) ? $data['slug'] : '';
        $sTitle = !empty($data['title']) ? $data['title'] : '';
        $sSlug  = $this->validateSlug($sSlug, $sTitle, $id);

        $oDb = Factory::service('Database');

        if (!$sSlug) {
            return false;
        } else {
            $oDb->set('slug', $sSlug);
        }

        //  Set data
        if (isset($data['blog_id'])) {
            $oDb->set('blog_id', $data['blog_id']);
        }

        if (isset($data['title'])) {
            $oDb->set('title', $data['title']);
        }

        if (isset($data['body'])) {
            $oDb->set('body', $data['body']);
        }

        if (isset($data['type'])) {
            $oDb->set('type', $data['type']);
        }

        if (isset($data['seo_title'])) {
            $oDb->set('seo_title', $data['title']);
        }

        if (isset($data['seo_description'])) {
            $oDb->set('seo_description', $data['seo_description']);
        }

        if (isset($data['seo_keywords'])) {
            $oDb->set('seo_keywords', $data['seo_keywords']);
        }

        if (isset($data['is_published'])) {
            $oDb->set('is_published', $data['is_published']);
        }

        if (isset($data['is_deleted'])) {
            $oDb->set('is_deleted', $data['is_deleted']);
        }

        //  Safety first!
        if (array_key_exists('image_id', $data)) {

            $imageId = (int) $data['image_id'];
            $imageId = !$imageId ? null : $imageId;

            $oDb->set('image_id', $imageId);
        }

        if (isset($data['video_url'])) {
            $oDb->set('video_url', $data['video_url']);
        }

        if (isset($data['audio_url'])) {
            $oDb->set('audio_url', $data['audio_url']);
        }

        //  Excerpt
        if (!empty($data['excerpt'])) {
            $oDb->set('excerpt', trim($data['excerpt']));
        } elseif (!empty($data['body'])) {
            $oDb->set('excerpt', word_limiter(trim(strip_tags($data['body']))), 50);
        }

        //  Publish date
        if (!empty($data['is_published']) && isset($data['published'])) {

            //  Published with date set
            $published = strtotime($data['published']);

            if ($published) {

                $published = toNailsDatetime($data['published']);

                $oDb->set('published', $published);

            } else {
                //  Failed, use NOW();
                $oDb->set('published', 'NOW()', false);
            }

        } else {

            //  No date set, use NOW();
            $oDb->set('published', 'NOW()', false);
        }

        if (isset($data['comments_enabled'])) {
            $oDb->set('comments_enabled', (bool) $data['comments_enabled']);
        }

        if (isset($data['comments_expire'])) {

            if (empty($data['comments_expire'])) {
                $oDb->set('comments_expire', null);
            } else {
                $oDb->set('comments_expire', $data['comments_expire']);
            }
        }

        $oDb->set('modified', 'NOW()', false);

        if (activeUser('id')) {

            $oDb->set('modified_by', activeUser('id'));
        }

        $oDb->where('id', $id);
        $oDb->update($this->table);

        // --------------------------------------------------------------------------

        //  Update/reset the post gallery if it's been defined
        if (isset($data['gallery'])) {

            //  Delete all categories
            $oDb->where('post_id', $id);
            $oDb->delete($this->tableImg);

            //  Recreate new ones
            if ($data['gallery']) {

                $galleryData = [];

                foreach ($data['gallery'] as $order => $imageId) {
                    if ((int) $imageId) {
                        $galleryData[] = ['post_id' => $id, 'image_id' => $imageId, 'order' => $order];
                    }
                }

                if ($galleryData) {

                    $oDb->insert_batch($this->tableImg, $galleryData);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Update/reset any categories/tags if any have been defined
        if (isset($data['categories'])) {

            //  Delete all categories
            $oDb->where('post_id', $id);
            $oDb->delete($this->tableCat);

            //  Recreate new ones
            if ($data['categories']) {

                $categoryData = [];

                foreach ($data['categories'] as $catId) {
                    $categoryData[] = ['post_id' => $id, 'category_id' => $catId];
                }

                $oDb->insert_batch($this->tableCat, $categoryData);
            }
        }

        if (isset($data['tags'])) {

            //  Delete all tags
            $oDb->where('post_id', $id);
            $oDb->delete($this->tableTag);

            //  Recreate new ones
            if ($data['tags']) {

                $tagData = [];

                foreach ($data['tags'] as $tagId) {
                    $tagData[] = ['post_id' => $id, 'tag_id' => $tagId];
                }

                $oDb->insert_batch($this->tableTag, $tagData);
            }
        }

        // --------------------------------------------------------------------------

        //  Add associations, if any
        if (isset($data['associations']) && is_array($data['associations'])) {

            //  Fetch association config
            $this->load->model('blog/blog_model');
            $associations = $this->blog_model->getAssociations();

            foreach ($data['associations'] as $index => $association) {

                if (!isset($associations[$index])) {

                    continue;
                }

                //  Clear old associations
                $oDb->where('post_id', $id);
                $oDb->delete($associations[$index]->target);

                //  Add new ones
                $associationData = [];

                foreach ($association as $associationId) {

                    $associationData[] = ['post_id' => $id, 'associated_id' => $associationId];
                }

                if ($associationData) {

                    $oDb->insert_batch($associations[$index]->target, $associationData);
                }
            }
        }

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Validates a slug, if supplied, generates one from the title, if not
     *
     * @param  string  $sSlug  The slug to test
     * @param  string  $sTitle The title to generate a slug from if no slug available
     * @param  integer $siId   The ID of the post to ignore from a comparison, if any
     *
     * @return string
     */
    private function validateSlug($sSlug, $sTitle, $iId = null)
    {
        /**
         * Slugs don't matter in preview mode
         */
        if ($this->isPreviewMode) {
            return 'slug';
        }

        // --------------------------------------------------------------------------

        /**
         * Handle the slug
         * If a slug has been provided, check it is unique, if one hasn't then
         * generate one.
         */

        if (empty($sSlug)) {

            $prefix = array_search($sSlug, $this->reservedWords) !== false ? 'post-' : '';
            $sSlug  = $this->generateSlug($sTitle, $prefix);

        } else {

            $oDb = Factory::service('Database');

            if (!empty($iId)) {
                $oDb->where('id !=', $iId);
            }
            $oDb->where('slug', $sSlug);
            if ($oDb->count_all_results($this->table)) {
                $this->setError('Slug "' . $sSlug . '" is already in use by another post.');
                return false;
            }
        }

        //  If a the slug is a reserved word then bail out
        if (array_search($sSlug, $this->reservedWords) !== false) {

            $this->setError('Slug "' . $sSlug . '" is a reserved word and cannot be used.');
            return false;
        }

        return $sSlug;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all posts
     *
     * @param int   $page           The page number of the results, if null then no pagination
     * @param int   $perPage        How many items per page of paginated results
     * @param mixed $data           Any data to pass to getCountCommon()
     * @param bool  $includeDeleted If non-destructive delete is enabled then include deleted items
     *
     * @return array
     **/
    public function getAll($page = null, $perPage = null, $data = null, $includeDeleted = false)
    {
        //  If the first value is an array then treat as if called with getAll(null, null, $aData);
        //  @todo (Pablo - 2017-11-09) - Convert these to expandable fields
        if (is_array($page)) {
            $data = $page;
            $page = null;
        }

        $posts = parent::getAll($page, $perPage, $data, $includeDeleted);

        $this->load->model('blog/blog_model');
        $oDb          = Factory::service('Database');
        $associations = $this->blog_model->getAssociations();

        foreach ($posts as $post) {

            //  Fetch associated categories
            $post->categories = [];

            if (!empty($data['include_categories'])) {

                $this->load->model('blog/blog_category_model');

                $oDb->select('c.id,c.blog_id,c.slug,c.label');
                $oDb->join(
                    NAILS_DB_PREFIX . 'blog_category c',
                    'c.id = ' . $this->tableCatPrefix . '.category_id'
                );
                $oDb->where($this->tableCatPrefix . '.post_id', $post->id);
                $oDb->group_by('c.id');
                $oDb->order_by('c.label');
                $post->categories = $oDb->get($this->tableCat . ' ' . $this->tableCatPrefix)->result();

                foreach ($post->categories as $c) {

                    $c->url = $this->blog_category_model->formatUrl($c->slug, $c->blog_id);
                }
            }

            // --------------------------------------------------------------------------

            //  Fetch associated tags
            $post->tags = [];

            if (!empty($data['include_tags'])) {

                $this->load->model('blog/blog_tag_model');

                //  Fetch associated tags
                $oDb->select('t.id,t.blog_id,t.slug,t.label');
                $oDb->join(NAILS_DB_PREFIX . 'blog_tag t', 't.id = ' . $this->tableTagPrefix . '.tag_id');
                $oDb->where($this->tableTagPrefix . '.post_id', $post->id);
                $oDb->group_by('t.id');
                $oDb->order_by('t.label');
                $post->tags = $oDb->get($this->tableTag . ' ' . $this->tableTagPrefix)->result();

                foreach ($post->tags as $t) {

                    $t->url = $this->blog_tag_model->formatUrl($t->slug, $t->blog_id);
                }
            }

            // --------------------------------------------------------------------------

            //  Fetch other associations
            $post->associations = [];

            if (!empty($data['include_associations']) && $associations) {

                foreach ($associations as $index => $assoc) {

                    $post->associations[$index] = $assoc;

                    /**
                     * Fetch the association data from the source, fail ungracefully - the
                     * dev should have this configured correctly.
                     */

                    $oDb->select('src.' . $assoc->source->id . ' id, src.' . $assoc->source->label . ' label');
                    $oDb->join(
                        $assoc->source->table . ' src',
                        'src.' . $assoc->source->id . '=target.associated_id',
                        'LEFT'
                    );
                    $oDb->where('target.post_id', $post->id);
                    $post->associations[$index]->current = $oDb->get($assoc->target . ' target')->result();
                }
            }

            // --------------------------------------------------------------------------

            //  Fetch associated images
            $post->gallery = [];
            if (!empty($data['include_gallery'])) {

                $oDb->where('post_id', $post->id);
                $oDb->order_by('order');
                $post->gallery = $oDb->get($this->tableImg)->result();

            }

            // --------------------------------------------------------------------------

            //  Fetch siblings
            $post->siblings       = new \stdClass();
            $post->siblings->next = null;
            $post->siblings->prev = null;

            if (!empty($data['include_siblings'])) {

                $aSiblingData = [
                    'sort'  => [$this->tableAlias . '.published', 'desc'],
                    'where' => [
                        ['column' => $this->tableAlias . '.published >', 'value' => $post->published],
                        ['column' => $this->tableAlias . '.blog_id', 'value' => $post->blog->id],
                        ['column' => $this->tableAlias . '.is_published', 'value' => true],
                        ['column' => $this->tableAlias . '.published <=', 'value' => 'NOW()', 'escape' => false],
                    ],
                ];
                $aResult      = $this->getAll(0, 1, $aSiblingData);

                if (!empty($aResult)) {
                    $post->siblings->next = $aResult[0];
                }

                $aSiblingData['where'][0]['column'] = $this->tableAlias . '.published <';
                $aResult                            = $this->getAll(0, 1, $aSiblingData);

                if (!empty($aResult)) {
                    $post->siblings->prev = $aResult[0];
                }
            }
        }

        // --------------------------------------------------------------------------

        return $posts;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch a pst by it's ID
     *
     * @param  int   $id   The ID of the object to fetch
     * @param  mixed $data Any data to pass to getCountCommon()
     *
     * @return stdClass
     **/
    public function getById($id, $data = null)
    {
        $data = $this->includeEverything($data);
        return parent::getById($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch a post by it's slug
     *
     * @param  int   $slug The slug of the object to fetch
     * @param  mixed $data Any data to pass to getCountCommon()
     *
     * @return stdClass
     **/
    public function getBySlug($id, $data = null)
    {
        $data = $this->includeEverything($data);
        return parent::getBySlug($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch an object by it's id or slug
     *
     * Auto-detects whether to use the ID or slug as the selector when fetching
     * an object. Note that this method uses is_numeric() to determine whether
     * an ID or a slug has been passed, thus numeric slugs (which are against
     * Nails style guidelines) will be interpreted incorrectly.
     *
     * @param  mixed $id_slug The ID or slug of the object to fetch
     * @param  mixed $data    Any data to pass to getCountCommon()
     *
     * @return stdClass
     **/
    public function getByIdOrSlug($id, $data = null)
    {
        $data = $this->includeEverything($data);
        return parent::getById($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     *
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $data Data passed from the calling method
     *
     * @return void
     **/
    protected function getCountCommon($data = [])
    {
        $oDb = Factory::service('Database');
        $oDb->select(
            [
                $this->tableAlias . '.id',
                $this->tableAlias . '.blog_id',
                'b.label blog_label',
                $this->tableAlias . '.slug',
                $this->tableAlias . '.title',
                $this->tableAlias . '.image_id',
                $this->tableAlias . '.excerpt',
                $this->tableAlias . '.seo_title',
                $this->tableAlias . '.seo_description',
                $this->tableAlias . '.seo_keywords',
                $this->tableAlias . '.is_published',
                $this->tableAlias . '.is_deleted',
                $this->tableAlias . '.created',
                $this->tableAlias . '.created_by',
                $this->tableAlias . '.modified',
                $this->tableAlias . '.modified_by',
                $this->tableAlias . '.published',
                $this->tableAlias . '.comments_enabled',
                $this->tableAlias . '.comments_expire',
                $this->tableAlias . '.type',
                $this->tableAlias . '.audio_url',
                $this->tableAlias . '.video_url',
                'u.first_name',
                'u.last_name',
                'ue.email',
                'u.profile_img',
                'u.gender',
            ]
        );

        $oDb->join(NAILS_DB_PREFIX . 'blog b', $this->tableAlias . '.blog_id = b.id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user u', $this->tableAlias . '.modified_by = u.id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        // --------------------------------------------------------------------------

        if (!empty($data['include_body'])) {
            $oDb->select($this->tableAlias . '.body');
        }

        // --------------------------------------------------------------------------

        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {

                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->tableAlias . '.title',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->tableAlias . '.excerpt',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->tableAlias . '.body',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->tableAlias . '.seo_description',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->tableAlias . '.seo_keywords',
                'value'  => $data['keywords'],
            ];
        }

        // --------------------------------------------------------------------------

        parent::getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the data array to include everything
     *
     * This method is called by the getBy*() methods and, if not already set,
     * will alter the $data array so that all the include_* parameters are set.
     *
     * @param string $data Data passed from the calling method
     *
     * @return void
     **/
    protected function includeEverything($data)
    {
        if (is_null($data)) {

            $data = [];
        }

        if (!isset($data['include_body'])) {

            $data['include_body'] = true;
        }

        if (!isset($data['include_categories'])) {

            $data['include_categories'] = true;
        }

        if (!isset($data['include_tags'])) {

            $data['include_tags'] = true;
        }

        if (!isset($data['include_associations'])) {

            $data['include_associations'] = true;
        }

        if (!isset($data['include_gallery'])) {

            $data['include_gallery'] = true;
        }

        if (!isset($data['include_siblings'])) {

            $data['include_siblings'] = true;
        }

        // --------------------------------------------------------------------------

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches latest posts
     *
     * @param  int   $limit          The number of posts to return
     * @param  mixed $data           Any data to pass to getCountCommon()
     * @param  bool  $includeDeleted If non-destructive delete is enabled then include deleted items
     *
     * @return array
     **/
    public function getLatest($limit = 9, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');
        $oDb->limit($limit);
        $oDb->order_by($this->tableAlias . '.published', 'DESC');
        return $this->getAll(null, null, $data, $includeDeleted, 'GET_LATEST');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches posts published within a certain year and/or month
     *
     * @param  int   $year           The year to restrict the search to
     * @param  int   $month          The month to restrict the search to
     * @param  mixed $data           Any data to pass to getCountCommon()
     * @param  bool  $includeDeleted If non-destructive delete is enabled then include deleted items
     *
     * @return array
     **/
    public function getArchive($year = null, $month = null, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');

        if ($year) {
            $oDb->where('YEAR(' . $this->tableAlias . '.published) = ', (int) $year);
        }

        // --------------------------------------------------------------------------

        if ($month) {
            $oDb->where('MONTH(' . $this->tableAlias . '.published) = ', (int) $month);
        }

        // --------------------------------------------------------------------------

        return $this->getAll(null, null, $data, $includeDeleted, 'GET_ARCHIVE');
    }

    // --------------------------------------------------------------------------

    /**
     * Gets posts which are in a particular category
     *
     * @param  mixed   $categoryIdSlug The category's ID or slug
     * @param  int     $page           The page to render
     * @param  int     $perPage        The number of posts per page
     * @param  array   $data           Data to pass to getCountCommon()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     *
     * @return array
     */
    public function getWithCategory($categoryIdSlug, $page = null, $perPage = null, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');

        //  Join the $this->tableCat table so we can WHERE on it.
        $oDb->join(
            $this->tableCat . ' ' . $this->tableCatPrefix,
            $this->tableCatPrefix . '.post_id = ' . $this->tableAlias . '.id'
        );
        $oDb->join(
            NAILS_DB_PREFIX . 'blog_category bc',
            'bc.id = ' . $this->tableCatPrefix . '.category_id'
        );

        //  Set the where
        if (is_null($data)) {
            $data = ['where' => []];
        }

        if (!isset($data['where'])) {
            $data['where'] = [];
        }

        if (is_numeric($categoryIdSlug)) {
            $data['where'][] = ['column' => 'bc.id', 'value' => (int) $categoryIdSlug];
        } else {
            $data['where'][] = ['column' => 'bc.slug', 'value' => $categoryIdSlug];
        }

        $oDb->group_by($this->tableAlias . '.id');

        return $this->getAll($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of posts in a particular category
     *
     * @param  mixed   $categoryIdSlug The category's ID or slug
     * @param  array   $data           Data to pass to getCountCommon()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     *
     * @return int
     */
    public function countWithCategory($categoryIdSlug, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');

        //  Join the $this->tableCat table so we can WHERE on it.
        $oDb->join(
            $this->tableCat . ' ' . $this->tableCatPrefix,
            $this->tableCatPrefix . '.post_id = ' . $this->tableAlias . '.id'
        );
        $oDb->join(
            NAILS_DB_PREFIX . 'blog_category bc',
            'bc.id = ' . $this->tableCatPrefix . '.category_id'
        );

        //  Set the where
        if (is_null($data)) {
            $data = ['where' => []];
        }

        if (is_numeric($categoryIdSlug)) {
            $data['where'][] = ['column' => 'bc.id', 'value' => (int) $categoryIdSlug];
        } else {
            $data['where'][] = ['column' => 'bc.slug', 'value' => $categoryIdSlug];
        }

        return $this->countAll($data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets posts which are in a particular tag
     *
     * @param  mixed   $tagIdSlug      The tag's ID or slug
     * @param  int     $page           The page to render
     * @param  int     $perPage        The number of posts per page
     * @param  array   $data           Data to pass to getCountCommon()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     *
     * @return array
     */
    public function getWithTag($tagIdSlug, $page = null, $perPage = null, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');

        //  Join the $this->tableTag table so we can WHERE on it.
        $oDb->join(
            $this->tableTag . ' ' . $this->tableTagPrefix,
            $this->tableTagPrefix . '.post_id = ' . $this->tableAlias . '.id'
        );
        $oDb->join(
            NAILS_DB_PREFIX . 'blog_tag bt',
            'bt.id = ' . $this->tableTagPrefix . '.tag_id'
        );

        //  Set the where
        if (is_null($data)) {
            $data = ['where' => []];
        }

        if (is_numeric($tagIdSlug)) {
            $data['where'][] = ['column' => 'bt.id', 'value' => (int) $tagIdSlug];
        } else {
            $data['where'][] = ['column' => 'bt.slug', 'value' => $tagIdSlug];
        }

        $oDb->group_by($this->tableAlias . '.id');

        return $this->getAll($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of posts in a particular tag
     *
     * @param  mixed   $tagIdSlug      The tag's ID or slug
     * @param  array   $data           Data to pass to getCountCommon()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     *
     * @return int
     */
    public function countWithTag($tagIdSlug, $data = null, $includeDeleted = false)
    {
        $oDb = Factory::service('Database');

        //  Join the $this->tableTag table so we can WHERE on it.
        $oDb->join(
            $this->tableTag . ' ' . $this->tableTagPrefix,
            $this->tableTagPrefix . '.post_id = ' . $this->tableAlias . '.id'
        );
        $oDb->join(
            NAILS_DB_PREFIX . 'blog_tag bt',
            'bt.id = ' . $this->tableTagPrefix . '.tag_id'
        );

        //  Set the where
        if (is_null($data)) {

            $data = ['where' => []];
        }

        if (is_numeric($tagIdSlug)) {

            $data['where'][] = ['column' => 'bt.id', 'value' => (int) $tagIdSlug];

        } else {

            $data['where'][] = ['column' => 'bt.slug', 'value' => $tagIdSlug];
        }

        return $this->countAll($data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    public function countDrafts($blogId, $data = [], $includeDeleted = false)
    {
        $data['where'] = [
            ['blog_id', $blogId],
            ['is_published', false],
        ];

        return $this->countAll($data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Get posts with a particular association
     *
     * @param  int $associationIndex The association's index
     * @param  int $associatedId     The Id of the item to be associated with
     *
     * @return array
     */
    public function getWithAssociation($associationIndex, $associatedId)
    {
        $oConfig = Factory::service('Config');
        $oConfig->load('blog/blog');

        $associations = $oConfig->item('blog_post_associations');

        if (!isset($associations[$associationIndex])) {
            return [];
        }

        $oDb = Factory::service('Database');
        $oDb->select('post_id');
        $oDb->where('associated_id', $associatedId);
        $posts = $oDb->get($associations[$associationIndex]->target)->result();

        $ids = [];
        foreach ($posts as $post) {
            $ids[] = $post->post_id;
        }

        if (empty($ids)) {
            //  No IDs? No posts.
            return [];
        }

        $oDb->where_in($this->tableAlias . '.id', $ids);

        return $this->getAll();
    }

    // --------------------------------------------------------------------------

    /**
     * Add a hit to a post
     *
     * @param int   $id   The post's ID
     * @param array $data Details about the hit
     */
    public function addHit($id, $data = [])
    {
        if (!$id) {
            $this->setError('Post ID is required.');
            return false;
        }

        // --------------------------------------------------------------------------

        $oDate                 = Factory::factory('DateTime');
        $hitData               = [];
        $hitData['post_id']    = $id;
        $hitData['user_id']    = empty($data['user_id']) ? null : $data['user_id'];
        $hitData['ip_address'] = $this->input->ipAddress();
        $hitData['created']    = $oDate->format('Y-m-d H:i:s');
        $hitData['referrer']   = empty($data['referrer']) ? null : prep_url(trim($data['referrer']));

        if ($hitData['user_id'] && isAdmin($hitData['user_id'])) {
            $this->setError('Administrators cannot affect the post\'s popularity.');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Registered a hit on this post in the past 5 minutes? Try to prevent abuse
         * of the popularity system.
         */

        $oDb = Factory::service('Database');
        $oDb->where('post_id', $hitData['post_id']);
        $oDb->where('user_id', $hitData['user_id']);
        $oDb->where('ip_address', $hitData['ip_address']);
        $oDb->where('created > "' . $oDate->sub(new \DateInterval('PT5M'))->format('Y-m-d H:i:s') . '"');

        if ($oDb->count_all_results($this->tableHit)) {
            $this->setError('Hit timeout in effect.');
            return false;
        }

        // --------------------------------------------------------------------------

        $oDb->set($hitData);

        if ($oDb->insert($this->tableHit)) {
            return true;
        } else {
            $this->setError('Failed to add hit.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Parses the `type` column and returns an array of potential post types
     * @return array
     */
    public function getTypes()
    {
        $oDb     = Factory::service('Database');
        $oResult = $oDb->query('SHOW COLUMNS FROM `' . $this->table . '` LIKE "type"')->row();
        $sTypes  = $oResult->Type;
        $sTypes  = preg_replace('/enum\((.*)\)/', '$1', $sTypes);
        $sTypes  = str_replace("'", '', $sTypes);
        $aTypes  = explode(',', $sTypes);

        $aOut = [];

        foreach ($aTypes as $sType) {
            $aOut[$sType] = ucwords(strtolower($sType));
        }

        return $aOut;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a posts's URL
     *
     * @param  string $slug   The post's slug
     * @param  int    $blogId The blog's ID
     *
     * @return string
     */
    public function formatUrl($slug, $blogId)
    {
        $this->load->model('blog/blog_model');
        return $this->blog_model->getBlogUrl($blogId) . '/' . $slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Formats a single object
     *
     * The getAll() method iterates over each returned item with this method so as to
     * correctly format the output. Use this to cast integers and booleans and/or organise data into objects.
     *
     * @param  object $oObj      A reference to the object being formatted.
     * @param  array  $aData     The same data array which is passed to _getcount_common, for reference if needed
     * @param  array  $aIntegers Fields which should be cast as integers if numerical and not null
     * @param  array  $aBools    Fields which should be cast as booleans if not null
     * @param  array  $aFloats   Fields which should be cast as floats if not null
     *
     * @return void
     */
    protected function formatObject(
        &$oObj,
        $aData = [],
        $aIntegers = [],
        $aBools = [],
        $aFloats = []
    ) {

        parent::formatObject($oObj, $aData, $aIntegers, $aBools, $aFloats);

        //  Generate URL
        $oObj->url = $this->formatUrl($oObj->slug, $oObj->blog_id);

        //  Blog
        $oObj->blog        = new \stdClass();
        $oObj->blog->id    = (int) $oObj->blog_id;
        $oObj->blog->label = $oObj->blog_label;

        //  Author
        $oObj->author              = new \stdClass();
        $oObj->author->id          = (int) $oObj->modified_by;
        $oObj->author->first_name  = $oObj->first_name;
        $oObj->author->last_name   = $oObj->last_name;
        $oObj->author->email       = $oObj->email;
        $oObj->author->profile_img = $oObj->profile_img;
        $oObj->author->gender      = $oObj->gender;

        unset($oObj->blog_id);
        unset($oObj->blog_label);
        unset($oObj->modified_by);
        unset($oObj->first_name);
        unset($oObj->last_name);
        unset($oObj->email);
        unset($oObj->profile_img);
        unset($oObj->gender);

        // --------------------------------------------------------------------------

        //  Handle certain post types
        switch ($oObj->type) {

            case 'VIDEO':
                $oObj->video       = new \stdClass();
                $oObj->video->id   = $this->extractYoutubeId($oObj->video_url);
                $oObj->video->type = null;
                $oObj->video->url  = null;

                if (!empty($oObj->video->id)) {
                    $oObj->video->type = 'YOUTUBE';
                    $oObj->video->url  = 'https://www.youtube.com/watch?v=' . $oObj->video->id;
                } else {
                    $oObj->video->id = $this->extractVimeoId($oObj->video_url);
                    if (!empty($oObj->video->id)) {
                        $oObj->video->type = 'VIMEO';
                        $oObj->video->url  = 'https://www.vimeo.com/' . $oObj->video->id;
                    }
                }
                break;

            case 'AUDIO':
                $oObj->audio       = new \stdClass();
                $oObj->audio->id   = $this->extractSpotifyId($oObj->audio_url);
                $oObj->audio->type = null;
                $oObj->audio->url  = null;

                if (!empty($oObj->audio->id)) {
                    $oObj->audio->type = 'SPOTIFY';
                    $oObj->audio->url  = 'https://open.spotify.com/track/' . $oObj->audio->id;
                }
                break;

            case 'PHOTO':
                $oObj->photo     = new \stdClass();
                $oObj->photo->id = (int) $oObj->image_id ? (int) $oObj->image_id : null;
                break;
        }

        unset($oObj->image_id);
        unset($oObj->audio_url);
        unset($oObj->video_url);
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts the ID from a YouTube URL
     *
     * @param  string $sUrl The YouTube URL
     *
     * @return string
     */
    public function extractYoutubeId($sUrl)
    {
        preg_match('/^https?\:\/\/www\.youtube\.com\/watch\?v=([a-zA-Z0-9_\-]+)$/', $sUrl, $aMatches);

        if (!empty($aMatches[1])) {

            return $aMatches[1];

        } else {

            preg_match('/^https?\:\/\/youtu\.be\/([a-zA-Z0-9_\-]+)$/', $sUrl, $aMatches);

            if (!empty($aMatches[1])) {

                return $aMatches[1];
            }
        }

        return null;
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts the ID from a Vimeo URL
     *
     * @param  string $sUrl The Vimeo URL
     *
     * @return string
     */
    public function extractVimeoId($sUrl)
    {
        preg_match('/^https?\:\/\/(www\.)?vimeo\.com\/(.*\/)?([0-9]+)$/', $sUrl, $aMatches);

        if (!empty($aMatches[3])) {

            return $aMatches[3];

        } else {

            return null;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Extracts the ID from a Spotify URL
     *
     * @param  string $sUrl The Spotify URL
     *
     * @return string
     */
    public function extractSpotifyId($sUrl)
    {
        preg_match('/^https?\:\/\/open\.spotify\.com\/track\/([a-zA-Z0-9]+)$/', $sUrl, $aMatches);

        if (!empty($aMatches[1])) {

            return $aMatches[1];

        } else {

            return null;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the siblings of a post (i.e the posts before and after it)
     *
     * @param  integer $iPostId The post's ID
     * @param  array   $aData   Any data to pass to getAll()
     *
     * @return stdClass
     */
    public function getSiblings($iPostId, $aData = [])
    {
        $oOut = new \stdClass();
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' MODELS
 *
 * The following block of code makes it simple to extend one of the core Nails
 * models. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_POST_MODEL')) {

    class Blog_post_model extends NAILS_Blog_post_model
    {
    }
}
