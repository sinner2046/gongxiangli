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
        $img = I('img');
        $content = I('content');
        $hangye = I('hangye', 0, 'int');
        $visible = I('visible', 1, 'int');

        if(!in_array($type, array(1, 2, 3, 4))){
            $this->ajaxError('参数错误');
        }
        if(strLength($title) < 1 || strLength($title) > 20){
            $this->ajaxError('标题在1-20 个字符之间');
        }
        if(empty($img)){
            $this->ajaxError('请先上传图片');
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
        if($type == 3 || $type == 4){
            $finish_date = I('finish_date');
            if(!checkDateFormat($finish_date)){
                $this->ajaxError('请选择正确的完成日期');
            }
        }
        if($type == 1){
            $file_type = I('file_type');
            $url = I('url');
            if(!in_array($file_type, array('image', 'audio', 'video'))){
                $this->ajaxError('请选择作品类型');
            }
            if(empty($url)){
                $this->ajaxError('请先上传作品内容');
            }
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
        if($type == 3){
            $data['finish_date'] = $finish_date;
            $data['is_finished'] = 0;
        }
        if($type == 4){
            $data['finish_date'] = $finish_date;
            $data['is_finished'] = 1;
        }
        $id = M('Share')->add($data);

        if(!$id){
            $this->ajaxError('发布失败，请稍后再试');
        }
        if($type == 1){
            $data['share_id'] = $id;
            $data['type'] = $file_type;
            if($file_type == 'image'){
                foreach ($url as $v){
                    $data['url'] = $v;
                    $res = M('ShareFile')->add($data);
                    if(!$res){
                        M('Share')->delete($id);
                        $this->ajaxError('发布失败，请稍后再试');
                    }
                }
            }else{
                $data['url'] = $url;
                $res = M('ShareFile')->add($data);
                if(!$res){
                    M('Share')->delete($id);
                    $this->ajaxError('发布失败，请稍后再试');
                }
            }
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
        if($hangye){
            $where['hangye'] = array('in', $hangye);
        }
        if($uids){
            $where['uid'] = array('in', $uids);
        }
        if($hangye || $uids){
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
        }
        $map['visible']  = array('gt',0);

        $field = 'id, uid, type, img, title, content, comment, liked, follow, finish_date, is_finished, create_time';
        $order = 'create_time DESC';
        $data = $this->pageData($page, 'Share', $map, $field, $order);

        foreach ($data as $k=>$v){
            $info = getUserInfo($v['uid']);
            $data[$k] = array_merge($v, $info);

            $where = [];
            $where['share_id'] = $v['id'];
            $where['uid'] = $this->uid;
            $data[$k]['is_liked'] = M('ShareLiked')->where($where)->count();

            $data[$k]['is_follow'] = M('ShareFollow')->where($where)->count();
        }

        $this->ajaxSuccess($data);
    }

    //获取收藏列表
    public function getFollowShare($page = 1){
        $where['uid'] = $this->uid;
        $data = $this->pageData($page, 'ShareFollow', $where, 'share_id', 'create_time DESC');

        foreach ($data as $k=>$v){

            $field = 'uid, type, img, title, content, comment, liked, follow, finish_date, is_finished, create_time';
            $share_info = M('Share')->field($field)->find($v['share_id']);

            $info = getUserInfo($share_info['uid']);

            $data[$k] = array_merge($v, $info, $share_info);
        }

        $this->ajaxSuccess($data);
    }

}