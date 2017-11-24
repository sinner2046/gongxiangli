<?php
namespace Api\Controller;

class IndexController extends BaseController{

    private function userInfo($uid){
        //个人资料
        $where['uid'] = $uid;
        $field = 'headimg, sex, nickname, zhiye, signature, prov, city, birthday, tag, tag1';
        $data = M('User')->field($field)->where($where)->find();

        $data['zhiye'] = getTagName($data['zhiye']);
        $tags = $data['tag'];
        if($data['tag1']){
            $tags = $tags .','. $data['tag1'];
        }
        $data['tags'] = getTagName($tags);
        unset($data['tag'], $data['tag1']);

        //统计
        $share = M('share');
        $where['uid'] = $uid;
        $where['type'] = 1;
        $data['zuopin'] = $share->where($where)->count();
        $where['type'] = 2;
        $data['linggan'] = $share->where($where)->count();
        $where['type'] = 3;
        $data['mubiao'] = $share->where($where)->count();
        $where['type'] = 4;
        $data['chengjiu'] = $share->where($where)->count();

        $data['facetoface'] = M('Facetoface')->where("uid={$uid} OR to_uid={$uid}")->count();

        $facetoface_comment = M('FacetofaceComment');
        $where = [];
        $where['to_uid'] = $uid;
        $data['comment'] = $facetoface_comment->where($where)->count();
        $data['goutong'] = $facetoface_comment->where($where)->avg('goutong');
        $data['zhuanye'] = $facetoface_comment->where($where)->avg('zhuanye');

        return $data;
    }

    //首页左侧信息
    public function leftInfo(){
        //是否有未读通知
        $where['uid'] = $this->uid;
        $where['is_read'] = 0;
        $notify= M('Notify')->field('id')->where($where)->find();
        if($notify){
            $data['notify'] = 1;
        }else{
            $data['notify'] = 0;
        }

        //是否有未读私信
        $where = [];
        $where['to_uid'] = $this->uid;
        $where['is_read'] = 0;
        $message= M('Message')->field('id')->where($where)->find();
        if($message){
            $data['message'] = 1;
        }else{
            $data['message'] = 0;
        }

        //是否有未读面对面邀请
        $where = [];
        $where['to_uid'] = $this->uid;
        $where['is_read'] = 0;
        $message= M('Facetoface')->field('id')->where($where)->find();
        if($message){
            $data['facetoface'] = 1;
        }else{
            $data['facetoface'] = 0;
        }

        $info = $this->userInfo($this->uid);
        $data = array_merge($data, $info);

        $this->ajaxSuccess($data);
    }

    //首页头部信息
    public function topInfo(){
        $data = M('User')->field('times')->find($this->uid);
        $data['times'] = $this->secToTime($data['times']);

        $share_follow = M('ShareFollow');
        $where['f.uid'] = $this->uid;
        $where['s.type'] = 1;
        $join = "gxl_share s ON f.share_id=s.id";
        $data['zuopin'] =  $share_follow->alias('f')->join($join)->where($where)->count();

        $where['s.type'] = 2;
        $data['linggan'] =  $share_follow->alias('f')->join($join)->where($where)->count();

        $where['s.type'] = 3;
        $data['mubiao'] =  $share_follow->alias('f')->join($join)->where($where)->count();

        $where['s.type'] = 4;
        $data['chengjiu'] =  $share_follow->alias('f')->join($join)->where($where)->count();

        $where = [];
        $where['uid'] = $this->uid;
        $data['link'] = M('Friend')->where($where)->count();

        $where['star'] = 1;
        $data['star'] = M('Friend')->field('follow_uid')->where($where)->order('create_time DESC')->select();

        foreach ($data['star'] as $k=>$v){
            $info = getUserInfo($v['follow_uid']);
            $data['star'][$k] = array_merge($v, $info);
        }

        $this->ajaxSuccess($data);
    }

    //他人左侧信息
    public function otherLeftInfo(){
        $other_uid = I('other_uid', 0, 'int');

        if ($other_uid < 1) {
            $this->ajaxError('请先选择用户');
        }
        $where['uid'] = $other_uid;
        $where['status'] = array('gt', 0);
        if (!M('User')->where($where)->count()) {
            $this->ajaxError('此用户不存在或被禁用');
        }

        $data = $this->userInfo($other_uid);

        $visible = M('User')->field('visible')->find($other_uid);
        if($visible['visible'] == 0){
            $data['signature'] = '保密';
            $data['prov'] = '保';
            $data['city'] = '密';
            $data['birthday'] = substr($data['birthday'], 2, 1).'0 后';
        }

        $this->ajaxSuccess($data);
    }

    //他人首页头部信息
    public function otherTopInfo(){
        $other_uid = I('other_uid', 0, 'int');

        if ($other_uid < 1) {
            $this->ajaxError('请先选择用户');
        }
        $where['uid'] = $other_uid;
        $where['status'] = array('gt', 0);
        if (!M('User')->where($where)->count()) {
            $this->ajaxError('此用户不存在或被禁用');
        }

        $this->uid = $other_uid;
        $this->topInfo();
    }

     private function secToTime($times){
        if ($times>0) {
            $hour = floor($times/3600);
            $minute = floor(($times-3600 * $hour)/60);
            $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
            $result = array(
                'hour' => $hour,
                'minute' => $minute,
                'sec' => $second
            );
        }
        return $result;
    }
}