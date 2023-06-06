<?php

namespace App\Http\Controllers\Lptapp;

use App\Services\Childadmin\NewShopServer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMiniCourseController extends Controller
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
    public function tcvideolist(Request $request)
    {

        $name      = $request->input('name');
        $file_type = $request->input('file_type');
        $data[]    = ['id', '>', 0];
        if (!empty($file_type)) {
            $data[] = ['file_type', $file_type];
        }
        if (!empty($name)) {
            $data[] = ['name', 'LIKE', '%' . $name . '%'];
        }
        $returndata = DB::table('el_n_zy_tcvideo')->select('id', 'name', 'create_time', 'file_type')->where($data)->whereNull('delete_time')->orderBy('id', 'desc')->paginate(10);
        foreach ($returndata->items() as $k => &$v) {
            $v->created_at = date('Y-m-d H:i:s', $v->create_time);
            //$v->created_at = date('Y-m-d H:i:s', $v->create_time);

        }
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }
    //当前优惠券
    public function listindex(Request $request)
    {
        $uid = $uidtest ?: $this->getTokenUserId();
        //$data    = ['uu.status' => 0, 'cc.type' => 1, 'cc.is_del' => 0];
        $returndata = DB::table('el_mini_course')->select('id', 'course_title', 'is_publish', 'created_at', 'updated_at')->orderBy('id', 'desc')->paginate(10);
        foreach ($returndata->items() as $k => &$v) {
            $v->created_day    = date('Y-m-d', strtotime($v->created_at));
            $v->student_num    = $this->studentNum($v->id);
            $v->insale_sku_num = $this->insaleSkuNum($v->id);
        }
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }

    public function studentNum($id = 0)
    {
        $data['course_id'] = $id;
        return DB::table('el_mini_course_user')->where($data)->count();
    }
    public function insaleSkuNum($id = 0)
    {
        $data['course_id']  = $id;
        $data['is_publish'] = 1;
        return DB::table('el_mini_course_sku')->where($data)->count();
    }
    public function allsaleSkuNumPid($pid = 0)
    {

        $f = DB::select("SELECT id from el_mini_course_sku where FIND_IN_SET({$pid},chapter_ids)");
        return count($f);
    }

    public function insaleSkuNumPid($pid = 0)
    {

        $f = DB::select("SELECT id from el_mini_course_sku where FIND_IN_SET({$pid},chapter_ids) and is_publish=1");
        return count($f);
    }

    //当前优惠券
    public function editindex(Request $request)
    {

        $id           = intval($request->input('course_id'));
        $course_title = $request->input('course_title');
        $course_intro = $request->input('course_intro');
        $market_click = $request->input('market_click');
        $cover_pic    = $request->input('cover_pic');
        $kf_pic       = $request->input('kf_pic');
        $share_title  = $request->input('share_title');
        $share_pic    = $request->input('share_pic');
        if (!empty($id)) {
            if (!empty($course_title)) {
                $data['course_title'] = $course_title;
            }
            if (!empty($course_intro)) {
                $data['course_intro'] = $course_intro;
            }
            if (!empty($market_click)) {
                $data['market_click'] = $market_click;
            }
            if (!empty($cover_pic)) {
                $data['cover_pic'] = $cover_pic;
            }
            if (!empty($kf_pic)) {
                $data['kf_pic'] = $kf_pic;
            }
            if (!empty($share_title)) {
                $data['share_title'] = $share_title;
            }
            if (!empty($share_pic)) {
                $data['share_pic'] = $share_pic;
            }
            DB::table('el_mini_course')->where('id', $id)->update($data);
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);
    }

    public function addindex(Request $request)
    {
        $data['course_title'] = $request->input('course_title');
        $rs                   = 0;

        if (!empty($data['course_title'])) {
            $rs = DB::table('el_mini_course')->insertGetId($data);
            $this->fixCourseCode($rs);

        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
    }
    public function fixCourseCode($id)
    {

        $type                       = 0;
        $type1                      = 1;
        $url1                       = "coursePkg/main/main?channel=dianshang&id={$id}";
        $url1                       = base64_encode($url1);
        $url2                       = "coursePkg/main/main?channel=yunying&id={$id}";
        $url2                       = base64_encode($url2);
        $url3                       = "coursePkg/main/main?channel=dianshang&id={$id}";
        $url3                       = base64_encode($url3);
        $url4                       = "coursePkg/main/main?channel=yunying&id={$id}";
        $url4                       = base64_encode($url4);
        $host                       = env('SYSPRE_URL');
        $token                      = md5(date('Y-n-j') . 'liupinsy');
        $data1                      = file_get_contents("{$host}?app=dakaprogram&mod=Minicourse&act=createOne&url={$url1}&type={$type}&token={$token}");
        $data2                      = file_get_contents("{$host}?app=dakaprogram&mod=Minicourse&act=createOne&url={$url2}&type={$type}&token={$token}");
        $data3                      = file_get_contents("{$host}?app=dakaprogram&mod=Minicourse&act=createOne&url={$url3}&type={$type1}&token={$token}");
        $data4                      = file_get_contents("{$host}?app=dakaprogram&mod=Minicourse&act=createOne&url={$url4}&type={$type1}&token={$token}");
        $data1                      = json_decode($data1, true);
        $data2                      = json_decode($data2, true);
        $data3                      = json_decode($data3, true);
        $data4                      = json_decode($data4, true);
        $update['dianshang_round']  = $data1['data'];
        $update['yunying_round']    = $data2['data'];
        $update['dianshang_square'] = $data3['data'];
        $update['yunying_square']   = $data4['data'];
        DB::table('el_mini_course')->where('id', $id)->update($update);
    }
    public function showindex(Request $request)
    {
        $id      = intval($request->input('course_id'));
        $fixcode = intval($request->input('fixcode'));
        if (!empty($fixcode)) {
            $this->fixCourseCode($id);
        }
        $map[] = ['id', '=', $id];
        $rs    = DB::table('el_mini_course')->where($map)->first();
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
    }

    //当前优惠券
    public function updateskupublish(Request $request)
    {
        $sku_id             = $request->input('sku_id');
        $is_publish         = DB::table('el_mini_course_sku')->where('id', $sku_id)->value('is_publish');
        $data['is_publish'] = $is_publish ? 0 : 1;
        DB::table('el_mini_course_sku')->where('id', $sku_id)->update($data);
        return response()->json(['status' => 0, "msg" => '成功', 'data' => null]);

    }
    //当前优惠券
    public function updatecoursepublish(Request $request)
    {
        $course_id          = $request->input('course_id');
        $is_publish         = DB::table('el_mini_course')->where('id', $course_id)->value('is_publish');
        $data['is_publish'] = $is_publish ? 0 : 1;
        if ($data['is_publish']) {
            $f = $this->insaleSkuNum($course_id);
            if (empty($f)) {
                return response()->json(['status' => 0, "msg" => '无上架SKU', 'data' => null]);
            }
        }
        DB::table('el_mini_course')->where('id', $course_id)->update($data);
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);

    }

    //当前优惠券
    public function iosbutton(Request $request)
    {
        $set     = $request->input('set');
        $is_open = DB::table('el_mini_course_config')->where('rule', 'iosbutton')->value('is_open');
        if ($set) {
            $data['is_open'] = $is_open ? 0 : 1;
            DB::table('el_mini_course_config')->where('rule', 'iosbutton')->update($data);
        }

        $returndata['is_open'] = DB::table('el_mini_course_config')->where('rule', 'iosbutton')->value('is_open');
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }
    public function showsection(Request $request)
    {
        $id                = intval($request->input('section_id'));
        $map[]             = ['id', '=', $id];
        $rs                = DB::table('el_mini_course_section')->where($map)->first();
        $rs->tcvideo_title = DB::table('el_n_zy_tcvideo')->where('id', intval($rs->tcvideo_id))->value('name');
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
        //return responseJson(200, '成功', $returndata);
    }
    //当前优惠券
    public function editsection(Request $request)
    {

        $title          = $request->input('title');
        $homework_info  = $request->input('homework_info');
        $empty_homework = $request->input('empty_homework');
        //$homework_info = $request->homework_info ?: '';
        $is_try     = $request->input('is_try');
        $tcvideo_id = $request->input('tcvideo_id');
        $section_id = intval($request->input('section_id'));
        $sort_id    = intval($request->input('sort_id'));

        if (!empty($section_id)) {
            if (isset($tcvideo_id)) {
                $data['tcvideo_id'] = intval($tcvideo_id);
            }
            if (isset($is_try)) {
                $data['is_try'] = intval($is_try);
            }
            if (!empty($title)) {
                $data['title'] = $title;
            }
            if (isset($homework_info)) {
                $data['homework_info'] = $homework_info;
            }
            if ($empty_homework) {
                $data['homework_info'] = '';
            }
            if (!empty($sort_id)) {
                $data['sort_id'] = $sort_id;
            }
            //var_dump($homework_info);
            DB::table('el_mini_course_section')->where('id', $section_id)->update($data);
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);
    }

    //当前优惠券
    public function delsection(Request $request)
    {
        $section_id = intval($request->input('section_id'));
        if (!empty($section_id)) {
            $data['is_del'] = 1;
            DB::table('el_mini_course_section')->where('id', $section_id)->update($data);
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);
    }

    //当前优惠券
    public function delsectioninfo(Request $request)
    {
        $section_id     = intval($request->input('section_id'));
        $data['status'] = $this->statusInfo($section_id);
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $data]);
    }

    //当前优惠券
    public function statusInfo($id = 0)
    {
        $insaleSkuNumPid = $this->allsaleSkuNumPid($id);
        if ($insaleSkuNumPid > 0) {
            return 2;
        }
        $totalCourseNum = $this->totalCourseNum($id);
        if ($totalCourseNum > 0) {
            return 3;
        }
        return 1;
    }

    public function addsection(Request $request)
    {
        $title         = $request->input('title');
        $homework_info = $request->input('homework_info');
        $is_try        = intval($request->input('is_try'));
        $tcvideo_id    = intval($request->input('tcvideo_id'));
        $course_id     = intval($request->input('course_id'));
        $pid           = intval($request->input('pid'));
        $sort_id       = intval($request->input('sort_id'));

        if (!empty($tcvideo_id)) {
            $data['tcvideo_id'] = $tcvideo_id;
        }
        if (isset($is_try)) {
            $data['is_try'] = $is_try;
        }
        if (!empty($homework_info)) {
            $data['homework_info'] = $homework_info;
        }
        if (!empty($course_id)) {
            $data['course_id'] = $course_id;
        }
        if (!empty($pid)) {
            $data['pid'] = $pid;
        }
        if (!empty($sort_id)) {
            $data['sort_id'] = $sort_id;
        }
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (empty($sort_id) && $pid == 0) {
            $where['pid']       = 0;
            $where['course_id'] = $course_id;
            $data['sort_id']    = intval(DB::table('el_mini_course_section')->where($where)->orderBy('sort_id', 'desc')->take(1)->value('sort_id')) + 1;
        }
        if (empty($sort_id) && $pid > 0) {

            $wheres['course_id'] = $course_id;
            $data['sort_id']     = intval(DB::table('el_mini_course_section')->where($wheres)->orderBy('sort_id', 'desc')->take(1)->value('sort_id')) + 10;
        }
        $rs = 0;
        if (!empty($title)) {
            $rs = DB::table('el_mini_course_section')->insertGetId($data);
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
    }

    public function sectionindex(Request $request)
    {
        $id                = intval($request->input('course_id'));
        $data['course_id'] = $id;
        $data['pid']       = 0;
        $data['is_del']    = 0;
        $rs                = DB::table('el_mini_course_section')->select('id', 'title', 'sort_id')->where($data)->orderBy('sort_id', 'asc')->orderBy('id', 'asc')->get();
        $rs                = objectToArray($rs);
        foreach ($rs as $key => $value) {
            $where['pid']         = $value['id'];
            $where['is_del']      = 0;
            $rs[$key]['datalist'] = DB::table('el_mini_course_section')->select('id', 'title', 'homework_info', 'tcvideo_id', 'sort_id', 'is_try')->where($where)->orderBy('sort_id', 'desc')->orderBy('id', 'desc')->get();
            $rs[$key]['datalist'] = objectToArray($rs[$key]['datalist']);
            foreach ($rs[$key]['datalist'] as $kk => $vv) {
                $rs[$key]['datalist'][$kk]['is_homework']   = !empty($vv['homework_info']) ? 1 : 0;
                $rs[$key]['datalist'][$kk]['tcvideo_title'] = DB::table('el_n_zy_tcvideo')->where('id', $vv['tcvideo_id'])->value('name');
            }
            $rs[$key]['datalist']          = objectToArray($rs[$key]['datalist']);
            $rs[$key]['update_course_num'] = $this->updateCourseNum($value['id'], $id);
            $rs[$key]['total_course_num']  = $this->totalCourseNum($value['id'], $id);
            $rs[$key]['insale_sku_num']    = $this->insaleSkuNumPid($value['id']);

        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
        //return responseJson(200, '成功', $returndata);
    }
    public function updateCourseNum($pid = 0, $course_id)
    {
        $where[] = ['pid', '=', $pid];
        $where[] = ['is_del', '=', 0];
        $where[] = ['course_id', '=', $course_id];
        $where[] = ['tcvideo_id', '>', 0];
        $RS      = DB::table('el_mini_course_section')->where($where)->count();
        return $RS;
    }
    public function totalCourseNum($pid = 0, $course_id = 0)
    {
        $where['pid']    = $pid;
        $where['is_del'] = 0;
        if ($course_id > 0) {
            $where['course_id'] = $course_id;
        }
        $RS = DB::table('el_mini_course_section')->where($where)->count();
        return $RS;
    }
    //当前优惠券
    public function evaluationindex(Request $request)
    {

        $course_id         = intval($request->input('course_id'));
        $data['course_id'] = intval($course_id);
        $returndata        = DB::table('el_mini_course_evaluation')->select('id', 'uid', 'star', 'sort_id', 'is_hide', 'created_at', 'sku_buy_time', 'sku_buy_time', 'sku_name')->where($data)->orderBy('created_at', 'desc')->paginate(10);
        foreach ($returndata->items() as $k => &$v) {
            $v->created_day = date('Y-m-d', strtotime($v->created_at));
            $v->sku_buy_day = !empty($v->sku_buy_time) ? date('Y-m-d', strtotime($v->sku_buy_time)) : "";
            //$v->sku_name    = "111";
            $v->uname = gotUserName($v->uid);
        }
        $rest['star_num'] = $this->starNum($course_id);
        $rest['list']     = $returndata;
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $rest]);

    }

    //当前优惠券
    public function showevaluation(Request $request)
    {

        $id                      = intval($request->input('id'));
        $data['id']              = $id;
        $returndata              = DB::table('el_mini_course_evaluation')->select('id', 'uid', 'review_content', 'quick_title', 'star', 'sort_id', 'is_hide', 'created_at', 'sku_buy_time', 'sku_name')->where($data)->first();
        $returndata->created_day = date('Y-m-d', strtotime($returndata->created_at));
        $returndata->uname       = gotUserName($returndata->uid);
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }
    //当前优惠券
    public function starNum($course_id = 0)
    {
        $data['course_id'] = $course_id;
        $c                 = DB::table('el_mini_course_evaluation')->where($data)->count() ?: 10;
        $star              = DB::table('el_mini_course_evaluation')->where($data)->sum('star');
        return sprintf("%.1f", $star / $c);

    }

    //当前优惠券
    public function editevaluation(Request $request)
    {

        $is_hide = $request->input('is_hide');
        $sort_id = intval($request->input('sort_id'));
        $id      = intval($request->input('id'));
        if (!empty($id)) {
            if (!empty($sort_id)) {
                $data['sort_id'] = $sort_id;
            }
            if (isset($is_hide)) {
                $data['is_hide'] = intval($is_hide);
            }
            DB::table('el_mini_course_evaluation')->where('id', $id)->update($data);
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);
    }

    //noevaluausers
    public function noevusers(Request $request)
    {
        $course_id = intval($request->input('course_id'));
        //购买过课程的uid。去除评价过的uid
        $data['course_id'] = $course_id;
        // $returndata          = DB::table('el_mini_course_evaluation')->where($data)->orderBy('id', 'desc')->paginate(10);
        $where['pay_status'] = 3;
        $where['course_id']  = $course_id;
        $notin               = DB::table('el_mini_course_evaluation')->where($data)->groupBy('uid')->pluck('uid');
        $notin               = objectToArray($notin);
        $in                  = DB::table('el_mini_course_order')->where($where)->groupBy('uid')->pluck('uid');
        $in                  = objectToArray($in);

        $returndata = DB::table('el_mini_course_order')->select('uid')->whereIn('uid', $in)->whereNotIn('uid', $notin)->groupBy('uid')->orderBy('id', 'desc')->paginate(10);
        // print_r($returndata);
        foreach ($returndata->items() as $k => &$v) {
            $v->uname       = gotUserName($v->uid);
            $v->created_day = $this->lastBuyTime($course_id, $v->uid);
            $v->sku_name    = $this->lastBuySkuName($course_id, $v->uid);
        }
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }

    //当前优惠券
    public function lastBuyTime($course_id = 0, $uid = 0)
    {
        $where['pay_status'] = 3;
        $where['course_id']  = $course_id;
        $where['uid']        = $uid;
        $rs                  = DB::table('el_mini_course_order')->where($where)->orderBy('updated_at', 'desc')->value('updated_at');
        return date('Y-m-d', strtotime($rs));
    }

    public function lastBuySkuName($course_id = 0, $uid = 0)
    {
        $where['pay_status'] = 3;
        $where['course_id']  = $course_id;
        $where['uid']        = $uid;
        $rs                  = DB::table('el_mini_course_order')->where($where)->orderBy('updated_at', 'desc')->value('sku_name');
        return $rs;
    }

    //noevaluausers
    public function skulist(Request $request)
    {

        $course_id         = intval($request->input('course_id'));
        $data['course_id'] = $course_id;
        $returndata        = DB::table('el_mini_course_sku')->where($data)->orderBy('sort_id', 'desc')->orderBy('id', 'desc')->paginate(10);
        foreach ($returndata->items() as $kk => &$vv) {
            if ($vv->is_fucai > 0) {
                $this->fixstockData($vv->id, $vv->zt_id, $vv->zt_sku_code);
            }
        };
        $where['course_id'] = $course_id;
        $chapterData        = DB::table('el_mini_course_section')->select('id', 'title')->where($where)->get();

        $result = DB::table('el_mini_course_sku')->where($data)->orderBy('sort_id', 'desc')->orderBy('id', 'desc')->paginate(10);
        foreach ($result->items() as $k => &$v) {
            $v->created_day  = date('Y-m-d', strtotime($v->created_at));
            $v->updated_day  = date('Y-m-d', strtotime($v->updated_at));
            $v->chapter_data = $this->chapterData($v->chapter_ids, $chapterData);
            // $v->chapter_data = "控笔和笔画、偏旁部首、间架结构";
            $v->order_num  = $this->skuOrderNum($v->id);
            $v->totalprice = $this->skuTotalprice($v->id);
        }
        //print_r($result);
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $result]);

    }
