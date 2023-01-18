<?php

namespace ComputeEngine;
use Ubench;

class Helpers{

    public static function printBenchmarkStats(Ubench $bench){
        $data = [
            'ElapsedTime' => $bench->getTime(),
            'PeakMemoryUsage' => $bench->getMemoryPeak(),
            'MemoryUsage' => $bench->getMemoryUsage()
        ];

        print_r($data);
    }

}