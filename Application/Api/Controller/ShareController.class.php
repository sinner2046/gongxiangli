<?php
namespace Api\Controller;

class ShareController extends BaseController{
    //获取规则
    public function getShareSetting(){
        $where['id'] = array('lt', 5);
        $data = M('TimeSetting')->where($where)->select();
        if(!$data){
            $this->ajaxError('暂无数据');
        }
        $this->ajaxSuccess($data);
    }

    //目标图库
    public function getImg(){
        $field = 'i.picture_id, i.name, p.path';
        $join = 'gxl_picture as p on i.id=p.id';
        $data = M('Img')->alias('i')->field($field)->join($join)->select();

        $this->ajaxSuccess($data);
    }

    //发布
    public function addShare(){
        $type = I('type', 0, 'int');
        $title = I('title');
        $content = I('content');
        $hangye = I('hangye', 0, 'int');
        $visible = I('visible', 1, 'int');

        if(!in_array($type, array(1, 2, 3, 4))){
            $this->ajaxError('参数错误');
        }
        if(strLength($title) < 1 || strLength($title) > 20){
            $this->ajaxError('标题在1-20 个字符之间');
        }
        if(strLength($content) < 1 || strLength($content) > 200){
            $this->ajaxError('正文内容在1-200 个字符之间');
        }
        if(empty($hangye)){
            $this->ajaxError('请选择所属行业');
        }
        if(!checkTag($hangye)){
            $this->ajaxError('此行业不存在');
        }
        if(!in_array($visible, array(0, 1))){
            $this->ajaxError('请选择可见性');
        }

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
        if($info['file']['savename']) {
            $img = substr($config['rootPath'], 1) . $info['file']['savepath'] . $info['file']['savename'];
        }else{
            $this->ajaxError('图像像上传失败，请稍后再试');
        }

        if($type == 1){

        }
        if($type == 2){

        }
        if($type == 3){

        }
        if($type == 4){

        }

        $data = array(
            'uid' => $this->uid,
            'type' => $type,
            'img' => $img,
            'title' => $title,
            'content' => $content,
            'hangye' => $hangye,
            'visible' => $visible,
            'create_time' => NOW_TIME
        );
        $id = M('Share')->add($data);

        if(!$id){
            $this->ajaxError('发布失败，请稍后再试');
        }

        $set = M('TimeSetting')->find($type);

        if(!timesChange($this->uid, $set['qty'], $set['type'], '发布内容', 'add_share', $id)){
            M('Share')->delete($id);
            @unlink(substr($img, 1));
            $this->ajaxError('系统繁忙，请稍后再试');
        }
        $this->ajaxSuccess('','发布成功');
    }

    //检测share_id是否存在
    private $share_id;
    private function checkShareId(){
        $this->share_id = I('share_id', 0, 'int');
        if($this->share_id < 1){
            $this->ajaxError('请选择正确的内容');
        }
        $where['id'] = $this->share_id;
        $where['visible'] = array('gt', 0);
        if(!M('Share')->where($where)->count()){
            $this->ajaxError('此内容不存在或被隐藏');
        }
    }


    //删除/设为隐私，公开 内容
    public function setShare(){
        $visible = I('visible');
        $this->share_id = I('share_id', 0, 'int');

        if(!in_array($visible, array('0', '1', '-1'))){
            $this->ajaxError('参数错误');
        }
        if($this->share_id < 1){
            $this->ajaxError('请选择正确的内容');
        }

        $where['id'] = $this->share_id;
        $where['uid'] = $this->uid;
        $where['visible'] = array('gt', -1);
        $share = M('Share')->where($where)->find();
        if(!$share){
            $this->ajaxError('此内容不存在或被删除');
        }

        $data['visible'] = $visible;
        unset($where['visible']);
        $set_id = M('Share')->where($where)->save($data);
        if(!$set_id){
            $this->ajaxError('操作失败，请稍后再试');
        }

        if($visible == '-1'){
            $where = [];
            $where['uid'] = $this->uid;
            $where['act'] = 'add_share';
            $where['act_id'] = $this->share_id;
            $log = M('TimesLog')->where($where)->find();
            if($log['type'] == 1){
                $type = -1;
            }else{
                $type = 1;
            }
            if(!timesChange($this->uid, $log['amount'], $type, '删除内容', 'del_share', $this->share_id)){
                $data['visible'] = $share['visible'];
                $where = [];
                $where['id'] = $this->share_id;
                $where['uid'] = $this->uid;
                M('Share')->where($where)->save($data);
                $this->ajaxError('系统错误，请稍后再试');
            }
        }
        $this->ajaxSuccess('', '操作成功');
    }
}