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


    //用户注册
    public function register(){
        $type = I('type');
        if(!in_array($type, array('mobile', 'email'))){
            $this->ajaxError('参数错误');
        }
        if($type == 'mobile'){
            $data['mobile'] = I('mobile');
        }
        if($type == 'email'){
            $data['email'] = I('email');
        }
        $data['password'] = I('password');


        $user = D('Admin/User');
        $uid = $user->register($data);
        if($uid){
            $this->ajaxSuccess($uid);
        }
        $this->ajaxError($user->getError());
    }
}