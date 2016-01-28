<?php

// let salic handle the rest ;)
require_once('salic/ImgSteward.php');

$img_path = $_GET['img_path'];
$width = array_key_exists('w', $_GET) ? $_GET['w'] : null;

ImgSteward::serve($img_path, $width);

?>