<?php

namespace App\Models\LptApp;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\CommonOperationTrait;

class CouponActivityBatch extends Model
{
    use CommonOperationTrait;

    protected $table = 'el_coupon_activity_batch';
    protected $guarded = ['id'];

}
