<?php

namespace App\Models\LptApp;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\CommonOperationTrait;

class CouponActivityUser extends Model
{
    use CommonOperationTrait;

    protected $table = 'el_coupon_activity_user';

    protected $guarded = ['id'];

    public function couponActivityBatch()
    {
        return $this->belongsTo(CouponActivityBatch::class, 'batch_id', 'id');
    }

    public function couponActivity()
    {
        return $this->belongsTo(CouponActivity::class, 'activity_id', 'id');
    }

    public function formatBrief()
    {
        return '';
    }

    public function formatStatus()
    {
        return '';
    }

    public function formatChannel()
    {
        return '批量增发';
    }
}
