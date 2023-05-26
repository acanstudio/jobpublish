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
     * 当前可用优惠券
     *
     */
    public function myValidCoupon()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();
        $price = $_REQUEST['price'];
        $couponData = $model->getMyValidCoupons($user['uid'], 'full');
        $maxDiscount = 0;
        $coupons = $couponData['coupons'];
        $newCoupons = $disableCoupons = [];
        foreach ($coupons as $coupon) {

            $coupon['disable'] = 0;
            $coupon['disable_reason'] = '';

            if ($coupon['type'] == 1 && $price < $coupon['fullNum']) {
                $coupon['disable'] = 1;
                $coupon['disable_reason'] = '金额没达到满减额度';
                $disableCoupons[] = $coupon;
            } else {
                $maxDiscount = max($maxDiscount, $coupon['money']);
                $newCoupons[] = $coupon;
            }
        }
        $couponData['coupons'] = array_merge($newCoupons, $disableCoupons);
        $couponData['maxDiscount'] = $maxDiscount;
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
    public function applyCoupon()
    {
        $auth = $this->checkAuth();
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();

        $result['status'] = 1;
        $result['info'] = 'success';
        $result['data'] = $model->applyCoupon($user);
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

        $courses = $model->getCourseDatas($uid);
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
        $result['info'] = 'success';
        $result['status'] = 1;
        $result['data']['courseNum'] = $model->getCourseNum();
        $result['data']['courseId'] = 1;
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
        $result['data']['iosbutton'] = $iosbutton;
        if (empty($iosbutton)) {
            echo json_encode($result);exit;
        }

        $auth = false;//$this->checkAuth(false);
        if (empty($auth)) {
            $userType = 'new';
            $uid = 0;
        } else {
            $user = $this->getUserInfo();
            $uid = $user['uid'];
            $userType = $model->getUserType($user['uid']);
        }

        $activity = $model->getPointTypeInfo($userType, $uid);
        $result = [
            'info' => 'success',
            'data' => ['type' => $userType, 'iosbutton' => $iosbutton, 'activity' => $activity],
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
                'info' => '有新内容,请重新进入1' . '-' . $validToken,
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
