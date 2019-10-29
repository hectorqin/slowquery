<?php

if (!function_exists('env')) {
    function env($name, $default = null)
    {
        static $env = null;
        if (is_null($env)) {
            $env = is_file(".env") ? parse_ini_file(".env") : (is_file("../.env") ? parse_ini_file("../.env") : []);
        }
        return isset($env[$name]) ? $env[$name] : $default;
    }
}

if (!function_exists('db')) {
    function db()
    {
        static $connection = null;
        if (is_null($connection)) {
            $connection = mysqli_connect(env('SLOWQUERY_DB_HOST'), env('SLOWQUERY_DB_USER'), env('SLOWQUERY_DB_PASSWORD'), env('SLOWQUERY_DB_DATABASE'), env('SLOWQUERY_DB_PORT')) or die("数据库链接错误" . mysqli_connect_error());
            mysqli_query($connection, "set names utf8");
        }
        return $connection;
    }
}

if (!function_exists('page')) {
    function page($totalCount, $pageSize = 10, $params = [], $pageName='page')
    {
        $totalPage   = ceil($totalCount / $pageSize); //计算出总页数
        $maxPageCount = 10;
        $buffCount    = 2;
        $startPage    = 1;

        if ($totalPage < $maxPageCount) {
            $maxPageCount = $totalPage;
        }

        $currentPage = isset($_GET[$pageName]) ? $_GET[$pageName] : (isset($_POST[$pageName]) ? $_POST[$pageName] : 1);
        $params = empty($params) ? (!empty($_POST) ? $_POST : $_GET) : $params;
        if ($currentPage < $buffCount) {
            $startPage = 1;
        } else if ($currentPage >= $buffCount and $currentPage < $totalPage - $maxPageCount) {
            $startPage = $currentPage - $buffCount + 1;
        } else {
            $startPage = $totalPage - $maxPageCount + 1;
        }

        $endPage = $startPage + $maxPageCount - 1;

        $pageStr = "";

        $pageLink = $_SERVER['PHP_SELF'];
        $pageLink .= empty($params) ? "?${pageName}" : ('?' . http_build_query($params) . "&${pageName}");

        $pageStr .= "<ul class='pagination'>";
        if ($currentPage > 1) {
            $pageStr .= "<li class='page-item'> <a class='page-link' href='${pageLink}=1" . "'>第一页</a></li>";
            $pageStr .= "<li class='page-item'> <a class='page-link' href='${pageLink}=" . ($currentPage - 1) . "'>上一页</a></li>";
        }

        $pageStr .= "<li class='page-item'><span class='page-link disabled'>总共${totalPage}页</span></li>";

        for ($i = $startPage; $i <= $endPage; $i++) {
            $pageStr .= "<li class='page-item'><a class='page-link' href='${pageLink}=" . $i . "'>" . $i . "</a></li>";
        }

        if ($currentPage < $totalPage) {
            $pageStr .= "<li class='page-item'><a class='page-link' href='${pageLink}=" . ($currentPage + 1) . "'>下一页</a></li>";
            $pageStr .= "<li class='page-item'><a class='page-link' href='${pageLink}=" . $totalPage . "'>最后页</a></li>";
        }

        $pageStr .= "</ul>";
        return [$pageStr, ($currentPage - 1) * $pageSize . "," . $pageSize];
    }
}

if (!function_exists('fixTimeZone')) {
    function fixTimeZone($date, $format="Y-m-d H:i:s")
    {
        static $timeZoneFix = null;
        if (is_null($timeZoneFix)) {
            $timeZoneFix = env('TIME_ZONE_FIX');
        }
        return date($format, strtotime($date) + $timeZoneFix * 3600);
    }
}