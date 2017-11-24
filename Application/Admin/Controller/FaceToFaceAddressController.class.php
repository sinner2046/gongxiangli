<?php
namespace Admin\Controller;

class FaceToFaceAddressController extends AdminController{
    //列表
    public function index(){
        $list = M('FacetofaceAddress')->order('id DESC')->select();
        $this->assign('list', $list);
        $this->meta_title = '面对面地址管理';
        $this->display();
    }

    //新增/编辑
    public function edit($id = null){
        if(IS_POST){
            $address = I('address');
            if(empty($address)){
                $this->error('请填写地址');
            }

            $data['address'] = $address;
            if(!$id){
                $res = M('FacetofaceAddress')->add($data);
            }else{
                $where = [];
                $where['id'] = $id;
                $res  = M('FacetofaceAddress')->where($where)->save($data);
            }
            if(!$res){
                $this->error('操作失败，请稍后再试');
            }
            $this->success('操作成功！', U('index'));
        }

        if(!empty($id)){
            $info = M('FacetofaceAddress')->find($id);
            $this->assign('info', $info);
            $this->meta_title = '编辑面对面地址';
        }else{
            $this->meta_title = '新增面对面地址';
        }

        $this->display();
    }
}