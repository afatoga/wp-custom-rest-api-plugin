<?php

namespace Afatoga\Services;

class HashTableService
{
    public function getData(string $hash): array
    {
        $file = fopen(__DIR__ . '/hashtable_links.csv', 'r');
        $value = "";
        while (($line = fgetcsv($file, 0, ";")) !== FALSE) {
            if ($line[1] === $hash) {
                $value = $line[0];
                break;
            }
        }
        fclose($file);

        if (empty($value)) return [];
        
        $currency = substr($value, 0, 3);
        $ratio = (int) substr($value, 4, 2);
        return ["secretRatio" => $ratio, "currency" => $currency];
    }
}
