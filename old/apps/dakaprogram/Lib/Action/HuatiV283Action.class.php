<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;

/**
 * 打卡小程序排行榜
 * @author   @lee
 * @version shufadaka1.0
 */
class HuatiV283Action extends ApiTokenAction
{

    public function huatishow()
    {
        $mid           = $uid           = intval($_REQUEST['mid']);
        $id            = intval($_REQUEST['id']);
        $r             = $rs             = M('dk_huati')->find(intval($_REQUEST['id']));
        $rs['uname']   = getUserName($rs['ceouser']);
        $rs['avatar']  = getUserFace($rs['ceouser']);
        $rs['dakanum'] = M('homework_submit')->where(['dk_type' => $_REQUEST['id'], 'is_del' => 2, 'is_show' => 1])->count();

        $rs['circle_title']    = M('dk_circle')->where(['id' => $rs['relate_circle_id']])->getField('title');
        $rs['join_circle']     = D('Circles')->isjoin($rs['relate_circle_id'], $uid);
        $rs['if_follow']       = M('dk_huati_user')->where(['huati_id' => $rs['id'], 'uid' => $mid])->count() > 0 ? 1 : 0;
        $rs['delete']          = empty($r) ? 1 : 0;
        $condition['huati_id'] = $id;
        $joinNum               = M('dk_huati_user')->where($condition)->count();
        $rs['join_num']        = $this->turnToW($joinNum);
        $rs['dakanum']         = $this->turnToW($rs['dakanum']);
        if ($id > 0) {
            $sid = M('dk_huati_user')->where(['huati_id' => $id, 'uid' => $mid])->getField('id');
            $sql = "UPDATE `el_dk_huati_user` SET `click`=click +1 where  `id`=" . $sid;
            M()->execute($sql);
        }
        $rs['kf_pic_url']    = getImageUrlByAttachId($rs['kf_pic']);
        $rs['is_ziliao']     = $this->ptypeOn($id, 1);
        $rs['ziliao_click']  = $this->ziliaoClick($id);
        $rs['share_pic_url'] = getImageUrlByAttachId($rs['share_pic']);
        $rs['is_yinyue']     = $this->ptypeOn($id, 2);
        $rs['share_title']   = $this->shareTitle($rs['share_title'], $rs['title']);
        if (empty($uid)) {
            $rs['if_follow'] = 0;
        }
        $rs['ziliaolist']      = $this->ziliaolist($id, $mid);
        $rs['marketdata']      = $this->marketdatanew($id, $mid);
        //$rs['marketdata']      = $this->marketdata($id, $mid);
        $rs['have_phone']      = $this->havePhone($mid);
        $rs['jieshao_pic_url'] =  M('dk_cock')->where(['code' => 'huati_pd_url'])->getField('data') ?: "";
        $result['data']        = $rs;
        $result['info']        = "查询成功";
        $result['status']      = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    protected function marketdatanew($id)
    {
        $infos = M('dk_huati_box')->where(['huati_id' => $id, 'is_publish' => 1])->order('location asc, sort_id desc, id asc')->select();
        if (empty($infos) || count($infos) < 3) {
            return ['is_show' => 0, 'is_display' => 0];
        }
        
        $results = [];
        $positions = [1 => 'left', 2 => 'right'];
        foreach ($infos as $info) {
            $position = $positions[$info['location']];
            $results[$position][] = [
                'coverurl' => getImageUrlByAttachId($info['cover']),
                'type' => $info['type'],
                'jump_url' => $info['jump_url'],
            ];
        }
        if (count($results) != 2 || count($results['right']) != 2) {
            return ['is_show' => 0, 'is_display' => 0];
        }
        $results['is_show'] = 0;
        $results['is_display'] = 1;
        return $results;
    }

    public function marketdata($id = 0)
    {
        $data['huati_id']       = $id;
        $data['is_publish']     = 1;
        $data['location']       = 1;
        $rs['left']             = M('dk_huati_box')->where($data)->find();
        $count1                 = M('dk_huati_box')->where($data)->count();
        $rs['left']['coverurl'] = getImageUrlByAttachId($rs['left']['cover']);
        //$count1                 = count($rs['left']);
        $data2['huati_id']   = $id;
        $data2['is_publish'] = 1;
        $data2['location']   = 2;
        $rs['right']         = M('dk_huati_box')->where($data2)->order('sort_id desc ,id asc')->select();
        foreach ($rs['right'] as $key => $value) {
            $rs['right'][$key]['coverurl'] = getImageUrlByAttachId($rs['right'][$key]['cover']);
        }
        $count2        = count($rs['right']);
        $rs['is_show'] = $count1 * $count2 > 0 ? 1 : 0;
        return $rs;

    }

    public function havePhone($uid = 0)
    {
        $data['uid'] = $uid;
        $infophone   = M('dk_user_info')->where($data)->getField('infophone');
        $rs          = empty($infophone) ? 0 : 1;
        return $rs;

    }
    public function ziliaolist($id = 0, $uid = 0)
    {
        // $r   = "select hh.album_id from el_dk_album_history hh left join el_dk_album aa on aa.id=hh.album_id  left join el_dk_huati_album cc on cc.album_id=aa.id  where cc.id_del=0 and   aa.is_del=0 and aa.is_publish=1 and hh.uid={$uid}  and hh.huati_id={$id} GROUP BY hh.album_id order by hh.created_at desc limit 2";
        // $re  = M()->query($r);
        // $re1 = array_column($re, 'album_id');

        // $c = count($re);
        // if ($c > 0) {
        //     $data['hh.album_id'] = array('not in', $re1);
        // }
        $data['hh.huati_id']   = $id;
        $data['aa.is_del']     = 0;
        $data['hh.is_del']     = 0;
        $data['hh.ptype']      = 1;
        $data['aa.is_publish'] = 1;
        $rs                    = M('dk_huati_album as hh')->field('hh.album_id,hh.sort_id,hh.ptype')->join('el_dk_album as aa on aa.id=hh.album_id')->where($data)->select();
        // echo M()->getlastsql();
        // $re2 = array_column($rs, 'album_id');

        // $merge = array_merge($re1, $re2);
        foreach ($rs as $key => $value) {
            $ar                       = M('dk_album')->find($value['album_id']);
            $rs[$key]['stype']        = $ar['stype'];
            $rs[$key]['id']           = $ar['id'];
            $rs[$key]['album_title']  = $ar['album_title'];
            $real_view                = $ar['real_view'];
            $unreal_view              = $ar['unreal_view'];
            $r                        = $real_view + $unreal_view * 10000;
            $r                        = $this->turnToW($r);
            $rs[$key]['ziliao_click'] = $r;
            $rs[$key]['sort_use']     = $this->sortUse($uid, $id, $ar['id'], $value['sort_id']);
            $rs[$key]['is_read']      = $this->isLastRead($uid, $id, $ar['id'], $value['ptype']);
        }

        array_multisort(array_column($rs, 'sort_use'), SORT_DESC, $rs);
        $result = array_splice($rs, 0, 2);
        return $result;

    }
    public function isLastRead($mid = 0, $huati_id = 0, $album_id = 0, $ptype = 1)
    {

        $data['bb.huati_id'] = $huati_id;
        $data['bb.uid']      = $mid;
        $data['aa.is_del']   = 0;
        $data['aa.ptype']    = $ptype;
        //$data['aa.album_id'] = 'bb.album_id';
        $id                  = M('dk_album_history as bb')->join('el_dk_huati_album as aa on aa.huati_id=bb.huati_id and aa.album_id=bb.album_id')->where($data)->order('bb.updated_at desc')->limit(1)->getField('bb.album_id');
       // echo M()->getlastsql();
        return $id == $album_id ? 1 : 0;

    }
    public function sortUse($mid = 0, $huati_id = 0, $album_id = 0, $sort_id = 0)
    {
        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        $data['album_id'] = $album_id;
        $updated_at       = strtotime(M('dk_album_history')->where($data)->order('updated_at desc')->limit(1)->getField('updated_at'));
        $sort_index       = 1000 - $sort_id;
        return $updated_at > 0 ? $updated_at : $sort_index;
    }
    public function shareTitle($share_title = '', $title = '')
    {
        if (empty($share_title)) {
            return "我正在参加#{$title}#话题打卡，一起来吧";
        } else {
            return $share_title;
        }

    }
    public function ptypeOn($id = 0, $ptype = 0)
    {

        $data['hh.huati_id']   = $id;
        $data['aa.is_del']     = 0;
        $data['hh.is_del']     = 0;
        $data['hh.ptype']      = $ptype;
        $data['aa.is_publish'] = 1;
        $rs                    = M('dk_huati_album as hh')->field('hh.album_id,hh.sort_id')->join('el_dk_album as aa on aa.id=hh.album_id')->where($data)->count();
        return $rs > 0 ? 1 : 0;
    }
    public function ziliaoClick($id = 0)
    {
        $data['huati_id']  = $id;
        $data['is_del']    = 0;
        $rs                = M('dk_huati_album')->field('album_id')->where($data)->select();
        $rs                = array_column($rs, 'album_id');
        $aid['id']         = array('in', $rs);
        $aid['is_publish'] = 1;
        $real_view         = M('dk_album')->field('album_id')->where($aid)->sum('real_view');
        $unreal_view       = M('dk_album')->field('album_id')->where($aid)->sum('unreal_view');
        $r                 = $real_view + $unreal_view * 10000;
        $r                 = $this->turnToW($r);
        return $r;
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
    public function huatifollow()
    {
        $data['uid']      = $mid      = intval($_REQUEST['mid']);
        $data['huati_id'] = $id = intval($_REQUEST['id']);
        $rs               = M('dk_huati_user')->where($data)->find();
        if (empty($mid) || empty($id)) {
            $result['data']   = null;
            $result['info']   = "关注失败[#CODE MID ID]";
            $result['status'] = 0;
            $this->ajaxreturn($result['data'], $result['info'], $result['status']);
        }
        if (empty($rs)) {
            M('dk_huati_user')->add($data);
            $relate_circle_id = M('dk_huati')->where(['id' => $id])->getField('relate_circle_id');
            if ($relate_circle_id > 0) {
                $this->dojoinboxcircle($mid, $relate_circle_id);
                $num = M('dk_usercircle')->where(['cid' => $relate_circle_id])->count();
                $num += intval(M('dk_circle')->where(['id' => $relate_circle_id])->getField('join_number'));
            } else {
                $num = M('dk_huati_user')->where(['huati_id' => $id])->count();
            }
            $result['data']   = $num;
            $result['info']   = "关注成功";
            $result['status'] = 1;
        } else {
            $result['data']   = null;
            $result['info']   = "请勿重复关注[#CODE REAPT]";
            $result['status'] = 2;
        }

        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function dojoinboxcircle($mid = 0, $circle_id = 0)
    {
        $data['uid'] = $mid;
        $data['cid'] = $circle_id;
        if (empty($mid) || empty($circle_id)) {
            return 0;
        }
        $rs = M('dk_usercircle')->where($data)->find();
        if (empty($rs)) {
            $data['addtime'] = time();
            M('dk_usercircle')->add($data);
            return 1;
        } else {
            return 0;
        }
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function joinboxcircle()
    {
        $data['uid'] = $mid = intval($_REQUEST['mid']);
        $data['cid'] = intval($_REQUEST['circle_id']);
        $rs          = M('dk_usercircle')->where($data)->find();
        if (empty($rs)) {
            $data['addtime'] = time();
            M('dk_usercircle')->add($data);
            $result['data']   = null;
            $result['info']   = "关注成功";
            $result['status'] = 1;
        } else {
            $result['data']   = null;
            $result['info']   = "请勿重复关注";
            $result['status'] = 0;
        }

        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function boxcircle()
    {
        $data['uid']        = $mid        = intval($_REQUEST['mid']);
        $huati_id           = intval($_REQUEST['id']);
        $countCircle        = M('dk_usercircle')->where(['uid' => $mid])->count();
        $countHuati         = M('dk_huati_user')->where(['uid' => $mid, 'huati_id' => $huati_id])->count();
        $rs['is_show']      = 0;
        $rs['uname']        = "六品练字";
        $rs['avatar']       = "https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/data/upload/avatar/2022/lptapp0.jpg";
        $rs['daynum']       = 266;
        $rs['circle_title'] = "楷书基础天天练";
        $rs['circle_id']    = 17;

        $datetime2              = date('Y-m-d H:i:s');
        $datetime               = date("Y-m-d H:i:s", strtotime("-1 days"));
        $wherebox['created_at'] = array('between', array("'" . $datetime . "'", "'" . $datetime2 . "'"));
        $wherebox['uid']        = $mid;
        // $sql                    = "SELECT count(1) as count FROM `el_dk_circle_showbox` WHERE ( (`created_at` BETWEEN '{$datetime}' AND '{$datetime2}' ) ) AND ( `uid` = {$mid} ) LIMIT 1";
        // $countTc                = M()->query($sql);
        // print_r($countTc);
        $countTc = M('dk_circle_showbox')->where($wherebox)->count();
        if ($countCircle == 0 && $countHuati == 1 && $countTc == 0 && $mid > 0) {
            $showTc = true;
        } else {
            $showTc = false;
        }
        //$showTc = true;
        if ($showTc) {
            $rs['is_show'] = 1;
            $uids          = M('homework_daka_new')->field('uid')->order('tol_days desc')->limit(100)->select();
            $uidarr        = array_column($uids, 'uid');
            $tuid          = $uidarr[rand(0, 99)];
            if ($tuid == $mid) {
                $tuid = 17;
            }
            $rs['uname']  = getUserName($tuid);
            $rs['avatar'] = getUserFace($tuid);
            $rs['daynum'] = M('homework_daka_new')->where(['uid' => $tuid])->getField('tol_days');
            $circle_id    = M('dk_circle_showbox')->order('id desc')->getField('circle_id');
            if ($circle_id == 17) {
                $circle_id = 21;
            } else {
                $circle_id = 17;
            }
            $rs['circle_title']    = M('dk_circle')->where(['id' => $circle_id])->getField('title');
            $rs['circle_id']       = $circle_id;
            $datauser['uid']       = $mid;
            $datauser['circle_id'] = $circle_id;
            M('dk_circle_showbox')->add($datauser);

        }
        $result['data']   = $rs;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function circlehuati()
    {
        $mid               = intval($_REQUEST['mid']);
        $data['circle_id'] = $circle_id = intval($_REQUEST['circle_id']);

        $ids                          = M('dk_huati_user')->field('huati_id')->where(['uid' => $mid])->select();
        $idarr                        = array_column($ids, 'huati_id');
        $wherebox['relate_circle_id'] = $circle_id;
        $wherebox['id']               = array('in', $idarr);
        $rss                          = M('dk_huati')->where($wherebox)->select();
        foreach ($rss as $key => $value) {
            $rss[$key]['sort_use'] = $this->thisSortsUse($mid, $value['id'], $value['sort_id']);
        }
        array_multisort(array_column($rss, 'sort_use'), SORT_DESC, $rss);
        $myids              = array_column($rss, 'id');
        $whereh['huati_id'] = array('in', $myids);
        $whereh['uid']      = $mid;

        $huati_id = M('dk_huati_user')->where($whereh)->order('updated_at desc')->limit(1)->getField('huati_id');
        //   echo $huati_id;
        $data['huati_id']    = $huati_id    = intval($huati_id);
        $data['huati_title'] = M('dk_huati')->where(['id' => $huati_id])->getField('title');
        $data['huati_data']  = $rss;

        $result['data']   = $data;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function thisSortsUse($mid = 0, $huati_id = 0, $sort_id = 0)
    {
        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        $updated_at       = strtotime(M('dk_huati_user')->where($data)->order('updated_at desc')->limit(1)->getField('updated_at'));
        $sort_index       = 10000 - $sort_id;
        return $updated_at > 0 ? $updated_at : $sort_index;
    }
    public function clickhuati()
    {
        $mid              = intval($_REQUEST['mid']);
        $data['huati_id'] = $huati_id = intval($_REQUEST['huati_id']);
        if ($huati_id > 0) {
            $id  = M('dk_huati_user')->where(['huati_id' => $huati_id, 'uid' => $mid])->getField('id');
            $sql = "UPDATE `el_dk_huati_user` SET `click`=click +1 where  `id`=" . $id;
            M()->execute($sql);
        }
        $result['data']   = null;
        $result['info']   = "点击成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function fixphone()
    {
        $mid  = intval($_REQUEST['mid']);
        $code = $_REQUEST['code'];
        if (empty($mid) || empty($code)) {
            $this->ajaxreturn(null, "数据错误[#CODE REAPT]", 0);
        }
        $access_token = Model('MiniProgram')->get_accessToken();
        $data['code'] = $code;
        $post         = json_encode($data);
        $url          = "https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=" . $access_token;
        $res          = json_decode($this->httpPost($url, $post), true);
        if ($res['errcode'] == 0) {
            $phone = $res['phone_info']['purePhoneNumber'];
            if (!empty($phone)) {
                $this->addinfoPhone($mid, $phone);
                $this->addUserPhone($mid, $phone);
            }
            $this->ajaxreturn(null, "success", 1);
        }
        $this->ajaxreturn(null, "success-1", 1);

    }
    public function lasthistory()
    {
        $mid              = intval($_REQUEST['mid']);
        $last_login_time  = $_REQUEST['last_login_time'];

        // coupon-info v3.0.2
        if (!empty($last_login_time)) {
            $user = M('user')->where(['uid' => $mid])->find();
            if (!empty($user)) {
                $action = new CouponActivityAction();
                $r = $action->applyCoupon($user);
            }
            /*$redis = Redis::getInstance();
            $redisKey = 'liupinshuyuan_miniprogram_apply_coupon_' . $user['uid'];
            if (!$redis->get($redisKey)) {
                $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
                $model->backCoupon($user);
            }*/
        }
        // end coupon-info v3.0.2

        $first_huati      = $_REQUEST['first_huati'];
        $first_huati_time = $_REQUEST['first_huati_time'];
        $last_huati       = $_REQUEST['last_huati'];
        $last_huati_time  = $_REQUEST['last_huati_time'];
        $data['uid']      = $mid;
        $f                = M('dk_user_info')->where($data)->find();
        if (empty($f)) {
            $dataA['uid'] = $mid;
            if (!empty($last_login_time)) {
                $dataA['last_login_time'] = date('Y-m-d H:i:s', $last_login_time);
            }
            if (!empty($first_huati_time)) {
                $dataA['first_huati_time'] = date('Y-m-d H:i:s', $first_huati_time);
            }
            if (!empty($last_huati_time)) {
                $dataA['last_huati_time'] = date('Y-m-d H:i:s', $last_huati_time);
            }
            if (!empty($first_huati)) {
                $dataA['first_huati'] = $first_huati;
            }
            if (!empty($last_huati)) {
                $dataA['last_huati'] = $last_huati;
            }
            M('dk_user_info')->add($dataA);
            $this->ajaxreturn(null, "成功", 1);
        } else {
            $dataAs['id'] = $f['id'];
            if (empty($f['first_huati'])) {
                if (!empty($first_huati_time)) {
                    $dataAs['first_huati_time'] = date('Y-m-d H:i:s', $first_huati_time);
                }
                if (!empty($first_huati)) {
                    $dataAs['first_huati'] = $first_huati;
                }
            }
            if (!empty($last_login_time)) {
                $dataAs['last_login_time'] = date('Y-m-d H:i:s', $last_login_time);
            }
            if (!empty($last_huati_time)) {
                $dataAs['last_huati_time'] = date('Y-m-d H:i:s', $last_huati_time);
            }
            if (!empty($last_huati)) {
                $dataAs['last_huati'] = $last_huati;
            }
            M('dk_user_info')->save($dataAs);

        }
        $this->ajaxreturn(null, "success-1", 1);

    }
    public function addUserPhone($mid, $phone)
    {
        $data['uid'] = $mid;
        $fphone      = $phone;
        $f           = M('user')->where($data)->find();
        if (empty($f['phone'])) {
            $dataAs['uid']   = $f['uid'];
            $dataAs['phone'] = $fphone;
            M('user')->save($dataAs);
        }

    }
    public function addinfoPhone($mid, $phone)
    {
        $data['uid'] = $mid;
        $fphone      = $phone;
        $f           = M('dk_user_info')->where($data)->find();
        if (empty($f) && !empty($fphone)) {
            $dataA['uid']       = $mid;
            $dataA['infophone'] = $fphone;
            M('dk_user_info')->add($dataA);
            $this->ajaxreturn([], "成功", 1);
        } else {
            if (empty($f['infophone'])) {
                $dataAs['id']        = $f['id'];
                $dataAs['infophone'] = $fphone;
                M('dk_user_info')->save($dataAs);
            }
        }

    }
    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
    public function httpPost($url, $post_data = array())
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

}
