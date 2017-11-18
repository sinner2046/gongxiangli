<?php
namespace Admin\Controller;

class ImgController extends AdminController{
    //列表
    public function index(){
        $list = M('Img')->order('id DESC')->select();
        $this->assign('list', $list);
        $this->meta_title = '目标图库管理';
        $this->display();
    }

    //新增/编辑
    public function edit($id = null){
        if(IS_POST){
            $picture_id = I('picture_id', 0, 'int');
            $name = I('name');
            if(empty($picture_id)){
                $this->error('请先上传图片');
            }
            if(empty($name)){
                $this->error('请填写图片名称');
            }
            $where['id'] = $picture_id;
            if(!M('Picture')->where($where)->count()){
                $this->error('无效图片');
            }
            $data['picture_id'] = $picture_id;
            $data['name'] = $name;
            if(!$id){
                $res = M('Img')->add($data);
            }else{
                $where = [];
                $where['id'] = $id;
                $res  = M('Img')->where($where)->save($data);
            }
            if(!$res){
                $this->error('上传失败，请稍后再试');
            }
            $this->success('上传成功！', U('index'));
        }

        if(!empty($id)){
            $info = M('Img')->find($id);
            $this->assign('info', $info);
        }
        $this->meta_title = '新增图库图片';
        $this->display();
    }
}