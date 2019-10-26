<?php
require 'config.php';
$reviewTable  = env('SLOWQUERY_DB_REVIEW_TABLE');
$historyTable = env('SLOWQUERY_DB_HISTORY_TABLE');
$con          = db();

$action = isset($_GET['action']) ? $_GET['action'] : 'top';

if ($action == 'top') {
    $mysqlResult = mysqli_query($con, "SELECT db_max,user_max,SUM(ts_cnt) AS top_count FROM
(SELECT h.db_max,h.user_max,SUM(h.ts_cnt) AS ts_cnt
FROM $reviewTable AS r JOIN $historyTable AS h
ON r.checksum=h.checksum
WHERE r.last_seen >= SUBDATE(NOW(),INTERVAL 14 DAY)
GROUP BY r.checksum) AS tmp
GROUP BY tmp.db_max");

    $result = [];

    while ($row = mysqli_fetch_array($mysqlResult, MYSQLI_ASSOC)) {
        $res            = new stdClass();
        $res->db_max    = $row['db_max'];
        $res->top_count = $row['top_count'];
        $result[]        = $res;
    }

    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else if ($action == 'graph') {
    /*
    $mysqlResult = mysqli_query($con,"SELECT r.checksum,r.fingerprint,h.db_max,h.user_max,r.last_seen,SUM(h.ts_cnt) AS ts_cnt,
    ROUND(MIN(h.Query_time_min),3) AS Query_time_min,ROUND(MAX(h.Query_time_max),3) AS Query_time_max,
    ROUND(SUM(h.Query_time_sum)/SUM(h.ts_cnt),3) AS Query_time_avg,r.sample
    FROM ${reviewTable} AS r JOIN ${historyTable} AS h
    ON r.checksum=h.checksum
    WHERE db_max = '${selectDb}' AND r.last_seen >= SUBDATE(NOW(),INTERVAL 14 DAY)
    GROUP BY r.checksum
    ORDER BY r.last_seen ASC,ts_cnt DESC");
     */
    session_start();

    if (!isset($_SESSION['selectDb'])) {
        exit('{}');
    }
    $selectDb = $_SESSION['selectDb'];

    $mysqlResult = mysqli_query($con, "SELECT ts_max,Query_time_max FROM ${historyTable}
    WHERE db_max = '${selectDb}' AND ts_max >= DATE_FORMAT(DATE_SUB(NOW(),INTERVAL 1 DAY),'%Y-%m-%d')");

    $result = [];

    while ($row = mysqli_fetch_array($mysqlResult, MYSQLI_ASSOC)) {
        $res                 = new stdClass();
        $res->ts_max         = $row['ts_max'];
        $res->Query_time_max = $row['Query_time_max'];
        $result[]             = $res;
    }

    echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
