<?php

$filename = @$_GET['file'];

if(empty($filename)) {
    die("no file given");
}
if(!is_file($filename)) {
    die("Invalid file: $filename");
}

$output = shell_exec($filename." 2>&1");
echo "<pre>$output</pre>";