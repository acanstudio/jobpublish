UPDATE  `el_mini_course` SET `market_info` = '限时优惠，福利大放送' WHERE `id` = 1;

INSERT INTO `el_dk_cock` (`id`, `data`, `remark`, `code`, `ext_data`) VALUES (NULL, 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/miniprogramImg/guideImg-min.png', '字典-示例', 'dict-demo', NULL);

INSERT INTO `el_dk_cock` (`id`, `data`, `remark`, `code`, `ext_data`) VALUES (NULL, 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/courseware/pc/2023/07/05/g6qcoqlop8.png|/pages/courseMarketing/courseMarketing?id=112&channel=dictpianpangkaishu', '字典-部首-楷书', 'dict-component-bossdict', NULL);
INSERT INTO `el_dk_cock` (`id`, `data`, `remark`, `code`, `ext_data`) VALUES (NULL, 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/courseware/pc/2023/07/05/g6qcoqlop8.png|/pages/courseMarketing/courseMarketing?id=112&channel=dictkongbikaishu', '字典-控笔-楷书', 'dict-penControl-bossdict', NULL);
INSERT INTO `el_dk_cock` (`id`, `data`, `remark`, `code`, `ext_data`) VALUES (NULL, 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/courseware/pc/2023/07/05/g6qcoqlop8.png|/pages/courseMarketing/courseMarketing?id=112&channel=dictbihuakaishu', '字典-笔画-楷书', 'dict-stroke-bossdict', NULL);



UPDATE `el_sp_word_calligraphy` SET `category` = 'bossCursive' WHERE `word_id` >= 2542 AND `word_id` <= 2628 AND `category` = 'standardgood';



CREATE TABLE `el_mini_dict_record` (
      `id` int(11) NOT NULL,
      `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户UID',
      `word_id` int(11) NOT NULL DEFAULT '0' COMMENT '单字ID',
      `word_type` varchar(30) DEFAULT '' COMMENT '单字类型',
      `calligraphy` varchar(30) DEFAULT '' COMMENT '书体',
      `view_num` smallint(5) DEFAULT '0' COMMENT '浏览数',
      `status` int(11) DEFAULT '0' COMMENT '状态',
      `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
      `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新日期'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户同步单字';
ALTER TABLE `el_mini_dict_record`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`uid`) USING BTREE;
ALTER TABLE `el_mini_dict_record`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `el_mini_dict_lack` (
      `id` int(11) NOT NULL,
      `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户UID',
      `word` varchar(30) DEFAULT '' COMMENT '单字类型',
      `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='资源缺失字库';
ALTER TABLE `el_mini_dict_lack`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user` (`uid`) USING BTREE;
ALTER TABLE `el_mini_dict_lack`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;


ALTER TABLE `el_mini_evaluation_pool` ADD `course_id` INT(11) NOT NULL DEFAULT '0' COMMENT '课程ID' AFTER `id`;
UPDATE `el_mini_evaluation_pool` SET `course_id` = 1 ;



UPDATE `el_mini_evaluation_user_pool` SET `evaluation_ids` = '', `evaluation_num` = 0 WHERE 1;
UPDATE `el_mini_evaluation_pool` SET `use_num` = 0 WHERE 1;



INSERT INTO `el_mini_evaluation_user_pool` (`course_id`, `uid`) VALUES
(3, 2933083),(3, 5908132),(3, 5905652),(3, 1208964),(3, 5349294),(3, 1382090),(3, 5905214),(3, 2008913),(3, 2664196),(3, 2721881),(3, 5837103),(3, 5905493),(3, 4958484),(3, 5857411),(3, 3172472),(3, 3567251),(3, 5905221),(3, 5908127),(3, 5908111),(3, 662646),(3, 1768705),(3, 5906154),(3, 5438657),(3, 5905537),(3, 5905484),(3, 5905233),(3, 1920506),(3, 5908100),(3, 5908134),(3, 56873),(3, 5908293),(3, 5905295),(3, 5905294),(3, 119032),(3, 5908096),(3, 5908321),(3, 3356119),(3, 5417473),(3, 5175371),(3, 1218764),(3, 5882516),(3, 5240890),(3, 4995200),(3, 5311952),(3, 5573441),(3, 4764308),(3, 6843),(3, 3925456),(3, 4308966),(3, 3491580),(3, 2338893),(3, 5321469),(3, 3249132),(3, 4869436),(3, 5905292),(3, 5905290),(3, 523885),(3, 4013228),(3, 239362),(3, 6830),(3, 5727311),

INSERT INTO `el_mini_evaluation_pool` (`course_id`, `evaluation`) VALUES  
(3, '对于我这种初学者来说非常友好，可以坚持学完'),
(3, '讲解得简单易懂，加上平时多加练习，进步还挺大的'),
(3, '学习这个楷书速成课，我发现写字还挺轻松的。'),
(3, '像是发现了一个知识宝库，示范讲的清晰易懂'),
(3, '强烈推荐任何对书法感兴趣的人看一下这门课。老师讲的很棒'),
(3, '这门课程价格实惠，物超所值。'),
(3, '课程材料一流'),
(3, '确实帮助我提高'),
(3, '这门课节奏很快，很容易上手。'),
(3, '非常流畅，没有任何卡顿或者延迟。'),
(3, '考虑到了学生的需求，例字非常实际'),
(3, '课程的制作非常用心'),
(3, '专业水准非常高'),
(3, '很多细节，我之前没注意的，视频里都讲到了'),
(3, '这门课非常全面，涵盖了楷书的基础知识和技巧，以及高级的技法和应用。'),
(3, '画面清晰，声音清晰。'),
(3, '讲解非常生动，让人容易理解。'),
(3, '课程的内容非常丰富，包括了楷书的基本知识和技巧。'),
(3, '市面上居然还有这么价美物廉的课程'),
(3, '虽然很便宜，但效果却很棒'),
(3, '这门楷书课价格亲民，效果出奇地好！'),
(3, '这门楷书课的价格真的是便宜到让人惊喜啊'),
(3, '性价比超高，真的很值得一学！'),
(3, '本来以为字帖没什么用，但是发现作用很大，看完边学边练，提升很大'),
(3, '赠送的字帖非常具有吸引力，有很大的提高'),
(3, '本来只想上来看看的，看了试看课，果断买了，我从来学不下去的人，居然看下去了，非常适合我这种新手，希望我能坚持下去'),
(3, '很喜欢老师的课程，老师及时细致的讲解很适合新手，和大家一起练字，让我更有动力，整体来说真的很不错！'),
(3, '看了试看课报名的，后面的内容没让我失望，很系统科学，把笔画、结构都讲的很清楚。适合小白哦'),
(3, '和我一样想练字的小白，推荐报名，都是干货讲的很清楚'),
(3, '说练字八百年了，这次真的下定决心好好练字，再也不能让别人笑话自己了'),
(3, '这个课程真的很好，简单易懂，讲解细致，学起来完全没有压力'),
(3, '给小孩买的，这时候就应该好好练习练习字体了 这个太好用 太实惠了 大人小孩可以一起学 ，写好中国字 这个就行了'),
(3, '不得不说这个课程对我帮助还挺大的，成人没人监督就有惰性，自己难以坚持练习，并且身边也没有朋友和我一起练，很枯燥，后来，买字帖了解到六品堂练字小程序，报名了这节课，学习了之后慢慢的让我有了自信，我一定要继续加油，坚持就是胜利'),
(3, '一直想学练字，对写字好看的人有一种天然的好感，但是自己买字帖练了一年多还是没什么效果，在朋友的推荐下买了这个课，上课的第一天就有种豁然开朗的感觉！自己之前真是白浪费了那么长时间，接下来就要继续好好听课，然后手上多下功夫！写一手好字指日可待啦'),
(3, '课程设置合理，层层深入，详略得当，讲解清楚，只要跟着认真学，就能有收获！收获非常大。特别是一些理论知识，自学是完全学不到的'),
(3, '视频很齐全，可以利用零碎时间学习，时间设置的刚好'),
(3, '我一直很相信李老师的课程，这次的课程的非常全面，很完整的一套逻辑'),
(3, '老师课程讲的非常的棒，讲解非常的详细，一笔一划的跟着练习，对自己有了很大的提升，很不错'),
(3, '非常相信六品堂这个品牌，我也没看试看课，直接盲买的，想不到效果这么好哈哈推荐大家'),
(3, '老师讲解简单明了， 写字结构技巧讲的透彻，写字这方面自己多多练习就可以了');
