<?php
namespace Api\Controller;
use Think\Controller;

class PublicController extends Controller{
    private $data;

    private function ajaxSuccess($data, $msg='成功'){
        $this->data['status'] = 1;
        $this->data['data'] = $data;
        $this->data['msg'] = $msg;
        $this->ajaxReturn($this->data);
    }

    private function ajaxError($msg = '失败'){
        $this->data['status'] = 0;
        $this->data['data'] = '';
        $this->data['msg'] = $msg;
        $this->ajaxReturn($this->data);
    }


/*    发送验证码
    mode：1 手机；2 邮箱
    type：1 注册；2 找回密码
*/
    public function sendCode(){
        $mode = I('mode');
        $type = I('type');
        $account = I('account');
        if(!in_array($mode, array('1', '2'))){
            $this->ajaxError('参数错误');
        }
        if(!in_array($type, array('1', '2'))){
            $this->ajaxError('参数错误');
        }
        if($type == '1'){
            $text = '注册用户';
        }
        if($type == '2'){
            $text = '找回密码';
        }
        $code = mt_rand(100000, 999999);
        if($mode == '1'){
            if(!checkMobile($account)){
                $this->ajaxError('手机号码格式不正确');
            }
            $where['mobile'] = $account;
            $info = M('User')->where($where)->count();
            if($type == '1'){
                if($info){
                    $this->ajaxError('此用户已经注册');
                }
            }
            if($type == '2'){
                if(!$info){
                    $this->ajaxError('此用户尚未注册');
                }
            }
            $res = sendSms($account, $code);
        }
        if($mode == '2'){
            if(!filter_var($account, FILTER_VALIDATE_EMAIL)) {
                $this->ajaxError('邮箱格式不正确');
            }
            $where['email'] = $account;
            $info = M('User')->where($where)->count();
            if($type == '1'){
                if($info){
                    $this->ajaxError('此用户已经注册');
                }
            }
            if($type == '2'){
                if(!$info){
                    $this->ajaxError('此用户尚未注册');
                }
            }
            $res = sendEmail($account, $text, $code);
        }

        if(!$res){
            $this->ajaxError('验证码发送失败，请稍后再试');
        }
        $data['account'] = $account;
        $data['mode'] = $mode;
        $data['type'] = $type;
        $data['code'] = $code;
        $data['create_time'] = NOW_TIME;
        $id = M('Code')->add($data);

        if(!$id){
            $this->ajaxError('系统错误，请稍后再试');
        }
        $this->ajaxSuccess('' ,'验证码发送成功');
    }

    //用户注册
    public function register(){
        $account = I('account');
        $mode = I('mode');
        $code = I('code', 0, 'int');
        if(!in_array($mode, array('1', '2'))){
            $this->ajaxError('参数错误');
        }
        if(empty($code)){
            $this->ajaxError('验证码不能为空');
        }
        if($code < 100000 || $code > 999999){
            $this->ajaxError('验证码格式不正确');
        }

        $where['account'] = $account;
        $where['mode'] = $mode;
        $where['type'] = 1;
        $code_info = M('Code')->where($where)->order('create_time DESC')->find();
        if(!$code_info){
            $this->ajaxError('请先发送验证码');
        }
        if($code != $code_info['code']){
            $this->ajaxError('验证码错误');
        }

        if($mode == '1'){
            $data['mobile'] = $account;
        }
        if($mode == '2'){

            $data['email'] = $account;
        }
        $data['password'] = I('password');

        $user = D('Admin/User');
        $uid = $user->register($data);
        if($uid){
            $this->ajaxSuccess($uid, '注册成功');
        }
        $this->ajaxError($user->getError());
    }


    //职业信息列表
    public function getCate(){
        $map  = array('status' => array('gt', 0));
        $list = M('Cate')->field('id,name,pid')->where($map)->order('sort, create_time')->select();

        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'child', 0);

        $this->ajaxSuccess($list);
    }

    //登陆
    public function login(){
        $account = I('account');
        $password = I('password');
        if(empty($account)){
            $this->ajaxError('手机号/邮箱 不能为空');
        }
        if(empty($password)){
            $this->ajaxError('密码不能为空');
        }

        $where['status'] = array('gt', 0);

        if(checkMobile($account)){
            $where['mobile'] = $account;
        }elseif(filter_var($account, FILTER_VALIDATE_EMAIL)){
            $where['eamil'] = $account;
        }else{
            $this->ajaxError('手机号/邮箱 验证失败');
        }

        $info = M("User")->field('uid, nickname, headimg, login_count')->where($where)->find();
        if(!is_array($info)){
            $this->ajaxError('用户不存在或被禁用');
        }
        if(!D('Admin/User')->verifyPassword($info['id'], $password)){
            $this->ajaxError('用户密码错误');
        }

        $where = [];
        $where['uid'] = $info['uid'];
        $data['login_time'] = NOW_TIME;
        $data['login_count'] = $info['login_count'] + 1;
        M('User')->where($where)->save($data);

        unset($info['login_count']);
        $this->ajaxSuccess($info,'登陆成功');
    }



}