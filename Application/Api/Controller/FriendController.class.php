<?php
namespace Api\Controller;

class FriendController extends BaseController{
    //添加链接
    public function addLink(){
        $to_uid = I('to_uid', 0, 'int');
        $info = I('info');

        if($to_uid < 1){
            $this->ajaxError('请选择被添加用户');
        }
        $where['uid'] = $to_uid;
        $where['status'] = array('gt', 0);
        if(!M('User')->where($where)->count()){
            $this->ajaxError('被添加用户不存在或被禁用');
        }
        if(strLength($info) < 1 || strLength($info) > 30){
            $this->ajaxError('申请信息在1 - 30个字符之间');
        }

        $data['from_uid'] = $this->uid;
        $data['to_uid'] = $to_uid;
        $data['info'] = $info;
        $data['status'] = 0;
        $data['create_time'] = NOW_TIME;
        $id = M('Friend')->add($data);

        if(!$id){
            $this->ajaxError('添加失败，请稍后再试');
        }
        $this->ajaxSuccess('', '添加成功');
    }
}