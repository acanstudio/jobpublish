<?php
namespace App\dakaprogram\Lib\Model;

use Eduline\Model;
use Library\Redis\Redis;

class BossdictModel extends Model
{
    /**
     * 获取字典示例信息
     */
    public function getDemoData()
    {
        $info = M('dk_cock')->where(['code' => 'dict-demo'])->find();
        if (empty($info)) {
            $picture = 'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/courseware/pc/2023/06/15/x1axeugctul.png';
            //$wordData = $this->getWordDatas('六');
        } else {
            //list($word, $picture) = explode('|', $info['data']);
            $picture = $info['data'];
            //$wordData = $this->getWordDatas($word);
        }
        $data = [
            'demoPicture' => $picture,
            //'wordData' => $wordData['wordDatas'],
        ];
        return $data;
    }
    /**
     * 登记没有资源的单字
     */
    public function recordLackResource($uid, $keyword)
    {
        $result = $this->getWordDatas($keyword, $uid);
        $words = isset($result['wordDatas']) ? $result['wordDatas'] : [];
        foreach ($words as $info) {
            if (empty($info['noResource'])) {
                continue;
            }
            $data = [
                'uid' => intval($uid),
                'word' => strval($info['name']),
            ];
            $data['created_at'] = date('Y-m-d H:i:s');
            M('mini_dict_lack')->add($data);
        }
        return true;
    }

