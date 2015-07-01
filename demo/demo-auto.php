<?php

/*
 * 本程序用来fsockopen /cron/cron-auto.php 通过后台发送一个任务，使这个任务可以定时执行
 * 使用时，把这段代码拷贝到你的项目中，并修改下方对应的变量
 */

$name = $_GET['cron'];
$host = $_SERVER['SERVER_NAME'];// cron.php的访问域名
$port = $_SERVER ['SERVER_PORT'];// cron.php的访问端口，一般是80端口
$path = '/cron/cron-auto.php?cron='.$name;// cron.php的访问路径
if($fp = @fsockopen($host,$port,$errno,$errstr,1)) {
  stream_set_blocking($fp,0);//开启了手册上说的非阻塞模式
  $header = "GET $path HTTP/1.1\r\n";
  $header.="Host: $host\r\n";
  $header.="Connection: Close\r\n\r\n";//长连接关闭
  fwrite($fp, $header);
  fclose($fp);
}