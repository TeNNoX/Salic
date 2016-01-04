<?php
namespace salic;
require_once('Salic.php');


$page = $_GET['page'];

// LANGUAGE SELECTION
$lang_settings = Settings::getLangSettings();
if (array_key_exists('lang', $_GET)) {
    $lang = $_GET['lang'];
    if (!array_key_exists($lang, $lang_settings['available'])) {
        echo "Invalid Language: $lang"; //TODO:
    }
} else {
    $lang = Utils::getDefaultLanguageFromHeader();
    if ($lang != $lang_settings['default']) { // if a better language is available, redirect to it
        echo "Redirect: <a href='/$lang/$page'>/$lang/$page</a>";
        http_response_code(303); //TODO: how to redirect properly for localisation
        header("Location:/$lang/$page");
        exit;
    }
}

$salic = new Salic($lang);

if (empty($page)) {
    reset($salic->pages); // reset array pointer, to be safe
    $page = key($salic->pages); // just use the first page as the default page, TODO: configurable default page?
}

$salic->initTwig();
$salic->renderPage($page);

?>