<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @package    MoceanAPI Abandoned Carts
 * @subpackage MoceanAPI Abandoned Carts/includes
 * @author     Micro Ocean Technologies
 */
class MoceanAPI{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 *  
	 * @access   protected
	 * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 *  
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 *  
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 *  
	 */
	public function __construct(){

		$this->plugin_name = MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG;
		$this->version = MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER;

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 *  
	 * @access   private
	 */
	private function load_dependencies(){

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-moceanapi-abandoned-carts-loader.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-moceanapi-abandoned-carts-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-moceanapi-abandoned-carts-public.php';

		$this->loader = new MoceanAPI_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 *  
	 * @access   private
	 */
	private function define_admin_hooks(){

		$admin = new MoceanAPI_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $admin, 'moceanapi_abandoned_carts_menu', 35 ); //Creates admin menu
		$this->loader->add_action( 'admin_head', $admin, 'menu_abandoned_count' );
		$this->loader->add_action( 'admin_head', $admin, 'register_admin_screen_options_tab' );
		$this->loader->add_action( 'admin_head', $admin, 'save_page_options' ); //Saving Screen options
		$this->loader->add_action( 'plugins_loaded', $admin, 'check_current_plugin_version' );
		$this->loader->add_action( 'init', $admin, 'moceanapi_abandoned_carts_text_domain' ); //Adding language support
		$this->loader->add_filter( 'moceanapi_abandoned_carts_remove_empty_carts_hook', $admin, 'delete_empty_carts' );
		$this->loader->add_filter( 'cron_schedules', $admin, 'additional_cron_intervals' ); //Ads a filter to set new interval for Wordpress cron function
		$this->loader->add_filter( 'update_option_moceanapi_abandoned_carts_notification_frequency', $admin, 'notification_sendout_interval_update' );
		$this->loader->add_filter( 'update_option_moceanapi_abandoned_carts_sms_notification_frequency', $admin, 'sms_notification_sendout_interval_update' );
		$this->loader->add_action( 'admin_notices', $admin, 'display_wp_cron_warnings' ); //Outputing warnings if any of the WP Cron events are note scheduled or if WP Cron is disabled
		$this->loader->add_action( 'moceanapi_abandoned_carts_notification_sendout_hook', $admin, 'send_email' ); //Hooks into Wordpress cron event to launch function for sending out e-mails
		$this->loader->add_action( 'moceanapi_abandoned_carts_sms_auto_sendout_hook', $admin, 'smsAbandonedCartSendMessage' );
		$this->loader->add_filter( 'woocommerce_billing_fields', $admin, 'lift_checkout_email_field', 10, 1 ); //Moves email field in the checkout higher to capture more abandoned carts
		$this->loader->add_action( 'woocommerce_new_order', $admin, 'handle_order', 30 ); //Hook fired once a new order is created via Checkout process. Order is created as soon as user is taken to payment page. No matter if he pays or not
		$this->loader->add_action( 'woocommerce_thankyou', $admin, 'handle_order', 30 ); //Hooks into Thank you page to delete a row with a user who completes the checkout (Backup version if first hook does not get triggered after an WooCommerce order gets created)
		$this->loader->add_action( 'profile_update', $admin, 'reset_abandoned_cart' ); //Handles clearing of abandoned cart in case a registered user changes his account data like Name, Surname, Location etc.	
		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 *  
	 * @access   private
	 */
	private function define_public_hooks(){

		$public = new MoceanAPI_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_before_checkout_form', $public, 'add_additional_scripts_on_checkout' ); //Adds additional functionality only to Checkout page
		$this->loader->add_action( 'wp_ajax_nopriv_moceanapi_abandoned_carts_save', $public, 'save_cart' ); //Handles data saving using Ajax after any changes made by the user on the E-mail or Phone field in Checkout form
		$this->loader->add_action( 'wp_ajax_moceanapi_abandoned_carts_save', $public, 'save_cart' ); //Handles data saving using Ajax after any changes made by the user on the E-mail field for Logged in users
		$this->loader->add_action( 'woocommerce_add_to_cart', $public, 'save_cart', 200 ); //Handles data saving if an item is added to shopping cart, 200 = priority set to run the function last after all other functions are finished
		$this->loader->add_action( 'woocommerce_cart_actions', $public, 'save_cart', 200 ); //Handles data updating if a cart is updated. 200 = priority set to run the function last after all other functions are finished
		$this->loader->add_action( 'woocommerce_cart_item_removed', $public, 'save_cart', 200 ); //Handles data updating if an item is removed from cart. 200 = priority set to run the function last after all other functions are finished
		$this->loader->add_filter( 'woocommerce_checkout_fields', $public, 'restore_input_data', 1 ); //Restoring previous user input in Checkout form
		$this->loader->add_action( 'wp_footer', $public, 'display_exit_intent_form' ); //Outputing the exit intent form in the footer of the page
		$this->loader->add_action( 'wp_ajax_nopriv_insert_exit_intent', $public, 'display_exit_intent_form' ); //Outputing the exit intent form in case if Ajax Add to Cart button pressed if the user is not logged in
		$this->loader->add_action( 'wp_ajax_insert_exit_intent', $public, 'display_exit_intent_form' ); //Outputing the exit intent form in case if Ajax Add to Cart button pressed if the user is logged in
		$this->loader->add_action( 'wp_ajax_nopriv_remove_exit_intent', $public, 'remove_exit_intent_form' ); //Checking if we have an empty cart in case of Ajax action
		$this->loader->add_action( 'wp_ajax_remove_exit_intent', $public, 'remove_exit_intent_form' ); //Checking if we have an empty cart in case of Ajax action if the user is logged in
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 *  
	 */
	public function run(){
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name(){
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(){
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(){
		return $this->version;
	}

	

}