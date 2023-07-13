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
        $fields = ['name', 'activity_type', 'banner_doc', 'jump_path', 'tag_doc', 'status', 'picture_url', 'start_at', 'end_at'];
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
        $newModel->where(['coupon_activity_id' => $info['id']])->whereNotIn('batch_id', $batchIds)->where('send_num', 0)->delete();
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
        $fields = ['name', 'banner_doc', 'tag_doc', 'status', 'picture_url', 'jump_path'];
        $data = [];

        foreach ($fields as $field) {
            if (!isset($params[$field])) {
                continue;
            }
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
        $batchModel = new CouponActivityBatch();
        $nowDate = $now->format('Y-m-d H:i:s');
        if (!empty($this->end_at) && $this->end_at < $nowDate) {
            return '已结束';
        }
        $validCount = $batchModel->whereIn('status', [1, 2])->where('coupon_activity_id', $this->id)->whereColumn('send_num', '<', 'total_num')->where(function ($query) use ($nowDate) {
            $query->whereNull('end_at')->orWhere('end_at', '>', $nowDate);
        })->count();
        if (!$validCount) {
            return '已结束';
        }
        if ($this->start_at && $now < Carbon::parse($this->start_at)) {
            return '未开始';
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
        $service = new CouponActivityService();

        foreach ($batchDatas as $batch) {
            $batch['status'] = $service->formatBatchStatus($batch);
            $results[] = [
                'batch_id' => $batch['batch_id'],
                'name' => $batch['name'],
                'send_num' => $batch['send_num'],
                'brief' => $batch['brief'],
                'status_value' => $statusValues[$batch['status']] ?? $batch['status'],
                'total_num' => $batch['total_num'],
                'time_desc' => $batch['time_type'] == 1 ? "{$batch['time_desc']}" : $batch['time_desc'],
            ];
        }
        return $results;
    }

    public function publishInfo()
    {
        if ($this->status == 1) {
            return ['publish_status' => 0, 'publish_text' => ''];
        }

        $type = $this->activity_type;
        $id = $this->id;
        $cDate = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM `el_coupon_activity` AS `ca`, `el_coupon_activity_batch` AS `b` WHERE `ca`.`status` = 1 AND `ca`.`id` != {$id} AND `ca`.`activity_type` = '{$type}' AND `ca`.`id` = `b`.`coupon_activity_id` AND `b`.`status` IN (1, 2) AND `b`.`total_num` > `b`.`send_num` AND (`b`.`end_at` IS NULL OR `b`.`end_at` > '{$cDate}') ;";
        if ($type == 'evene') {
            $sql .= " AND ((`ca`.`start_at` >= '{$this->start_at}' && `ca`.start_at` <= '{$this->end_at}') || (`ca`.`end_at` >= '{$this->start_at}' && `ca`.`end_at` <= '{$this->end_at}'))";
        }
        $r = \DB::select($sql);

        $validCount = count($r);


        $texts = [
            'new' => '当前已有“进行中”的新人活动。如仍需发布，将对“进行中”的新人活动予以取消发布。 确定要继续发布吗？', 
            'back' => '当前已有“进行中”的回归活动。如仍需发布，将对“进行中”的回归活动予以取消发布。确定要继续发布吗？', 
            'event' => '该活动与其他事件活动周期重合，重合期内将按最新创建的活动向目标人群发券。确定要继续发布吗？'
        ];
        return [
            'publish_status' => $validCount ? 1 : 0,
            'publish_text' => $validCount ? $texts[$type] : '',
        ];
    }
}
