<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Exceptions\BusinessException;
use App\Models\LptApp\CouponActivityUser;

class CouponActivityService
{
    public function getBatchList($where)
    {
        //$url = '/api/couponBatch/page';
        $url = '/api/couponBatch/pageForPlat';
        $resultSource = $this->fetchRemoteData($url, $where);
        $result = $rData = [];
        foreach ($resultSource['data'] as $data) {
            $brief = $data['type'] == 1 ? '满减' : '立减';
            $brief .= $data['type'] == 1 ? ' ' . $data['cutNum'] : " {$data['fullNum']} - {$data['cutNum']}";
            $data['brief'] = $brief;
            $data['isUse'] = rand(0, 1);
            $rData[] = $data;
        }
        $result = [
            'current_page' => $resultSource['pageIndex'],
            'from' => $resultSource['pageIndex'],
            'total' => $resultSource['totalNum'],
            'per_page' => $resultSource['pageSize'],
            'to' => $resultSource['totalPage'],
            'data' => $rData,
        ];
        return $result;
        return ['data' => $result];
    }

    public function getBatchType($where)
    {
        $url = '/api/couponPlatType/list';
        $result = $this->fetchRemoteData($url, $where);
        return $result;
    }

    public function getCoupon($data)
    {
        $data = [
            'platUsersInfo' => [
                ['platUid' => 1914250],
                ['platUid' => 1914251],
            ],
            'couponBatchId' => 1,
        ];
        $url = '/api/couponDetail/send';
        //$result = $this->fetchRemoteData($url, $data);
        $result = json_decode('{"status":64,"msg":"laborum commodo in nisi","data":[{"id":61,"couponBatchId":36,"useType":94,"timeStart":"1972-04-23 17:36:45","timeEnd":"2004-01-03 07:00:11","name":"老边力地","type":15,"fullNum":"87","cutNum":"34","platUid":96,"uid":11},{"id":13,"couponBatchId":39,"useType":37,"timeStart":"2009-04-11 21:30:33","timeEnd":"2006-12-31 17:39:57","name":"济便日近","type":21,"fullNum":"36","cutNum":"40","platUid":58,"uid":63},{"id":38,"couponBatchId":75,"useType":54,"timeStart":"1984-05-30 19:31:25","timeEnd":"1978-11-27 06:44:04","name":"体求国革","type":15,"fullNum":"97","cutNum":"85","platUid":83,"uid":81},{"id":32,"couponBatchId":16,"useType":34,"timeStart":"1973-03-09 10:23:04","timeEnd":"2013-11-16 23:13:34","name":"就内团表身角","type":48,"fullNum":"48","cutNum":"5","platUid":84,"uid":53},{"id":16,"couponBatchId":95,"useType":11,"timeStart":"1985-10-06 04:10:45","timeEnd":"1973-05-16 11:30:53","name":"习效样西音开","type":86,"fullNum":"65","cutNum":"70","platUid":10,"uid":88}],"totalNum":null,"pageIndex":null,"pageSize":null,"totalPage":null,"error":true,"success":false}', true);
        $coupons = $result['data'] ?? false;
        $model = new CouponActivityUser();
        foreach ((array) $coupons as $coupon) {
            $cData = [
                'batch_id' => $coupon['couponBatchId'],
                'coupon' => $coupon['id'],
                'name' => $coupon['name'],
                'start_at' => $coupon['timeStart'],
                'end_at' => $coupon['timeEnd'],
                'uid' => $coupon['platUid'],
            ];
            $model->create($cData);
        }
        return $result;
    }

    public function useCoupon($data)
    {
        $url = '/api/couponDetail/use';
        $data = [
            'platUid' => 96,
            'id' => 61,
        ];

        $result = $this->fetchRemoteData($url, $data);
        echo json_encode($result);
        //$result = json_decode('{"status":47,"msg":"in eiusmod aute","data":null,"totalNum":null,"pageIndex":null,"pageSize":null,"totalPage":null,"error":true,"success":false}', true);
        //$result = json_decode('{"status":-1,"msg":"\u9519\u8bef\u7528\u6237\uff01","data":null,"totalNum":null,"pageIndex":null,"pageSize":null,"totalPage":null,"error":true,"success":false}{"code":0,"msg":"success","data":{},"totalNum":null,"pageIndex":null,"pageSize":null,"totalPage":null,"error":true,"success":false}', true);
        //var_dump($result);
        return $result;
    }

    public function fetchRemoteData($url, $data = [], $throw = true)
    {
        $data = array_merge($data, ['platId' => 12]);
        $url = config('app.coupon_url') . $url;
        $headers = [
            'Authorization' => md5(date('Y-m-d') . '915yqsATBzxd'),
            'System' => 'lpt_write',
            'Content-Type' => 'application/json',
        ];
        $client = new Client([
            //'verify' => false, // 忽略SSL错误
            'headers' => $headers,
        ]);
        $paramType = $requestParam['paramType'] ?? 'json';
        try {
            $response = $client->post($url, [$paramType => $data]);
            //$response = $client->post($url, ['body' => json_encode($data)]); 
        } catch (\Exception $e) {
            $code = $e->getCode();
            \Log::debug('guzzle-exception-' . $e->getMessage());
            if ($throw) {
                throw new BusinessException('远程连接失败');
            }

            return ['code' => 1, 'msg' => $e->getMessage()];
        }
        $body = $response->getBody(); //获取响应体，对象
        $bodyStr = (string)$body; //对象转字串
        $result = json_decode($bodyStr, true);

        if ($result['status'] != 0 && $throw) {
            throw new BusinessException($result['msg']);
        }

        unset($result['status']);
        unset($result['msg']);
        return $result;
    }
}
