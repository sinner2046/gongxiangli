<?php
namespace Admin\Controller;

class CateController extends AdminController{
    //行业职业管理
    public function index(){

        $tree = D('Cate')->getTree(0,'id,name,sort,pid,status');
        $this->assign('tree', $tree);

        $this->meta_title = '行业职业信息';
        $this->display();
    }

    //新增
    public function add($pid = 0){
        $cate = D('Cate');

        if(IS_POST){
            if(false !== $cate->update()){
                $this->success('新增成功！', U('index'));
            } else {
                $error = $cate->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        }else{
            $category = array();
            if($pid){
                /* 获取上级分类信息 */
                $category = $cate->info($pid, 'id,name,status');
                if(!($category && 1 == $category['status'])){
                    $this->error('指定的上级分类不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $this->assign('info',       null);
            $this->assign('category', $category);
            $this->meta_title = '新增行业职业信息';
            $this->display('edit');
        }
    }

    /* 编辑 */
    public function edit($id = null, $pid = 0){
        $cate = D('Cate');

        if(IS_POST){ //提交表单
            if(false !== $cate->update()){
                $this->success('编辑成功！', U('index'));
            } else {
                $error = $cate->getError();
                $this->error(empty($error) ? '未知错误！' : $error);
            }
        } else {
            $category = '';
            if($pid){
                /* 获取上级分类信息 */
                $category = $cate->info($pid, 'id,name,status');
                if(!($category && 1 == $category['status'])){
                    $this->error('指定的上级不存在或被禁用！');
                }
            }

            /* 获取分类信息 */
            $info = $id ? $cate->info($id) : '';

            $this->assign('info',       $info);
            $this->assign('category',   $category);
            $this->meta_title = '编辑行业职业信息';
            $this->display();
        }
    }

    //删除
    public function remove(){
        $cate_id = I('id');
        if(empty($cate_id)){
            $this->error('参数错误!');
        }

        //判断该分类下有没有子分类，有则不允许删除
        $child = M('Cate')->where(array('pid'=>$cate_id))->field('id')->select();
        if(!empty($child)){
            $this->error('请先删除该分类下的子分类');
        }

        //判断该分类下有没有商品
//        $goods_list = M('Goods')->where(array('cate_id'=>$cate_id))->field('id')->select();
//        if(!empty($goods_list)){
//            $this->error('请先删除该分类下的商品');
//        }

        //删除该分类信息
        $res = M('GoodsCate')->delete($cate_id);
        if($res !== false){
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

}