<?php

/**
 * Migration:   2
 * Started:     21/07/2015
 * Finalised:   21/07/2015
 */

namespace Nails\Database\Migration\Nails\ModuleBlog;

use Nails\Common\Console\Migrate\Base;

class Migration2 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `commentsEnabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `seo_keywords`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `commentsExpire` DATETIME  NULL  AFTER `commentsEnabled`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `type` ENUM('TEXT','AUDIO','VIDEO','PHOTO')  NOT NULL  DEFAULT 'PHOTO'  AFTER `slug`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `video_url` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `image_id`;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `audio_url` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `video_url`;");
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}blog_post` SET type = 'TEXT' WHERE image_id IS NULL;");
    }
}
