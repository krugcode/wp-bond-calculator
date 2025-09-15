<?php

/**
 * Plugin Name: Bond Calculator
 * Description: Simple bond calculator with Svelte admin interface
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BC_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include main classes
require_once BC_PLUGIN_PATH . 'includes/class-admin.php';

class BondCalculator
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init()
    {
        // Initialize admin interface
        new BC_Admin();

        // Add REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_routes'));

        // Add shortcode for frontend
        add_shortcode('bond_calculator', array($this, 'frontend_shortcode'));
    }

    public function register_api_routes()
    {
        register_rest_route('bond-calculator/v1', '/calculator-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_calculator_data'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }

    public function get_calculator_data($request)
    {
        $sample_data = array(
            array(
                'date' => '12 Jun 2025',
                'email' => 'test@udit.co.za',
                'type' => 'transfer',
                'amount' => 500000,
                'fee' => 15000
            ),
            array(
                'date' => '10 Jun 2025',
                'email' => 'test@identipet.com',
                'type' => 'bond',
                'amount' => 1000000,
                'fee' => 25000
            )
        );

        return rest_ensure_response(array(
            'success' => true,
            'data' => $sample_data
        ));
    }

    public function check_admin_permissions($request)
    {
        return current_user_can('manage_options');
    }

    public function frontend_shortcode($atts)
    {
        return '<div id="bond-calculator-frontend">Frontend calculator will go here</div>';
    }

    public function activate()
    {
        // Create database tables, set default options, etc.
        $this->create_tables();
    }

    private function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'bond_calculations';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            amount decimal(15,2) NOT NULL,
            fee decimal(15,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
new BondCalculator();
