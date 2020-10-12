<?php

namespace Afatoga\Services;

class HashTableService
{
    private $hashTableLists;
    private $hashTableProducts;

    public function __construct()
    {
        $this->hashTableLists = [
            "aa7a3c2b0b" => "05_czk",
            "e582c05ece" => "10_czk",
            "e582c05sxx" => "20_czk",
            "e422c05saw" => "20_czk"
        ];
        $this->hashTableProducts = [
            "aa7a3c2b0b" => "00001gem",
            "e582c05ece" => "00002gem",
            "e582c05sxx" => "00003gem",
            "e422c05saw" => "00004gem"
        ];
    }

    public function getData(string $hash, string $type): array
    {
        if ($type === "product") {

            $file = fopen(__DIR__ .'/product.csv', 'r');
            $ar = [];
while (($line = fgetcsv($file, 0, ";")) !== FALSE) {
  $ar[$line[1]] = $line[0];
}
fclose($file);

            // if (!array_key_exists($hash, $this->hashTableProducts)) return [];
            // $value = $this->hashTableProducts[$hash];
            // $id = (int) substr($value, 0, 5);
            return $ar;
        } else {
            if (!array_key_exists($hash, $this->hashTableLists)) return [];
            $value = $this->hashTableLists[$hash];
            $ratio = (int) substr($value, 0, 2);
            $currency = substr($value, 2, 3);
            return ["secretRatio" => $ratio, "currency" => $currency];
        }
    }
}

/*
 $this->hashTable = [
        "aa7a3c2b0b90c8b9e321d6832ffa2262" => "afatoga_5czk",
        "e582c05ece5295a244742fd68d7063ba" => "afatoga_10czk",
       ];
*/
