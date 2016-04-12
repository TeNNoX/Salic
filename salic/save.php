<?php
namespace Salic;

use Salic\Settings\LangSettings;

require_once('Salic.php');

//TODO:? disable error_reporting

if (!Utils::validAuthentication()) {
    exit; // Utils should call exit(), but just to be sure...
}

$lang = strtolower($_GET['lang']);
if (!LangSettings::get()->exists($lang)) {
    die('{"success": false, "error": "APIException - Invalid Language: ' . $lang . '"}');
}

$salic = new SalicMng($lang);

$page = strtolower($_GET['page']);
if (empty($page)) {
    die('{"success": false, "error": "APIException - No pagekey given"}');
}
if(!Validator::checkPageKey($page)) {
    die('{"success": false, "error": "APIException - Invalid pagekey"}');
}

$salic->savePage($page);

?>