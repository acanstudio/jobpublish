<?php

namespace App\Http\Resources\LptApp;

use Illuminate\Http\Resources\Json\Resource;

class CouponActivityResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $fields = ['name', 'activity_type', 'banner_doc', 'tag_doc', 'status', 'picture_url', 'start_at', 'end_at', 'created_at'];
        $data = [
            'name' => $this->name,
            'activityType' => $this->activity_type,
            'activityTypeValue' => $this->getActivityTypeDatas($this->activity_type),
            'bannerDoc' => $this->banner_doc,
            'tagDoc' => $this->tag_doc,
            'status' => $this->status,
            'statusValue' => $this->formatStatus(),
            'pictureUrl' => $this->picture_url,
            'startAt' => $this->start_at,
            'endAt' => $this->end_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
        return $data;
    }
}

