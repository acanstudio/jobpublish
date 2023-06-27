<?php
namespace App\dakaprogram\Lib\Model;

use Eduline\Model;
use Library\Redis\Redis;

class BossdictModel extends Model
{
    public function getWordDatas($keyword, $uid)
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
        
        return ['typeDatas' => $typeDatas, 'wordDatas' => $remoteDatas];
    }

    public function getPartDetail($infos, $type, $wordId, $calligraphy, $uid)
    {
        $lastRecord = M('mini_dict_record')->where(['uid' => $uid])->order('updated_at desc')->find();
        $calligraphy = 'bossRunning';//empty($calligraphy) ? ($lastRecord ? $lastRecord['calligraphy'] : 'bossdict') : $calligraphy;

        $lists = $detail = [];
        foreach ($infos as $info) {
            if (!isset($info['resourceDatas'][$calligraphy]) || empty($info['resourceDatas'][$calligraphy]['picture'])) {
                continue;
            }
            $data = [
                'name' => $info['name'],
                'id' => $info['id'],
                'wordType' => $info['wordType'],
                'picture' => $info['resourceDatas'][$calligraphy]['picture']['originalUrl'],
                'fileid' => $info['resourceDatas'][$calligraphy]['video']['fileid'],
                'isCurrent' => 0,
            ];
            $lists[] = $data;
            if ($data['id'] == $wordId) {
                $data['isCurrent'] = 1;
                $detail = $data;
            }
        }
        if (empty($detail)) {
            $detail = isset($lists[0]) ? $lists[0] : [];
            $lists[0]['isCurrent'] = 1;
        }
        $calligraphyDatas = [];
        foreach ($this->getCalligraphyDatas() as $cCode => $cName) {
            $calligraphyDatas[] = [
                'code' => $cCode,
                'title' => $cName,
                'isCurrent' => $cCode == $calligraphy ? 1 : 0,
                'noResource' => rand(0, 1),
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
            'wordTypes' => $typeDatas,//$this->getTypeDatas(), 
            'calligraphies' => $calligraphyDatas,//$this->getCalligraphyDatas(), 
            //'currentType' => $type, 
            'bannerInfo' => $this->getBannerInfo($type, $calligraphy),
            //'currentCalligrphy' => $calligraphy, 
            'detail' => $detail, 
            'lists' => $lists
        ];
    }

    protected function getBannerInfo($type, $calligraphy)
    {
        $testBanners = [
            'https://1254153797.vod2.myqcloud.com/41f91735vodsh1254153797/09b395473270835009276355575/8GNP3uf9ba8A.png',
            'https://xsjy-1254153797.cos.ap-shanghai.myqcloud.com/edu/asset/pc/2023/05/29/test4.jpg"'
        ];
        $paths = ['pages/myHome/myHome', 'pages/circle/circle'];
        return [
            'picture' => $testBanners[rand(0, 1)],
            'path' => $paths[rand(0, 1)],
            'courseId' => [8, 28, 27][rand(0, 2)],
            'title' => "{$type}-{$calligraphy}-广告位",
        ];
    }

    public function getTypeDatas()
    {
        return [
            'dict' => '查字词',
            'stroke' => '笔画示范',
            'component' => '部首示范',
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
        $redis->set($redisKey, json_encode($result));
        return $result;
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
