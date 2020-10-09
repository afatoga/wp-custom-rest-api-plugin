<?php


namespace Afatoga\Api;


class ProductController {
    public function getProductList (string $currency): ?array {
        return ["currency"=>$currency];
    }

    public function getProductDetails (bool $logged_in, string $currency, int $productId): ?array {
        return [];
    }
}