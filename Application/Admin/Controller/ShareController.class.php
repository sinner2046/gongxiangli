<?php
namespace Admin\Controller;

class ShareController extends AdminController{
    public function index(){
        $list   = $this->lists('Share');
        foreach ($list as $k=>$v){
            $info = M('User')->field('nickname, headimg')->find($v['uid']);
            $list[$k] = array_merge($v, $info);
        }
        $this->assign('_list', $list);

        $this->meta_title = '内容信息';
        $this->display();
    }

    //删除
    public function del(){
        $id = I('id', 0, 'int');
        if(empty($id)){
            $this->error('请选择要删除的内容');
        }

        $where['id'] = $id;
        $data['visible'] = -1;
        $res = M('Share')->where($where)->save($data);

        if(!$res){
            $this->error('删除失败');
        }
        $this->success('删除成功');
    }
}