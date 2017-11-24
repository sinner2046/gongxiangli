<?php
namespace Api\Controller;
use Think\Controller;

class BaseController extends Controller{

    protected $data;
    protected $uid;

    protected function _initialize(){
        $this->uid = I('uid', 0, 'int');
        if ($this->uid < 1) {
            $this->ajaxError('请先登录');
        }
        $where['uid'] = $this->uid;
        $where['status'] = array('gt', 0);
        if (!M('User')->where($where)->count()) {
            $this->ajaxError('此用户不存在或被禁用');
        }
    }

    protected function ajaxSuccess($data, $msg='成功'){
        $this->data['status'] = 1;
        $this->data['data'] = $data;
        $this->data['msg'] = $msg;
        $this->ajaxReturn($this->data);
    }

    protected function ajaxError($msg = '失败'){
        $this->data['status'] = 0;
        $this->data['data'] = '';
        $this->data['msg'] = $msg;
        $this->ajaxReturn($this->data);
    }

    protected function pageData($page, $model, $where = [], $field = true, $order = ''){
        $page_size = C('PAGE_SIZE');
        $page = abs(intval($page));
        if ($page < 1) $page = 1;

        $count = M($model)->where($where)->count();
        $total_page = ceil($count / $page_size);
        if($page > $total_page){
            $this->ajaxError('没有更多数据了');
        }
        $first_size = ($page - 1) * $page_size;

        $this->data['total_page'] = $total_page;
        return M($model)->field($field)->where($where)->limit($first_size, $page_size )->order($order)->select();
    }

    public function _empty(){
        $this->ajaxError('非法操作');
    }
}