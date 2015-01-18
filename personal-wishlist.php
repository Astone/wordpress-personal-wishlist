<?php defined('ABSPATH') or die("Security error!");
/**
 * Plugin Name: Personal Wishlist
 * Plugin URI: 
 * Description: Add a wishlist to your website and let your guests interact with it.
 * Version: 1.0
 * Author: Bram Stoeller
 * Author URI: http://stoeller.nl
 * Text Domain: personal-wishlist
 * Domain Path: /languages
 * Network: true
 * License: GPLv3
 */

if ( ! class_exists( 'Personal_Wishlist' ) ) {

	global $pwl_db_version;
	$pwl_db_version = '1.0';

	load_plugin_textdomain('personal-wishlist', false, basename(dirname(__FILE__)) . '/language/');

	class Personal_Wishlist {

		/* 
		 * variables 
		 */
		public $plugin_path;
		public $template_url;

		/*
		 * constructor
		 */
		public function __construct() {
			add_action('init', array($this, 'handle_request'));
			add_action('plugins_loaded', array( $this, 'load_text_domain'));
			add_shortcode('wishlist', array($this, 'shortcode_callback'));
			add_action('personal_wishlist_pre_loop', array($this, 'render_header'));
			add_action('personal_wishlist_post_loop', array($this, 'render_footer'));
			add_filter('body_class', array($this, 'body_class'));
			add_action('admin_action_join', array( $this, 'handle_request'));
			register_activation_hook( __FILE__, array($this, 'create_db'));
			register_activation_hook( __FILE__, array($this, 'add_example_item'));
		}

		/**
		 * Make plugin ready for translation
		 *
		 * @access public
		 * @since 1.0
		 * @return none
		 */
		function load_text_domain() {
			load_plugin_textdomain('personal-wishlist', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		}

		/**
		 * Get the plugin path.
		 *
		 * @access public
		 * @since 1.0
		 * @return string
		 */
		function plugin_path() {
			if (! $this->plugin_path) $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
			return $this->plugin_path;
		}

		/**
		 * Create database
		 * @access public
		 * @since 1.0
		 * @return null
		 */
		function create_db() {
			global $wpdb;
			global $pwl_db_version;

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$table_name = $wpdb->prefix . 'pwl_item';
			$charset_collate = $wpdb->get_charset_collate();
				$sql = "CREATE TABLE $table_name (
				id int(11) NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				url varchar(255) DEFAULT NULL,
				price decimal(6,2) DEFAULT NULL,
				done int(1) NOT NULL DEFAULT 0,
				PRIMARY KEY  id (id)
			) $charset_collate;";
			dbDelta( $sql );

			$table_name = $wpdb->prefix . 'pwl_giver';
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE $table_name (
				user_id bigint(20) NOT NULL,
				item_id int(11) NOT NULL,
				PRIMARY KEY (user_id, item_id)
			) $charset_collate;";
			dbDelta( $sql );

			add_option('pwl_db_version', $pwl_db_version);
		}

		/**
		 * Add an example item
		 * @access public
		 * @since 1.0
		 * @return null
		 */
		function add_example_item() {
			global $wpdb;

			$item_table = $wpdb->prefix . 'pwl_item';
			$sql = "SELECT * FROM {$item_table} LIMIT 0, 1;";
			if ($wpdb->get_results($sql)) return;

			$wpdb->insert(
				$item_table,
				array(
					'name' => 'WordPress Coffe Mug',
					'url' => 'http://hellomerch.com/collections/wordpress/products/wp-powered-by-coffee-mug',
					'price' => 7.00
				)
			);
		}

		/**
		 * Get the template url
		 * @access public
		 * @since 1.0
		 * @return string
		 */
		function template_url() {
			if (! $this->template_url) $this->template_url = trailingslashit(apply_filters('pwl_template_url', 'personal-wishlist'));
			return $this->template_url;
		}

		/**
		 * Rendering the plugin
		 *
		 * @access public
		 * @since 1.0
		 * @param  array $atts shortcode attributes
		 * @param  string $content shortcode content, null for this shortcode
		 * @return string
		 */
		function shortcode_callback( $atts, $content = null ) {

			global $wpdb, $item, $item_list;

			$query_id = 'wishlist';

			// Handle GET requests
			do_action('personal_wishlist_handle_request', $query_id);

			// Start output buffer
			ob_start();

			// Query arguments
			$args = array(
				'offset' => 0,
				'number' => 1000000,
				'order_by' => 'name',
				'order' => 'ASC'
			);

			// Allow other themes/plugins to filter the query arguments
			$args = apply_filters('pwl_user_query_args', $args, $query_id);

			/// Get wishlist items 
			$item_table = $wpdb->prefix . 'pwl_item';
			$giver_table = $wpdb->prefix . 'pwl_giver';
			$sql = "SELECT id, name, price, url, count(user_id) as givers, done
				FROM {$item_table}
				LEFT JOIN {$giver_table} ON ({$giver_table}.item_id = {$item_table}.id)
				GROUP BY {$item_table}.id
				ORDER BY {$args['order_by']} {$args['order']}
				LIMIT {$args['offset']}, {$args['number']}";
			$item_list = $wpdb->get_results($sql);

			// Render pre-loop-html
			do_action('personal_wishlist_pre_loop', $query_id);

			// List each item
			if (!empty($item_list))
			{
				foreach($item_list as $item)
				{
					$item->is_giver = $this->user_is_giver($item->id);
					pwl_load_template('wishlist', 'item');
				}
			}
			else
			{
				pwl_load_template('wishlist', 'empty');
			}

			// Render post-loop-html
			do_action('personal_wishlist_post_loop', $query_id);

			// Read the buffer and stop buffering
			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

		/**
		 * Handle actions
		 *
		 * @access public
		 * @since 1.0
		 * @return null
		 */
		function handle_request()
		{
			if (! $item_id = intval($_GET['item_id'])) return;
			if (! $action = $_GET['action']) return;
			$send_mail = false;
			switch ($action)
			{
				case 'join':
					$send_mail = $this->join($item_id);
					break;
				case 'unjoin':
					$send_mail = $this->unjoin($item_id);
					break;
				case 'give':
					$send_mail = $this->give($item_id);
					break;
				case 'ungive':
					$send_mail = $this->ungive($item_id);
					break;
				default:
					return;
			}
			if ($send_mail) $this->send_mail($action, $item_id);
		}

		function join($item_id)
		{
			global $wpdb;
			if ($this->user_is_giver($item_id)) return false;
			$giver_table = $wpdb->prefix . 'pwl_giver';
			$user_id = intval(get_current_user_id());
			$wpdb->insert($giver_table, array('user_id' => $user_id, 'item_id' => $item_id));
			return true;
		}

		function unjoin($item_id)
		{
			global $wpdb;
			if (!$this->user_is_giver($item_id)) return false;
			$giver_table = $wpdb->prefix . 'pwl_giver';
			$user_id = intval(get_current_user_id());
			$wpdb->delete($giver_table, array('user_id' => $user_id, 'item_id' => $item_id));
			return true;
		}

		function give($item_id)
		{
			global $wpdb;
			$this->join($item_id);
			$item_table = $wpdb->prefix . 'pwl_item';
			return $wpdb->update($item_table, array('done' => 1), array('id' => $item_id));
		}

		function ungive($item_id)
		{
			global $wpdb;
			if ($this->user_is_only_giver($item_id)) $this->unjoin($item_id);
			$item_table = $wpdb->prefix . 'pwl_item';
			return $wpdb->update($item_table, array('done' => 0), array('id' => $item_id));
		}

		function user_is_giver($item_id) {
			global $wpdb;
			$giver_table = $wpdb->prefix . 'pwl_giver';
			$user_id = intval(get_current_user_id());
			$sql = "SELECT * FROM {$giver_table} WHERE item_id = {$item_id} AND user_id = {$user_id}";
			return $wpdb->get_results($sql) ? true : false;
		}

		function user_is_only_giver($item_id) {
			global $wpdb;
			if (! $this->user_is_giver($item_id)) return false;
			$giver_table = $wpdb->prefix . 'pwl_giver';
			$user_id = intval(get_current_user_id());
			$sql = "SELECT * FROM {$giver_table} WHERE item_id = {$item_id} AND user_id != {$user_id}";
			return $wpdb->get_results($sql) ? false : true;
		}

		function send_mail($action, $item_id)
		{
			global $wpdb;

			$item_table = $wpdb->prefix . 'pwl_item';
			$sql = "SELECT * FROM {$item_table} WHERE id = {$item_id}";
			$item = array_pop($wpdb->get_results($sql));

			$user_table = $wpdb->prefix . 'users';
			$user_id = intval(get_current_user_id());
			$sql = "SELECT display_name AS name, user_email AS mail FROM {$user_table} WHERE id = {$user_id}";
			$user = array_pop($wpdb->get_results($sql));

			$giver_table = $wpdb->prefix . 'pwl_giver';
			$sql = "SELECT display_name AS name, user_email AS mail FROM {$user_table} INNER JOIN {$giver_table} ON ({$user_table}.id = user_id) WHERE item_id = {$item_id} AND {$user_table}.id != {$user_id}";
			$user_list = $wpdb->get_results($sql);

			$page_link = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $_SERVER['REDIRECT_URL'];
			switch ($action)
			{
				case 'join':
					$body  = "Beste {$user->name},\n\nHierbij de bevestiging dat je meedoet met {$item->name}.\n\n";
					$body .= $user_list ? "In de CC van deze mail zie je wie er nog meer meedoen" : "Je bent de eerste die zich hiervoor heeft aangemeld";
					$body .= " en je ontvangt een e-mail zodra iemand anders zich aan- of afmeldt.\n\n";
					$body .= "- {$item->name}: {$item->url}\n- Overzicht: {$page_link}\n- Niet meer meedoen: {$page_link}?item_id={$item->id}&action=unjoin";
					break;
				case 'unjoin':
					$body  = "Beste {$user->name},\n\nHierbij de bevestiging dat je NIET meer meedoet met {$item->name}.\n\n";
					$body .= "Je ontvangt hier vanaf nu geen e-mail berichten meer over.\n\n";
					$body .= "- {$item->name}: {$item->url}\n- Overzicht: {$page_link}\n- Toch weer meedoen: {$page_link}?item_id={$item->id}&action=join";
					break;
				case 'give':
					$body  = "Beste {$user->name},\n\nHierbij de bevestiging dat ";
					$body .= $user_list ? "jullie {$item->name} gaan geven.\n\n" : "je {$item->name} gaat geven.\n\n";
					$body .= $user_list ? "In de CC van deze mail zie je wie er nog meer meedoen." : "";
					$body .= "Andere mensen kunnen nu niet meer meedoen via de website.\n\n";
					$body .= "- {$item->name}: {$item->url}\n- Overzicht: {$page_link}\n- Niet meer geven: {$page_link}?item_id={$item->id}&action=dont-give";
					break;
				case 'ungive':
					$body  = "Beste {$user->name},\n\nHierbij de bevestiging dat ";
					$body .= $user_list ? "jullie {$item->name} NIET meer gaan geven.\n\n" : "je {$item->name} NIET meer gaat geven.\n\n";
					$body .= $user_list ? "In de CC van deze mail zie je wie er nog meer meededen. " : "Je ontvangt hier vanaf nu geen e-mail berichten meer over.\n\n";
					$body .= $user_list ? "Iedereen staat nog steeds op de mailing list, dus je ontvangt een e-mail zodra iemand zich aan- of afmeldt voor {$item->name}.\n\n" : "";
					$body .= "- {$item->name}: {$item->url}\n- Overzicht: {$page_link}\n";
					$body .= $user_list ? "- Niet meer meedoen: {$page_link}?item_id={$item->id}&action=unjoin\n" : "- Toch weer meedoen: {$page_link}?item_id={$item->id}&action=join\n";
					$body .= "- Toch weer geven: {$page_link}?item_id={$item->id}&action=give";
					break;
				default:
					return;
			}
			$headers = "From: \"Bram's Feestje\" <noreply@bramsfeestje.nl>\r\n";
			if ($user_list)
			{
				$headers .= "Cc: {$user->name} <{$user->mail}>";
				foreach ($user_list as $cc) $headers .= ", {$cc->name} <{$cc->mail}>";
				$headers .= "\r\n";
			}
			wp_mail("{$user->name} <{$user->mail}>", $item->name, $body, $headers);
		}

		/**
		 * Render the header template
		 *
		 * @access public
		 * @since 1.0
		 * @return null
		 */
		function render_header() {
			pwl_load_template('wishlist', 'header');
		}

		/**
		 * Render the footer template
		 *
		 * @access public
		 * @since 1.0
		 * @return null
		 */
		function render_footer() {
			pwl_load_template('wishlist', 'footer');
		}

		/**
		 * Add body class
		 *
		 * @access public
		 * @since 1.0
		 * @param  array $c all generated WordPress body classes
		 * @return array
		 */
		function body_class( $c ) {
			if( has_wishlist() ) $c[] = 'wishlist';
			return $c;
		}
	}
}

global $personal_wishlist;
$personal_wishlist = new Personal_Wishlist();

/**
 * Get template part
 *
 * @access public
 * @since 1.0
 * @param string $domain
 * @param string $name
 * @return null
 */
function pwl_load_template( $domain, $name ) {
	global $personal_wishlist;
	$template = '';

	// Look in yourtheme/domain-name.php and yourtheme/personal-wishlist/domain-name.php
	$candidates = array("{$domain}-{$name}.php", "{$personal_wishlist->template_url()}{$domain}-{$name}.php");

	$template = locate_template($candidates);

	if (!$template && file_exists("{$personal_wishlist->plugin_path()}/templates/{$domain}-{$name}.php"))
		$template = "{$personal_wishlist->plugin_path()}/templates/{$domain}-{$name}.php";

	if ($template) load_template( $template, false );
}

/**
 * Is this a wishlist post/page?
 *
 * @access public
 * @since 1.0
 * @return boolean
 */
function has_wishlist()
{
	global $post;
	$is_list = is_singular() && isset($post->post_content) && has_shortcode( $post->post_content, 'wishlist' );
	return apply_filters( 'wpl_is_wishlist', $listing );
}
