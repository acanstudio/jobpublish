<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;

class MinicourseAction extends ApiTokenAction
{
    public function getcourse()
    {
        $id                           = intval($_REQUEST['id']);
        $mid                          = $uid                          = intval($_REQUEST['mid']);
        $rs                           = M('mini_course')->find($id);
        $ajaxreturn['id']             = $rs['id'];

        $low_price = $this->lowPrice($id);
        $high_price     = $this->highPrice($id);

        // coupon-info v3.0.2
        $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
        $realLowPrice = $this->realLowPrice($id, $uid);
        $couponData = $model->courseCouponInfo($mid, 0);
        if (empty($realLowPrice)) {
            $couponData['coupon_title'] = '';
            $ajaxreturn['coupon_info'] = $couponData;
        } else {
            $realHighPrice = $this->realHighPrice($id, $uid);
            $lowCoupon = $model->courseCouponInfo($mid, $realLowPrice);
            $highCoupon = $realLowPrice == $realHighPrice ? $lowCoupon : $model->courseCouponInfo($mid, $realHighPrice);
            if ($lowCoupon['coupon_num']) {
                $low_price = $realLowPrice - $lowCoupon['max_discount'];
                $low_price = $low_price <= 0 ? 0.01 : $low_price;
            }
            if ($highCoupon['coupon_num']) {
                $high_price = $realHighPrice - $highCoupon['max_discount'];
                $high_price = $high_price <= 0 ? 0.01 : $high_price;
            }
            $ajaxreturn['coupon_info'] = $couponData;
        }
        // end coupon-info v3.0.2

        $ajaxreturn['low_price']      = $low_price;
        $ajaxreturn['high_price']     = $high_price;
        $ajaxreturn['is_one_price']   = $low_price == $high_price ? 1 : 0;
        $ajaxreturn['one_price']      = $low_price;
        $ajaxreturn['bofang_num']     = $this->turnToW($rs['real_click'] + $rs['market_click'] * 10000);
        $ajaxreturn['progress']       = $this->progressDx($id);
        $ajaxreturn['section_num']    = $this->sectionNum($id);
        $ajaxreturn['try_num']        = $this->tryNum($id);
        $ajaxreturn['evaluation_num'] = $this->evaluationNum($id, $mid);
        $ajaxreturn['course_title']   = $rs['course_title'];
        $ajaxreturn['course_intro']   = $rs['course_intro'];
        $ajaxreturn['share_title']    = $rs['share_title'];
        $ajaxreturn['share_pic']      = $rs['share_pic'];
        $ajaxreturn['kf_url']         = $rs['kf_pic'];
        $ajaxreturn['cover_pic']      = $rs['cover_pic'];
        $ajaxreturn['buy_status']     = $this->coursebuyState($id, $mid);
        //$ajaxreturn['buy_status']     = 3;
        $ajaxreturn['iosbutton']  = $this->iosbutton();
        $ajaxreturn['is_publish'] = $rs['is_publish'];
        //$ajaxreturn['iosbutton']      = 0;
        $this->ajaxreturn($ajaxreturn, "查询成功", 1);
    }
    public function coursebuyState($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $data['uid']       = $mid;
        // print_r($data);
        return intval(M('mini_course_user')->where($data)->getField('id')) > 0 ? 3 : 0;
    }
    public function iosbutton($id = 0)
    {
        $data['rule'] = 'iosbutton';
        return intval(M('mini_course_config')->where($data)->getField('is_open'));
    }
    public function sectionNum($id = 0)
    {
        $data['course_id'] = $id;
        $data['pid']       = array('gt', 0);
        $data['is_del']    = 0;
        return M('mini_course_section')->where($data)->count();
    }
    public function tryNum($id = 0)
    {
        $data['is_try']    = 1;
        $data['course_id'] = $id;
        return M('mini_course_section')->where($data)->count();
    }
    public function evaluationNum($id = 0, $mid = 0)
    {

        $where['star']    = array('egt', 3);
        $where['uid']     = $mid;
        $where['_logic']  = 'or';
        $map['_complex']  = $where;
        $map['course_id'] = $id;
        $map['is_hide']   = 0;
        $count            = M('mini_course_evaluation')->where($map)->count();
        $date2            = date('Y-m-d');
        $date1            = date('2023-04-26');
        $c                = $this->dayCount($date1, $date2);
        return $count + abs($c) * 5;
    }
    public function dayCount($from, $to)
    {
        $first_date  = strtotime($from);
        $second_date = strtotime($to);
        $offset      = $second_date - $first_date;
        return floor($offset / 60 / 60 / 24);
    }
    public function turnToW($f = 10000)
    {
        if ($f < 10000) {
            return $f;
        }
        if ($f >= 10000) {
            return floor($f / 10000) . "万";
        }
    }
    public function realLowPrice($id = 0, $uid = 0)
    {
        $data['course_id']  = $id;
        $data['is_publish'] = 1;
        $sql = "SELECT * FROM `el_mini_course_sku` WHERE `course_id` = {$id} AND `is_publish` = 1 AND `id` NOT IN (SELECT `course_sku_id` FROM `el_mini_course_order` WHERE `uid` = {$uid} AND `course_id` = {$id} AND `pay_status` = 3) ORDER BY `price` ASC;";
        $sku = M()->query($sql);
        return $sku ? $sku[0]['price'] : 0;
    }
    public function realHighPrice($id = 0, $uid = 0)
    {
        $data['course_id']  = $id;
        $data['is_publish'] = 1;
        $sql = "SELECT * FROM `el_mini_course_sku` WHERE `course_id` = {$id} AND `is_publish` = 1 AND `id` NOT IN (SELECT `course_sku_id` FROM `el_mini_course_order` WHERE `uid` = {$uid} AND `course_id` = {$id} AND `pay_status` = 3) ORDER BY `price` DESC;";
        $sku = M()->query($sql);
        return $sku ? $sku[0]['price'] : 0;
    }
    public function lowPrice($id = 0)
    {
        $data['course_id']  = $id;
        $data['is_publish'] = 1;
        $RS                 = M('mini_course_sku')->where($data)->order('limit_price asc')->getField('limit_price');
        return $RS;
    }
    public function highPrice($id = 0)
    {
        $data['course_id']  = $id;
        $data['is_publish'] = 1;
        $RS                 = M('mini_course_sku')->where($data)->order('limit_price desc')->getField('limit_price');
        return $RS;
    }

