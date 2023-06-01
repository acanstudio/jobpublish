<?php
namespace App\dakaprogram\Lib\Action;

use App\dakaprogram\Lib\Action\CommonAction;

/**
 * 圈子练习列表
 * @author   @lee
 * @version shufadaka1.0
 */
class MininotifyAction extends CommonAction
{

    //微信异步通知回调
    public function wxnotify()
    {

        $response = model('WxPay')->wxNotifyDakaprogramH5();
        //file_put_contents('filetest.txt', json_encode($response), FILE_APPEND);
        if ($response["return_code"] == "SUCCESS" && $response["result_code"] == "SUCCESS") {
            //支付成功 添加一条订单信息
            $recharge = M('mini_course_order')->where(array('pay_order_num' => $response['out_trade_no']))->find();
            if (!$recharge) {
                return false;
            }
            $rechargeData['pay_status'] = 3;
            $rechargeData['pay_at']     = date('Y-m-d H:i:s');
            $rechargeData['id']         = $recharge['id'];
            $recharge_id                = M('mini_course_order')->save($rechargeData);
            $mini_address_id            = $recharge['mini_address_id'];
            $is_fucai                   = $recharge['is_fucai'];
            $order_id                   = $recharge['id'];
            if (!empty($recharge_id)) {
                D('CourseUserServer')->qadduser($recharge['uid'], $recharge['course_sku_id']);
                if ($mini_address_id > 0 && $is_fucai) {
                    D('NewShopServer')->ztWdtpush($order_id);
                }

                // coupon-info v3.0.2
                $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
                $model->payOrderCoupon($order_id);
                // end coupon-info v3.0.2
            }

        }
        echo "SUCCESS";
    }

}
