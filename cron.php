<?php
require 'lib/SendMail.php';

function get_include_contents($filename) {
    if (is_file($filename)) {
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}

$mailContent = get_include_contents('views/get_top100_slowsql.php');

$title='【告警】慢查询报警推送TOP100条,请及时优化.';
$content='下面的慢查询语句或许会影响到数据库的稳定性和健康性，请您在收到此邮件后及时优化语句或代码。数据库的稳定性需要大家的共同努力,感谢您的配合！<br><br>' .$mailContent;

sendMail(env('EMAIL_ADDRESS'), $title, $content);