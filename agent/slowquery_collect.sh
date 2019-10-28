#!/bin/bash

# 运维管理MySQL配置
SLOWQUERY_DB_HOST="127.0.0.1"
SLOWQUERY_DB_PORT="3306"
SLOWQUERY_DB_USER="admin"
SLOWQUERY_DB_PASSWORD="123456"
SLOWQUERY_DB_DATABASE="slowquery"
SLOWQUERY_DB_REVIEW_TABLE="mysql_slow_query_review"
SLOWQUERY_DB_HISTORY_TABLE="mysql_slow_query_review_history"

# 生产MySQL主库配置
MYSQL_CLIENT="/usr/local/mysql/bin/mysql"
MYSQL_HOST="127.0.0.1"
MYSQL_PORT="3306"
MYSQL_USER="admin"
MYSQL_PASSWORD="123456"

# 生产MySQL主库慢查询目录和慢查询执行时间（单位秒）
SLOWQUERY_DIR="/usr/local/mysql/mysql-slowlog"
SLOWQUERY_LONG_TIME=1

# 生产环境 pt-query-digest 脚本地址
PT_QUERY_DIGEST="/usr/bin/pt-query-digest"

# 生产MySQL主库server_id
MYSQL_SERVER_ID=1

# 使用环境变量覆盖
[ -f ".env" ] && . .env
[ -f "../.env" ] && . ../.env

# 查询当前慢日志文件路径
slowquery_file=`$MYSQL_CLIENT -h$MYSQL_HOST -P$MYSQL_PORT -u$MYSQL_USER -p$MYSQL_PASSWORD  -e "show variables like 'slow_query_log_file'"|grep log|awk '{print $2}'`

if [ $? -ne 0 ];
then
	echo "查询慢日志路径失败"
	exit 1
fi

#collect mysql slowquery log into slowquery database
$PT_QUERY_DIGEST --user=$SLOWQUERY_DB_USER --password=$SLOWQUERY_DB_PASSWORD --port=$SLOWQUERY_DB_PORT --review h=$SLOWQUERY_DB_HOST,D=$SLOWQUERY_DB_DATABASE,t=$SLOWQUERY_DB_REVIEW_TABLE  --history h=$SLOWQUERY_DB_HOST,D=$SLOWQUERY_DB_DATABASE,t=$SLOWQUERY_DB_HISTORY_TABLE  --no-report --limit=100% --filter=" \$event->{add_column} = length(\$event->{arg}) and \$event->{serverid}=$MYSQL_SERVER_ID " $slowquery_file > /tmp/slowquery_analysis.log

##### set a new slow query log ###########
new_slowquery_file=$SLOWQUERY_DIR/slowquery_$(date +%Y%m%d%H%M).log

#config mysql slowquery
$MYSQL_CLIENT -h$MYSQL_HOST -P$MYSQL_PORT -u$MYSQL_USER -p$MYSQL_PASSWORD -e "set global slow_query_log = 1;set global long_query_time = $SLOWQUERY_LONG_TIME;set global slow_query_log_file = '$new_slowquery_file';"

#delete log before 7 days
# cd $SLOWQUERY_DIR
# /usr/bin/find ./ -name 'slowquery_*' -mtime +7|xargs rm -f ;