<?php
namespace salic;
require_once('Salic.php');

$page = $_GET['page'];
$salic = new SalicMng();

if (empty($page)) {
    die('pagekey not given');
}

$salic->savePage($page);
die("success"); // if not stopped by an exception, print out the good news

?>