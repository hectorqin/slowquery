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
