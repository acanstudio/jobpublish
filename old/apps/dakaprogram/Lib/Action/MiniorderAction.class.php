<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;

class MiniorderAction extends ApiTokenAction
{

    public function test()
    {
        $title         = $_REQUEST['title'];
        $data['title'] = $title;
        $rs            = D('NewShopServer')->ztProductListByData($data, 0);
        print_r($rs);
    }
    public function paycourseinfo()
    {
        $id                                 = intval($_REQUEST['course_id']);
        $mid                                = intval($_REQUEST['mid']);
        $rs                                 = M('mini_course')->find($id);
        $ajaxreturn['course_title']         = $rs['course_title'];
        $ajaxreturn['cover_pic']            = $rs['cover_pic'];
        $ajaxreturn['is_fill_address']      = $this->haveAddress($mid);
        $ajaxreturn['address']              = $this->defaltAddress($mid, 'address');
        $ajaxreturn['mini_address_id']      = $this->defaltAddress($mid, 'id');
        $ajaxreturn['phone']                = $this->defaltAddress($mid, 'phone');
        $ajaxreturn['receive_name']         = $this->defaltAddress($mid, 'receive_name');
        $ajaxreturn['sku_data_common']      = $this->skufixlist($id, $mid, 0);
        $ajaxreturn['sku_data_specialsell'] = $this->skufixlist($id, $mid, 1);

        // coupon-info v3.0.2
        $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
        $couponInfo = $model->courseCouponInfo($mid);
        $ajaxreturn['coupon_info'] = $couponInfo;
        $ajaxreturn['sku_data_common'] = $model->formatSkuPrice($couponInfo, $ajaxreturn['sku_data_common']);
        // end coupon-info v3.0.2

        $this->ajaxreturn($ajaxreturn, "查询成功", 1);
    }
    public function paycourseinfo_test()
    {
        $id                                 = intval($_REQUEST['course_id']);
        $mid                                = intval($_REQUEST['mid']);
        $rs                                 = M('mini_course')->find($id);
        $ajaxreturn['course_title']         = $rs['course_title'];
        $ajaxreturn['cover_pic']            = $rs['cover_pic'];
        $ajaxreturn['is_fill_address']      = $this->haveAddress($mid);
        $ajaxreturn['address']              = $this->defaltAddress($mid, 'address');
        $ajaxreturn['mini_address_id']      = $this->defaltAddress($mid, 'id');
        $ajaxreturn['phone']                = $this->defaltAddress($mid, 'phone');
        $ajaxreturn['receive_name']         = $this->defaltAddress($mid, 'receive_name');
        $ajaxreturn['sku_data_common']      = $this->skufixlist($id, $mid, 0);
        $ajaxreturn['sku_data_specialsell'] = $this->skufixlist($id, $mid, 1);
        $this->ajaxreturn($ajaxreturn, "查询成功", 1);
    }

    public function skufixlist($id = 0, $mid = 0, $special_sell = 0)
    {
        $where['uid']        = $mid;
        $where['course_id']  = $id;
        $where['pay_status'] = 3;
        $res                 = M('mini_course_order')->field('id,course_sku_id')->where($where)->select();
        $count               = count($res);
        $chapterData         = M('mini_course_section')->field('id,title')->where($section)->select();
        if (empty($count)) {
            $datacommon['course_id']            = $id;
            $datacommon['special_sell']         = $special_sell;
            $datacommon['first_sell_recommend'] = 1;
            $datacommon['is_publish']           = 1;
            $minir                              = M('mini_course_sku')->field('id,sku_name,price,limit_price,fucai_title,is_fucai,chapter_ids')->where($datacommon)->order('sort_id asc,id desc')->select();
            foreach ($minir as $key => $value) {
                $minir[$key]['chapter'] = $this->chapterData($value['chapter_ids'], $chapterData);
            }
            return $minir;
        } else {

            $ids           = array_column($res, 'course_sku_id');
            $wherein['id'] = array('in', $ids);
            $re            = M('mini_course_sku')->field('sku_recommend_ids')->where($wherein)->select();
            $re            = array_column($re, 'sku_recommend_ids');

            $re                      = $this->jiaojiArray($re);
            $inarr                   = $this->diffarr($re, $ids);
            $datacommon['id']        = array('in', $inarr);
            $datacommon['course_id'] = $id;
            if ($special_sell > 0) {
                $datacommon['special_sell'] = $special_sell;
            }
            $datacommon['is_publish'] = 1;
            $minir                    = M('mini_course_sku')->field('id,sku_name,price,limit_price,fucai_title,is_fucai,chapter_ids')->where($datacommon)->order('sort_id asc,id desc')->select();
            $section['course_id']     = $id;

            foreach ($minir as $key => $value) {
                $minir[$key]['chapter'] = $this->chapterData($value['chapter_ids'], $chapterData);
            }
            return $minir;

        }

    }
//当前优惠券
    public function chapterData($chapter_ids = 0, $data = [])
    {
        $ids = explode(',', $chapter_ids);
        $rs  = $data;
        foreach ($rs as $key => $value) {
            $z[$value['id']] = $value['title'];
        }
        $a = '';
        foreach ($ids as $k => $v) {
            if ($k > 0) {
                $a .= "+" . $z[$v];
            } else {
                $a .= $z[$v];
            }
        }
        return $a;
    }

