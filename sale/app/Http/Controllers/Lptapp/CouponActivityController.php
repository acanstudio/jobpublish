<?php

namespace App\Http\Controllers\Lptapp;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\LptApp\CouponActivity;
use App\Services\PageServe;
use App\Services\CouponActivityService;

class CouponActivityController extends Controller
{

    /**
     * @group v302-后台管理
     *
     * cc-n 优惠券批次信息更新通知
     *
     * @queryParam batchId required int 批次ID
     *
     * @response 200 {
     * "code": 0,
     * "message": "OK",
     * "data": {}
     * }
     */
    public function couponNotice()
    {
        $batchId = request()->input('id');
        //print_r(request()->all());
        $bStr = serialize(request()->all());//is_array($batchId) ? serialize($batchId) : $batchId;
        \Log::debug('batch-id-' . $bStr);
        if (empty($batchId)) {
		    return response()->json(['status' => 0, 'msg' => '成功0', 'data' => (object)[]]);
        }
        $service = new CouponActivityService();
        $service->dealNotice($batchId);
		return response()->json(['status' => 0, 'msg' => '成功1', 'data' => (object)[]]);
    }

    /**
     * @group v302-后台管理
     *
     * cb-l 课程券批次列表
     *
     * @queryParam curPage       int  当前页
     * @queryParam endTime       string 活动结束时间
     * @queryParam name          string 
     * @queryParam pageSize      int  
     * @queryParam startTime     string 活动开始时间
     * @queryParam status        int  1-未开始；2-进行中；3-已结束；4-已失效
     * @queryParam specialStatus int  
     * @queryParam timeType      int  1-天数；2-起止时间
     * @queryParam type          int  1-满减；2-立减
     * @queryParam useTypes      int 使用类型；按业务线区分；具体可从“业务线优惠券类型”接口获取
     *
     * @responseFile responses/coupon/batch-list.json
     */
    public function batchList(PageServe $serve)
    {
        $service = new CouponActivityService();
        $datas = $service->getBatchList(request()->all());
        //$datas = $service->getCoupon(request()->all());
        //$datas = $service->useCoupon([]);
        //$datas = $service->getBatchType(request()->all());
        return response()->json(['status' => 0, 'msg' => '成功', 'data' => $datas]);
    }

    /**
     * @group v302-后台管理
     *
     * ca-l 课程券活动列表
     *
     * @queryParam name optional string 活动名称
     * @queryParam status optional string 活动状态[nopublish:未发布;nostart: 未开始;  finish: 已结束;running:进行中]
     *
     * @responseFile responses/coupon/coupon-activity-list.json
     */
    public function list(PageServe $serve, Request $request)
    {
        /*$extData = [
            'types' => $this->getModel()->getActivityTypeDatas(),
            'status' => $this->getModel()->formatStatusDatas(),
        ];*/
        $name = request()->input('name', '');
        $query = $this->getModel()->query();
        if (!empty($name)) {
            $query = $query->where('name', 'like', "%{$name}%");
        }
        $status = request()->input('status', '');
        $cDate = date('Y-m-d H:i:s');
        if (!empty($status)) {
            switch ($status) {
            case 'nopublish':
                $query = $query->where(['status' => 0]);
                break;
            case 'nostart':
                $query = $query->where('start_at', '>', $cDate);
                break;
            case 'running':
                $query = $query->where(function ($query) use ($cDate) {
                    $query->whereNull('end_at')->whereOr('end_at', '>', $cDate);
                })->where(function($query) {
                    $query->whereHasIn('batchDatas', function ($query) {
                        return $query->whereColumn('total_num', '>', 'send_num')->where('status', 2);
                    });
                });
                break;
            case 'finish':
                $query = $query->whereNotNull('end_at')->where('end_at', '<', $cDate)->where(function($query) {
                    $query->whereHasIn('batchDatas', function ($query) {
                        return $query->whereColumn('total_num', '<=', 'send_num')->orWhere('status', '<>', 2);
                    });
                });
                break;
            }
        }

        $data = $query->orderByDesc('id')->paginate($request->input('per_page',10));

		$data->map(function ($item){
            $item->activity_type_value = $item->getActivityTypeDatas($item->activity_type);
            $item->status_value = $item->formatStatus();
            $item->created_at = $item->created_at->toDateTimeString();
            $item->expiration_date = $item->formatExpiration();

            $item->batch_infos = $item->getBatchDatas();
			return $item;
		});
        //$data['extData'] = $extData;

        return response()->json(['status' => 0, 'msg' => '成功', 'data' => $data]);
    }

