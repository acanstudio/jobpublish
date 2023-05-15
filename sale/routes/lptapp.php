<?php
//无验证
$lptNoauthAttributes = [
    'prefix'    => 'lptapp',
    'namespace' => 'Lptapp',

];
//无验证
Route::group($lptNoauthAttributes, function () {
    Route::any('test', 'AppinfoTeController@test'); //六品堂用户列表
    Route::any('paycourse-wxnotify', 'PayCourseController@wxnotify'); //微信回调安卓
    Route::any('paycourse-alipaynotify', 'PayCourseController@alipaynotify'); //支付宝回调安卓
    Route::any('payvideoapple-applepay', 'PayvideoappleController@applepay'); //IOS异步地址回调验证
    Route::any('invite-login', 'InviteController@login'); //
    Route::any('invite-userlist', 'InviteController@userlist'); //
    Route::any('yzm-h5sendcode', 'YzmController@h5sendcode'); //验证码
    Route::any('getAreaAll', 'AddressController@getAreaAll'); //
    Route::any('getAreaChild', 'AddressController@getAreaChild'); //
    Route::any('taobaoinfo-goodsinfo', 'TaobaoInfoController@goodsinfo'); //对某条动态举报
    Route::any('checktxt', 'TextJackController@checktxt'); //checktxt
    Route::any('zhuanti-showindex', 'ZhuantiController@showindex'); //course
    Route::any('zhuanti-yhqlist', 'ZhuantiController@yhqlist'); //course
    Route::any('zhuanti-zhuantilist', 'ZhuantiController@zhuantilist'); //course
    Route::any('zhuanti-shoplist', 'ZhuantiController@shoplist'); //course
    Route::any('zhuanti-articlelist', 'ZhuantiController@articlelist'); //courseourse
    Route::any('zhuanti-bannerlist', 'ZhuantiController@bannerlist'); //courseourse
    Route::any('zhuanti-courselist', 'ZhuantiController@courselist'); //courseourse
    Route::any('v1/paycircle-wxnotify', 'v1\PayCircleController@wxnotify'); //微信回调安卓
    Route::any('v1/paycircle-alipaynotify', 'v1\PayCircleController@alipaynotify'); //支付宝回调安卓
    Route::any('admin-miniorder-orderexport', 'AdminMiniOrderController@orderexport'); //indexlist
});
//前台APPtoken验证
$AppTokenAuthAttributes = [
    'prefix'     => 'lptapp',
    'namespace'  => 'Lptapp',
    'middleware' => ['apptokenauth'],
];

