<?php
namespace salic;

require_once('Salic.php');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if(strpos($path, '/edit') === 0) {
    $page = substr($path, 6); // remove the '/edit/'
    $salic = new SalicMng();


    if(empty($page)) { // main edit page
        $salic->initTwig();
        $salic->renderBackend();
        exit;
    }

    if(strpos($page, '/save') !== false) {
        $salic->savePage(substr($page, 0, strlen($page)-5)); // remove the '/save'
        die("success");
    }
} else {
    $page = substr($path, 1);
    $salic = new Salic();
}

if(empty($page)) {
    reset($salic->pages);
    $page = key($salic->pages); // just use the first page as the default page, TODO: configurable default page?
}

$salic->initTwig();
$salic->renderPage($page);

?>