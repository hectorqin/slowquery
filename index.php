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

$perNumber   = 50; //每页显示的记录数
$condition   = $daysSince ? "last_seen >= SUBDATE(NOW(),INTERVAL $daysSince DAY)" : '';
$page        = isset($_GET['page']) ? $_GET['page'] : 1; //获得当前的页面值
$count       = mysqli_query($con, "select count(*) from ${reviewTable} " . ($condition ? "WHERE ${condition}" : '')); //获得记录总数
$rs          = mysqli_fetch_array($count);
$totalNumber = $rs[0];
$totalPage   = ceil($totalNumber / $perNumber); //计算出总页数

$startCount = ($page - 1) * $perNumber; //分页开始,根据此方法计算出开始的记录

if (!empty($selectDb)) {
    $condition = $condition ? "AND r.${condition}" : "";
    $sql       = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,SUM(h.ts_cnt) AS ts_cnt,
ROUND(MIN(h.Query_time_min),3) AS Query_time_min,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,r.sample
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
WHERE h.db_max = '${selectDb}'
${condition}
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $startCount,$perNumber";
} else {
    $condition = $condition ? "AND r.${condition}" : "";
    $sql       = "SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,SUM(h.ts_cnt) AS ts_cnt,
ROUND(MIN(h.Query_time_min),3) AS Query_time_min,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,r.sample
FROM ${reviewTable} AS r JOIN ${historyTable} AS h
ON r.checksum=h.checksum
${condition}
GROUP BY r.checksum
ORDER BY r.last_seen DESC,ts_cnt DESC LIMIT $startCount,$perNumber";
}

$slowQueryListResult = mysqli_query($con, $sql);

$maxPageCount = 10;
$buffCount    = 2;
$startPage    = 1;

if ($totalPage < $maxPageCount) {
    $maxPageCount = $totalPage;
}

if ($page < $buffCount) {
    $startPage = 1;
} else if ($page >= $buffCount and $page < $totalPage - $maxPageCount) {
    $startPage = $page - $buffCount + 1;
} else {
    $startPage = $totalPage - $maxPageCount + 1;
}

$endPage = $startPage + $maxPageCount - 1;

$pageStr = "";

$pageStr .= "<ul class='pagination'>";
if ($page > 1) {
    $pageStr .= "<li class='page-item'> <a class='page-link' href='index.php?db=${selectDb}&daysSince=${daysSince}&page=" . "1" . "'>第一页</a></li>";
    $pageStr .= "<li class='page-item'> <a class='page-link' href='index.php?db=${selectDb}&daysSince=${daysSince}&page=" . ($page - 1) . "'>上一页</a></li>";
}

$pageStr .= "<li class='page-item'><span class='page-link disabled'>总共${totalPage}页</span></li>";

for ($i = $startPage; $i <= $endPage; $i++) {
    $pageStr .= "<li class='page-item'><a class='page-link' href='index.php?db=${selectDb}&daysSince=${daysSince}&page=" . $i . "'>" . $i . "</a></li>";
}

if ($page < $totalPage) {
    $pageStr .= "<li class='page-item'><a class='page-link' href='index.php?db=${selectDb}&daysSince=${daysSince}&page=" . ($page + 1) . "'>下一页</a></li>";
    $pageStr .= "<li class='page-item'><a class='page-link' href='index.php?db=${selectDb}&daysSince=${daysSince}&page=" . $totalPage . "'>最后页</a></li>";
}

$pageStr .= "</ul>";
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
<script language="javascript">
function toggleDesc(id){
    var sqlPre = document.getElementById(id);
    // var desc = document.getElementById('desc-' + id);
    sqlPre.style.display = sqlPre.style.display=="block" ? "none" : "block";
    // desc.style.display = desc.style.display=="block" ? "none" : "block";
}
</script>
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
            <tr>
            <th>抽象语句</th>
            <th>数据库</th>
            <th>用户名</th>
            <th>最近时间</th>
            <th>次数</th>
            <th>平均时间(秒)</th>
            <th>最小时间(秒)</th>
            <th>最大时间(秒)</th>
            </tr>
            <?php
            while ($row = mysqli_fetch_array($slowQueryListResult)) {
                echo "<tr style='cursor: pointer;' onclick=\"toggleDesc('${row['0']}')\">";
                echo "<td width='100px'>✚  " . substr("{$row['1']}", 0, 50)
                    . "</td>";
                echo "<td>{$row['2']}</td>";
                echo "<td>{$row['3']}</td>";
                echo "<td>{$row['4']}</td>";
                echo "<td>{$row['5']}</td>";
                echo "<td>{$row['8']}</td>";
                echo "<td>{$row['6']}</td>";
                echo "<td>{$row['7']}</td>";
                echo "</tr>";
                echo "<tr><td colspan='8' class='desc-td'><a id='${row['0']}' style='display:none;' href='explain.php?checksum={$row['0']}'>" . SqlFormatter::format($row['1']) . "</a></td></tr>";
                // <td colspan='4' style='padding: 0'><pre id='desc-${row['0']}' style='display:none;'>" . json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre></td>
            }
            ?>
            </table>
        </div>
    </div>
</div>

<?php echo $pageStr; ?>

<script>
    document.getElementById('db-select').value = document.getElementById('db-select').getAttribute('data-val')
    document.getElementById('daysSince-select').value = document.getElementById('daysSince-select').getAttribute('data-val')
</script>
</body>
</html>