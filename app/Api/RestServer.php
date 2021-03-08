<?php

namespace Afatoga\Api;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use Afatoga\Services\ReCaptcha;

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
  var $my_namespace = "aa_restserver/v";
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
          "callback"        => [$this, "aa_register_new_user"],
          "permission_callback" => "__return_true",
        ]
      ]
    );

    register_rest_route(
      $namespace,
      "/submit_registration_form",
      [
        [
          "methods"         => "POST",
          "callback"        => [$this, "aa_submit_registration_form"],
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
          "callback"        => [$this, "aa_update_user_data"],
          "permission_callback" => [$this, "aa_is_user_logged_in"],
        ]
      ]
    );

    register_rest_route(
      $namespace,
      "/get_user_data",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "aa_get_user_data"],
          "permission_callback"   => [$this, "aa_is_user_logged_in"]
        ],
      ]
    );

    register_rest_route(
      $namespace,
      "/get_user_list",
      [
        [
          "methods"         => "GET",
          "callback"        => [$this, "aa_get_user_list"],
          "permission_callback" => [$this, "aa_is_user_logged_in"],
        ]
      ]
    );
  }

  public function aa_is_user_logged_in(WP_REST_Request $request)
  {

    //$payload = $request->get_params();
    //$route = $request->get_route(); // $route === "/aa_restserver/v1/get_product"

    if (!$this->logged_in) {
      return new WP_Error("rest_forbidden", "Access forbidden", ["status" => 401]);
    }
    // !current_user_can("read")
    return true;
  }

  public function aa_submit_registration_form(WP_REST_Request $request)
  {

    $payload = $request->get_params();

    $reCaptcha = new ReCaptcha();
    $isReCaptchaValid = $reCaptcha->verifyUserToken($payload["g-recaptcha-response"]);


    $email = filter_var($payload["email"], FILTER_VALIDATE_EMAIL);
    if (!$email || username_exists($email) || email_exists($email)) return new WP_Error("invalid_input", "Invalid email", ["status" => 400]);
    if (!$isReCaptchaValid) return new WP_Error("invalid_recaptcha", "Recaptcha is not valid", ["status" => 400]);


    $userId = wp_insert_user([
      'user_login' => $email,
      'user_email' => $email,
      'role' => 'subscriber'
    ]);

    if ($userId) {
      $approved = strpos($email, "@uhkt.cz") ? 1 : 0;
      update_user_meta($userId, 'vize_reg_approved', $approved);
    }

    return new WP_REST_Response(
      ["message" => "success"],
      201
    );
  }

  public function aa_register_new_user(WP_REST_Request $request)
  {
    if (!$this->user) return wp_send_json_error("400");

    $payload = $request->get_params();
    $email = filter_var($payload["username"], FILTER_VALIDATE_EMAIL);
    if (!$email) return wp_send_json_error("Invalid email", 400);

    register_new_user($email, $email);
    return new WP_REST_Response(
      ["message" => "success"],
      201
    );
  }

  public function aa_get_user_data()
  {
    if (!$this->user) return wp_send_json_error("400");

    //accessing user meta in a convenient way
    $all_meta_for_user = array_map(function ($a) {
      return $a[0];
    }, get_user_meta($this->user->ID));

    $data = [
      "email" => $this->user->user_email,
      "first_name" => $this->user->first_name,
      "last_name" => $this->user->last_name,
      "phone_number" => $all_meta_for_user["aa_phone_number"],
      "street" => $all_meta_for_user["aa_street"],
      "city" => $all_meta_for_user["aa_city"],
      "zip" => $all_meta_for_user["aa_zip"],
      "country" => $all_meta_for_user["aa_country"],
    ];

    return new WP_REST_Response(
      $data,
      200
    );
  }



  public function aa_update_user_data(WP_REST_Request $request)
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
    update_user_meta($userId, 'first_name', $firstName);
    update_user_meta($userId, 'last_name', $lastName);
    update_user_meta($userId, 'aa_phone_number', $phoneNumber);
    update_user_meta($userId, 'aa_street', $street);
    update_user_meta($userId, 'aa_city', $city);
    update_user_meta($userId, 'aa_zip', $zip);
    update_user_meta($userId, 'aa_country', $country);

    return new WP_REST_Response(
      ["message" => "success"],
      200
    );
  }

  public function aa_get_user_list(WP_REST_Request $request)
  {
    global $wpdb;
    $userMetaTable = $wpdb->prefix . "usermeta";
    $usersTable = $wpdb->prefix . "users";
    $query = "SELECT {$userMetaTable}.`user_id`, {$usersTable}.`user_registered`, 
                     {$usersTable}.`user_email`
              FROM {$userMetaTable}
              INNER JOIN {$usersTable} ON {$userMetaTable}.`user_id` = {$usersTable}.ID
              WHERE {$userMetaTable}.`meta_key` = 'vize_reg_approved'
              AND {$userMetaTable}.`meta_value` = 0
             ";
    $userList = $wpdb->get_results($query);

    if (empty($userList)) return new WP_Error("rest_forbidden", "Not found", ["status" => 404]);

    foreach ($userList as &$user) {
      $user->ID = (int) $user->ID;
    }

    return new WP_REST_Response(
      $userList,
      200
    );
  }
}
