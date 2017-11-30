<?php
namespace Api\Controller;

class FaceToFaceController extends BaseController{
    //获取发布规则，预设地点
    public function getSetting(){
        $set = M('TimeSetting')->field('type, qty')->find(5);
        $address = M('FacetofaceAddress')->field('address')->select();
        $data['setting'] = $set;
        $data['address'] = $address;
        $this->ajaxSuccess($data);
    }

    //发送面对面邀请
    public function addFaceToFace(){
        $to_uid = I('to_uid', 0, 'int');
        $datetime = I('datetime', 0, 'int');
        $theme = I('theme');
        $address = I('address');

        if($to_uid < 1){
            $this->ajaxError('请选择要发送面对面邀请的用户');
        }
        if(NOW_TIME > $datetime){
            $this->ajaxError('请选择正确的时间');
        }
        if(strLength($theme) < 1 || strLength($theme) > 30){
            $this->ajaxError('见面主题在1 - 30个字符之间');
        }
        if(strLength($address) < 1 || strLength($address) > 30){
            $this->ajaxError('见面地点在1 - 30个字符之间');
        }
        $where['uid'] = $to_uid;
        $where['status'] = array('gt', 0);
        if(!M('User')->where($where)->count()){
            $this->ajaxError('面对面邀请的用户不存在或被禁用');
        }

        //检测账户时间余额
        $set = M('TimeSetting')->field('type, qty')->find(5);
        if($set['type'] < 0){
            $where = [];
            $where['uid'] = $this->uid;
            $times = M('User')->where($where)->getField('times');
            if($times < $set['qty']){
                $this->ajaxError('账户时间余额不足');
            }
        }

        $data = array(
            'uid' => $this->uid,
            'to_uid' => $to_uid,
            'theme' => $theme,
            'datetime' => $datetime,
            'address' => $address,
            'is_read' => 0,
            'status' => 0,
            'create_time' => NOW_TIME
        );
        $id = M('Facetoface')->add($data);
        if(!$id){
            $this->ajaxError('发送面对面邀请失败，请稍后再试');
        }

        if(!timesChange($this->uid, $set['qty'], $set['type'], '发送面对面邀请', 'add_facetoface', $id)){
            M('Facetoface')->delete($id);
            $this->ajaxError('系统繁忙，请稍后再试');
        }
        $this->ajaxSuccess('','发送面对面邀请成功');
    }

    //邀请列表   1:我发出的 2：我收到的
    public function getList($page = 1){
        $type = I('type', 0, 'int');
        if(!in_array($type, array(1, 2))){
            $this->ajaxError('参数错误');
        }

        if($type == 1){
            $where['uid'] = $this->uid;
            $field = 'to_uid as uid, theme, datetime, create_time, status';
        }
        if($type == 2){
            $where['to_uid'] = $this->uid;
            $field = 'uid, theme, datetime, create_time, status';

            $set_data['is_read'] = 1;
            M('Facetoface')->where($where)->save($set_data);
        }
        $order = 'create_time DESC';
        $data = $this->pageData($page, 'Facetoface', $where, $field, $order);

        foreach ($data as $k=>$v){
            $where = [];
            $where['uid'] = $v['uid'];
            $info = M('User')->field('headimg, nickname')->where($where)->find();
            $data[$k]['headimg'] = $info['headimg'];
            $data[$k]['nickname'] = $info['nickname'];
        }

        $this->ajaxSuccess($data);
    }

    //接受 / 拒绝邀请
    public function changeStatus(){
        $id = I('id', 0, 'int');
        $status = I('status', 0, 'int');
        if(empty($id)){
            $this->ajaxError('请先选择要操作的邀请');
        }
        if(!in_array($status, array(1, -1))){
            $this->ajaxError('参数错误');
        }

        $where['to_uid'] = $this->uid;
        $where['status'] = 0;
        if(!M('Facetoface')->where($where)->count()){
            $this->ajaxError('没有要操作的邀请');
        }

        $data['status'] = $status;
        if(!M('Facetoface')->where($where)->save($data)){
            $this->ajaxError('操作失败，请稍后再试');
        }
        $this->ajaxSuccess('', '操作成功');
    }

    //获取邀请详情
    public function getInfo(){
        $id = I('id', 0, 'int');
        if(empty($id)){
            $this->ajaxError('请先选择邀请');
        }

        $field = 'datetime, theme, address, uid, to_uid';
        $info = M('Facetoface')->field($field)->find($id);
        if(!$info){
            $this->ajaxError('没有此邀请信息');
        }

        $where['uid'] = $info['uid'];
        $info['inviter'] = M('User')->field('uid, headimg, nickname')->where($where)->find();
        $where['uid'] = $info['to_uid'];
        $info['invitee'] = M('User')->field('uid, headimg, nickname')->where($where)->find();

        unset($info['uid'], $info['to_uid']);
        $this->ajaxSuccess($info);
    }

