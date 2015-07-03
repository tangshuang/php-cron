<?php

require(dirname(__FILE__).'/Cron.FILE.Class.php');

ignore_user_abort(true);
set_time_limit(0);

use PHPCron\FILE\Cron as CronFILE;
$Cron = new CronFILE();

// 如果不存在cron参数，则对所有任务进行检查，如果发现某些任务没有执行，那么现在执行它
if(!isset($_GET['cron'])) {
  $gmt_time = microtime(true);
  $schedules = $Cron->get();
  if(!empty($schedules)) foreach($schedules as $shd) {
    // 如果当前时间比该任务的下一次执行时间还要大，说明定时任务失败，通过该被动执行来完成定时任务
    if($gmt_time > $shd['next_run_time']) {
      $Cron->run($shd['name']);
      break;
    }
  }
  exit();
}

// 如果存在cron参数，就以它作为任务名称，去激活该任务
$name = $_GET['cron'];
$loop = 0;
// 如果多次访问这个任务，这个任务是否会被激活多次呢
$schedule = $Cron->get($name);
if(isset($schedule['status']) && $schedule['status'] == 1) {
  echo 'schedule is running...';
  exit();
}

do {
  $schedule = $Cron->get($name);
  if(!$schedule) break;// 如果在任务执行过程中，发现这个任务已经被删除了，则停止执行
  $gmt_time = microtime(true);
  $loop = $loop ? $loop : $schedule['next_run_time'] - $gmt_time;
  $loop = $loop > 0 ? $loop : 0;
  if(!$loop) break; // 如果循环的间隔为零，则停止
  sleep($loop);
  $Cron->run($name);
  $loop = $schedule['interval'];
} while(true);