<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;

/**
 * 打卡小程序精选接口
 * @author   @lee
 * @version shufadaka1.0
 */
class HomeindexAction extends ApiTokenAction
{

    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序圈子列表，更多圈子
     */
    public function indexInit()
    {
        $uid         = $_REQUEST['mid'] > 0 ? $_REQUEST['mid'] : $_REQUEST['uid'];
        $data['uid'] = $uid;
        $rs          = M('dk_usercircle')->where($data)->find();
        if (empty($rs)) {
            $peizhi['iscourse'] = 0;
            $peizhi['tol_days'] = 0;
        } else {
            $rsk                = M('homework_daka_new')->where($data)->getField('tol_days');
            $peizhi['iscourse'] = 1;
            $peizhi['tol_days'] = intval($rsk);
        }

        $credit = M("credit_user")->where(['uid' => $uid])->find();
        if ($credit['score_total']) {
            //可用银两
            $peizhi['score']          = isset($credit['score']) ? intval($credit['score']) : 0;
            $sql                      = "select * from  el_dk_credit_level where {$credit['score_total']} >=  scorelow and {$credit['score_total']} <=  scorehigh ";
            $row                      = M()->query($sql);
            $peizhi['level']          = $row[0];
            $peizhi['level']['image'] = getImageUrlByAttachId($peizhi['level']['levelpic']);
        } else {
            $peizhi['score'] = 0;
            $peizhi['level'] = '';
        }

        $bid = M("dk_advertising")->where(['position' => 1])->getField("id");

        $list = M("dk_advertising")->where(['pid' => $bid, 'is_use' => 1, 'status' => 0])->field("pic,jump_url,jump_type")->order(" sort asc ")->select();

        foreach ($list as &$v) {
            $v['image'] = getImageUrlByAttachId($v['pic']);
        }
        $peizhi['banner'] = $list;
        $result['data']   = $peizhi;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    private function getUserCredit($uid)
    {

        $credit = M("credit_user")->where(['uid' => $uid])->find();

        //可用银两
        $return['score']       = isset($credit['score']) ? $credit['score'] : 0;
        $sql                   = "select * from  el_dk_credit_level where {$credit['score_total']} >=  scorelow and {$credit['score_total']} <=  scorehigh ";
        $row                   = M()->query($sql);
        $return['level']       = $row[0];
        $return['score_total'] = $row[0];

        return $return;

    }

    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序首页列表最新8条精选打卡帖子，2个圈子
     */
    public function newlist()
    {

        $uid            = intval($_REQUEST['mid']);
        $result['data'] = array();
        $rs             = M('homework_submit')->field('id,uid,img_url as img,content as title,atime')->where('is_show=1 and id>210000')->order('id desc')->findPage(8);
        //echo M('homework_submit')->getlastsql();
        foreach ($rs['data'] as $skey => $rValue) {
            // //加入人数，先随机@lee
            // $rand_count=rand(1,20000);

            // $times="15470".rand(1,99999);
            // if($rand_count>=10000){
            //     $rand_count=$rand_count/10000;
            //     $rand_count=sprintf("%.2f",$rand_count)."w";
            // }
            $rs['data'][$skey]['zan_count']     = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['is_collection'] = D('Circles')->isCollection($rValue['id'], $uid);
            $rs['data'][$skey]['is_zan']        = D('Circles')->isPraise($rValue['id'], $uid);
            $rs['data'][$skey]['imageindex']    = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['img_remark']    = D('Circles')->getRemarkImage_url($rValue['img']);
            $rs['data'][$skey]['sex']           = D('Circles')->getUserSex($rValue['uid']);
            $rs['data'][$skey]['uid']           = $rValue['uid'];
            $rs['data'][$skey]['time_remark']   = D('Circles')->timeDecode($rValue['atime']);
            $rs['data'][$skey]['img_avatar']    = D('Members')->getUserfaceNopic($rValue['uid']);
            $rs['data'][$skey]['uname']         = getUserName($rValue['uid']);
            $rs['data'][$skey]['intype']        = "1";
            $rs['data'][$skey]['isjoin']        = 0;
        }
        $dataA['is_del']   = 0;
        $dataA['status']   = 1;
        $dk_circle['data'] = M('dk_circle')->field('id,cover,title')->where($dataA)->order('rand()')->limit(2)->select();
        foreach ($dk_circle['data'] as $dk => $dv) {
            //加入人数，先随机@lee
            $dk_circle['data'][$dk]['isjoin']     = D('Circles')->isjoin($dv['id'], $uid);
            $dk_circle['data'][$dk]['join_num']   = M('dk_usercircle')->field('id')->where(['cid' => $dv['id']])->count();
            $dk_circle['data'][$dk]['daka_num']   = D('Circles')->dakaNum($dv['id']);
            $dk_circle['data'][$dk]['imageindex'] = D('Circles')->getCoverBig($dv['cover']);
            $dk_circle['data'][$dk]['intype']     = "2";
        }

        $result['data']   = array_merge($rs['data'], $dk_circle['data']);
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序精选打卡帖子，2个圈子
     */
    public function listRecommend()
    {

        $uid            = intval($_REQUEST['mid']);
        $result['data'] = array();
        $p              = intval($_REQUEST['p']);
        $page           = "$p,6";
        $result['data'] = array();
        $rs['data']     = M('homework_submit')->field('id,uid,img_url as img,content,atime,cid,img_url_thumb,share,dk_type,activityid,relate_circle_id,course_id,section_id')->where('is_show=1 and recommend=2 and is_del=2')->page($page)->order('id desc')->select();
        if (empty($rs['data'])) {
            $rs['data'] = array();
        }

        $redis = Redis::getInstance();
        foreach ($rs['data'] as $skey => $rValue) {

            $rs['data'][$skey]['zan_count']     = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['countcomment']  = D('Daka')->countComment($rValue['id']);
            $rs['data'][$skey]['is_collection'] = D('Circles')->isCollection($rValue['id'], $uid);
            $rs['data'][$skey]['is_zan']        = D('Circles')->isPraise($rValue['id'], $uid);
            $rs['data'][$skey]['is_follow']     = D('Use')->isFollow($rValue['uid'], $uid);
            $rs['data'][$skey]['imageindex']    = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['imgarray']      = D('Circles')->imgArray($rValue['img']);
            $rs['data'][$skey]['huati']         = D('Huati')->huaInfo($rValue['dk_type']);
            $rs['data'][$skey]['activity']      = D('Huati')->activityInfo($rValue['activityid'], $rValue['dk_type'], $rValue['cid']);
            //$rs['data'][$skey]['img_remark']= D('Circles')->getRemarkImage_url($rValue['img']);

            $rs['data'][$skey]['time_remark'] = D('Circles')->timeDecode($rValue['atime']);
            $rs['data'][$skey]['circleinfo']  = D('Circles')->easycircleinfo($rValue['cid'], $uid);
            //圈子信息
            $rs['data'][$skey]['img_avatar'] = D('Members')->getUserfaceNopic($rValue['uid']);
            $rs['data'][$skey]['uname']      = D('UnameS')->getUserNameCache($rValue['uid']);
//            $tol_days=M('homework_daka_new')->where('uid='.intval($rValue['uid']))->getField('tol_days');
            if ($redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']))) {
                $tol_days = $redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']));
            } else {
                $tol_days = M('homework_daka_new')->where('uid=' . intval($rValue['uid']))->getField('tol_days');
                $redis->set('liupinshuyuan_tol_days_' . intval($rValue['uid']), $tol_days, 1800);
            }
            $rs['data'][$skey]['tol_days']     = intval($tol_days);
            $rs['data'][$skey]['levelname']    = D('Members')->levelname3($rValue['uid']);
            $rs['data'][$skey]['new_tab']      = D('Huati')->tabNewInfo($rValue['cid'], $rValue['dk_type']);
            $rs['data'][$skey]['new_tabs']     = D('Huati')->tabNewInfos($rValue['relate_circle_id'], $rValue['dk_type'], $uid);
            $rs['data'][$skey]['new_activity'] = D('Huati')->newActivityInfo($rValue['activityid']);
            $rs['data'][$skey]['course_tabs']  = D('MiniCourse')->tabCourseInfos($rValue['course_id'], $rValue['uid'], $uid, $rValue['section_id']);

        }
        $result['data']   = $rs['data'];
        $result['status'] = 1;
        echo json_encode($result);exit;
    }
    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序精选打卡帖子，2个圈子
     */
    public function interestnum()
    {
        $mid                           = intval($_REQUEST['mid']);
        $redis                         = Redis::getInstance();
        $liupinshuyuan_interestnum_mid = $redis->get('liupinshuyuan_interestnum_' . $mid);
        if (!empty($liupinshuyuan_interestnum_mid)) {
            $rs             = $liupinshuyuan_interestnum_mid;
            $result['info'] = "查询成功REDIS";
        } else {
            $rs = D('Use')->interestnum($mid);
            $redis->set('liupinshuyuan_interestnum_' . $mid, $rs, 1800);
            $result['info'] = "查询成功MYSQL";
        }
        $result['data']   = $rs;
        $result['status'] = 1;
        echo json_encode($result);exit;
    }
    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序精选打卡帖子，2个圈子
     */
    public function listFollow()
    {
        $uid             = intval($_REQUEST['mid']);
        $dataUser['uid'] = $uid;
        $users           = M('user_follow')->where($dataUser)->field('fid')->select();
        $inuser          = array_column($users, 'fid');
        $result['data']  = array();
        $data['uid']     = array('in', $inuser);
        $data['is_show'] = 1;
        $data['is_del']  = 2;
        $rs              = M('homework_submit')->field('id,uid,img_url as img,content,atime,cid,img_url_thumb,share,dk_type,recommend,activityid,relate_circle_id,course_id,section_id')->where($data)->order('id desc')->findPage(6);
        $p               = $_REQUEST['p'];
        if ($p > $rs['totalPages']) {
            $rs['data'] = array();
        }
        $redis = Redis::getInstance();
        foreach ($rs['data'] as $skey => $rValue) {

            $rs['data'][$skey]['zan_count']     = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['countcomment']  = D('Daka')->countComment($rValue['id']);
            $rs['data'][$skey]['is_collection'] = D('Circles')->isCollection($rValue['id'], $uid);
            $rs['data'][$skey]['is_zan']        = D('Circles')->isPraise($rValue['id'], $uid);
            $rs['data'][$skey]['is_follow']     = D('Use')->isFollow($rValue['uid'], $uid);
            $rs['data'][$skey]['imageindex']    = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['huati']         = D('Huati')->huaInfo($rValue['dk_type']);
            $rs['data'][$skey]['activity']      = D('Huati')->activityInfo($rValue['activityid'], $rValue['dk_type'], $rValue['cid']);
            $rs['data'][$skey]['imgarray']      = D('Circles')->imgArray($rValue['img']);
            //$rs['data'][$skey]['img_remark']= D('Circles')->getRemarkImage_url($rValue['img']);
            $rs['data'][$skey]['time_remark'] = D('Circles')->timeDecode($rValue['atime']);
            $rs['data'][$skey]['circleinfo']  = D('Circles')->easycircleinfo($rValue['cid'], $uid);
            //圈子信息
            $rs['data'][$skey]['img_avatar'] = D('Members')->getUserfaceNopic($rValue['uid']);
            $rs['data'][$skey]['uname']      = D('UnameS')->getUserNameCache($rValue['uid']);
//            $tol_days=M('homework_daka_new')->where('uid='.intval($rValue['uid']))->getField('tol_days');
            if ($redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']))) {
                $tol_days = $redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']));
            } else {
                $tol_days = M('homework_daka_new')->where('uid=' . intval($rValue['uid']))->getField('tol_days');
                $redis->set('liupinshuyuan_tol_days_' . intval($rValue['uid']), $tol_days, 1800);
            }
            $rs['data'][$skey]['tol_days']     = intval($tol_days);
            $credit                            = model('Credit')->_getUserCredit($rValue['uid']);
            $rs['data'][$skey]['score_total']  = intval($credit);
            $rs['data'][$skey]['levelname']    = D('Members')->levelname3($rValue['uid']);
            $rs['data'][$skey]['new_tab']      = D('Huati')->tabNewInfo($rValue['cid'], $rValue['dk_type']);
            $rs['data'][$skey]['new_tabs']     = D('Huati')->tabNewInfos($rValue['relate_circle_id'], $rValue['dk_type'], $uid);
            $rs['data'][$skey]['new_activity'] = D('Huati')->newActivityInfo($rValue['activityid']);
            $rs['data'][$skey]['course_tabs']  = D('MiniCourse')->tabCourseInfos($rValue['course_id'], $rValue['uid'], $uid, $rValue['section_id']);

        }
        $result['data']   = $rs['data'];
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }
    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序精选打卡帖子，2个圈子
     */
    public function listNew()
    {

        $uid            = intval($_REQUEST['mid']);
        $p              = intval($_REQUEST['p']);
        $page           = "$p,6";
        $result['data'] = array();
        $rs['data']     = M('homework_submit')->field('id,uid,img_url as img,content,atime,cid,img_url_thumb,share,dk_type,recommend,activityid,relate_circle_id,course_id,section_id')->where('is_show=1 and is_del=2')->page($page)->order('id desc')->select();
        if (empty($rs['data'])) {
            $rs['data'] = array();
        }

        //$r= M()->getlastsql();
        $redis = Redis::getInstance();
        foreach ($rs['data'] as $skey => $rValue) {

            $rs['data'][$skey]['zan_count']     = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['countcomment']  = D('Daka')->countComment($rValue['id']);
            $rs['data'][$skey]['is_collection'] = D('Circles')->isCollection($rValue['id'], $uid);
            $rs['data'][$skey]['is_zan']        = D('Circles')->isPraise($rValue['id'], $uid);
            $rs['data'][$skey]['is_follow']     = D('Use')->isFollow($rValue['uid'], $uid);
            $rs['data'][$skey]['imageindex']    = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['imgarray']      = D('Circles')->imgArray($rValue['img']);
            $rs['data'][$skey]['huati']         = D('Huati')->huaInfo($rValue['dk_type']);
            $rs['data'][$skey]['activity']      = D('Huati')->activityInfo($rValue['activityid'], $rValue['dk_type'], $rValue['cid']);
            //$rs['data'][$skey]['img_remark']= D('Circles')->getRemarkImage_url($rValue['img']);
            $rs['data'][$skey]['time_remark'] = D('Circles')->timeDecode($rValue['atime']);
            $rs['data'][$skey]['circleinfo']  = D('Circles')->easycircleinfo($rValue['cid'], $uid);
            //圈子信息
            $rs['data'][$skey]['img_avatar'] = D('Members')->getUserfaceNopic($rValue['uid']);
            $rs['data'][$skey]['uname']      = D('UnameS')->getUserNameCache($rValue['uid']);
//            $tol_days=M('homework_daka_new')->where('uid='.intval($rValue['uid']))->getField('tol_days');
            if ($redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']))) {
                $tol_days = $redis->get('liupinshuyuan_tol_days_' . intval($rValue['uid']));
            } else {
                $tol_days = M('homework_daka_new')->where('uid=' . intval($rValue['uid']))->getField('tol_days');
                $redis->set('liupinshuyuan_tol_days_' . intval($rValue['uid']), $tol_days, 1800);
            }
            $rs['data'][$skey]['tol_days']     = intval($tol_days);
            $rs['data'][$skey]['levelname']    = D('Members')->levelname3($rValue['uid']);
            $rs['data'][$skey]['new_tab']      = D('Huati')->tabNewInfo($rValue['cid'], $rValue['dk_type']);
            $rs['data'][$skey]['new_tabs']     = D('Huati')->tabNewInfos($rValue['relate_circle_id'], $rValue['dk_type'], $uid);
            $rs['data'][$skey]['new_activity'] = D('Huati')->newActivityInfo($rValue['activityid']);
            $rs['data'][$skey]['course_tabs']  = D('MiniCourse')->tabCourseInfos($rValue['course_id'], $rValue['uid'], $uid, $rValue['section_id']);

        }
        $result['data']   = $rs['data'];
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序首页列表精选8条最新打卡帖子，2个圈子
     */
    public function newlistf()
    {

        $uid            = $_REQUEST['uid']            = 90216;
        $result['data'] = array();
        $rs             = M('homework_submit')->field('id,uid,img_url as img,content as title,atime')->order('atime desc')->findPage(8);
        foreach ($rs['data'] as $skey => $rValue) {
            $rs['data'][$skey]['zan_count']   = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['imageindex']  = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['img_remark']  = D('Circles')->getRemarkImage_url($rValue['img']);
            $rs['data'][$skey]['sex']         = D('Circles')->getUserSex($rValue['uid']);
            $rs['data'][$skey]['time_remark'] = D('Circles')->timeDecode($rValue['atime']);
            $rs['data'][$skey]['img_avatar']  = getUserface($rValue['uid'], "b");
            $rs['data'][$skey]['intype']      = "1";
        }
        $dk_circle = M('dk_circle')->field('id,cover,title')->order('id desc')->findPage(2);
        foreach ($dk_circle['data'] as $dk => $dV) {
            //加入人数，先随机@lee
            $dk_circle['data'][$dk]['join_num']   = M('dk_usercircle')->field('id')->where(['cid' => $dV['cid']])->count();
            $dk_circle['data'][$dk]['daka_num']   = D('Circles')->dakaNum($dV['cid']);
            $dk_circle['data'][$dk]['imageindex'] = D('Circles')->getCoverBig($dv['cover']);
            $dk_circle['data'][$dk]['intype']     = "2";
        }

        $result['data']   = array_merge($rs['data'], $dk_circle['data']);
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    /**
     * Created by
     * @author: @lee
     * @todo: 打卡小程序圈子列表，更多圈子
     */
    public function mycircle()
    {
        $dataA['uid']   = $uid   = $_REQUEST['mid'];
        $data['is_del'] = 0;
        $data['status'] = 1;
        // $sqlCircle="SELECT u.cid,c.title,c.cover from el_dk_usercircle  as u LEFT JOIN el_dk_circle as c on u.cid=c.id where u.uid=".intval($uid)." order by u.id desc limit 6";
        $rs = M('dk_usercircle')->field('cid')->where($dataA)->order('updatetime desc')->limit(6)->select();
        //$rs=M()->query($sqlCircle);
        foreach ($rs as $skey => $svalue) {
            $circle           = M('dk_circle')->field('title,id,cover,iscontent')->find($svalue['cid']);
            $condition['cid'] = $svalue['cid'];
            // $rs[$skey]['daka_num']=M('homework_submit')->field('id')->where($condition)->count();//卡100
            // $rs[$skey]['join_num']=M('dk_usercircle')->field('id')->where($condition)->count();
            $rs[$skey]['imgindex']  = D('Circles')->getCoverBig(intval($circle['cover']));
            $rs[$skey]['iscontent'] = $circle['iscontent'];
            $rs[$skey]['title']     = $circle['title'];
            $rs[$skey]['id']        = $svalue['cid'];
            //$rs[$skey]['progress_remark']=D('Circles')->get_progress_remark($uid,$svalue['cid']);;//卡100
        }
        // if(empty($rs)){
        //    $rs= M('dk_circle')->field('id,cover,title,iscontent')->where($data)->order('ishome desc,id')->limit(2)->select();
        //    foreach ($rs as $key => $value) {
        //       $condition['cid']=$value['id'];
        //     $rs[$key]['imgindex']=D('Circles')->getCoverBig($value['cover']);
        //        $rs[$key]['daka_num']=D('Circles')->dakaNum($value['id']);
        //        $rs[$key]['join_num']=M('dk_usercircle')->field('id')->where($condition)->count();
        //        $rs[$key]['iscontent']=$value['iscontent'];
        //    }
        // }
        $result['data']   = $rs;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    /**
     * 换算日期
     * @param $timestamp 时间戳
     *
     * @return false|string
     */
    public function timeDecode($timestamp)
    {
        $now       = time();
        $last_year = strtotime('-1 year');
        $diff_time = $now - $timestamp;
        switch (true) {
            case $diff_time < 3600 * 24; //24h以内
                //几分钟以前
                if ($diff_time < 60) {
                    return '刚刚';
                } elseif ($diff_time >= 60 && $diff_time < 3600) {
                    return floor($diff_time / 60) . '分钟前';
                } elseif ($diff_time >= 3600) {
                    return floor($diff_time / 3600) . '小时前';
                }
                break;
            case $diff_time > 3600 * 24 && $diff_time <= $last_year; //24小时以外 一年以内
                return date('n月j日', $timestamp);
                break;
            case $diff_time > $last_year; //一年以外

                return date('Y年n月j日', $timestamp);
                break;
        }
        return '';
    }

    public function recommendCourses()
    {
        $mid = intval($_REQUEST['mid']);
        $myCourse = false;
        if (!empty($mid)) {
            $myCourse = M('mini_course_user')->where(['uid' => $mid])->find();
        }

        if (empty($myCourse)) {
            $result = [
                'status' => 1,
                'info' => 'ok',
                'noCourse' => 1,
                'data' => $this->defaultRecommendCourses(),
            ];
            echo json_encode($result);exit;
        }
        $result = [
            'status' => 1,
            'info' => 'ok',
            'noCourse' => 1,
            'data' => $this->formatMyCourse($myCourse),
        ];
        echo json_encode($result);exit;

    }

    protected function formatMyCourse($myCourse)
    {
        return [
            'courseId' => 1,
            'courseName' => '李六军硬笔楷书课',
            'coverUrl' => 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/asset/pc/2023/04/27/%E5%B0%81%E9%9D%A2.png',
            'videoUrl' => 'https://1254153797.vod2.myqcloud.com/41f91735vodsh1254153797/333a6113243791581806610575/Yag6fsAESaIA.mp4',
            'fileid' => '3270835009025764900',
        ];
    }

    protected function defaultRecommendCourses()
    {
        return [
            'regular' => [
                'name' => '楷书精品课',
                'courseInfo' => [
                    'courseId' => 1,
                    'courseName' => '李六军硬笔楷书课',
                    'coverUrl' => 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/asset/pc/2023/04/27/%E5%B0%81%E9%9D%A2.png',
                    'videoUrl' => 'https://1254153797.vod2.myqcloud.com/41f91735vodsh1254153797/333a6113243791581806610575/Yag6fsAESaIA.mp4',
                    'fileid' => '3270835009025764900',
                ],
            ],
            'runningRegular' => [
                'name' => '行楷精品课',
                'courseInfo' => [
                    'courseId' => 1,
                    'courseName' => '李六军硬笔行楷课',
                    'coverUrl' => 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/asset/pc/2023/04/27/56.jpg',
                    'videoUrl' => 'https://1254153797.vod2.myqcloud.com/41f91735vodsh1254153797/333a6113243791581806610575/Yag6fsAESaIA.mp4',
                    'fileid' => '3270835009025764900',
                ],
            ],
            'cursive' => [
                'name' => '行书精品课',
                'courseInfo' => [
                    'courseId' => 1,
                    'courseName' => '李六军硬笔行书课',
                    'coverUrl' => 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/asset/pc/2023/04/27/%E5%B0%81%E9%9D%A2.png',
                    'videoUrl' => 'https://1254153797.vod2.myqcloud.com/41f91735vodsh1254153797/333a6113243791581806610575/Yag6fsAESaIA.mp4',
                    'fileid' => '3270835009025764900',
                ],
            ],
        ];
    }
}
