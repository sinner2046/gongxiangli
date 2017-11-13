<?php
namespace Admin\Controller;

class UserController extends AdminController{

    //会员首页信息
    public function index(){
        $nickname       =   I('nickname');
        $map['status']  =   array('egt',0);
        if(is_numeric($nickname)){
            $map['uid|nickname']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);
        }else{
            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');
        }

        $list   = $this->lists('User', $map);
        int_to_string($list);
        $this->assign('_list', $list);
        $this->meta_title = '会员信息';
        $this->display();
    }

    //会员状态修改
    public function changeStatus($method=null){
        $id = array_unique((array)I('id',0));
        $id = is_array($id) ? implode(',',$id) : $id;

        if ( empty($id) ) {
            $this->error('请选择要操作的数据!');
        }
        $map['uid'] =   array('in',$id);
        switch ( strtolower($method) ){
            case 'forbiduser':
                $this->forbid('User', $map );
                break;
            case 'resumeuser':
                $this->resume('User', $map );
                break;
            case 'deleteuser':
                $this->delete('User', $map );
                break;
            default:
                $this->error('参数非法');
        }
    }
}