//一个 是前端接口0
Route::group($AppTokenAuthAttributes, function () {
    //@lee
    Route::any('course-listindex', 'CourseController@listindex'); //课程列表001
    Route::any('course-orderindex', 'CourseController@orderindex'); //我的课程002
    Route::any('course-info', 'CourseController@info'); //课程详情003
    Route::any('course-sectiontree', 'CourseController@sectiontree'); //课程目录和播放004
    Route::any('course-listtype', 'CourseController@listtype'); //课程类型
    Route::any('course-listnew', 'CourseController@listnew'); //三门新课程
    Route::any('ordercourse-listindex', 'OrdercourseController@listindex'); //我的订单
    Route::any('login-smslogin', 'LoginController@smslogin'); //手机登录注册
    Route::any('loginumeng-login', 'LoginUmengController@login'); //友盟登录注册
    Route::any('login-refreshtoken', 'LoginController@refreshtoken'); //refreshtoken
    Route::any('usercode-setavatar', 'UsercodeController@setavatar'); //修改头像
    Route::any('usercode-setinfo', 'UsercodeController@setinfo'); //修改昵称,修改年龄,修改性别
    Route::any('usercode-setphone', 'UsercodeController@setphone'); //setphone修改手机号
    Route::any('usercode-checkphone', 'UsercodeController@checkphone'); //checkphone验证手机号
    Route::any('usercode-zhuxiao', 'UsercodeController@zhuxiao'); //注销账号
    Route::any('usercode-info', 'UsercodeController@info'); //编辑详情
    Route::any('quan-listindex', 'QuanController@listindex'); //优惠券列表
    Route::any('quan-historyindex', 'QuanController@historyindex'); //历史优惠券列表
    Route::any('coursebox-record', 'CourseboxController@record'); //加入播放记录表
    Route::any('coursebox-addorder', 'CourseboxController@addorder'); //加入订单表
    Route::any('yzm-sendcode', 'YzmController@sendcode'); //验证码
    Route::any('logapp-getfile', 'LogappController@getfile'); //getfile
    Route::any('publicdaka-addappcontent', 'PublicdakaController@addappcontent'); //打卡巨型操作库
    Route::any('paycourse-info', 'PayCourseController@info'); //支付详情页
    Route::any('paycourse-user-address', 'PayCourseController@userAddress');
    Route::any('paycourse-place-order', 'PayCourseController@placeOrder');
    Route::any('paycourse-creatpayorder', 'PayCourseController@creatpayorder'); //ios支付
    Route::any('paycourse-creatpayorderapp', 'PayCourseController@creatpayorderapp'); //安卓APP支付

    Route::any('v1/paycircle-creatpaycircleapple', 'v1\PayCircleController@creatpaycircleapple'); //ios支付
    Route::any('v1/paycircle-creatpaycircleandroid', 'v1\PayCircleController@creatpaycircleandroid'); //安卓APP支付
    Route::any('v1/paycircle-info', 'v1\PayCircleController@info'); //支付详情页
    //IOS同步地址回调验证
    Route::any('payvideoapple-appleverifyreceipt', 'PayvideoappleController@appleverifyreceipt');
    Route::any('coins-balance', 'CoinsController@balance'); //余额
    Route::any('bills-listindex', 'BillsController@listindex'); //学币

    Route::any('payvideo-creatpayorder', 'PayvideoController@creatpayorder'); //过期详情 ，可删
    Route::any('payvideo-courseorderconfirminfo', 'PayvideoController@courseorderconfirminfo'); //过期详情 ，可删
    //1.3.2
    Route::any('loginwechat-login', 'LoginwechatController@login');
    Route::any('loginwechat-loginphone', 'LoginwechatController@loginphone');
    Route::any('loginwechat-mergeinfo', 'LmergeController@mergeinfo');
    Route::any('loginwechat-merge', 'LmergeController@merge');

    Route::any('loginapple-login', 'LoginappleController@login'); //
    Route::any('loginapple-loginphone', 'LoginappleController@loginphone'); //

    Route::any('accounts-bindphone', 'AccountsController@bindphone'); //
    Route::any('accounts-bindappleid', 'AccountsController@bindappleid'); //
    Route::any('accounts-bindwechat', 'AccountsController@bindwechat'); //
    Route::any('accounts-bindinfo', 'AccountsController@bindinfo'); //
    Route::any('invite-myinvite', 'InviteController@myinvite'); //
    Route::any('invite-inviteinfo', 'InviteController@inviteinfo'); //

    Route::any('coursebox-pop', 'CourseboxController@pop'); //弹窗
    Route::any('address-listindex', 'AddressController@listindex'); //
    Route::any('address-addaddress', 'AddressController@addAddress'); //弹窗
    Route::any('address-showaddress', 'AddressController@showAddress'); //弹窗
    Route::any('address-editaddress', 'AddressController@editAddress'); //弹窗
    Route::any('address-deladdress', 'AddressController@delAddress');

    Route::any('appinfo-newlibao', 'AppinfoController@newlibao'); //
    Route::any('appinfo-getjumpcode', 'AppinfoController@getjumpcode'); //
    Route::any('dynamichandle-jubao', 'DynamicHandleController@jubao'); //对某条动态举报
    Route::any('dynamichandle-notshowtome', 'DynamicHandleController@notshowtome'); //对某条动态不感兴趣
    Route::any('homework-listnew', 'DynamicHandleController@homeworkList'); //对某条动态举报
    Route::any('taobaoinfo-goodsrecommend', 'TaobaoInfoController@goodsrecommend'); //对某条动态举报
    Route::any('appinfo-giftcourselist', 'AppinfoController@giftcourselist'); //新人礼包
    Route::any('course-listhomeindex', 'CourseController@listhomeindex'); //首页课程列表
    Route::any('course-listhometype', 'CourseController@listhometype'); //首页栏目
    Route::any('homeindex-fpush', 'HomeindexController@fpush'); //首页T精选推送
    Route::any('homeindex-topshow', 'HomeindexController@topshow'); //首页TOP
    Route::any('zhuanti-addcoupon', 'ZhuantiController@addCoupon'); //领券
    Route::any('appinfote-test', 'AppinfoTeController@test'); //缓存测试
    Route::any('appinfote-getkey', 'AppinfoTeController@getkey'); //缓存获取

    Route::any('v1/circle-info', 'v1\CircleController@info'); //圈子详情
    Route::any('v1/circle-joincircle', 'v1\CircleController@joincircle'); //圈子加入
    Route::any('v1/circle-quitcircle', 'v1\CircleController@quitcircle'); //退圈
    Route::any('v1/circle-infomore', 'v1\CircleController@infomore'); //圈子详情页

    Route::any('v1/circle-practise', 'v1\CircleController@practise'); //practise
    Route::any('v1/circle-circletype', 'v1\CircleController@circletype'); //circletype
    Route::any('v1/circle-circlelist', 'v1\CircleController@circlelist'); //circlelist

    Route::any('v1/dakalist-newlist', 'v1\DakaListController@newlist'); //圈子详情页
    Route::any('v1/dakalist-quanzhudakalist', 'v1\DakaListController@quanzhudakalist'); //圈子详情页
    Route::any('v1/dakalist-highpraiselist', 'v1\DakaListController@highpraiselist'); //圈子详情页
    Route::any('v1/dakalist-recommendlist', 'v1\DakaListController@recommendlist'); //圈子详情页
    Route::any('v1/dakalist-coursenewlist', 'v1\DakaListController@coursenewlist'); //圈子详情页
    Route::any('v1/dakalist-courserecommentlist', 'v1\DakaListController@courserecommentlist'); //圈子详情页
    Route::any('v1/dakalist-test', 'v1\DakaListController@test'); //圈子详情页

    Route::any('dakalistnew-index', 'DakaListNewController@index'); //动态列表


    Route::any('v1/circle-xcircle', 'v1\CircleController@xcircle'); //圈子详情页
    Route::any('v1/circle-xuser', 'v1\CircleController@xuser'); //圈子详情页
    Route::any('v1/circle-hotcircle', 'v1\CircleController@hotcircle'); //圈子详情页
    Route::any('v1/circleuc-orderlist', 'v1\CircleUcController@orderlist'); //圈子详情页
    //@yy
    Route::any('hot-course', 'HomePageController@hotCourse'); //热门课程
    Route::any('recommend-course', 'HomePageController@recommendCourse'); //课程推荐
    Route::any('featured-push', 'HomePageController@featuredPush'); //精选推送
    Route::any('dynamic-detail', 'DynamicController@dynamicDetail'); //动态详情
    Route::post('user-page', 'PersonalCenterController@userPage'); //主页
    Route::any('follows-list', 'PersonalCenterController@followsList'); //关注列表
    Route::any('fans-list', 'PersonalCenterController@fansList'); //粉丝列表
    Route::post('add-follow', 'PersonalCenterController@addFollow'); //关注及取消关注
    Route::post('user-mark-news', 'PersonalCenterController@userMarkNews'); //用户未读数据
    Route::any('question', 'HomePageController@question'); //有奖问答
    Route::post('answer-question', 'HomePageController@answerQuestion'); //回答问题
    Route::post('praise-list', 'DynamicController@praiseList'); //点赞列表
    Route::post('comment-list', 'DynamicController@commentList'); //评论列表
    Route::post('review-list', 'DynamicController@reviewList'); //回复列表
    Route::post('follow-homework', 'DynamicController@followHomework'); //关注动态列表
    Route::post('homework-list', 'DynamicController@homeworkList'); //动态列表
    Route::post('praise-homework', 'DynamicController@praiseHomework'); //点赞及取消点赞
    Route::post('comment-homework', 'DynamicController@commentHomework'); //评论
    Route::post('review-homework', 'DynamicController@reviewHomework'); //回复
    Route::post('personal-center', 'PersonalCenterController@personalCenter'); //个人中心数据
    Route::post('dynamic-praise', 'DynamicController@dynamicPraise'); //某条动态点赞列表
    Route::post('dynamic-comment', 'DynamicController@dynamicComment'); //某条动态评论回复列表
    Route::post('noread-dynamic', 'DynamicController@noReadDynamic'); //动态未读数
    Route::post('feedback-add', 'PersonalCenterController@feedbackAdd'); //提交问题反馈

});

