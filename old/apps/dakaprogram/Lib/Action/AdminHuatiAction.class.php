<?php
/**
 * Created by PhpStorm.
 * User: @leee
 * Date: 2019
 * Time: 13:30
 */

namespace App\dakaprogram\Lib\Action;

use App\admin\Lib\Action\AdministratorAction;
use Library\Redis\Redis;

//自由打卡和定位
class AdminHuatiAction extends AdministratorAction
{
    public $vid;

    public function _initialize()
    {
//页面标签参数配置
        parent::_initialize();
    }
    //首页@lee
    public function huatiRelateCircleIds()
    {
        return array(17, 21);
    }
    //首页@lee
    public function huatilist()
    {
        $this->pageTab[] = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageTab[] = ['title' => '添加话题', 'tabHash' => 'addOne', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=addOne"];
        //页面列表默认展示的表头参数
        //搜索字段
        $this->pageButton[] = array(
            'title'   => "上传作品",
            'onclick' => "window.location.href='" . U('dakaprogram/AdminHuati/addHomesubmitInHuati') . "'",
        );
        $this->allSelected = false;

        $this->pageKeyList = ['id', 'title', 'relate_circle_id', 'follow_num', 'description', 'status', 'dakanum', 'sort', 'addtime', 'DOACTION'];

        $homeWorkInfo = M("dk_huati")->where()->order("id desc")->findPage(10);
        foreach ($homeWorkInfo['data'] as $key => $value) {
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminHuati/editOne', array('tabHash' => 'editOne', 'id' => $value['id'])) . '">' . '编辑' . '</a> |';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminHuati/riji', array('tabHash' => 'riji', 'id' => $value['id'])) . '">' . '打卡明细' . '</a> |';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminMz/listHuatiBox', array('tabHash' => 'listHuatiBox', 'id' => $value['id'])) . '">' . 'Banner' . '</a> |';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminHuati/ziliao', array('tabHash' => 'riji', 'id' => $value['id'])) . '">' . '资料' . '</a> |';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminHuati/yinyue', array('tabHash' => 'riji', 'id' => $value['id'])) . '">' . '音乐' . '</a> |';
            $infos = $value['status'] == 0 ? '显示' : '隐藏';
            //$homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="disableHuati(' . $value['id'] . ')">' . $infos . '</a> |';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/Qrcode/downlodeqrHT', array('type' => '1', 'source' => '1', 'rel_id' => $value['id'])) . '">' . '电商圆码' . '</a> -';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/Qrcode/downlodeqrHT', array('type' => '1', 'source' => '2', 'rel_id' => $value['id'])) . '">' . '运营圆码' . '</a> -';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/Qrcode/downlodeqrHT', array('type' => '1', 'source' => '3', 'rel_id' => $value['id'])) . '">' . '电商方码' . '</a> -';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/Qrcode/downlodeqrHT', array('type' => '1', 'source' => '4', 'rel_id' => $value['id'])) . '">' . '运营方码' . '</a> ';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '| <a href="' . U('dakaprogram/AdminHuatiCollection/index', array('title' => $value['title'], 'h_id' => $value['id'])) . '">' . '商品集合' . '</a> ';
            // $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="gengxinEwm(' . $value['id'] . ')"> |生成二维码</a>';

            $homeWorkInfo['data'][$key]['status']     = $value['status'] == 0 ? '<font color="grey">OFF</font>' : '<font color="green">ON</font>';
            $homeWorkInfo['data'][$key]['sort']       = "<input type='text' id='sortid_$value[id]' onBlur='editHuatiSort($value[id],$value[sort])' value='$value[sort]' style='width:40px'";
            $homeWorkInfo['data'][$key]['ceouser']    = $homeWorkInfo['data'][$key]['ceouser'] . "(" . getUserName($homeWorkInfo['data'][$key]['ceouser']) . ")";
            $homeWorkInfo['data'][$key]['addtime']    = date("Y-m-d H:i:s", $value['addtime']);
            $homeWorkInfo['data'][$key]['follow_num'] = $this->findFollonNum($value['id']);

            $homeWorkInfo['data'][$key]['dakanum']     = M('homework_submit')->where(['dk_type' => $value['id']])->count();
            $homeWorkInfo['data'][$key]['description'] = mb_substr($value['description'], 0, 20) . "...";

            $homeWorkInfo['data'][$key]['relate_circle_id'] = M('dk_circle')->where(['id' => $value['relate_circle_id']])->getField('title');

        }

        $this->displayList($homeWorkInfo);
        echo "<script src='./apps/dakaprogram/_static/js/admin_huati.js?Z=343'>
        </script>";
    }
    //视频库
    public function getAblumList()
    {

        $map['is_publish'] = 1;
        if ($_POST['s_title']) {
            $map['album_title'] = array('like', '%' . t($_POST['s_title']) . '%');
        }
        $map['stype'] = array('lt', 5);
        if ($_POST['s_type']) {
            $map['stype'] = intval($_POST['s_type']);
        }

        $total     = M('dk_album')->where($map)->count(); //总记录数
        $page      = intval($_POST['pageNum']); //当前页
        $pageSize  = 10; //每页显示数
        $totalPage = ceil($total / $pageSize); //总页数

        $startPage = $page * $pageSize; //开始记录
        //构造数组
        $list['total']     = $total;
        $list['pageSize']  = $pageSize;
        $list['totalPage'] = $totalPage;

        $list['data'] = M('dk_album')->where($map)->order('created_at desc')->limit("{$startPage} , {$pageSize}")->findAll();
        $type_text    = [0 => '未知', 1 => '视频', 2 => '单字视频', 3 => '音频', 4 => '文件'];
        foreach ($list['data'] as &$val) {
            $val['type']  = $type_text[$val['type']];
            $val['stype'] = $type_text[$val['stype']];
            $val['uid']   = getUserName($val['uid']);
            $val['ctime'] = date('Y-m-d', $val['ctime']);
        }
        exit(json_encode($list));
    }
    //首页@lee
    public function ziliao()
    {
        /*$this->pageTab[] = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageTab[] = ['title' => '添加话题', 'tabHash' => 'addOne', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=addOne"];*/

        $huati_id           = $map['huati_id']           = $_REQUEST['id'];
        $this->pageTab[] = ['title' => '资料列表', 'tabHash' => 'ziliaolist', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=ziliao&id={$huati_id}"];
        $this->pageTab[] = ['title' => '资料分类', 'tabHash' => 'categorylist', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=ziliaoCategory&id={$huati_id}"];
        //页面列表默认展示的表头参数
        $this->pageButton[] = array('title' => '+添加', 'onclick' => "addZiliaoTreeCategory({$huati_id})");

        $this->allSelected = false;

        $this->pageKeyList = ['id', 'album_id', 'album_title', 'huati_category', 'stype', 'sort_id', 'is_publish', 'real_view', 'content_num', 'created_at', 'DOACTION'];
        $map['ptype']      = 1;
        $map['huati_id']   = $huati_id;
        $map['is_del']     = 0;
        $map['album_id']   = array('gt', 0);
        $homeWorkInfo      = M("dk_huati_album")->where($map)->order("sort_id asc,id asc")->findPage(10);foreach ($homeWorkInfo['data'] as $key => $value) {
            $stype = $this->albumField($value['album_id'], 'stype');
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="setHuatiCategory(' . $value['id'] . ')">设置分类</a> ';
            if ($value['is_del'] == 0) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="delZiliao(' . $value['id'] . ')">移除</a> ';
            }
            if ($stype == 1 || $stype == 2) {
                $infos = $value['is_ai'] == 0 ? '开启AI评测' : '隐藏AI评测';
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="disableAiZiliao(' . $value['id'] . ')">' . $infos . '</a> ';
            }
            $homeWorkInfo['data'][$key]['sort_id']     = "<input type='text' id='sortid_$value[id]' onBlur='editZiliaoSort($value[id],$value[sort_id])' value='$value[sort_id]' style='width:40px'";
            $homeWorkInfo['data'][$key]['album_title'] = $this->albumField($value['album_id'], 'album_title');
            $homeWorkInfo['data'][$key]['stype']       = $this->checkStype($stype);
            $homeWorkInfo['data'][$key]['is_publish']  = $this->albumField($value['album_id'], 'is_publish') ? "已发布" : "未发布";
            $homeWorkInfo['data'][$key]['content_num'] = $this->contentNum($value['album_id']);
            $homeWorkInfo['data'][$key]['real_view']   = $this->albumField($value['album_id'], 'real_view');
            $homeWorkInfo['data'][$key]['huati_category'] = $this->getHuatiCategory($value['huati_category']);

        }

        $this->displayList($homeWorkInfo);
        $time = time();
        echo "<script src='./apps/dakaprogram/_static/js/admin_ziliao.js?Z={$time}166'>
        </script>";
    }

    //首页@lee
    public function yinyue()
    {
        $this->pageTab[] = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageTab[] = ['title' => '添加话题', 'tabHash' => 'addOne', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=addOne"];
        //页面列表默认展示的表头参数
        //搜索字段
        $huati_id           = $map['huati_id']           = $_REQUEST['id'];
        $this->pageButton[] = array('title' => '+添加', 'onclick' => "addZiliaoYinyueTreeCategory({$huati_id})");

        $this->allSelected = false;

        $this->pageKeyList = ['id', 'album_id', 'album_title', 'stype', 'sort_id', 'is_publish', 'real_view', 'content_num', 'created_at', 'DOACTION'];
        $map['ptype']      = 2;
        $map['huati_id']   = $huati_id;
        $map['is_del']     = 0;
        $map['album_id']   = array('gt', 0);
        $homeWorkInfo      = M("dk_huati_album")->where($map)->order("sort_id asc,id asc")->findPage(10);
        foreach ($homeWorkInfo['data'] as $key => $value) {
            if ($value['is_del'] == 0) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="delZiliao(' . $value['id'] . ')">移除</a> ';
            }
            // $infos = $value['is_ai'] == 0 ? '开启AI评测' : '隐藏AI评测';
            // $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="disableAiZiliao(' . $value['id'] . ')">' . $infos . '</a> ';
            $homeWorkInfo['data'][$key]['sort_id'] = "<input type='text' id='sortid_$value[id]' onBlur='editZiliaoSort($value[id],$value[sort_id])' value='$value[sort_id]' style='width:40px'";

            $homeWorkInfo['data'][$key]['album_title'] = $this->albumField($value['album_id'], 'album_title');
            $homeWorkInfo['data'][$key]['stype']       = $this->checkStype($this->albumField($value['album_id'], 'stype'));
            $homeWorkInfo['data'][$key]['is_publish']  = $this->albumField($value['album_id'], 'is_publish') ? "已发布" : "未发布";
            $homeWorkInfo['data'][$key]['content_num'] = $this->contentNum($value['album_id']);
            $homeWorkInfo['data'][$key]['real_view']   = $this->albumField($value['album_id'], 'real_view');

        }

        $this->displayList($homeWorkInfo);
        $time = time();
        echo "<script src='./apps/dakaprogram/_static/js/admin_ziliao.js?Z={$time}177'>
        </script>";
    }
    public function contentNum($id = 0)
    {
        $data['is_del']     = 0;
        $data['album_id']   = $id;
        $data['is_chapter'] = 0;
        return M('dk_album_data')->where($data)->count();
    }

    public function albumField($id = 0, $str = 'id')
    {
        return M('dk_album')->where(['id' => $id])->getField($str);
    }
    public function checkStype($id = 0)
    {
        switch ($id) {
            case '1':
                return "视频";
                break;
            case '2':
                return "单字视频";
                break;
            case '3':
                return "音频";
                break;
            case '4':
                return "文件";
                break;
            default:
                return "其他";
                break;
        }
    }

    //更新排序
    public function editZiliaoSort()
    {
        $G['id']      = $_REQUEST['id'];
        $G['sort_id'] = (int) $_REQUEST['sort_id'];
        $uInfo        = M("dk_huati_album")->save($G);
        if ($uInfo) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }
    //禁用圈子
    public function delZiliao()
    {
        $Ba['id']     = $_REQUEST['id'];
        $uInfo        = M("dk_huati_album")->where("id=" . $Ba['id'])->field("is_del")->find();
        $Ba['is_del'] = (int) $uInfo['is_del'] == 0 ? 1 : 0;
        $result       = M("dk_huati_album")->save($Ba);
        if ($result) {
            exit(json_encode(array(
                "status" => 0,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作失败！",
            )));
        }
    }
    //禁用圈子
    public function disableAiZiliao()
    {
        $Ba['id']    = $_REQUEST['id'];
        $uInfo       = M("dk_huati_album")->where("id=" . $Ba['id'])->field("is_ai")->find();
        $Ba['is_ai'] = (int) $uInfo['is_ai'] == 0 ? 1 : 0;
        $result      = M("dk_huati_album")->save($Ba);
        if ($result) {
            exit(json_encode(array(
                "status" => 0,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作失败！",
            )));
        }
    }
    /**
     * 添加章节页面
     * @return void
     */
    public function addZiliaoHtml()
    {
        $id = intval($_GET['id']);
        $this->assign('huati_id', $id);
        if (is_admin($this->mid)) {
            $this->assign('cid', 0);
        }
        $this->display();
    }
    /**
     * 添加章节页面
     * @return void
     */
    public function addZiliaoYinyueHtml()
    {
        $id = intval($_GET['id']);
        $this->assign('huati_id', $id);
        if (is_admin($this->mid)) {
            $this->assign('cid', 0);
        }
        $this->display();
    }
    /**
     * 添加章节操作
     * @return json 返回相关的JSON信息
     */
    public function doAddHuatiAlbum()
    {
        $album_id     = $dta['huati_id']     = intval($_REQUEST['huati_id']);
        $huati_id     = $dta['album_id']     = intval($_REQUEST['album_id']);
        $ptype        = intval($_REQUEST['ptype']);
        $dta['ptype'] = $ptype == 2 ? 2 : 1;
        $rss          = M('dk_huati_album')->where($dta)->find();
        if (empty($album_id) || empty($huati_id)) {
            $res['status'] = 0;
            $res['data']   = '参数为空';
            exit(json_encode($res));
        }
        if (empty($rss)) {
            M('dk_huati_album')->add($dta);
        } else {
            if ($rss['is_del']) {
                $data['created_at'] = $data['updated_at'] = date('Y-m-d H:i:s');
            } else {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            $data['is_del'] = 0;
            $data['id']     = $rss['id'];
            M('dk_huati_album')->save($data);
        }
        $res['status'] = 1;
        $res['data']   = '添加成功';
        exit(json_encode($res));
    }

    public function findFollonNum($id = 0)
    {
        return M('dk_huati_user')->where(['huati_id' => $id])->count();
    }
    public function getWxEwmDS($id = 0)
    {
        return M('dk_huati_qrcode')->where(['rel_id' => $id])->getField('url_ds');
    }
    public function getWxEwmYY($id = 0)
    {
        return M('dk_huati_qrcode')->where(['rel_id' => $id])->getField('url_yy');
    }
    public function gengxinEwm()
    {
        $id = $_REQUEST['id'];

        $qrcodedaka        = new QrcodeAction();
        $res1              = $qrcodedaka->getWxcodeHuatiDS($id);
        $res2              = $qrcodedaka->getWxcodeHuatiYY($id);
        $res3              = $qrcodedaka->getWxcodeHuatiDSFang($id);
        $res4              = $qrcodedaka->getWxcodeHuatiYYFang($id);
        $qr['rel_id']      = $id;
        $qr['url_ds']      = $res1;
        $qr['url_yy']      = $res2;
        $qr['url_ds_fang'] = $res3;
        $qr['url_yy_fang'] = $res4;

        $ok = M('dk_huati_qrcode')->where(['rel_id' => $id])->find();
        if (!empty($ok)) {
            $qr['id'] = $ok['id'];
            $result   = M('dk_huati_qrcode')->save($qr);
        } else {
            $result = M('dk_huati_qrcode')->add($qr);
        }

        if ($result) {
            exit(json_encode(array(
                "status" => 0,
                "data"   => $qr,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作失败！",
            )));
        }
    }
    public function gengxinEwmQZAll()
    {
        $x    = $_REQUEST['x'];
        $y    = $_REQUEST['y'];
        $type = $_REQUEST['type'] ?: 1;
        for ($i = $x; $i < $y; $i++) {
            $this->gengxinEwmQZ($i, $type);
        }

    }
    public function gengxinEwmQZ($ids = 0, $type = 1)
    {
        $idr        = $_REQUEST['id'];
        $id         = $idr ?: $ids;
        $qrcodedaka = new QrcodeAction();
        if ($type == 2) {
            $res1 = $qrcodedaka->getWxcodeQZPractise($id, 1);
            $res2 = $qrcodedaka->getWxcodeQZPractise($id, 2);

        } else {
            $res1 = $qrcodedaka->getWxcodeQZDS($id);
            $res2 = $qrcodedaka->getWxcodeQZYY($id);
            $res3 = $qrcodedaka->getWxcodeQZDSFang($id);
            $res4 = $qrcodedaka->getWxcodeQZYYFang($id);
        }
        $qr['rel_id'] = $id;
        $qr['img_1']  = $res1;
        $qr['img_2']  = $res2;
        $qr['img_3']  = $res3;
        $qr['img_4']  = $res4;
        $qr['type']   = $type;
        print_r($qr);
        $ok = M('dk_qrcode')->where(['rel_id' => $id, 'type' => $type])->find();
        if (!empty($ok)) {
            $qr['id'] = $ok['id'];
            $result   = M('dk_qrcode')->save($qr);
        } else {
            $result = M('dk_qrcode')->add($qr);
        }
        if ($result) {
            echo json_encode(array(
                "status" => 0,
                "msg"    => "操作成功！",
            ));
        } else {
            echo json_encode(array(
                "status" => 1,
                "msg"    => "操作失败！",
            ));
        }
    }
    //添加打卡后台
    public function addHomesubmitInHuati()
    {
        $infocircle = M('dk_huati')->where('status=1')->select();
        $this->assign('infoCircle', $infocircle);
        $this->display();
    }
    /**
     * @return  用户提交的作业详情
     */
    public function doaddHomesubmitHuati()
    {
        $post = $_POST;

        $uid = $dt['uid'] = $post['uid'];
        if (empty($post['img_url_t'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => "请选择作品",
            )));
        }
        // if (!empty($post['video_url'])&&(strpos($post['video_url'], 'mp4') == false) ) {
        //      exit(json_encode(array(
        //          'status' => '0',
        //          'info' => "视频不对吧?"
        //      )));
        //  }
        //
        if (empty($post['uid'])) {
            exit(json_encode(array(
                'status' => '0',
                'info'   => "用户",
            )));
        }
        $udata['title']         = $post['title'];
        $udata['img_url']       = implode(',', $post['img_url_t']);
        $udata['img_url_thumb'] = implode(',', $post['img_url_thumb']);
        // if (empty($udata['img_url'])) {
        //     $udata['img_url'] = "https://".$_SERVER["HTTP_HOST"]."/data/upload/dakaprogram/bofang.jpg";
        // }
        // if (empty($udata['img_url_thumb'])) {
        //     $udata['img_url_thumb'] = "/data/upload/dakaprogram/bofang.jpg";
        // }
        $udata['uid']        = $post['uid'];
        $udata['is_show']    = $post['is_show'];
        $udata['initzan']    = $post['initzan'];
        $udata['dk_type']    = $post['dk_type'];
        $udata['recommend']  = $post['recommend'];
        $udata['isadmin']    = 1;
        $udata['is_program'] = 1;
        $udata['content']    = $post['content'];
        $udata['atime']      = time();
        D('Daka')->dakatotal($uid);
        M('homework_submit')->add($udata);
        D('Daka')->weekAndMonthRefresh($uid);
        exit(json_encode(array(
            'status' => '1',
            'info'   => "成功",
        )));
    }

    /**
     * 评论弹出框
     * @author
     * @time 2019-09-02
     */
    public function Comment()
    {
        $id      = $_POST['id'];
        $uid     = $_POST['uid'];
        $cid     = $_POST['cid'];
        $comment = $_POST['comment'];
        $this->assign('id', $id);
        $this->assign('uid', $uid);

        $this->assign('cid', $cid);
        $this->assign('comment', $comment);
        $this->display();
    }
    //更新排序
    public function picLoad($RS)
    {
        $rs   = $_REQUEST['pic'];
        $vusl = base64_decode($rs);
        $this->assign('info', $vusl);
        $this->display();
    }
    public function riji()
    {
        //搜索字段
        $this->pageTab[]    = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->searchKey    = ['id', 'recommend', array('startTime', 'endTime')];
        $this->pageKeyList  = ['id', 'avatar', 'username', 'sub_time', 'is_show', 'dakaimg', 'zans', 'recommend', 'excellent', 'comment', 'DOACTION'];
        $this->searchKey    = ['uid', 'uname'];
        $this->pageButton[] = ['title' => "搜索", 'onclick' => "admin.fold('search_form')"];

        $map['is_program'] = 1;
        $map['dk_type']    = intval($_REQUEST['id']);
        if (isset($_POST)) {
            //加入搜索的参数
            if (!empty($_POST['uid'])) {
                $map['uid'] = $_POST['uid'];
            }

            if (!empty($_POST['uname'])) {
                $data       = \model('user')->where(['uname' => $_POST['uname']])->find();
                $map['uid'] = $data['uid'];
            }

        }

        // print_r($map);
        $homeWorkInfo = M("homework_submit")->where($map)->order("atime desc")->findPage(20);
        //循环处理数据
        //缓存评论数或者点赞数
        $redis = Redis::getInstance();
        foreach ($homeWorkInfo['data'] as $key => $val) {
            $userInfo                               = model('user')->getUserInfo($val['uid']);
            $homeWorkInfo['data'][$key]['avatar']   = "<img width='40' height='40' src='{$userInfo['avatar_tiny']}'/>";
            $homeWorkInfo['data'][$key]['username'] = "<b>{$userInfo['uname']}</b>";
            $homeWorkInfo['data'][$key]['sub_time'] = date('Y-m-d H:i:s', $val["atime"]);
            //$homeWorkInfo['data'][$key]['cicletitle'] = $this->cicletitle($val['cid']);
            $praceInfo = M("dk_practise")->where("id=" . $val['xid'])->find();
//            $zans=M("homework_praise")->where("hsid=".$val['id'])->count();
            if ($redis->get('liupinshuyuan_count_praise_' . $val['id'])) {
                $zans = $redis->get('liupinshuyuan_count_praise_' . $val['id']);
            } else {
                $zans = M("homework_praise")->where("hsid=" . $val['id'])->count();
                $redis->set('liupinshuyuan_count_praise_' . $val['id'], $zans, 1800);
            }
//            $commet_number = M('homework_comments')->where(['hsid'=>$val['id']])->count();
            if ($redis->get('liupinshuyuan_count_comments_' . $val['id'])) {
                $commet_number = $redis->get('liupinshuyuan_count_comments_' . $val['id']);
            } else {
                $commet_number = M('homework_comments')->where(['hsid' => $val['id']])->count();
                $redis->set('liupinshuyuan_count_comments_' . $val['id'], $commet_number, 1800);
            }
            $homeWorkInfo['data'][$key]['suoshu'] = $praceInfo['practise_name'];
            $img_url_arr                          = explode(',', $val['img_url']);

            // $str = '<span onclick="load_pic(this)">';
            // foreach ($img_url_arr as  $img){
            //     $str.= "<a data-gallery='manual' href=".$img."><img src=".$img." width=80 height=80></a>";
            // }
            // // $str.= '</span>';
            // $homeWorkInfo['data'][$key]['dakaimg'] = $str;
            if (count($img_url_arr) < 2) {
                $vusls                                 = base64_encode($val['img_url']);
                $homeWorkInfo['data'][$key]['dakaimg'] = '<img onclick=' . 'picLoad("' . $vusls . '")' . ' src="' . $val['img_url'] . '" width=80 height=80>';
            } else {
                foreach ($img_url_arr as $vul) {
                    $vusl = base64_encode($vul);
                    $homeWorkInfo['data'][$key]['dakaimg'] .= '<img style="margin-left:10px;" onclick=' . 'picLoad("' . $vusl . '")' . ' src="' . $vul . '" width=80 height=80>';
                }
            }
            $is_del = '';
            if ($val['is_del'] == 1) {
                $is_del = "<span style='color: grey'>    [已删除]</span>";
            }
            if ($val['is_show'] == 1) {
                $homeWorkInfo['data'][$key]['is_show'] = "<span style='color: #0bb20c'>公开</span>" . $is_del;
            } else {
                $homeWorkInfo['data'][$key]['is_show'] = "<span style='color:red'>仅自己</span>" . $is_del;
            }
            $homeWorkInfo['data'][$key]['zans']           = $zans;
            $homeWorkInfo['data'][$key]['comment_number'] = $commet_number;
            $homeWorkInfo['data'][$key]['recommend']      = $this->recommend($val['recommend']);
            $homeWorkInfo['data'][$key]['excellent']      = $this->excellent($val['excellent']);
            $homeWorkInfo['data'][$key]['comment']        = "<textarea  style=\"line-height: 1.5;height: 100px;\" rows=\"10\" cols=\"25\" ></textarea>";
            //组合操作栏
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="' . U('dakaprogram/AdminHuati/subdetail', array('sid' => $val['id'], 'ortype' => 'waiquan')) . '">看详情</a>  |';
            if ($val['recommend'] != 2) {

                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="bestJx(' . $val['id'] . ',' . $val['uid'] . ')">设精选</a> |';
            }
            if ($val['recommend'] == 2) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="nobestJx(' . $val['id'] . ')">取消精选</a> |';
            }

            if ($val['excellent'] == 0) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="zhiding(' . $val['id'] . ',' . $val['dk_type'] . ')">置顶 |</a>';
            } else {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="nozhiding(' . $val['id'] . ',' . $val['dk_type'] . ')">取消置顶 |</a>';
            }
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="doDelHm(' . $val['id'] . ',this)">删除</a> |';
            if ($val['is_show'] == 1) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="doShowOrHide(' . $val['id'] . ',' . $val['is_show'] . ')">隐藏</a>';
            } else {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="doShowOrHide(' . $val['id'] . ',' . $val['is_show'] . ')">显示</a>';
            }
            $homeWorkInfo['data'][$key]['DOACTION'] .= ' | <a href="javascript:void(0)" onclick="doComment(' . $val['id'] . ',' . $val['uid'] . ',this)">点评</a>';
        }
        $this->searchPostUrl = U('dakaprogram/AdminHuati/riji', ['id' => $map['dk_type']]);
        $this->displayList($homeWorkInfo);
        echo '<script type="text/javascript" src="https://file.liupinshuyuan.com/addons/theme/stv1/_static/js/photoviewer/photoviewer.js"></script>';
        echo '<script type="text/javascript" src="https://file.liupinshuyuan.com/addons/theme/stv1/_static/js/layer/layer.js"></script>';
        echo "<script src='./apps/dakaprogram/_static/js/admin_huati.js?Z=555555555'></script>";
        echo "<script src='./apps/dakaprogram/_static/js/admin_content.js?Z=999'></script>";
    }
    //设为精选
    public function sdel()
    {
        $sid             = $_POST['id'];
        $Tdata['id']     = $sid;
        $Tdata['is_del'] = 1;
        $rs              = M("homework_submit")->save($Tdata);
        if ($rs) {
            echo json_encode(array("status" => 1, "msg" => "操作成功"));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }
    //设为精选
    public function sdelcomment()
    {
        $sid             = $_POST['id'];
        $Tdata['id']     = $sid;
        $Tdata['is_del'] = 1;
        $rs              = M("homework_comments")->save($Tdata);
        if ($rs) {
            echo json_encode(array("status" => 1, "msg" => "操作成功"));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }
    //设为精选
    public function sdelreview()
    {
        $sid             = $_POST['id'];
        $Tdata['id']     = $sid;
        $Tdata['is_del'] = 1;
        $rs              = M("homework_reviews")->save($Tdata);
        if ($rs) {
            echo json_encode(array("status" => 1, "msg" => "操作成功"));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }
    /**
     * 评论提价
     * @author zhongzhong
     * @time 2019-09-02
     */
    public function doComment()
    {
        $id       = intval($_POST['id']);
        $uid      = intval($_POST['uid']);
        $gfuid    = intval($_POST['gfuid']);
        $content  = t($_POST['comment']);
        $is_water = intval($_POST['is_water']);
        if ($is_water == 1) {
            $water_uids = M('dk_fakeuser')->field("uid")->order(" uid asc ")->select();
            $water_uids = array_column($water_uids, 'uid');
            $num        = mt_rand(0, count($water_uids));
            $push_id    = $water_uids[$num];
        } else if ($is_water == 2) {
            $push_id = intval($gfuid);
            if (empty($push_id)) {
                $push_id = 7;
            }

        } else {
            $push_id = $this->mid;
        }
        $data['hsid']    = $id;
        $data['uid']     = $push_id;
        $data['uname']   = getUserName($push_id);
        $data['content'] = $content;
        $data['atime']   = time();
        $res             = M("homework_comments")->add($data);
        if ($res) {
            $this->_sendCommentMessageNews($id, $uid, $cid, $data['uname'], $data['content']);
            $this->_sendLoaclMessage($push_id, $uid, $content, $id, $cid);
            $this->ajaxReturn('', '操作成功!', 1);
        } else {
            $this->ajaxReturn('', '点评失败!', 0);
        }
    }
    protected function _sendCommentMessageNews($sid, $uid, $cid = 0, $name, $content)
    {
        $post                   = [];
        $post['page']           = 'pages/circleSignInfo/circleSignInfo?id=' . $sid;
        $post['template_id_id'] = 2;
        $post['template_id']    = 'A9EMprBTcrCjmLcqD4muwBTfNBnByGtb0KOuzbw8am4';
        if (mb_strlen($content, 'utf8') > 20) {
            $t2 = mb_substr($content, 0, 15, 'utf8') . '...';
        } else {
            $t2 = $content;
        }
        $post['str'] = [
            'keyword1' => $name,
            'keyword2' => $t2,
        ];
        D('Subscription', 'dakaprogram')->getTempWeiduJx($uid, $post, 3);
    }
    /**
     * @param $from_uid  发送人uid
     * @param $to_uid    发送给的人 uid
     * @param $content    内容
     * @param $hsid      作业id
     * @param $cid       圈子id
     */
    protected function _sendLoaclMessage($from_uid, $to_uid, $content, $hsid, $cid)
    {
        $data['from_uid']   = $from_uid;
        $data['to_uid']     = $to_uid;
        $data['type']       = 1;
        $data['content']    = $content;
        $data['hsid']       = $hsid;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['isprogram']  = 1;
        $data['circleid']   = $cid;
        M('msg_interact')->add($data);
    }
    /**
     * @return  用户提交的作业详情
     */
    public function subdetail()
    {
        $sid = $_GET['sid'];
        if (empty($sid)) {
            $this->error('缺少参数');
        }
        $hwInfo = M('homework_submit')->where(['id' => $sid])->field('id,hid,is_hide,is_top,uid,img_url,content,on_time,atime,type,dk_type,xid,cid,recommend,is_show')->find();
        if ($hwInfo['dk_type']) {
            $quzhiInfo = M("dk_huati")->where("id=" . $hwInfo['dk_type'])->find();

        }
        $studentInfo           = model('user')->getUserInfo($hwInfo['uid']);
        $hwInfo['avatar']      = $studentInfo['avatar_middle'];
        $hwInfo['studentName'] = $studentInfo['uname'];
        $hwInfo['img_url']     = explode(',', $hwInfo['img_url']);
        $hwInfo['atime']       = date('Y-m-d H:i:s', $hwInfo['atime']);
        //作业评论
        $hwInfo['comments'] = M('homework_comments')->where(['hsid' => $sid, 'is_del' => 2])->field('id,uname,hsid,uid,content,atime')->order('atime desc')->select();
        //echo M('homework_comments')->getlastsql();
        //var_dump($hwInfo['comments'] );
        foreach ($hwInfo['comments'] as $kk => $vv) {
            $uInfo                              = model('user')->getUserInfo($vv['uid']);
            $hwInfo['comments'][$kk]['uname']   = $uInfo['uname'];
            $hwInfo['comments'][$kk]['avatar']  = $uInfo['avatar_original'];
            $hwInfo['comments'][$kk]['atime']   = date('Y-m-d H:i:s', $vv["atime"]);
            $hwInfo['comments'][$kk]['com_com'] = D('HwReview', 'live')->getReviews($vv['id']);
        }
        $adminInfo              = model('user')->getUserInfo($this->mid);
        $hwInfo['admin_name']   = $adminInfo['uname'];
        $hwInfo['admin_avatar'] = $adminInfo['avatar_original'];
        //圈子请求过来的URL有参数ortype=quanzhi，同时查出圈子，练习信息

        $hwInfo['huati_r'] = $quzhiInfo['title'];
        if ($hwInfo['recommend'] == 2) {
            $hwInfo['recommend_r'] = "精选";
        } else {
            $hwInfo['recommend_r'] = "普通";
        }
        if ($hwInfo['is_show'] == 1) {
            $hwInfo['is_show_r'] = "显示";
        } else {
            $hwInfo['is_show_r'] = "隐藏";
        }
        $practename = $lianxiInfo['practise_name'];
        $this->assign("ptype", $_REQUEST['ortype']);
        $this->assign("huatiname", $circlename);
        $this->assign("huatiname", $circlename);
        $this->assign("practename", $practename);

        $circlename = $quzhiInfo['title'];
        $this->assign("ortype", $ortype);
        $this->assign('hwInfo', $hwInfo);
        $this->assign('img_url', $hwInfo['img_url']);
        $this->display();
    }
    public function delCom()
    {
        $id  = $_GET['com_id'];
        $rid = $_GET['rid'];

        $c_type = $_GET['c_type'];
        if (empty($id)) {
//||$rid === null
            $this->ajaxReturn('', '删除失败', 0);
        }
        try {
            $com_info = D('HwSubComment', 'live')->where(['id' => $id])->find();
            $rev_info = D('HwReview', 'live')->where(['id' => $rid])->find();
            if ($uid == $com_info['uid'] || $uid == $rev_info['uid']) {
                if ($c_type == 'com') {
//作业评论
                    $d['is_del'] = 1;
                    $d['id']     = $id;
                    M('homework_comments')->save($d);
                } elseif ($c_type == 'review') {
                    $dss['is_del'] = 1;
                    $dss['id']     = $id;
                    M('homework_reviews')->save($dss);
                    //echo M('homework_reviews')->getlastsql();
                } else {
                    throw new \Exception('评论类型错误');
                }
                $this->ajaxReturn('', '删除成功', 1);
            } else {
                throw new \Exception('无删除权限');
            }
        } catch (\Exception $e) {
            $this->ajaxReturn($e->getMessage(), '删除失败', 0);
        }
    }
    /**
     * @return  精选@lee
     */
    public function recommend($c = 0)
    {
        return $c == 2 ? "<font color=green>精选</font>" : ($c == 1 ? "<font color=red>待审核</font>" : "<font color=grey>普通</font>");
    }
    /**
     * @return  精选@lee
     */
    public function excellent($c = 0)
    {
        return $c == 1 ? "<font color=green>置顶</font>" : "<font color=grey>普通</font>";
    }
    //更新排序
    public function editHuatiSort()
    {
        $G['id']   = $_REQUEST['id'];
        $G['sort'] = (int) $_REQUEST['sort_id'];
        $uInfo     = M("dk_huati")->save($G);
        if ($uInfo) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }

    //更新排序
    public function doDelHm()
    {
        $G['id']     = $_REQUEST['id'];
        $G['is_del'] = 1;
        $uInfo       = M("homework_submit")->save($G);
        if ($uInfo) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }

    //禁用圈子
    public function disableHuati()
    {
        $Ba['id']     = $_REQUEST['id'];
        $uInfo        = M("dk_huati")->where("id=" . $Ba['id'])->field("status")->find();
        $Ba['status'] = (int) $uInfo['status'] == 0 ? 1 : 0;
        $result       = M("dk_huati")->save($Ba);
        if ($result) {
            exit(json_encode(array(
                "status" => 0,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作失败！",
            )));
        }
    }
    //禁用圈子
    public function zhiding()
    {
        $Ba['id']         = $_REQUEST['id'];
        $h['dk_type']     = $_REQUEST['dk_type'];
        $h['excellent']   = 1;
        $rs               = M('homework_submit')->where($h)->find();
        $qrs['id']        = $rs['id'];
        $qrs['excellent'] = 0;
        M('homework_submit')->save($qrs);
        $uInfo           = M("homework_submit")->where("id=" . $Ba['id'])->field("excellent")->find();
        $Ba['excellent'] = 1;
        $result          = M("homework_submit")->save($Ba);
        if ($result) {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 0,
                "msg"    => "操作失败！",
            )));
        }
    }
    //禁用圈子
    public function nozhiding()
    {
        $Ba['id']        = $_REQUEST['id'];
        $Ba['excellent'] = 0;
        $result          = M("homework_submit")->save($Ba);
        if ($result) {
            exit(json_encode(array(
                "status" => 1,
                "msg"    => "操作成功！",
            )));
        } else {
            exit(json_encode(array(
                "status" => 0,
                "msg"    => "操作失败！",
            )));
        }
    }

    /**
     * Notes:添加打卡背景图
     * User: @lee
     *
     */
    public function addOne()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
