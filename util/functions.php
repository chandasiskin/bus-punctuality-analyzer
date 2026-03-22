<?php
    function copy_headers($fp_from, $fp_to) {
        // UTF-8 BOM
        fputs($fp_to, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        $headers = fgetcsv($fp_from, 0, ';');
    
        $extra_headers = [
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
        
        $headers = array_merge($headers, $extra_headers);
        
        fputcsv($fp_to, $headers, ';');
    }



    function copy_contents($fp_from, $fp_to) {
        while (($line = fgetcsv($fp_from, 0, ';')) !== false) {
            $line[1] = date('Y-m-d', strtotime($line[1]));
            $line[6] = formatTimeHMS($line[6]);
            $line[10] = formatTimeHMS($line[10]);
            $line[13] = formatTimeHMS($line[13]);

            $planned = normalizeTime($line[6]);
            $vmt = normalizeTime($line[10]);
            $type = $line[12];
            $sl = normalizeTime($line[13]);

            $vmt_planned = calculateTimeDifference($planned, $vmt);
            $sl_planned = calculateTimeDifference($planned, $sl);
            $vmt_sl = $vmt !== false && $sl !== false ? abs($vmt_planned - $sl_planned) : '';
            
            $extra_line = [
                $vmt_planned,
                $sl_planned,
                $vmt_sl,
                $vmt && isEarly($type, $vmt_planned) ? 'Ja' : '',
                $sl && isEarly($type, $sl_planned) ? 'Ja' : '',
                $vmt && isLate($type, $vmt_planned) ? 'Ja' : '',
                $sl && isLate($type, $sl_planned) ? 'Ja' : '',
                $vmt && is19Late($vmt_planned) ? 'Ja' : '',
                $sl && is19Late($sl_planned) ? 'Ja' : ''
            ];

            $line = array_merge($line, $extra_line);

            fputcsv($fp_to, $line, ';');
        }
    }



    function calculateTimeDifference($planned, $actual) {
        if (!$actual) return '';

        $whole_day = 24 * 60 * 60;
        $half_day = $whole_day / 2;

        $diff = ($planned - $actual + $half_day) % $whole_day;

        if ($diff < 0) $diff += $whole_day;

        return $diff - $half_day;
    }
    
    
    
    function isEarly($type, $time) {
        if ($type === 'Arrival') return $time > 300;
        if ($type === 'Departure' || $type === 'Passage') return $time > 60;

        return false;
    }
    
    

    function isLate($type, $time) {
        if ($type === 'Arrival') return $time < -60;
        if ($type === 'Departure' || $type === 'Passage') return $time < -180;

        return false;
    }
    
    

    function is19Late($time) {
        return $time <= -19 * 60; // 19 minutes converted to seconds
    }



    function normalizeTime($time) {
        if ($time == null || $time === '') return false;

        if (is_numeric($time)) return (int) round($time * 24 * 60 * 60);

        $ts = strtotime($time);

        return $ts !== false ? $ts : false;
    }



    function formatTimeHMS($time) {
        if (!$time) return '';

        if (is_numeric($time)) return gmdate('H:i:s', $time);
        
        $ts = strtotime($time);

        return $ts !== false ? date('H:i:s', $ts) : $time;
    }