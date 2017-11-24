<?php
namespace Api\Controller;

class NotifyController extends BaseController{
    //获取列表
    public function getList($page = 1){
        $type = I('type', 0, 'int');
        if(!in_array($type, array(1, 2))){
            $this->ajaxError('参数错误');
        }

        $where['uid'] = $this->uid;
        $where['type'] = $type;
        $field = 'id, from_uid, info, create_time';
        $order = 'create_time DESC';
        $data = $this->pageData($page, 'Notify', $where, $field, $order);

        $set_data['is_read'] = 1;
        M('Notify')->where($where)->save($set_data);

        foreach ($data as $k=>$v){
            $where = [];
            $where['uid'] = $v['from_uid'];
            $info = M('User')->field('headimg, nickname')->where($where)->find();
            $data[$k]['headimg'] = $info['headimg'];
            $data[$k]['nickname'] = $info['nickname'];
        }

        $this->ajaxSuccess($data);
    }

    //删除通知
    public function delNotify(){
        $ids = I('ids');
        if(empty($ids)){
            $this->ajaxError('请选择要删除的通知');
        }
        $ids = explode(',', $ids);

        foreach ($ids as $id){
            $where['id'] = $id;
            $where['uid'] = $this->uid;
            if(!M('Notify')->where($where)->count()){
                $this->ajaxError('此通知不存在');
            }
            if(!M('Notify')->delete($id)){
                $this->ajaxError('删除失败，请稍后再试');
            }
        }

        $this->ajaxSuccess('','删除成功');
    }
}