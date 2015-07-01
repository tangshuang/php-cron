<?php

/*
 * 本文件用来fsockopen /cron/cron-check.php 通过后台检测是否有任务没有在预订的时间执行，如果遇到了错过的任务，就立即执行，但都是后台完成的
 * 使用时，把这段代码拷贝到你的项目中，并修改下方对应的变量
 // http://bbs.csdn.net/topics/390740758?page=1 非阻塞模式
 */

$host = $_SERVER['SERVER_NAME'];// cron.php的访问域名
$port = $_SERVER ['SERVER_PORT'];// cron.php的访问端口，一般是80端口
$path = '/cron/cron-check.php';// cron.php的访问路径
if($fp = @fsockopen($host,$port,$errno,$errstr,1)) {
  stream_set_blocking($fp,0);//开启了手册上说的非阻塞模式
  $header = "GET $path HTTP/1.1\r\n";
  $header.="Host: $host\r\n";
  $header.="Connection: Close\r\n\r\n";//长连接关闭
  fwrite($fp, $header);
  fclose($fp);
}