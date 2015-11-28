<?php
use salic\Parser;

require_once('salic/init.php');

$result = Parser::parseFile('templates/index.html', array(
    'headline' => "Headline",
    'content' => "Test Content <br>Hi, <b>there</b>!",
    'staticlist' => array('Static 1', "Static 2"),
    'dynamiclist' => array(),
));

echo "<br><br>Result:<table border='1'><tr><td><pre>" .htmlentities($result). "</pre></td><td>$result</td></tr></table>";

?>