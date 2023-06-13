<?php
namespace App\dakaprogram\Lib\Action;

/**
 * 打卡小程序排行榜
 * @author   @lee
 * @version shufadaka1.0
 */
class HuatiAlbumAction extends ApiTokenAction
{
    public function sharelist()
    {
        $mid              = intval($_REQUEST['mid']);
        $huati_id         = intval($_REQUEST['huati_id']);
        $type             = intval($_REQUEST['type']);
        $huati_id         = 88;
        $mid              = 111836;
        $data['huati_id'] = $huati_id;
        $data['ptype']    = 1;
    }
    public function searchlist()
    {
        $mid      = intval($_REQUEST['mid']);
        $huati_id = intval($_REQUEST['huati_id']);
        $huati_category = intval($_REQUEST['huati_category']);
        $keyword  = $_REQUEST['keyword'];
        $type     = $_REQUEST['type'];
        // $huati_id         = 88;
        // $mid              = 111836;
        $data['a.huati_id'] = $huati_id;
        $data['a.ptype']    = 1;
        $data['a.is_del']   = 0;
        if ($type == 'lianziting') {
            $data['a.ptype'] = 2;
        }
        if (isset($huati_category)) {
            $data['a.huati_category'] = intval($huati_category);
        }
        $data['b.album_title'] = array('like', "%" . $keyword . "%");
        $data['b.is_publish']  = 1;
        $rs                    = M('dk_huati_album a')->field('a.id,a.album_id,b.album_title,b.stype,b.is_publish')->join('el_dk_album as b on b.id=a.album_id')->where($data)->order('a.sort_id asc,a.id desc')->select();
        $album_id              = $this->historyalbumId($mid, $huati_id);
        foreach ($rs as $key => &$value) {

            $rs[$key]['last_read']   = 0;
            $rs[$key]['bofang_num']  = $this->bofangNum($value['album_id']);
            $rs[$key]['section_num'] = $this->sectionNum($value['album_id']);
            $rs[$key]['last_read']   = ($value['album_id'] == $album_id) ? 1 : 0;

        }
        $kf_pic           = M('dk_huati')->where(['id' => $huati_id])->getField('kf_pic');
        $kf_pic           = getImageUrlByAttachId($kf_pic);
        $result['data']   = $rs;
        $result['info']   = $kf_pic;
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function datalist()
    {
        $mid      = intval($_REQUEST['mid']);
        $huati_id = intval($_REQUEST['huati_id']);
        $withCategory = intval($_REQUEST['with_category']);
        $huatiCategory     = $_REQUEST['huati_category'];
        //var_dump($huatiCategory);
        $type     = $_REQUEST['type'];
        // $huati_id         = 88;
        // $mid              = 111836;
        $data['huati_id'] = $huati_id;
        $data['ptype']    = 1;
        $data['is_del']   = 0;
        if ($type == 'lianziting') {
            $data['ptype'] = 2;
        }

        $huatiCategories = M('dk_huati_category')->where("huati_id = {$huati_id} AND `album_num` > 0")->order('sort_id asc')->select();
        $categories = [];
        foreach ($huatiCategories as $hCategory) {
            if (is_null($huatiCategory)) {
                $huatiCategory = $hCategory['id'];
            }
            $categories[] = ['id' => $hCategory['id'], 'title' => $hCategory['title']];
        }
        $noCategoryCount = M('dk_huati_album')->where("huati_id = {$huati_id} AND `huati_category` = 0")->count();
        if ($noCategoryCount && count($categories) > 0) {
            $categories[] = ['id' => 0, 'title' => '其他'];
        }

        if (isset($huatiCategory) && $withCategory) {
            $data['huati_category'] = intval($huatiCategory);
        }
        $rs = M('dk_huati_album')->where($data)->order('sort_id asc,id asc')->select();

        //$lastRead = $this->lastRead($huati_id, $mid);
        foreach ($rs as $key => &$value) {

            $rs[$key]['album_title'] = $this->albumField($value['album_id'], 'album_title');
            $rs[$key]['stype']       = $this->albumField($value['album_id'], 'stype');
            $rs[$key]['last_read']   = $this->isLastRead($mid, $huati_id, $value['album_id'], $value['ptype']);
            $rs[$key]['bofang_num']  = $this->bofangNum($value['album_id']);
            $rs[$key]['section_num'] = $this->sectionNum($value['album_id']);
            $rs[$key]['sort_use']    = $this->sortUse($mid, $huati_id, $value['album_id'], $value['sort_id']);
            $rs[$key]['is_publish']  = $this->albumField($value['album_id'], 'is_publish');
            if ($rs[$key]['is_publish'] == 0) {
                unset($rs[$key]);
            }

        }
        $rs = array_values($rs);
        // array_multisort(array_column($rs, 'sort_use'), SORT_DESC, $rs);
        $album_id = intval($rs[0]['album_id']);

        $result['data']   = $withCategory ? ['categories' => $categories, 'infos' => $rs] : $rs;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function turnToW($f = 10000)
    {
        if ($f < 10000) {
            return $f;
        }
        if ($f >= 10000) {
            return floor($f / 10000) . "万";
        }
    }
    public function isLastRead($mid = 0, $huati_id = 0, $album_id = 0, $ptype = 1)
    {

        $data['bb.huati_id'] = $huati_id;
        $data['bb.uid']      = $mid;
        $data['aa.is_del']   = 0;
        $data['aa.ptype']    = $ptype;
        //$data['aa.album_id'] = 'bb.album_id';
        $id = M('dk_album_history as bb')->join('el_dk_huati_album as aa on aa.huati_id=bb.huati_id and aa.album_id=bb.album_id')->where($data)->order('bb.updated_at desc')->limit(1)->getField('bb.album_id');
        //echo M()->getlastsql();
        return $id == $album_id ? 1 : 0;

    }
    public function historyalbumId($mid = 0, $huati_id = 0)
    {

        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        $id               = M('dk_album_history')->where($data)->order('id desc')->limit(1)->getField('album_id');
        return $album_id;
    }
    public function historyHave($mid = 0, $huati_id = 0, $album_id = 0)
    {

        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        $data['album_id'] = $album_id;
        $id               = M('dk_album_history')->where($data)->order('id desc')->limit(1)->getField('id');
        return $id ? 1 : 0;
    }
    public function sortUse($mid = 0, $huati_id = 0, $album_id = 0, $sort_id = 0)
    {
        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        $data['album_id'] = $album_id;
        $updated_at       = strtotime(M('dk_album_history')->where($data)->order('updated_at desc')->limit(1)->getField('updated_at'));
        $sort_index       = 1000 - $sort_id;
        return $updated_at > 0 ? $updated_at : $sort_index;
    }
    public function lastRead($huati_id = 0, $mid = 0)
    {
        $data['huati_id'] = $huati_id;
        $data['uid']      = $mid;
        return M('dk_album_history')->where($data)->order('updated_at desc')->limit(1)->getField('album_id');
    }
    public function sectionNum($album_id = 0)
    {
        $data['album_id']   = $album_id;
        $data['is_del']     = 0;
        $data['is_chapter'] = 0;
        return M('dk_album_data')->where($data)->count();
    }
    public function bofangNum($id)
    {
        $r = $this->albumField($id, 'real_view') + $this->albumField($id, 'unreal_view') * 10000;
        return $this->turnToW($r);
    }
    public function isAi($huati_id = 0, $album_id = 0)
    {
        $datawhere['album_id'] = $album_id;
        $datawhere['huati_id'] = $huati_id;
        $isAi                  = M('dk_huati_album')->where($datawhere)->getField('is_ai');
        return $isAi ? 1 : 0;
    }
    public function historyAlbumDataId($uid = 0, $huati_id = 0, $album_id = 0)
    {
        $datawhere['album_id'] = $album_id;
        $datawhere['uid']      = $uid;
        $datawhere['huati_id'] = $huati_id;
        $history               = M('dk_album_history')->where($datawhere)->order('updated_at desc')->getField('album_data_id');
        return $history;
    }
    public function contentlist()
    {
        $id                 = intval($_REQUEST['album_id']);
        $mid                = intval($_REQUEST['mid']);
        $huati_id           = intval($_REQUEST['huati_id']);
        $data['album_id']   = $id;
        $data['pid']        = 0;
        $data['is_del']     = 0;
        $data['is_chapter'] = 1;
        $rs                 = M('dk_album_data')->field('id,title,sort_id')->where($data)->order('sort_id asc,id asc')->select();
        foreach ($rs as $key => $value) {
            $where['pid']         = $value['id'];
            $where['is_del']      = 0;
            $where['is_chapter']  = 0;
            $rs[$key]['datalist'] = M('dk_album_data')->field('id,title,sort_id,created_at,tcvideo_id')->where($where)->order('sort_id asc,id asc')->select();
            foreach ($rs[$key]['datalist'] as $k => $v) {
                $rs[$key]['datalist'][$k]['created_day']  = date('Y-m-d', strtotime($v['created_at']));
                $rs[$key]['datalist'][$k]['title']        = $v['title'];
                $rs[$key]['datalist'][$k]['tcvideo_data'] = M('n_zy_tcvideo')->field('fileid,hd_size,img_url,original_url,file_type')->where(['id' => $v['tcvideo_id']])->find();
                if ($rs[$key]['datalist'][$k]['tcvideo_data']['file_type'] == 'video') {
                    $rs[$key]['datalist'][$k]['tcvideo_data']['original_url'] = '';
                }
                $rs[$key]['datalist'][$k]['is_read'] = $this->isRead($mid, $huati_id, $id, $v['id']);
            }
        }

        $res['contentlist'] = $rs;
        $wild['pid']        = 0;
        $wild['is_del']     = 0;
        $wild['is_chapter'] = 0;
        $wild['album_id']   = $id;
        $res['wildlist']    = M('dk_album_data')->field('id,title,sort_id,created_at,tcvideo_id')->where($wild)->order('sort_id asc,id asc')->select();
        foreach ($res['wildlist'] as $kk => $vv) {
            $res['wildlist'][$kk]['created_day'] = date('Y-m-d', strtotime($vv['created_at']));
            //$res['wildlist'][$kk]['title']        = $vv['title'];
            $res['wildlist'][$kk]['tcvideo_data'] = M('n_zy_tcvideo')->field('fileid,hd_size,img_url,original_url,file_type')->where(['id' => $vv['tcvideo_id']])->find();
            if ($res['wildlist'][$kk]['tcvideo_data']['file_type'] == 'video') {
                $res['wildlist'][$kk]['tcvideo_data']['original_url'] = '';
            }
            $res['wildlist'][$kk]['is_read'] = $this->isRead($mid, $huati_id, $id, $vv['id']);
        }
        $result['data']   = $res;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function album()
    {
        $album_id           = intval($_REQUEST['album_id']);
        $huati_id           = intval($_REQUEST['huati_id']);
        $mid                = intval($_REQUEST['mid']);
        $data['album_id']   = $album_id;
        $data['is_del']     = 0;
        $data['is_chapter'] = 0;
        $rs                 = M('dk_album_data')->where($data)->field('id,tcvideo_id,album_id,title')->order('sort_id asc,id asc')->select();
       // echo M()->getLastSql();
        $dk_album = M('dk_album')->where(['id' => $album_id])->find();
        foreach ($rs as $key => &$value) {
            $rs[$key]['title']        = $value['title'];
            $page                     = $key + 1;
            $rs[$key]['page']         = "第" . $page . "页";
            $rs[$key]['tcvideo_data'] = M('n_zy_tcvideo')->field('fileid,hd_size,img_url,original_url,file_type')->where(['id' => $value['tcvideo_id']])->find();
            if ($rs[$key]['tcvideo_data']['file_type'] == 'video') {
                $rs[$key]['tcvideo_data']['original_url'] = '';
            }
            $rs[$key]['is_read'] = $this->isRead($mid, $huati_id, $album_id, $value['id']);
        }
        $history = $this->historyAlbumDataId($mid, $huati_id, $album_id);

        $last_album_data_id    = $history > 0 ? $history : $rs[0]['id'];
        $data['data']          = $rs;
        $data['album_data_id'] = $last_album_data_id;
        $data['album_title']   = $dk_album['album_title'];
        $data['stype']         = $dk_album['stype'];
        $data['is_ai']         = $this->isAi($huati_id, $album_id);
        $data['huati_id']      = $huati_id;

        $data['if_follow']        = M('dk_huati_user')->where(['huati_id' => $huati_id, 'uid' => $mid])->count() > 0 ? 1 : 0;
        $data['if_daka']          = M('homework_submit')->where(['dk_type' => $huati_id, 'uid' => $mid])->count() > 0 ? 1 : 0;
        $data['relate_circle_id'] = $relate_circle_id = M('dk_huati')->where(['id' => $huati_id])->getField('relate_circle_id');
        $data['join_circle']      = D('Circles')->isjoin($relate_circle_id, $mid);
        $data['huati_title']      = M('dk_huati')->where(['id' => $huati_id])->getField('title');
        $data['kf_pic_url']       = getImageUrlByAttachId(M('dk_huati')->where(['id' => $huati_id])->getField('kf_pic'));
        $data['x_pic']            = M('dk_cock')->where(['code' => 'album_x'])->getField('data') ?: "";
        $data['x_url']            = M('dk_cock')->where(['code' => 'album_x'])->getField('ext_data') ?: "";
        $this->addRealView($album_id);
        $data['if_daka_count'] = M('homework_submit')->where(['uid' => $mid])->count() > 0 ? 1 : 0;
        $result['data']        = $data;
        $result['info']        = "查询成功";
        $result['status']      = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
    public function addRealView($album_id = 0)
    {
        $album_id = intval($album_id);
        if ($album_id > 0) {
            $sql = "UPDATE `el_dk_album` SET `real_view`=real_view +1 where `id`=" . $album_id;
            M()->execute($sql);
        }

    }
    public function isRead($mid = 0, $huati_id = 0, $album_id = 0, $album_data_id = 0)
    {
        $data['huati_id']      = $huati_id;
        $data['uid']           = $mid;
        $data['album_id']      = $album_id;
        $data['album_data_id'] = $album_data_id;
        return M('dk_album_history')->where($data)->getField('id') ? 1 : 0;
    }

    public function clickAlbum()
    {
        $album_id             = $map['album_id']             = $data['album_id']             = intval($_REQUEST['album_id']);
        $map['album_data_id'] = $data['album_data_id'] = intval($_REQUEST['album_data_id']);
        $map['uid']           = $data['uid']           = intval($_REQUEST['mid']);
        $map['huati_id']      = $data['huati_id']      = intval($_REQUEST['huati_id']);
        $data['second']       = intval($_REQUEST['second']);
        $rs                   = M('dk_album_history')->where($map)->order('id desc')->find();

        if (empty($map['album_id'] * $map['album_data_id'] * $map['uid'] * $map['huati_id'])) {
            $result['data']   = null;
            $result['info']   = "参数失败";
            $result['status'] = 0;
            $this->ajaxreturn($result['data'], $result['info'], $result['status']);
        }
        if (empty($rs)) {
            M('dk_album_history')->add($data);
        } else {
            $data['id']         = $rs['id'];
            $data['updated_at'] = date('Y-m-d H:i:s');
            M('dk_album_history')->save($data);
        }
        M('dk_album')->where($map)->order('id desc')->find();
        //$this->addRealView($album_id);
        $result['data']   = null;
        $result['info']   = "点击成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function yinyue()
    {
        $album_id = intval($_REQUEST['album_id']);
        $huati_id = intval($_REQUEST['huati_id']);
        $mid      = intval($_REQUEST['mid']);
        // $huati_id         = 88;
        // $mid              = 111836;
        // $album_id         = 14;
        $data['album_id']   = $album_id;
        $data['is_del']     = 0;
        $data['is_chapter'] = 0;

        $rs       = M('dk_album_data')->where($data)->field('id,tcvideo_id,album_id,title')->order('sort_id asc,id asc')->select();
        $dk_album = M('dk_album')->where(['id' => $album_id])->find();
        foreach ($rs as $key => &$value) {
            $rs[$key]['title']        = $value['title'];
            $rs[$key]['tcvideo_data'] = M('n_zy_tcvideo')->field('fileid,hd_size,img_url,original_url,file_type,duration')->where(['id' => $value['tcvideo_id']])->find();
            if ($rs[$key]['tcvideo_data']['file_type'] == 'video') {
                $rs[$key]['tcvideo_data']['original_url'] = '';
            }
            $rs[$key]['is_read']      = $this->isRead($mid, $huati_id, $album_id, $value['id']);
            $rs[$key]['original_url'] = $rs[$key]['tcvideo_data']['original_url'];
            if (empty($rs[$key]['tcvideo_data']['duration'])) {
                $rs[$key]['tcvideo_data']['duration'] = '';
            }
        }
        $history                  = $this->historyAlbumDataId($mid, $huati_id, $album_id);
        $last_album_data_id       = $history > 0 ? $history : $rs[0]['id'];
        $data['data']             = $rs;
        $data['album_data_id']    = $last_album_data_id;
        $data['album_title']      = $dk_album['album_title'];
        $data['stype']            = $dk_album['stype'];
        $data['is_ai']            = $dk_album['is_ai'];
        $data['huati_id']         = $huati_id;
        $data['relate_circle_id'] = $relate_circle_id = M('dk_huati')->where(['id' => $huati_id])->getField('relate_circle_id');
        $data['huati_title']      = M('dk_huati')->where(['id' => $huati_id])->getField('title');
        $data['kf_pic_url']       = getImageUrlByAttachId(M('dk_huati')->where(['id' => $huati_id])->getField('kf_pic'));
        $data['bofang_num']       = $this->bofangNum($album_id);
        $data['section_num']      = $this->sectionNum($album_id);
        $this->addRealView($album_id);
        $result['data']   = $data;
        $result['info']   = "查询成功";
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }

    public function albumField($id = 0, $str = 'id')
    {
        return M('dk_album')->where(['id' => $id])->getField($str);
    }

    /**
     * 按分类显示话题资料
     */
    public function listinfo()
    {
        $mid      = intval($_REQUEST['mid']);
        $huatiId = intval($_REQUEST['huati_id']);
        $keyword  = $_REQUEST['keyword'];
        $type     = $_REQUEST['type'];
        $huatiCategory     = $_REQUEST['huati_category'];
        $huatiInfo = M('dk_huati')->where("id = {$huatiId} AND `album_num` > 0")->find();
        $huatiCategories = M('dk_huati_category')->where(['huati_id' => $huatiId])->order('sort_id asc')->select();
        $categories = [];
        foreach ($huatiCategories as $hCategory) {
            $categories[$hCategory['id']] = $hCategory['title'];
        }

        $data['a.huati_id'] = $huatiId;
        $data['a.ptype']    = 1;
        $data['a.is_del']   = 0;
        if (!empty($huatiCategory)) {
            $data['a.huati_category'] = $huatiCategory;
        }
        if ($type == 'lianziting') {
            $data['a.ptype'] = 2;
        }
        $data['b.album_title'] = array('like', "%" . $keyword . "%");
        $data['b.is_publish']  = 1;
        $rs                    = M('dk_huati_album a')->field('a.id,a.huati_category,a.album_id,b.album_title,b.stype,b.is_publish')->join('el_dk_album as b on b.id=a.album_id')->where($data)->order('a.sort_id asc,a.id desc')->select();
        $album_id              = $this->historyalbumId($mid, $huatiId);
        foreach ($rs as $key => &$value) {

            $rs[$key]['last_read']   = 0;
            $rs[$key]['bofang_num']  = $this->bofangNum($value['album_id']);
            $rs[$key]['section_num'] = $this->sectionNum($value['album_id']);
            $rs[$key]['last_read']   = ($value['album_id'] == $album_id) ? 1 : 0;

        }
        $kf_pic           = getImageUrlByAttachId($huaitiInfo['kf_pic']);
        //print_r($rs);exit();
        $result['data'] = [
            'kf_pic' => $kf_pic,
            'categories' => $categories,
            'infos' => $rs,
        ];
        $result['info']   = 'ok';
        $result['status'] = 1;
        $this->ajaxreturn($result['data'], $result['info'], $result['status']);
    }
}
