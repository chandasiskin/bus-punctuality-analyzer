<?php
/**
 * Bus Punctuality Analyzer - File Upload Handler
 *
 * This script processes uploaded XLSX files containing bus schedule data,
 * converts them to CSV format with additional punctuality analysis columns,
 * and outputs the result as a downloadable CSV file.
 */

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

require __DIR__ . '/../vendor/autoload.php';

use Chanda\BusPunctualityAnalyzer\Service\Analyzer;

// if (!isset($_FILES['file'])) {
//     http_response_code(400);
//     exit('No file uploaded');
// }

// $tmpPath = $_FILES['file']['tmp_name'];
// $originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
$tmpPath='../Block Details.xlsx';$originalName='Block Details';

$analyzer = new Analyzer();
$outputFile = $analyzer->process($tmpPath);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $originalName . '_processed.csv"');

readfile($outputFile);
unlink($outputFile);