<?php

namespace Salic;

use Salic\Settings\LangSettings;

require_once 'test.php';

function test_language_detection()
{
    echo "<b>Your header:</b> ".$_SERVER["HTTP_ACCEPT_LANGUAGE"];
    $lang_settings = LangSettings::get();

    $default = $lang_settings->default;
    $available = array_keys($lang_settings->available);

    require_once 'salic/LanguageDetection.php';

    $values = array(
        'de' => "de",
        'en' => "en",
        'de-DE' => "de",
        'DE-dE' => "de",
        'en-FUCKME' => "en",
        'en-GB' => "en",
        'en-US' => "en",
        'de,en-US;q=0.7,en;q=0.3' => "de",
        'de;q=0.1,en-US;q=0.7,en;q=0.3' => "en",
        'de-DE,en-US;q=0.7,en;q=0.3' => "de",
        'de-DE;q=0.1,en-US;q=0.7,en;q=0.3' => "en",
        'pt,en-US;q=0.7,en;q=0.3' => "pt",
        'pt;q=0.1,en-US;q=0.7,en;q=0.3' => "en",
        'pt-PT,en-US;q=0.7,en;q=0.3' => "pt",
        'pt-PT;q=0.1,en-US;q=0.7,en;q=0.3' => "en",
    );

    echo "<table border='1'><tr> <th>Header</th> <th>Parsed</th> <th>Result</th> <th>Expected</th> </tr>";
    foreach ($values as $val => $expected) {
        echo "<tr><td><i>$val</i></td> <td>";
        $result = parseDefaultLanguage($val, $available, $default, true);
        $bg = $result == $expected ? "green" : "red";
        echo "</td> <td bgcolor='$bg'><b>$result</b></td>";
        echo "<td>$expected</td> </tr>";
    }
    echo "</table>";
}

test_language_detection();

?>