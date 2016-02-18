<?php

echo "<h1>Tests:</h1><ul>";
foreach (glob('test_*.php') as $filename) {
    if (preg_match('/^test_(.+).php$/', $filename, $matches) == -1)
        echo "<li>preg error: $filename</li>";
    else {
        echo "<li><a href='" . $matches[1] . "/'>" . $matches[1] . "</a>";
    }
}
echo "</ul>";