    public function getsection()
    {
        $id                = intval($_REQUEST['course_id']);
        $mid               = intval($_REQUEST['mid']);
        $data['course_id'] = $id;
        $data['pid']       = 0;
        $data['is_del']    = 0;
        $res['mulu']       = $rs       = M('mini_course_section')->field('id,title')->where($data)->order('sort_id asc,id asc')->select();
        foreach ($rs as $key => $value) {
            $where['pid']         = $value['id'];
            $where['is_del']      = 0;
            $where['course_id']   = $id;
            $rs[$key]['datalist'] = M('mini_course_section')->field('id,title,is_try,homework_info,tcvideo_id')->where($where)->order('sort_id asc,id asc')->select() ?: [];
            foreach ($rs[$key]['datalist'] as $kk => $vv) {
                $rs[$key]['datalist'][$kk]['status']      = $vv['is_try'] == 1 ? 1 : 2;
                $rs[$key]['datalist'][$kk]['is_homework'] = !empty($vv['homework_info']) ? 1 : 0;
                $rs[$key]['datalist'][$kk]['fileid']      = M('n_zy_tcvideo')->where(['id' => $vv['tcvideo_id']])->getField('fileid');
            }
            $rs[$key]['update_course_num'] = $this->updateCourseNum($value['id'], $id);
            $rs[$key]['total_course_num']  = $this->totalCourseNum($value['id'], $id);
            $rs[$key]['stype']             = $this->stype($rs[$key]['update_course_num'], $rs[$key]['total_course_num']);
        }
        $res['course'] = $rs;

        $this->ajaxreturn($res, "查询成功", 1);
    }
    public function stype($update_course_num = 0, $totalCourseNum = 0)
    {
        if ($update_course_num == 0) {
            return 3;
        }
        if ($totalCourseNum > 0 && $totalCourseNum > $update_course_num) {
            return 2;
        }
        return 1;
    }
    public function progressDx($course_id = 0)
    {
        $where['is_del']     = 0;
        $where['course_id']  = $course_id;
        $where['tcvideo_id'] = 0;
        $where['pid']        = array('gt', 0);
        $RS                  = M('mini_course_section')->where($where)->count();
        return $RS > 0 ? 1 : 2;
    }
    public function updateCourseNum($pid = 0, $course_id)
    {
        $where['pid']        = $pid;
        $where['is_del']     = 0;
        $where['course_id']  = $course_id;
        $where['tcvideo_id'] = array('gt', 0);
        $RS                  = M('mini_course_section')->where($where)->count();
        return $RS;
    }
    public function totalCourseNum($pid = 0, $course_id)
    {
        $where['pid']       = $pid;
        $where['is_del']    = 0;
        $where['course_id'] = $course_id;
        $RS                 = M('mini_course_section')->where($where)->count();
        return $RS;
    }
    public function noSKU($course_id = 0, $pid = 0)
    {
        $rs  = "SELECT id from el_mini_course_sku where course_id={$course_id} and is_publish=1 and   FIND_IN_SET({$pid}, chapter_ids)>0";
        $idS = M()->query($rs);
        return count($idS) > 0 ? 0 : 1;
    }
    public function getlearnsection()
    {
        $id                = intval($_REQUEST['course_id']);
        $mid               = intval($_REQUEST['mid']);
        $data['course_id'] = $id;
        $data['pid']       = 0;
        $data['is_del']    = 0;
        $res['mulu']       = $rs       = M('mini_course_section')->field('id,title')->where($data)->order('sort_id asc,id asc')->select();
        $homeworksubmit    = $this->homeworkList($id, $mid);
        $userchapters      = $this->userchapters($id, $mid);
        //print_r($userchapters);
        foreach ($rs as $key => $value) {
            $where['pid']         = $value['id'];
            $where['is_del']      = 0;
            $where['course_id']   = $id;
            $rs[$key]['datalist'] = M('mini_course_section')->field('id,title,homework_info,tcvideo_id,is_try,pid')->where($where)->order('sort_id asc,id asc')->select() ?: [];
            $nosku                = $this->noSKU($id, $value['id']);
            foreach ($rs[$key]['datalist'] as $kk => $vv) {
                $is_homework                         = !empty($vv['homework_info']) ? 1 : 0;
                $rs[$key]['datalist'][$kk]['fileid'] = M('n_zy_tcvideo')->where(['id' => $vv['tcvideo_id']])->getField('fileid');
                // $rs[$key]['datalist'][$kk]['fileid'] = "5285890801960477501";
                $rs[$key]['datalist'][$kk]['status'] = $this->tiStatus($vv['is_try'], $vv['pid'], $vv['tcvideo_id'], $userchapters, $nosku);
                //$is_homework                                  = 1;
                $rs[$key]['datalist'][$kk]['homework_status'] = $this->homeworkStatus($vv['id'], $is_homework, $homeworksubmit);
            }
            $rs[$key]['update_course_num'] = $this->updateCourseNum($value['id'], $id);
            $rs[$key]['total_course_num']  = $this->totalCourseNum($value['id'], $id);
            $rs[$key]['stype']             = $this->stype($rs[$key]['update_course_num'], $rs[$key]['total_course_num']);
        }
        $res['course'] = $rs;

        $res['section_id'] = $this->lastSectionId($id, $mid);
        $res['chapter_id'] = $this->chapterId($res['section_id']);
        $this->ajaxreturn($res, "查询成功", 1);
    }
    public function tiStatus($is_try = 0, $pid = 0, $tcvideo = 0, $userchapters = [], $nosku = 0)
    {
        if ($nosku == 1) {
            if (!empty($tcvideo)) {
                return 4;
            } else {
                return 3;
            }
        } else {

            if ($is_try > 0 && !in_array($pid, $userchapters)) {
                return 1;
            } else if (!in_array($pid, $userchapters)) {
                return 2;
            } else if (empty($tcvideo)) {
                return 3;
            } else {
                return 4;
            }
        }
    }
    public function userchapters($id = 0, $mid = 0)
    {
        $data['uid']       = $mid;
        $data['course_id'] = $id;
        $rs                = M('mini_course_user')->where($data)->getField('chapter_ids');
        return explode(',', $rs);
        // return intval($rs);

    }
    public function homeworkStatus($id = 0, $is_homework, $data)
    {
        if ($data[$id] > 0) {
            return 2;
        } else if (!empty($is_homework)) {
            return 1;
        } else {
            return 0;
        }
    }
    public function homeworkList($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $data['uid']       = $mid;
        if ($id > 0 && $mid > 0) {
            $rs = M('homework_submit')->field('id,section_id')->where($data)->select();
            foreach ($rs as $key => $value) {
                $res[$value['section_id']] = $value['id'];
            }
            return $res;
        }
        return [];
    }
    public function lastSectionId($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $uid               = $data['uid']               = $mid;
        $rs                = M('mini_course_history')->where($data)->order('updated_at desc')->getField('section_id');
        return intval($rs);
    }

