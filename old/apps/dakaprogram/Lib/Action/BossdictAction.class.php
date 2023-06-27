<?php
namespace App\dakaprogram\Lib\Action;

use Library\Redis\Redis;
use App\dakaprogram\Lib\Model\BossdictModel;

/**
 * BOSS字典功能模块
 *
 * @version 3.1.0
 */
class BossdictAction
{
    /**
     * 查字词
     *
     */
    public function wordDict()
    {
        $auth = $this->checkAuth(false);
        $model = $this->getBossdictModel();
        $keyword = $_REQUEST['keyword'];
        if (empty($keyword)) {
            echo json_encode(['status' => 0, 'info' => '参数有误', 'data' => '']);exit();
        }
        $user = $this->getUserInfo();
        $datas = $model->getWordDatas($keyword, $user['uid']);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => $datas,
        ];
        echo json_encode($result);exit;
    }

    /**
     * 控笔、笔画、部首
     *
     */
    public function partDict()
    {
        $auth = $this->checkAuth(false);
        $model = $this->getBossdictModel();
        $user = $this->getUserInfo();
        $uid = $user ? $user['uid'] : 0;

        $type = strval($_REQUEST['type']);
        if (empty($type)) {
            $type = $model->getLastType($uid);
        }
        $infos = $model->getPartDictInfos($type);
        //print_r($infos);

        $calligraphy = strval($_REQUEST['calligraphy']);
        $wordId = $_REQUEST['word_id'];
        $data = $model->getPartDetail($infos, $type, $wordId, $calligraphy, $uid);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => $data,
        ];
        echo json_encode($result);exit;
    }

    /**
     * 记录缺失的资源
     *
     */
    public function resourceLack()
    {
        $auth = $this->checkAuth();
        $model = $this->getBossdictModel();
        $user = $this->getUserInfo();
        $words = strval($_REQUEST['words']);
        if (empty($words)) {
            echo json_encode(['status' => 0, 'info' => '参数有误', 'data' => '']);exit();
        }

        //$model->recordLackResource($user, $words);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => null
        ];
        echo json_encode($result);exit;
    }

    /**
     * 记录上次浏览的控笔、笔画或偏旁
     *
     */
    public function partRecord()
    {
        $auth = $this->checkAuth();
        $model = $this->getBossdictModel();
        $user = $this->getUserInfo();
        $wordId = intval($_REQUEST['word_id']);
        $type = strval($_REQUEST['type']);
        $calligraphy = strval($_REQUEST['calligraphy']);
        if (empty($user) || empty($wordId) || empty($type) || empty($calligraphy)) {
            echo json_encode(['status' => 0, 'info' => '参数有误', 'data' => '']);exit();
        }

        //$model->createPartRecord($user, $wordId, $type, $calligraphy);
        $result = [
            'status' => 1,
            'info' => 'success',
            'data' => null
        ];
        echo json_encode($result);exit;
    }

    public function checkAuth($throw = true)
    {
    	$token = $_REQUEST['token'];
    	$validToken = md5(date('Y-n-j').'liupinsy');
    	if ($token != $validToken) {
            if (!$throw) {
                return false;
            }
            $result = [
                'info' => '有新内容,请重新进入1',
                'status' => 0,
                'data' => null,
            ];
    	    echo json_encode($result);exit;
    	}
        return true;
    }

    public function getUserInfo()
    {
        $uid = intval($_REQUEST['mid']);
        $user = M('user')->where(['uid' => $uid])->find();
        return $user;
    }

    protected function getBossdictModel()
    {
        return new BossdictModel();
    }
}
