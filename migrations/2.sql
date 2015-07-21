ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `commentsEnabled` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '1'  AFTER `seo_keywords`;
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `commentsExpire` DATETIME  NULL  AFTER `commentsEnabled`;
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `type` ENUM('TEXT','AUDIO','VIDEO','PHOTO')  NOT NULL  DEFAULT 'PHOTO'  AFTER `slug`;
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `video_url` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `image_id`;
ALTER TABLE `{{NAILS_DB_PREFIX}}blog_post` ADD `audio_url` VARCHAR(255)  NULL  DEFAULT NULL  AFTER `video_url`;
UPDATE `{{NAILS_DB_PREFIX}}blog_post` SET type = 'TEXT' WHERE image_id IS NULL;
