<?php
namespace App\dakaprogram\Lib\Action;

use App\admin\Lib\Action\AdministratorAction;

class AdminMzAction extends AdministratorAction
{
    public function _initialize()
    {
        //页面标签参数配置
        parent::_initialize();
    }
    public function listHuatiBox()
    {
        //页面列表默认展示的表头参数
        $id                = $_REQUEST['id'];
        $this->pageTab[]   = ['title' => '话题列表', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=AdminHuati&act=huatilist"];
        $this->pageKeyList = ['id', 'title', 'coverurl', 'sort_id', 'is_publish', 'updated_at', 'DOACTION'];

        $r1                 = M('dk_huati_box')->where(['huati_id' => $id, 'location' => 2, 'is_publish' => 1])->count();
        $r2                 = M('dk_huati_box')->where(['huati_id' => $id, 'location' => 1, 'is_publish' => 1])->count();
        $title              = $r1 >= 2 && $r2 >= 1 ? "<font color=white>banner正常展示中</font>" : "<font color=white><b>全部左轮播位”禁用或“右推广位”未全部启用，不予展示banne</b>r</font>";
        $this->pageButton[] = array(
            'title'   => $title,
            'onclick' => "window.location.href='#'",
        );
        $map = [];
        $rse = M('dk_huati_box')->where(['huati_id' => $id])->find();
        if (empty($rse)) {
            $this->initHuatiBox($id);
            /*$dataw1['huati_id']   = $id;
            $dataw1['title']      = "左推广位";
            $dataw1['location']   = 1;
            $dataw1['is_publish'] = 0;
            M('dk_huati_box')->add($dataw1);

            $dataw2['huati_id']   = $id;
            $dataw2['location']   = 2;
            $dataw2['title']      = "右轮播位";
            $dataw2['is_publish'] = 0;
            M('dk_huati_box')->add($dataw2);

            $dataw3['huati_id']   = $id;
            $dataw3['location']   = 2;
            $dataw3['title']      = "右轮播位";
            $dataw3['is_publish'] = 0;
            M('dk_huati_box')->add($dataw3);

            $dataw4['huati_id']   = $id;
            $dataw4['location']   = 2;
            $dataw4['title']      = "右轮播位";
            $dataw4['is_publish'] = 0;
            M('dk_huati_box')->add($dataw4);*/
        }
        $homeWorkInfo['data'] = M('dk_huati_box')->where(['huati_id' => $id])->order('location desc ,sort_id desc,id asc')->select();
        foreach ($homeWorkInfo['data'] as $k => $v) {
            $homeWorkInfo['data'][$k]['id']         = $v['id'];
            $homeWorkInfo['data'][$k]['title']      = $v['title'];
            $homeWorkInfo['data'][$k]['coverurl']   = "<img src='" . getImageUrlByAttachId($v['cover']) . "' height='60' alt='' />";
            $homeWorkInfo['data'][$k]['sort_id']    = $v['location'] == 1 ? "<input type='text' id='sortid_$v[id]' onBlur='editThisSort($v[id],$v[sort_id])' value='$v[sort_id]' style='width:40px'" : '';
            $homeWorkInfo['data'][$k]['is_publish'] = $v['is_publish'] == 0 ? '<font color="red">OFF</font>' : '<font color="green">ON</font>';
            $homeWorkInfo['data'][$k]['switch']     = $v['is_publish'] == 1 ? '禁用' : '开启';
            $homeWorkInfo['data'][$k]['DOACTION']   = '<a href="' . U('dakaprogram/AdminMz/editHuatiBannerBox', array(
                'tabHash' => 'editBannerBox',
                'id'      => $v['id'],
            )) . '">' . '编辑' . '</a> |';
            $homeWorkInfo['data'][$k]['DOACTION'] .= " <a href='javascript:;' onclick='disableBanner({$v['id']},$id)'>" . $homeWorkInfo['data'][$k]['switch'] . "</a>  ";
        }
        //print_r($homeWorkInfo['data']);
        $this->displayList($homeWorkInfo);
        $rand = rand(0, 1000);
        echo "<script src='./apps/dakaprogram/_static/js/admin_mz.js?Z={$rand}'>
        </script>";
    }
    //更新排序
    public function editThisSort()
    {
        $G['id']      = $_REQUEST['id'];
        $G['sort_id'] = (int) $_REQUEST['sort_id'];
        $uInfo        = M("dk_huati_box")->save($G);
        if ($uInfo) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败")));
        }
    }
    public function editHuatiBannerBox()
    {
        $_REQUEST['tabHash'] = 'editHuatiBannerBox';
        $id                  = getR('id', 0);

        $this->pageTab[]   = ['title' => '编辑话题Banner', 'tabHash' => 'index', 'url' => "#"];
        $this->pageKeyList = ['cover', 'type', 'jump_url'];

        if ($id && $_SERVER['REQUEST_METHOD'] == 'POST') {

            if (empty($_POST['cover'])) {
                $this->error("请上传广告图片");
            }
            if (empty($_POST['jump_url'])) {
                $this->error('请输入跳转地址');
            }
            $ary['cover']    = $_POST['cover'];
            $ary['jump_url'] = t($_POST['jump_url']);
            $ary['type']     = t($_POST['type']);
            $ary['id']       = $id;
            //print_r($ary);exit;
            $res      = M('dk_huati_box')->save($ary);
            $huati_id = M('dk_huati_box')->where(['id' => $ary['id']])->getField('huati_id');
            if ($res != false) {
                $this->assign('jumpUrl', U('dakaprogram/AdminMz/listHuatiBox', array(
                    'id' => $huati_id,
                )));
                $this->success('修改成功');
            }
            $this->error('修改失败');
        }

        $this->notEmpty = array(
            'cover',
            'jump_url',
        );
        $this->savePostUrl = U('dakaprogram/AdminMz/editHuatiBannerBox', array(
            'id' => $id,
        ));
        $dataGin           = M('dk_huati_box')->where(['id' => $id])->find();
        $this->opt['type'] = array('0' => '请选择跳转类型', '1' => '站内跳转', '2' => 'H5');
        $this->displayConfig($dataGin);
    }
    public function listTcBox()
    {
        //页面列表默认展示的表头参数
        $cid               = $_REQUEST['circle_id'];
        $this->pageTab[]   = ['title' => '产品圈子', 'tabHash' => 'index', 'url' => "/index.php?app=dakaprogram&mod=Admindaka&act=index"];
        $this->pageKeyList = ['id', 'title', 'coverurl', 'is_publish', 'updated_at', 'DOACTION'];

        $map = [];
        $rse = M('mini_circle_tcbox')->where(['circle_id' => $cid])->find();
        if (empty($rse)) {
            $dataw['circle_id']  = $cid;
            $dataw['is_publish'] = 0;
            M('mini_circle_tcbox')->add($dataw);
        }

        $homeWorkInfo['data'] = M('mini_circle_tcbox')->where(['circle_id' => $cid])->select();

        foreach ($homeWorkInfo['data'] as $k => $v) {
            $homeWorkInfo['data'][$k]['id']         = $v['id'];
            $homeWorkInfo['data'][$k]['title']      = $v['title'];
            $homeWorkInfo['data'][$k]['coverurl']   = "<img src='" . getImageUrlByAttachId($v['cover']) . "' height='60' alt='' />";
            $homeWorkInfo['data'][$k]['is_publish'] = $v['is_publish'] == 0 ? '<font color="red">OFF</font>' : '<font color="green">ON</font>';
            $homeWorkInfo['data'][$k]['switch']     = $v['is_publish'] == 1 ? '禁用' : '开启';
            $homeWorkInfo['data'][$k]['DOACTION']   = '<a href="' . U('dakaprogram/AdminMz/editTcBox', array(
                'tabHash' => 'editTcBox',
                'id'      => $v['id'],
            )) . '">' . '编辑' . '</a> |';
            $homeWorkInfo['data'][$k]['DOACTION'] .= " <a href='javascript:;' onclick='circle_disabletc({$v['id']},$cid)'>" . $homeWorkInfo['data'][$k]['switch'] . "</a>  ";
            // $homeWorkInfo['data'][$k]['DOACTION'].= " <a href='javascript:;' onclick='admin.del_adverList({$v['id']},$pid)'>删除</a>";
        }
        $this->displayList($homeWorkInfo);
        echo "<script src='./apps/dakaprogram/_static/js/admin_mz.js?Z=6'>
        </script>";
    }
    public function disableBanner()
    {
        $Ba['id']         = $_REQUEST['id'];
        $uInfo            = M("dk_huati_box")->where("id=" . $Ba['id'])->find();
        $Ba['is_publish'] = (int) $uInfo['is_publish'] == 1 ? 0 : 1;
        if ($Ba['is_publish'] == 1) {
            if (empty($uInfo['cover']) || empty($uInfo['jump_url'])) {
                exit(json_encode(array("status" => 0, "msg" => "ERROR:资源和链接非空")));
            }
        }
        $result = M("dk_huati_box")->save($Ba);
        if ($result) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败！")));
        }

    }
    public function disablecircletc()
    {
        $Ba['id']         = $_REQUEST['id'];
        $uInfo            = M("mini_circle_tcbox")->where("id=" . $Ba['id'])->field("is_publish")->find();
        $Ba['is_publish'] = (int) $uInfo['is_publish'] == 1 ? 0 : 1;

        $result = M("mini_circle_tcbox")->save($Ba);
        if ($result) {
            exit(json_encode(array("status" => 1, "msg" => "操作成功！")));
        } else {
            exit(json_encode(array("status" => 0, "msg" => "操作失败！")));
        }

    }

    public function editTcBox()
    {
        $_REQUEST['tabHash'] = 'editTcBox';
        $id                  = getR('id', 0);

        $this->pageTab[]   = ['title' => '编辑弹窗广告', 'tabHash' => 'index', 'url' => "#"];
        $this->pageKeyList = ['cover', 'jump_url'];

        if ($id && $_SERVER['REQUEST_METHOD'] == 'POST') {

            if (empty($_POST['cover'])) {
                $this->error("请上传广告图片");
            }

            if (empty($_POST['jump_url'])) {
                $this->error('请输入跳转地址');
            }
            $ary['cover']    = $_POST['cover'];
            $ary['jump_url'] = t($_POST['jump_url']);
            $ary['id']       = $id;
            //print_r($ary);exit;
            $res = M('mini_circle_tcbox')->save($ary);
            $cid = M('mini_circle_tcbox')->where(['id' => $ary['id']])->getField('circle_id');
            if ($res != false) {
                $this->assign('jumpUrl', U('dakaprogram/AdminMz/listTcBox', array(
                    'circle_id' => $cid,
                )));
                $this->success('修改成功');
            }
            $this->error('修改失败');
        }

        $this->notEmpty = array(
            'cover',
            'jump_url',
        );
        $this->savePostUrl = U('dakaprogram/AdminMz/editTcBox', array(
            'id' => $id,
        ));
        $dataGin = M('mini_circle_tcbox')->where(['id' => $id])->find();
        $this->displayConfig($dataGin);
    }

    //首页@lee
    public function listcirclecourse()
    {

        //页面列表默认展示的表头参数
        $circle_id          = $_REQUEST['circle_id'];
        $this->pageButton[] = array('title' => '+添加课程', 'onclick' => "addCicleCourseTreeCategory({$circle_id})");

        $this->allSelected = false;

        $this->pageKeyList = ['id', 'course_title', 'updated_at', 'DOACTION'];
        $map['circle_id']  = $circle_id;
        $map['is_del']     = 0;
        $homeWorkInfo      = M("mini_circle_course")->where($map)->order("created_at desc")->findPage(10);
        foreach ($homeWorkInfo['data'] as $key => $value) {
            if ($value['is_del'] == 0) {
                $homeWorkInfo['data'][$key]['DOACTION'] .= '<a href="javascript:void(0)" onclick="delCircleCourse(' . $value['id'] . ')">移除</a> ';
            }
            $mapwhere['id']                             = $value['mini_course_id'];
            $homeWorkInfo['data'][$key]['course_title'] = M("mini_course")->where($mapwhere)->getField("course_title");

        }
        $this->displayList($homeWorkInfo);
        $time = time();
        echo "<script src='./apps/dakaprogram/_static/js/admin_mz.js?Z={$time}5345'>
        </script>";
    }
    //禁用圈子
    public function delcirclecourse()
    {
        $Ba['id']     = $_REQUEST['id'];
        $uInfo        = M("mini_circle_course")->where("id=" . $Ba['id'])->field("is_del")->find();
        $Ba['is_del'] = (int) $uInfo['is_del'] == 0 ? 1 : 0;
        $result       = M("mini_circle_course")->save($Ba);
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
    public function addcirclecourseHtml()
    {
        $id = intval($_GET['id']);
        $this->assign('circle_id', $id);
        if (is_admin($this->mid)) {
            $this->assign('cid', 0);
        }
        $this->display();
    }
    //视频库
    public function getCourseList()
    {

        $map['id']         = array('gt', 0);
        $map['is_publish'] = 1;
        $total             = M('mini_course')->where($map)->count(); //总记录数
        $page              = intval($_POST['pageNum']); //当前页
        $pageSize          = 10; //每页显示数
        $totalPage         = ceil($total / $pageSize); //总页数

        $startPage = $page * $pageSize; //开始记录
        //构造数组
        $list['total']     = $total;
        $list['pageSize']  = $pageSize;
        $list['totalPage'] = $totalPage;

        $list['data'] = M('mini_course')->where($map)->order('created_at desc')->limit("{$startPage} , {$pageSize}")->findAll();
        // foreach ($list['data'] as &$val) {
        //     $val['type']  = $type_text[$val['type']];
        //     $val['stype'] = $type_text[$val['stype']];
        //     $val['uid']   = getUserName($val['uid']);
        //     $val['ctime'] = date('Y-m-d', $val['ctime']);
        // }
        exit(json_encode($list));
    }

    public function doCheckCircleCourse()
    {
        $circle_id      = $dta['circle_id']      = intval($_REQUEST['circle_id']);
        $mini_course_id = $dta['mini_course_id'] = intval($_REQUEST['mini_course_id']);
        $rss            = M('mini_circle_course')->where($dta)->find();
        if (empty($circle_id) || empty($mini_course_id)) {
            $res['status'] = 0;
            $res['data']   = '参数为空';
            exit(json_encode($res));
        }
        if (empty($rss)) {
            M('mini_circle_course')->add($dta);
        } else {
            $data['is_del'] = 0;
            $data['id']     = $rss['id'];
            M('mini_circle_course')->save($data);
        }
        $res['status'] = 1;
        $res['data']   = '添加成功';
        exit(json_encode($res));
    }

    protected function initHuatiBox($id)
    {
        $i = 5;
        foreach ([2 => ['右上推广位', '右下推广位'], 1 => ['左轮播位', '左轮播位', '左轮播位']] as $location => $ads) {
            foreach ($ads as $aTitle) {
                $aData = [
                    'huati_id' => $id,
                    'title' => $aTitle,
                    'location' => $location,
                    'is_publish' => 0,
                    'sort_id' => $i,
                ];
                M('dk_huati_box')->add($aData);
                $i--;
            }
        }
        return true;
    }
}
