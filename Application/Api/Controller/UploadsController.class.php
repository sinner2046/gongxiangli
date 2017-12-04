<?php
namespace Api\Controller;

class UploadsController extends BaseController{
    //图片上传
    public function shareImg(){
        /* 图片上传相关配置 */
        $config = array(
            'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
            'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
            'autoSub'  => true, //自动子目录保存文件
            'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => './Uploads/Share/', //保存根路径
            'savePath' => '', //保存路径
            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt'  => '', //文件保存后缀，空则使用原后缀
            'replace'  => false, //存在同名是否覆盖
        );
        $upload = new \Think\Upload($config);
        $info   =   $upload->upload();

        if(!$info) {// 上传错误提示错误信息
            $this->ajaxError($upload->getError());
        }
        foreach ($info as $k=>$v){
            $data[] = substr($config['rootPath'], 1).$v['savepath'].$v['savename'];
        }
        if(empty($data)){
            $this->ajaxError('图像上传失败，请稍后再试');
        }
        $this->ajaxSuccess($data, '图片上传成功');
    }

    //音乐上传
    public function shareMusic(){
        /* 图片上传相关配置 */
        $config = array(
            'maxSize'  => 5*1024*1024, //上传的文件大小限制 (0-不做限制)
            'exts'     => 'mp3,wav,wma', //允许上传的文件后缀
            'autoSub'  => true, //自动子目录保存文件
            'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => './Uploads/Share/', //保存根路径
            'savePath' => '', //保存路径
            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt'  => '', //文件保存后缀，空则使用原后缀
            'replace'  => false, //存在同名是否覆盖
        );
        $upload = new \Think\Upload($config);
        $info   =   $upload->upload();

        if(!$info) {// 上传错误提示错误信息
            $this->ajaxError($upload->getError());
        }
        foreach ($info as $k=>$v){
            $data[] = substr($config['rootPath'], 1).$v['savepath'].$v['savename'];
        }
        if(empty($data)){
            $this->ajaxError('音乐上传失败，请稍后再试');
        }
        $this->ajaxSuccess($data, '音乐上传成功');
    }

    //面对面评论图片上传
    public function facetofaceImg(){
        /* 图片上传相关配置 */
        $config = array(
            'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
            'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
            'autoSub'  => true, //自动子目录保存文件
            'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => './Uploads/FaceToFace/', //保存根路径
            'savePath' => '', //保存路径
            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt'  => '', //文件保存后缀，空则使用原后缀
            'replace'  => false, //存在同名是否覆盖
        );
        $upload = new \Think\Upload($config);
        $info   =   $upload->upload();

        if(!$info) {// 上传错误提示错误信息
            $this->ajaxError($upload->getError());
        }
        foreach ($info as $k=>$v){
            $data[] = substr($config['rootPath'], 1).$v['savepath'].$v['savename'];
        }
        if(empty($data)){
            $this->ajaxError('图像上传失败，请稍后再试');
        }
        $this->ajaxSuccess($data, '图片上传成功');
    }

    //删除图片
    public function del(){
        $url = I('url');
        if(empty($url)){
            $this->ajaxError('请选择要删除的图片');
        }
        if(is_array($url)){
            foreach ($url as $u){
                @unlink(substr($u, 1));
            }
        }else{
            @unlink(substr($url, 1));
        }
        $this->ajaxSuccess('', '删除成功');
    }
}