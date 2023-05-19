<?php

namespace App\Http\Controllers\Lptapp;

use App\Exports\MiniOrderDataExport;
use App\Services\Childadmin\NewShopServer;
use App\Services\CouponActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdminMiniOrderController extends Controller
{
    // test
    public function __construct()
    {
        error_reporting(E_ALL ^ E_NOTICE);
        $putfile['request'] = request()->input();
        $putfile['header']  = request()->header();
        $putfile['url']     = request()->url();
        (new LogappController)->putfile($putfile);

    }

    //当前优惠券
    public function orderlist(Request $request)
    {

        $order_id   = $request->input('order_id');
        $created_at = $request->input('created_at');
        $uid        = $request->input('uid');
        $uname      = $request->input('uname');
        $type       = $request->input('type');
        $data[]     = ['id', '>', 0];
        if (!empty($order_id)) {
            $data[] = ['id', $order_id];
        }
        if (!empty($uid)) {
            $data[] = ['uid', $uid];
        }
        if (!empty($uname)) {
            $dataus['uname'] = $uname;
            $uid             = DB::table('el_user')->where($dataus)->value('uid');
            $data[]          = ['uid', $uid];
        }
        if (!empty($created_at)) {
            $start_time = explode('|', $created_at);
            $data[]     = ['created_at', ">=", $start_time[0]];
            $data[]     = ['created_at', "<=", $start_time[1]];
        }
        if (!empty($type)) {
            if ($type == 1) {
                $data[] = ['mini_address_id', 0];
                $data[] = ['is_fucai', 1];
            }
            if ($type == 2) {
                $data[] = ['wdt_state', 2];
            }
            if ($type == 3) {
                $data[] = ['mini_address_id', '>', 0];
                $data[] = ['is_fucai', 1];
                $data[] = ['wdt_state', '=', 1];
                $data[] = ['delivery_state', 0];
            }
            if ($type == 4) {
                $data[] = ['mini_address_id', '>', 0];
                $data[] = ['is_fucai', 1];
                $data[] = ['wdt_state', '=', 1];
                $data[] = ['delivery_state', 1];
            }
            if ($type == 5) {
                $data[] = ['mini_address_id', '>', 0];
                $data[] = ['is_fucai', 1];
                $data[] = ['wdt_state', '=', 1];
                $data[] = ['delivery_state', 2];
            }
            if ($type == 6) {
                $data[] = ['is_fucai', 0];
            }

        }
        $returndata = DB::table('el_mini_course_order')->select('id', 'uid', 'course_sku_id', 'created_at', 'sku_name', 'totalprice', 'is_fucai', 'delivery_state', 'wdt_state', 'order_type', 'mini_address_id', 'shoporder_id', 'pay_status')->where($data)->whereIn('pay_status', [2, 3])->orderBy('id', 'desc')->paginate(10);
        foreach ($returndata->items() as $k => &$v) {
            if ($v->wdt_state == 1 && $v->delivery_state < 2) {
                $this->deliveryState($v->id, $v->shoporder_id);
            }
        }
        $returndata = DB::table('el_mini_course_order')->select('id', 'uid', 'course_id', 'course_sku_id', 'created_at', 'sku_name', 'totalprice', 'is_fucai', 'delivery_state', 'wdt_state', 'order_type', 'mini_address_id', 'shoporder_id', 'pay_status')->where($data)->whereIn('pay_status', [2, 3])->orderBy('id', 'desc')->paginate(10);

        $service = new CouponActivityService();
        foreach ($returndata->items() as $k => &$v) {
            $v->coupon_info  = $service->getOrderCoupon($v->id, 'simple');
            $v->uname        = gotUserName($v->uid);
            $v->fill_address = intval($v->mini_address_id) > 0 ? 1 : 0;
            $v->course_title = $this->getCourseField($v->course_id, 'course_title');

        }
        return response()->json(['status' => 0, "msg" => 'success', 'data' => $returndata]);

    }
    public function getCourseField($course_id = 0, $field = 'id')
    {
        $where[] = ['id', '=', $course_id];
        $RS      = DB::table('el_mini_course')->where($where)->value($field);
        return $RS;
    }

    //当前优惠券
    public function orderinfo(Request $request)
    {

        $id = $request->input('order_id');

        $data['id'] = $id;
        $returndata = DB::table('el_mini_course_order')->select('id', 'course_id', 'uid', 'created_at', 'sku_name', 'course_sku_id', 'province', 'city', 'region', 'phone', 'receive_name', 'address', 'totalprice', 'delivery_state', 'order_type', 'wdt_state', 'mini_address_id', 'pay_status', 'shoporder_id', 'sku_fucai_title', 'is_fucai', 'zt_id', 'zt_sku_code')->where($data)->first();
        if (empty($returndata)) {
            return response()->json(['status' => 0, "msg" => 'success', 'data' => null]);
        }
        $returndata->uname        = gotUserName($returndata->uid);
        $returndata->fill_address = intval($returndata->mini_address_id) > 0 ? 1 : 0;
        $returndata->course_title = $this->getCourseField($returndata->course_id, 'course_title');
        $service = new CouponActivityService();
        $returndata->coupon_info  = $service->getOrderCoupon($returndata->id, 'show');

        $shopid = $returndata->shoporder_id;
        $url    = (new NewShopServer)->httpURLConfig()['auth_url'] . (new NewShopServer)->httpURLConfig()['pre'] . '/v1/orders/' . $shopid;
        $token  = (new NewShopServer)->authorization(0);
        $rs     = (new NewShopServer)->httpQuery($url, null, $token);
        //print_r($rs);
        $ret                   = json_decode($rs);
        $returndata->ship_data = $ret->ship_data;
        $returndata->all       = $ret;
        return response()->json(['code' => 200, "msg" => 'success', 'data' => $returndata]);

    }

    public function deliveryState($id = 0, $shopid = '')
    {
        $url                      = (new NewShopServer)->httpURLConfig()['auth_url'] . (new NewShopServer)->httpURLConfig()['pre'] . '/v1/orders/' . $shopid;
        $token                    = (new NewShopServer)->authorization(0);
        $rs                       = (new NewShopServer)->httpQuery($url, null, $token);
        $rs                       = json_decode($rs, true);
        $data['delivery_state']   = intval($rs['ship_status']);
        $data['delivery_company'] = $rs['ship_data']['name'];
        $data['delivery_code']    = $rs['ship_no'];
        DB::table('el_mini_course_order')->where('id', $id)->update($data);

    }

    //当前优惠券
    public function ordernuminfo(Request $request)
    {

        $returndata['totalprice_num']  = $this->totalpriceNum();
        $returndata['no_address_num']  = $this->noAddressNum();
        $returndata['fail_wdt_num']    = $this->failWdtNum();
        $returndata['not_shipped_num'] = $this->notShippedNum();
        $returndata['shipped_num']     = $this->shippedNum();
        $returndata['received_num']    = $this->receivedNum();
        $returndata['no_needship_num'] = $this->noNeedshipNum();
        return response()->json(['status' => 0, "msg" => 'success', 'data' => $returndata]);

    }
    public function notShippedNum($sku_id = 0)
    {

        $where[] = ['mini_address_id', '>', 0];
        $where[] = ['wdt_state', '=', 1];
        $where[] = ['is_fucai', 1];
        $where[] = ['delivery_state', 0];
        $rs      = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        return $rs;
    }
    public function shippedNum($sku_id = 0)
    {

        $where[] = ['mini_address_id', '>', 0];
        $where[] = ['is_fucai', 1];
        $where[] = ['wdt_state', '=', 1];
        $where[] = ['delivery_state', 1];

        $rs = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        return $rs;
    }

    public function receivedNum()
    {

        $where[] = ['mini_address_id', '>', 0];
        $where[] = ['is_fucai', 1];
        $where[] = ['wdt_state', '=', 1];
        $where[] = ['delivery_state', 2];

        $rs = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        //echo M()->getLastSql();
        return $rs;
    }

    public function noNeedshipNum($sku_id = 0)
    {

        $where['is_fucai'] = 0;
        $rs                = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        return $rs;
    }

    public function totalpriceNum($sku_id = 0)
    {
        $where['pay_status'] = 3;
        $rs                  = DB::table('el_mini_course_order')->where($where)->SUM('totalprice');
        return $rs;
    }

    public function noAddressNum($sku_id = 0)
    {

        $where['mini_address_id'] = 0;
        $where['is_fucai']        = 1;
        $rs                       = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        return $rs;
    }
    public function failWdtNum($sku_id = 0)
    {
        $where['wdt_state'] = 2;
        $rs                 = DB::table('el_mini_course_order')->where($where)->whereIn('pay_status', [2, 3])->count();
        return $rs;
    }

    public function orderexport(Request $request)
    {
        $created_at    = $request->input('created_at');
        $token         = $request->input('token');
        $type          = intval($request->input('type'));
        $created_at    = urldecode($created_at);
        $start_time    = explode('|', $created_at);
        $start_time[0] = !empty($start_time[0]) ? $start_time[0] : "2023-04-20 00:00:00";
        $start_time[1] = !empty($start_time[1]) ? $start_time[1] : date('Y-m-d H:i:s');
        $s0            = date('Ymd-His', strtotime($start_time[0]));
        $s1            = date('Ymd-His', strtotime($start_time[1]));
        $fileName      = '课程订单_' . $s0 . "_" . $s1;
        $fileName .= '.xlsx';
        if ($this->falseToken($request->input('token'))) {
            return responseJsonHttp(400, 'Token error');
        }
        return Excel::download(new MiniOrderDataExport($request), $fileName);
        return responseJson(200, 'success', []);
    }
    public function falseToken($token)
    {
        $date = date('Ymd');
        $md5  = md5($date . 'lpsy');
        //return 0;
        if ($md5 !== $token) {
            return 1;
        } else {
            return 0;
        }
    }

    public function wdtpush(Request $request)
    {
        $force    = $request->input('force');
        $order_id = $request->input('order_id');
        $rs       = DB::table('el_mini_course_order')->where('id', $order_id)->first();
        if (!$order_id) {
            return response()->json(['status' => 0, "msg" => '请选择订单', 'data' => null]);
        }
        if (empty($rs)) {
            return response()->json(['status' => 0, "msg" => '订单状态错误', 'data' => null]);
        }
        if ($rs->is_fucai != 1) {
            return response()->json(['status' => 0, "msg" => '无需发货', 'data' => null]);
        }
        if (empty($rs->mini_address_id)) {
            return response()->json(['status' => 0, "msg" => '地址为空', 'data' => null]);
        }
        if ($force == 0) {
            if ($rs->wdt_state != 2) {
                return response()->json(['status' => 0, "msg" => '推送状态不匹配', 'data' => null]);
            }
        }
        if ($force == 1) {
            if ($rs->wdt_state != 2 || $rs->wdt_state != 0) {
                return response()->json(['status' => 0, "msg" => '推送状态不匹配', 'data' => null]);
            }
        }
        $ret = $this->createOrder2Shopcenter($order_id);
        if (empty($ret['status'])) {
            return response()->json(['status' => 0, "msg" => '中台提示:' . $ret['message'], 'data' => null]);
        }
        //print_r($ret);exit;
        return responseJson(200, 'success', null);

    }

    public function createOrder2Shopcenter($id = 0)
    {
        $id  = intval($id);
        $rs  = $this->CQhttp($id);
        $rs1 = json_decode($rs, true);
        if (!empty($rs1['no'])) {
            $rss                  = json_decode($rs, true);
            $data['shoporder_no'] = $rss['no'];
            $data['shoporder_id'] = $rss['id'];
            $data['wdt_state']    = 1;
            DB::table('el_mini_course_order')->where('id', $id)->update($data);
            $res['status']  = 1;
            $res['message'] = 0;
            return $res;
        } else {
            $res['status']  = 0;
            $res['message'] = $rs1['message'];
            return $res;
        }

        // return $rs1;
    }

    public function CQhttp($id = 0)
    {
        $url = (new NewShopServer)->httpURLConfig()['auth_url'] . (new NewShopServer)->httpURLConfig()['pre'] . '/v1/orders';
        //echo $url;
        $info = $this->creatHttp2CenterOrder($url, $id);
        return $info;
    }

    //HTTP请求（支持HTTP/HTTPS，支持GET/POST）
    public function creatHttp2CenterOrder($url = '', $id = 0)
    {

        $rs            = DB::table('el_mini_course_order')->where('id', $id)->first();
        $course_sku_id = intval($rs->course_sku_id);
        //$course_sku_id = 9;
        //$sku        = DB::table('el_mini_course_sku')->where('id', $course_sku_id)->first();
        $product_id = $rs->zt_id;
        $no         = $rs->zt_sku_code;
        $urlcurl    = (new NewShopServer)->findSKU($product_id, $no);
        if (empty($urlcurl['data'][0]['id'])) {
            $rescode['status']  = 0;
            $rescode['message'] = $urlcurl['message'];
            return json_encode($rescode);
        }

        $item[0]['sku_id']   = intval($urlcurl['data'][0]['id']);
        $item[0]['discount'] = "无优惠/在线教育订单同步";
        $item[0]['amount']   = '1';

        $js['province'] = $rs->province;
        $js['contacts'] = $rs->receive_name;
        $js['city']     = $rs->city;
        $js['region']   = $rs->region;
        $js['phone']    = $rs->phone;
        $js['detail']   = $rs->address;

        $address = json_encode($js);
        $item    = array_values($item);
        $data1   = array(
            'address'      => $address,
            'total_amount' => 1,
            'items'        => $item,
        );

        $token  = (new NewShopServer)->authorization(0);
        $output = (new NewShopServer)->httpQuery($url, http_build_query($data1), $token);

        return $output;
    }
}
