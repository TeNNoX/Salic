<?php
namespace salic;
require_once('Salic.php');


$page = strtolower($_GET['page']);

// LANGUAGE SELECTION
$lang_settings = Settings::getLangSettings();
if (array_key_exists('lang', $_GET)) {
    $lang = $_GET['lang'];
    if (!array_key_exists($lang, $lang_settings['available'])) {
        echo "Invalid Language: $lang"; //TODO: ignore invalid language?
        exit;
    }
} else {
    $lang = Utils::getDefaultLanguageFromHeader();  // language is not given, redirect to the best one
    echo "Redirect: <a href='/$lang/$page'>/$lang/$page</a>";
    http_response_code(303); //TODO: how to redirect properly for localisation
    header("Location:/$lang/$page");
    exit;
}

$salic = new Salic($lang);

if (empty($page)) {
    $page = $salic->getPageSettings()['default'];
}

$salic->initTwig();
$salic->renderPage($page);

?>