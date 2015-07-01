<?php

require(dirname(__FILE__).'/Cron.Class.php');

use PHPCron\Cron;
$Cron = new Cron();
$gmt_time = microtime(true);

ignore_user_abort(true);
set_time_limit(0);

$schedules = $Cron->get();
if(!empty($schedules)) foreach($schedules as $shd) {
  // 如果当前时间比该任务的下一次执行时间还要大，说明定时任务失败，通过该被动执行来完成定时任务
  if($gmt_time > $shd['next_run_time']) {
    $Cron->run($shd['name']);
    break;
  }
}