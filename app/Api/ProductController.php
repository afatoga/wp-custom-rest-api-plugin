<?php


namespace Afatoga\Api;

use Afatoga\Services\DatabaseService;
use Afatoga\Services\HashTableService;


class ProductController
{

    private $db;

    public function __construct()
    {
        $dbService = new DatabaseService();
        $this->db = $dbService->getConnection();
    }

    public function getProductList(string $hash, int $limit, int $offset): array
    {
        $query = "SELECT af_products.*, af_product_videos.main AS video
            FROM af_products
            LEFT OUTER JOIN af_product_videos ON af_products.Code = af_product_videos.product_code
            -- LIMIT :size
            -- OFFSET :offset";
        $hashData = $this->getDataFromHash($hash);
        $secretRatio = (!isset($hashData["secretRatio"])) ? 0 : 0.01*$hashData["secretRatio"];
        $currencyName = strtoupper($hashData["currency"]);
        // return ["c"=> $hashData];

        $stmt = $this->db->prepare($query);
        $stmt->execute([":size" => $limit, ":offset" => $offset]);
        $num = $stmt->rowCount();
        if (!$num) return [];

        $productList = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if ($currencyName !== "XXX") {

            $currencyRate = $this->getCurrencyRate($currencyName);
            if (!$currencyRate) return [];

            foreach ($productList as &$product) {
                $product["Price"] = ceil((int) $product["Minimal_price_USDct"] * $currencyRate * (1+$secretRatio));
                unset($product["Minimal_price_USDct"]);
                unset($product["Code_private"]);
            }
        } else {
            unset($product["Minimal_price_USDct"]);
            unset($product["Code_private"]);
        }

        return ["productList"=>$productList, "currency"=> $currencyName];
    }

    public function getProductDetail(bool $logged_in, string $hash): array
    {
        $hashData = $this->getDataFromHash($hash);
        if (!isset($hashData["productCode"])) return false;
        $secretRatio = (!isset($hashData["secretRatio"])) ? 0 : 0.01*$hashData["secretRatio"];
        $currency = ($logged_in && $hashData["currency"] !== "xxx") ? $hashData["currency"] : null;
        $productDetailData = $this->getProductDetailData($hashData["productCode"], $currency, $secretRatio);
        return $productDetailData;
    }

    private function getDataFromHash(string $hash): array
    {   
        $hashTableService = new HashTableService();
        $data = $hashTableService->getData($hash);
        return $data;
    }

    private function getProductDetailData(string $productCode, ?string $currency, float $secretRatio)
    {
        $query = "SELECT af_products.*, af_product_videos.main, af_product_videos.artificial, af_product_videos.natural  
        FROM af_products 
        LEFT OUTER JOIN af_product_videos ON af_products.Code = af_product_videos.product_code
        WHERE `Code` = ? LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $productCode, \PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (empty($result)) return false;
        
        if ($currency) {
            $result["Price"] = ceil((int)$result["Minimal_price_USDct"] * $this->getCurrencyRate($currency) * (1+$secretRatio));

            $result["currency"] = strtoupper($currency);
        } else {
            unset($result["Minimal_price_USDct"]);
        }

        return $result;
    }

    private function getCurrencyRate(string $currency): ?float
    {
        $currency = strtoupper($currency);
        if ($currency === "USD") return 1.00;
        if ($currency === "EUR") return 0.80;
        if ($currency === "CZK") return 23.00;
        if ($currency === "GBP") return 0.60;

        $query = "SELECT Rate FROM af_currency_rates WHERE `Name` = ? LIMIT 1";

        $stmt = $this->db->prepare($query);
        $currencyRateName = 'USD' . $currency;
        $stmt->bindParam(1, $currencyRateName);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        
        if (!$result) return null;
        return (float) $result;
    }
}
