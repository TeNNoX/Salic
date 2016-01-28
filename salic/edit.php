<?php
namespace Salic;
use Salic\Settings\LangSettings;

require_once('Salic.php');

if(!Utils::validAuthentication()) {
    exit; // Utils should call exit(), but just to be sure...
}

// for just /edit/, render the backend.
if (!array_key_exists('page', $_GET) && !array_key_exists('lang', $_GET)) {
    // main backend page
    $salic = new SalicMng('en');
    $salic->initTwig();
    $salic->renderBackend();
    exit;
}

$lang = strtolower(@$_GET['lang']);
if (!LangSettings::get()->exists($lang)) {
    echo "Invalid Language: '$lang'";
    exit;
}

$salic = new SalicMng($lang);
$salic->initTwig();

$page = strtolower($_GET['page']);
if (empty($page)) { // default page
    try {
        $page = Settings\NavSettings::get()->homepage;
    } catch (\Exception $e) {
        $salic->renderError($e, "selecting homepage");
    }
}

$salic->renderPage($page);

?>