<?php
namespace Api\Controller;
use Think\Controller;

class IndexController extends Controller{
    public function index(){



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
        $mail->Subject = '共享力用户注册验证码';

        $body = '你好';
        $mail->MsgHTML($body);

        $mail->AddAddress('sinner2046@qq.com');



        echo $mail->Send() ? true : $mail->ErrorInfo;
    }
}