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
      "/product",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "af_get_product"],
          "permission_callback"   => "__return_true"
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
          "permission_callback"   => "__return_true"
        ],
      ]
    );
  }

  public function af_is_user_logged_in()
  { 
    // if( class_exists('Jwt_Auth_Public') ) $jwtAuth_plugin = new \Jwt_Auth_Public();
    // $jwtAuth_plugin->validate_token(false);

    if (!$this->logged_in) {
      return true;
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

    $currencyList = ["czk", "usd", "eur"];

    if (!in_array($currency, $currencyList)) {
      return new \WP_Error("rest_forbidden", "Currency not found", ["status" => 404]);
    }

    $linkList = $linkController->getLinkList($currency);
    //var_dump($linkList);

    return new \WP_REST_Response(
      ["data" => $linkList],
      200
    );
  }

  public function af_get_productlist(\WP_REST_Request $request)
  {
    $productController = new ProductController();
    $payload = $request->get_params();
    $currency = filter_var($payload["currency"], FILTER_SANITIZE_STRING);
    $hash = filter_var($payload["h"], FILTER_SANITIZE_STRING);

    $currencyList = ["czk", "usd", "eur"];

    if (!in_array($currency, $currencyList)) {
      return new \WP_Error("not_found", "Currency not found", ["status" => 404]);
    }

    $productList = $productController->getProductList($currency, $hash);
    if (!$productList) return new \WP_Error("not_found", "Items not found", ["status" => 404]);

    return new \WP_REST_Response(
      ["data" => $productList],
      200
    );
  }

  public function af_get_product(\WP_REST_Request $request)
  {
    $productController = new ProductController();
    $payload = $request->get_params();
    $currency = filter_var($payload["currency"], FILTER_SANITIZE_STRING);
    $hash = filter_var($payload["h"], FILTER_SANITIZE_STRING);

    $productDetail = $productController->getProductDetail($this->logged_in, $currency, $hash);

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

  //   public function has_user_elearning_access(WP_REST_Request $request)
  //   {
  //     $host = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=UTF8";
  //     $connect = new PDO($host, DB_USER, DB_PASSWORD);

  //     $user_id = get_current_user_id();
  //     $af_elearningCourseItemId = get_page_by_path("online-kurz-termin", OBJECT, "af_courseitem")->ID;

  //     $query = "SELECT `af_course_item_id`, `user_id`, `is_gift`
  //               FROM `af_order_items`
  //               WHERE `user_id` = :userId
  //               AND `af_course_item_id` = :af_course_item_id";
  //     $statement = $connect->prepare($query);
  //     $statement->execute([":userId" => $user_id, ":af_course_item_id" => $af_elearningCourseItemId]);
  //     $result = $statement->fetch();
  //     $response = ["has_access" => ((int)$result["af_course_item_id"] === $af_elearningCourseItemId) ? true : false,
  //                   "is_gift" => (bool)$result["is_gift"]];

  //     return rest_ensure_response($response);
  //   }
}
