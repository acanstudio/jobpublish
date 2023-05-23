<?php

namespace App\Models\LptApp;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CouponActivityUser extends Model
{

    protected $table = 'el_coupon_activity_user';

    protected $guarded = ['id'];

    public function userData()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }

    public function couponActivityBatch()
    {
        return $this->belongsTo(CouponActivityBatch::class, 'batch_id', 'id');
    }

    public function couponActivity()
    {
        return $this->belongsTo(CouponActivity::class, 'activity_id', 'id');
    }

    public function formatStatus()
    {
        if (!empty($this->used_at)) {
            return '已使用';
        }

        $now = Carbon::now();
        if (empty($this->end_at) || $now < Carbon::parse($this->end_at)) {
            return '未使用';
        }
        return '已过期';
    }
}