    /**
     * @group v302-后台管理
     *
     * ca-a 添加课程券活动
     *
     * @bodyParam activity_type required string 活动类型
     * @bodyParam name required string 活动名称
     * @bodyParam batch_ids required string 关联的优惠券批次号，如 1,2,3
     * @bodyParam picture_url required string 弹窗图片
     * @bodyParam coupon_desc required string 券banner文案
     * @bodyParam tag_desc required string 标签文案
     * @bodyParam start_at required string 起始时间
     * @bodyParam end_at required string 截止时间
     *
     * @response 400 {
     * "code": 1,
     * "message": "参数有误*",
     * "data": {}
     * }
     * @response 200 {
     * "code": 0,
     * "message": "OK",
     * "data": {}
     * }
     */
    public function add()
    {
        $params = request()->all();
        $validatorInfo = Validator::make($params, [
            'name' => 'required|string',
            'batch_ids' => 'required|string',
            'activity_type' => 'required|in:new,event,back',
        ]);
        if ($validatorInfo->fails()) {
            return responseJsonHttp(1, $validatorInfo->errors()->first());
        }
        $return = $this->getModel()->createActivityRecord($params);

		return response()->json(['status' => 0, 'msg' => '添加成功', 'data' => (object)[]]);
    }

    /**
     * @group v302-后台管理
     *
     * ca-u 编辑课程券活动
     *
     * @bodyParam id required string 信息ID
     * @bodyParam activity_type string 活动类型
     * @bodyParam name string 活动名称
     * @bodyParam batch_ids string 关联的优惠券批次号，如 1,2,3
     * @bodyParam picture_url string 弹窗图片
     * @bodyParam coupon_desc string 券banner文案
     * @bodyParam tag_desc string 标签文案
     * @bodyParam start_at string 起始时间
     * @bodyParam end_at string 截止时间
     *
     * @response 400 {
     * "code": 1,
     * "message": "参数有误*",
     * "data": {}
     * }
     * @response 200 {
     * "code": 0,
     * "message": "OK",
     * "data": {}
     * }
     */
    public function update()
    {
        $params = request()->all();
        $validatorInfo = Validator::make($params, [
            'id' => 'required|exists:el_coupon_activity',
            'activity_type' => 'nullable|in:new,event,back',
        ]);
        if ($validatorInfo->fails()) {
            return responseJsonHttp(1, $validatorInfo->errors()->first());
        }
        $info = $this->getModel()->find($params['id']);
        $result = $info->updateInfo($params);
		return response()->json(['status' => 0, 'msg' => '保存成功', 'data' => (object)[]]);
    }

    /**
     * @group v302-后台管理
     *
     * ca-uc 取消课程券活动
     *
     * @bodyParam id required string 信息ID
     * @bodyParam cancel required int 1: 取消发布; 0 ：或其他值为发布当前活动
     *
     * @response 400 {
     * "code": 200,
     * "message": "参数有误*",
     * "data": {}
     * }
     * @response 200 {
     * "code": 200,
     * "message": "OK",
     * "data": {}
     * }
     */
    public function publish()
    {
        $params = request()->all();
        $validatorInfo = Validator::make($params, [
            'id' => 'required|exists:el_coupon_activity',
        ]);
        if ($validatorInfo->fails()) {
            return responseJsonHttp(1, $validatorInfo->errors()->first());
        }
        $info = $this->getModel()->find($params['id']);
        $info->status = isset($params['cancel']) && $params['cancel'] == 1 ? 0 : 1;
        $info->save();
		return response()->json(['status' => 0, 'msg' => '保存成功', 'data' => (object)[]]);
    }

    protected function getModel()
    {
        return new CouponActivity();
    }

    public function test()
    {
        $midPath = '/home/www/selfpath/';
        $elems = [
            'old' => [
                'basePath' => '/usr/local/nginx/html/selfdev_liupin/',
                'targetPath' => '/usr/local/nginx/html/old.liupinshuyuan.com/',
            ],
            'sale' => [
                'basePath' => base_path(),
                'targetPath' => '/usr/local/nginx/html/sale',
            ],
        ];

        foreach ($elems as $elem => $eInfo) {
            $basePath = $eInfo['basePath'];
            $targetPath = $eInfo['targetPath'];
            $files = file($midPath . "/{$elem}.txt");
            $cpCommand = $publishCommand = $sCommand = $testCommand = '';
            foreach ($files as $file) {
                $file = trim($file);
                $midFile = "{$midPath}/{$elem}/{$file}";
                $mPath = dirname($midFile);
                if (!is_dir($mPath)) {
                    $cpCommand .= "mkdir {$mPath} -p;\n";
                }
                $sFile = $targetPath . '/' . $file;
                if (true) {//file_exists($sFile)) {
                    $sCommand .= "cp {$sFile} {$midFile}\n";
                }
                $cpCommand .= "cp {$basePath}/{$file} {$midFile}\n";
    
                $tFile = "{$targetPath}/{$file}";
                $tPath = dirname($tFile);
                if (!is_dir($tPath)) {
                    $publishCommand .= "mkdir {$tPath};\n";
                }
                $publishCommand .= "cp {$basePath}/{$file} {$tFile}\n";
                $testCommand .= "cp {$tFile} {$basePath}/{$file}\n";
            }
            echo $sCommand . "\n";
            echo 'fff';
            file_put_contents($midPath . "/{$elem}-source.sh", $sCommand);
            file_put_contents($midPath . "/{$elem}-cp.sh", $cpCommand);
            file_put_contents($midPath . "/{$elem}-publish.sh", $publishCommand);
            file_put_contents($midPath . "/{$elem}-test.sh", $testCommand);
        }
        exit();
    }
}
