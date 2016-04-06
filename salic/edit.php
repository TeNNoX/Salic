<?php
namespace Salic;

use Salic\Settings\LangSettings;

require_once('Salic.php');

if (!Utils::validAuthentication()) {
    exit; // Utils should call exit(), but just to be sure...
}

$lang = strtolower(@$_GET['lang']);
if(empty($lang)) {
    $lang = LangSettings::get()->default;
}
if (!LangSettings::get()->exists($lang)) {
    echo "Invalid Language: '$lang'";
    exit;
}

$salic = new SalicMng($lang);
$salic->initTwig();

$page = strtolower(@$_GET['page']);
if (empty($page)) { // default page
    try {
        $page = Settings\NavSettings::get()->homepage;
    } catch (\Exception $e) {
        Utils::dieWithError($e, 'Homepage determination', $salic);
        exit;
    }
}

$salic->renderPage($page);

?>