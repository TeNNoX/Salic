<?php
namespace salic;
require_once('Salic.php');

$page = $_GET['page'];
$salic = new Salic();

if(empty($page)) {
    reset($salic->pages); // reset array pointer, to be safe
    $page = key($salic->pages); // just use the first page as the default page, TODO: configurable default page?
}

$salic->initTwig();
$salic->renderPage($page);

?>