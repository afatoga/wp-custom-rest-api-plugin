<?php

namespace Afatoga\Services;

class HashTableService
{
    public function getData(string $hash): array
    {
        $file = fopen(__DIR__ . '/hashtable_links.csv', 'r');
        $value = "";

        if (strlen($hash) < 10) return [];
        $hashPart = substr($hash, 0, 10);

        while (($line = fgetcsv($file, 0, ",")) !== FALSE) {
            if ($line[1] === $hashPart) {
                $value = $line[0];
                break;
            }
        }
        fclose($file);

        if (empty($value)) return [];
        
        $currency = substr($value, 0, 3);
        $ratio = intval(substr($value, 4, 2));
        $productCode = (strlen($hash) === 14) ? substr($hash, 10, 4) : null;

        return [
            "secretRatio" => $ratio, 
            "currency" => $currency,
            "productCode" => $productCode
        ];
    }

    public function getHashlist(string $currency): array
    {
        $file = fopen(__DIR__ . '/hashtable_links.csv', 'r');

        if (strlen($currency) < 3) return [];

        $hashlist = [];

        while (($line = fgetcsv($file, 0, ",")) !== FALSE) {
            if (substr($line[0], 0, 3) === $currency) {
                $secretRatio = intval(substr($line[0], 4, 2));
                $hashlist[$secretRatio] = $line[1];
            }
        }
        fclose($file);

        return $hashlist;
    }
}
