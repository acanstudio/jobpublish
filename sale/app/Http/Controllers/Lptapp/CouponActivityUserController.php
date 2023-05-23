<?php

namespace App\Http\Controllers\Lptapp;

use App\Models\LptApp\CouponActivityUser;
use App\Models\LptApp\CouponActivityBatch;
use App\Models\LptApp\CouponActivity;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ExcelDownloadService;
use Maatwebsite\Excel\Facades\Excel;

class CouponActivityUserController extends Controller
{
    /**
     * @group v302-后台管理
     *
     * ca-u-l 领券记录列表
     *
     * @queryParam coupon_name_id optional string 券名或代码
     * @queryParam user_name_id optional string 用户ID或用户名
     * @queryParam time_type optional string 时间类型[get:领券时间;use:使用时间;end:失效时间]
     * @queryParam start_time optional string 开始时间 如 2023-05-17 10:00:00
     * @queryParam end_time optional string 结束时间 如 2023-05-17 10:00:00
     *
     * @responseFile responses/coupon/coupon-activity-user-list.json
     */
    public function list($download = false)
    {
        $request = request();
        $userNameId = request()->input('user_name_id');
        $query = $this->getModel()->query();
        if (!empty($userNameId)) {
            $query = $query->whereHasIn('userData', function ($query) use ($userNameId) {
                $query->where('uid', $userNameId)->orWhere('uname', 'like', "%{$userNameId}%");
            });
        }
        $couponNameId = request()->input('coupon_name_id');
        if (!empty($couponNameId)) {
            $query = $query->where(function($query) use ($couponNameId) {
                $query->where('coupon', $couponNameId)->orWhere('name', 'like', "%{$couponNameId}%");
            });
        }

        $timeType = request()->input('time_type');
        $startTime = request()->input('start_time');
        $endTime = request()->input('end_time');

        if (!empty($timeType) && (!empty($startTime) || !empty($endTime))) {
            $timeFields = ['use' => 'used_at', 'end' => 'end_at', 'get' => 'created_at'];
            $field = $timeFields[$timeType];
            if (!empty($startTime)) {
                $query = $query->where($field, '>=', $startTime);
            }
            if (!empty($endTime)) {
                $query = $query->where($field, '<=', $endTime);
            }
        }

        $pageSize = $download ? 500 : 10;
        $data = $query->orderByDesc('id')->paginate($request->input('per_page', $pageSize));

		$data->map(function ($item){
            $userData = User::find($item->uid);
            $activityData = CouponActivity::find($item->activity_id);
            $batchData = CouponActivityBatch::where(['batch_id' => $item->batch_id])->first();;
            $item->user_name = $userData ? $userData->uname : '';
            $item->status = $item->formatStatus();
            $item->brief = $batchData ? $batchData['brief'] : '';
            $item->channel = $activityData ? $activityData['name'] : '批量发券';
            $item->created_at = $item->created_at->toDateTimeString();

			return $item;
		});
        if ($download) {
            return $data;
        }

        return response()->json(['status' => 0, 'msg' => '成功', 'data' => $data]);
        return $this->_index($serve);
    }

    /**
     * @group v302-后台管理
     *
     * c-a-u-e 优惠券下载
     * 
     * 请求接口直接下载一个excel文件
     *
     * @queryParam coupon_name_id optional string 券名或代码
     * @queryParam user_name_id optional string 用户ID或用户名
     * @queryParam time_type optional string 时间类型[get:领券时间;use:使用时间;end:失效时间]
     * @queryParam time_info optional string 时间 如 2023-05-17 10:00:00 | 2023-05-18 10:00:00
     *
     * @response 200 {
     * "status": 0,
     * "msg": "成功",
     * "data": {
     * "url": "http://192.168.203.9:8284/docs/download/兑换码1684293240.xlsx"
     * }
     * }
     */
    public function export()
    {
        $file = 'export_coupon_user_' . date('Y-m-d-H-i-s') . '.xlsx';
        $datas = $this->list(true);
        $fields = [
            'coupon' => '优惠券ID',
            'name' => '券名称',
            'brief' => '优惠内容',
            'uid' => '用户UID',
            'user_name' => '用户名',
            'status' => '券状态',
            'created_at' => '领券时间',
            'used_at' => '使用时间',
            'end_at' => '失效时间',
            'orderid' => '订单编号',
            'channel' => '发券渠道',
        ];
        $res[] = array_values($fields);
        foreach ($datas as $data) {
            $fData = [];
            foreach ($fields as $field => $fName) {
                $value = $data[$field] ?? '';
                $fData[] = strval($value);
            }
            $res[] = $fData;
        }
        $service    = new ExcelDownloadService();
        $service->setSourceDatas($res);
        return Excel::download($service, $file);
        return response()->json(['status' => 0, 'msg' => '成功', 'data' => ['url' => 'http://192.168.203.9:8284/docs/download/兑换码1684293240.xlsx']]);
    }

    protected function getModel()
    {
        return new CouponActivityUser();
    }
}
