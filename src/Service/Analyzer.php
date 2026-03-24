<?php

namespace Chanda\BusPunctualityAnalyzer\Service;

use Shuchkin\SimpleXLSX;

/**
 * Analyzer class for processing bus schedule data and calculating punctuality metrics.
 *
 * This class processes an uploaded XLSX file containing bus schedule data and calculates 
 * various punctuality metrics.
 * The processed data is outputted as a CSV file.
 */
class Analyzer {
    /**
     * Processes the uploaded XLSX file and generates a CSV with punctuality metrics.
     *
     * This method takes the file path of the uploaded XLSX file, parses its contents, 
     * and calculates various punctuality metrics for each row of data. It outputs a CSV 
     * file containing the original data along with the computed metrics.
     *
     * @param string $filePath The path to the XLSX file to process.
     * @return string The path to the generated CSV file.
     * @throws \Exception If the XLSX file cannot be parsed.
     */
    public function process(string $filePath): string {
        if ($xlsx = SimpleXLSX::parse($filePath)) {
            // Create a temporary file for the output CSV
            $outputFile = tempnam(sys_get_temp_dir(), 'csv_') . '.csv';
            $file = fopen($outputFile, 'w+');
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Insert BOM for UTF-8 encoding

            $headerWritten = false;

            // Iterate over the rows of the XLSX file
            foreach ($xlsx->rows() as $rowData) {
                $rowData = (array) $rowData;

                // Skip empty rows
                if (empty(array_filter($rowData))) continue;

                // Write headers only once
                if (!$headerWritten) {
                    $extraHeaders = [
                        'VMT-PLAN',
                        'SL-PLAN',
                        'VMT-SL DIFF',
                        'TIDIG VMT',
                        'TIDIG SL',
                        'SEN VMT',
                        'SEN SL',
                        '19 SEN VMT',
                        '19 SEN SL'
                    ];

                    $header = array_merge($rowData, $extraHeaders);
                    fputcsv($file, $header, ';');
                    $headerWritten = true;
                    continue;
                }

                // Extract the relevant data from each row
                $planned = $rowData[6];
                $vmt = $rowData[10];
                $sl = $rowData[13];
                $type = $rowData[12];

                // Convert times to seconds
                $plannedSeconds = $this->toSeconds($planned);
                $vmtSeconds = $this->toSeconds($vmt);
                $slSeconds = $this->toSeconds($sl);

                // Calculate time differences
                $vmtDiff = $this->calculateDiff($plannedSeconds, $vmtSeconds);
                $slDiff = $this->calculateDiff($plannedSeconds, $slSeconds);
                $vmtSlDiff = $vmtDiff !== null && $slDiff !== null ? abs($vmtDiff - $slDiff) : '';

                // Calculate punctuality metrics for each row
                $extraRow = [
                    $vmtDiff,                                       // 'VMT-PLAN'
                    $slDiff,                                        // 'SL-PLAN'
                    $vmtSlDiff,                                     // 'VMT-SL DIFF'
                    $this->isEarly($type, $vmtDiff) ? 'Ja' : '',    // 'TIDIG VMT'
                    $this->isEarly($type, $slDiff) ? 'Ja' : '',     // 'TIDIG SL'
                    $this->isLate($type, $vmtDiff) ? 'Ja' : '',     // 'SEN VMT'
                    $this->isLate($type, $slDiff) ? 'Ja' : '',      // 'SEN SL'
                    $this->isVeryLate($vmtDiff) ? 'Ja' : '',        // '19 SEN VMT'
                    $this->isVeryLate($slDiff) ? 'Ja' : ''          // '19 SEN SL'
                ];

                // Format the date and time fields
                $rowData[1] = date('Y-m-d', strtotime($rowData[1]));
                $rowData[6] = date('H:i:s', strtotime($rowData[6]));
                $rowData[10] = $vmt !== '' ? date('H:i:s', strtotime($rowData[10])) : '';
                $rowData[13] = $sl !== '' ? date('H:i:s', strtotime($rowData[13])) : '';

                // Merge the original row with the calculated metrics
                $rowData = array_merge($rowData, $extraRow);

                // Write the row data to the CSV
                fputcsv($file, $rowData, ';');
            }

            fclose($file);

            // Return the path to the generated CSV
            return $outputFile;
        } else {
            throw new \Exception('Failed to read the excel-file');
        }
    }

    /**
     * Converts a time string to seconds.
     *
     * This method takes a time string (e.g., '12:30:00') and converts it to the 
     * number of seconds since midnight. If the time is invalid or null, it returns null.
     *
     * @param mixed $time The time string to convert.
     * @return int|null The time in seconds, or null if the time is invalid.
     */
    private function toSeconds($time): ?int {
        if (!$time) return null;

        $ts = strtotime($time);

        return $ts !== false ? $ts % (24 * 60 * 60) : null;
    }

    /**
     * Calculates the time difference between the planned and actual times.
     *
     * This method calculates the difference between the planned and actual times, 
     * considering the circular nature of time (i.e., if the actual time exceeds the 
     * planned time, it wraps around). The result is adjusted for half a day to 
     * handle early/late differences correctly.
     *
     * @param int|null $planned The planned time in seconds.
     * @param int|null $actual The actual time in seconds.
     * @return int|null The time difference in seconds, or null if either time is invalid.
     */
    private function calculateDiff($planned, $actual): ?int {
        if ($planned === null || $actual === null) return null;

        $wholeDay = 24 * 60 * 60;
        $halfDay = $wholeDay / 2;

        $diff = ($planned - $actual + $halfDay) % $wholeDay;

        if ($diff < 0) $diff += $wholeDay;

        return $diff - $halfDay;
    }

    /**
     * Determines if the event is considered early.
     *
     * This method checks whether an event is considered early based on the type of event 
     * (e.g., Arrival, Departure, Passage) and the calculated time difference.
     *
     * @param string $type The type of event (Arrival, Departure, Passage).
     * @param int|null $diff The time difference in seconds.
     * @return bool True if the event is early, false otherwise.
     */
    private function isEarly(string $type, ?int $diff): bool {
        if ($type === 'Arrival') return $diff > 300;     // Arrival is early if > 5 minutes
        if ($type === 'Departure' || $type === 'Passage') return $diff > 60;  // Departure/Passage is early if > 1 minute

        return false;
    }

    /**
     * Determines if the event is considered late.
     *
     * This method checks whether an event is considered late based on the type of event 
     * and the calculated time difference.
     *
     * @param string $type The type of event (Arrival, Departure, Passage).
     * @param int|null $diff The time difference in seconds.
     * @return bool True if the event is late, false otherwise.
     */
    private function isLate(string $type, ?int $diff): bool {
        if ($type === 'Arrival') return $diff < -60;      // Arrival is late if < -1 minute
        if ($type === 'Departure' || $type === 'Passage') return $diff < -180; // Departure/Passage is late if < -3 minutes

        return false;
    }

    /**
     * Determines if the event is considered very late.
     *
     * This method checks whether an event is considered very late based on the calculated 
     * time difference being more than 19 minutes.
     *
     * @param int|null $diff The time difference in seconds.
     * @return bool True if the event is very late, false otherwise.
     */
    private function isVeryLate(?int $diff): bool {
        return $diff !== null && $diff <= -19 * 60;  // Very late if < -19 minutes
    }
}
