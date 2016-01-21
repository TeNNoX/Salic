<?php
namespace Salic;
use Salic\Settings\LangSettings;

require_once('Salic.php');

$page = $_GET['page'];

$lang = strtolower($_GET['lang']);
if (!LangSettings::get()->exists($lang)) {
    echo "Invalid Language: $lang"; //TODO:
    exit;
}

$salic = new SalicMng($lang);

if (empty($page)) {
    die('pagekey not given');
}

$salic->savePage($page);

?>