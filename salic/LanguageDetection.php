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

function parseDefaultLanguage($http_accept, $available, $deflang = 'en')
{
    echo "http: $http_accept, avail: " . var_export($available, true) . ", default: $deflang\n<br>";
    if (isset($http_accept) && strlen($http_accept) > 1 && sizeof($available) > 0) {
        if (sizeof($available) == 1)
            return strtolower($available[0]);

        # Split possible languages into array
        $x = explode(",", $http_accept);
        $lang = array();
        foreach ($x as $val) {
            #check for q-value and create associative array. No q-value means 1 by rule
            if (preg_match("/(.*);q=([0-1]{0,1}.\d{0,4})/i", $val, $matches))
                $lang[$matches[1]] = (float)$matches[2];
            else
                $lang[$val] = 1.0;
        }

        #return default language (highest q-value)
        $qval = 0.0;
        foreach ($lang as $key => $value) {
            if (!in_array(strtolower($key), $available)) // skip if not available
                continue;

            if ($value > $qval) {
                $qval = (float)$value;
                $deflang = $key;
            }
        }
    }
    echo "RESULT: ".$deflang."\n<br>";
    return strtolower($deflang);
}

?>

