<?php

namespace App\Http\Resources\LptApp;

use Illuminate\Http\Resources\Json\Resource;

class CouponActivityUserResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $fields = ['coupon', 'name', 'name', 'uid', 'user_name', 'status', 'created_at', 'use_at', 'end_at', 'orderid', 'channel'];
        $data = [
            'coupon' => $this->coupon,
            'name' => $this->name,
            'uid' => $this->uid,
            'userName' => $this->userInfo ? $this->userInfo->user_name : '',
            'status' => $this->formatStatus(),
            'useAt' => $this->use_at,
            'endAt' => $this->end_at,
            'orderid' => $this->orderid,
            'channel' => $this->formatChannel(),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
        return $data;
    }
}

