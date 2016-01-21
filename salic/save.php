<?php
namespace Salic;

use Salic\Settings\LangSettings;

require_once('Salic.php');

if (!Utils::validAuthentication()) {
    exit; // Utils should call exit(), but just to be sure...
}

$lang = strtolower($_GET['lang']);
if (!LangSettings::get()->exists($lang)) {
    echo "Invalid Language: $lang"; //TODO:
    exit;
}

$salic = new SalicMng($lang);

$page = strtolower($_GET['page']);
if (empty($page)) {
    die('pagekey not given');
}

$salic->savePage($page);

?>