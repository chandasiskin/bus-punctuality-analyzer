<?php
/**
 * Bus Punctuality Analyzer - File Upload Handler
 *
 * This script processes uploaded XLSX files containing bus schedule data,
 * converts them to CSV format with additional punctuality analysis columns,
 * and outputs the result as a downloadable CSV file.
 */

use Shuchkin\SimpleXLSX;

// Check if a file was uploaded
if (!$_FILES || empty($_FILES)) die('You need to upload a file');

// Extract filename without extension and temporary path
$filename = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
$path = $_FILES['file']['tmp_name'];



// Include the SimpleXLSX library for parsing XLSX files
require 'lib/SimpleXLSX.php';

// Parse the uploaded XLSX file
if (!$xlsx = SimpleXLSX::parse($path)) die(SimpleXLSX::parseError());

// Create a temporary file to store CSV data
$fp_from = fopen('php://temp', 'w+');

// Convert XLSX rows to CSV format
foreach ($xlsx->rows() as $row) {
    fputcsv($fp_from, (array) $row, ';');
}

rewind($fp_from);

// Check if the temporary file was created successfully
if (!$fp_from) die('Could not read uploaded file');

// Create another temporary file for processed output
$fp_to = fopen('php://temp', 'w+');

// Check temp-file
if (!$fp_to) die('Could not create new file');



// Include helper functions for processing
include 'util/functions.php';

// Process the data: copy headers and contents with analysis
copy_headers($fp_from, $fp_to);
copy_contents($fp_from, $fp_to);

rewind($fp_to);

// Clear output buffer
ob_clean();
flush();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

// Output the processed CSV data
fpassthru($fp_to);

// Close file handles
fclose($fp_from);
fclose($fp_to);
fclose($fp_from);
fclose($fp_to);
