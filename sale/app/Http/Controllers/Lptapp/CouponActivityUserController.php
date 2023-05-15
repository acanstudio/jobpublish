<?php

namespace App\Http\Controllers\Lptapp;

use App\Models\LptApp\CouponActivityUser;
use App\Services\PageServe;
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
    public function list(PageServe $serve)
    {
        return $this->_index($serve);
        $return = [];
        return responseJsonHttp(200, 'success', $return);
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
