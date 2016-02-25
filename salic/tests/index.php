<?php

echo "<h1>Tests:</h1><ul>";

foreach (glob('test_*.php') as $filename) {
    if (!preg_match('/^test_(.+).php$/', $filename, $matches))
        echo "<li>preg error: $filename</li>";
    else {
        echo "<li><a href='$filename/'>" . $matches[1] . "</a>";
    }
}
foreach (glob('test_*.py') as $filename) {
    if (!preg_match('/^test_(.+).py$/', $filename, $matches))
        echo "<li>preg error: $filename</li>";
    else {
        echo "<li><a href='py_wrapper.php?file=" . $filename . "'>" . $matches[1] . " [PY]</a>";
    }
}

echo "</ul>";