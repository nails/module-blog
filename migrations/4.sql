ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` CHANGE `commentsEnabled` `comments_enabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1';
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` CHANGE `commentsExpire` `comments_expire` DATETIME  NULL  DEFAULT NULL;
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post_preview` CHANGE `commentsEnabled` `comments_enabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1';
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post_preview` CHANGE `commentsExpire` `comments_expire` DATETIME  NULL  DEFAULT NULL;
