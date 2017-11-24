<?php
namespace Api\Controller;

class MessageController extends BaseController{
    //发送私信
    public function sendMessage(){
        $to_uid = I('to_uid', 0, 'int');
        $info = I('info');

        if($to_uid < 1){
            $this->ajaxError('请选择要发送私信的用户');
        }
        if(strLength($info) < 1 || strLength($info) > 100){
            $this->ajaxError('私信内容在1 - 100 个字符之间');
        }
        $where['uid'] = $to_uid;
        $where['status'] = array('gt', 0);
        if(!M('User')->where($where)->count()){
            $this->ajaxError('私信用户不存在或被禁用');
        }

        $data['uid'] = $this->uid;
        $data['to_uid'] = $to_uid;
        $data['info'] = $info;
        $data['is_read'] = 0;
        $data['create_time'] = NOW_TIME;
        $id = M('Message')->add($data);

        if(!$id){
            $this->ajaxError('发送私信失败，请稍后再试');
        }
        $this->ajaxSuccess('', '发送私信成功');
    }

    //私信列表
    public function getList($page = 1){
        $where['to_uid'] = $this->uid;
        $field = 'to_uid, info, create_time';
        $order = 'create_time DESC';
        $data = $this->pageData($page, 'Message', $where, $field, $order);

        $set_data['is_read'] = 1;
        M('Message')->where($where)->save($set_data);

        foreach ($data as $k=>$v){
            $where = [];
            $where['uid'] = $v['to_uid'];
            $info = M('User')->field('headimg, nickname')->where($where)->find();
            $data[$k]['headimg'] = $info['headimg'];
            $data[$k]['nickname'] = $info['nickname'];
        }

        $this->ajaxSuccess($data);
    }

    //删除私信
    public function delMessage(){
        $id = I('id', 0, 'int');
        if(empty($id)){
            $this->ajaxError('请选择要删除的私信');
        }

        $where['id'] = $id;
        $where['to_uid'] = $this->uid;
        if(!M('Message')->where($where)->count()){
            $this->ajaxError('要删除的私信不存在');
        }

        if(!M('Message')->delete($id)){
            $this->ajaxError('删除失败，请稍后再试');
        }
        $this->ajaxSuccess('', '删除成功');
    }
}