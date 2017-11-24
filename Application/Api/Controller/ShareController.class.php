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

        //检测账户时间余额
        $set = M('TimeSetting')->field('type, qty')->find($type);
        if($set['type'] < 0){
            $where['uid'] = $this->uid;
            $times = M('User')->where($where)->getField('times');
            if($times < $set['qty']){
                $this->ajaxError('账户时间余额不足');
            }
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
            $this->ajaxError('图像上传失败，请稍后再试');
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

        if(!timesChange($this->uid, $set['qty'], $set['type'], '发布内容', 'add_share', $id)){
            M('Share')->delete($id);
            @unlink(substr($img, 1));
            $this->ajaxError('系统繁忙，请稍后再试');
        }
        $this->ajaxSuccess('','发布成功');
    }

    //获取列表
    public function getShareList($page = 1){
        $where['uid'] = $this->uid;
        $hangye = M('User')->where($where)->getField('hangye');
        $hangye1 = M('User')->where($where)->getField('hangye1');
        if($hangye1){
            $hangye = $hangye.','.$hangye1;
        }

        $uids = getLink($this->uid);

        $where = [];
        $where['hangye'] = array('in', $hangye);
        $where['uid'] = array('in', $uids);
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $map['visible']  = array('gt',0);

        $field = 'id, uid, type, img, title, comment, liked, follow, create_time';
        $order = 'create_time DESC';
        $data = $this->pageData($page, 'Share', $map, $field, $order);

        foreach ($data as $k=>$v){
            $info = getUserInfo($v['uid']);
            $data[$k]['nickname'] = $info['nickname'];
            $data[$k]['headimg'] = $info['headimg'];
            $data[$k]['zhiye'] = $info['zhiye'];
        }

        $this->ajaxSuccess($data);
    }


}