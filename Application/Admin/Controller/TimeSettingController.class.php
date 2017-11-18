<?php
namespace Admin\Controller;

class TimeSettingController extends AdminController{
    //列表页
    public function index(){
        $list = M('TimeSetting')->select();
        $this->assign('list', $list);

        $this->meta_title = '时间规则设置';
        $this->display();
    }

    //编辑
    public function edit($id = null){
        if(IS_POST){
            $data['type'] = I('type', 0, 'int');
            $data['qty'] = I('qty', 0, 'int');
            if(!in_array($data['type'], array(1, -1))){
                $this->error('参数错误');
            }
            if(empty($data['qty'])){
                $this->error('请填写数值');
            }
            $set = M('TimeSetting');
            $where['id'] = $id;
            if(false !== $set->where($where)->save($data)){
                $this->success('修改成功！', U('index'));
            } else {
                $this->error('未知错误');
            }
        }

        $info = M('TimeSetting')->find($id);
        $this->assign('info', $info);
        $this->meta_title = '时间规则设置';
        $this->display();
    }
}