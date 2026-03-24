<?php
/**
 * Bus Punctuality Analyzer - File Upload Handler
 *
 * This script processes uploaded XLSX files containing bus schedule data,
 * converts them to CSV format with additional punctuality analysis columns,
 * and outputs the result as a downloadable CSV file.
 */

ini_set('memory_limit', '512M');        // Increase memory limit for large file processing
ini_set('max_execution_time', 300);     // Set max execution time to 5 minutes

require __DIR__ . '/../vendor/autoload.php'; // Autoload necessary libraries

use Chanda\BusPunctualityAnalyzer\Service\Analyzer;

// Check if file is uploaded
if (!isset($_FILES['file'])) {
    http_response_code(400);  // Bad request response code
    exit('No file uploaded'); // Exit if no file is provided
}

// Get the temporary file path and the original file name (without extension)
$tmpPath = $_FILES['file']['tmp_name'];
$originalName = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);

// Instantiate the Analyzer class
$analyzer = new Analyzer();

// Process the uploaded file and generate the output CSV
$outputFile = $analyzer->process($tmpPath);

// Set headers for CSV file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $originalName . '_processed.csv"');

// Output the contents of the processed CSV file to the browser
readfile($outputFile);

// Remove the temporary CSV file after it has been read
unlink($outputFile);