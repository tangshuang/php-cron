<?php

namespace PHPCron\DB;
date_default_timezone_set('PRC');


class Cron {
  private $db;
  private $cron_url;

  function __construct($host,$user,$pass,$name) {
    // 创建数据库连接
    $this->db = mysql_connect($host,$user,$pass);
    mysql_select_db($name,$this->db);
    // 用来执行某个任务的cron.php文件url，一般要带上?cron=name才可以，它是和Cron:_sock()配合用的，如果不是放在根目录下，需要自己设定具体的访问路径
    if(defined('CRON_URL')) {
      $this->cron_url = CRON_URL;
    }
    else {
      $this->cron_url = 'http://'.$_SERVER['SERVER_NAME'].'/php-cron/cron/cron.php';
    }
  }

  // 判断任务是否存在
  function exists($name) {
  }

  // 获取某个任务的信息
  function get($name = null) {
  }

  // 添加一个任务（添加到列表中，而不是马上执行）
  function add($name,$microtime,$interval,$url) {
  }

  // 更新任务，当任务不存在时，添加这个任务
  function update($name,$microtime,$interval,$url) {
  }

  // 删除一个任务
  function delete($name) {
  }

  // 立即执行一个任务，执行时，就会更新任务信息
  function run($name) {
  }

  // 定时执行一个任务，通过sleep实现开始定时执行
  function set($name) {
  }

  // 检查定时任务，如果某个任务没有执行，就在检查的时候执行
  function check() {
  }

  /*
   * 公有函数，并非cron内含动作
   */
  // 远程请求（不获取内容）函数
  function _sock($url) {
    $host = parse_url($url,PHP_URL_HOST);
    $port = parse_url($url,PHP_URL_PORT);
	  $port = $port ? $port : 80;
    $scheme = parse_url($url,PHP_URL_SCHEME);
    $path = parse_url($url,PHP_URL_PATH);
    $query = parse_url($url,PHP_URL_QUERY);
    if($query) $path .= '?'.$query;
    if($scheme == 'https') {
      $host = 'ssl://'.$host;
    }
    if($fp = @fsockopen($host,$port,$error_code,$error_msg,5)) {
      stream_set_blocking($fp,0);//开启了手册上说的非阻塞模式
      $header = "GET $path HTTP/1.1\r\n";
      $header.="Host: $host\r\n";
      $header.="Connection: Close\r\n\r\n";//长连接关闭
      fwrite($fp, $header);
      fclose($fp);
    }
    return array($error_code,$error_msg);
  }
  // 记录log
  function _log($msg) {
  }

}