# Slowquery - MySQL慢日志图形工具

参考了开源工具Anemometer图形展示思路，并且把小米Soar工具集成进去，开发在页面上点击慢SQL，就会自动反馈优化建议，从而降低DBA人肉成本，同时也支持自动发送邮件报警功能。

agent客户端慢日志采集分析是结合Percona pt-query-digest工具来实现。

## 截图预览

![image](https://github.com/hectorqin/slowquery/raw/master/statics/image/1.png)
![image](https://github.com/hectorqin/slowquery/raw/master/statics/image/2.png)
![image](https://github.com/hectorqin/slowquery/raw/master/statics/image/3.png)

## 安装步骤

    1、percona-toolkit工具的安装

    ``` bash
    sudo yum install -y https://repo.percona.com/yum/percona-release-latest.noarch.rpm
    percona-release enable-only tools release
    sudo yum install -y percona-toolkit
    ```

    2、php web mysql环境的搭建

    ``` bash
    yum install httpd mysql php php-mysql -y
    ```

    3、安装Slowquery并配置

    4、导入慢查询日志

    5、访问界面，查看慢查询

    6、配置邮件报警

## slowquery 配置步骤

1、移动到web目录

    ``` bash
    mv slowquery  /var/www/html/
    ```

2、进入到slowquery目录下

导入install.sql表结构文件到你的运维管理机MySQL里。（注：dbinfo表是保存生产MySQL主库的配置信息。）

    ``` bash
    mysql -uroot -p123456 slow_query < ./install.sql
    ```

录入你要监控的MySQL主库配置信息

    ``` bash
    mysql> INSERT INTO slow_query.dbinfo VALUES (1,'testdb','192.168.0.123','test','admin','123456',3306);
    ```

3、修改环境配置文件 .env，修改里面的相关配置

    ``` bash
    cp .env.example .env
    ```

4、把 agent/collect.sh 脚本拷贝到生产MySQL主库上做慢日志分析推送，并修改里面的配置信息(或者带上环境配置文件)

5、生产MySQL服务器添加定时任务（10分钟一次）

    ``` bash
    */10 * * * * /bin/bash /usr/local/bin/collect.sh > /dev/null 2>&1
    ```

6、运维服务器添加邮件推送任务

    ``` bash
    0 */3 * * * cd /var/www/html/slowquery;/usr/bin/php cron.php > /dev/null 2>&1
    ```
