<?php

/*
 * 这个文件用来作为测试的，也就是定时任务要访问的目标路径
 * 本文件通过在本地写一个log文件来记录执行的情况
 */

$msg = "执行时间：".date('Y-m-d H:i:s')."\n";
file_put_contents("test.log",$msg,FILE_APPEND);