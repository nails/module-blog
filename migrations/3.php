<?php

/**
 * Migration:   3
 * Started:     25/07/2015
 * Finalised:   25/07/2015
 */

namespace Nails\Database\Migration\Nails\ModuleBlog;

use Nails\Common\Console\Migrate\Base;

class Migration3 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_preview` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `blog_id` int(11) unsigned NOT NULL,
                `slug` varchar(150) NOT NULL DEFAULT '',
                `type` enum('TEXT','AUDIO','VIDEO','PHOTO') NOT NULL DEFAULT 'PHOTO',
                `title` varchar(150) NOT NULL DEFAULT '',
                `excerpt` text NOT NULL,
                `body` longtext NOT NULL,
                `image_id` int(11) unsigned DEFAULT NULL,
                `video_url` varchar(255) DEFAULT NULL,
                `audio_url` varchar(255) DEFAULT NULL,
                `seo_title` varchar(200) DEFAULT NULL,
                `seo_description` varchar(200) DEFAULT NULL,
                `seo_keywords` varchar(200) DEFAULT NULL,
                `commentsEnabled` tinyint(1) unsigned NOT NULL DEFAULT '1',
                `commentsExpire` datetime DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                `is_published` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `is_deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
                `published` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `image_id` (`image_id`),
                KEY `blog_id` (`blog_id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `cdn_object` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_ibfk_2` FOREIGN KEY (`blog_id`) REFERENCES `blog` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_preview_category` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `category_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `category_id` (`category_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_category_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post_preview` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `blog_category` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_preview_image` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `image_id` int(11) unsigned DEFAULT NULL,
                `order` tinyint(1) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `image_id` (`image_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_image_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post_preview` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_image_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `cdn_object` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_preview_tag` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `tag_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `tag_id` (`tag_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post_preview` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_preview_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `blog_tag` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
