<?php
namespace Admin\Model;
use Think\Model;

define('AUTH_KEY','u1E|zO4AwiLdyoi>-[P:HvU0j1p{3*M!K~h$)NyC');

class UserModel extends Model{

    protected $_validate = array(
        /* 验证手机号码 */
        array('mobile', '11', '手机号长度不正确 ', self::EXISTS_VALIDATE,  'length'),
        array('mobile', '/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$/', '手机号格式不正确 ', self::EXISTS_VALIDATE,  'regex'),
        array('mobile', '', '手机号被占用', self::EXISTS_VALIDATE, 'unique'),
        /* 验证邮箱 */
        array('email', 'email', '邮箱格式不正确', self::EXISTS_VALIDATE),
        array('email', '1,32', '邮箱长度不合法', self::EXISTS_VALIDATE, 'length'),
        array('email', '', '邮箱被占用', self::EXISTS_VALIDATE, 'unique'),
        /* 验证密码 */
        array('password', '6,30', '密码长度不合法', self::EXISTS_VALIDATE, 'length'),
    );

    protected $_auto = array(
        array('password', 'think_ucenter_md5', self::MODEL_BOTH, 'callback', AUTH_KEY),
        array('headimg', '/Uploads/headimg.jpg', self::MODEL_INSERT),
        array('reg_time', NOW_TIME, self::MODEL_INSERT),
        array('status', 1, self::MODEL_INSERT),
    );

    protected function think_ucenter_md5($str, $key = 'ThinkUCenter'){
        return '' === $str ? '' : md5(sha1($str) . $key);
    }

    public function register($data){
        if($this->create($data)){
            $res = $this->add();
            return $res;
        } else {
            return false;
        }
    }

    public function verifyPassword($uid, $password){
        $md5_password = $this->getFieldById($uid, 'password');
        if($this->think_ucenter_md5($password, AUTH_KEY) === $md5_password){
            return true;
        }
        return false;
    }
}