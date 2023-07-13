<?php
namespace App\dakaprogram\Lib\Model;

use Eduline\Model;
use Library\Redis\Redis;

class DiscoveryModel extends Model
{
    /**
     * 获取发现页基本信息：广告
     */
    public function getBaseInfos($uid)
    {
        $score = $uid ? M('credit_user')->where(['uid'=>$uid])->getField('score') : 0;
		$score = intval($score);
        $data = [
            'score' => $score,
            'banners' => $this->getPointBanners(74),
            'huatiInfos' => $this->getHuatiInfos(),
        ];
        return $data;
    }

    /**
     * 获取广告信息
     */
    public function getPointBanners($pid)
    {
        $banners = M("dk_advertising")->where(['pid' => $pid, 'is_use' => 1, 'status' => 0])->field("id,pic,jump_url,jump_type")->order(" sort asc ")->select();
        foreach ($banners as & $banner) {
            $banner['image'] = getImageUrlByAttachId($banner['pic']);
        }
        return $banners;
    }

    /**
     * 获取热门话题信息
     */
    public function getHuatiInfos()
    {
        $infos = M("dk_huati")->where(['status' => 1])->field("id, title,description")->order("sort asc")->limit(10)->select();
        return $infos;
    }
}
