<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;
use App\dakaprogram\Lib\Model\CouponActivityModel;

/**
 * 打卡小程序优惠券相关接口
 *
 * @version 3.0.2
 */
class CouponActivityAction
{
    /**
     * 获取当前返利相关的信息
     *
     */
    public function currentRebate()
    {
        $model = $this->getCouponModel();
        $iosbutton = $model->getIosbutton();
        $result['status'] = 1;

        $auth = $this->checkAuth(false);
        $uid = 0;
        if (!empty($auth)) {
            $user = $this->getUserInfo();
            $uid = $user['uid'];
        }

        $result = [
            'info' => 'success',
            'data' => [
                'iosbutton' => $iosbutton, 
                'rebateInfo' => [],
            ],
        ];
        echo json_encode($result);exit;
    }

    /**
     * 课程券消息推送，用户有未使用的课程券时，提醒用户及时使用
     *
     */
    public function couponNotice()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();
        $couponData = $model->getMyValidCoupons($user['uid'], 'full');
        $coupons = $couponData['coupons'];
        $result = [
            'status' => 1,
            'info' => 'success',
        ];
        if (empty($coupons)) {
            $result['data'] = ['noNotice' => 1];
            echo json_encode($result);exit;
        }
        $fullMoney = 0;
        foreach ($coupons as $coupon) {
            $fullMoney += $coupon['money'];
        }
        $result['data'] = [
            'noNotice' => 0,
            'myCouponNum' => $couponData['myCouponNum'],
            'fullMoney' => $fullMoney,
            'activityId' => $couponData['activityId'],
            'jumpPath' => $couponData['jumpPath'],
        ];
        echo json_encode($result);exit;
    }

    /**
     * 当前可用优惠券
     *
     */
    public function myValidCoupon()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();
        $price = $_REQUEST['price'];
        $couponData = $model->getCouponsWithPrice($user['uid'], $price);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => $couponData,
        ];
        echo json_encode($result);exit;
    }

    /**
     * 我的优惠码数量
     *
     */
    public function myCouponNum()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();
        $couponData = $model->getMyValidCoupons($user['uid']);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => $couponData,
        ];
        echo json_encode($result);exit;
    }

    /**
     * 申请获取优惠券
     *
     */
    public function applyCoupon($pointUser = null)
    {
        $isIos = intval($_REQUEST['isIos']);
        $model = $this->getCouponModel();
        $iosbutton = $model->getIosbutton();
        if ($isIos && empty($iosbutton)) {
            if (is_null($pointUser)) {
                echo json_encode(['status' => 1, 'info' => 'sucess', 'data' => 'IOS']);exit();
            } else {
                return false;
            }
        }
        if (is_null($pointUser)) {
            $auth = $this->checkAuth();
            $user = $this->getUserInfo();
        } else {
            $user = $pointUser;
        }
        $redis = Redis::getInstance();
        $redisKey = 'liupinshuyuan_miniprogram_apply_coupon_' . $user['uid'];
        if ($redis->get($redisKey)) {
            if (is_null($pointUser)) {
                echo json_encode(['status' => 1, 'info' => 'sucess', 'data' => '过于频繁']);exit();
            } else {
                return false;
            }
        }
        $redis->setex($redisKey, 5, '1');
        $rData = $model->applyCoupon($user);
        if (!is_null($pointUser)) {
            return $rData;
        }

        $result['status'] = 1;
        $result['info'] = 'success';
        $result['data'] = $rData;
        echo json_encode($result);exit;
    }

    /**
     * 可用优惠券的课程列表
     *
     */
    public function courseList()
    {
        $model = new CouponActivityModel();
        $uid = intval($_REQUEST['mid']);
        $fromPage = $_REQUEST['from_page'];

        $courses = $model->getCourseDatas($uid, $fromPage);
        $result['info'] = 'success';
        $result['data']['courses'] = $courses;
        $result['status'] = 1;
        echo json_encode($result);exit;
    }

    /**
     * 获取当前用户的券信息
     *
     */
    public function myCouponList()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();

        $myCoupons = $model->getMyCoupons($user['uid']);
        $defaultCourse = M('mini_course')->find(1);
        $courseNum = $model->getCourseNum();
        $result['info'] = 'success';
        $result['status'] = 1;
        $result['data']['courseNum'] = $courseNum;
        $result['data']['courseId'] = 1;
        if ($courseNum == 1) {
            $validCourse = M('mini_course')->where(['is_publish' => 1])->find();
            $result['data']['courseId'] = $validCourse['id'];
        }
        $result['data']['kfPic'] = $defaultCourse['kf_pic'];
        $result['data']['myCoupons'] = $myCoupons;
        echo json_encode($result);exit;
    }

    /**
     * 获取当前可用课程码活动信息
     *
     */
    public function currentCoupon()
    {
        $model = $this->getCouponModel();
        $iosbutton = $model->getIosbutton();
        $result['status'] = 1;
        //$result['data']['iosbutton'] = $iosbutton;
        /*if (empty($iosbutton)) {
            echo json_encode($result);exit;
        }*/

        $auth = $this->checkAuth(false);
        if (empty($auth)) {
            $userType = 'new';
            $uid = 0;
        } else {
            $user = $this->getUserInfo();
            $uid = $user['uid'];
            $userType = $model->getUserType($user['uid']);
        }

        $activity = $model->getPointTypeInfo($userType, $uid);
        $jumpPath = $activity ? $activity['jump_path'] : ''; // '/coursePkg/main/main?id=1&channel=share'
        $result = [
            'info' => 'success',
            'data' => ['type' => $userType, 'iosbutton' => $iosbutton, 'jumpPath' => $jumpPath, 'activity' => $activity],
        ];
        echo json_encode($result);exit;
    }

    protected function checkAuth($throw = true)
    {
    	$token = $_REQUEST['token'];
    	$validToken = md5(date('Y-n-j').'liupinsy');
    	if ($token != $validToken) {
            if (!$throw) {
                return false;
            }
            $result = [
                'info' => '有新内容,请重新进入1',
                'status' => 0,
                'data' => null,
            ];
    	    echo json_encode($result);exit;
    	}
        return true;
    }

    protected function getUserInfo()
    {
        $uid = intval($_REQUEST['mid']);
        $user = M('user')->where(['uid' => $uid])->find();
        return $user;
    }

    protected function getCouponModel()
    {
        return new CouponActivityModel();
    }
}