//如果是post表单提交

            if (empty($_POST['title'])) {
                $this->error("请上传标题备注");
            }
            if (empty($_POST['description'])) {
                $_POST['description'] = "";
            }
            if (empty($_POST['share_pic'])) {
                $this->error("分享图");
            }
            if (empty($_POST['kf_pic'])) {
                $this->error("分享图");
            }

            $data['title']            = t($_POST['title']);
            $data['ceouser']          = t($_POST['ceouser']);
            $data['share_pic']        = $_POST['share_pic'];
            $data['kf_pic']           = $_POST['kf_pic'];
            $data['share_title']      = $_POST['share_title'];
            $data['description']      = $_POST['description'];
            $data['status']           = $_POST['status'];
            $data['relate_circle_id'] = intval($_POST['relate_circle_id']);
            $data['addtime']          = time();
            $res                      = M('dk_huati')->add($data);
            if ($res) {
                $qrcodedaka        = new QrcodeAction();
                $res1              = $qrcodedaka->getWxcodeHuatiDS($res);
                $res2              = $qrcodedaka->getWxcodeHuatiYY($res);
                $res3              = $qrcodedaka->getWxcodeHuatiDSFang($res);
                $res4              = $qrcodedaka->getWxcodeHuatiYYFang($res);
                $qr['rel_id']      = $res;
                $qr['url_ds']      = $res1;
                $qr['url_yy']      = $res2;
                $qr['url_ds_fang'] = $res3;
                $qr['url_yy_fang'] = $res4;
                $result            = M('dk_huati_qrcode')->add($qr);
                $this->assign('jumpUrl', U('dakaprogram/AdminHuati/huatilist'));
                $this->success('添加成功');
            }
            $this->error('添加失败');
        }
        $_REQUEST['tabHash']           = 'addOne';
        $c17                           = M('dk_circle')->where(['id' => 17])->getField('title');
        $c21                           = M('dk_circle')->where(['id' => 21])->getField('title');
        $this->opt['relate_circle_id'] = array('0' => '不关联', '17' => "{$c17}", '21' => "{$c21}");
        $this->opt['status']           = array('0' => '关闭中', '1' => '开启中');
        $this->pageTitle['addOne']     = '编辑/添加';
        $this->pageTab[]               = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageTab[]               = ['title' => '添加/编辑', 'tabHash' => 'addOne', 'url' => "#"];
        $this->pageKeyList             = array('title', 'ceouser', 'description', 'kf_pic', 'share_pic', 'share_title', 'relate_circle_id', 'status');
        $this->notEmpty                = array('title', 'ceouser', 'kf_pic', 'share_pic', 'status');
        $this->savePostUrl             = U('dakaprogram/AdminHuati/addOne');
        $this->displayConfig();
        $time = date('YmdH');
        echo "<script src='./apps/dakaprogram/_static/js/admin_activity.js?Z={$time}'>
        </script>";
    }

    public function editOne()
    {
        $_REQUEST['tabHash'] = 'editOne';
        $this->pageTab[]     = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageTab[]     = ['title' => '添加/编辑', 'tabHash' => 'editOne', 'url' => "#"];
        $id                  = getR('id', 0);
        if ($id && $_SERVER['REQUEST_METHOD'] == 'POST') {
            //如果是post表单提交

            if (empty($_POST['title'])) {
                $this->error("标题");
            }
            if (empty($_POST['description'])) {
                $_POST['description'] = "";
            }
            if (empty($_POST['share_pic'])) {
                $this->error("分享图");
            }
            if (empty($_POST['kf_pic'])) {
                $this->error("客服二维码");
            }

            $dat1a['title']            = $_POST['title'];
            $dat1a['share_pic']        = $_POST['share_pic'];
            $dat1a['kf_pic']           = $_POST['kf_pic'];
            $dat1a['share_title']      = $_POST['share_title'];
            $dat1a['description']      = $_POST['description'];
            $dat1a['ceouser']          = intval($_POST['ceouser']);
            $dat1a['relate_circle_id'] = intval($_POST['relate_circle_id']);
            $dat1a['status']           = intval($_POST['status']);
            $dat1a['id']               = $id;
            // print_r($dat1a);exit;
            $res = D('dk_huati')->save($dat1a);
            // echo M('dk_huati')->getlastsql();exit;
            if ($res != false) {
                $this->assign('jumpUrl', U('dakaprogram/AdminHuati/huatilist'));
                $this->success('修改成功');
            }

            $this->error('修改失败');
        }
        $dataGin                       = M('dk_huati')->where(['id' => $id])->find();
        $c17                           = M('dk_circle')->where(['id' => 17])->getField('title');
        $c21                           = M('dk_circle')->where(['id' => 21])->getField('title');
        $this->opt['relate_circle_id'] = array('0' => '不关联', '17' => "{$c17}", '21' => "{$c21}");
        $this->opt['status']           = array('0' => '关闭', '1' => '开启');
        $this->opt['mode']             = array('0' => '定时模式', '1' => '随时模式');
        $this->pageKeyList             = array('id', 'title', 'ceouser', 'description', 'kf_pic', 'share_pic', 'share_title', 'relate_circle_id', 'status');
        $this->notEmpty                = array('title', 'ceouser', 'kf_pic', 'share_pic');
        $this->savePostUrl             = U('dakaprogram/AdminHuati/editOne', array('id' => $id));
        $this->displayConfig($dataGin);
        $time = date('YmdH');
        echo "<script src='./apps/dakaprogram/_static/js/admin_activity.js?Z={$time}'>
        </script>";
        echo "<script src='./apps/dakaprogram/_static/js/admin_huatix.js?Z={$time}1'>
        </script>";
    }
    //更新排序
    public function showContent()
    {
        $id   = $_REQUEST['id'];
        $info = M('homework_submit')->find($id);
        $this->assign('info', $info);
        $this->display();
    }
    //更新排序
    public function paneltj()
    {

        $id = $_REQUEST['id'];

        $dataQQ['aid']    = $id;
        $rs[0]['title']   = "*参与人数";
        $rs[0]['num']     = M('dk_activity_user')->where($dataQQ)->count();
        $wc['aid']        = $id;
        $wc['state']      = 3;
        $rs[1]['title']   = "*完成人数";
        $rs[1]['num']     = M('dk_activity_user')->where($wc)->count();
        $d['rel_id']      = $id;
        $d['rel_type']    = "activity_reward";
        $rs[2]['title']   = "*奖励银两";
        $rs[2]['num']     = intval(M('credit_user_flow')->where($d)->SUM('num'));
        $d2['rel_id']     = $id;
        $d2['rel_type']   = "activity_join";
        $rs[3]['title']   = "*报名银两";
        $rs[3]['num']     = intval(M('credit_user_flow')->where($d2)->SUM('num'));
        $dataQQQ['aid']   = $id;
        $dataQQQ['state'] = 2;
        $rs[4]['title']   = "*失败人次";
        $rs[4]['num']     = M('dk_activity_user')->where($dataQQQ)->count();

        $dataQQQq['aid']   = $id;
        $dataQQQq['state'] = 1;
        $rs[5]['title']    = "*进行中";
        $rs[5]['num']      = M('dk_activity_user')->where($dataQQQq)->count();
        $this->assign('rs', $rs);
        $this->assign('info', $info);
        $this->display();
    }
    //更新排序
    public function paneltjall()
    {

        $rs[0]['title'] = "*总计人次";
        $rs[0]['num']   = M('dk_activity_user')->count();

        $rs[1]['num']   = count(M('dk_activity_user')->field('uid')->group('uid')->select());
        $rs[1]['title'] = "*总计人数";

        $d['rel_type']  = "activity_reward";
        $rs[2]['title'] = "*总奖励银两";
        $rs[2]['num']   = intval(M('credit_user_flow')->where($d)->SUM('num'));

        $d2['rel_type'] = "activity_join";
        $rs[3]['title'] = "*总报名银两";
        $rs[3]['num']   = intval(M('credit_user_flow')->where($d2)->SUM('num'));

        $dag['state']        = 2;
        $rs[4]['title']      = "*失败人次";
        $rs[4]['num']        = M('dk_activity_user')->where($dag)->count();
        $dataQQQq['state']   = 1;
        $rs[5]['title']      = "*进行中人次";
        $rs[5]['num']        = M('dk_activity_user')->where($dataQQQq)->count();
        $dataQQQ['state']    = 3;
        $rs[4]['title']      = "*成功人次";
        $rs[4]['num']        = M('dk_activity_user')->where($dataQQQ)->count();
        $where['activityid'] = array('gt', 0);
        $rs[4]['title']      = "*活动总打卡";
        $rs[4]['num']        = M('homework_submit')->where($where)->count();
        $this->assign('rs', $rs);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 设置话题资料分类页面
     * @return void
     */
    public function setHuatiCategoryHtml()
    {
        $albumId = intval($_GET['album_id']);
        $info = M('dk_huati_album')->where(['id' => $albumId])->find();
        if (empty($info)) {
            $this->assign('errorinfo', '参数有误');
            $this->display();
            return ;
        }

        if (is_admin($this->mid)) {
            $this->assign('cid', 0);
        }
        $categories = M('dk_huati_category')->where(['huati_id' => $info['huati_id']])->select();
        $this->assign('categories', $categories);
        $this->assign('currentCategory', $info['huati_category']);
        $this->assign('album_id', $albumId);
        $this->display();
    }

    /**
     * 设置话题资料分类
     */
    public function setHuatiCategory()
    {
        $albumId = intval($_POST['album_id']);
        $huatiCategory = intval($_POST['huati_category']);
        if (empty($albumId)) {
            exit(json_encode(['status' => 0, 'info' => '参数有误']));
        }
        $info = M('dk_huati_album')->where(['id' => $albumId])->find();
        if (empty($info)) {
            exit(json_encode(['status' => 0, 'info' => '信息有误']));
        }
        $uData = [
            'id' => $info['id'],
            'huati_category' => $huatiCategory,
        ];
        $r = M('dk_huati_album')->save($uData);
        $sql = "UPDATE `el_dk_huati_category` AS `hc`, (SELECT `huati_category`, COUNT(*) AS `count` FROM `el_dk_huati_album` WHERE `huati_category` > 0 GROUP BY `huati_category`) AS `ha` SET `hc`.`album_num` = `ha`.`count` WHERE `hc`.`id` = `ha`.`huati_category`;";
        $infos = M()->query($sql);
        exit(json_encode(['status' => 1, 'info' => '设置成功']));
    }

    /**
     * 添加话题资料分类页面
     * @return void
     */
    public function saveHuatiCategoryHtml()
    {
        $huatiId = intval($_GET['huati_id']);
        $id = intval($_GET['id']);
        if (!empty($id)) {
            $info = M('dk_huati_category')->where(['id' => $id])->find();
            if (!empty($info)) {
                $this->assign('id', $id);
                $this->assign('title', $info['title']);
                $huatiId = $info['huati_id'];
            }
        }
        $this->assign('huati_id', $huatiId);
        if (is_admin($this->mid)) {
            $this->assign('cid', 0);
        }
        $this->display();
    }

    /**
     * 保存话题资料分类
     */
    public function saveHuatiCategory()
    {
        $id = intval($_POST['id']);
        $title = strval($_POST['title']);
        if (!empty($id)) {
            $info = M('dk_huati_category')->where(['id' => $id])->find();
            if (empty($info)) {
                exit(json_encode(['status' => 0, 'info' => '信息有误']));
            }
            $uData = ['id' => $info['id']];
            if (!empty($title)) {
                $exist = M('dk_huati_category')->where("huati_id = {$info['huati_id']} AND title ='{$title}' AND id != {$info['id']}")->find();
                if ($exist) {
                    exit(json_encode(['status' => 0, 'info' => '对应分类名已存在']));
                }
                $uData['title'] = $title;
            }
            if (isset($_REQUEST['sort_id'])) {
                $uData['sort_id'] = intval($_REQUEST['sort_id']);
            }
            M('dk_huati_category')->save($uData);
            exit(json_encode(['status' => 1, 'info' => '编辑成功']));
        }

        $huatiId = intval($_POST['huati_id']);
        if (empty($title) || empty($huatiId)) {
            exit(json_encode(['status' => 0, 'info' => '参数有误']));
        }

        $aData = ['huati_id' => $huatiId, 'title' => $title];
        $exist = M('dk_huati_category')->where($aData)->find();
        if (!empty($exist)) {
            exit(json_encode(['status' => 0, 'info' => '信息已存在']));
        }
        $r = M('dk_huati_category')->add($aData);
        exit(json_encode(['status' => 1, 'info' => '添加成功']));
    }

    // 删除话题分类
    public function delHuatiCategory()
    {
        $id = $_REQUEST['id'];
        $where = ['id' => $id];
        $info = M('dk_huati_category')->where($where)->find();
        if (empty($info)) {
            exit(json_encode(['status' => 0, 'info' => '信息不存在']));
        }

        M('dk_huati_album')->where(['huati_category' => $info['id']])->save(['huati_category' => 0]);
		$r = M('dk_huati_category')->where($where)->delete();
        exit(json_encode(['status' => 1, 'info' => '删除成功']));
    }

    // 话题资料分类
    public function ziliaoCategory()
    {
        $huati_id           = $map['huati_id']           = $_REQUEST['id'];
        $this->pageTab[] = ['title' => '资料列表', 'tabHash' => 'ziliaolist', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=ziliao&id={$huati_id}"];
        $this->pageTab[] = ['title' => '资料分类', 'tabHash' => 'categorylist', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=ziliaoCategory&id={$huati_id}"];
        //页面列表默认展示的表头参数
        $this->pageButton[] = array('title' => '+添加', 'onclick' => "saveHuatiCategory({$huati_id})");

        $this->allSelected = false;

        $this->pageKeyList = ['id', 'title', 'sort_id', 'created_at', 'DOACTION'];
        $map['huati_id']   = $huati_id;
        $homeWorkInfo      = M("dk_huati_category")->where($map)->order("sort_id asc,id asc")->findPage(10);
        foreach ($homeWorkInfo['data'] as $key => $value) {
            $stype = $this->albumField($value['album_id'], 'stype');
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="saveHuatiCategory(' . $value['huati_id'] . ',' . $value['id'] . ')">编辑</a> ';
            $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="delHuatiCategory(' . $value['id'] . ')">删除</a> ';
            $homeWorkInfo['data'][$key]['sort_id']     = "<input type='text' id='sortid_$value[id]' onBlur='editHuatiCategorySort($value[id],$value[sort_id])' value='$value[sort_id]' style='width:40px'";
        }

        $this->displayList($homeWorkInfo);
        $time = time();
        echo "<script src='./apps/dakaprogram/_static/js/admin_ziliao.js?v={$time}1661'>
        </script>";
    }

    public function getHuatiCategory($categoryId)
    {
        // ALTER TABLE `el_dk_huati_album` ADD `huati_category` INT(11) NOT NULL DEFAULT '0' COMMENT '话题分类ID' AFTER `huati_id`;
        if (empty($categoryId)) {
            return '';
        }
        $info = M('dk_huati_category')->where(['id' => $categoryId])->find();
        return empty($info) ? '' : $info['title'];
    }
}
