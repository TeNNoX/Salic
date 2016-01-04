<?php
namespace salic;
require_once('Salic.php');

$page = strtolower($_GET['page']);
$salic = new SalicMng();
$salic->initTwig();

if (empty($page)) { // main edit page
    $salic->renderBackend();
    exit;
}

$salic->renderPage($page);

?>