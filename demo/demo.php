<?php

require(dirname(__FILE__).'/../cron/Cron.Class.php');
use PHPCron\Cron;
$Cron = new Cron();
$name = time();

// 先增加一个定时任务
$Cron->add($name,microtime(true) + 120,60*10,'http://127.0.0.1/php-cron/demo/test.php');

// 然后立即定时任务
$Cron->set($name);

// 删除这个任务，需将上面两个动作注释掉
//$Cron->delete($name);