<?php
namespace Api\Controller;

class ShareOperateController extends BaseController{
    //检测share_id是否存在
    protected $share_id;
    protected function _initialize(){
        parent::_initialize();
        $this->share_id = I('share_id', 0, 'int');
        if($this->share_id < 1){
            $this->ajaxError('请选择正确的内容');
        }
        $where['id'] = $this->share_id;
        $where['visible'] = array('gt', -1);
        $field = 'uid, visible';
        $info = M('Share')->field($field)->where($where)->find();
        if(!$info){
            $this->ajaxError('此内容不存在或被删除');
        }
        if($info['visible'] == 0 && $this->uid != $info['uid']){
            $this->ajaxError('此内容不存在或不公开');
        }
    }

    //获取详情
    public function getShareInfo(){
        $field = 'id, uid, type, img, title, content, comment, liked, follow, finish_date, is_finished, create_time';
        $data = M('Share')->field($field)->find($this->share_id);

        $info = getUserInfo($data['uid']);
        $data['nickname'] = $info['nickname'];
        $data['headimg'] = $info['headimg'];
        $data['zhiye'] = $info['zhiye'];

        $where['share_id'] = $this->share_id;
        $data['comment_list'] = M('ShareComment')->field('uid, content, create_time')->where($where)->select();
        if($data['comment_list']){
            foreach ($data['comment_list'] as $k=>$v){
                $info = getUserInfo($v['uid']);
                $data['comment_list'][$k] = array_merge($v, $info);
            }
        }
        if($data['type'] == 1){
            $data['file'] = M('ShareFile')->field('type, url')->where($where)->select();
        }

        $this->ajaxSuccess($data);
    }

    //删除/设为隐私，公开 内容
    public function setShare(){
        $visible = I('visible');
        $this->share_id = I('share_id', 0, 'int');

        if(!in_array($visible, array('0', '1', '-1'))){
            $this->ajaxError('参数错误');
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

    //添加评论
    public function addComment(){
        $content = I('content');
        if(strLength($content) < 1 || strLength($content) > 100){
            $this->ajaxError('评论内容在1 - 100个字符之间');
        }
        $data['share_id'] = $this->share_id;
        $data['uid'] = $this->uid;
        $data['content'] = $content;
        $data['create_time'] = NOW_TIME;

        $id = M('ShareComment')->add($data);
        if(!$id){
            $this->ajaxError('评论失败，请稍后再试');
        }
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->setInc('comment');
        if(!$res){
            M('ShareComment')->delete($id);
            $this->ajaxError('评论失败，请稍后再试');
        }

        $info = M('Share')->field('uid, title')->where($where)->find();

        $notify = M('notify');
        $notify->uid = $info['uid'];
        $notify->from_uid = $this->uid;
        $notify->type = 1;
        $notify->info = "评论了 {$info['title']}";
        $notify->is_read = 0;
        $notify->act_id = $this->share_id;
        $notify->create_time = NOW_TIME;
        $notify->add();

        $this->ajaxSuccess('', '评论成功');
    }

    //添加收藏
    public function addFollow(){
        $where['share_id'] = $this->share_id;
        $where['uid'] = $this->uid;

        if(M('ShareFollow')->where($where)->count()){
            $this->ajaxError('已经收藏此内容');
        }

        $data['share_id'] = $this->share_id;
        $data['uid'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $id = M('ShareFollow')->add($data);

        if(!$id){
            $this->ajaxError('收藏失败，请稍后再试');
        }
        $where = [];
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->setInc('follow');
        if(!$res){
            M('ShareFollow')->delete($id);
            $this->ajaxError('收藏失败，请稍后再试');
        }
        $this->ajaxSuccess('', '收藏成功');
    }

    //取消收藏
    public function delFollow(){
        $where['share_id'] = $this->share_id;
        $where['uid'] = $this->uid;

        $info = M('ShareFollow')->where($where)->find();
        if(!$info){
            $this->ajaxError('没有收藏此内容');
        }

        $id = M('ShareFollow')->delete($info['id']);
        if(!$id){
            $this->ajaxError('取消收藏失败，请稍后再试');
        }
        $where = [];
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->setDec('follow');
        if(!$res){
            M('ShareFollow')->add($info);
            $this->ajaxError('取消收藏失败，请稍后再试');
        }
        $this->ajaxSuccess('', '取消收藏成功');
    }

    //点赞
    public function addLiked(){
        $where['share_id'] = $this->share_id;
        $where['uid'] = $this->uid;

        if(M('ShareLiked')->where($where)->count()){
            $this->ajaxError('已经点过赞了');
        }

        $data['share_id'] = $this->share_id;
        $data['uid'] = $this->uid;
        $data['create_time'] = NOW_TIME;
        $id = M('ShareLiked')->add($data);

        if(!$id){
            $this->ajaxError('点赞失败，请稍后再试');
        }
        $where = [];
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->setInc('liked');
        if(!$res){
            M('ShareFollow')->delete($id);
            $this->ajaxError('点赞失败，请稍后再试');
        }
        $this->ajaxSuccess('', '点赞成功');
    }

    //取消点赞
    public function delLiked(){
        $where['share_id'] = $this->share_id;
        $where['uid'] = $this->uid;

        $info = M('ShareLiked')->where($where)->find();
        if(!$info){
            $this->ajaxError('没有点赞此内容');
        }

        $id = M('ShareLiked')->delete($info['id']);
        if(!$id){
            $this->ajaxError('取消点赞失败，请稍后再试');
        }
        $where = [];
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->setDec('liked');
        if(!$res){
            M('ShareLiked')->add($info);
            $this->ajaxError('取消点赞失败，请稍后再试');
        }
        $this->ajaxSuccess('', '取消点赞成功');
    }

    //设置目标已完成，未完成
    public function setFinshed(){
        $finished = I('finished', 0, 'int');
        if(!in_array($finished, array(1, -1))){
            $this->ajaxError('参数错误');
        }

        $where['id'] = $this->share_id;
        $where['uid'] = $this->uid;
        $where['type'] = 3;
        $where['is_finished'] = 0;
        $where['visible'] = array('gt', -1);
        if(!M('Share')->where($where)->count()){
            $this->ajaxError('没有需要设置的目标');
        }

        if($finished == 1){
            $data['is_finished'] = 1;
            $data['type'] = 4;
        }
        if($finished == -1){
            $data['is_finished'] = -1;
        }
        $where = [];
        $where['id'] = $this->share_id;
        $res = M('Share')->where($where)->save($data);

        if(!$res){
            $this->ajaxError('设置失败，请稍后再试');
        }
        $this->ajaxSuccess('', '设置成功');
    }
}