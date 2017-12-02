<?php
namespace Api\Controller;

class UserController extends BaseController{
    //更改用户头像
    public function changeHeadimg(){
        /* 图片上传相关配置 */
        $config = array(
            'maxSize'  => 2*1024*1024, //上传的文件大小限制 (0-不做限制)
            'exts'     => 'jpg,gif,png,jpeg', //允许上传的文件后缀
            'autoSub'  => true, //自动子目录保存文件
            'subName'  => array('date', 'Y-m-d'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'rootPath' => './Uploads/Headimg/', //保存根路径
            'savePath' => '', //保存路径
            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
            'saveExt'  => '', //文件保存后缀，空则使用原后缀
            'replace'  => false, //存在同名是否覆盖
        );
        $upload = new \Think\Upload($config);
        $info   =   $upload->upload();

        if(!$info) {// 上传错误提示错误信息
            $this->ajaxError($upload->getError());

        }else{// 上传成功 获取上传文件信息
            if($info['headimg']['savename']){

                $users = M('User');
                $where['uid'] = $this->uid;
                $headimg = $users->where($where)->getField('headimg');

                $data['headimg'] = substr($config['rootPath'], 1).$info['headimg']['savepath'].$info['headimg']['savename'];
                $id = $users->where($where)->save($data);
                if($id){
                    if($headimg && $headimg != '/Uploads/headimg.jpg'){
                        @unlink(substr($headimg, 1));
                    }

                    $this->ajaxSuccess($data['headimg'], '上传头像成功');
                }else{
                    $this->ajaxError('头像保存失败，请稍后再试');
                }
            }else{
                $this->ajaxError('头像上传失败，请稍后再试');
            }
        }
    }

    //修改个人信息
    public function changeInfo(){
        $nickname = I('nickname');
        $sex = I('sex', 1, 'int');
        $signature = I('signature');
        $birthday = I('birthday');
        $prov = I('prov');
        $city = I('city');
        $dist = I('dist');
        $visible = I('visible', 1, 'int');

        if(empty($nickname)){
            $this->ajaxError('昵称不能为空');
        }
        if(strLength($nickname) < 1 || strLength($nickname) > 10){
            $this->ajaxError('昵称在1-10个字符之间');
        }
        if(!in_array($sex, array('1','2'))){
            $this->ajaxError('请选择性别');
        }
        if(empty($signature)){
            $this->ajaxError('签名不能为空');
        }
        if(strLength($signature) < 1 || strLength($signature) > 100){
            $this->ajaxError('签名在1-100个字符之间');
        }
        if(!checkDateFormat($birthday)){
            $this->ajaxError('请选择正确的出生日期');
        }
        if(strLength($prov) < 2 || strLength($prov) > 10){
            $this->ajaxError('请选择省');
        }
        if(strLength($city) < 2 || strLength($city) > 10){
            $this->ajaxError('请选择市');
        }
        if(strLength($dist) < 2 || strLength($dist) > 10){
            $this->ajaxError('请选择区');
        }
        if(!in_array($visible, array('1', '2', '0'))){
            $this->ajaxError('请选择资料可见性');
        }

        $data = array(
            'nickname' => $nickname,
            'sex' => $sex,
            'signature' => $signature,
            'birthday' => $birthday,
            'prov' => $prov,
            'city' => $city,
            'dist' => $dist,
            'visible' => $visible
        );
        $where['uid'] = $this->uid;
        $res = M('User')->where($where)->save($data);

        if($res){
            $this->ajaxSuccess('', '资料修改成功');
        }else{
            $this->ajaxError('资料修改失败');
        }
    }

    //修改职业信息
    public function changeTag(){
        $hangye = I('hangye', 0, 'int');
        $zhiye = I('zhiye', 0, 'int');
        $tag = I('tag');
        $hangye1 = I('hangye1', 0, 'int');
        $zhiye1 = I('zhiye1', 0, 'int');
        $tag1 = I('tag1');

        if(empty($hangye) || empty($zhiye) || empty($tag)){
            $this->ajaxError('请选择主业信息');
        }
        if(!checkTag($hangye)){
            $this->ajaxError('主业行业信息不存在');
        }
        if(!checkTag($zhiye)){
            $this->ajaxError('主业职业信息不存在');
        }
        $tags = explode(',', $tag);
        if(count($tags) > 2){
            $this->ajaxError('主业技能标签最多选择两个');
        }
        foreach ($tags as $t){
            if(!checkTag($t)){
                $this->ajaxError('主业技能标签不存在');
            }
        }

        $data['hangye'] = $hangye;
        $data['zhiye'] = $zhiye;
        $data['tag'] = $tag;

        if(!empty($hangye1)){
            if(!checkTag($hangye1)){
                $this->ajaxError('副业行业信息不存在');
            }
            $data['hangye1'] = $hangye1;
        }
        if(!empty($zhiye1)){
            if(!checkTag($zhiye1)){
                $this->ajaxError('副业职业信息不存在');
            }
            $data['zhiye1'] = $zhiye1;
        }
        if(!empty($tag1)){
            $tags1 = explode(',', $tag1);
            if(count($tags1) > 2){
                $this->ajaxError('副业技能标签最多选择两个');
            }
            foreach ($tags1 as $t1){
                if(!checkTag($t1)){
                    $this->ajaxError('副业技能标签不存在');
                }
            }
            $data['tag1'] = $tag1;
        }

        $where['uid'] = $this->uid;
        $res = M('User')->where($where)->save($data);

        if($res){
            $this->ajaxSuccess('', '资料修改成功');
        }else{
            $this->ajaxError('资料修改失败');
        }
    }

    //获取用户资料
    public function getUserInfo(){
        $where['uid'] = $this->uid;
        $field = 'email, mobile, headimg, nickname, sex, signature, birthday, prov, city, dist, visible, hangye, zhiye, tag, hangye1, zhiye1, tag1';
        $data = M('User')->field($field)->where($where)->find();

        $data['hangye'] = getTagName($data['hangye']);
        $data['zhiye'] = getTagName($data['zhiye']);
        $data['tag'] = getTagName($data['tag']);
        $data['hangye1'] = getTagName($data['hangye1']);
        $data['zhiye1'] = getTagName($data['zhiye1']);
        $data['tag1'] = getTagName($data['tag1']);

        $this->ajaxSuccess($data);
    }

    //时间银行记录
    public function getTimesLog($page = 1){
        $type = I('type', 0, 'int');
        if(!in_array($type, array(0, 1, -1, 2))){
            $this->ajaxError('参数错误');
        }
        $where['uid'] = $this->uid;
        if($type != 0){
            if($type == 2){
                $where['act'] = 'recharge';
            }else{
                $where['type'] = $type;
            }
        }
        $field = 'type, amount, info, create_time, after';
        $data = $this->pageData($page, 'TimesLog', $where, $field, 'create_time DESC');

        $where = [];
        $where['uid'] = $this->uid;
        $where['type'] = 1;
        $where['create_time'] = array(
            array('egt', strtotime(date('Y-m-d', NOW_TIME )  . ' 00:00:00')),
            array('elt', strtotime(date('Y-m-d', NOW_TIME) . ' 23:59:59'))
        );
        $this->data['total_in'] = M('TimesLog')->where($where)->sum('amount');

        $where['type'] = -1;
        $this->data['total_out'] = M('TimesLog')->where($where)->sum('amount');

        $this->ajaxSuccess($data);
    }

    //换绑手机
    public function changeMobile(){
        $mobile = I('mobile');
        $code = I('code', 0, 'int');
        if(!checkMobile($mobile)){
            $this->ajaxError('手机号码格式不正确');
        }
        if(empty($code)){
            $this->ajaxError('验证码不能为空');
        }
        if($code < 100000 || $code > 999999){
            $this->ajaxError('验证码格式不正确');
        }

        $where['account'] = $mobile;
        $where['mode'] = 1;
        $where['type'] = 3;
        $code_info = M('Code')->where($where)->order('create_time DESC')->find();
        if(!$code_info){
            $this->ajaxError('请先发送验证码');
        }
        if($code != $code_info['code']){
            $this->ajaxError('验证码错误');
        }

        $where = [];
        $where['mobile'] = $mobile;
        if(M('User')->where($where)->count()){
            $this->ajaxError('此手机号码已经注册');
        }

        $where = [];
        $where['uid'] = $this->uid;
        $data['mobile'] = $mobile;
        $res = M('User')->where($where)->save($data);
        if(!$res){
            $this->ajaxError('手机号码更改失败，请稍后再试');
        }
        $this->ajaxSuccess('', '手机号码更换成功');
    }

    //换绑邮箱
    public function changeEmail(){
        $email = I('email');
        $code = I('code', 0, 'int');
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $this->ajaxError('邮箱格式不正确');
        }
        if(empty($code)){
            $this->ajaxError('验证码不能为空');
        }
        if($code < 100000 || $code > 999999){
            $this->ajaxError('验证码格式不正确');
        }

        $where['account'] = $email;
        $where['mode'] = 2;
        $where['type'] = 3;
        $code_info = M('Code')->where($where)->order('create_time DESC')->find();
        if(!$code_info){
            $this->ajaxError('请先发送验证码');
        }
        if($code != $code_info['code']){
            $this->ajaxError('验证码错误');
        }

        $where = [];
        $where['email'] = $email;
        if(M('User')->where($where)->count()){
            $this->ajaxError('此邮箱已经注册');
        }

        $where = [];
        $where['uid'] = $this->uid;
        $data['email'] = $email;
        $res = M('User')->where($where)->save($data);
        if(!$res){
            $this->ajaxError('邮箱更改失败，请稍后再试');
        }
        $this->ajaxSuccess('', '邮箱更换成功');
    }

    //更改密码
    public function changePassword(){
        $password = I('password');
        $new_password = I('new_password');
        if(empty($password) || empty($new_password)){
            $this->ajaxError('密码不能为空');
        }

        $user = D('Admin/User');
        if(!$user->verifyPassword($this->uid, $password)){
            $this->ajaxError('用户密码错误');
        }
        if(!$user->changePassword($this->uid, $new_password)){
            $this->ajaxError($user->getError());
        }
        $this->ajaxSuccess('','密码修改成功');
    }
}