<?php
namespace Salic;
use Salic\Settings\NavSettings;

require_once('Salic.php');


$page = strtolower($_GET['page']);

// LANGUAGE SELECTION
try {
    if (array_key_exists('lang', $_GET)) {
        $lang = $_GET['lang'];
        if (!Settings\LangSettings::get()->exists($lang)) {
            echo "Invalid Language: $lang"; //TODO: ignore invalid language?
            exit;
        }
    } else {
        $lang = Utils::getDefaultLanguageFromHeader();  // language is not given, redirect to the best one
        echo "Redirect: <a href='/$lang/$page'>/$lang/$page</a>";
        http_response_code(302); //TODO: how to redirect properly for localisation
        header("Location:/$lang/$page");
        exit;
    }
} catch (\Exception $e) {
    Utils::dieWithError($e, 'Language determination');
    exit;
}

$salic = new Salic($lang);
$salic->initTwig();

if (empty($page)) {
    try {
        $page = NavSettings::get()->homepage;
    } catch (\Exception $e) {
        Utils::dieWithError($e, 'Homepage determination', $salic);
    }
}

$salic->renderPage($page);

?>