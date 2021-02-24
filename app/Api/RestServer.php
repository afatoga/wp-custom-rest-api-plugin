<?php

namespace Afatoga\Api;

use Afatoga\Api\LinkController;

class RestServer extends \WP_REST_Controller
{
  private $logged_in;
  private $user;

  public function __construct()
  {
    $this->user = wp_get_current_user();
    $this->logged_in = is_user_logged_in();
  }

  //The namespace and version for the REST SERVER
  var $my_namespace = "af_restserver/v";
  var $my_version   = "1";

  public function register_routes()
  {
    $namespace = $this->my_namespace . $this->my_version;

    register_rest_route(
      $namespace,
      "/register",
      [
        [
          "methods"         => "POST",
          "callback"        => [$this, "af_register_new_user"],
          "permission_callback" => "__return_true",
        ]
      ]
    );

    register_rest_route(
      $namespace,
      "/update_user_data",
      [
        [
          "methods"         => "POST",
          "callback"        => [$this, "af_update_user_data"],
          "permission_callback" => [$this, "af_is_user_logged_in"],
        ]
      ]
    );

    register_rest_route(
      $namespace,
      "/get_user_data",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "af_get_user_data"],
          "permission_callback"   => [$this, "af_is_user_logged_in"]
        ],
      ]
    );

    register_rest_route(
      $namespace,
      "/get_product",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "af_get_product"],
          "permission_callback"   => [$this, "af_is_user_logged_in"]
        ],
      ]
    );
    register_rest_route(
      $namespace,
      "/get_productlist",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "af_get_productlist"],
          "permission_callback"   => [$this, "af_is_user_logged_in"]
        ],
      ]
    );
    register_rest_route(
      $namespace,
      "/get_linklist",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "af_get_linklist"],
          "permission_callback"   => [$this, "af_is_user_logged_in"]
        ],
      ]
    );
  }

  public function af_is_user_logged_in(\WP_REST_Request $request)
  { 

    // $payload = $request->get_params();
    // $route = $request->get_route();
    // $hash = filter_var($payload["h"], FILTER_SANITIZE_STRING);
    // if ($hash === "7150ab71b8" && ($route === "/af_restserver/v1/get_product" || $route === "/af_restserver/v1/get_productlist")) return true;

    if (!$this->logged_in) {
      //return true;
      return new \WP_Error("rest_forbidden", "Access forbidden", ["status" => 401]);
    }
    // !current_user_can("read")
    return true;
  }

  public function af_get_linklist(\WP_REST_Request $request)
  {
    $linkController = new LinkController();
    $payload = $request->get_params();
    $currency = filter_var($payload["currency"], FILTER_SANITIZE_STRING);

    $currencyList = ["CZK", "USD", "EUR", "RUB", "GBP"];

    if (!$currency || !in_array($currency, $currencyList)) {
      return new \WP_Error("rest_not_found", "Currency not found", ["status" => 404]);
    }

    $currency = strtolower($currency);
    $linkList = $linkController->getLinkList($currency);

    return new \WP_REST_Response(
      ["data" => $linkList],
      200
    );
  }

  public function af_get_productlist(\WP_REST_Request $request)
  {
    $productController = new ProductController();
    $payload = $request->get_params();
    $hash = (isset($payload["h"])) ? filter_var($payload["h"], FILTER_SANITIZE_STRING) : null;
    $limit = (isset($payload["limit"])) ? filter_var($payload["limit"], FILTER_VALIDATE_INT) : 10;
    $offset = (isset($payload["offset"])) ? filter_var($payload["offset"], FILTER_VALIDATE_INT) : 0;

    if (!$hash) return new \WP_Error("rest_bad_request", "Hash not valid", ["status" => 400]);

    $response = $productController->getProductList($hash, $limit, $offset);
    if (empty($response["productList"])) return new \WP_Error("rest_not_found", "Products not found", ["status" => 404]);

    return new \WP_REST_Response(
      ["data" => $response],
      200
    );
  }

  public function af_get_product(\WP_REST_Request $request)
  {
    $productController = new ProductController();
    $payload = $request->get_params();
    //$currency = filter_var($payload["currency"], FILTER_SANITIZE_STRING);
    $hash = filter_var($payload["h"], FILTER_SANITIZE_STRING);

    $productDetail = $productController->getProductDetail($this->logged_in, $hash);
    
    if (empty($productDetail)) return new \WP_Error("rest_not_found", "Product not found", ["status" => 404]);

    return new \WP_REST_Response(
      ["data" => $productDetail],
      200
    );
  }

  public function af_register_new_user(\WP_REST_Request $request)
  {
    if (!$this->user) return wp_send_json_error("400");

    $payload = $request->get_params();
    $email = filter_var($payload["username"], FILTER_VALIDATE_EMAIL);
    if (!$email) return wp_send_json_error("Invalid email", 400);

    register_new_user($email, $email);
    return new \WP_REST_Response(
      ["message" => "success"],
      201
    );
  }

  public function af_get_user_data()
  { 
    if (!$this->user) return wp_send_json_error("400");
   
    $all_meta_for_user = array_map( function( $a ){ return $a[0]; }, get_user_meta( $this->user->ID ) );

    $data = [
      "email" => $this->user->user_email,
      "first_name" => $this->user->first_name,
      "last_name" => $this->user->last_name,
      "phone_number" => $all_meta_for_user["gemz_phone_number"],
      "street" => $all_meta_for_user["gemz_street"],
      "city" => $all_meta_for_user["gemz_city"],
      "zip" => $all_meta_for_user["gemz_zip"],
      "country" => $all_meta_for_user["gemz_country"],
    ];

    return new \WP_REST_Response(
      $data,
      200
    );
  }

  

  public function af_update_user_data(\WP_REST_Request $request)
  {
    if (!$this->user) return wp_send_json_error("400");
    $userId = $this->user->ID;

    $payload = $request->get_params();

    $firstName = filter_var($payload["first_name"], FILTER_SANITIZE_STRING);
    $lastName = filter_var($payload["last_name"], FILTER_SANITIZE_STRING);
    $phoneNumber = filter_var($payload["phone_number"], FILTER_SANITIZE_STRING);
    $street = filter_var($payload["street"], FILTER_SANITIZE_STRING);
    $city = filter_var($payload["city"], FILTER_SANITIZE_STRING);
    $zip = filter_var($payload["zip"], FILTER_SANITIZE_STRING);
    $country = filter_var($payload["country"], FILTER_SANITIZE_STRING);

    //if (!$email) return wp_send_json_error("Invalid email", 400);
    update_user_meta( $userId, 'first_name', $firstName);
    update_user_meta( $userId, 'last_name', $lastName);
    update_user_meta( $userId, 'gemz_phone_number', $phoneNumber);
    update_user_meta( $userId, 'gemz_street', $street);
    update_user_meta( $userId, 'gemz_city', $city);
    update_user_meta( $userId, 'gemz_zip', $zip);
    update_user_meta( $userId, 'gemz_country', $country);

    return new \WP_REST_Response(
      ["message" => "success"],
      200
    );
  }
}
