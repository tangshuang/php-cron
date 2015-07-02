<?php

/*
 * @否子戈
 * 本文件用来演示如何添加并定时一个任务
 */

// 先引入和声明Crone类
//define('CRON_URL','http://'.$_SERVER['SERVER_NAME'].'/php-cron/cron/cron.php'); // 这个定义用来告诉CRON定时文件的访问路径，如果你是将php-cron放在根目录下的话，其实是可以不用声明
require(dirname(__FILE__).'/../cron/Cron.Class.php');
use PHPCron\Cron;
$Cron = new Cron();

// $name是这个定时任务的名称，一但添加这个任务，就会在../schedules目录中生成一个$name.php文件用来记录这个任务的执行时间、要访问的url
$name = 'mycron';

// 先增加一个定时任务
$Cron->update($name,microtime(true) + 30,60,'http://'.$_SERVER['SERVER_NAME'].'/php-cron/demo/test.php');

/*
 * demo目录下的test.php是一个用来测试定时任务是否执行了的文件，每一次定时任务执行时，都会在test.log文件末尾添加执行任务的信息，你可以通过查看test.log来看执行的情况
 * 要关闭该定时任务，只需要删除../schedules目录中对应的文件即可，没有文件，下一次定时任务时就会暂停
 */

// 然后立即定时任务
//$Cron->run($name);

// 等待1秒，让文件写入后再执行
sleep(1);

// 激活定时任务
$Cron->set($name);

// 删除这个任务，需将上面两个动作注释掉
//$Cron->delete($name);