<?php

namespace Afatoga\Api;

use WP_Error;
use WP_REST_Response;
use WP_REST_Request;
use Afatoga\Services\ReCaptcha;
use Afatoga\Services\WP_Mail;

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
      "/user_registration_request",
      [
        [
          "methods"         => "POST",
          "callback"        => [$this, "aa_respond_to_user_registration_request"],
          "permission_callback" => [$this, "aa_user_can_edit_pages"],
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

  public function aa_user_can_edit_pages()
  {
    if (!$this->logged_in || !$this->user->has_cap( 'edit_pages' )) {
      return new WP_Error("rest_forbidden", "Access forbidden", ["status" => 401]);
    }
    return true;
  }

  public function aa_submit_registration_form(WP_REST_Request $request)
  {

    $payload = $request->get_params();

    $reCaptcha = new ReCaptcha();
    $isReCaptchaValid = $reCaptcha->verifyUserToken($payload["g-recaptcha-response"]);


    $email = filter_var(trim($payload["email"]), FILTER_VALIDATE_EMAIL);
    if (!$email || username_exists($email) || email_exists($email)) return new WP_Error("invalid_email", "Tento email je obsazen nebo není validní", ["status" => 400, "payload_item"=>"email"]);
    if (!$isReCaptchaValid) return new WP_Error("invalid_recaptcha", "Recaptcha není validní", ["status" => 400]);

    $password = filter_var($payload["password"], FILTER_SANITIZE_STRING);
    $password_retyped = filter_var($payload["password_retyped"], FILTER_SANITIZE_STRING);

    if ($password !== $password_retyped) return new WP_Error("invalid_password", "Hesla se neshodují", ["status" => 400, "payload_item"=>"password_retyped"]);

    $email = strtolower($email);
    $first_name = filter_var(trim($payload["first_name"]), FILTER_SANITIZE_STRING);
    $last_name = filter_var(trim($payload["last_name"]), FILTER_SANITIZE_STRING);
    $department = filter_var(trim($payload["department"]), FILTER_SANITIZE_STRING);

    $userId = wp_insert_user([
      'user_login' => $email,
      'user_email' => $email,
      'first_name' => $first_name,
      'last_name' => $last_name,
      'user_pass' => $password,
      'show_admin_bar_front' => 'false',
      'role' => 'subscriber'
    ]);

    if ($userId) {
      $approved = strpos($email, "@uhkt.cz") ? 1 : 0;
      update_user_meta($userId, 'vize_reg_approved', $approved);

      if ($department) update_user_meta($userId, 'vize_department', $department);
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



  public function aa_respond_to_user_registration_request(WP_REST_Request $request)
  {
    $payload = $request->get_params();
    $action = filter_var($payload["action"], FILTER_SANITIZE_STRING);
    $user_id = filter_var($payload["user_id"], FILTER_VALIDATE_INT);

    $user = new \WP_User($user_id);

    if (!$user_id || !$user) return new WP_Error("rest_not_found", "Not found", ["status" => 404]);

    $user_id = (int) $user_id;

    if ($action === "confirm") {
      update_user_meta($user_id, 'vize_reg_approved', 1);
      $email = WP_Mail::init()
      ->to($user->user_email)
      ->from('VIZE 2030 <info@vize2030.cz>')
      ->subject('Registrace povolena')
      ->template(ABSPATH . 'wp-content/plugins/af-restapi/app/Sources/Email_Templates/registration_confirmed.php', [
         "user_email"=>$user->user_email
      ])
      ->send();

    } else if ($action === "cancel") {
      require_once(ABSPATH.'wp-admin/includes/user.php');
      wp_delete_user($user_id);
    }
    
    return new WP_REST_Response(
      ["message" => "success"],
      200
    );
  }

  public function aa_get_user_list()
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

    if (empty($userList)) return new WP_Error("rest_not_found", "Seznam žádostí je prázdný", ["status" => 404]);

    foreach ($userList as &$user) {
      $userId = (int) $user->user_id;
      //$user->ID = $userId;
      $user->first_name = (string) get_user_meta($userId, "first_name", true);
      $user->last_name = (string) get_user_meta($userId, "last_name", true);
      $user->department = (string) get_user_meta($userId, "vize_department", true);
      $user->user_registered = date("d. m. Y", strtotime($user->user_registered));
    }

    return new WP_REST_Response(
      $userList,
      200
    );
  }
}
