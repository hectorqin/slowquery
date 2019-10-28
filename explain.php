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
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['select_type'] . '</td>';
            echo '<td>' . $row['table'] . '</td>';
            echo '<td>' . $row['type'] . '</td>';
            echo '<td>' . $row['possible_keys'] . '</td>';
            echo '<td>' . $row['key'] . '</td>';
            echo '<td>' . $row['key_len'] . '</td>';
            echo '<td>' . $row['ref'] . '</td>';
            echo '<td>' . $row['rows'] . '</td>';
            echo '<td>' . $row['Extra'] . '</td>';
            echo '</tr></table></div>';
        }
    } else {
        echo "<p>未配置数据库 ${sampleDbName} 的链接信息</p>";
    }
    ?>
    </div>
</div>

<?php
echo $soarResult;
?>
</body>
</html>


