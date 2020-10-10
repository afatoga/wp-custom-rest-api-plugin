<?php


namespace Afatoga\Api;


class LinkController {
    public function getLinkList (string $currency) {
        return ["currency"=>$currency];
    }
}