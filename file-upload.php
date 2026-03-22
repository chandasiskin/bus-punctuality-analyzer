<?php

use Shuchkin\SimpleXLSX;

if (!$_FILES || empty($_FILES)) die('You need to upload a file');



$filename = substr($_FILES['file']['name'], 0, -5);
$path = $_FILES['file']['tmp_name'];



require 'lib/SimpleXLSX.php';

if (!$xlsx = SimpleXLSX::parse($path)) die(SimpleXLSX::parseError());

$fp_from = fopen('php://temp', 'w+');

foreach ($xlsx->rows() as $row) {
    fputcsv($fp_from, (array) $row, ';');
}

rewind($fp_from);



// Check connection
if (!$fp_from) die('Could not read uploaded file');

// Trying to create a temp-file to write to
$fp_to = fopen('php://temp', 'w+');

// Check temp-file
if (!$fp_to) die('Could not create new file');



// Load helper-functions
include 'util/functions.php';

copy_headers($fp_from, $fp_to);
copy_contents($fp_from, $fp_to);

rewind($fp_to);

ob_clean();
flush();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="'.$filename.'.csv"');

fpassthru($fp_to);
fclose($fp_from);
fclose($fp_to);
