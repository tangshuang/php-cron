<?php

require(dirname(__FILE__).'/Cron.Class.php');

use PHPCron\Cron;
$Cron = new Cron();

$name = $_GET['cron'];
$Cron->set($name);