<?php
namespace App\dakaprogram\Lib\Model;

use Eduline\Model;
use App\dakaprogram\Lib\Action\MinicourseAction;

class CouponActivityModel extends Model
{
    public function getPointTypeInfo($type)
    {
        //$infos = M('coupon_activity')->where(['type' => $type])->find();
        $infos = M('coupon_activity')->select();
        
        foreach ($infos as $info) {
            //print_r($info);
        }

        return $info;
    }

    public function getIosbutton()
    {
        $action = new MinicourseAction();
        $iosbutton = $action->iosbutton();
        return $iosbutton;
    }

    public function getMyValidCoupons($uid)
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}' AND `used_at` IS NULL AND (`end_at` IS NULL OR `end_at` > '{$cDate}') ORDER BY `created_at` DESC";
        $r = M()->query($sql);
        $lastCoupon = $r[0];
        $activity = false;
        if (!empty($lastCoupon)) {
            $activity = M('coupon_activity')->where(['id' => $lastCoupon['activity_id']])->find();
        }
        return [
            'myCouponNum' => count($r),
            'couponTitle' => $activity ? $activity['name'] : '',
            'tagDoc' => $activity ? $activity['tag_doc'] : '',
            'bannerDoc' => $activity ? $activity['banner_doc'] : '',
        ];
    }

    public function getMyCoupons($uid)
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}'";
        $infos = M()->query($sql);

        $results = [];
        foreach ($infos as $info) {
            $activity = M('coupon_activity')->where(['id' => $info['activity_id']])->find();
            $batch = M('coupon_activity_batch')->where(['id' => $info['batch_id']])->find();
            $sort = 'available';
            $statusValue = '去使用';
            if (!empty($info['used_at'])) {
                $statusValue = '已使用';
                $sort = 'used';
            }
            if (empty($info['used_at']) && (!empty($info['end_at']) && strtotime($info['end_at']) < time())) {
                $statusValue = '已过期';
                $sort = 'used';
            }
            $data = [
                'name' => $info['name'],
                'brief' => $batch ? $batch['brief'] : '',
                'money' => $info['money'],
                'expireAt' => $this->formatExpire($info['end_at']),
                'endAt' => $info['end_at'] ? date('Y.m.d H:i', strtotime($info['end_at'])) : '',
                'isNotice' => rand(0, 1),
                'statusValue' => $statusValue,
            ];
            $results[$sort][] = $data;
        }
        return $results;
    }

    public function getCourseNum()
    {
        return M('mini_course')->where(['is_publish' => 1])->count();
    }

    public function getCourseDatas($mid)
    {
        $infos = M('mini_course')->limit(3)->select();
        $results = [];
        $action = new MinicourseAction();
        foreach ($infos as $info) {
            $id = $info['id'];
            $highPrice = $action->highPrice($id);
            $lowPrice = $action->lowPrice($id);
            $data = [
                'id' => $id,
                'low_price' => $lowPrice,
                'high_price' => $highPrice,
                'is_one_price' => $highPrice ? 1 : 0,
                'one_price' => $lowPrice,
                'bofang_num' => $action->turnToW($info['real_click'] + $info['market_click'] * 10000),
                'progress' => $action->progressDx($id),
                'section_num' => $action->sectionNum($id),
                'try_num' => $action->tryNum($id),
                'evaluation_num' => $action->evaluationNum($id, $mid),
                'course_title' => $info['course_title'],
                'share_title' => $info['share_title'],
                'share_pic' => $info['share_pic'],
                'kf_url' => $info['kf_pic'],
                'cover_pic' => $info['cover_pic'],
                'buy_status' => $action->coursebuyState($id, $mid),
                'iosbutton' => $action->iosbutton(),
                'is_publish' => $info['is_publish'],
            ];
            $result[] = $data;
        }
        return $result;
    }

    public function getUserType($uid)
    {
        $user = M('user')->where(['uid'=>$uid])->find();
        $isNew = $this->isNew($user);
        if ($isNew) {
            return 'new';
        }

        $isBack = $this->isBack($user);
        if ($isBack) {
            return 'back';
        }
        return 'event';
    }

    public function applyCoupon($uid)
    {
        $user = M('user')->where(['uid'=>$uid])->find();
        $type = $this->getUserType($uid);
        return dispatchCoupon($user, $type);
    }

    public function isNew($user)
    {
        $uid = $user['uid'];
        $cTime = time();
        $loginInfo = M('login')->where(['uid' => $uid])->where('oauth_token_secret_program', '<>', '')->find();
        if (empty($loginInfo) || empty($loginInfo->time_l)) {
            return true;
        }
        $diff = time() - $loginInfo->time_l;
        if ($diff < 86400 * 1) {
            return true;
        }
        return false;
    }

    public function isBack($user)
    {
        $cTime = time();
        $info = M('dk_user_info')->where(['uid' => $user['uid']])->find();
        $lastLoginTime = $info['last_login_time'];
        if (empty($lastLoginTime)) {
            return true;
        }
        $diff = $cTime - strtotime($lastLoginTime);
        if ($diff >= 86400 * 30) {
            return true;
        }
        return false;
    }

    public function dispatchCoupon($user, $type)
    {
        $sql = "SELECT *, `ca`.`id` AS `activity_id` FROM `el_coupon_activity` AS `ca`, `el_coupon_activity_batch` AS `b` WHERE `ca`.`id` = `b`.`coupon_activity_id` AND `total_num` > `send_num`;";
        //echo $sql;
        //$sql = "SELECT *, `ca`.`id` AS `activity_id` FROM `el_coupon_activity` AS `ca`, `el_coupon_activity_batch` AS `b` WHERE `ca`.`activity_type` = '{$type}' AND `ca`.`id` = `b`.`coupon_activity_id` AND `b`.`status` = 2 AND `total_num` > `send_num`;";
        $infos = M()->query($sql);
        foreach ($infos as $info) {
            $couponData = $this->getCoupon($user['uid'], $info['batch_id']);
            if (!empty($couponData)) {
                $this->dealCoupon($user, $info, $couponData);
                break;
            }
        }
        return true;
    }

    /**
     * 从优惠券中台核销优惠券
     *
     * @params string $action 请求事件
     * @params array $postData 提交的参数
     * @return mixed
     */
    public function useCoupon($uid, $couponId)
    {
        $data = [
            'platUid' => $uid,
            'id' => $couponId,
        ];
        $url = '/api/couponDetail/use';
        return $this->_asyncRequest($url, $data);
    }

    /**
     * 从优惠券中台领取优惠券
     *
     * @params string $action 请求事件
     * @params array $postData 提交的参数
     * @return mixed
     */
    public function getCoupon($uid, $batchId)
    {
        $data = [
            'platUsersInfo' => [
                ['platUid' => $uid]
            ], 
            'couponBatchId' => $batchId
        ];
        $url = '/api/couponDetail/send';
        return $this->_asyncRequest($url, $data);
    }

    public function dealCoupon($user, $info, $couponData)
    {
        $cDate = date('Y-m-d H:i:s');
        foreach ($couponData['data'] as $cData) {
            $cData = [
                'activity_id' => $info['activity_id'],
                'activity_type' => $info['activity_type'],
                'batch_id' => $cData['couponBatchId'],
                'coupon' => $cData['id'],
                'name' => $cData['name'],
                'start_at' => $cData['timeStart'],
                'end_at' => $cData['timeEnd'],
                'created_at' => $cDate,
                'updated_at' => $cDate,
                'uid' => $cData['platUid'],
            ];
            $r = M('coupon_activity_user')->add($cData);
        }
        return true;
    }

    public function _asyncRequest($url, $postData, $debug = true)
    {
        /*$r = '{"status":0,"msg":"success","data":[{"id":76,"couponBatchId":1,"useType":-1,"timeStart":"2023-05-18 22:41:23","timeEnd":"2023-05-19 23:59:59","name":"进行中-天数","type":1,"fullNum":"300","cutNum":"30","platUid":1914250,"uid":null}],"totalNum":null,"pageIndex":null,"pageSize":null,"totalPage":null,"success":true,"error":false}';
        return json_decode($r, true);*/

        $postData['platId'] = 12;
        $urlBase = C('coupon_url');
        $url = rtrim($urlBase, '/') . $url;

        $postData = json_encode($postData);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . md5(date('Y-m-d') . '915yqsATBzxd'),
            'System: lpt_write',
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($postData)
        ]);
         
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        if (empty($result) || !isset($result['status']) || $result['status'] == -1) {
            return false;
        }
        return $result;
    }

    public function courseCouponInfo($uid)
    {
        $couponLists = $this->getMyCoupons($uid);
        return [
            'coupon_num' => rand(0, 10),
            'coupon_title' => ['', '新人特惠，最高省100元'][rand(0, 1)],
            'max_discount' => rand(10, 100),
            'coupon_list' => isset($couponLists['available']) ? $couponLists['available'] : [],
        ];
    }

    public function orderCouponInfo($orderId, $returnType = 'money')
    {
        $infos = M('coupon_activity_user')->where(['orderid' => $orderId])->select();
        if ($returnType == 'money') {
            $money = 0;
            foreach ($infos as $info) {
                $money += $info['money'];
            }
            return $money;
        }
        return $infos;
    }

    protected function formatExpire($endAt)
    {
        return '2天后过期';
    }
}
