<?php
namespace salic;
require_once('Salic.php');

if (!array_key_exists('page', $_GET) && !array_key_exists('lang', $_GET)) {
    // main backend page
    $salic = new SalicMng('en');
    $salic->initTwig();
    $salic->renderBackend();
    exit;
}

$lang_settings = Settings::getLangSettings();
$lang = strtolower(@$_GET['lang']);
if (!array_key_exists($lang, $lang_settings['available'])) {
    echo "Invalid Language: '$lang'";
    exit;
}

$salic = new SalicMng($lang);
$salic->initTwig();

$page = strtolower($_GET['page']);
if (!$page) { // default page
    reset($salic->pages); // reset array pointer, to be safe
    $page = key($salic->pages); // just use the first page as the default page, TODO: configurable default page?
}

$salic->renderPage($page);

?>