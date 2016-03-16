<?php
ini_set('memory_limit', '512M');
set_time_limit(0);

include("class/smartcrop-class.php");

$cv=new SmartCrop();

$cv->load("file/ddd.jpg");
$cv->edgeDetect(false);
$cv->update();
$cv->skinDetect(false);
$cv->update();
$cv->saturationDetect(false);

$cv->return_image();

?>