<?php


namespace Afatoga\Api;
use Afatoga\Services\HashTableService;


class LinkController {
    public function getLinkList (string $currency): array 
    {
        $hashTableService = new HashTableService();
        return $hashTableService->getHashlist($currency);
    }
}