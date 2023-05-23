<?php

namespace App\Models\LptApp;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Services\CouponActivityService;

class CouponActivity extends Model
{
    protected $table = 'el_coupon_activity';
    protected $guarded = ['id'];

    public function createActivityRecord($params)
    {
        $fields = ['name', 'activity_type', 'banner_doc', 'tag_doc', 'status', 'picture_url', 'start_at', 'end_at'];
        $data = [];
        foreach ($fields as $field) {
            $value = $params[$field] ?? '';
            if (empty($value) && in_array($field, ['start_at', 'end_at'])) {
                $value = null;
            }
            $data[$field] = $value;
        }
        //$data['status'] = 1;
        $info = $this->create($data);
        $batchIds = $params['batch_ids'] ?? '';
        $info->createBatch($info, $batchIds);
        return true;
    }

    public function createBatch($info, $batchIds)
    {
        $batchIds = str_replace(' ', '', $batchIds);
        $batchIds = explode(',', $batchIds);
        $batchIds = array_filter($batchIds);
        if (empty($batchIds)) {
            return false;
        }
        $newModel = new CouponActivityBatch();
        foreach ($batchIds as $batchId) {
            $exist = $newModel->where(['batch_id' => $batchId])->first();
            if ($exist) {
                continue;
            }
            $data = [
                'batch_id' => $batchId,
                'coupon_activity_id' => $info['id'],
            ];
            $newModel->create($data);
        }
        $service = new CouponActivityService();
        $service->dealNotice($batchIds);
        return true;
    }

    public function updateInfo($params)
    {
        $fields = ['name', 'banner_doc', 'tag_doc', 'status', 'picture_url'];
        $data = [];
        foreach ($fields as $field) {
            $value = $params[$field] ?? '';
            $this->$field = $value;
        }
        $this->save();
        $batchIds = $params['batch_ids'] ?? '';
        $this->createBatch($this, $batchIds);
        return true;
    }

    public function getActivityTypeDatas($code = null)
    {
        $datas = [
            'new' => '新人',
            'back' => '回归',
            'event' => '事件',
        ];
        if (is_null($code)) {
            return $datas;
        }
        return $datas[$code] ?? $code;
    }

    public function formatStatusDatas()
    {
        return [
            'nopublish' => '未发布',
            'nostart' => '未开始',
            'finish' => '已结束',
            'running' => '进行中',
        ];
    }

    public function formatStatus()
    {
        if ($this->status === 0) {
            return '未发布';
        }
        $now = Carbon::now();
        if ($this->start_at && $now < Carbon::parse($this->start)) {
            return '未开始';
        }
        $batchModel = new CouponActivityBatch();
        $validCount = $batchModel->where('coupon_activity_id', $this->id)->whereColumn('send_num', '<', 'total_num')->count();
        if ($validCount) {
            return '已结束';
        }
        return '进行中';
    }

    public function batchDatas()
    {
        return $this->hasMany(CouponActivityBatch::class, 'coupon_activity_id', 'id');
    }

    public function formatExpiration()
    {
        if (empty($this->start_at) && empty($this->end_at)) {
            return '一直有效';
        }
        $str = '';
        $str .= !empty($this->start_at) ? "{$this->start_at} 开始" : '';
        $str .= !empty($this->end_at) ? "{$this->end_at} 结束" : '';
        return $str;
    }

    public function getBatchDatas()
    {
        $batchDatas = CouponActivityBatch::where(['coupon_activity_id' => $this->id])->get();
        $results = [];
        $statusValues = [1 => '未开始', 2 => '进行中', 3 => '已结束', 4 => '已失效'];

        foreach ($batchDatas as $batch) {
            $results[] = [
                'name' => $batch['name'],
                'brief' => $batch['brief'],
                'status_value' => $statusValues[$batch['status']] ?? $batch['status'],
                'total_num' => $batch['total_num'],
                'time_desc' => $batch['time_type'] == 1 ? "{$batch['time_desc']} 天内有效" : $batch['time_desc'],
            ];
        }
        return $results;
    }
}