//当前优惠券
    public function fixstockData($sku_id = 0, $product_id = 0, $no = 0)
    {
        if ($product_id > 0) {
            $rs = (new NewShopServer)->findSKU($product_id, $no);
            $id = intval($rs['data'][0]['id']);
            if ($id > 0) {
                $update['stock_data'] = intval($rs['data'][0]['stock']);
                DB::table('el_mini_course_sku')->where('id', $sku_id)->update($update);
            }
        }

    }
    //当前优惠券
    public function chapterData($chapter_ids = 0, $data = [])
    {
        $ids = explode(',', $chapter_ids);
        $rs  = $data;
        foreach ($rs as $key => $value) {
            $z[$value->id] = $value->title;
        }
        $a = '';
        foreach ($ids as $k => $v) {
            $a .= $z[$v] . "、";
        }
        return rtrim($a, '、');
        return $a;
    }
    //当前优惠券
    public function skuOrderNum($sku_id = 0)
    {
        $where['pay_status']    = 3;
        $where['course_sku_id'] = $sku_id;
        $rs                     = DB::table('el_mini_course_order')->where($where)->count();
        return $rs;
    }

    //当前优惠券
    public function skuTotalprice($sku_id = 0)
    {
        $where['pay_status']    = 3;
        $where['course_sku_id'] = $sku_id;
        $rs                     = DB::table('el_mini_course_order')->where($where)->SUM('totalprice');
        return $rs;
    }

    //当前优惠券
    public function editsku(Request $request)
    {

        $sku_name             = $request->input('sku_name');
        $price                = $request->input('price');
        $sort_id              = $request->input('sort_id');
        $first_sell_recommend = $request->input('first_sell_recommend');
        $special_sell         = $request->input('special_sell');
        $is_fucai             = $request->input('is_fucai');
        $pid                  = intval($request->input('pid'));
        $sort_id              = intval($request->input('sort_id'));
        $sku_id               = $request->input('sku_id');
        $fucai_title          = $request->input('fucai_title');
        $zt_id                = $request->input('zt_id');
        $zt_sku_code          = $request->input('zt_sku_code');
        $chapter_ids          = $request->input('chapter_ids');
        $sku_recommend_ids    = $request->input('sku_recommend_ids');
        $limit_price          = $request->input('limit_price');

        if (!empty($sku_name)) {
            $data['sku_name'] = $sku_name;
        }
        if (!empty($sku_recommend_ids)) {
            $data['sku_recommend_ids'] = $this->sortData($sku_recommend_ids);
        }
        if (!empty($chapter_ids)) {
            $data['chapter_ids'] = $this->sortData($chapter_ids);
        }
        if (isset($price)) {
            $data['price'] = $price;
        }
        if (isset($limit_price)) {
            $data['limit_price'] = $limit_price;
        }
        if (!empty($sort_id)) {
            $data['sort_id'] = intval($sort_id);
        }
        if (isset($first_sell_recommend)) {
            $data['first_sell_recommend'] = intval($first_sell_recommend);
        }
        if (isset($is_fucai)) {
            $data['is_fucai'] = intval($is_fucai);
        }
        if (isset($special_sell)) {
            $data['special_sell'] = intval($special_sell);
        }
        if (!empty($zt_id)) {
            $data['zt_id'] = $zt_id;
        }
        if (!empty($fucai_title)) {
            $data['fucai_title'] = $fucai_title;
        }
        if (!empty($zt_sku_code)) {
            $data['zt_sku_code'] = $zt_sku_code;
        }
        if (!empty($sku_id)) {
            DB::table('el_mini_course_sku')->where('id', $sku_id)->update($data);
            $vvs = DB::table('el_mini_course_sku')->where('id', $sku_id)->first();
            if ($vvs->is_fucai > 0) {
                $this->fixstockData($vvs->id, $vvs->zt_id, $vvs->zt_sku_code);
            }
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => null]);
    }
    public function sortData($chapter_ids = 0)
    {
        $chapter_ids = explode(',', $chapter_ids);
        sort($chapter_ids);
        $diff = implode(',', $chapter_ids);
        $diff = trim($diff, ',');
        return $diff;

    }

    public function addsku(Request $request)
    {

        $sku_name             = $request->input('sku_name');
        $price                = $request->input('price');
        $limit_price                = $request->input('limit_price');
        $sort_id              = $request->input('sort_id');
        $first_sell_recommend = intval($request->input('first_sell_recommend'));
        $is_fucai             = intval($request->input('is_fucai'));
        $course_id            = intval($request->input('course_id'));
        $pid                  = intval($request->input('pid'));
        $sort_id              = intval($request->input('sort_id'));
        $zt_id                = $request->input('zt_id');
        $zt_sku_code          = $request->input('zt_sku_code');
        $sku_recommend_ids    = $request->input('sku_recommend_ids');
        $fucai_title          = $request->input('fucai_title');
        $chapter_ids          = $request->input('chapter_ids');
        $special_sell         = $request->input('special_sell');
        if (!empty($sku_name)) {
            $data['sku_name'] = $sku_name;
        }
        if (!empty($chapter_ids)) {
            $data['chapter_ids'] = $this->sortData($chapter_ids);
        }
        if (!empty($sku_recommend_ids)) {
            $data['sku_recommend_ids'] = $this->sortData($sku_recommend_ids);
        }
        if (!empty($fucai_title)) {
            $data['fucai_title'] = $fucai_title;
        }
        if (isset($price)) {
            $data['price'] = $price;
        }
        if (isset($limit_price)) {
            $data['limit_price'] = $limit_price;
        }
        if (isset($special_sell)) {
            $data['special_sell'] = intval($special_sell);
        }
        if (!empty($sort_id)) {
            $data['sort_id'] = intval($sort_id);
        }
        if (empty($sort_id)) {
            $where['course_id'] = $course_id;
            $data['sort_id']    = intval(DB::table('el_mini_course_sku')->where($where)->orderBy('sort_id', 'desc')->take(1)->value('sort_id')) + 10;
        }
        if (!empty($first_sell_recommend)) {
            $data['first_sell_recommend'] = $first_sell_recommend;
        }
        if (!empty($is_fucai)) {
            $data['is_fucai'] = $is_fucai;
        }
        if (!empty($special_sell)) {
            $data['special_sell'] = $special_sell;
        }
        if (!empty($zt_id)) {
            $data['zt_id'] = $zt_id;
        }
        if (!empty($zt_title)) {
            $data['zt_title'] = $zt_title;
        }
        if (!empty($zt_sku_code)) {
            $data['zt_sku_code'] = $zt_sku_code;
        }
        if (!empty($course_id)) {
            $data['course_id'] = $course_id;
        }
        if (!empty($sku_name)) {
            $rs  = DB::table('el_mini_course_sku')->insertGetId($data);
            $vvs = DB::table('el_mini_course_sku')->where('id', $rs)->first();
            if ($vvs->is_fucai > 0) {
                $this->fixstockData($vvs->id, $vvs->zt_id, $vvs->zt_sku_code);
            }
        }
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $rs]);
    }

    //当前优惠券
    public function showsku(Request $request)
    {
        $where['id'] = $id = $request->input('sku_id');
        $returndata  = DB::table('el_mini_course_sku')->where($where)->first();

        // $notin       = [$id];
        // //print_r($notin);
        // $post['course_id'] = $course_id = $returndata->course_id;
        // $sku_recommend_ids = $returndata->sku_recommend_ids;
        // $sku_arr           = explode(',', $sku_recommend_ids);
        // $chapter_ids       = $returndata->chapter_ids;
        // $chapter_arr       = explode(',', $chapter_ids);
        // // print_r($sku_arr);
        // $sku_data = DB::table('el_mini_course_sku')->select('id', 'sku_name')->where($post)->whereNotIn('id', $notin)->get();
        // $sku_data = objectToArray($sku_data);
        // // print_r($sku_data);
        // foreach ($sku_data as $key => $value) {
        //     $sku_data[$key]['checkbox'] = 0;
        //     if (in_array($value['id'], $sku_arr)) {
        //         $sku_data[$key]['checkbox'] = 1;
        //     }
        // }
        // $ds['course_id'] = $course_id;
        // $ds['pid']       = 0;
        // $chapter_data    = DB::table('el_mini_course_section')->select('id', 'title')->where($ds)->get();
        // $chapter_data    = objectToArray($chapter_data);
        // foreach ($chapter_data as $kk => $vv) {
        //     $chapter_data[$kk]['checkbox'] = 0;
        //     if (in_array($vv['id'], $chapter_arr)) {
        //         $chapter_data[$kk]['checkbox'] = 1;
        //     }
        // }
        // $returndata->sku_data     = $sku_data;
        // $returndata->chapter_data = $chapter_data;
        return response()->json(['status' => 0, "msg" => '成功', 'data' => $returndata]);

    }
    //当前优惠券
    public function assistsku(Request $request)
    {
        $course_id           = $request->input('course_id');
        $sku_id              = intval($request->input('sku_id'));
        $where['course_id']  = $course_id;
        $where['is_publish'] = 1;

        $data['sku_data']     = DB::table('el_mini_course_sku')->select('id', 'sku_name')->where($where)->orderBy('sort_id', 'desc')->whereNotIn('id', [$sku_id])->orderBy('id', 'desc')->get();
        $where1['course_id']  = $course_id;
        $data['sku_data_all'] = DB::table('el_mini_course_sku')->select('id', 'sku_name', 'is_publish')->where($where1)->get();
        $ds['course_id']      = $course_id;
        $ds['pid']            = 0;
        $ds['is_del']         = 0;
        $data['chapter_data'] = DB::table('el_mini_course_section')->select('id', 'title')->where($ds)->get();
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $data]);
    }
    //当前优惠券
    public function centerskulist(Request $request)
    {
        $where['title'] = urlencode($request->input('title'));
        $where['id']    = urlencode($request->input('zt_id'));

        $force = $request->input('force');
        $data  = (new NewShopServer)->ztProductListByData($where, $force);
        return response()->json(['code' => 200, "msg" => '成功', 'data' => $data]);
        /// print_r($token);
    }

}
