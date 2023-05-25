<?php
namespace App\dakaprogram\Lib\Model;

use Eduline\Model;
use App\dakaprogram\Lib\Action\MinicourseAction;

class CouponActivityModel extends Model
{
    public function getPointTypeInfo($type, $uid)
    {
        if (!empty($uid)) {
            $info = $this->getMyValidCoupons($uid);
            if (empty($info['activityId'])) {
                return [];
            }
            $activity = M('coupon_activity')->where(['id' => $info['activityId']])->find();
            return $activity;
        }
        $infos = $this->getValidActivities('new');
        if (empty($infos)) {
            return [];
        }
        $lastInfo = isset($infos[0]) ? $infos[0] : [];
        $activity = M('coupon_activity')->where(['id' => $lastInfo['coupon_activity_id']])->find();
        return $activity;
    }

    public function getIosbutton()
    {
        $action = new MinicourseAction();
        $iosbutton = $action->iosbutton();
        return $iosbutton;
    }

    public function getMyValidCoupons($uid, $type = 'simple')
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}' AND `used_at` IS NULL AND (`end_at` IS NULL OR `end_at` > '{$cDate}') ORDER BY `created_at` DESC";
        $r = M()->query($sql);
        $lastCoupon = $r[0];
        $activity = false;
        if (!empty($lastCoupon)) {
            $activity = M('coupon_activity')->where(['id' => $lastCoupon['activity_id']])->find();
        }
        
        $baseData = [
            'myCouponNum' => count($r),
            'couponTitle' => $activity ? $activity['name'] : '',
            'tagDoc' => $activity ? $activity['tag_doc'] : '',
            'bannerDoc' => $activity ? $activity['banner_doc'] : '',
            'activityId' => $activity ? $activity['id'] : 0,
        ];
        if ($type == 'simple') {
            return $baseData;
        }

        $coupons = [];
        $maxDiscount = 0;
        foreach ($r as $info) {
            $data = $this->formatCouponData($info);
            $coupons[] = $data;
            $maxDiscount = max($maxDiscount, $data['money']);
        }
        $baseData['maxDiscount'] = $maxDiscount;
        $baseData['coupons'] = $coupons;
        return $baseData;
    }

    public function getMyCoupons($uid)
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}'";
        $infos = M()->query($sql);

        $results = [];
        foreach ($infos as $info) {
            $data = $this->formatCouponData($info);
            $sort = $data['subSort'] == '' ? 'available' : 'used';
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
        $infos = M('mini_course')->where(['is_publish' => 1])->limit(4)->select();
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
                'tag' => $this->getCourseTag($info['id']),
                'score' => $this->getScourseScore($info['id']),
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

    public function applyCoupon($user)
    {
        $couponData = $this->getMyValidCoupons($user['uid']);
        if ($couponData['myCouponNum'] > 1) {
            return false;
        }
        $type = $this->getUserType($uid);
        return $this->dispatchCoupon($user, $type);
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
        $infos = $this->getValidActivities($type);
        foreach ($infos as $info) {
            $couponData = $this->getCoupon($user['uid'], $info['batch_id']);
            if (!empty($couponData)) {
                $this->dealCoupon($user, $info, $couponData);
                break;
            }
        }
        return true;
    }

    public function payOrderCoupon($orderid)
    {
        $infos = M('coupon_activity_user')->where(['orderid' => $orderid])->select();
        if (empty($infos)) {
            return ;
        }
        foreach ($infos as $info) {
            if (!empty($info->used_at)) {
                continue;
            }

            $r = $this->useCoupon($info['uid'], $info['coupon']);
            var_dump($r);
            $uData = [
                'id' => $info['id'],
                'used_at' => date('Y-m-d H:i:s'),
                'status' => $r,
            ];
            M('coupon_activity_user')->save($uData);
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
        print_r($data);
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
                'activity_id' => $info['coupon_activity_id'],
                'activity_type' => $info['activity_type'],
                'batch_id' => $cData['couponBatchId'],
                'coupon' => $cData['id'],
                'name' => $cData['name'],
                'type' => $cData['type'],
                'full_num' => $cData['fullNum'],
                'cut_num' => $cData['cutNum'],
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
        print_r($result);
        if (empty($result) || !isset($result['status']) || $result['status'] == -1) {
            return false;
        }
        return $result;
    }

    public function courseCouponInfo($uid)
    {
        if (empty($uid)) {
            $newActivity = $this->getCurrentActivity('new');
            return [
                'coupon_num' => 0,
                'coupon_title' => $newActivity ? $newActivity['name'] : '',
                'max_discount' => 0,
                'coupon_list' => [],
            ];
        }
        $couponData = $this->getMyValidCoupons($uid, 'full');
        return [
            'coupon_num' => $couponData['myCouponNum'],
            'coupon_title' => $couponData['couponTitle'],
            'max_discount' => $couponData['maxDiscount'],
            'coupon_list' => $couponData['coupons'],
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

    public function getCourseTag($id)
    {
        return [['title' => '容易听懂'], ['title' => '性价比超高']];
    }

    public function getCourseScore($id)
    {
        return 4.9;
    }

    protected function formatCouponData($info)
    {
        $activity = M('coupon_activity')->where(['id' => $info['activity_id']])->find();
        $batch = M('coupon_activity_batch')->where(['id' => $info['batch_id']])->find();
        $statusValue = '去使用';
        $subSort = '';
        if (!empty($info['used_at'])) {
            $statusValue = '已使用';
            $subSort = 'used';
        }
        if (empty($info['used_at']) && (!empty($info['end_at']) && strtotime($info['end_at']) < time())) {
            $statusValue = '已过期';
            $subSort = 'expired';
        }
        $money = $info['cut_num'] == intval($info['cut_num']) ? intval($info['cut_num']) : $info['cut_num'];
        $data = [
            'name' => $info['name'],
            'brief' => $batch ? $batch['brief'] : '',
            'money' => $money,
            'expireAt' => $this->formatExpire($info['end_at']),
            'endAt' => $info['end_at'] ? date('Y.m.d H:i', strtotime($info['end_at'])) : '',
            'isNotice' => rand(0, 1),
            'subSort' => $subSort,
            'statusValue' => $statusValue,
        ];
        return $data;
    }

    protected function getValidActivities($type)
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity` AS `ca`, `el_coupon_activity_batch` AS `b` WHERE `ca`.`activity_type` = '{$type}' AND `ca`.`id` = `b`.`coupon_activity_id` AND `b`.`status` = 2 AND `b`.`total_num` > `b`.`send_num` AND (`b`.`end_at` IS NULL OR `b`.`end_at` > '{$cDate}') ORDER BY `ca`.`created_at` DESC;";
        $infos = M()->query($sql);
        return $infos;
    }

    public function checkCoupon($couponId, $mid, $price, $pay_price)
    {
        $info = M('coupon_activity_user')->where(['uid' => $mid, 'id' => $couponId])->find();
        if (empty($info) || empty($info['type'])) {
            return '优惠券不存在';
        }
        if ($info['type'] == 1 && $price < $info['full_num']) {
            return '满减优惠券的满减额度为：' . $info['full_num'];
        }

        $realPrice = $price - $info['cut_num'];
        $realPrice = $realPrice <= 0 ? 0.01 : $pay_price;
        //if ($realPrice != 0.01 && $realPrice != $pay_price) {
        if ($realPrice != $pay_price) {
            return '优惠额度有误：' . $pay_price . '-' . $info['cut_num'];
        }
        return $info;
    }
}
