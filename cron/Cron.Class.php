<?php

namespace PHPCron;
session_start();

class Cron {
  public $schedules_dir;
  public $schedules;

  function __contstrut() {
    $this->schedules_dir = dirname(__FILE__).'/../.schedules/';
    if(isset($_SESSION['_schedules'])) {
      $this->schedules = $_SESSION['_schedules'];
    }
    else {
      $this->schedules = get_schedules();
      $_SESSION['_schedules'] = $this->schedules;
    }
  }

  // 获取某个任务的信息
  function get($name == null) {
    if(empty($name)) {
      $schedules_files = $this->_scandir($this->schedules_dir);
      $schedules = array();
      if(!empty($schedules_files)) foreach($schedules_files as $file) {
        if($file == '.' || $file == '..') { continue; }
        $schedules[] = basename($file,'.php');
      }
      return $schedules;
    }
    if(!in_array($name,$this->schedules)) return false; // 不存在该任务时，返回错误
    $file_content = file($this->schedules_dir.$name.'.php');
    $schedule = unserialize($file_content[1]);
    return $schedule;
  }

  // 添加一个任务（添加到列表中，而不是马上执行）
  function add($name,$microtime,$interval,$action) {
    if(in_array($name,$this->schedules)) return false; // 如果已经存在该任务了，就只能使用update，而不能使用add
    $this->update_schedule($name,$microtime,$interval,$action);
  }

  // 更新任务
  function update($name,$microtime,$interval,$action) {
    $file = $this->schedules_dir.$name.'.php';
    $gmt_time = microtime(true);
    if(filemtime($file) > $gmt_time - 1) return 0; // 如果文件被极速写入，为了防止文件被同时更改，则返回错误
    $schedule = $this->get_schedule($name);
    $schedule['next_run_time'] = $microtime;
    $schedule['interval'] = $interval;
    $schedule['action'] = $action;
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

  // 删除一个任务
  function delete($name) {
    $file = $this->schedules_dir.$name.'.php';
    @unlink($file);
  }

  // 立即执行一个任务，执行时，就会更新任务信息
  function run($name) {
    if(!in_array($name,$this->schedules)) return false; // 不存在该任务时，返回错误
    $file = $this->schedules_dir.$name.'.php';
    $gmt_time = microtime(true);
    if(filemtime($file) > $gmt_time - 1) return 0; // 如果文件被极速写入，为了防止文件被同时更改，则返回错误
    $schedule = get_schedule($name);
    // 执行action
    $url = $schedule['action'];
    $host = parse_url($url,PHP_URL_HOST);
    $port = parse_url($url,PHP_URL_PORT);
    $scheme = parse_url($url,PHP_URL_SCHEME);
    $path = parse_url($url,PHP_URL_PATH);
    $query = parse_url($url,PHP_URL_QUERY);
    if($query) $path .= '?'.$query;
    if($scheme == 'https') {
      $host = 'ssl://'.$host;
    }
    if($fp = @fsockopen($host,$port,$errno,$errstr,1)) {
      stream_set_blocking($fp,0);//开启了手册上说的非阻塞模式
      $header = "GET $path HTTP/1.1\r\n";
      $header.="Host: $host\r\n";
      $header.="Connection: Close\r\n\r\n";//长连接关闭
      fwrite($fp, $header);
      fclose($fp);
    }
    // 更新schedule
    $schedule['last_run_time'] = $schedule['next_run_time'];
    $schedule['next_run_time'] += $schedule['interval'];
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
    ignore_user_abort(true);
    set_time_limit(0);
    $loop = 0;
    do {
      $schedule = $this->get($name);
      if(!$schedule) break;// 如果在任务执行过程中，发现这个任务已经被删除了，则停止执行
      $gmt_time = microtime(true);
      $loop = $loop ? $loop : $schedule['next_run_time'] - $gmt_time;
      $loop = $loop > 0 ? $loop : 0;
      if(!$loop) break; // 如果循环的间隔为零，则停止
      sleep($loop);
      $this->run($name);
      $loop = $schedule['interval'];
    } while(true);
  }

  /*
   * 私有函数
   */
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