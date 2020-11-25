<?php

namespace Afatoga\Services;

class HashTableService
{
    public function getData(string $hash): array
    {
        $file = fopen(__DIR__ . '/hashtable_links.csv', 'r');
        $value = "";

        if (strlen($hash) < 10) return [];
        $hashPart = substr($hash, 0, 9);

        while (($line = fgetcsv($file, 0, ",")) !== FALSE) {
            if ($line[1] === $hashPart) {
                $value = $line[0];
                break;
            }
        }
        fclose($file);

        if (empty($value)) return [];
        
        $currency = substr($value, 0, 3);
        $ratio = (int) substr($value, 4, 2);
        $productCode = (strlen($hash) === 14) ? substr($hash, 10, 4) : null;

        return [
            "secretRatio" => $ratio, 
            "currency" => $currency,
            "productCode" => $productCode
        ];
    }
}
