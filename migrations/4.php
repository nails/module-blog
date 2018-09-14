<?php

/**
 * Migration:   4
 * Started:     29/09/2015
 * Finalised:   29/09/2015
 */

namespace Nails\Database\Migration\Nails\ModuleBlog;

use Nails\Common\Console\Migrate\Base;

class Migration4 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` CHANGE `commentsEnabled` `comments_enabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` CHANGE `commentsExpire` `comments_expire` DATETIME  NULL  DEFAULT NULL;");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post_preview` CHANGE `commentsEnabled` `comments_enabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1';");
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post_preview` CHANGE `commentsExpire` `comments_expire` DATETIME  NULL  DEFAULT NULL;");
    }
}
