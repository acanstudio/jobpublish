SELECT `v`.`id`, `v`.`name`, `bc`.`id`, `bb`.`url`, `bb`.`created_at` FROM `el_sp_broadcast_classroom` AS `bc`, `el_sp_broadcast_back` AS `bb`, `el_sp_video` AS `v` WHERE `bc`.`id` = `bb`.`classroom_id` AND `bc`.`section_id` = `v`.`id`;

ALTER TABLE `el_mini_course_evaluation` ADD `point_study_day` SMALLINT(5) NOT NULL DEFAULT '0' COMMENT '指定学习天数' AFTER `sku_buy_time`;
ALTER TABLE `el_mini_evaluation_pool` ADD `day_range` VARCHAR(30) NOT NULL DEFAULT '' COMMENT '学习天数范围just 1-3天；like 4-10;often 11-20;habit 21-50' AFTER `evaluation`;
ALTER TABLE `el_mini_evaluation_pool` ADD `cancel_status` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '取消使用' AFTER `use_num`;

UPDATE `el_mini_evaluation_pool` SET `cancel_status` = 1 WHERE 1;
UPDATE `el_mini_evaluation_pool` AS `cp`, `el_mini_course_evaluation` AS `ce` SET `ce`.`point_study_day` = `cp`.`day_range` WHERE `cp`.`evaluation` = `ce`.`review_content`  AND `cp`.`day_range` > 0;
SELECT * FROM `el_mini_evaluation_pool` AS `cp`, `el_mini_course_evaluation` AS `ce` WHERE `cp`.`evaluation` = `ce`.`review_content`;


UPDATE  `el_mini_course` SET `market_info` = '限时优惠，福利大放送' WHERE `id` = 1;

INSERT INTO `el_dk_cock` (`id`, `data`, `remark`, `code`, `ext_data`) VALUES (NULL, 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/miniprogramImg/guideImg-min.png', '字典-示例', 'dict-demo', NULL);

UPDATE `el_sp_word_calligraphy` SET `category` = 'bossCursive' WHERE `word_id` >= 2542 AND `word_id` <= 2628 AND `category` = 'standardgood';

ALTER TABLE `el_mini_evaluation_pool` ADD `course_id` INT(11) NOT NULL DEFAULT '0' COMMENT '课程ID' AFTER `id`;
UPDATE `el_mini_evaluation_pool` SET `course_id` = 1 ;



UPDATE `el_mini_evaluation_user_pool` SET `evaluation_ids` = '', `evaluation_num` = 0 WHERE 1;
UPDATE `el_mini_evaluation_pool` SET `use_num` = 0 WHERE 1;



INSERT INTO `el_mini_evaluation_user_pool` (`course_id`, `uid`) VALUES

INSERT INTO `el_mini_evaluation_pool` (`course_id`, `evaluation`) VALUES  