    public function diffarr($arr1 = [], $arr2 = [])
    {
        $intersect = array_intersect($arr1, $arr2);
        $diff      = array_diff($arr1, $intersect);
        return $diff;
    }
    public function jiaojiArray($re)
    {
        $r = [];
        foreach ($re as $key => $value) {
            $va[] = explode(',', $value);
        }
        if (count($va) > 1) {
            $va = call_user_func_array('array_intersect', $va);
        } else {
            $va = $va[0];
        }
        $va = array_values($va);
        return $va;
    }
    public function xarray($re)
    {
        $r = [];
        foreach ($re as $key => $value) {
            $va = explode(',', $value);
            foreach ($va as $k => $v) {
                if (!empty($v)) {
                    $r[] = $v;
                }
            }
        }
        return $r;
    }
    public function dopaycourse()
    {

        $mid           = intval($_REQUEST['mid']);
        $course_id     = intval($_REQUEST['course_id']);
        $pay_price     = $_REQUEST['price'];
        $course_sku_id = intval($_REQUEST['course_sku_id']);
        $address_id    = intval($_REQUEST['address_id']);
        $paytype       = "wxpay";

        if (empty($mid)) {
            $this->ajaxReturn('', '无法获取到用户UID,请稍后再试', 0);
        }
        if (empty($pay_price)) {
            $this->ajaxReturn('', '无法获取到用户支付价格,请稍后再试', 0);
        }
        if (empty($course_id)) {
            $this->ajaxReturn('', '无法获取到课程ID,请稍后再试', 0);
        }
        if (empty($course_sku_id)) {
            $this->ajaxReturn('', '无法获取到购买的SKU,请稍后再试', 0);
        }
        $redis = Redis::getInstance();
        if ($redis->get("liupinshuyuan_dopaycourse_" . $mid)) {
            $this->ajaxReturn('', '订单处理中,请勿重复提交', 0);
        } else {
            $redis->setex("liupinshuyuan_dopaycourse_" . $mid, 5, '1');
        }
        $where['uid']           = $mid;
        $where['course_id']     = $course_id;
        $where['course_sku_id'] = $course_sku_id;
        $where['pay_status']    = 3;
        $order                  = M('mini_course_order')->where($where)->find();
        $sku                    = M('mini_course_sku')->where(['id' => $course_sku_id])->find();
        // $price                  = $sku['price'];
        //$price = $sku['limit_price'] > 0 ? $sku['limit_price'] : $sku['price'];
        $coupon_id = intval($_REQUEST['coupon_id']); // coupon-info v3.0.2
        $price = $sku['limit_price'] > 0 && empty($coupon_id) ? $sku['limit_price'] : $sku['price']; // coupon-info v3.0.2
        if (!empty($order)) {
            $this->ajaxReturn('', '订单支付重复', 0);
        }
        if ($pay_price <= 0) {
            $this->ajaxReturn('', '所需支付值不正确', 0);
        }

        // coupon-info v3.0.2
        $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
        $couponInfo = false;
        if (!empty($coupon_id)) {
            $couponInfo = $model->checkCoupon($coupon_id, $mid, $price, $pay_price);
            if (is_string($couponInfo)) {
                $this->ajaxReturn('', $couponInfo, 0);
            }
            $price = $pay_price;
        }
        // end coupon-info v3.0.2

        if ($price != $pay_price) {
            $this->ajaxReturn('', '支付值错误', 0);
        }
        if ($paytype == 'wxpay') {

            $pay_pass_num                         = "M" . thirdpartyOrderNum(); //订单号
            $rechargeDataorder['uid']             = $mid;
            $rechargeDataorder['pay_order_num']   = $pay_pass_num;
            $rechargeDataorder['totalprice']      = $pay_price;
            $rechargeDataorder['course_id']       = $course_id;
            $rechargeDataorder['course_sku_id']   = $course_sku_id;
            $rechargeDataorder['pay_status']      = 1;
            $rechargeDataorder['mini_address_id'] = $address_id;
            $rechargeDataorder['sku_name']        = $sku['sku_name'];
            $rechargeDataorder['is_fucai']        = $sku['is_fucai'];
            $rechargeDataorder['sku_fucai_title'] = $sku['is_fucai'] ? $sku['fucai_title'] : "";
            $rechargeDataorder['zt_id']           = $sku['is_fucai'] ? $sku['zt_id'] : 0;
            $rechargeDataorder['zt_sku_code']     = $sku['is_fucai'] ? $sku['zt_sku_code'] : '';
            if ($address_id > 0) {
                $addressinfo                       = M('mini_address')->find($address_id);
                $rechargeDataorder['province']     = $addressinfo['province'];
                $rechargeDataorder['city']         = $addressinfo['city'];
                $rechargeDataorder['region']       = $addressinfo['region'];
                $rechargeDataorder['address']      = $addressinfo['address'];
                $rechargeDataorder['receive_name'] = $addressinfo['receive_name'];
                $rechargeDataorder['phone']        = $addressinfo['phone'];
            }
            $rechargeid = M('mini_course_order')->add($rechargeDataorder);
            if (!$rechargeid) {
                $this->ajaxReturn('', '订单支付异常,亲稍后再试！', 0);
            }

            // coupon-info v3.0.2
            if (!empty($couponInfo)) {
                $couponUpdate = [
                    'id' => $couponInfo['id'],
                    //'used_at' => date('Y-m-d H:i:s'),
                    'order_num' => $couponInfo['order_num'] + 1,
                    'order_data' => $couponInfo['order_data'] . ';' . $rechargeid,
                    'orderid' => $rechargeid,
                ];
                M('coupon_activity_user')->save($couponUpdate);
            }
            // end coupon-info v3.0.2

            $wxData['out_trade_no'] = $pay_pass_num; //订单号
            $wxData['total_fee']    = $price * 100; //单位：分
            //$wxData['total_fee']    = 1; //单位：分
            $wxData['subject']     = "六品堂练字-课程订单购买";
            $wxData['product']     = $course_sku_id;
            $wxData['attach']      = $pay_pass_num;
            $wxData['uid']         = $mid;
            $wxpay_res             = $this->wxpaycourse($wxData);
            $wxpay_res['order_id'] = $rechargeid;
            $this->ajaxReturn($wxpay_res, '发起支付成功', 1);
        }
        $this->ajaxReturn('', '订单支付错误,亲稍后再试！', 0);
    }

/**
 * Notes:购买
 * User: dopaycourse.
 */
    public function ordersuccessinfo()
    {

        $mid                    = intval($_REQUEST['mid']);
        $order_id               = intval($_REQUEST['order_id']);
        $res                    = M("mini_course_order")->find($order_id);
        $mini_course            = M("mini_course")->find($res['course_id']);
        $res['cover_pic']       = $this->courseInfo($res['course_id'], 'cover_pic');
        $res['kf_pic']          = $this->courseInfo($res['course_id'], 'kf_pic');
        $res['course_title']    = $this->courseInfo($res['course_id'], 'course_title');
        $res['fill_address']    = $this->fillAddress($res['id']);
        $res['is_mini_address'] = $this->haveAddress($mid);

        $this->ajaxReturn($res, 'success', 1);
    }
    public function deliveryState($id = 0, $shopid = '')
    {
        $url   = D('NewShopServer')->httpURLConfig()['auth_url'] . D('NewShopServer')->httpURLConfig()['pre'] . '/v1/orders/' . $shopid;
        $token = D('NewShopServer')->authorization(0);
        $rs    = D('NewShopServer')->httpQuery($url, null, $token);
        $rs    = json_decode($rs, true);
        //print_r($rs);exit;
        $data['delivery_state']   = intval($rs['ship_status']);
        $data['delivery_company'] = $rs['ship_data']['name'];
        $data['delivery_code']    = $rs['ship_no'];
        $data['id']               = $id;
        M('mini_course_order')->save($data);

    }
    public function orderinfo()
    {

        $mid               = intval($_REQUEST['mid']);
        $order_id          = intval($_REQUEST['order_id']);
        $mini_course_order = M("mini_course_order")->find($order_id);
        if ($mini_course_order['wdt_state'] == 1 && $mini_course_order['delivery_state'] < 2) {
            //echo 111;exit;
            $this->deliveryState($mini_course_order['id'], $mini_course_order['shoporder_id']);
            $mini_course_order = M("mini_course_order")->find($order_id);
        }
        $res['course_title']    = $this->courseInfo($mini_course_order['course_id'], 'course_title');
        $res['sku_price']       = $this->courseSkuInfo($mini_course_order['course_sku_id'], 'price');
        $res['totalprice']      = $mini_course_order['totalprice'];
        $wb                     = "lpt" . date('Ymd', strtotime($mini_course_order['created_at']));
        $res['no_id']           = $wb . $order_id;
        $res['sku_name']        = $mini_course_order['sku_name'];
        $res['sku_fucai_title'] = $mini_course_order['sku_fucai_title'];
        $res['fill_address']    = $this->fillAddress($mini_course_order['id']);
        $res['is_mini_address'] = $this->haveAddress($mid);
        $res['delivery_state']  = $mini_course_order['delivery_state'];
        $res['wdt_state']       = $mini_course_order['wdt_state'];
        $res['order_type']      = $mini_course_order['order_type'];
        $res['remark']          = $mini_course_order['remark'];
        $res['course_id']       = $mini_course_order['course_id'];
        $res['created_at']      = $mini_course_order['created_at'];
        $res['province']        = $mini_course_order['province'];
        $res['city']            = $mini_course_order['city'];
        $res['region']          = $mini_course_order['region'];
        $res['address']         = $mini_course_order['address'];
        $res['receive_name']    = $mini_course_order['receive_name'];
        $res['phone']           = $mini_course_order['phone'];
        //$res['star']            = $this->evaluationStar($mini_course_order['course_id'], $mid);
        $res['is_evaluation'] = $this->isEvaluation($mini_course_order['course_id'], $mid);
        //$res['is_evaluation']   = 0;
        $res['kf_url']   = $this->courseInfo($mini_course_order['course_id'], 'kf_pic');
        $res['is_fucai'] = $mini_course_order['is_fucai'];
        //$res['delivery_state']  = 2;

        // coupon-info v3.0.2
        $model = new \App\dakaprogram\Lib\Model\CouponActivityModel();
        $res['coupon_money'] = $model->orderCouponInfo($order_id);
        // end coupon-info v3.0.2

        $this->ajaxReturn($res, 'success', 1);
    }
    public function evaluationStar($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $data['uid']       = $mid;
        $rs                = intval(M('mini_course_evaluation')->where($data)->getField('star'));
        return $rs;
    }
    public function isEvaluation($id = 0, $mid = 0)
    {
        $data['course_id'] = $id;
        $data['uid']       = $mid;
        $rs                = M('mini_course_evaluation')->where($data)->getField('id');
        return intval($rs) > 0 ? 1 : 0;
    }
    public function courseInfo($id = 0, $field = "id")
    {
        $data['id'] = $id;
        return M("mini_course")->where($data)->getField($field);
    }

