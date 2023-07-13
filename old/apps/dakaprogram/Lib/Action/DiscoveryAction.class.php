<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;
use App\dakaprogram\Lib\Model\DiscoveryModel;

/**
 * 发现页相关功能
 *
 * @version 3.1.5
 */
class DiscoveryAction
{
    /**
     * 返回发现页基本信息
     *
     */
    public function baseInfos()
    {
        $action = new BossdictAction();
        $auth = $action->checkAuth(false);
        $uid = 0;
        if ($auth) {
            $user = $action->getUserInfo();
            $uid = $user ? $user['uid'] : 0;
        }

        $model = $this->getDiscoveryModel();
        $data = $model->getBaseInfos($uid);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => $data,
        ];
        echo json_encode($result);exit;
    }

    protected function getDiscoveryModel()
    {
        return new DiscoveryModel();
    }
}
