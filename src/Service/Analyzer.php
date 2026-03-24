<?php

namespace Chanda\BusPunctualityAnalyzer\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Analyzer {
    public function process(string $filePath): string {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(null, true, true, true);
        $rows = array_values($rows);

        if (empty($rows)) throw new \Exception('Empty file');

        $outputSpreadsheet = new Spreadsheet();
        $outputSheet = $outputSpreadsheet->getActiveSheet();

        $header = array_values($rows[0]);
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
        $header = array_merge($header, $extraHeaders);

        $outputSheet->fromArray($header, null, 'A1');

        $rowIndex = 2;

        foreach (array_slice($rows, 1) as $row) {
            $row = array_values($row);

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
                $this->isEaryl($type, $vmtDiff) ? 'Ja' : '',    // 'TIDIG VMT',
                $this->isEaryl($type, $slDiff) ? 'Ja' : '',     // 'TIDIG SL',
                $this->isLate($type, $vmtDiff) ? 'Ja' : '',     // 'SEN VMT',
                $this->isLate($type, $slDiff) ? 'Ja' : '',      // 'SEN SL',
                $this->isVeryLate($vmtDiff) ? 'Ja' : '', // '19 SEN VMT',
                $this->isVeryLate($slDiff) ? 'Ja' : ''   // '19 SEN SL'
            ];
            $row = array_merge($row, $extraRow);

            $outputSheet->fromArray($row, null, 'A' . $rowIndex);
            $rowIndex++;
        }

        return $this->writeExcel($outputSpreadsheet);
    }

    private function toSeconds($time) {
        if (!$time) return null;

        $ts = strtotime($time);

        return $ts !== false ? $ts % (24 * 60 * 60) : null;
    }

    private function calculateDiff($planned, $actual) {
        if ($planned === null || $actual === null) return null;

        $wholeDay = 24 * 60 * 60;
        $halfDay = $wholeDay / 2;

        $diff = ($planned - $actual + $halfDay) % $wholeDay;

        if ($diff < 0) $diff += $wholeDay;

        return $diff - $halfDay;
    }

    private function isEaryl(string $type, ?int $diff): bool {
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

    private function writeExcel(Spreadsheet $spreadsheet): string {
        $file = tempnam(sys_get_temp_dir(), 'xlsx_') . '.xslx';

        $writer = new Xlsx($spreadsheet);
        $writer->save($file);

        return $file;
    }
}