//一个 是后台接口
$lptAdminAttributes = [
    'prefix'     => 'lptapp',
    'namespace'  => 'Lptapp',
    //'middleware' => ['auth:api', 'admin.log'],
];
Route::group($lptAdminAttributes, function () {
    Route::any('admin-user-listindex', 'AdminUserController@listindex'); //六品堂用户列表
    Route::any('admin-order-listindex', 'AdminOrderController@listindex'); ///六品堂订单列表
    Route::any('admin-order-courselist', 'AdminOrderController@courselist'); ///courselist
    Route::any('admin-order-tongji', 'AdminOrderController@tongji'); //六品堂订单统计列表
    Route::any('admin-learncoin-listindex', 'AdminLearncoinController@listindex'); //六品堂学币明细
    Route::any('admin-addons-freelibao', 'AdminAddonsController@freelibao'); //礼包列表
    Route::any('admin-addons-freelibaohandle', 'AdminAddonsController@freelibaohandle'); //礼包操作
    Route::any('admin-addons-coupon', 'AdminAddonsController@coupon'); //coupon
    Route::any('admin-addons-course', 'AdminAddonsController@course'); //course
    Route::any('admin-addons-feedback', 'AdminAddonsController@feedback'); //feedback
    Route::any('admin-addons-userhandle', 'AdminAddonsController@userhandle'); //feedback
    Route::any('admin-addons-delchange', 'AdminAddonsController@delchange'); //feedback
    Route::any('admin-addons-loginchange', 'AdminAddonsController@loginchange'); //feedback
    Route::any('admin-addons-editvideoext', 'AdminAddonsController@editvideoext'); //编辑课程附属表
    Route::any('admin-addons-videoextinfo', 'AdminAddonsController@videoextinfo'); //课程附属表详情
    Route::any('admin-addons-wxteamlist', 'AdminAddonsController@wxteamlist'); //课程附属表详情
    Route::any('admin-order-preorderlist', 'AdminOrderController@preorderlist'); //赠送订单
    Route::any('admin-order-preorderdo', 'AdminOrderController@preorderdo'); //赠送订单
    Route::any('admin-addons-giftcourse', 'AdminAddonsController@giftcourse'); //course
    Route::any('admin-addons-addgiftcourse', 'AdminAddonsController@addgiftcourse'); //cours
    Route::any('admin-addons-editgiftcourse', 'AdminAddonsController@editgiftcourse'); //
    Route::any('admin-addons-delgiftcourse', 'AdminAddonsController@delgiftcourse'); //cours
    Route::any('admin-addons-courseselect', 'AdminAddonsController@courseselect'); //course
    Route::any('admin-zhuanti-listindex', 'AdminZhuantiController@listindex'); //listindex
    Route::any('admin-zhuanti-addindex', 'AdminZhuantiController@addindex'); //addindex
    Route::any('admin-zhuanti-editindex', 'AdminZhuantiController@editindex'); //editindex
    Route::any('admin-zhuanti-showindex', 'AdminZhuantiController@showindex'); //showindex
    Route::any('admin-zhuanti-modelindex', 'AdminZhuantiController@modelindex'); //modelindex
    Route::any('admin-zhuanti-contenthandle', 'AdminZhuantiController@contenthandle'); //modelindex
    Route::any('index-test', 'IndexController@test');









    Route::any('admin-hotcourse-list', 'AdminHotCourseController@index'); //热门课程列表
    Route::post('admin-hotcourse-add', 'AdminHotCourseController@addHotCourse'); //热门课程添加
    Route::post('admin-hotcourse-edit', 'AdminHotCourseController@editHotCourse'); //热门课程编辑
    Route::post('admin-hotcourse-set', 'AdminHotCourseController@setStatus'); //热门课程设置显示隐藏
    Route::get('admin-hotcourse-select', 'AdminHotCourseController@choiceCourse'); //热门课程--课程选择
    Route::get('admin-question-list', 'AdminQuestionController@index'); //问题列表
    Route::post('admin-question-add', 'AdminQuestionController@addQuestion'); //问答--添加
    Route::post('admin-question-set', 'AdminQuestionController@setStatus'); //问答--设置
    Route::get('admin-question-result', 'AdminQuestionController@nswerNumerical'); //问答--结果统计
    Route::get('admin-question-detail', 'AdminQuestionController@getQuestionDetail'); //问答--获取问题详情
    Route::post('admin-question-edit', 'AdminQuestionController@editHotCourse'); //问答--编辑



	Route::get('admin-album-list', 'AdminAlbumController@index'); //合集列表
	Route::post('admin-album-add', 'AdminAlbumController@add'); //合集添加
	Route::post('admin-album-edit', 'AdminAlbumController@edit'); //合集编辑
	Route::post('admin-album-publish', 'AdminAlbumController@publish'); //合集发布
	Route::get('admin-album-contentfont', 'AdminAlbumController@contentfont'); //合集单字视频字体列表
	Route::get('admin-album-contentlist', 'AdminAlbumController@content'); //合集内容列表
	Route::post('admin-album-contentsort', 'AdminAlbumController@contentsort'); //合集内容排序
	Route::post('admin-album-contentdel', 'AdminAlbumController@contentdel'); //合集内容移除
	Route::post('admin-album-contentadd', 'AdminAlbumController@contentadd'); //合集内容添加
	Route::post('admin-album-contentedit', 'AdminAlbumController@contentedit'); //合集内容编辑

    //课程管理
    Route::any('admin-minicourse-listindex', 'AdminMiniCourseController@listindex'); //indexlist
    Route::any('admin-minicourse-iosbutton', 'AdminMiniCourseController@iosbutton'); //indexlist
    Route::any('admin-minicourse-addindex', 'AdminMiniCourseController@addindex'); //indexlist
    Route::any('admin-minicourse-editindex', 'AdminMiniCourseController@editindex'); //indexlist
    Route::any('admin-minicourse-showindex', 'AdminMiniCourseController@showindex'); //indexlist
    Route::any('admin-minicourse-addsection', 'AdminMiniCourseController@addsection'); //indexlist
    Route::any('admin-minicourse-editsection', 'AdminMiniCourseController@editsection'); //indexlist
    Route::any('admin-minicourse-sectionindex', 'AdminMiniCourseController@sectionindex'); //indexlist
    Route::any('admin-minicourse-showsection', 'AdminMiniCourseController@showsection'); //indexlist
    Route::any('admin-minicourse-delsection', 'AdminMiniCourseController@delsection'); //indexlist
    Route::any('admin-minicourse-delsectioninfo', 'AdminMiniCourseController@delsectioninfo'); //indexlist
    Route::any('admin-minicourse-showevaluation', 'AdminMiniCourseController@showevaluation'); //indexlist
    Route::any('admin-minicourse-editevaluation', 'AdminMiniCourseController@editevaluation'); //indexlist
    Route::any('admin-minicourse-evaluationindex', 'AdminMiniCourseController@evaluationindex'); //indexlist
    Route::any('admin-minicourse-skulist', 'AdminMiniCourseController@skulist'); //indexlist
    Route::any('admin-minicourse-editsku', 'AdminMiniCourseController@editsku'); //indexlist
    Route::any('admin-minicourse-addsku', 'AdminMiniCourseController@addsku'); //indexlist
    Route::any('admin-minicourse-showsku', 'AdminMiniCourseController@showsku'); //indexlist
    Route::any('admin-minicourse-updateskupublish', 'AdminMiniCourseController@updateskupublish');
    Route::any('admin-minicourse-updatecoursepublish', 'AdminMiniCourseController@updatecoursepublish');
    Route::any('admin-minicourse-noevusers', 'AdminMiniCourseController@noevusers');
    Route::any('admin-minicourse-centerskulist', 'AdminMiniCourseController@centerskulist'); //
    //Route::any('admin-minicourse-findSKU', 'AdminMiniCourseController@findSKU');
    Route::any('admin-minicourse-tcvideolist', 'AdminMiniCourseController@tcvideolist'); //indexlist
    Route::any('admin-minicourse-assistsku', 'AdminMiniCourseController@assistsku'); //indexlist
    Route::any('admin-miniorder-orderlist', 'AdminMiniOrderController@orderlist'); //indexlist
    Route::any('admin-miniorder-orderinfo', 'AdminMiniOrderController@orderinfo'); //indexlist
    Route::any('admin-miniorder-wdtpush', 'AdminMiniOrderController@wdtpush');
   
    Route::any('admin-miniorder-ordernuminfo', 'AdminMiniOrderController@ordernuminfo'); //indexlist
    Route::any('admin-miniuser-listindex', 'AdminMiniUserController@listindex'); //indexlist
    Route::any('admin-miniuser-adduser', 'AdminMiniUserController@adduser'); //indexlist
    Route::any('admin-miniuser-editusersku', 'AdminMiniUserController@editusersku'); //indexlist

    // 课程兑换码
    Route::get('coupon-activity-list', 'CouponActivityController@list')->name('coupon-activity-list'); // 课程券活动列表
    Route::get('coupon-batch-list', 'CouponActivityController@batchList')->name('coupon-batch-list'); // 课程券批次列表
    Route::post('coupon-activity-add', 'CouponActivityController@add')->name('coupon-activity-add'); // 添加课程券活动
    Route::put('coupon-activity-update', 'CouponActivityController@update')->name('coupon-activity-update'); // 编辑课程券活动
    Route::post('coupon-activity-cancel', 'CouponActivityController@cancel')->name('coupon-activity-cancel'); // 取消发布课程券活动

    Route::get('coupon-activity-user-list', 'CouponActivityUserController@list')->name('coupon-activity-user-list'); // 用户领券记录
    Route::get('coupon-activity-user-export', 'CouponActivityUserController@export')->name('coupon-activity-user-export'); // 导出用户领券记录
    Route::get('coupon-activity-user-batch', 'CouponActivityUserController@batch')->name('coupon-activity-user-batch'); // 批量发券

    Route::get('coupon-activity-test', 'CouponActivityController@test')->name('coupon-activity-test'); // 小功能测试验收
});


