<?php

namespace App\Http\Controllers\Lptapp;

use App\Models\LptApp\CouponActivityUser;
use App\Services\PageServe;
use Illuminate\Http\Request;
use App\Http\Controllers\TraitBackendOperation;

use App\Http\Resources\LptApp\CouponActivityUserResource;
use App\Http\Resources\LptApp\CouponActivityUserListResource;

class CouponActivityUserController extends Controller
{
    use TraitBackendOperation;

    /**
     * @group v302-后台管理
     *
     * ca-u-l 领券记录列表
     *
     * @queryParam coupon_name_id optional string 券名或代码
     * @queryParam user_name_id optional string 用户ID或用户名
     * @queryParam time_type optional string 时间类型[get:领券时间;use:使用时间;end:失效时间]
     * @queryParam time_info optional string 时间 如 2023-05-17 10:00:00 | 2023-05-18 10:00:00
     *
     * @responseFile responses/coupon/coupon-activity-user-list.json
     */
    public function list(Request $request)
    {
        $data = $this->getModel()->query()
			->orderByDesc('id')
			->paginate($request->input('per_page',10));

		$data->map(function ($item){
            $item->user_name = $item->userInfo ? $item->userInfo->user_name : '';
            $item->status = $item->formatStatus();
            $item->brief = $item->formatBrief();
            $item->channel = $item->formatChannel();
            $item->created_at = $item->created_at->toDateTimeString();

			return $item;
		});

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
        return response()->json(['status' => 0, 'msg' => '成功', 'data' => ['url' => 'http://192.168.203.9:8284/docs/download/兑换码1684293240.xlsx']]);
    }

    protected function getResource($datas, $type = '')
    {
        switch ($type) {
        case 'listinfo':
            return CouponActivityUserListResource::collection($datas);
        default:
            return CouponActivityUserResource::collection($datas);
        }
    }

    protected function getModel()
    {
        return new CouponActivityUser();
    }
}
