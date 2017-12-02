<?php
namespace Admin\Controller;

//面对面纠纷处理
class FacetoFaceDisputeController extends AdminController{
    public function index(){
        $list   = $this->lists('FacetofaceDispute');
        foreach ($list as $k=>$v){
            $list[$k] = M('Facetoface')->find($v['facetoface_id']);
            $list[$k]['id'] = $v['id'];
            $list[$k]['status'] = $v['status'];
            $list[$k]['facetoface_id'] = $v['facetoface_id'];

            $where['uid'] = $list[$k]['uid'];
            $list[$k]['inviter_nickname'] = M('User')->where($where)->getField('nickname');

            $where['uid'] = $list[$k]['to_uid'];
            $list[$k]['invitee_nickname'] = M('User')->where($where)->getField('nickname');
        }

        $this->assign('_list', $list);

        $this->meta_title = '面对面纠纷列表';
        $this->display();
    }

    //详情
    public function info(){
        $id = I('id', 0, 'int');
        if(empty($id)){
            $this->error('请选择纠纷信息');
        }
        $info = M('FacetofaceDispute')->find($id);
        if(!$info){
            $this->error('暂无此纠纷信息');
        }

        $facetoface_id =1 ;

        $this->meta_title = '面对面纠纷详情';
        $this->display();
    }
}