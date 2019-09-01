<?php

/*
Plugin Name: Shadertoy Gallery
Plugin URI: https://reindernijhoff.net/shadertoy/
Description: Creates and update a gallery with Shadertoy shaders based on a query.
Version: 0.1
Author: Reinder Nijhoff
Author URI: https://reindernijhoff.net/
*/

$shadertoy_db_version = '1.0';

function shadertoy_install() {
	global $wpdb;
	global $shadertoy_db_version;

	$table_name = $wpdb->prefix . 'shadertoy';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id varchar(255) NOT NULL,
  		requested datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  		data mediumtext NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'shadertoy_db_version', $shadertoy_db_version );
}

function shadertoy_curl_get_contents($url) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
}

function shadertoy_cleanup_json($json_string) {
	return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json_string);
}

function shadertoy_do_query($key, $query, $sort = '', $timeout = 60*60*24*21) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'shadertoy';

	$timeout += intval(rand(0, 60*60*2)); // prevent that all cached items get invalid at the same time

	$json = '';

	$dbkey = $query . ($sort ? '-' . $sort : '');

	$cached = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %s AND requested > NOW() - %d", $dbkey, $timeout) );
	if ($cached) {
		$json = $cached->data;
	} else {
		$url = 'https://www.shadertoy.com/api/v1/shaders/' . $query . '?key=' . $key . ($sort ? '&sort=' . $sort : '');
		$json = shadertoy_cleanup_json(shadertoy_curl_get_contents($url));

		$wpdb->replace( $table_name, array( 'id' => $dbkey, 'data' => $json ), array( '%s', '%s' ) );
		sleep(0.25); // don't DOS shadertoy!
	}

	$obj = json_decode($json, true);
	return $obj;
}

function shadertoy_list_demo($atts) {
	$a = shortcode_atts( array(
		'username' => false,
		'key' => false,
		'query' => '',
		'sort' => 'newest',
		'columns' => 2
	), $atts );

	if (!$a['key']) {
		return '<p style="color:red;">Error: please provide a Shadertoy API Key.<p>';
	}

	$username = $a['username'];

	$list = shadertoy_do_query($a['key'], 'query/' . urlencode($a['query']), $a['sort'], 60*60*24);
	$results = $list["Results"];

	$html = '<ul class="wp-block-gallery columns-' . $a['columns'] . ' is-cropped">';

	$start = microtime(true);

	foreach ($results as $key => $shader) {
		$shaderdata = shadertoy_do_query($a['key'], $shader);
		$info = $shaderdata['Shader']['info'];

		if (!$username || $username == $info['username']) {
			$html .= shadertoy_layout_shader($info);
		}

		if (microtime(true) - $start > 60) {
			break;
		}
	}


	$html .= '</ul>';	 
    return $html;
}

function shadertoy_layout_shader($info) {
	$html = '<li class="blocks-gallery-item"><figure>';
	$html .= '<a href="https://shadertoy.com/view/' . $info['id'] . '">';
	$html .= '<img src="https://reindernijhoff.net/shadertoythumbs/' . $info['id'] . '.jpg" style="width:100%" alt="' . htmlentities($info['description']) . '">';
	$html .= '<figcaption>' . $info['name'] . '</figcaption>';
	$html .= '</a>';
	$html .= '</figure></li>';

	return $html;
}

register_activation_hook( __FILE__, 'shadertoy_install' );
add_shortcode('shadertoy-list', 'shadertoy_list_demo');