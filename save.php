<?php

namespace salic;

try {
    if (!array_key_exists('pagekey', $_POST)) {
        http_response_code(400);
        die("Error: missing pagekey in POST data");
    }
    if (!array_key_exists('regions', $_POST)) {
        http_response_code(400);
        die("Error: missing regions in POST data");
    }
    $pagekey = $_POST['pagekey'];
    $regions = $_POST['regions'];

    require_once('salic/Salic.php');

    $salic = new Salic();
    $salic->loadPages();

    if (!array_key_exists($pagekey, $salic->pages)) {
        //TODO: error handling
        http_response_code(400);
        die("Error: Unknown pagekey '$pagekey'");
    }

    $salic->savePage($pagekey, $regions);
    echo "success";

} catch (\Exception $e) {
    die("Exception while trying to save:<br>\n$e");
}
?>