    //面对面邀请评价
    public function comment(){
        $id = I('id', 0, 'int');
        $inviter = I('inviter', 0, 'int');
        $invitee = I('invitee', 0, 'int');
        $content = I('content');
        $goutong = I('goutong', 0, 'int');
        $zhuanye = I('zhuanye', 0, 'int');

        if(empty($id)){
            $this->ajaxError('请先选择邀请');
        }
        if(!in_array($inviter, array(0, 1)) || !in_array($invitee, array(0, 1))){
            $this->ajaxError('参数错误');
        }
        if(strLength($content) < 1 || strLength($content) > 100){
            $this->ajaxError('评价内容在1 - 100个字符之间');
        }
        if($goutong < 1 || $goutong > 100){
            $this->ajaxError('请选择沟通力');
        }
        if($zhuanye < 1 || $zhuanye > 100){
            $this->ajaxError('请选择专业力');
        }

        $where['id'] = $id;
        $where['_string'] = "uid = {$this->uid} OR to_uid = {$this->uid}";
        $info = M('Facetoface')->field('uid, to_uid, inviter, invitee')->where($where)->find();
        if(!$info){
            $this->ajaxError('此邀请不存在');
        }

        $where = [];
        $where['facetoface_id'] = $id;
        $where['uid'] = $this->uid;
        if(M('FacetofaceComment')->where($where)->count()){
            $this->ajaxError('您已经评价此邀请');
        }

        $data = array(
            'facetoface_id' => $id,
            'uid' => $this->uid,
            'inviter' => $inviter,
            'invitee' => $invitee,
            'content' => $content,
            'goutong' => $goutong,
            'zhuanye' => $zhuanye,
            'create_time' => NOW_TIME
        );
        if($info['uid'] == $this->uid){
            if($info['inviter'] == 1){
                $this->ajaxError('您已经评价此邀请');
            }
            $data['to_uid'] = $info['to_uid'];
            $set_data['inviter'] = 1;
        }
        if($info['to_uid'] == $this->uid){
            if($info['invitee'] == 1){
                $this->ajaxError('您已经评价此邀请');
            }
            $data['to_uid'] = $info['uid'];
            $set_data['invitee'] = 1;
        }

        $add_id = M('FacetofaceComment')->add($data);
        if(!$add_id){
            $this->ajaxError('评价失败，请稍后再试');
        }

        //判断对方是否评价
        $where = [];
        $where['facetoface_id'] = $id;
        $where['to_uid'] = $this->uid;
        $comment = M('FacetofaceComment')->field('inviter, invitee')->where($where)->find();
        if($comment){
            if($comment['inviter'] == $inviter && $comment['invitee'] == $invitee){
                if(!$this->changeTime($id, $this->uid, $info['uid'], $info['to_uid'], $inviter, $invitee)){
                    M('FacetofaceComment')->delete($add_id);
                    $this->ajaxError('系统繁忙，请稍后再试');
                }
            }else{
                $data = array(
                    'facetoface_id' => $id,
                    'inviter' => 0,
                    'invitee' => 0,
                    'status' => 0,
                    'create_time' => NOW_TIME
                );
                $dis_id = M('FacetofaceDispute')->add($data);
                if(!$dis_id){
                    M('FacetofaceComment')->delete($add_id);
                    $this->ajaxError('系统繁忙，请稍后再试');
                }
            }
        }

        $where = [];
        $where['id'] = $id;
        if(!M('Facetoface')->where($where)->save($set_data)){
            M('FacetofaceComment')->delete($add_id);
            if($dis_id){
                M('FacetofaceDispute')->delete($dis_id);
            }
            $this->ajaxError('系统错误，请稍后再试');
        }

        $this->ajaxSuccess('', '评价成功');
    }

    //评价一致 时间变动
    private function changeTime($id, $send_uid, $to_uid, $inviter, $invitee){
        $where['uid'] = $send_uid;
        $where['act'] = 'add_facetoface';
        $where['act_id'] = $id;
        $log = M('TimesLog')->where($where)->find();
        if ($inviter == 1) {
            if ($invitee == 1) {
                if (!timesChange($to_uid, $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $id)) {
                    return false;
                } else {
                    return true;
                }
            }
            if ($invitee == 0) {
                if (!timesChange($send_uid, $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $id)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
        if ($inviter == 0) {
            if ($invitee == 1) {
                if (!timesChange($send_uid, $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $id)) {
                    return false;
                } else {
                    return true;
                }
            }
            if ($invitee == 0) {
                if (!timesChange($to_uid, $log['amount'], 1, '面对面邀请评价', 'facetoface_comment', $id)) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }
}