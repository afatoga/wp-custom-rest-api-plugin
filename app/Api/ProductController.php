<?php


namespace Afatoga\Api;

use Afatoga\Services\DatabaseService;


class ProductController
{

    private $db;

    public function __construct()
    {
        $dbService = new DatabaseService();
        $this->db = $dbService->getConnection();
    }

    public function getProductList(string $currency): ?array
    {

        $query = "SELECT * FROM af_products LIMIT 150";

        $stmt = $this->db->prepare($query);
        //$stmt->bindParam(1, $email);
        $stmt->execute();
        $num = $stmt->rowCount();

        if (!$num) return false;

        // if ($currency === "usd") {
        //     return $currency;
        // } else {

        // }
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductDetails(bool $logged_in, string $currency, int $productId): ?array
    {
        return [];
    }
}
