<?php
require 'config.php';
require 'lib/SqlFormatter.php';

$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$dbListTable  = env('SLOWQUERY_DB_LIST_TABLE');
$con          = db();

$checksum                  = $_GET['checksum'];
$result                    = mysqli_query($con, "SELECT `sample`,`db_max` FROM `${historyTable}` WHERE `checksum`='${checksum}' LIMIT 1");
list($sqlSample, $sampleDbName) = mysqli_fetch_array($result);

$slowQueryListResult       = mysqli_query($con, "SELECT *, ROUND(`Query_time_sum`/`ts_cnt`, 3) AS Query_time_avg,
ROUND(`Lock_time_sum`/`ts_cnt`, 3) AS Lock_time_avg,
ROUND(`Rows_examined_sum`/`ts_cnt`, 3) AS Rows_examined_avg,
ROUND(`Rows_sent_sum`/`ts_cnt`,3) AS Rows_sent_avg FROM `${historyTable}` WHERE `checksum`='${checksum}' LIMIT 5");

$result                                = mysqli_query($con, "SELECT `ip`,`dbname`,`user`,`pwd`,`port` FROM `${dbListTable}` WHERE `dbname`='${sampleDbName}'");
list($ip, $dbname, $user, $pwd, $port) = mysqli_fetch_array($result);

$explainResult = false;
$soarResult    = '';
if ($ip) {
    $explainConnection = mysqli_connect("$ip", "$user", "$pwd", "$dbname", $port) or die("数据库${user}@${ip}:${port}/${dbname}链接错误" . mysqli_connect_error());
    mysqli_query($explainConnection, "SET names utf8");
    $explainResult = mysqli_query($explainConnection, "EXPLAIN $sqlSample");

    $test_user = env('SOAR_TEST_DB_USER');
    $test_pwd  = env('SOAR_TEST_DB_PASSWORD');
    $test_ip   = env('SOAR_TEST_DB_HOST');
    $test_port = env('SOAR_TEST_DB_PORT');
    $test_db   = env('SOAR_TEST_DB_DATABASE');

    $soarOutput = [];
    exec("echo '$sqlSample' | ./include/soar/soar -online-dsn='${user}:${pwd}@${ip}:${port}/${dbname}' -test-dsn='$test_user:$test_pwd@$test_ip:$test_port/$test_db' -report-type='html' -explain=true -log-output=./soar.log", $soarOutput);
    $soarResult = implode("\n", $soarOutput);
}

?>
<html>
<head>
    <meta http-equiv="Content-Type"  content="text/html;  charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>慢查询日志</title>
    <link rel="stylesheet" href="statics/css/styles.css">
    <style>
        .card {
            margin-bottom: 0;
        }
        .card-header {
            border-bottom: 1px solid #eee;
        }
        .card-header h3 {
            margin-bottom: 0;
        }
        .sql-sample pre {
            padding: 10px;
            margin-bottom: 0 !important;
        }
        .desc-td {
            padding: 0 !important;
        }
        .desc-td pre {
            padding: 10px;
            background: #fff;
            margin-bottom: 0 !important;
        }
    </style>
</head>

<body>

<div class="card">
    <div class="card-header">
        <h3 class="h3">慢查询详情</h3>
    </div>
    <div class="card-body">
        <h4 class="h4">慢查询样例：</h4>
        <div class="sql-sample">
            <?php
            echo SqlFormatter::format($sqlSample);
            ?>
        </div>

        <h4 class="h4" style="margin-top: 30px;">最近列表：</h4>
        <div class="table-responsive">
            <table class="table table-hover table-striped">
            <tr style="font-size: 14px">
            <th>抽象语句</th>
            <th>数据库</th>
            <th>用户名</th>
            <th>执行时间</th>
            <th>次数</th>
            <th>平均时间(秒)</th>
            <th>最大时间(秒)</th>
            <th>平均锁等待时间(秒)</th>
            <th>最大锁等待时间(秒)</th>
            <th>平均扫描行</th>
            <th>最大扫描行</th>
            <th>平均返回行</th>
            <th>最大返回行</th>
            </tr>
            <?php
            while ($row = mysqli_fetch_array($slowQueryListResult, MYSQLI_ASSOC)) {
                $sample = $row['sample'];
                unset($row['sample']);
                echo "<tr style='cursor: pointer;font-size: 14px;' onclick=\"toggleDesc('${row['checksum']}')\">";
                echo "<td width='100px'>✚  " . substr("{$sample}", 0, 50)
                    . "</td>";
                echo "<td>{$row['db_max']}</td>";
                echo "<td>{$row['user_max']}</td>";
                echo "<td>{$row['ts_min']} ~ {$row['ts_max']}</td>";
                echo "<td>{$row['ts_cnt']}</td>";
                echo "<td>{$row['Query_time_avg']}</td>";
                echo "<td>{$row['Query_time_max']}</td>";
                echo "<td>{$row['Lock_time_avg']}</td>";
                echo "<td>{$row['Lock_time_max']}</td>";
                echo "<td>{$row['Rows_examined_avg']}</td>";
                echo "<td>{$row['Rows_examined_max']}</td>";
                echo "<td>{$row['Rows_sent_avg']}</td>";
                echo "<td>{$row['Rows_sent_max']}</td>";
                echo "</tr>";
                echo "<tr><td colspan='6' class='desc-td'><div id='${row['checksum']}' style='display:none;'>" . SqlFormatter::format($sample) . "</div></td><td class='desc-td'></td><td colspan='6' class='desc-td'><div id='desc-${row['checksum']}' style='display:none;'><pre>" . json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre></div></td></tr>";
            }
            ?>
            </table>
        </div>

    <?php if ($explainResult) {?>
        <h4 class="h4" style="margin-top: 30px;">执行计划：</h4>
        <div class="table-responsive">
            <table class="table table-hover">
                <tr>
                    <th>id</th>
                    <th>select_type</th>
                    <th>table</th>
                    <th>type</th>
                    <th>possible_keys</th>
                    <th>key</th>
                    <th>key_len</th>
                    <th>ref</th>
                    <th>rows</th>
                    <th>Extra</th>
                </tr>
    <?php
        while ($row = mysqli_fetch_array($explainResult)) {
            echo '<tr>' .
                    '<td>' . $row['id'] . '</td>' .
                    '<td>' . $row['select_type'] . '</td>' .
                    '<td>' . $row['table'] . '</td>' .
                    '<td>' . $row['type'] . '</td>' .
                    '<td>' . $row['possible_keys'] . '</td>' .
                    '<td>' . $row['key'] . '</td>' .
                    '<td>' . $row['key_len'] . '</td>' .
                    '<td>' . $row['ref'] . '</td>' .
                    '<td>' . $row['rows'] . '</td>' .
                    '<td>' . $row['Extra'] . '</td>' .
                '</tr>';
        }
        echo '</table></div>';
    } else {
        echo "<br/><h4 class='h4'>未配置数据库 ${sampleDbName} 的链接信息，无法</h4>";
    }
    ?>
    <?php if ($soarResult) { ?>
    <h4 class="h4" style="margin-top: 30px;">Soar优化：</h4>
    <iframe width="100%" id="soar-result" scrolling="no" οnlοad="setIframeHeight('soar-result')" frameborder="0" srcdoc="<?php echo htmlentities(str_replace('width:800px;', '', $soarResult));?>"></iframe>
    </div>
    <?php }?>
</div>
<script>
function setIframeHeight(iframeId) {
    var iframe = document.getElementById(iframeId);
    var height = 0;
    if (iframe) {
        var iframeWin = iframe.contentWindow || iframe.contentDocument.parentWindow;
        height = iframeWin.document.documentElement.scrollHeight || iframeWin.document.body.scrollHeight;
        iframe.height = height;
    }
    return height;
}
var lastHeight = 0;
var timerID = setInterval(function(){
    var height = setIframeHeight('soar-result');
    if (height == lastHeight) {
        clearInterval(timerID);
    } else {
        lastHeight = height;
    }
}, 200);
function toggleDesc(id){
    var sqlPre = document.getElementById(id);
    var descPre = document.getElementById('desc-' + id);
    sqlPre.style.display = sqlPre.style.display=="block" ? "none" : "block";
    descPre.style.display = descPre.style.display=="block" ? "none" : "block";
}
</script>
</body>
</html>


