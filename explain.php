<?php
require 'config.php';
require 'lib/SqlFormatter.php';

$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$dbListTable  = env('SLOWQUERY_DB_LIST_TABLE');
$con          = db();
?>
<html>
<head>
    <meta http-equiv="Content-Type"  content="text/html;  charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>慢查询日志</title>
    <link rel="stylesheet" href="statics/css/simple-line-icons.css">
    <link rel="stylesheet" href="statics/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="statics/css/styles.css">
</head>

<body>

<div class="card">
    <div class="card-header bg-light">
        详细的慢SQL语句是：
    </div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-hover">
<?php
$checksum                       = $_GET['checksum'];
$get_sql                        = "select sample,db_max from ${historyTable} where checksum='${checksum}' limit 1";
$result1                        = mysqli_query($con, $get_sql);
list($sample_sql, $db_max_name) = mysqli_fetch_array($result1);
echo "<tr><td>" . SqlFormatter::format($sample_sql) . "</tr></td>";
?>
</table>

    <div class="card-header bg-light">
        执行计划：
    </div>
    <table class="table table-hover">
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
$get_db_ip                             = "select ip,dbname,user,pwd,port from ${dbListTable} where dbname='${db_max_name}'";
$result2                               = mysqli_query($con, $get_db_ip);
list($ip, $dbname, $user, $pwd, $port) = mysqli_fetch_array($result2);

$con_explain = mysqli_connect("$ip", "$user", "$pwd", "$dbname", "$port") or die("数据库${user}@${ip}:${port}/${db}链接错误" . mysqli_connect_error());
mysqli_query($con_explain, "set names utf8");
$get_sql_explain = "EXPLAIN $sample_sql";
$result3         = mysqli_query($con_explain, $get_sql_explain);
while ($row = mysqli_fetch_array($result3)) {
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
    echo '</tr>';
}
?>

</table>
</div>
</div>
</div>

<?php
$get_sql                        = "SELECT sample,db_max FROM ${historyTable} WHERE checksum='${checksum}' LIMIT 1";
$result1                        = mysqli_query($con, $get_sql);
list($sample_sql, $db_max_name) = mysqli_fetch_array($result1);

$get_db_ip                             = "SELECT ip,dbname,user,pwd,port FROM ${dbListTable} WHERE dbname='${db_max_name}'";
$result2                               = mysqli_query($con, $get_db_ip);
list($ip, $dbname, $user, $pwd, $port) = mysqli_fetch_array($result2);

$sql_advisor_export = "echo '$sample_sql'";

$test_user = env('SOAR_TEST_DB_USER');
$test_pwd  = env('SOAR_TEST_DB_PASSWORD');
$test_ip   = env('SOAR_TEST_DB_HOST');
$test_port = env('SOAR_TEST_DB_PORT');
$test_db   = env('SOAR_TEST_DB_DATABASE');

$html_str = system("$sql_advisor_export | ./include/soar/soar -online-dsn='${user}:${pwd}@${ip}:${port}/${dbname}' -test-dsn='$test_user:$test_pwd@$test_ip:$test_port/$test_db' -report-type='html' -explain=true -log-output=./soar.log");
echo $html_str;
echo '<br><h3><a href="javascript:history.back(-1);">点击此处返回</a></h3></br>';

?>

</body>
</html>


