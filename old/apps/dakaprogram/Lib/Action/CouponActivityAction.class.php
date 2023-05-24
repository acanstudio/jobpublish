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
     * 我的优惠码数量
     *
     */
    public function myCouponNum()
    {
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
        $uid = intval($_REQUEST['mid']);
        //$result['data']['myCouponNum'] = rand(1, 10);
        $model = $this->getCouponModel();

        $result['status'] = 1;
        $result['info'] = 'success';
        $result['data'] = $model->applyCoupon($uid);
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
        $model = $this->getCouponModel();
        $user = $this->getUserInfo();

        $myCoupons = $model->getMyCoupons($user['uid']);
        $result['info'] = 'success';
        $result['status'] = 1;
        $result['data']['couponNum'] = $model->getCourseNum();
        $result['data']['myCoupons'] = $myCoupons;
        echo json_encode($result);exit;
    }

    /**
     * 获取当前可用课程码活动信息
     *
     */
    public function currentCoupon()
    {
        $uid = intval($_REQUEST['mid']);
        $user = M('user')->where(['uid'=>$uid])->find();
        $model = new CouponActivityModel();
        $userType = 'new';//$model->getUserType($user);

        $iosbutton = $model->getIosbutton();
        $result['status'] = 1;
        $result['data']['iosbutton'] = $iosbutton;
        if (empty($iosbutton)) {
            echo json_encode($result);exit;
        }

        $activity = $model->getPointTypeInfo($userType);
        $result['data']['activity'] = $activity;
        $result['data']['type'] = $userType;
        $result['info'] = 'success';
        echo json_encode($result);exit;
    }

    protected function getUserInfo($auth = true)
    {
        if ($auth) {
    	    $token = $_REQUEST['token'];
    	    $validToken = md5(date('Y-n-j').'liupinsy');
    	    if (false) {//$token != $validToken) {
                $result = [
                    'info' => '有新内容,请重新进入',
                    'status' => 0,
                    'data' => null,
                ];
    	        echo json_encode($result);exit;
    	    }
        }
        $uid = intval($_REQUEST['mid']);
        $user = M('user')->where(['uid' => $uid])->find();
        return $user;
    }

    protected function getCouponModel()
    {
        return new CouponActivityModel();
    }
}
