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
            if (empty($activity) || empty($activity['status'])) {
                return [];
            }
            $statusStr = $activity['activity_type'] == 'event' ? '(1, 2)' : '(2)';
            $bSql = "SELECT * FROM `el_coupon_activity_batch` WHERE `coupon_activity_id` = {$activity['id']} AND `status` IN {$statusStr} AND (`end_at` IS NULL OR (`end_at` > '{$cDate}')) AND `send_num` < `total_num`";
            $bInfos = M()->query($bSql);
            if (empty($bInfos)) {
                return [];
            }
            
            return $activity;
        }
        return []; // 非登录用户，不返回活动信息
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
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}' AND `used_at` IS NULL AND (`end_at` IS NULL OR `end_at` > '{$cDate}') ORDER BY `cut_num` DESC, `end_at` ASC";
        $r = M()->query($sql);
        $lastCoupon = $r[0];
        $activity = false;
        if (!empty($lastCoupon)) {
            $activity = M('coupon_activity')->where(['id' => $lastCoupon['activity_id']])->find();
            if ($activity['status'] == 1) {
                $statusStr = $activity['activity_type'] == 'event' ? '(1, 2)' : '(2)';
                $bSql = "SELECT * FROM `el_coupon_activity_batch` WHERE `coupon_activity_id` = {$activity['id']} AND `status` IN {$statusStr} AND (`end_at` IS NULL OR (`end_at` > '{$cDate}')) AND `send_num` < `total_num`";
                $bInfos = M()->query($bSql);
                if (empty($bInfos)) {
                    $activity['status'] = 0;
                }
            }
        }
        
        $baseData = [
            'myCouponNum' => count($r),
            'couponTitle' => $activity && $activity['status'] == 1 ? $activity['banner_doc'] : '',
            'tagDoc' => $activity && $activity['status'] == 1 ? $activity['tag_doc'] : '',
            'bannerDoc' => $activity && $activity['status'] == 1 ? $activity['banner_doc'] : '',
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
        $sql = "SELECT * FROM `el_coupon_activity_user` WHERE `uid` = '{$uid}' ORDER BY `cut_num` DESC, `end_at` ASC";
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
        $infos = M('mini_course')->where(['is_publish' => 1])->limit(40)->select();
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
                'score' => $this->getCourseScore($info['id']),
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
        if (empty($user)) {
            return false;
        }
        $couponData = $this->getMyValidCoupons($user['uid']);
        if ($couponData['myCouponNum'] >= 1) {
            return false;
        }
        $uType = $this->getUserType($user['uid']);
        $fetch = $this->dispatchCoupon($user, $uType);
        if (empty($fetch) && in_array($uType, ['new', 'back'])) {
            $fetch = $this->dispatchCoupon($user, 'event');
        }
        return $fetch;
    }

    public function isNew($user)
    {
        $uid = $user['uid'];
        $loginInfo = M('login')->where("uid = {$uid} AND oauth_token_secret_program != ''")->find();
        if (empty($loginInfo) || empty($loginInfo['time_l'])) {
            return true;
        }
        $diff = time() - $loginInfo['time_l'];
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
        if (!empty($lastLoginTime)) {
            $diff = $cTime - strtotime($lastLoginTime);
            if ($diff >= 86400 * 30) {
                return true;
            } else {
                return false;
            }
        }
        $where = ['uid' => $user['uid'], 'rel_type' => 'today_login'];
        $todayTime = strtotime(date('Y-m-d'));
        $info = M('credit_user_flow')->where("uid = {$user['uid']} AND rel_type = 'today_login' AND ctime < {$todayTime}")->order('ctime desc')->find();
        if (empty($info)) {
            return true;
        }

        $diff = $cTime - $info['ctime'];
        /*$log_file = './data/upload/dakaprogram/coupon.txt';
        file_put_contents($log_file, "\r\n {$user['uid']} - {$diff} \r\n", FILE_APPEND);*/
        if ($diff >= 86400 * 30) {
            return true;
        }
        return false;
    }

    public function dispatchCoupon($user, $type)
    {
        if ($type == 'new') {
            $exist = M('coupon_activity_user')->where(['uid' => $user['uid'], 'activity_type' => 'new'])->find();
            if (!empty($exist)) {
                return false;
            }
        }
        $infos = $this->getValidActivities($type);
        foreach ($infos as $keyPre => $info) {
            $existCouponNum = M('coupon_activity_user')->where(['uid' => $user['uid'], 'activity_id' => $info['coupon_activity_id']])->count();
            if ($existCouponNum && $info['activity_type'] == 'event') {
                unset($infos[$keyPre]);
            }
        }
        $couponNum = 0;
        foreach ($infos as $key => $info) {
            if ($key > 0) {
                if ($infos[$key]['coupon_activity_id'] != $infos[$key - 1]['coupon_activity_id'] && !empty($couponNum)) {
                    break;
                }
            }
            $couponData = $this->getCoupon($user['uid'], $info['batch_id']);
            if (!empty($couponData)) {
                $r = $this->dealCoupon($user, $info, $couponData);
                if ($r) {
                    $couponNum++;
                }
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
            if (!empty($info['used_at'])) {
                continue;
            }

            $r = $this->useCoupon($info['uid'], $info['coupon']);
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
            $aData = [
                'activity_id' => $info['coupon_activity_id'],
                'activity_type' => $info['activity_type'],
                'batch_id' => $cData['couponBatchId'],
                'coupon' => $cData['id'],
                'name' => $cData['name'],
                'type' => $cData['type'],
                'full_num' => floatval($cData['fullNum']),
                'cut_num' => $cData['cutNum'],
                'start_at' => $cData['timeStart'],
                'end_at' => $cData['timeEnd'],
                'created_at' => $cDate,
                'updated_at' => $cDate,
                'uid' => $cData['platUid'],
            ];
            $r = M('coupon_activity_user')->add($aData);
            if ($r) {
                $uData = ['send_num' => ['exp', 'send_num + 1']];
                M('coupon_activity_batch')->where(['batch_id' => $cData['couponBatchId']])->save($uData);
            }
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

        $log_file = "./data/upload/dakaprogram/logs/" . date('Y-m-d-H').'.txt';
        $inputStr = serialize($postData);
        $lStr = "\r\n ---coupon center data --- \r\n" . $inputStr . "\r\n\r\n" . $result . "\r\n\r\n" . '---coupon center data end --- ' . "\r\n";
        file_put_contents($log_file, $lStr, FILE_APPEND);
        $result = json_decode($result, true);
        //print_r($result);
        if (empty($result) || !isset($result['status']) || $result['status'] == -1) {
            return false;
        }
        return $result;
    }

    public function courseCouponInfo($uid, $price)
    {
        if (empty($uid)) {
            //$aInfos = $this->getValidActivities('new');
            //$newActivity = is_array($aInfos) ? $aInfos[0] : false;
            return [
                'coupon_num' => 0,
                'coupon_title' => '',//$newActivity ? $newActivity['banner_doc'] : '',
                'max_discount' => 0,
                'coupon_list' => [],
            ];
        }
        //$couponData = $this->getMyValidCoupons($uid, 'full');
        $couponData = $this->getCouponsWithPrice($uid, $price);
        return [
            'coupon_num' => $couponData['myCouponNum'],
            'coupon_title' => $couponData['bannerDoc'],
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
                $money += $info['cut_num'];
            }
            return $money;
        }
        return $infos;
    }

    protected function formatExpire($endAt, $used)
    {
        $endTime = strtotime($endAt);
        $now = time();
        if ($endTime < $now || $used) {
            return date('Y-m-d H:i', $endTime) . '到期';
        }
        $diff = $endTime - $now;
        if ($diff > 86400 * 10) {
            return date('m月d日', $endTime) . '到期';
        }
        if ($diff > 86400) {
            $days = ceil($diff / 86400);
            return "{$days}天后到期";
        }
        $timeStr = date('H:i', $endTime);
        $str = date('d', $endTime) != date('d', $now) && $timeStr != '00:00' ? '明日' : '今日';
        $timeStr = $timeStr == '00:00' ? '23:59' : $timeStr;
        ////var_dump($endAt);var_dump(date('d', $endAt));var_dump(date('d', $now));
        return $str . $timeStr . '到期';
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
        $batch = M('coupon_activity_batch')->where(['batch_id' => $info['batch_id']])->find();
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
        $expireAt = $this->formatExpire($info['end_at'], $info['used_at']);
        $fullNum = $info['full_num'] == intval($info['full_num']) ? intval($info['full_num']) : $info['full_num'];
        $data = [
            'id' => $info['id'],
            'name' => $info['name'],
            'type' => $info['type'],
            'brief' => $info['type'] == 1 ? '满' . $fullNum . '使用' : '无门槛使用',//$batch ? $batch['brief'] : '',
            'money' => $money,
            'fullNum' => $fullNum,
            'expireAt' => $expireAt,
            'endAt' => $info['end_at'] ? date('Y.m.d H:i', strtotime($info['end_at'])) : '',
            'isNotice' => strpos($expireAt, '今日') !== false || strpos($expireAt, '明日') !== false ? 1 : 0,
            'subSort' => $subSort,
            'statusValue' => $statusValue,
        ];
        return $data;
    }

    protected function getValidActivities($type)
    {
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity` AS `ca`, `el_coupon_activity_batch` AS `b` WHERE `ca`.`status` = 1 AND `ca`.`activity_type` = '{$type}' AND (`ca`.`end_at` IS NULL OR `ca`.`end_at` > '{$cDate}') AND (`ca`.`start_at` IS NULL OR `ca`.`start_at` <= '{$cDate}') AND `ca`.`id` = `b`.`coupon_activity_id` AND `b`.`status` = 2 AND `b`.`total_num` > `b`.`send_num` AND (`b`.`end_at` IS NULL OR `b`.`end_at` > '{$cDate}') AND (`b`.`start_at` IS NULL OR `b`.`start_at` <= '{$cDate}') ORDER BY `ca`.`created_at` DESC;";
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

        $realPrice = round($price - $info['cut_num'], 2);
        $realPrice = $realPrice <= 0 ? 0.01 : $realPrice;
        //if ($realPrice != 0.01 && $realPrice != $pay_price) {
        if ($realPrice != $pay_price) {
            return '优惠额度有误！';// . $realPrice . '=' . $pay_price . '-' . $info['cut_num'];
        }
        return $info;
    }

    public function formatSkuPrice($couponData, $skuDatas)
    {
        foreach ($skuDatas as & $skuData) {
            $skuCoupon = $this->getSkuCoupon($skuData['price'], $couponData['coupon_num'], $couponData['coupon_list']);
            $skuData['price'] = $skuData['price'] == intval($skuData['price']) ? intval($skuData['price']) : $skuData['price'];
            $skuData['coupon_valid'] = $skuCoupon['coupon_valid'];
            $skuData['coupon_price'] = $skuCoupon['coupon_price'];
            $skuData['coupon_money'] = $skuCoupon['coupon_money'];
            $skuData['coupon_id'] = $skuCoupon['coupon_id'];
        }
        return $skuDatas;
    }

    public function getSkuCoupon($skuPrice, $couponNum, $coupons)
    {
        $valid = 0;
        $price = 0;
        $default = ['coupon_valid' => $valid, 'coupon_price' => $price, 'coupon_money' => 0, 'coupon_id' => 0];
        if ($couponNum < 1) {
            return $default;
        }
        
        $negative = $cPrices = [];
        foreach ($coupons as $coupon) {
            if ($coupon['type'] == 1 && $coupon['fullNum'] > $skuPrice) {
                continue;
            }
            $diff = $skuPrice - $coupon['money'];
            $dKey = abs($diff);
            if ($diff <= 0) {
                $negative[$dKey] = ['money' => $coupon['money'], 'coupon_id' => $coupon['id']];
            } else {
                $cPrices[$dKey] = ['money' => $coupon['money'], 'coupon_id' => $coupon['id']];
            }
        }
        if (empty($cPrices) && empty($negative)) {
            return $default;
        }
        if (!empty($negative)) {
            krsort($negative);
            $info = array_pop($negative);
            return ['coupon_valid' => 1, 'coupon_price' => '0.01', 'coupon_money' => $info['money'], 'coupon_id' => $info['coupon_id']];
        }

        krsort($cPrices);
        $info = array_pop($cPrices);
        $couponPrice = round($skuPrice - $info['money'], 2);
        $couponPrice = $couponPrice == intval($couponPrice) ? intval($couponPrice) : $couponPrice;
        return ['coupon_valid' => 1, 'coupon_price' => $couponPrice, 'coupon_money' => $info['money'], 'coupon_id' => $info['coupon_id']];
    }

    public function getCouponsWithPrice($uid, $price)
    {
        $couponData = $this->getMyValidCoupons($uid, 'full');
        $maxDiscount = 0;
        $coupons = $couponData['coupons'];
        $newCoupons = $disableCoupons = [];

        $bestInfo = $this->getSkuCoupon($price, $couponData['myCouponNum'], $couponData['coupons']);
        $availableNum = 0;
        foreach ($coupons as $coupon) {

            $coupon['disable'] = 0;
            $coupon['disable_reason'] = '';

            if ($coupon['type'] == 1 && (!empty($price) && $price < $coupon['fullNum'])) {
                $coupon['disable'] = 1;
                $coupon['disable_reason'] = '金额没达到满减额度';
                $disableCoupons[] = $coupon;
            } else {
                $availableNum++;
                $maxDiscount = max($maxDiscount, $coupon['money']);
                $newCoupons[$coupon['id']] = $coupon;

                if ($bestInfo && strval($bestInfo['coupon_money']) == strval($coupon['money'])) {
                    $bestId = $coupon['id'];
                }
            }
        }
        /*if (empty($availableNum)) {
            $couponData['couponTitle'] = '';
            $couponData['tagDoc'] = '';
            $couponData['bannerDoc'] = '';
        }*/
        $bestCoupon = [];
        if (!empty($bestInfo) && !empty($bestInfo['coupon_id'])) {
            $bestCoupon = $newCoupons[$bestInfo['coupon_id']];
            unset($newCoupons[$bestInfo['coupon_id']]);
            array_unshift($newCoupons, $bestCoupon);
        }
        $couponData['coupons'] = array_merge(array_values($newCoupons), $disableCoupons);
        $couponData['maxDiscount'] = $maxDiscount;
        return $couponData;
    }

    public function backCoupon($user)
    {
        if (empty($user)) {
            return false;
        }
        $isNew = $this->isNew($user);
        if ($isNew) {
            return false;
        }
        $isBack = $this->isBack($user);
        if (empty($isBack)) {
            return false;
        }
        $couponData = $this->getMyValidCoupons($user['uid']);
        if ($couponData['myCouponNum'] >= 1) {
            return false;
        }
        
        return $this->dispatchCoupon($user, 'back');
    }

    public function isIosDevice()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $isIphone = (strpos($agent, 'iphone')) ? true : false;   
        if ($isIphone) {
            return true;
        }
        $isIpad = (strpos($agent, 'ipad')) ? true : false;   
        if ($isIpad) {
            return true;
        }
        return false;
    }
}
