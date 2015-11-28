<?php
namespace salic;

require_once('salic/Salic.php');

$salic = new SalicMng();
$salic->init();

$pagekey = isset($_GET['page']) ? @$_GET['page'] : 'home';

$salic->renderPage($pagekey);

?>