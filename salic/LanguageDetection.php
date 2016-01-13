<?php
#########################################################
# Copyright © 2008 Darrin Yeager                        #
# https://www.dyeager.org/                              #
# Licensed under BSD license.                           #
#   https://www.dyeager.org/downloads/license-bsd.txt   #
#########################################################
# ! MODIFIED to only choose from available languages    #
#########################################################

function getDefaultLanguage($available, $default = 'en')
{
    if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
        return parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"], $available);
    else
        return parseDefaultLanguage(NULL, $available, $default);
}

function parseDefaultLanguage($http_accept, $available, $deflang = 'en', $debugPrint = false)
{
    if (isset($http_accept) && strlen($http_accept) > 1 && sizeof($available) > 0) {
        if (sizeof($available) == 1) // no point in detection, if there's only one available...
            return strtolower($available[0]);

        // Split possible languages into array
        $x = explode(",", $http_accept);
        $lang = array();
        foreach ($x as $val) {
            // check for q-value and create associative array. No q-value means 1 by rule
            if (preg_match("/(.*);q=([0-1]{0,1}.\d{0,4})/i", $val, $matches))
                $lang[$matches[1]] = (float)$matches[2];
            else
                $lang[$val] = 1.0;
        }

        // return default language (highest q-value)
        $qval = 0.0;
        if ($debugPrint)
            echo "<pre>" . var_export($lang, true) . "</pre>";

        foreach ($lang as $key => $value) {
            // ignore special identifiers (eg 'en-US'), TODO: language subtag matching
            if (preg_match("/^(\w+)-(\w+)/", $key, $matches)) {
                if ($debugPrint)
                    echo "reducing " . $key . " to " . $matches[1] . "...<br>";
                $key = $matches[1];
            }

            if (!in_array(strtolower($key), $available))
                continue; // skip if not available

            if ($value > $qval) {
                $qval = (float)$value;
                $deflang = $key;
            }
        }
    }
    return strtolower($deflang);
}

?>

