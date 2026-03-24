<?php

namespace Chanda\BusPunctualityAnalyzer\Service;

use Shuchkin\SimpleXLSX;

class Analyzer {
    public function process(string $filePath): string {
        if ($xlsx = SimpleXLSX::parse($filePath)) {
            $outputFile = tempnam(sys_get_temp_dir(), 'csv_') . '.csv';
            $file = fopen($outputFile, 'w+');
            fwrite($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Insert BOM for UTF-8 encoding

            $headerWritten = false;

            foreach ($xlsx->rows() as $rowIndex => $rowData) {
                $rowData = (array) $rowData;

                if (empty(array_filter($rowData))) continue;

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



                $planned = $row[6] ?? null;
                $vmt = $row[10] ?? null;
                $sl = $row[13] ?? null;
                $type = $row[12] ?? '';
                
                $plannedSeconds = $this->toSeconds($planned);
                $vmtSeconds = $this->toSeconds($vmt);
                $slSeconds = $this->toSeconds($sl);

                $vmtDiff = $this->calculateDiff($plannedSeconds, $vmtSeconds);
                $slDiff = $this->calculateDiff($plannedSeconds, $slSeconds);

                $extraRow = [
                    $vmtDiff,                                       // 'VMT-PLAN',
                    $slDiff,                                        // 'SL-PLAN',
                    abs($vmtDiff - $slDiff),                        // 'VMT-SL DIFF',
                    $this->isEarly($type, $vmtDiff) ? 'Ja' : '',    // 'TIDIG VMT',
                    $this->isEarly($type, $slDiff) ? 'Ja' : '',     // 'TIDIG SL',
                    $this->isLate($type, $vmtDiff) ? 'Ja' : '',     // 'SEN VMT',
                    $this->isLate($type, $slDiff) ? 'Ja' : '',      // 'SEN SL',
                    $this->isVeryLate($vmtDiff) ? 'Ja' : '',        // '19 SEN VMT',
                    $this->isVeryLate($slDiff) ? 'Ja' : ''          // '19 SEN SL'
                ];

                $rowData = array_merge($rowData, $extraRow);

                fputcsv($file, $rowData, ';');
            }

            fclose($file);

            return $outputFile;
        } else {
            throw new \Exception('Failed to read the excel-file');
        }
    }

    private function toSeconds($time): ?int {
        if (!$time) return null;

        $ts = strtotime($time);

        return $ts !== false ? $ts % (24 * 60 * 60) : null;
    }

    private function calculateDiff($planned, $actual): ?int {
        if ($planned === null || $actual === null) return null;

        $wholeDay = 24 * 60 * 60;
        $halfDay = $wholeDay / 2;

        $diff = ($planned - $actual + $halfDay) % $wholeDay;

        if ($diff < 0) $diff += $wholeDay;

        return $diff - $halfDay;
    }

    private function isEarly(string $type, ?int $diff): bool {
        if ($type === 'Arrival') return $diff > 300;
        if ($type === 'Departure' || $type === 'Passage') return $diff > 60;

        return false;
    }

    private function isLate(string $type, ?int $diff): bool {
        if ($type === 'Arrival') return $diff < -60;
        if ($type === 'Departure' || $type === 'Passage') return $diff < -180;

        return false;
    }

    private function isVeryLate(?int $diff): bool {
        return $diff !== null && $diff <= -19 * 60;
    }
}