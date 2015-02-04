<?php

/**
 * This model handles everything to do with blog posts.
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

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

        $this->_table               = NAILS_DB_PREFIX . 'blog_post';
        $this->_table_prefix        = 'bp'; //  Hard-coded throughout model; take care when changing this
        $this->_table_label_column  = 'title';
        $this->_destructive_delete  = false;

        // --------------------------------------------------------------------------

        //  Define reserved words (for slugs, basically just controller methods)
        $this->reservedWords = array('index', 'single', 'category','tag', 'archive');
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new object
     * @param  array $data The data to create the object with
     * @return mixed
     **/
    public function create($data = array())
    {
        //  Prepare slug
        $counter = 0;

        if (empty($data['title'])) {

            $this->_set_error('Title missing');
            return false;
        }

        if (empty($data['blog_id'])) {

            $this->_set_error('Blog ID missing');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Generate a slug
        $prefix = array_search($data['title'], $this->reservedWords) !== false ? 'post-' : '';
        $this->db->set('slug', $this->_generate_slug($data['title'], $prefix));

        // --------------------------------------------------------------------------

        //  Set data
        $this->db->set('blog_id', $data['blog_id']);
        $this->db->set('title', $data['title']);

        if (isset($data['body'])) {

            $this->db->set('body', $data['body']);
        }

        if (isset($data['seo_title'])) {

            $this->db->set('seo_title', $data['title']);
        }

        if (isset($data['seo_description'])) {

            $this->db->set('seo_description', $data['seo_description']);
        }

        if (isset($data['seo_keywords'])) {

            $this->db->set('seo_keywords', $data['seo_keywords']);
        }

        if (isset($data['is_published'])) {

            $this->db->set('is_published', $data['is_published']);
        }

        //  Safety first!
        if (array_key_exists('image_id', $data)) {

            $imageId = (int) $data['image_id'];
            $imageId = !$imageId ? null : $imageId;

            $this->db->set('image_id', $imageId);
        }

        //  Excerpt
        if (!empty($data['excerpt'])) {

            $this->db->set('excerpt', trim($data['excerpt']));

        } elseif (!empty($data['body'])) {

            $this->db->set('excerpt', word_limiter(trim(strip_tags($data['body']))), 50);
        }

        //  Publish date
        if (!empty($data['is_published']) && isset($data['published'])) {

            //  Published with date set
            $published = strtotime($data['published']);

            if ($published) {

                $published = userReverseDatetime($data['published']);
                $this->db->set('published', $published);

            } else {

                //  Failed, use NOW();
                $this->db->set('published', 'NOW()', false);
            }

        } else {

            //  No date set, use NOW()
            $this->db->set('published', 'NOW()', false);
        }

        $this->db->set('created', 'NOW()', false);
        $this->db->set('modified', 'NOW()', false);
        $this->db->set('created_by', active_user('id'));
        $this->db->set('modified_by', active_user('id'));

        $this->db->insert(NAILS_DB_PREFIX . 'blog_post');

        if ($this->db->affected_rows()) {

            $id = $this->db->insert_id();

            //  Add Gallery items, if any
            if (!empty($data['gallery'])) {

                $galleryData = array();

                foreach ($data['gallery'] as $order => $imageId) {

                    if ((int) $imageId) {

                        $galleryData[] = array('post_id' => $id, 'image_id' => $imageId, 'order' => $order);
                    }
                }

                if ($galleryData) {

                    $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_image', $galleryData);
                }
            }

            // --------------------------------------------------------------------------

            //  Add Categories and tags, if any
            if (!empty($data['categories'])) {

                $categoryData = array();

                foreach ($data['categories'] as $catId) {

                    $categoryData[] = array('post_id' => $id, 'category_id' => $catId);
                }

                $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_category', $categoryData);
            }

            if (!empty($data['tags'])) {

                $tagData = array();

                foreach ($data['tags'] as $tagId) {

                    $tagData[] = array('post_id' => $id, 'tag_id' => $tagId);
                }

                $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_tag', $tagData);
            }

            // --------------------------------------------------------------------------

            //  Add associations, if any
            if (!empty($data['associations'])) {

                //  Fetch associations config
                $associations = $this->config->item('blog_post_associations');

                foreach ($data['associations'] as $index => $association) {

                    if (!isset($associations[$index])) {

                        continue;
                    }

                    $associationData = array();

                    foreach ($association as $associationId) {

                        $associationData[] = array('post_id' => $id, 'associated_id' => $associationId);
                    }

                    if ($associationData) {

                        $this->db->insert_batch($associations[$index]->target, $associationData);
                    }
                }
            }

            // --------------------------------------------------------------------------

            return $id;

        } else {

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Updates an existing object
     * @param  int   $id   The ID of the object to update
     * @param  array $data The data to update the object with
     * @return bool
     **/
    public function update($id, $data = array())
    {
        /**
         * Prepare slug; the slug is regenrated if the blog post is in a transitioning
         * state to published. We don't want to be changing slugs of published posts
         * but while it's a draft we can do whatever (even if it was previously published,
         * made a draft then republished).
         */

        $this->db->select('is_published');
        $this->db->where('id', $id);
        $current = $this->db->get(NAILS_DB_PREFIX . 'blog_post')->row();

        if (!$current->is_published && $data['is_published']) {

            $counter = 0;

            if (!isset($data['title']) || !$data['title']) {

                $this->_set_error('Title missing');
                return false;
            }

            //  Generate a slug
            $prefix = array_search($data['title'], $this->reservedWords) !== false ? 'post-' : '';
            $slug   = $this->_generate_slug($data['title'], $prefix);

        } else {

            $slug = false;
        }

        // --------------------------------------------------------------------------

        //  Set data
        if (isset($data['blog_id'])) {

            $this->db->set('blog_id', $data['blog_id']);
        }

        if (isset($data['title'])) {

            $this->db->set('title', $data['title']);
        }

        if (isset($data['body'])) {

            $this->db->set('body', $data['body']);
        }

        if (isset($data['seo_title'])) {

            $this->db->set('seo_title', $data['title']);
        }

        if (isset($data['seo_description'])) {

            $this->db->set('seo_description', $data['seo_description']);
        }

        if (isset($data['seo_keywords'])) {

            $this->db->set('seo_keywords', $data['seo_keywords']);
        }

        if (isset($data['is_published'])) {

            $this->db->set('is_published', $data['is_published']);
        }

        if (isset($data['is_deleted'])) {

            $this->db->set('is_deleted', $data['is_deleted']);
        }

        //  Safety first!
        if (array_key_exists('image_id', $data)) {

            $imageId = (int) $data['image_id'];
            $imageId = !$imageId ? null : $imageId;

            $this->db->set('image_id', $imageId);
        }

        //  Excerpt
        if (!empty($data['excerpt'])) {

            $this->db->set('excerpt', trim($data['excerpt']));

        } elseif (!empty($data['body'])) {

            $this->db->set('excerpt', word_limiter(trim(strip_tags($data['body']))), 50);
        }

        //  Publish date
        if (!empty($data['is_published']) && isset($data['published'])) {

            //  Published with date set
            $published = strtotime($data['published']);

            if ($published) {

                $published = userReverseDatetime($data['published']);

                $this->db->set('published', $published);

            } else {

                //  Failed, use NOW();
                $this->db->set('published', 'NOW()', false);
            }

        } else {

            //  No date set, use NOW();
            $this->db->set('published', 'NOW()', false);
        }

        $this->db->set('modified', 'NOW()', false);

        if (active_user('id')) {

            $this->db->set('modified_by', active_user('id'));
        }

        if ($slug) {

            $this->db->set('slug', $slug);
        }

        $this->db->where('id', $id);
        $this->db->update(NAILS_DB_PREFIX . 'blog_post');

        // --------------------------------------------------------------------------

        //  Update/reset the post gallery if it's been defined
        if (isset($data['gallery'])) {

            //  Delete all categories
            $this->db->where('post_id', $id);
            $this->db->delete(NAILS_DB_PREFIX . 'blog_post_image');

            //  Recreate new ones
            if ($data['gallery']) {

                $galleryData = array();

                foreach ($data['gallery'] as $order => $imageId) {

                    if ((int) $imageId) {

                        $galleryData[] = array('post_id' => $id, 'image_id' => $imageId, 'order' => $order);
                    }
                }

                if ($galleryData) {

                    $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_image', $galleryData);
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Update/reset any categories/tags if any have been defined
        if (isset($data['categories'])) {

            //  Delete all categories
            $this->db->where('post_id', $id);
            $this->db->delete(NAILS_DB_PREFIX . 'blog_post_category');

            //  Recreate new ones
            if ($data['categories']) {

                $categoryData = array();

                foreach ($data['categories'] as $catId) {

                    $categoryData[] = array('post_id' => $id, 'category_id' => $catId);
                }

                $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_category', $categoryData);
            }
        }

        if (isset($data['tags'])) {

            //  Delete all tags
            $this->db->where('post_id', $id);
            $this->db->delete(NAILS_DB_PREFIX . 'blog_post_tag');

            //  Recreate new ones
            if ($data['tags']) {

                $tagData = array();

                foreach ($data['tags'] as $tagId) {

                    $tagData[] = array('post_id' => $id, 'tag_id' => $tagId);
                }

                $this->db->insert_batch(NAILS_DB_PREFIX . 'blog_post_tag', $tagData);
            }
        }

        // --------------------------------------------------------------------------

        //  Add associations, if any
        if (isset($data['associations']) && is_array($data['associations'])) {

            //  Fetch association config
            $this->load->model('blog/blog_model');
            $associations = $this->blog_model->get_associations();

            foreach ($data['associations'] as $index => $association) {

                if (!isset($associations[$index])) {

                    continue;
                }

                //  Clear old associations
                $this->db->where('post_id', $id);
                $this->db->delete($associations[$index]->target);

                //  Add new ones
                $associationData = array();

                foreach ($association as $associationId) {

                    $associationData[] = array('post_id' => $id, 'associated_id' => $associationId);
                }

                if ($associationData) {

                    $this->db->insert_batch($associations[$index]->target, $associationData);
                }
            }
        }

        // --------------------------------------------------------------------------

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all posts
     * @param int    $page           The page number of the results, if null then no pagination
     * @param int    $perPage        How many items per page of paginated results
     * @param mixed  $data           Any data to pass to _getcount_common()
     * @param bool   $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @param string $_caller        Internal flag to pass to _getcount_common(), contains the calling method
     * @return array
     **/
    public function get_all($page = null, $perPage = null, $data = null, $includeDeleted = false, $_caller = 'GET_ALL')
    {
        $posts = parent::get_all($page, $perPage, $data, $includeDeleted, $_caller);

        $this->load->model('blog/blog_model');
        $associations = $this->blog_model->get_associations();

        foreach ($posts as $post) {

            //  Fetch associated categories
            if (!empty($data['include_categories'])) {

                $this->load->model('blog/blog_category_model');

                $this->db->select('c.id,c.blog_id,c.slug,c.label');
                $this->db->join(NAILS_DB_PREFIX . 'blog_category c', 'c.id = pc.category_id');
                $this->db->where('pc.post_id', $post->id);
                $this->db->group_by('c.id');
                $this->db->order_by('c.label');
                $post->categories = $this->db->get(NAILS_DB_PREFIX . 'blog_post_category pc')->result();

                foreach ($post->categories as $c) {

                    $c->url = $this->blog_category_model->format_url($c->slug, $c->blog_id);
                }

            } else {

                $post->categories = array();
            }

            // --------------------------------------------------------------------------

            //  Fetch associated tags
            if (!empty($data['include_tags'])) {

                $this->load->model('blog/blog_tag_model');

                //  Fetch associated tags
                $this->db->select('t.id,t.blog_id,t.slug,t.label');
                $this->db->join(NAILS_DB_PREFIX . 'blog_tag t', 't.id = pt.tag_id');
                $this->db->where('pt.post_id', $post->id);
                $this->db->group_by('t.id');
                $this->db->order_by('t.label');
                $post->tags = $this->db->get(NAILS_DB_PREFIX . 'blog_post_tag pt')->result();

                foreach ($post->tags as $t) {

                    $t->url = $this->blog_tag_model->format_url($t->slug, $t->blog_id);
                }

            } else {

                $post->tags = array();
            }

            // --------------------------------------------------------------------------

            //  Fetch other associations
            if (!empty($data['include_associations']) && $associations) {

                foreach ($associations as $index => $assoc) {

                    $post->associations[$index] = $assoc;

                    //  Fetch the association data from the source, fail ungracefully - the dev should have this configured correctly.
                    $this->db->select('src.' . $assoc->source->id . ' id, src.' . $assoc->source->label . ' label');
                    $this->db->join($assoc->source->table . ' src', 'src.' . $assoc->source->id . '=target.associated_id', 'LEFT');
                    $this->db->where('target.post_id', $post->id);
                    $post->associations[$index]->current = $this->db->get($assoc->target . ' target')->result();
                }

            } else {

                $post->associations = array();
            }

            // --------------------------------------------------------------------------

            //  Fetch associated images
            if (!empty($data['include_gallery']) ) {

                $this->db->where('post_id', $post->id);
                $this->db->order_by('order');
                $post->gallery = $this->db->get(NAILS_DB_PREFIX . 'blog_post_image')->result();

            } else {

                $post->gallery = array();
            }
        }

        // --------------------------------------------------------------------------

        return $posts;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch a pst by it's ID
     * @param  int   $id The ID of the object to fetch
     * @param  mixed $data Any data to pass to _getcount_common()
     * @return stdClass
     **/
    public function get_by_id($id, $data = null)
    {
        $data = $this->_include_everything($data);
        return parent::get_by_id($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Fetch a post by it's slug
     * @param  int   $slug The slug of the object to fetch
     * @param  mixed $data Any data to pass to _getcount_common()
     * @return stdClass
     **/
    public function get_by_slug($id, $data = null)
    {
        $data = $this->_include_everything($data);
        return parent::get_by_slug($id, $data);
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
     * @param  mixed $data Any data to pass to _getcount_common()
     * @return stdClass
     **/
    public function get_by_id_or_slug($id, $data = null)
    {
        $data = $this->_include_everything($data);
        return parent::get_by_id($id, $data);
    }

    // --------------------------------------------------------------------------

    /**
     * Applies common conditionals
     *
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param string $data Data passed from the calling method
     * @param string $_caller The name of the calling method
     * @return void
     **/
    protected function _getcount_common($data = null, $_caller = null)
    {
        parent::_getcount_common($data, $_caller);

        // --------------------------------------------------------------------------

        $this->db->select('bp.id, bp.blog_id, b.label blog_label, bp.slug, bp.title, bp.image_id, bp.excerpt, bp.seo_title');
        $this->db->select('bp.seo_description, bp.seo_keywords, bp.is_published, bp.is_deleted, bp.created, bp.created_by, bp.modified, bp.modified_by, bp.published');

        $this->db->select('u.first_name, u.last_name, ue.email, u.profile_img, u.gender');

        $this->db->join(NAILS_DB_PREFIX . 'blog b', 'bp.blog_id = b.id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user u', 'bp.modified_by = u.id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.user_id = u.id AND ue.is_primary = 1', 'LEFT');

        // --------------------------------------------------------------------------

        if (!empty($data['include_body'])) {

            $this->db->select('bp.body');
        }

        // --------------------------------------------------------------------------

        if (!empty($data['search'])) {

            $this->db->or_like($this->_table_prefix . '.title', $data['search']);
            $this->db->or_like($this->_table_prefix . '.excerpt', $data['search']);
            $this->db->or_like($this->_table_prefix . '.body', $data['search']);
            $this->db->or_like($this->_table_prefix . '.seo_description', $data['search']);
            $this->db->or_like($this->_table_prefix . '.seo_keywords', $data['search']);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the data array to include everything
     *
     * This method is called by the get_by_*() methods and, if not already set,
     * will alter the $data array so that all the include_* parameters are set.
     *
     * @param string $data Data passed from the calling method
     * @return void
     **/
    protected function _include_everything($data)
    {
        if (is_null($data)) {

            $data = array();
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

        // --------------------------------------------------------------------------

        return $data;
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches latestposts
     * @param int   $limit The number of posts to return
     * @param mixed $data Any data to pass to _getcount_common()
     * @param bool  $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     **/
    public function get_latest($limit = 9, $data = null, $includeDeleted = false)
    {
        $this->db->limit($limit);
        $this->db->order_by('bp.published', 'DESC');
        return $this->get_all(null, null, $data, $includeDeleted, 'GET_LATEST');
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches posts published within a certain year and/or month
     * @param int $year The year to restrict the search to
     * @param int $month The month to restrict the search to
     * @param mixed $data Any data to pass to _getcount_common()
     * @param bool $includeDeleted If non-destructive delete is enabled then this flag allows you to include deleted items
     * @return array
     **/
    public function get_archive($year = null, $month = null, $data = null, $includeDeleted = false)
    {
        if ($year) {

            $this->db->where('YEAR(bp.published) = ', (int) $year);
        }

        // --------------------------------------------------------------------------

        if ($month) {

            $this->db->where('MONTH(bp.published) = ', (int) $month);
        }

        // --------------------------------------------------------------------------

        return $this->get_all(null, null, $data, $includeDeleted, 'GET_ARCHIVE');
    }

    // --------------------------------------------------------------------------

    /**
     * Gets posts which are in a particular category
     * @param  mixed   $categoryIdSlug The category's ID or slug
     * @param  int     $page           The page to render
     * @param  int     $perPage        The number of posts per page
     * @param  array   $data           Data to pass to _getcount_common()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     * @return array
     */
    public function get_with_category($categoryIdSlug, $page = null, $perPage = null, $data = null, $includeDeleted = false)
    {
        //  Join the blog_post_category table so we can WHERE on it.
        $this->db->join(NAILS_DB_PREFIX . 'blog_post_category bpc', 'bpc.post_id = bp.id');
        $this->db->join(NAILS_DB_PREFIX . 'blog_category bc', 'bc.id = bpc.category_id');

        //  Set the where
        if (is_null($data)) {

            $data = array('where' => array());
        }

        if (!isset($data['where'])) {

            $data['where'] = array();
        }

        if (is_numeric($categoryIdSlug)) {

            $data['where'][] = array('column' => 'bc.id', 'value' => (int) $categoryIdSlug);

        } else {

            $data['where'][] = array('column' => 'bc.slug', 'value' => $categoryIdSlug);
        }

        $this->db->group_by($this->_table_prefix . '.id');

        return $this->get_all($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of posts in a particular category
     * @param  mixed   $categoryIdSlug The category's ID or slug
     * @param  array   $data           Data to pass to _getcount_common()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     * @return int
     */
    public function count_with_category($categoryIdSlug, $data = null, $includeDeleted = false)
    {
        //  Join the blog_post_category table so we can WHERE on it.
        $this->db->join(NAILS_DB_PREFIX . 'blog_post_category bpc', 'bpc.post_id = bp.id');
        $this->db->join(NAILS_DB_PREFIX . 'blog_category bc', 'bc.id = bpc.category_id');

        //  Set the where
        if (is_null($data)) {

            $data = array('where' => array());
        }

        if (is_numeric($categoryIdSlug)) {

            $data['where'][] = array('column' => 'bc.id', 'value' => (int) $categoryIdSlug);

        } else {

            $data['where'][] = array('column' => 'bc.slug', 'value' => $categoryIdSlug);
        }

        return $this->count_all($data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Gets posts which are in a particular tag
     * @param  mixed   $tagIdSlug      The tag's ID or slug
     * @param  int     $page           The page to render
     * @param  int     $perPage        The number of posts per page
     * @param  array   $data           Data to pass to _getcount_common()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     * @return array
     */
    public function get_with_tag($tagIdSlug, $page = null, $perPage = null, $data = null, $includeDeleted = false)
    {
        //  Join the blog_post_tag table so we can WHERE on it.
        $this->db->join(NAILS_DB_PREFIX . 'blog_post_tag bpt', 'bpt.post_id = bp.id');
        $this->db->join(NAILS_DB_PREFIX . 'blog_tag bt', 'bt.id = bpt.tag_id');

        //  Set the where
        if (is_null($data)) {

            $data = array('where' => array());
        }

        if (is_numeric($tagIdSlug)) {

            $data['where'][] = array('column' => 'bt.id', 'value' => (int) $tagIdSlug);

        } else {

            $data['where'][] = array('column' => 'bt.slug', 'value' => $tagIdSlug);
        }

        $this->db->group_by($this->_table_prefix . '.id');

        return $this->get_all($page, $perPage, $data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of posts in a particular tag
     * @param  mixed   $tagIdSlug      The tag's ID or slug
     * @param  array   $data           Data to pass to _getcount_common()
     * @param  boolean $includeDeleted Whether to include deleted posts in the result
     * @return int
     */
    public function count_with_tag($tagIdSlug, $data = null, $includeDeleted = false)
    {
        //  Join the blog_post_category table so we can WHERE on it.
        $this->db->join(NAILS_DB_PREFIX . 'blog_post_tag bpt', 'bpt.post_id = bp.id');
        $this->db->join(NAILS_DB_PREFIX . 'blog_tag bt', 'bt.id = bpt.tag_id');

        //  Set the where
        if (is_null($data)) {

            $data = array('where' => array());
        }

        if (is_numeric($tagIdSlug)) {

            $data['where'][] = array('column' => 'bt.id', 'value' => (int) $tagIdSlug);

        } else {

            $data['where'][] = array('column' => 'bt.slug', 'value' => $tagIdSlug);
        }

        return $this->count_all($data, $includeDeleted);
    }

    // --------------------------------------------------------------------------

    /**
     * Get posts with a particular association
     * @param  int $associationIndex The association's index
     * @param  int $associatedId     The Id of the item to be associated with
     * @return array
     */
    public function get_with_association($associationIndex, $associatedId)
    {
        $this->config->load('blog/blog');

        $associations = $this->config->item('blog_post_associations');

        if (!isset($associations[$associationIndex])) {

            return array();
        }

        $this->db->select('post_id');
        $this->db->where('associated_id', $associatedId);
        $posts = $this->db->get($associations[$associationIndex]->target)->result();

        $ids = array();
        foreach ($posts as $post) {

            $ids[] = $post->post_id;
        }

        if (empty($ids)) {

            //  No IDs? No posts.
            return array();
        }

        $this->db->where_in($this->_table_prefix . '.id', $ids);

        return $this->get_all();
    }

    // --------------------------------------------------------------------------

    /**
     * Add a hit to a post
     * @param int    $id   The post's ID
     * @param array  $data Details about the hit
     */
    public function add_hit($id, $data = array())
    {
        if (!$id) {

            $this->_set_error('Post ID is required.');
            return false;
        }

        // --------------------------------------------------------------------------

        $hitData               = array();
        $hitData['post_id']    = $id;
        $hitData['user_id']    = empty($data['user_id']) ? null : $data['user_id'];
        $hitData['ip_address'] = $this->input->ip_address();
        $hitData['created']    = date('Y-m-d H:i:s');
        $hitData['referrer']   = empty($data['referrer']) ? null : prep_url(trim($data['referrer']));

        if ($hitData['user_id'] && $this->user_model->is_admin($hitData['user_id'])) {

            $this->_set_error('Administrators cannot affect the post\'s popularity.');
            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Registered a hit on this post in the past 5 minutes? Try to prevent abuse
         * of the popularity system.
         */

        $this->db->where('post_id', $hitData['post_id']);
        $this->db->where('user_id', $hitData['user_id']);
        $this->db->where('ip_address', $hitData['ip_address']);
        $this->db->where('created > "' . date('Y-m-d H:i:s', strtotime('-5 MINS')) . '"');

        if ($this->db->count_all_results(NAILS_DB_PREFIX . 'blog_post_hit')) {

            $this->_set_error('Hit timeout in effect.');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->db->set($hitData);

        if ($this->db->insert(NAILS_DB_PREFIX . 'blog_post_hit')) {

            return true;

        } else {

            $this->_set_error('Failed to add hit.');
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Format a posts's URL
     * @param  string $slug   The post's slug
     * @param  int    $blogId The blog's ID
     * @return string
     */
    public function format_url($slug, $blogId)
    {
        $this->load->model('blog/blog_model');
        return $this->blog_model->getBlogUrl($blogId) . $slug;
    }

    // --------------------------------------------------------------------------

    /**
     * Format a post object
     * @param  stdClass &$post The post Object to format
     * @return void
     */
    protected function _format_object(&$post)
    {
        parent::_format_object($post);

        // --------------------------------------------------------------------------

        //  Type casting
        $post->is_published = (bool) $post->is_published;
        $post->is_deleted   = (bool) $post->is_deleted;

        //  Generate URL
        $post->url = $this->format_url($post->slug, $post->blog_id);

        //  Blog
        $post->blog        = new stdClass();
        $post->blog->id    = (int) $post->blog_id;
        $post->blog->label = $post->blog_label;

        //  Author
        $post->author              = new stdClass();
        $post->author->id          = (int) $post->modified_by;
        $post->author->first_name  = $post->first_name;
        $post->author->last_name   = $post->last_name;
        $post->author->email       = $post->email;
        $post->author->profile_img = $post->profile_img;
        $post->author->gender      = $post->gender;

        unset($post->blog_id);
        unset($post->blog_label);
        unset($post->modified_by);
        unset($post->first_name);
        unset($post->last_name);
        unset($post->email);
        unset($post->profile_img);
        unset($post->gender);
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
