
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


CREATE TABLE `el_dk_huati_category` (
      `id` int(11) NOT NULL COMMENT '主键ID',
      `huati_id` int(11) NOT NULL DEFAULT '0' COMMENT '话题ID',
      `sort_id` int(11) DEFAULT '10' COMMENT '排序ID',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资料分类表' ROW_FORMAT=DYNAMIC;
ALTER TABLE `el_dk_huati_category`
  ADD PRIMARY KEY (`id`) USING BTREE;
ALTER TABLE `el_dk_huati_category`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID';


SELECT * FROM `el_mini_evaluation_pool` WHERE `evaluation` IN (
'上完课才回来评价，一直想把字写好，也买过很多字帖临摹（就是那种一层薄纸的字帖），但是自己写的时候又回到了老样子。同事推荐的，抱着试一试的想法购买了课程。老师讲解清楚，已经坚持了一个月，每天练一页已经成为了一种习惯。';
'我报了这个班，觉得真是捡到宝了。老师讲课很细致，特别是会提醒写字时容易犯的错误，这对新手真是太友好了，而且不光教授正确的写法，还会说明为什么要这么写，讲的很透彻。另外，老师还会介绍结构和笔画的规律，怎么写才规范、讲究。不仅仅是照着字帖，也会教其它的写法。现在才跟了一个月，就感觉收获特别大';
'不得不说这个课程对我帮助还挺大的，成人没人监督就有惰性，自己难以坚持练习，并且身边也没有朋友和我一起练，很枯燥，后来，买字帖了解到六品堂练字小程序，报名了这节课，学习了之后慢慢的让我有了自信，我一定要继续加油，坚持就是胜利';
'一直想学练字，对写字好看的人有一种天然的好感，但是自己买字帖练了一年多还是没什么效果，在朋友的推荐下买了这个课，上课的第一天就有种豁然开朗的感觉！自己之前真是白浪费了那么长时间，接下来就要继续好好听课，然后手上多下功夫！写一手好字指日可待啦';
'连续看完一整节课，一点不卡顿，画面声音清晰，内容完整，非常值得，比线下上课方便多了，随时有时间随时上课，可以一节课学完也可以分段学，可以反复回放，不浪费时间，也不用安排固定时间，真的很适合业余学习，强烈建议购买！');

INSERT INTO `el_mini_evaluation_pool` ( `evaluation`) VALUES  
('上完课才回来评价，一直想把字写好，也买过很多字帖临摹，直到同事推荐我试试这个课。老师讲解清楚，已经坚持了一个月，每天练一页'),
('真是捡到宝了！老师讲课很细致，会提醒写字时容易犯的错误，这对我这种新手真是太友好了，而且还会说明为什么要这么写，已经跟学了一个月，就感觉收获特别大'),
('身边没人和我一起练，想放弃，后来买字帖了解到六品堂，就报了这课，学了以后真对我帮助挺大的，能从基础开始每天看一节练一节，继续坚持呀！'),
('对写字好看的人有一种天然好感，但自己买字帖练了一年多了没啥效果。朋友推荐去听这个课，第一天就豁然开朗了！真是白浪费那么长时间，好好听课，多下功夫！'),
('连续看完一整节课，一点不卡，画面声音还挺清晰，适合我这种业余学习'),




UPDATE `el_mini_evaluation_user_pool` SET `evaluation_ids` = '', `evaluation_num` = 0 WHERE 1;
UPDATE `el_mini_evaluation_pool` SET `use_num` = 0 WHERE 1;



INSERT INTO `el_mini_evaluation_pool` (`evaluation`)
SELECT `subcontent` FROM `el_dianpin` GROUP BY `uid`, `subcontent` LIMIT 100;

INSERT INTO `el_mini_evaluation_user_pool` (`uid`, `course_id`)
SELECT `uid`, 1 FROM `el_dianpin` GROUP BY `uid` LIMIT 100;


CREATE TABLE `el_mini_evaluation_user_pool` (
      `id` smallint(5) UNSIGNED NOT NULL COMMENT 'ID',
      `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户UID',
      `course_id` smallint(5) NOT NULL DEFAULT '0' COMMENT '课程ID',
      `evaluation_ids` varchar(5000) NOT NULL DEFAULT '' COMMENT '评论ID',
      `evaluation_num` smallint(5) NOT NULL DEFAULT '0' COMMENT '已评论数',
      `status` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='推荐位' ROW_FORMAT=COMPACT;
ALTER TABLE `el_mini_evaluation_user_pool`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `el_mini_evaluation_user_pool`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ID';


INSERT INTO `el_mini_evaluation_user_pool` (`uid`) VALUES
(6786),(447302),(101076),(6695),(524674),(701799),(629208),(145),(116889),(6837),(503294),(263606),(201515),(372413),(59187),(418342),(364585),(130850),(579783),(433007),(615475),(119032),(700429),(6810),(10028),(41422),(47440),(514581),(615528),(186002),(364552),(700384),(700383),(654152),(667339),(700356),(105123),(700353),(700343),(637430),(6808),(8495),(688788),(186055),(12),(700287),(700275),(345132),(69074),(569369),(700258),(683785),(669129),(363266),(6813),(381438),(6811),(1181847),(1181848),(1181849),(1181850),(1181851),(1181852),(1181853),(1181854),(1181855),(1181856),(1181857),(1181858),(1181859),(1181860),(1181861),(1181862),(1181863),(1181864),(1181865),(1181866),(1181867),(1181868),(1181869),(1181870),(1181871),(1181872),(1181873),(1181874),(1181875),(1181876),(1181877),(1181878),(1181879),(1181880),(1181881),(1181882),(1181883),(1181884),(1181885),(1181887),(1181888),(1181889),(1181890),(1181891),(1181892),(1181893),(1181894),(1181895),(1181896),(1181897),(1181898),(1181899),(1181900),(1181901),(1181902),(1181903),(1181904),(1181905),(1181906),(1181907),(1181908),(1181909),(1181910),(1181911),(1181912),(1181913),(1181914),(1181915),(1181916),(1181917),(1181918),(1181919),(1181920),(1181921),(1181922),(1181923),(1181924),(1181925),(1181926),(1181927),(1181928),(1181929),(1181930),(1181931),(1181932),(1181933),(1181934),(1181935),(1181936),(1181937),(1181938),(1181939),(1181940),(1181941),(1181942),(1181943),(1181944),(1181945),(1181946),(1181947),(1181948),(1181949),(1181950),(1181951),(1181952),(1181953),(1181954),(1181955),(1181956),(1181957),(1181958),(1181959),(1181960),(1181961),(1181962),(1181963),(1181964),(1181965),(1181966),(1181967),(1181968),(1181969),(1181970),(1181971),(1181972),(1181973),(1181974),(1181975),(1181976),(1181977),(1181978),(1181979),(1181980),(1181981),(1181982),(1181983),(1181984),(1181985),(1181986),(1181987),(1181988),(1181989),(1181990),(1181991),(1181992),(1181993),(1181994),(1181995),(1181996),(1181997),(1181998),(1181999),(1182000),(1182001),(1182002),(1182003),(1182004),(1182005),(1182006),(1182007),(1182008),(1182009),(1182010),(1182011),(1182012),(1182013),(1182014),(1182015),(1182016),(1182017),(1182018),(1182019),(1182020),(1182021),(1182022),(1182023),(1182024),(1182025),(1182026),(1182027),(1182028),(1182029),(1182030),(1182031),(1182032),(1182033),(1182034),(1182035),(1182036),(1182037),(1182038),(1182039),(1182040),(1182041),(1182042),(1182043),(1182044),(1182045),(1182046),(1182047),(1182048),(1182049),(1182050),(1182051),(1182052),(1182053),(1182054),(1182055),(1182056),(1182057),(1182058),(1182059),(1182060),(1182061),(1182062),(2114733),(5822891),(2805864),(5822828),(3356119),(3665691),(2815978),(3681256),(4093777),(5475936),(5282227),(2667716),(3697653),(5814030),(5699599),(5734327),(2888981),(4770364),(4203902),(4221659),(5221810),(2514723),(4444454),(1909223),(5417473),(4770095),(5269733),(5762942),(4864166),(5514329),(4791450),(5272880),(3562691)



INSERT INTO `el_mini_evaluation_pool` ( `evaluation`) VALUES  
('这门硬笔书法课程非常实用，让我从零基础开始学习，现在已经能够写的字比之前好多了'),
('老师的教学非常细致，每个字体都有详细的讲解，让我能够更好地掌握每一种字体的特点和技巧。'),
('对我的生活和工作都有很大的帮助。'),
('练字的过程让我感到非常充实和满足。'),
('从一个小小的笔画开始，让我一步步逐渐掌握了书法的技巧和精髓。'),
('老师的教学方法非常实用，让我能够快速掌握每一种字体的写法和技巧，非常感谢老师的辛勤付出。'),
('练习让我非常享受这种写字的过程。'),
('老师的教学非常细致，每个字体都有详细的讲解，让我能够更好地掌握每一种字体的特点和技巧。'),
('学习硬笔书法让我不仅仅学会了书法，更让我懂得了如何沉下心来，专注于一件事情并不断努力，这对我的生活和工作都有很大的帮助。'),
('老师的教学风格非常亲切和耐心，让我感到非常温馨和舒适。'),
('感到自己在不断进步，每一次写字都比上一次更加流畅和自如了。'),
('这个课程的教学方法非常实用'),
('老师的教学过程非常严谨和细致'),
('经过这段时间的学习，让我学会了如何运用不同的笔画和字体来表达自己的情感和思想'),
('教学方法非常实用和有效，让我能够更好地掌握书法技巧'),
('这门课程内容很充实，非常实用。'),
('这门课程帮助我提高了很多技能和知识。'),
('课程设置合理，老师讲解清晰易懂，让人很容易理解。'),
('老师讲解的很容易理解和接受。'),
('课程内容涵盖了很多方面，非常全面，老师授课很有条理，让人很容易跟着走。'),
('配套教材收到了，课程作业量适中，每节课学习完之后才去练习，不会过于拖沓。'),
('课程每节课时间节奏很好，不会让人感到无聊。'),
('内容很有针对性，非常适合我的需求。'),
('这门课程的课件设计非常棒，让我更好地理解和记忆课程内容，确实能够提高我的学习效果。'),
('从笔画笔顺和结构方面详细讲解了汉字的书写方法，很适合我这种初学者，坚持每天练习，效果还是比较明显的，个人感觉汉字书写更端正了，笔画的书写也明朗了一些，这是我能发现的自己的进步吧'),
('和我一样想练字的小白，推荐报名，都是干货讲的很清楚'),
('说练字八百年了，这次真的下定决心好好练字，再也不能让别人笑话自己了'),
('上次被同事笑话了，然后买了字帖，机缘巧合又看到了这门课，报名了，现在同事都说我怎么写的这么好'),
('大家一定要趁早买，当时犹豫了一下就涨价了，不过还好，主要物有所值。'),
('李老师的课录的很棒，逐字分析，清晰易懂，小白可以学到很多'),
('找个老师指导比自己闷头练好，能看出好多没写好的地方才练了20天，可能我基础太差了吧，进步很明显，练字以年为单位，慢慢来吧'),
('很适合小白学习，读书的时候没认真学，现在工作了有时间提升自己就抽空买了这个课程'),
('上完课才回来评价，一直想把字写好，也买过很多字帖临摹（就是那种一层薄纸的字帖），但是自己写的时候又回到了老样子。同事推荐的，抱着试一试的想法购买了课程。老师讲解清楚，已经坚持了一个月，每天练一页已经成为了一种习惯。'),


('报课之前的犹犹豫豫，报课之后的意想不到；我从零基础学员，到报课后的练习，个人感觉进步蛮大的'),
('我报了这个班，觉得真是捡到宝了。老师讲课很细致，特别是会提醒写字时容易犯的错误，这对新手真是太友好了，而且不光教授正确的写法，还会说明为什么要这么写，讲的很透彻。另外，老师还会介绍结构和笔画的规律，怎么写才规范、讲究。不仅仅是照着字帖，也会教其它的写法。现在才跟了一个月，就感觉收获特别大'),
('练了一个月，有很大进步'),
('课程很满意，老师很专业，从零开始讲解，非常适合自己在家自学练习，边学边练，非常的方便'),
('感觉非常好，老师水平很高而且很认真，课程安排也很合理，跟着练会有很大进步的， 墙推！！！'),
('感觉自己进步挺大的'),
('这个课程非常好，从基础入手，非常扎实，特别是特别是把一些基本笔画讲解的非常清楚，因此通过这个课程的学习呢，可以进入一个非常正规的硬笔书法学习道路，学习可以在未来有一个非常大的进步空间'),
('本来买来给孩子学习的，现在平时有时间我也跟着孩子一起学习，真的很不错，现在考试都有卷面分，字写的漂亮真的很重要'),
('着手练了一些时间，练字效果很不错，课程通俗易懂，学习课程安排的很合理，受益匪浅'),
('非常不错的课程，我是之前没有想过要练字的，由于平时比较闲想找点事，突然喜欢上了练字的感觉，很好的课程'),
('课程非常齐全，又高清，从零开始，一笔一划都讲解非常详细，太好了，自己随便练习了一点，感觉还是不错'),
('孩子刚开始学习写字，学习课程很有用，值得推荐，讲解很细致'),
('给小孩买的，这时候就应该好好练习练习字体了 这个太好用 太实惠了 大人小孩可以一起学'),
('不得不说这个课程对我帮助还挺大的，成人没人监督就有惰性，自己难以坚持练习，并且身边也没有朋友和我一起练，很枯燥，后来，买字帖了解到六品堂练字小程序，报名了这节课，学习了之后慢慢的让我有了自信，我一定要继续加油，坚持就是胜利'),
('一直想学练字，对写字好看的人有一种天然的好感，但是自己买字帖练了一年多还是没什么效果，在朋友的推荐下买了这个课，上课的第一天就有种豁然开朗的感觉！自己之前真是白浪费了那么长时间，接下来就要继续好好听课，然后手上多下功夫！写一手好字指日可待啦'),
('新课首发时候买的课，真的好划算，快200节课了，市面上难道不到上千吗，我觉得自己是赚到了，赶紧买赶紧买'),
('说真的，真正能坚持下去的动力就是看到前后对比字的差别，真的进步好大，课程真的牛'),
('不错，在家就能自学硬笔书法课程，好好练字'),
('再也不用羡慕别人的好字了，每天拿出半小时练字，跟着李老师的课程，反复学习，不出一个星期，肯定进步的'),
('拉着家属入坑，家属学了一月进步不小，练字练的上瘾哈哈哈~'),
('老师讲的很认真，跟着练了几天，感觉自己的硬笔书法有了一点提升，好评。'),
('给孩子买的，他们班其他小孩家长线下花了五六千报的班都没我家孩子练的好，这次又续了课提升一下孩子的书写速度和篇幅书写的能力！老师教的真的好！'),
('线上课就是方便，每天睡前练练字，不知不觉就变好了'),
('一节课只要几毛钱的课，打着灯笼都难找'),
('客观评价一下，网上课程参差不齐，真心觉得这个不错'),
('练字一个月，感触颇深，方法比努力重要，回想自己当初描字帖两年，不如这一个月进步大！'),
('很好，期待后期的改变'),
('课很不错，感觉少走了很多弯路，对书法也更有兴趣了，老师经常说练字会上瘾的，现在有点感觉了'),



('开始报这个班是有点持怀疑的态度，觉得很可能起不到任何效果，白花钱。但是这一个月下来觉得还是很有进步的'),
('好评，我觉得还不错'),
('比自己练有用。之前朋友发我的这个课，半信半疑的买了，然后天天催我练字，现在我觉得是有明显效果，字写的大气了，控笔也明显稳了。现在回过头想想，如果从小就好好写，也不至于现在还要报个练字班学写字'),
('变化非常大，我每天至少练习是半小时，现在虽然我笔画还不算很好，但是字的结构我感觉比之前提升特别大。'),
('小学开始我的字就不好看，属于爱怎么写就怎么写。最近这两年当老师，当着学生面写字，感觉字好辣眼。今年开始励志练字，希望学生们看到我的字的时候都可以由衷佩服。'),
('感觉自己进步很大'),
('通过练字提高了很多，真不错'),
('晚上才有时间看课，随报随学很适合我这样的，目前整体体验还挺不错的'),
('楷书讲的很好，但还是想练行楷，希望行楷早点上'),
('课程录制好的，直接打开可以看，适合新手学习，知识点都讲透了'),
('空闲时间随时可以自学'),
('可以经常回放，很方便，练起字来，也有信心。小白入门，希望有所成绩'),
('发现宝藏课程，感谢老师指导'),
('对书法比较感兴趣，但是完全属于零基础，一直在不停的问叶老师，非常耐心的给我解答问题，太感谢啦！突然有了更多的动力'),
('没有有效期可以一直看'),
('连续看完一整节课，一点不卡顿，画面声音清晰，内容完整，非常值得，比线下上课方便多了，随时有时间随时上课，可以一节课学完也可以分段学，可以反复回放，不浪费时间，也不用安排固定时间，真的很适合业余学习，强烈建议购买！'),
('讲得还可以，就是要有时间跟着练。'),
('看了一节课，讲解的很到位，非常满意'),
('零基础上的第一阶段课，真的很不错，老师讲的很仔细，差点花几千块钱在外面学'),
('小价钱，大价值，值得购买！'),
('很好 讲的很细致'),
('问了线上网络课程和线下实体店，决定继续自己先练练，看看课程再说'),
('感觉发现了宝藏课程'),
('老师讲解简单易懂，很好的自学课，希望能学有所成。'),
('课程质量很好'),
('先买了笔画课程，学习完之后，又毫不犹豫的买了后面的课程，相对于实体和其他店铺已经很便宜了，想好好练字的，推荐一开始就全买，极力推荐不会踩坑'),
('推荐过来练字的，老师水准确实很好'),
('特别满意，一直都是在这边练字精进，现在感觉写字越来越顺手，对结构也有了新的认识。非常厉害的一个老师'),



('讲的也特别好 ，课程根据自己时间来学习 不会担心有事耽误课程学习。'),
('这个模式很棒，太适合没有什么时间的人来练字 了，老师水平很好，几句话就能把问题讲明白'),
('之前写字又丑又慢，学习了练字课程之后字写的又快又好看'),
('讲解细致，以前字写的总是歪歪扭扭，没有字体。现在看起来感觉好多啦！'),
('比想象中的好，点赞！'),
('当时买的时候还在犹豫价格，买来之后一点都不后悔，可以入手'),
('之前在其他平台买的几百块，有效期6个月，忘记看了就过去了，这边问了一下，没有有效期，果断买了，很适合我'),
('这课真的很好讲得很详细很清晰，我学了一段时间进步很大还推荐给我朋友'),
('小时候不爱练字，字写的很差，抱着试试的态度跟着课程学习，讲解的课程非常详细。现在写的越来越好'),
('听着不错，等学一阵看看如何，目前挺好'),
('感觉自己进步挺大的'),
