<?php
ini_set('memory_limit', '512M');
set_time_limit(0);

include("class/smartcrop-class.php");

$cv=new SmartCrop();

$cv->load("file/bbb.jpg");
$cv->skinDetect(false);
$cv->return_image();

?>