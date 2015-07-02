<?php

/*
 * @作者：否子戈
 *
 * 用来实现本地的PHP定时任务，无需借助Liunx CronTab，而是借助生成本地文件，可以实现定时任务
 *
 */

namespace PHPCron;
date_default_timezone_set('PRC');

class Cron {
  private $schedules_dir;
  private $cron_url;

  function __construct() {
    $this->schedules_dir = realpath(dirname(__FILE__).'/../schedules');
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
    $file = $this->schedules_dir.'/'.$name.'.php';
    if(!file_exists($file)) return false;
    return true;
  }

  // 获取某个任务的信息
  function get($name = null) {
    // 为空是，获取任务列表
    if(empty($name)) {
      $schedules_files = $this->_scandir($this->schedules_dir);
      $schedules = array();
      if(!empty($schedules_files)) foreach($schedules_files as $file) {
        if($file == '.' || $file == '..') { continue; }
        $schedules[] = basename($file,'.php');
      }
      return $schedules;
    }
    // 否则获取对应的任务信息
    $file = $this->schedules_dir.'/'.$name.'.php';
    if(!file_exists($file)) return false; // 不存在该任务时，返回错误
    $file_content = file($file);
    $schedule = unserialize($file_content[1]);
    return $schedule;
  }

  // 添加一个任务（添加到列表中，而不是马上执行）
  function add($name,$microtime,$interval,$url) {
    if($this->exists($name)) return false; // 如果已经存在该任务了，就只能使用update，而不能使用add
    return $this->update($name,$microtime,$interval,$url);
  }

  // 更新任务，当任务不存在时，添加这个任务
  function update($name,$microtime,$interval,$url) {
    $file = $this->schedules_dir.'/'.$name.'.php';
    $gmt_time = microtime(true);
    if(file_exists($file)) {
      if(filemtime($file) > $gmt_time - 1) return 0; // 如果文件被极速写入，为了防止文件被同时更改，则返回错误
        $schedule = $this->get($name);
    }
    else {
      $schedule = array('name' => $name);
    }
    $schedule['next_run_time'] = $microtime;
    $schedule['interval'] = $interval;
    $schedule['url'] = $url;
    $schedule = serialize($schedule);
    // 创建任务信息文件
    if($handle = @fopen($file, "w")) {
      fwrite($handle,'<?php exit(); ?>'."\r\n");
      fwrite($handle,$schedule);
      fclose($handle);
    }
    else {
      return null; // 用null表示写入失败
    }
    return true;
  }

  // 删除一个任务
  function delete($name) {
    $file = $this->schedules_dir.'/'.$name.'.php';
    @unlink($file);
  }

  // 立即执行一个任务，执行时，就会更新任务信息
  function run($name) {
    $file = $this->schedules_dir.'/'.$name.'.php';
    if(!file_exists($file)) return false; // 不存在该任务时，返回错误
    $gmt_time = microtime(true);
    if(filemtime($file) > $gmt_time - 1) return 0; // 如果文件被极速写入，为了防止文件被同时更改，则返回错误
    $schedule = $this->get($name);
    // 执行url
    $url = $schedule['url'];
    list($error_code,$error_msg) = $this->_sock($url);
    // 记录这次执行的情况
    $msg = date('Y-m-d H:i:s')." 执行任务：$name 结果：$error_code $error_msg\n";
    $this->_log($msg);
    // 更新schedule
    $schedule['last_run_time'] = $schedule['next_run_time'];
    $schedule['next_run_time'] += $schedule['interval'];
    $schedule['status'] = 1;
    $schedule = serialize($schedule);
    if($handle = @fopen($file, "w")) {
      fwrite($handle,'<?php exit(); ?>'."\r\n");
      fwrite($handle,$schedule);
      fclose($handle);
    }
    else {
      return null; // 用null表示写入失败
    }
    return true;
  }

  // 定时执行一个任务，通过sleep实现开始定时执行
  function set($name) {
    // 执行中的任务就不能再激活了
    $schedule = $Cron->get($name);
    if(isset($schedule['status']) && $schedule['status'] == 1) {
      echo 'schedule is running...';
      exit();
    }
    // 通过远程访问cron.php
    $url = $this->cron_url.'?cron='.$name;
    list($error_code,$error_msg) = $this->_sock($url);
    $msg = date('Y-m-d H:i:s')." 激活任务：$name 结果：$error_code $error_msg\n";
    $this->_log($msg);
  }

  // 检查定时任务，如果某个任务没有执行，就在检查的时候执行
  function check() {
    $url = $this->cron_url;
    list($error_code,$error_msg) = $this->_sock($url);
    $msg = date('Y-m-d H:i:s')." 检查任务：$name 结果：$error_code $error_msg\n";
    $this->_log($msg);
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
    $file = dirname(__FILE__).'/cron.log';
    $fp = fopen($file,'a');
    fwrite($fp,$msg);
    fclose($fp);
  }

  /*
   * 私有函数
   */
  // 浏览目录
  private function _scandir($dir) {
    if(function_exists('scandir')) {
      return scandir($dir);
    }
    else {
      $handle = @opendir($dir);
      $arr = array();
      while(($arr[] = @readdir($handle)) !== false) {}
      @closedir($handle);
      $arr = array_filter($arr);
      return $arr;
    }
  }
}