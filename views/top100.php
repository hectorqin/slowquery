<?php
if (is_file('config.php')) {
    require 'config.php';
} else if (is_file('../config.php')) {
    require '../config.php';
}
$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$con          = db();

$sql          = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,r.sample,SUM(h.ts_cnt) AS ts_cnt,
ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
ROUND(SUM(h.Lock_time_sum)/SUM(h.ts_cnt),3) AS Lock_time_avg,ROUND(MAX(h.Lock_time_max),3) AS Lock_time_max,
ROUND(SUM(h.Rows_examined_sum)/SUM(h.ts_cnt),3) AS Rows_examined_avg,ROUND(MAX(h.Rows_examined_max),3) AS Rows_examined_max,
ROUND(SUM(h.Rows_sent_sum)/SUM(h.ts_cnt),3) AS Rows_sent_avg,ROUND(MAX(h.Rows_sent_max),3) AS Rows_sent_max
    FROM ${reviewTable} AS r
    JOIN ${historyTable} AS h ON r.checksum = h.checksum
    WHERE
    r.last_seen >= SUBDATE(NOW(), INTERVAL 5 DAY)
    GROUP BY
    r.checksum
    ORDER BY
        r.last_seen DESC,
        ts_cnt DESC
    LIMIT
    100";

$slowQueryListResult = mysqli_query($con, $sql);
?>
<html>
<head>
    <meta http-equiv="Content-Type"  content="text/html;  charset=UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>慢查询日志</title>

<body>

<style>
    th,td{border:1px solid red;}
</style>

<table style="table-layout:fixed;width:100%;border:0;" cellpadding="1" cellspacing="0">
<tr style="font-size: 14px">
<th style="width:30%">抽象语句</th>
<th>数据库</th>
<th>用户名</th>
<th>最近时间</th>
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
    $row['last_seen'] = fixTimeZone($row['last_seen']);
    echo "<tr>";
    echo "<td style='width:30%'>{$row['fingerprint']}</td>";
    echo "<td align='center'>{$row['db_max']}</td>";
    echo "<td align='center'>{$row['user_max']}</td>";
    echo "<td align='center'>{$row['last_seen']}</td>";
    echo "<td align='center'>{$row['ts_cnt']}</td>";
    echo "<td align='center'>{$row['Query_time_avg']}</td>";
    echo "<td align='center'>{$row['Query_time_max']}</td>";
    echo "<td align='center'>{$row['Lock_time_avg']}</td>";
    echo "<td align='center'>{$row['Lock_time_max']}</td>";
    echo "<td align='center'>{$row['Rows_examined_avg']}</td>";
    echo "<td align='center'>{$row['Rows_examined_max']}</td>";
    echo "<td align='center'>{$row['Rows_sent_avg']}</td>";
    echo "<td align='center'>{$row['Rows_sent_max']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";
echo "</div>";
echo "</div>";

?>

