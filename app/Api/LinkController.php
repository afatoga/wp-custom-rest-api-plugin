<?php


namespace Afatoga\Api;


class LinkController {
    public function getLinks (string $currency) {
        return ["currency"=>$currency];
    }
}