一、使用方法

 * desc:这是定时任务规范：
 * 1.一个任务一个进程（一个文件完成一个任务）
 * 2.定时任务文件里一般不写业务逻辑，它只是触发业务逻辑，调用servic里的业务逻辑
 * 3.为了后期定时任务多，统一管理，定时任务统一放到job目录下，定时任务文件统一命名：xxxJob.php
 * 4.$needLocked = true;  //这个变量很重要,你的任务处理的业务是允许多个进程同时处理：TRUE 不允许；false 允许）
 * 4.一些耗时的业务逻辑，都建议放到计划任务里

# 服务器上的定时任务 使用方法：
* * * * * php /xjlc/php/fzcs.xiaojilicai.com/app/schedules/cli.php xxxJob  para1  para2  >>/tmp/xxxJob.log &




