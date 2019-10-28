# Slowquery - MySQL慢日志图形工具

参考了开源工具Anemometer图形展示思路，并且把小米Soar工具集成进去，开发在页面上点击慢SQL，就会自动反馈优化建议，从而降低DBA人肉成本，同时也支持自动发送邮件报警功能。

agent客户端慢日志采集分析是结合Percona pt-query-digest工具来实现。

需要安装的步骤如下：

    1、percona-toolkit工具的安装

    2、php web mysql环境的搭建

    # yum install httpd mysql php php-mysql -y

    3、安装Slowquery并配置

    4、导入慢查询日志

    5、访问界面，查看慢查询

    6、配置邮件报警

![image](https://github.com/hcymysql/slowquery/blob/master/image/1.png)
![image](https://github.com/hcymysql/slowquery/blob/master/image/2.png)

工具搭建配置

1、移动到web目录

    ``` bash
    mv  slowquery  /var/www/html/
    ```

2、进入到slowquery目录下

导入install.sql表结构文件到你的运维管理机MySQL里。（注：dbinfo表是保存生产MySQL主库的配置信息。）

    ``` bash
    mysql -uroot -p123456 sql_db < ./install.sql
    ```

录入你要监控的MySQL主库配置信息

    ``` bash
    mysql> INSERT INTO sql_db.dbinfo VALUES (1,'192.168.148.101','test','admin','123456',3306);
    ```

3、修改环境配置文件 .env，将里面的配置改成你的运维管理机MySQL的地址（用户权限最好是管理员）

4、把 agent/slowquery_analysis.sh 脚本拷贝到生产MySQL主库上做慢日志分析推送，并修改里面的配置信息(或者带上环境配置文件)

5、生产MySQL服务器添加定时任务（10分钟一次）

    ``` bash
    */10 * * * * /bin/bash /usr/local/bin/slowquery_analysis.sh > /dev/null 2>&1
    ```

6、运维服务器添加邮件推送任务

    ``` bash
    0 */3 * * * cd /var/www/html/slowquery/alarm_mail;/usr/bin/php  /var/www/html/slowquery/alarm_mail/sendmail.php > /dev/null 2>&1
    ```