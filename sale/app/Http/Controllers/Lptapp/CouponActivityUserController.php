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
     * @queryParam name optional string 活动名称
     * @queryParam status optional string 活动状态
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
