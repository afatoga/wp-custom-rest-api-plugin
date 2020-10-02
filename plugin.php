<?php

/**
 * Plugin.
 *
 * @package aa-restserver
 * @wordpress-plugin
 *
 * Plugin Name:     aa_restserver
 * Description:     REST API for a custom theme
 * Author:          aa
 * Author URL:      https://leoweb.cz
 * Version:         0.1
 * Domain Path:     /
 */


/**
 * API Settings
 */

class AA_restserver extends WP_REST_Controller
{
  private $logged_in;
  private $user;

  public function __construct()
  {
    $this->user = wp_get_current_user();
    $this->logged_in = is_user_logged_in();
  }

  //The namespace and version for the REST SERVER
  var $my_namespace = 'aa_restserver/v';
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
          'callback'        => array($this, 'aa_register_new_user')
        )
      )
    );
    register_rest_route(
      $namespace,
      '/get_items',
      array(
        array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array($this, 'aa_get_items')
          // ,  'permission_callback'   => array($this, 'get_latest_post_permission')
        ),
      )
    );
  }

  public function get_latest_post_permission()
  {
    if (!current_user_can('edit_posts')) {
      return new WP_Error('rest_forbidden', 'Access forbidden', array('status' => 401));
    }

    // This approach blocks the endpoint operation. You could alternatively do this by an un-blocking approach, by returning false here and changing the permissions check.
    return true;
  }

  public function aa_get_items(WP_REST_Request $request)
  {
    $payload = $request->get_params();
    return rest_ensure_response(["hello", $payload]);
  }

  public function aa_register_new_user(WP_REST_Request $request)
  {
    if (!$this->user) return wp_send_json_error('400');

    $payload = $request->get_params();
    $email = filter_var($payload["email"], FILTER_VALIDATE_EMAIL);
    if (!$email) return wp_send_json_error('Invalid email', 400);

    register_new_user($email, $email);
    return rest_ensure_response(new WP_REST_Response(
      array(
        'status' => 201,
        'message' => "success",
        'data' => ["message" => "hello", "inside" => [1, 2, 3]]
      )
    ));
  }
}

add_action('rest_api_init', function () {
  $aa_restserver = new AA_restserver();
  $aa_restserver->register_routes();
});
