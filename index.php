<?php
require 'config.php';
require 'lib/SqlFormatter.php';

$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$dbListTable  = env('SLOWQUERY_DB_LIST_TABLE');
$con          = db();
session_start();

$selectDb = false;
if (isset($_GET['db']) && $_GET['db']) {
    $_SESSION['selectDb'] = $_GET['db'];
    $selectDb             = $_GET['db'];
} else {
    unset($_SESSION['selectDb']);
}
$daysSince = isset($_GET['daysSince']) ? $_GET['daysSince'] : 30;

$dbListResult = mysqli_query($con, "SELECT dbname,label FROM ${dbListTable} order by dbname ASC");

$condition   = $daysSince ? "last_seen >= SUBDATE(NOW(),INTERVAL $daysSince DAY)" : '';
$countResult = mysqli_query($con, "select count(*) from ${reviewTable} " . ($condition ? "WHERE ${condition}" : '')); //获得记录总数
$count       = mysqli_fetch_array($countResult);

list($pageStr, $limit) = page($count[0], 50, ['daysSince' => $daysSince, 'db' => $selectDb]);

if (!empty($selectDb)) {
    $condition = $condition ? "AND r.${condition}" : "";
    $sql       = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,r.sample,SUM(h.ts_cnt) AS ts_cnt,
    ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
    ROUND(SUM(h.Lock_time_sum)/SUM(h.ts_cnt),3) AS Lock_time_avg,ROUND(MAX(h.Lock_time_max),3) AS Lock_time_max,
    ROUND(SUM(h.Rows_examined_sum)/SUM(h.ts_cnt),3) AS Rows_examined_avg,ROUND(MAX(h.Rows_examined_max),3) AS Rows_examined_max,
    ROUND(SUM(h.Rows_sent_sum)/SUM(h.ts_cnt),3) AS Rows_sent_avg,ROUND(MAX(h.Rows_sent_max),3) AS Rows_sent_max
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
WHERE h.db_max = '${selectDb}'
${condition}
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $limit";
} else {
    $condition = $condition ? "AND r.${condition}" : "";
    $sql       = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,r.sample,SUM(h.ts_cnt) AS ts_cnt,
    ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
    ROUND(SUM(h.Lock_time_sum)/SUM(h.ts_cnt),3) AS Lock_time_avg,ROUND(MAX(h.Lock_time_max),3) AS Lock_time_max,
    ROUND(SUM(h.Rows_examined_sum)/SUM(h.ts_cnt),3) AS Rows_examined_avg,ROUND(MAX(h.Rows_examined_max),3) AS Rows_examined_max,
    ROUND(SUM(h.Rows_sent_sum)/SUM(h.ts_cnt),3) AS Rows_sent_avg,ROUND(MAX(h.Rows_sent_max),3) AS Rows_sent_max
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
${condition}
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $limit";
}

$slowQueryListResult = mysqli_query($con, $sql);
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
        .pagination {
            justify-content: center;
        }
        .desc-td {
            padding: 0 !important;
        }
        .desc-td a {
            display: block;
            padding: 15px;
        }
        .desc-td pre {
            padding: 10px;
            margin-bottom: 0 !important;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <h3 class="h3">MySQL 慢查询分析</h3>
    </div>

    <div class="card-body">
        <form class="form-inline" action="" method="get">
            <select class="form-control" id="db-select" name="db" data-val="<?php echo $selectDb; ?>">
                <option value="">选择你的数据库</option>
                <?php
                while ($row = mysqli_fetch_array($dbListResult)) {
                    echo "<option value='" . $row[0] . "'>" . $row[1] . "</option>";
                }
                ?>
            </select>
            <input type="hidden" name="daysSince" value="<?php echo $daysSince; ?>"/>
            <input type="submit" class="btn btn-primary" style="margin-left: 10px"/>
        </form>

<?php
if ($selectDb) {
    require './views/show.html';
} else {
    require './views/top.html';
}
?>

        <h4 class="h4" style="margin-top: 30px;">慢日志记录 <small class="small">采集阈值：10分钟/次</small></h4>
        <form class="form-inline" action="" method="get">
            <label for="daysSince-select">慢日志记录查看范围： </label>
            <select class="form-control" id="daysSince-select" name="daysSince" data-val="<?php echo $daysSince; ?>">
                <option value="7">最近7天</option>
                <option value="15">最近15天</option>
                <option value="30">最近30天</option>
                <option value="0">全部</option>
            </select>
            <input type="hidden" name="db" value="<?php echo $selectDb; ?>"/>
            <input type="submit" class="btn btn-primary" style="margin-left: 10px"/>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
            <tr style="font-size: 14px">
            <th>抽象语句</th>
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
                echo "<tr style='cursor: pointer;font-size: 14px;' onclick=\"toggleDesc('${row['checksum']}')\">";
                echo "<td width='100px'>✚  " . substr("{$row['fingerprint']}", 0, 50)
                    . "</td>";
                echo "<td>{$row['db_max']}</td>";
                echo "<td>{$row['user_max']}</td>";
                echo "<td>{$row['last_seen']}</td>";
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
                echo "<tr><td colspan='13' class='desc-td'><a id='${row['checksum']}' style='display:none;' href='explain.php?checksum={$row['checksum']}'>" . SqlFormatter::format($row['sample']) . "</a></td></tr>";
            }
            ?>
            </table>
        </div>
    </div>
</div>

<?php echo $pageStr; ?>

<script>
    document.getElementById('db-select').value = document.getElementById('db-select').getAttribute('data-val');
    document.getElementById('daysSince-select').value = document.getElementById('daysSince-select').getAttribute('data-val');
    function toggleDesc(id){
        var sqlPre = document.getElementById(id);
        sqlPre.style.display = sqlPre.style.display=="block" ? "none" : "block";
    }
</script>
</body>
</html>