    public function courseSkuInfo($id = 0, $field = "id")
    {
        $data['id'] = $id;
        return M("mini_course_sku")->where($data)->getField($field);
    }

    public function orderlist()
    {

        $mid                = intval($_REQUEST['mid']);
        $p                  = intval($_REQUEST['p']);
        $data['uid']        = $mid;
        $data['pay_status'] = 3;
        $mini_course_order  = M("mini_course_order")->where($data)->order('id desc')->findPage(10);
        if ($p > $mini_course_order['totalPages']) {
            $mini_course_order['data'] = array();
        }
        foreach ($mini_course_order['data'] as $key => &$value) {
            $mini_course_order['data'][$key]['cover_pic']       = $this->courseInfo($value['course_id'], 'cover_pic');
            $mini_course_order['data'][$key]['course_title']    = $this->courseInfo($value['course_id'], 'course_title');
            $mini_course_order['data'][$key]['fill_address']    = $this->fillAddress($value['id']);
            $mini_course_order['data'][$key]['is_mini_address'] = $this->haveAddress($mid);
        }
        $this->ajaxReturn($mini_course_order, 'success', 1);
    }

    public function ordership()
    {
        $order_id  = intval($_REQUEST['order_id']);
        $resTaoBao = M('mini_course_order')->field('delivery_state,delivery_company,delivery_code,shoporder_id')->find($order_id);
        $shopid    = $resTaoBao['shoporder_id'];
        //$shopid    = 25107;
        $url   = D('NewShopServer')->httpURLConfig()['auth_url'] . D('NewShopServer')->httpURLConfig()['pre'] . '/v1/orders/' . $shopid;
        $token = D('NewShopServer')->authorization(0);
        $rs    = D('NewShopServer')->httpQuery($url, null, $token);
        $ret   = json_decode($rs);
        //print_r($ret);
        $resTaoBao['ship_data']      = $ret->ship_data;
        $resTaoBao['delivery_state'] = $resTaoBao['delivery_state'];
        $this->ajaxReturn($resTaoBao, 'success', 1);

    }
    public function defaltAddress($mid = 0, $field = 'id')
    {

        $data['uid']    = $mid;
        $data['is_del'] = 0;
        $rs             = M('mini_address')->where($data)->order('is_default desc,id desc')->getField($field);
        return $rs;
    }
    public function haveAddress($mid = 0)
    {

        $data['uid']    = $mid;
        $data['is_del'] = 0;
        $rs             = M('mini_address')->where($data)->getField('id');
        return intval($rs) > 0 ? 1 : 0;
    }
    public function fillAddress($id = 0)
    {
        $data['id'] = $id;
        $rs         = M('mini_course_order')->where($data)->getField('mini_address_id');
        return intval($rs) > 0 ? 1 : 0;

    }

/**
 * 发起微信支付
 * @param $data //支付数据
 * @return bool
 */
    private function wxpaycourse($data)
    {
        if (empty($data)) {
            return false;
        }
        $notifyUrl = SITE_URL . '/dakaprogram_mininotify_wxnotify.html'; // 设置异步回调地址
        //file_put_contents('filetest.txt', json_encode($notifyUrl), FILE_APPEND);
        $from       = 'jsapi';
        $attr       = $data['attach'];
        $openidInfo = M("login")->where("uid=" . $data['uid'])->field("oauth_token_secret_program")->find();
        $attributes = [
            'body'         => $data['subject'],
            'out_trade_no' => "{$data['out_trade_no']}",
            'total_fee'    => "{$data['total_fee']}",
            'attach'       => $attr, //自定义参数 仅服务端异步可以接收
            'openid'       => $openidInfo['oauth_token_secret_program'] ? $openidInfo['oauth_token_secret_program'] : '', //自定义参数 仅服务端异步可以接收
        ];
        $appid = 'wxec9580fe517dceda';
        $wxPay = model('WxPay')->wx_Pay_dakaprogram_product($attributes, 'jsapi', $notifyUrl, $appid);
        D('Leader')->putContent('wxPayShopping_wxPay', $wxPay);
        if ($wxPay['return_code'] === 'FAIL') {
            $this->error = $wxPay['return_msg'];
            return false;
        }
        return $wxPay;
        exit;
    }/**
     * Notes:购买
     * User: dopaycourse.
     */
    public function fillorder()
    {

        $id          = intval($_REQUEST['order_id']);
        $address_id  = intval($_REQUEST['address_id']);
        $addressinfo = M('mini_address')->find($address_id);
        $find        = M("mini_course_order")->find($id);
        if (empty($find)) {
            $this->ajaxReturn(null, 'Order-iD', 0);
        }
        if (empty($addressinfo)) {
            $this->ajaxReturn(null, '地址错误', 0);
        }
        if ($find['pay_status'] != 3) {
            $this->ajaxReturn(null, '订单错误', 0);
        }
        if ($find['mini_address_id'] > 0) {
            $this->ajaxReturn(null, '订单已经填写地址', 0);
        }
        if (empty($find['is_fucai'])) {
            $this->ajaxReturn(null, '订单无需发货', 0);
        }
        $data['province']        = $addressinfo['province'];
        $data['city']            = $addressinfo['city'];
        $data['region']          = $addressinfo['region'];
        $data['address']         = $addressinfo['address'];
        $data['receive_name']    = $addressinfo['receive_name'];
        $data['phone']           = $addressinfo['phone'];
        $data['id']              = $id;
        $data['mini_address_id'] = $address_id;
        $mini_course_order       = M("mini_course_order")->save($data);
        if (!empty($mini_course_order)) {
            D('NewShopServer')->ztWdtpush($id);
        }
        $this->ajaxReturn($mini_course_order, '填写成功', 1);
    }

}