    /**
     * 记录用户学习的字库信息
     */
    public function createPartRecord($uid, $wordId, $type, $calligraphy)
    {
        if (empty($uid) || empty($wordId) || empty($type) || empty($calligraphy)) {
            return false;
        }
        $data = [
            'uid' => $uid,
            'word_id' => $wordId,
            'word_type' => $type,
            'calligraphy' => $calligraphy,
        ];
        $exist = M('mini_dict_record')->where($data)->find();
        if ($exist) {
            $uData = [
                'id' => $exist['id'],
                'view_num' => $exist['view_num'] + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            M('mini_dict_record')->save($uData);
            return true;
        }
        $data['view_num'] = 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        M('mini_dict_record')->add($data);
        return true;
    }

    public function getWordDatas($keyword, $uid = 0)
    {
        $url = '/proxy/word-dict';
        $remoteDatas = $this->_asyncRequest($url, ['keyword' => $keyword]);

        $typeDatas = [];
        foreach ($this->getTypeDatas() as $tCode => $tName) {
            $typeDatas[] = [
                'code' => $tCode,
                'title' => $tName,
                'isCurrent' => $tCode == 'dict' ? 1 : 0,
            ];
        }
        $banners = [];
        foreach ($this->getCalligraphyDatas() as $calligraphy => $cName) {
            $banners[$calligraphy] = $this->getBannerInfo('dict', $calligraphy);
        }
        foreach ($remoteDatas as & $rData) {
            if (!isset($rData['resourceDatas']) || empty($rData['resourceDatas'])) {
                continue;
            }
            foreach ($rData['resourceDatas'] as & $resource) {
                $resource['banner'] = $banners[$resource['calligraphy']];
            }
            //$rData['resourceDatas'] = $rData['resourceDatas'];
        }
        
        return ['typeDatas' => $typeDatas, 'wordDatas' => $remoteDatas];
    }

    /**
     * 获取当前笔画或部首控笔的详情，格式化对应的列表信息
     */
    public function getPartDetail($infos, $type, $wordId, $calligraphy, $uid)
    {
        $sourceCalligraphies = $this->getCalligraphyDatas();
        $calligraphy = empty($calligraphy) ? $this->getPointCalligraphy($uid, $type, $infos['listNums'], $sourceCalligraphies) : $calligraphy;
        //$calligraphy = empty($calligraphy) ? ($lastRecord ? $lastRecord['calligraphy'] : 'bossdict') : $calligraphy;

        if (empty($wordId)) {
            $lastRecord = $this->getLastRecord($uid, ['word_type' => $type, 'calligraphy' =>$calligraphy]);
            $wordId = $lastRecord ? $lastRecord['word_id'] : 0;
        }
        $lists = $infos['lists'][$calligraphy];
        $listNums = $infos['listNums'];
        $detail = [];
        foreach ($lists as & $info) {
            if ($info['id'] == $wordId) {
                $info['isCurrent'] = 1;
                $detail = $info;
            }
        }
        if (empty($detail) && !empty($lists)) {
            $detail = isset($lists[0]) ? $lists[0] : [];
            $lists[0]['isCurrent'] = 1;
        }

        $this->createPartRecord($uid, $wordId, $type, $calligraphy);
        $calligraphyDatas = [];
        foreach ($sourceCalligraphies as $cCode => $cName) {
            $calligraphyDatas[] = [
                'code' => $cCode,
                'title' => $cName,
                'isCurrent' => $cCode == $calligraphy ? 1 : 0,
                'noResource' => !isset($listNums[$cCode]) || empty($listNums[$cCode]) ? 1 : 0,
            ];
        }
        $typeDatas = [];
        foreach ($this->getTypeDatas() as $tCode => $tName) {
            $typeDatas[] = [
                'code' => $tCode,
                'title' => $tName,
                'isCurrent' => $tCode == $type ? 1 : 0,
            ];
        }

        return [
            'listNum' => $listNums,
            'wordTypes' => $typeDatas,
            'calligraphies' => $calligraphyDatas,
            'bannerInfo' => $this->getBannerInfo($type, $calligraphy),
            'detail' => $detail, 
            'lists' => $lists
        ];
    }

    protected function getBannerInfo($type, $calligraphy)
    {
        $code = "dict-{$type}-{$calligraphy}";
        $info = M('dk_cock')->where(['code' => $code])->find();
        if (empty($info)) {
            return [];
        }
        if ($type != 'dict') {
            list($picture, $path, $courseId, $title) = explode('|', $info['data']);
            return [
                'picture' => strval($picture),
                'path' => strval($path),
                'courseId' => intval($courseId),
                'title' => strval($title),
            ];
        }
        $courseId = intval(trim($info['data']));
        if (empty($courseId)) {
            return [];
        }
        $course = M('mini_course')->where(['id' => $courseId])->find();
        if (empty($course)) {
            return [];
        }
        $viewNum = $course['real_click'] + $rs['market_click'] * 10000;
        $viewNum = $viewNum < 10000 ? $viewNum : round($viewNum / 10000, 2);
        return [
            'courseId' => $courseId,
            'viewNum' => $viewNum
        ];
    }

    public function getTypeDatas()
    {
        return [
            'dict' => '查字词',
            'stroke' => '笔画示范',
            'component' => '偏旁示范',
            'penControl' => '控笔示范',
        ];
    }

    public function getCalligraphyDatas()
    {
        return [
            'bossdict' => '楷书',
            'bossRunning' => '行楷',
            'bossCursive' => '行书',
        ];
    }

    public function getLastRecord($uid, $where = [])
    {
        $where['uid'] = $uid;
        $lastRecord = M('mini_dict_record')->where($where)->order('updated_at desc')->find();
        return $lastRecord;
    }

    public function getPointCalligraphy($uid, $type, $listNums, $sourceCalligraphies)
    {
        $cCalligraphy = '';
        $firstCalligraphy = false;
        foreach ($sourceCalligraphies as $calligraphy => $cName) {
            $num = isset($listNums[$calligraphy]) ? $listNums[$calligraphy] : 0;
            $firstCalligraphy = empty($firstCalligraphy) ? $calligraphy : $firstCalligraphy;
            if ($num > 0) {
                $cCalligraphy = $calligraphy;
                break;
            }
        }
        $calligraphy = empty($cCalligraphy) ? $firstCalligraphy : $cCalligraphy;
        if (empty($uid)) {
            return $calligraphy;
        }

        $lastRecord = $this->getLastRecord($uid, ['word_type' => $type]);
        if (empty($lastRecord)) {
            return $calligraphy;
        }
        $cCalligraphy = $lastRecord['calligraphy'];
        $calligraphy = $listNums[$cCalligraphy] > 0 ? $cCalligraphy : $calligraphy;
        return $calligraphy;
    }

    public function getLastType($uid)
    {
        if (empty($uid)) {
            return 'stroke';
        }
        $lastRecord = $this->getLastRecord($uid);
        if ($lastRecord) {
            return $lastRecord['word_type'];
        }
        return 'stroke';
    }

    /**
     * 获取笔画、部首、控笔的列表信息
     *
     * @params string $type 类型[笔画、部首、控笔]
     * @params int $uid 用户UID
     * @return mixed
     */
    public function getPartDictInfos($type, $force = false)
    {
        $redis = Redis::getInstance();
        $redisKey = 'liupinshuyuan_miniprogram_bossdict_part_infos_' . $type;
        $cacheDatas = $redis->get($redisKey);
        if (empty($force) && $cacheDatas) {
            return json_decode($cacheDatas, true);
        }
        $data = [
            'wordType' => $type,
        ];
        $url = '/proxy/part-infos';
        $result = $this->_asyncRequest($url, $data);

        $lists = [];
        $listNums = [];
        foreach ($result as $info) {
            foreach ($this->getCalligraphyDatas() as $sCalligraphy => $sName) {
                if (!isset($info['resourceDatas'][$sCalligraphy]) || empty($info['resourceDatas'][$sCalligraphy]['picture'])) {
                    continue;
                }
                $listNums[$sCalligraphy] += 1;
                $data = [
                    'name' => $info['name'],
                    'id' => $info['id'],
                    'wordType' => $info['wordType'],
                    'picture' => $info['resourceDatas'][$sCalligraphy]['picture']['originalUrl'],
                    'fileid' => $info['resourceDatas'][$sCalligraphy]['video']['fileid'],
                    'isCurrent' => 0,
                ];
                $lists[$sCalligraphy][] = $data;
            }
        }
        $partDatas = ['listNums' => $listNums, 'lists' => $lists];
        $redis->set($redisKey, json_encode($partDatas));
        return $partDatas;
    }

    public function _asyncRequest($url, $postData, $debug = true)
    {
        $postData['platId'] = 12;
        $urlBase = C('resource_url');
        $url = rtrim($urlBase, '/') . $url;

        //$postData = json_encode($postData);
        //echo $url;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            //'Authorization: ' . md5(date('Y-m-d') . '915yqsATBzxd'),
            'System: lpt_write',
            //'Content-Type: application/json; charset=utf-8',
            //'Content-Length: ' . strlen($postData)
        ]);
         
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        if (empty($result) || !isset($result['code']) || $result['status'] == 200) {
            return false;
        }
        return $result['data'];
    }
}
