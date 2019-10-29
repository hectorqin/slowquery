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

$mailContent = get_include_contents('views/top100.php');

$title='【告警】当天慢查询报警推送TOP100条,请及时优化.';

sendMail(env('EMAIL_ADDRESS'), $title, $mailContent);