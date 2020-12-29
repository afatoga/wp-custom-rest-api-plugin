<?php

use Afatoga\AutoLoader;
use Afatoga\Api\RestServer;

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
 * Autoloader
 */
require plugin_dir_path( __FILE__ ).'/app/AutoLoader.php';
$loader = new AutoLoader();
$loader->register();
$loader->addNamespace( 'Afatoga', plugin_dir_path( __FILE__ ).'/app' ); 

/**
 * WP API hook
 */

add_action("rest_api_init", function () {
  $af_restserver = new RestServer();
  $af_restserver->register_routes();
});

/**
 * Register the /wp-json/af_restserver/v1/product endpoint not to request authentication
 */

add_filter( 'jwt_auth_whitelist', function ( $endpoints ) {
  return [
      '/wp-json/af_restserver/v1/product',
      '/wp-json/af_restserver/v1/get_productlist'
  ];
} );

/**
 * Register the /wp-json/af_restserver/v1/get_productlist endpoint so it will be cached.
 */

function af_add_productlist_endpoint( $allowed_endpoints ) {
    if ( ! isset( $allowed_endpoints[ 'af_restserver/v1' ] ) || 
         ! in_array( 'get_productlist', $allowed_endpoints[ 'af_restserver/v1' ] ) ) {
        $allowed_endpoints[ 'af_restserver/v1' ][] = 'get_productlist';
    }
    return $allowed_endpoints;
}
//add_filter( 'wp_rest_cache/allowed_endpoints', 'af_add_productlist_endpoint', 10, 1);