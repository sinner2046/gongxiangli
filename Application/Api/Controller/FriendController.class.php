<?php
namespace Api\Controller;

class FriendController extends BaseController{
    //添加链接
    public function addLink(){
        $follow_uid = I('follow_uid', 0, 'int');

        if($follow_uid < 1){
            $this->ajaxError('请选择被链接的用户');
        }
        $where['uid'] = $follow_uid;
        $where['status'] = array('gt', 0);
        if(!M('User')->where($where)->count()){
            $this->ajaxError('被链接用户不存在或被禁用');
        }

        $where = [];
        $where['uid'] = $this->uid;
        $where['follow_uid'] = $follow_uid;
        if(M('Friend')->where($where)->count()){
            $this->ajaxError('已经链接此用户');
        }

        $where = [];
        $where['uid'] = $follow_uid;
        $where['follow_uid'] = $this->uid;
        $link = M('Friend')->where($where)->count();
        if($link){
            $data['status'] = 1;
            $change_id = M('Friend')->where($where)->save($data);
        }else{
            $data['status'] = 0;
        }

        $data['uid'] = $this->uid;
        $data['follow_uid'] = $follow_uid;
        $data['star'] = 0;
        $data['create_time'] = NOW_TIME;
        $id = M('Friend')->add($data);

        if(!$id){
            if($change_id){
                $data['status'] = 0;
                M('Friend')->where($where)->save($data);
            }
            $this->ajaxError('链接失败，请稍后再试');
        }

        $notify = M('notify');
        $notify->uid = $follow_uid;
        $notify->from_uid = $this->uid;
        $notify->type = 2;
        $notify->info = '添加你为链接';
        $notify->is_read = 0;
        $notify->act_id = $id;
        $notify->create_time = NOW_TIME;
        $notify->add();

        $this->ajaxSuccess('', '链接成功');
    }

    //解除链接
    public function delLink(){
        $follow_uid = I('follow_uid', 0, 'int');

        if($follow_uid < 1){
            $this->ajaxError('请选择被解除链接的用户');
        }
        $where['uid'] = $this->uid;
        $where['follow_uid'] = $follow_uid;
        $link = M('Friend')->field('id, status')->where($where)->find();
        if(!$link){
            $this->ajaxError('没有链接记录');
        }

        if($link['status'] == 1){
            $where = [];
            $where['uid'] = $follow_uid;
            $where['follow_uid'] = $this->uid;
            $data['status'] = 0;
            $change_id = M('Friend')->where($where)->save($data);
        }

        if(!M('Friend')->delete($link['id'])){
            if($change_id){
                $data['status'] = 1;
                M('Friend')->where($where)->save($data);
            }
            $this->ajaxError('解除链接失败');
        }

        $this->ajaxSuccess('', '解除链接成功');
    }

    //我的链接
    public function myLink(){
        $field = 'u.hangye, GROUP_CONCAT(u.uid)';
        $where['f.uid'] = $this->uid;
        $join = "gxl_user u ON f.follow_uid=u.uid";
        $order = 'f.star DESC, f.status DESC, f.create_time DESC';
        $data = M('Friend')->alias('f')->field($field)->where($where)->join($join)->group('u.hangye')->order($order)->select();

        foreach ($data as $k=>$v){
            $data[$k]['hangye'] = getTagName($v['hangye']);
            $uids = explode(',', $v['group_concat(u.uid)']);
            unset($data[$k]['group_concat(u.uid)']);
            foreach ($uids as $u){
                $info = getUserInfo($u);

                $where = [];
                $where['uid'] = $this->uid;
                $where['follow_uid'] = $u;
                $res = M('Friend')->field('star,status')->where($where)->find();

                $data[$k]['link'][] = array_merge(array('uid'=>$u), $info, $res);
            }
        }
        $this->ajaxSuccess($data);
    }

    //设置 星标
    public function addStar(){
        $follow_uid = I('follow_uid', 0, 'int');

        if($follow_uid < 1){
            $this->ajaxError('请选择设置星标链接的用户');
        }
        $where['uid'] = $this->uid;
        $where['follow_uid'] = $follow_uid;
        $info = M('Friend')->where($where)->find();
        if(!$info){
            $this->ajaxError('请先链接此用户');
        }
        if($info['star'] == 1){
            $this->ajaxError('已经设置此链接为星标');
        }

        $map['uid'] = $this->uid;
        $map['star'] = 1;
        $count = M('Friend')->where($map)->count();
        if($count >= 4){
            $this->ajaxError('最多设置 4 个星标链接');
        }

        $data['star'] = 1;
        $res = M('Friend')->where($where)->save($data);
        if(!$res){
            $this->ajaxError('设置星标链接失败');
        }
        $this->ajaxSuccess('', '设置星标链接成功');
    }

    //解除星标
    public function delStar(){
        $follow_uid = I('follow_uid', 0, 'int');

        if($follow_uid < 1){
            $this->ajaxError('请选择解除星标链接的用户');
        }
        $where['uid'] = $this->uid;
        $where['follow_uid'] = $follow_uid;
        $info = M('Friend')->where($where)->find();
        if(!$info){
            $this->ajaxError('请先链接此用户');
        }
        if($info['star'] == 0){
            $this->ajaxError('尚未设置此链接为星标');
        }

        $data['star'] = 0;
        $res = M('Friend')->where($where)->save($data);
        if(!$res){
            $this->ajaxError('解除星标链接失败');
        }
        $this->ajaxSuccess('', '解除星标链接成功');
    }
}