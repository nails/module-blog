<?php

/**
 * The model provides some widget functionality to the blog
 *
 * @package     Nails
 * @subpackage  module-blog
 * @category    Model
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Blog_widget_model extends NAILS_Model
{
    /**
     * Returns an array of the latest blog posts
     * @param  integer $blogId The ID of the blog to get posts from
     * @param  integer $limit  The maximum number of posts to return
     * @return array
     */
    public function latestPosts($blogId, $limit = 5)
    {
        $this->db->select('id,blog_id,slug,title,published');
        $this->db->where('is_published', true);
        $this->db->where('published <=', 'NOW()', false);
        $this->db->where('is_deleted', false);
        $this->db->where('blog_id', $blogId);
        $this->db->limit($limit);
        $this->db->order_by('published', 'DESC');
        $posts = $this->db->get(NAILS_DB_PREFIX . 'blog_post')->result();

        if (!$this->load->isModelLoaded('blog_post_model')) {

            $this->load->model('blog/blog_post_model');
        }

        foreach ($posts as $post) {

            $post->url = $this->blog_post_model->formatUrl($post->slug, $post->blog_id);
        }

        return $posts;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of the most popular blog posts
     * @param  integer $blogId The ID of the blog to get posts from
     * @param  integer $limit  The maximum number of posts to return
     * @return array
     */
    public function popularPosts($blogId, $limit = 5)
    {
        $this->db->select('bp.id,bp.blog_id,bp.slug,bp.title,bp.published,COUNT(bph.id) hits');
        $this->db->join(NAILS_DB_PREFIX . 'blog_post bp', 'bp.id = bph.post_id');
        $this->db->where('bp.is_published', true);
        $this->db->where('bp.published <=', 'NOW()', false);
        $this->db->where('bp.is_deleted', false);
        $this->db->where('blog_id', $blogId);
        $this->db->group_by('bp.id');
        $this->db->order_by('hits', 'DESC');
        $this->db->order_by('bp.published', 'DESC');
        $this->db->limit($limit);

        $posts = $this->db->get(NAILS_DB_PREFIX . 'blog_post_hit bph')->result();

        if (!$this->load->isModelLoaded('blog_post_model')) {

            $this->load->model('blog/blog_post_model');
        }

        foreach ($posts as $post) {

            $post->url = $this->blog_post_model->formatUrl($post->slug, $post->blog_id);
        }

        return $posts;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of a blog's categories
     * @param  integer $blogId        The ID of the blog to get categories from
     * @param  boolean $includeCount  Whether to include the post count of each category
     * @param  boolean $onlyPopulated Whether to remove categories which don't have any posts in them
     * @return array
     */
    public function categories($blogId, $includeCount = true, $onlyPopulated = true)
    {
        $this->db->select('c.id,c.blog_id,c.slug,c.label');

        if ($includeCount) {

            $sql  = '(SELECT COUNT(DISTINCT bpc.post_id) FROM ' . NAILS_DB_PREFIX . 'blog_post_category bpc JOIN ';
            $sql .= NAILS_DB_PREFIX . 'blog_post bp ON bpc.post_id = bp.id WHERE bpc.category_id = c.id AND ';
            $sql .= 'bp.is_published = 1 AND bp.is_deleted = 0 AND bp.published <= NOW()) post_count';

            $this->db->select($sql);
        }

        if ($onlyPopulated) {

            $this->db->having('post_count > ', 0);
        }

        $this->db->where('c.blog_id', $blogId);
        $this->db->order_by('c.label');

        $categories = $this->db->get(NAILS_DB_PREFIX . 'blog_category c')->result();

        $this->load->model('blog/blog_category_model');

        foreach ($categories as $category) {

            $category->url = $this->blog_category_model->formatUrl($category->slug, $category->blog_id);
        }

        return $categories;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of a blog's tags
     * @param  integer $blogId        The ID of the blog to get tags from
     * @param  boolean $includeCount  Whether to include the post count of each tag
     * @param  boolean $onlyPopulated Whether to remove tags which don't have any posts in them
     * @return array
     */
    public function tags($blogId, $includeCount = true, $onlyPopulated = true)
    {
        $this->db->select('t.id,t.blog_id,t.slug,t.label');

        if ($includeCount) {

            $sql  = '(SELECT COUNT(DISTINCT bpt.post_id) FROM ' . NAILS_DB_PREFIX . 'blog_post_tag bpt JOIN ';
            $sql .= NAILS_DB_PREFIX . 'blog_post bp ON bpt.post_id = bp.id WHERE bpt.tag_id = t.id AND ';
            $sql .= 'bp.is_published = 1 AND bp.is_deleted = 0 AND bp.published <= NOW()) post_count';

            $this->db->select($sql);
        }

        if ($onlyPopulated) {

            $this->db->having('post_count > ', 0);
        }

        $this->db->where('t.blog_id', $blogId);
        $this->db->order_by('t.label');

        $tags = $this->db->get(NAILS_DB_PREFIX . 'blog_tag t')->result();

        $this->load->model('blog/blog_tag_model');

        foreach ($tags as $tag) {

            $tag->url = $this->blog_tag_model->formatUrl($tag->slug, $tag->blog_id);
        }

        return $tags;
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

if (!defined('NAILS_ALLOW_EXTENSION_BLOG_WIDGET_MODEL')) {

    class Blog_widget_model extends NAILS_Blog_widget_model
    {
    }
}
