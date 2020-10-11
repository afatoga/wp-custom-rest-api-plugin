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

    public function getProductList(string $currency, string $hash): ?array
    {
        $currencyRate = (float) $this->getCurrencyRate($currency);
        if (!$currencyRate) return false;
        $query = "SELECT * FROM af_products LIMIT 150";

        $hashData = $this->getDataFromHash($hash);
        if (!isset($hashData["secretRatio"])) return false;

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $num = $stmt->rowCount();

        if (!$num) return false;

        $productList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($productList as &$product) {
            $product["Price"] = ceil((int) $product["Minimal_price_USDct"] * $currencyRate);
            unset($product["Minimal_price_USDct"]);
            unset($product["Code_private"]);
        }
        return $productList;
    }

    public function getProductDetail(bool $logged_in, string $currency, string $hash): ?array
    {
        $hashData = $this->getDataFromHash($hash);
        if (!isset($hashData["productId"])) return false;
        if (!$logged_in) $currency = null;
        $productDetailData = $this->getProductDetailData($productId, $currency);
        return $productDetailData;
    }

    private function getDataFromHash(string $hash): array
    {
        //is hash valid?
        return ["secretRatio" => 1.5];
        return ["productId" => 1, "secretRatio" => 1.5];
    }

    private function getProductDetailData(int $productId, ?string $currency)
    {
        $query = "SELECT * FROM af_products WHERE `ID` = ? LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $productId);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (empty($result)) return false;
        if ($currency) $result["Price"] = ceil((int)$result["Minimal_price_USDct"] * $this->getCurrencyRate($currency));

        return $result;
    }

    private function getCurrencyRate(string $currency): ?string
    {
        $currency = strtoupper($currency);
        if ($currency === "USD") return '1';

        $query = "SELECT Rate FROM af_currency_rates WHERE `Name` = ? LIMIT 1";

        $stmt = $this->db->prepare($query);
        $currencyRateName = 'USD' . $currency;
        $stmt->bindParam(1, $currencyRateName);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        if ($result) return $result;
        return false;
    }
}
