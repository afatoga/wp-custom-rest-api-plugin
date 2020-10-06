<?php
/**
 * Plugin.
 *
 * @package af-restserver
 * @wordpress-plugin
 *
 * Plugin Name:     af_restserver
 * Description:     REST API for a custom theme
 * Author:          Afatoga
 * Author URL:      https://leoweb.cz
 * Version:         0.1
 * Domain Path:     /
 */


/**
 * API Settings
 */

class AF_restserver extends WP_REST_Controller
{
  private $logged_in;
  private $user;

  public function __construct()
  {
    $this->user = wp_get_current_user();
    $this->logged_in = is_user_logged_in();
  }

  //The namespace and version for the REST SERVER
  var $my_namespace = 'af_restserver/v';
  var $my_version   = '1';

  public function register_routes()
  {
    $namespace = $this->my_namespace . $this->my_version;

    register_rest_route(
      $namespace,
      '/register',
      array(
        array(
            'methods'         => WP_REST_Server::CREATABLE,
            'callback'        => array($this, 'af_register_new_user')
          )
      )
    );
    register_rest_route(
      $namespace,
      '/get_items',
      array(
        array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array($this, 'af_get_items')
        ,  'permission_callback'   => array($this, 'get_latest_post_permission')
        ),
      )
    );
  }

  public function get_latest_post_permission()
  {
    if (!current_user_can('read')) {
      return new WP_Error('rest_forbidden', 'Access forbidden', array('status' => 401));
    }

    // This approach blocks the endpoint operation. You could alternatively do this by an un-blocking approach, by returning false here and changing the permissions check.
    return true;
  }
  
  public function af_get_items(WP_REST_Request $request)
  {
    $payload = $request->get_params();
    return rest_ensure_response(["hello", $payload]);
  }

  public function af_register_new_user(WP_REST_Request $request)
  {
    if (!$this->user) return wp_send_json_error('400');

    $payload = $request->get_params();
    $email = filter_var($payload["username"], FILTER_VALIDATE_EMAIL);
    if (!$email) return wp_send_json_error('Invalid email', 400);

    register_new_user($email, $email);
    return new WP_REST_Response(
        ['message' => "success"],
        201
    );

    // $firstName = filter_var($payload["name"], FILTER_SANITIZE_STRING);
    // $surname = filter_var($payload["surname"], FILTER_SANITIZE_STRING);
    // $title = filter_var($payload["title"], FILTER_SANITIZE_STRING);

    // $firstName = (is_string($firstName)) ? $firstName : $editedUser->name;
    // $surname = (is_string($surname)) ? $surname : $editedUser->surname;
    // $title = (is_string($title)) ? $title : $editedUser->title;

    // $newData["name"] = ($firstName === "") ? null : $firstName;
    // $newData["surname"] = ($surname === "") ? null : $surname;
    // $newData["title"] = ($title === "") ? null : $title;

    // $host = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=UTF8";
    // $connect = new PDO($host, DB_USER, DB_PASSWORD);

    // $qFirstName = "name = :name,";
    // $qSurname = "surname = :surname,";
    // $qTitle = "title = :title,";

    // $query = "UPDATE af_users SET ";
    // $query .= rtrim($qFirstName . $qSurname . $qTitle, ",");
    // $query .= " WHERE ID = :ID";

    // $statement = $connect->prepare($query);
    // $statement->execute($newData);

    // $result = $statement->rowCount();
    // if ($result > 0) {
    //   return rest_ensure_response($newData);
    // } else {
    //   return wp_send_json_error('400');
    // }
  }

//   public function has_user_elearning_access(WP_REST_Request $request)
//   {
//     $host = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=UTF8";
//     $connect = new PDO($host, DB_USER, DB_PASSWORD);

//     $user_id = get_current_user_id();
//     $af_elearningCourseItemId = get_page_by_path('online-kurz-termin', OBJECT, 'af_courseitem')->ID;

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

add_action( 'rest_api_init', function () {
    $af_restserver = new AF_restserver();
    $af_restserver->register_routes();
});
