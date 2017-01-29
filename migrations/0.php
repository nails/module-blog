<?php

/**
 * Migration:   0
 * Started:     09/01/2015
 * Finalised:   09/01/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleBlog;

use Nails\Common\Console\Migrate\Base;

class Migration0 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `label` varchar(150) NOT NULL DEFAULT '',
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_category` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `blog_id` int(10) unsigned NOT NULL,
                `slug` varchar(150) NOT NULL DEFAULT '',
                `label` varchar(150) NOT NULL DEFAULT '',
                `description` text,
                `seo_title` varchar(150) DEFAULT NULL,
                `seo_description` varchar(300) DEFAULT NULL,
                `seo_keywords` varchar(150) DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(10) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `blog_id` (`blog_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_category_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_category_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_category_ibfk_3` FOREIGN KEY (`blog_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `blog_id` int(11) unsigned NOT NULL,
                `slug` varchar(150) NOT NULL DEFAULT '',
                `title` varchar(150) NOT NULL DEFAULT '',
                `excerpt` text NOT NULL,
                `body` longtext NOT NULL,
                `image_id` int(11) unsigned DEFAULT NULL,
                `seo_title` varchar(200) DEFAULT NULL,
                `seo_description` varchar(200) DEFAULT NULL,
                `seo_keywords` varchar(200) DEFAULT NULL,
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
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_ibfk_1` FOREIGN KEY (`image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_ibfk_2` FOREIGN KEY (`blog_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_ibfk_4` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_category` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `category_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `category_id` (`category_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_category_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_category` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_hit` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `user_id` int(11) unsigned DEFAULT NULL,
                `ip_address` varchar(39) DEFAULT NULL,
                `created` datetime NOT NULL,
                `referrer` varchar(300) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `user_id` (`user_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_hit_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_hit_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_image` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `image_id` int(11) unsigned DEFAULT NULL,
                `order` tinyint(1) unsigned NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `image_id` (`image_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_image_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_image_ibfk_2` FOREIGN KEY (`image_id`) REFERENCES `{{NAILS_DB_PREFIX}}cdn_object` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_post_tag` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) unsigned NOT NULL,
                `tag_id` int(11) unsigned NOT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `tag_id` (`tag_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_post` (`id`) ON DELETE CASCADE,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_post_tag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog_tag` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}blog_tag` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `blog_id` int(11) unsigned NOT NULL,
                `slug` varchar(150) NOT NULL DEFAULT '',
                `label` varchar(150) NOT NULL DEFAULT '',
                `description` text,
                `seo_title` varchar(150) DEFAULT NULL,
                `seo_description` varchar(300) DEFAULT NULL,
                `seo_keywords` varchar(150) DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                KEY `blog_id` (`blog_id`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_tag_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_tag_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}blog_tag_ibfk_3` FOREIGN KEY (`blog_id`) REFERENCES `{{NAILS_DB_PREFIX}}blog` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
