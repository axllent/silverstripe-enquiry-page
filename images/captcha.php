<?php
header('Content-type: image/jpeg');
$width = 60;
$height = 24;
$my_image = imagecreatetruecolor($width, $height);
imagefill($my_image, 0, 0, 0xFFFFFF);
$purple = imageColorAllocate($my_image, 200, 0, 255);
$black = imageColorAllocate($my_image, 255, 255, 255);
$green = imageColorAllocate($my_image, 22, 255, 2);
$random_colours = array($purple,$green,$black);
// add noise
for ($c = 0; $c < 150; $c++){
	$x = rand(0,$width-1);
	$y = rand(0,$height-1);
	imagesetpixel($my_image, $x, $y, $random_colours[array_rand($random_colours)]);
}
$x = rand(1,10);
$rand_string = rand(1000,9999);
$numbers = str_split($rand_string);
foreach ($numbers as $number){
	$y = rand(1,10);
	imagestring($my_image, 5, $x, $y, $number, 0x000000);
	$x = $x+12;
}
@session_start();
$_SESSION['customcaptcha'] = md5($rand_string.$_SERVER['REMOTE_ADDR']).'a4xn';
imagejpeg($my_image);
imagedestroy($my_image);