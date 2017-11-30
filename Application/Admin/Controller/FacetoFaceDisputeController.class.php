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


        $this->meta_title = '面对面纠纷详情';
        $this->display();
    }
}