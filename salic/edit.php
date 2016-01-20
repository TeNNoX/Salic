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
if (empty($page)) { // default page
    try {
        $page = Settings::getNavSettings()['homepage'];
    } catch (\Exception $e) {
        $salic->renderError($e, "selecting homepage");
    }
}

$salic->renderPage($page);

?>