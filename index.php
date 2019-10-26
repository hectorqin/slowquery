<?php
require 'config.php';
require 'lib/SqlFormatter.php';

$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$con = db();
session_start();

$selectDb = false;
if (isset($_GET['db']) && $_GET['db']) {
    $_SESSION['selectDb'] = $_GET['db'];
    $selectDb = $_GET['db'];
} else {
    unset($_SESSION['selectDb']);
}
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

<script language="javascript">
function TestBlack(TagName){
 var obj = document.getElementById(TagName);
 if(obj.style.display=="block"){
  obj.style.display = "none";
 }else{
  obj.style.display = "block";
 }
}
</script>
</head>
<body>
<div class="card">
<div class="card-header bg-light">
    <h1><a href="index.php">MySQL 慢查询分析</a></h1>
</div>

<div class="card-body">
<div class="table-responsive">
<form action="" method="get" name="sql_statement">
  <div>
    <tr>
        <td>
            <select id="select" name="db">
                <option value="">选择你的数据库</option>
                <?php
$dbListTable = env('SLOWQUERY_DB_LIST_TABLE');

$result = mysqli_query($con, "SELECT dbname,label FROM ${dbListTable} order by dbname ASC");
while ($row = mysqli_fetch_array($result)) {
    if ($selectDb) {
        if ($selectDb == $row[0]) {
            echo "<option selected='selected' value=\"" . $row[0] . "\">" . $row[1] . "</option>" . "<br>";
        } else {
            echo "<option value=\"" . $row[0] . "\">" . $row[1] . "</option>" . "<br>";
        }
    } else {echo "<option value=\"" . $row[0] . "\">" . $row[1] . "</option>" . "<br>";}
}
?>
            </select>
        <td>
    </tr>
    <input type="submit" class="STYLE3"/>
    </label>
  </div>
</form>

<?php
if ($selectDb) {
    require './views/show.html';
} else {
    require './views/top.html';
}
?>

<table class="table table-hover">
<thead>
<tr>
<th>抽象语句</th>
<th>数据库</th>
<th>用户名</th>
<th>最近时间</th>
<th>次数</th>
<th>平均时间</th>
<th>最小时间</th>
<th>最大时间</th>
</tr>
</thead>
<tbody>

<?php
$perNumber    = 50; //每页显示的记录数
$page         = isset($_GET['page']) ? $_GET['page'] : 1; //获得当前的页面值
$count        = mysqli_query($con, "select count(*) from ${reviewTable}"); //获得记录总数
$rs           = mysqli_fetch_array($count);
$totalNumber  = $rs[0];
$totalPage    = ceil($totalNumber / $perNumber); //计算出总页数

$startCount = ($page - 1) * $perNumber; //分页开始,根据此方法计算出开始的记录

if (!empty($selectDb)) {
    $sql = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,SUM(h.ts_cnt) AS ts_cnt,
ROUND(MIN(h.Query_time_min),3) AS Query_time_min,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,r.sample
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
WHERE h.db_max = '${selectDb}'
AND r.last_seen >= SUBDATE(NOW(),INTERVAL 31 DAY)
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $startCount,$perNumber";
} else {
    $sql = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,SUM(h.ts_cnt) AS ts_cnt,
ROUND(MIN(h.Query_time_min),3) AS Query_time_min,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,r.sample
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
WHERE r.last_seen >= SUBDATE(NOW(),INTERVAL 31 DAY)
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $startCount,$perNumber";
}

$result = mysqli_query($con, $sql);

echo "<br> 慢查询日志agent采集阀值是每10分钟/次，SQL执行时间（单位：秒）</br>";

while ($row = mysqli_fetch_array($result)) {
    echo "<tr>";
    echo "<td width='100px' onclick=\"TestBlack('${row['0']}')\">✚  &nbsp;" . substr("{$row['1']}", 0, 50)
    . "<div id='${row['0']}' style='display:none;'><a href='slowquery_explain.php?checksum={$row['0']}'>" . SqlFormatter::format($row['1']) . "</br></div></a></td>";
    echo "<td>{$row['2']}</td>";
    echo "<td>{$row['3']}</td>";
    echo "<td>{$row['4']}</td>";
    echo "<td>{$row['5']}</td>";
    echo "<td>{$row['8']}</td>";
    echo "<td>{$row['6']}</td>";
    echo "<td>{$row['7']}</td>";
    echo "</tr>";
}
//end while

echo "</tbody>";
echo "</table>";
echo "</div>";
echo "</div>";
echo "</div>";

$maxPageCount = 10;
$buffCount    = 2;
$startPage    = 1;

if ($page < $buffCount) {
    $startPage = 1;
} else if ($page >= $buffCount and $page < $totalPage - $maxPageCount) {
    $startPage = $page - $buffCount + 1;
} else {
    $startPage = $totalPage - $maxPageCount + 1;
}

$endPage = $startPage + $maxPageCount - 1;

$htmlstr = "";

$htmlstr .= "<table class='bordered' border='1' align='center'><tr>";
if ($page > 1) {
    $htmlstr .= "<td> <a href='index.php?db=$selectDb&page=" . "1" . "'>第一页</a></td>";
    $htmlstr .= "<td> <a href='index.php?db=$selectDb&page=" . ($page - 1) . "'>上一页</a></td>";
}

$htmlstr .= "<td> 总共${totalPage}页</td>";

for ($i = $startPage; $i <= $endPage; $i++) {
    $htmlstr .= "<td><a href='index.php?db=$selectDb&page=" . $i . "'>" . $i . "</a></td>";
}

if ($page < $totalPage) {
    $htmlstr .= "<td><a href='index.php?db=$selectDb&page=" . ($page + 1) . "'>下一页</a></td>";
    $htmlstr .= "<td><a href='index.php?db=$selectDb&page=" . $totalPage . "'>最后页</a></td>";

}

$htmlstr .= "</tr></table>";

echo $htmlstr;

?>