    public function chapterId($id = 0)
    {
        $data['id'] = $id;
        $rs         = M('mini_course_section')->where($data)->getField('pid');
        return intval($rs);

    }
    public function addRealClick($course_id = 0)
    {
        $course_id = intval($course_id);
        if ($course_id > 0) {
            $sql = "UPDATE `el_mini_course` SET `real_click`=real_click +1 where `id`=" . intval($course_id);
            M()->execute($sql);
        }

    }

    public function addhistory()
    {

        $course_id  = $data['course_id']  = intval($_REQUEST['course_id']);
        $uid        = $data['uid']        = intval($_REQUEST['mid']);
        $section_id = $data['section_id'] = intval($_REQUEST['section_id']);
        $rs         = M('mini_course_history')->where($data)->order('id desc')->find();
        if (empty($course_id * $uid * $section_id)) {
            $result['data']   = null;
            $result['info']   = "参数失败";
            $result['status'] = 0;
            $this->ajaxreturn($result['data'], $result['info'], $result['status']);
        }
        if (empty($rs)) {
            M('mini_course_history')->add($data);
            $this->addRealClick($course_id);
        } else {
            $data['id']         = $rs['id'];
            $data['updated_at'] = date('Y-m-d H:i:s');
            M('mini_course_history')->save($data);
            $this->addRealClick($course_id);
        }
        $result['data']   = null;
        $result['info']   = "点击成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function evconfig()
    {
        $rees = M('mini_course_evaluation_quick')->field('id,title')->order('sort_id asc,id asc')->select();
        return $rees;
    }
//评论历史
    public function countEvaluation($id)
    {
        $where['course_id'] = $id;
        $rees               = M('mini_course_evaluation')->field('quick_ids')->where($where)->select();
        $res                = '';
        foreach ($rees as $key => $value) {
            $res .= $value['quick_ids'] . ",";
        }
        $res    = rtrim($res, ',');
        $resStr = explode(',', $res);
        // print_r($res); print_r($resStr);
        $array_count_values = array_count_values($resStr);
        //print_r($array_count_values);
        $conf = $this->evconfig();
        foreach ($conf as $kk => $vv) {
            $ff[$conf[$kk]['id']] = $conf[$kk]['title'];
        }
        foreach ($array_count_values as $kz => $vz) {
            $va[$kz]['title'] = $ff[$kz];
            $va[$kz]['id']    = $kz;
            $va[$kz]['num']   = $vz;
            if (empty($va[$kz]['title'])) {
                unset($va[$kz]);
            }
        }
        return array_values($va);
    }
    public function evaluationinfo()
    {
        $id               = intval($_REQUEST['course_id']);
        $mid              = intval($_REQUEST['mid']);
        $res['total_num'] = $this->evaluationNum($id, $mid);
        $quick_ev         = $this->countEvaluation($id);
        $res['quick_ev']  = $quick_ev;
        $this->ajaxreturn($res, "查询成功", 1);
    }
    //评论历史
    public function evaluationlist()
    {
        $id  = intval($_REQUEST['course_id']);
        $mid = intval($_REQUEST['mid']);

        $where['star']    = array('egt', 3);
        $where['uid']     = $mid;
        $where['_logic']  = 'or';
        $map['_complex']  = $where;
        $map['course_id'] = $id;
        $map['is_hide']   = 0;
        //$map['quick_ids'] = array('exp', 'not null');
        $res = M('mini_course_evaluation')->field('id,uid,review_content,star,quick_ids,quick_title,created_at')->where($map)->order('rand()')->findPage(6);
        $p   = $_REQUEST['p'];
        if ($p > $res['totalPages']) {
            $res['data'] = array();
        }
        foreach ($res['data'] as $skey => $svalue) {
            $res['data'][$skey]['avatar']      = D('Local', 'dakaprogram')->gotUserFace($svalue['uid']);
            $res['data'][$skey]['created_day'] = date("Y-m-d", strtotime($svalue['created_at']));
            $res['data'][$skey]['uname']       = getUserName($svalue['uid']);
        }
        $this->ajaxreturn($res, "查询成功", 1);

    }
    public function sectiondakalist()
    {
        $uid        = $_REQUEST['mid'];
        $section_id = $_REQUEST['section_id'];
        $p          = $_REQUEST['p'];
        if (!empty($dk_type)) {
            $condition['dk_type'] = $dk_type;
        }
        $rsk                     = M('dk_circle')->where('assistants,circle_leader')->find($condition['cid']);
        $condition['is_show']    = 1;
        $condition['is_del']     = 2;
        $condition['section_id'] = $section_id;
        $rs                      = M('homework_submit')->field('id,uid,img_url as img,content,atime,cid,img_url_thumb,share,recommend,activityid')->where($condition)->order('id desc')->findPage(8);
        if ($p > $rs['totalPages']) {
            $rs['data'] = array();
        }
        foreach ($rs['data'] as $skey => $rValue) {
            $rs['data'][$skey]['zan_count']     = D('Circles')->get_zan_count($rValue['id']);
            $rs['data'][$skey]['countcomment']  = D('Daka')->countComment($rValue['id']);
            $rs['data'][$skey]['is_collection'] = D('Circles')->isCollection($rValue['id'], $uid);
            $rs['data'][$skey]['is_zan']        = D('Circles')->isPraise($rValue['id'], $uid);
            $rs['data'][$skey]['is_follow']     = D('Use')->isFollow($rValue['uid'], $uid);
            $rs['data'][$skey]['imageindex']    = D('Circles')->imgindex($rValue['img']);
            $rs['data'][$skey]['imgarray']      = D('Circles')->imgArray($rValue['img']);
            //$rs['data'][$skey]['img_remark']= D('Circles')->getRemarkImage_url($rValue['img']);
            $rs['data'][$skey]['time_remark'] = D('Circles')->timeDecode($rValue['atime']);
            //$rs['data'][$skey]['circleinfo']=D('Circles')->easycircleinfo($rValue['cid'],$uid);
            //圈子信息
            $rs['data'][$skey]['img_avatar']   = D('Members')->getUserfaceNopic($rValue['uid']);
            $rs['data'][$skey]['uname']        = D('UnameS')->getUserNameCache($rValue['uid']);
            $tol_days                          = M('homework_daka_new')->where('uid=' . intval($rValue['uid']))->getField('tol_days');
            $rs['data'][$skey]['tol_days']     = intval($tol_days);
            $rs['data'][$skey]['levelname']    = D('Members')->levelname3($rValue['uid']);
            $rs['data'][$skey]['comments']     = D('Daka')->getCommentOnlyLeaderAndAssist($rValue['id'], $rsk['circle_leader'], $rsk['assistants']);
            $rs['data'][$skey]['new_activity'] = D('Huati')->newActivityInfo($rValue['activityid']);
        }
        $result['data']   = $rs;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function getsectioninfo()
    {
        $id                          = intval($_REQUEST['course_id']);
        $section_id                  = intval($_REQUEST['section_id']);
        $mid                         = intval($_REQUEST['mid']);
        $rs                          = M('mini_course')->find($id);
        $mini_course_section         = M('mini_course_section')->find($section_id);
        $ajaxreturn['id']            = $rs['id'];
        $ajaxreturn['course_title']  = $rs['course_title'];
        $ajaxreturn['homework_info'] = $rs['homework_info'];
        $ajaxreturn['share_title']   = $rs['share_title'];
        $ajaxreturn['share_pic']     = $rs['share_pic'];
        $ajaxreturn['kf_pic']        = $rs['kf_pic'];
        $ajaxreturn['homework_info'] = $mini_course_section['homework_info'];
        // $ajaxreturn['homework_info'] = "显示作业=====";
        //$ajaxreturn['button_state']  = rand(1, 5);
        $ajaxreturn['button_state']  = $this->buttonState($id, $section_id, $mid);
        $ajaxreturn['is_evaluation'] = $this->isEvaluation($id, $mid);
        //$ajaxreturn['is_evaluation']  = 0;
        $ajaxreturn['is_pop_address'] = $this->isPopAddress($id, $mid);
        $ajaxreturn['pop_order_id']   = $this->popOrderId($id, $mid);
        $ajaxreturn['have_address']   = $this->haveAddress($mid);
        $ajaxreturn['evaluation_num'] = $this->evaluationNum($id, $mid);
        $ajaxreturn['circle_id']      = $this->courseCircleId($id);
        $ajaxreturn['iosbutton']      = $this->iosbutton();
        // $ajaxreturn['buy_status']     = $this->coursebuyState($id, $mid);

        // coupon-info v3.0.2
        $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
        $ajaxreturn['coupon_info'] = $model->courseCouponInfo($mid, $this->realLowPrice($mini_course_section['course_id'], $uid));
        // end coupon-info v3.0.2

        $this->ajaxreturn($ajaxreturn, "查询成功", 1);

    }
    public function buttonState($id = 0, $section_id = 0, $mid = 0)
    {
        //1 本节无作业，2 立即打卡，3 再次打卡，4 课程更新中，5  课时未购 。立即购买

        $section           = M('mini_course_section')->where(['id' => $section_id])->find();
        $pid               = $section['pid'];
        $nosku             = $this->noSKU($id, $pid);
        $tcvideo_id        = $section['tcvideo_id'];
        $homework_info     = $section['homework_info'];
        $data['uid']       = $mid;
        $data['course_id'] = $id;
        $chapter_ids       = M('mini_course_user')->where($data)->getField('chapter_ids');

        $data['uid']        = $mid;
        $data['section_id'] = $section_id;
        $homework_submit    = M('homework_submit')->where($data)->getField('id');
        $userchapters       = explode(',', $chapter_ids);

        if ($nosku == 1) {

            if (empty($tcvideo_id)) {
                return 4;
            }
            if (empty($homework_info)) {
                return 1;
            }
            if (!empty($homework_submit)) {
                return 3;
            }
            if (empty($homework_submit)) {
                return 2;
            }
        } else {
            if (!in_array($pid, $userchapters)) {
                return 5;
            }
            if (empty($tcvideo_id)) {
                return 4;
            }
            if (empty($homework_info)) {
                return 1;
            }
            if (!empty($homework_submit)) {
                return 3;
            }
            if (empty($homework_submit)) {
                return 2;
            }
        }

        return 4;
    }
    public function haveAddress($mid = 0)
    {

        $data['uid']    = $mid;
        $data['is_del'] = 0;
        $rs             = M('mini_address')->where($data)->getField('id');
        return intval($rs) > 0 ? 1 : 0;
    }
    public function isPopAddress($id = 0, $mid = 0)
    {
        $data['course_id']       = $id;
        $data['is_fucai']        = 1;
        $data['uid']             = $mid;
        $data['pay_status']      = 3;
        $data['mini_address_id'] = 0;
        $rs                      = M('mini_course_order')->where($data)->getField('id');
        return intval($rs) > 0 ? 1 : 0;

    }
    public function popOrderId($id = 0, $mid = 0)
    {
        $data['course_id']       = $id;
        $data['uid']             = $mid;
        $data['pay_status']      = 3;
        $data['mini_address_id'] = 0;
        $data['is_fucai']        = 1;
        $rs                      = M('mini_course_order')->where($data)->getField('id');
        return intval($rs);
    }

    public function courseCircleId($id = 0)
    {

        $rs        = "SELECT bb.circle_id from el_mini_circle_course bb LEFT JOIN el_mini_course cc on cc.id=bb.mini_course_id where cc.is_publish=1 and bb.mini_course_id={$id} and bb.is_del=0 ORDER BY bb.id asc limit 1";
        $get       = M()->query($rs);
        $circle_id = intval($get[0]['circle_id']);
        return $circle_id;
    }

    public function isEvaluation($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $data['uid']       = $mid;
        $rs                = M('mini_course_evaluation')->where($data)->getField('id');
        return intval($rs) > 0 ? 1 : 0;
    }
    public function quicklist()
    {
        $rs = M('mini_course_evaluation_quick')->order('sort_id asc,id asc')->select();
        $this->ajaxreturn($rs, "查询成功", 1);

    }

    public function addevaluation()
    {
        $course_id = $aData['course_id'] = intval($_REQUEST['course_id']);
        $uid       = $aData['uid']       = intval($_REQUEST['mid']);
        $star      = $aData['star']      = intval($_REQUEST['star']);
        $redis     = Redis::getInstance();
        if ($redis->get("liupinshuyuan_addevaluation_" . $uid)) {
            $this->ajaxReturn('', '评论提交中,请稍后', 0);
        } else {
            $redis->setex("liupinshuyuan_addevaluation_" . $uid, 5, '1');
        }
        $aCount['course_id'] = $aData['course_id'];
        $aCount['uid']       = $aData['uid'];
        $count               = M('mini_course_evaluation')->where($aCount)->count();
        if ($count > 0) {
            $this->ajaxReturn('', '请勿重复评论', 0);
        }
        $aData['quick_title']    = $_REQUEST['quick_title'];
        $aData['review_content'] = $_REQUEST['review_content'];
        $aData['quick_ids']      = $_REQUEST['quick_ids'];
        $res                     = $this->lastOrder($course_id, $uid);
        //print_r($res);exit;
        $aData['sku_name']     = $res['sku_name'];
        $aData['sku_id']       = $res['course_sku_id'];
        $aData['sku_buy_time'] = $res['pay_at'];

        if ($star * $uid * $course_id > 0) {
            if (empty($aData['review_content']) && empty($aData['quick_title'])) {
                $aData['is_hide'] = 1;
            }
            $rs = M('mini_course_evaluation')->add($aData);
            // echo M()->getlastsql();
            $this->ajaxreturn($rs, "点评成功", 1);
        } else {
            $this->ajaxreturn(null, "点评失败", 0);
        }

    }
    public function lastOrder($course_id = 0, $mid = 0)
    {
        $data['pay_status'] = 3;
        //$data['order_type'] = 1;
        $data['uid']       = $mid;
        $data['course_id'] = $course_id;
        return M('mini_course_order')->where($data)->order('pay_at desc')->limit(1)->find();
    }
    public function createOne()
    {
        $url        = $_REQUEST['url'];
        $type       = intval($_REQUEST['type']);
        $qrcodedaka = new QrcodeAction();
        $res1       = $qrcodedaka->createOne($url, $type);
        $this->ajaxreturn($res1, "点评成功", 1);
    }

}
