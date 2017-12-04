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

            $where['uid'] = $v['uid'];
            $list[$k]['inviter_nickname'] = M('User')->where($where)->getField('nickname');

            $where['uid'] = $v['to_uid'];
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

        $facetoface_info = M('Facetoface')->field('uid, to_uid')->find($info['facetoface_id']);

        $where['facetoface_id'] = $info['facetoface_id'];
        $data = M('FacetofaceComment')->where($where)->select();

        foreach ($data as $k=>$v){
            if($facetoface_info['uid'] = $v['uid']){
                $data[$k]['role'] = '邀请人';
            }
            if($facetoface_info['to_uid'] = $v['uid']){
                $data[$k]['role'] = '被邀请人';
            }
            $where = [];
            $where['uid'] = $v['uid'];
            $data[$k]['nickname'] = M('User')->where($where)->getField('nickname');
        }
        $this->assign('_list', $data);
        $this->assign('info', $info);

        $this->meta_title = '面对面纠纷详情';
        $this->display();
    }

    //处理纠纷
    public function handle(){
        $id = I('id', 0, 'int');
        $inviter = I('inviter');
        $invitee = I('invitee');
        if(empty($id)){
            $this->error('请选择纠纷信息');
        }
        if(!in_array($inviter, array(0, 1)) || !in_array($invitee, array(0, 1))){
            $this->error('请选择正确的处理结果');
        }
        $info = M('FacetofaceDispute')->find($id);
        if(!$info){
            $this->error('暂无此纠纷信息');
        }
        if($info['status'] == 1){
            $this->error('已处理此纠纷');
        }

        $data = array(
            'inviter' => $inviter,
            'invitee' => $invitee,
            'status' => 1,
            'handle_time' => NOW_TIME
        );
        $where['id'] = $id;
        $res = M('FacetofaceDispute')->where($where)->save($data);
        if(!$res){
            $this->error('处理失败，请稍后再试');
        }

        $facetoface_info = M('Facetoface')->field('uid, to_uid')->find($info['facetoface_id']);

        $where = [];
        $where['act'] = 'add_facetoface';
        $where['act_id'] = $info['facetoface_id'];
        $log = M('TimesLog')->where($where)->find();

        if(($inviter == 0 && $invitee == 0) || ($inviter == 1 && $invitee == 0)){
            if ($this->timesChange($facetoface_info['uid'], $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $info['facetoface_id'])) {
                $change = true;
            }
        }
        if(($inviter == 1 && $invitee == 1) || ($inviter == 0 && $invitee == 1)){
            if ($this->timesChange($facetoface_info['to_uid'], $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $info['facetoface_id'])) {
                $change = true;
            }
        }

        if(!$change){
            $data = array(
                'inviter' => 0,
                'invitee' => 0,
                'status' => 0,
                'handle_time' => 0
            );
            $where['id'] = $id;
            M('FacetofaceDispute')->where($where)->save($data);
            $this->error('系统错误，请稍后再试');
        }

        $this->success('处理成功', U('index'));
    }

    //账户时间变动
    private function timesChange($uid, $amount, $type, $info, $act, $act_id){
        $where['uid'] = $uid;
        $times = M('User')->where($where)->getField('times');
        if($type > 0){
            $user['times'] = $times + $amount;
        }else{
            $user['times'] = $times - $amount;
        }

        $times_change = M('User')->where($where)->save($user);
        if(!$times_change){
            return false;
        }

        $log = array(
            'uid' => $uid,
            'type' => $type,
            'info' => $info,
            'amount' => $amount,
            'after' => $user['times'],
            'act' => $act,
            'act_id' => $act_id,
            'create_time' => NOW_TIME
        );
        $log_id = M('TimesLog')->add($log);

        if(!$log_id){
            $user['times'] = $times;
            M('User')->where($where)->save($user);
            return false;
        }
        return true;
    }
}