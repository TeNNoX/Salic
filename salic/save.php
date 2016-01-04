<?php
namespace salic;
require_once('Salic.php');

$page = $_GET['page'];

$lang_settings = Settings::getLangSettings();
$lang = strtolower($_GET['lang']);
if (!array_key_exists($lang, $lang_settings['available'])) {
    echo "Invalid Language: $lang"; //TODO:
    exit;
}

$salic = new SalicMng($lang);

if (empty($page)) {
    die('pagekey not given');
}

$salic->savePage($page);
die("success"); // if not stopped by an exception, print out the good news

?>