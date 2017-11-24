<?php
//检测手机号码
function checkMobile($mobile){
    if(strlen($mobile) != 11){
        return false;
    }
    if(preg_match('/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$/', $mobile)){
        return true;
    }else{
        return false;
    }
}

//获取字符串长度
function strLength($str){
    return mb_strlen($str,"UTF8");
}

//检测日期
function checkDateFormat($date){
    if (preg_match ("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $date, $parts)){
        //检测是否为日期
        if(checkdate($parts[2],$parts[3],$parts[1])){
            return true;
        }else{
            return false;
        }
    }
}

//检测职业，行业，标签
function checkTag($id){
    $where['id'] = $id;
    return M('Cate')->where($where)->count();
}

//获取标签名称
function getTagName($id){
    if(empty($id)){
        return '';
    }
    $ids = explode(',', $id);
    if(count($ids) > 1){
        $ids = explode(',', $id);
        foreach ($ids as $i){
            $where['id'] = $i;
            $name[] = M('Cate')->where($where)->getField('name');
        }
    }else{
        $where['id'] = $id;
        $name = M('Cate')->where($where)->getField('name');
    }
    return $name;
}

//发送邮件验证码
function sendEmail($email, $type, $code){
    Vendor('PHPMailer.PHPMailer');
    Vendor('PHPMailer.SMTP');
    $mail = new \PHPMailer();

    $mail->CharSet = 'UTF-8';
    $mail->IsSMTP();
    $mail->SMTPDebug = 0;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';

    $mail->Host = 'smtp.sharingli.com';
    $mail->Port = '465';

    $mail->Username = 'service@sharingli.com';
    $mail->Password = 'ZHANGLIaliyun@';
    $mail->From = 'service@sharingli.com';

    $mail->FromName = '共享力';
    $mail->Subject = '共享力'.$type.'验证码';

    $body = '<div style="width: 80%;margin: 0 auto;padding-bottom:30px;border: 1px solid #888;"><div class="header" style="height:68px;text-align: center;background-color: #029BDE;font-size: 32px;color:#ffffff;line-height: 68px;">共享力</div><div class="main" style="padding: 25px;text-align: center;"><p style="text-align: left;">尊敬的用户，你好！</p><p style="text-align: left;">您当前正在进行'.$type.'操作，请在30分钟内输入如下验证码进行下一步操作：</p><span style="display: inline-block;background: rgba(2,155,222,0.2);font-size: 24px;padding: 15px;color: #027ADE;">'.$code.'</span></div></div>';
    $mail->MsgHTML($body);

    $mail->AddAddress($email);

    return $mail->Send() ? true : false;
}

//发送手机验证码
function sendSms($mobile, $code){
    $appid = 1400050830;
    $appkey = "cfc62f6ca4f0d750fb28e544cf76efe0";
    $random = mt_rand(100000, 999999);
    $time = NOW_TIME;
    $url = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms?sdkappid={$appid}&random={$random}";
    $data = array(
        'tel' => array(
            'nationcode' => '86',
            'mobile' => $mobile
        ),
        'tpl_id' => 59411,
        'params' => array($code),
        'sig' => hash('sha256', "appkey={$appkey}&random={$random}&time={$time}&mobile={$mobile}"),
        'time' => $time
    );

    $res = sendCurlPost($url, json_encode($data));
    if($res->result != 0){
        return false;
    }
    return true;
}

function sendCurlPost($url, $data) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS,  $data);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    $result = curl_exec($curl);

    curl_close($curl);
    return json_decode($result);
}

//账户时间变动
function timesChange($uid, $amount, $type, $info, $act, $act_id){
    $where['uid'] = $uid;
    $times = M('User')->where($where)->getField('times');
    if($type > 0){
        $user['times'] = $times + $amount;
    }else{
        $user['times'] = $times - $amount;
    }

    $times_change = M('User')->where($where)->save($user);
    if(!$times_change){
        return false;
    }

    $log = array(
        'uid' => $uid,
        'type' => $type,
        'info' => $info,
        'amount' => $amount,
        'after' => $user['times'],
        'act' => $act,
        'act_id' => $act_id,
        'create_time' => NOW_TIME
    );
    $log_id = M('TimesLog')->add($log);

    if(!$log_id){
        $user['times'] = $times;
        M('User')->where($where)->save($user);
        return false;
    }
    return true;
}

//获取链接用户id
function getLink($uid){
    $where['uid'] = $uid;
    $uids = M('Friend')->field('follow_uid')->where($where)->select();
    if(!$uids){
        return false;
    }

    foreach ($uids as $k=>$v){
        if($k == 0){
            $uids_str = $v['follow_uid'];
        }else{
            $uids_str .= ','.$v['follow_uid'];
        }
    }
    return $uids_str;
}

//获取用户信息
function getUserInfo($uid){
    $where['uid'] = $uid;
    $info = M('User')->field('nickname, headimg, zhiye')->where($where)->find();

    if($info['zhiye']){
        $info['zhiye'] = getTagName($info['zhiye']);
    }else{
        $info['zhiye'] = '';
    }
    return $info;
}