<?php
/**
 * Bus Punctuality Analyzer - File Upload Handler
 *
 * This script processes uploaded XLSX files containing bus schedule data,
 * converts them to CSV format with additional punctuality analysis columns,
 * and outputs the result as a downloadable CSV file.
 */

require __DIR__ . '/../vendor/autoload.php';

use Chanda\BusPunctualityAnalyzer\Service\Analyzer;

if (!isset($_FILES['file'])) {
    http_response_code(400);
    exit('No file uploaded');
}

$tmpPath = $_FILES['file']['tmp_name'];
$originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);

$analyzer = new Analyzer();
$outputFile = $analyzer->process($tmpPath);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $originalName . '_processed.xlsx"');

readfile($outputFile);
unlink($outputFile);