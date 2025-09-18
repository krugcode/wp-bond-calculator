<?php

class BC_Database
{
    public static function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Transfer Costs Table
        $transfer_costs_table = $wpdb->prefix . 'bc_transfer_costs';
        $transfer_sql = "CREATE TABLE $transfer_costs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            purchase_price decimal(15,2) NOT NULL,
            attorney_fee decimal(15,2) NOT NULL,
            vat decimal(15,2) NOT NULL,
            transfer_duty decimal(15,2) NOT NULL,
            deeds_office_fee decimal(15,2) NOT NULL,
            total_cost decimal(15,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY purchase_price (purchase_price)
        ) $charset_collate;";

        // Bond Costs Table
        $bond_costs_table = $wpdb->prefix . 'bc_bond_costs';
        $bond_sql = "CREATE TABLE $bond_costs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            bond_amount decimal(15,2) NOT NULL,
            attorney_fee decimal(15,2) NOT NULL,
            vat decimal(15,2) NOT NULL,
            deeds_office_fee decimal(15,2) NOT NULL,
            total_cost decimal(15,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY bond_amount (bond_amount)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($transfer_sql);
        dbDelta($bond_sql);
    }

    public static function get_transfer_costs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_transfer_costs';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY purchase_price ASC");
    }

    public static function clear_transfer_costs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_transfer_costs';
        return $wpdb->query("TRUNCATE TABLE $table");
    }

    public static function insert_transfer_costs($costs)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_transfer_costs';

        foreach ($costs as $cost) {
            $wpdb->replace($table, [
                'purchase_price' => $cost['purchase_price'],
                'attorney_fee' => $cost['attorney_fee'],
                'vat' => $cost['vat'],
                'transfer_duty' => $cost['transfer_duty'],
                'deeds_office_fee' => $cost['deeds_office_fee'],
                'total_cost' => $cost['total_cost']
            ], ['%f', '%f', '%f', '%f', '%f', '%f']);
        }
    }

    // Bond Costs Methods
    public static function get_bond_costs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_bond_costs';
        return $wpdb->get_results("SELECT * FROM $table ORDER BY bond_amount ASC");
    }

    public static function clear_bond_costs()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_bond_costs';
        return $wpdb->query("TRUNCATE TABLE $table");
    }

    public static function insert_bond_costs($costs)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_bond_costs';

        foreach ($costs as $cost) {
            $wpdb->replace($table, [
                'bond_amount' => $cost['bond_amount'],
                'attorney_fee' => $cost['attorney_fee'],
                'vat' => $cost['vat'],
                'deeds_office_fee' => $cost['deeds_office_fee'],
                'total_cost' => $cost['total_cost']
            ], ['%f', '%f', '%f', '%f', '%f']);
        }
    }

    // Calculate transfer cost for a given purchase price
    public static function calculate_transfer_cost($purchase_price)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_transfer_costs';

        // Find the closest cost tier
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE purchase_price <= %f ORDER BY purchase_price DESC LIMIT 1",
            $purchase_price
        ));

        return $result;
    }

    // Calculate bond cost for a given bond amount
    public static function calculate_bond_cost($bond_amount)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'bc_bond_costs';

        // Find the closest cost tier
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE bond_amount <= %f ORDER BY bond_amount DESC LIMIT 1",
            $bond_amount
        ));

        return $result;
    }
}

class BC_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Bond Calculator',
            'Bond Calculator',
            'manage_options',
            'bond-calculator',
            array($this, 'admin_page'),
            'dashicons-calculator',
            26
        );
    }

    public function admin_page()
    {
        include BC_PLUGIN_PATH . 'admin/dashboard.php';
    }

    private function is_vite_dev_server_running()
    {
        $vite_dev_url = 'http://localhost:5173';
        $context = stream_context_create([
            'http' => [
                'timeout' => 1,
                'method' => 'HEAD'
            ]
        ]);
        return @file_get_contents($vite_dev_url, false, $context) !== false;
    }

    public function enqueue_admin_scripts($hook)
    {
        // Only load on our admin page
        if ('toplevel_page_bond-calculator' !== $hook) {
            return;
        }

        if ($this->is_vite_dev_server_running()) {
            // Development mode - load from Vite dev server
            wp_enqueue_script(
                'bc-admin-js',
                'http://localhost:5173/src/admin.js',
                array(),
                null,
                true
            );
        } else {
            // Production mode - load built assets
            $js_file = BC_PLUGIN_PATH . 'dist/admin.js';
            $css_file = BC_PLUGIN_PATH . 'dist/app.css';

            if (file_exists($js_file) && file_exists($css_file)) {
                wp_enqueue_script(
                    'bc-admin-js',
                    BC_PLUGIN_URL . 'dist/admin.js',
                    array(),
                    filemtime($js_file),
                    true
                );
                wp_enqueue_style(
                    'bc-admin-css',
                    BC_PLUGIN_URL . 'dist/app.css',
                    array(),
                    filemtime($css_file)
                );
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Bond Calculator: Please run <code>npm run build</code> to build the admin assets.</p></div>';
                });
                return;
            }
        }

        // Pass data to JavaScript
        wp_localize_script('bc-admin-js', 'bcAdmin', array(
            'apiUrl' => rest_url('bond-calculator/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isDev' => $this->is_vite_dev_server_running()
        ));
    }
}

class BC_API
{
    public function register_routes()
    {
        // Sample calculator data (existing functionality)
        register_rest_route('bond-calculator/v1', '/calculator-data', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_calculator_data'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // PDF Settings endpoints (existing functionality)
        register_rest_route('bond-calculator/v1', '/pdf-settings', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_pdf_settings'),
                'permission_callback' => array($this, 'check_admin_permissions')
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_pdf_settings'),
                'permission_callback' => array($this, 'check_admin_permissions')
            )
        ));

        register_rest_route('bond-calculator/v1', '/pdf-example', array(
            'methods' => 'GET',
            'callback' => array($this, 'generate_example_pdf'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // Transfer Costs endpoints
        register_rest_route('bond-calculator/v1', '/transfer-costs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_transfer_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route('bond-calculator/v1', '/upload-transfer-costs', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_transfer_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route('bond-calculator/v1', '/download-transfer-costs', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_transfer_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        // Bond Costs endpoints
        register_rest_route('bond-calculator/v1', '/bond-costs', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_bond_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route('bond-calculator/v1', '/upload-bond-costs', array(
            'methods' => 'POST',
            'callback' => array($this, 'upload_bond_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));

        register_rest_route('bond-calculator/v1', '/download-bond-costs', array(
            'methods' => 'GET',
            'callback' => array($this, 'download_bond_costs'),
            'permission_callback' => array($this, 'check_admin_permissions')
        ));
    }

    public function check_admin_permissions()
    {
        return current_user_can('manage_options');
    }

    // Existing methods (calculator data, PDF settings)
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
        return rest_ensure_response($sample_data);
    }

    public function get_pdf_settings($request)
    {
        $api_key = get_option('bc_api2pdf_key', '');
        $brevo_api_key = get_option('bc_brevo_api_key', '');
        $template_html = get_option('bc_pdf_template', '');
        $sender_email = get_option('bc_pdf_sender_email', '');
        $sender_name = get_option('bc_pdf_sender_name', '');
        $subject_line = get_option('bc_pdf_subject_line', 'Your [CALCULATOR_TYPE] Calculator Results');

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'api_key' => $api_key,
                'brevo_api_key' => $brevo_api_key,
                'template_html' => $template_html,
                'sender_email' => $sender_email,
                'sender_name' => $sender_name,
                'subject_line' => $subject_line
            )
        ));
    }

    public function save_pdf_settings($request)
    {
        $json = $request->get_json_params();

        if (isset($json['api_key'])) {
            update_option('bc_api2pdf_key', sanitize_text_field($json['api_key']));
        }

        if (isset($json['brevo_api_key'])) {
            update_option('bc_brevo_api_key', sanitize_text_field($json['brevo_api_key']));
        }

        if (isset($json['template_html'])) {
            update_option('bc_pdf_template', wp_kses_post($json['template_html']));
        }

        if (isset($json['sender_email'])) {
            update_option('bc_pdf_sender_email', sanitize_email($json['sender_email']));
        }

        if (isset($json['sender_name'])) {
            update_option('bc_pdf_sender_name', sanitize_text_field($json['sender_name']));
        }

        if (isset($json['subject_line'])) {
            update_option('bc_pdf_subject_line', sanitize_text_field($json['subject_line']));
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Settings saved successfully'
        ));
    }

    public function generate_example_pdf($request)
    {
        // Implementation for PDF generation (existing functionality)
        return rest_ensure_response(array(
            'success' => true,
            'message' => 'PDF generation not fully implemented yet'
        ));
    }

    // Transfer Costs Methods
    public function get_transfer_costs($request)
    {
        $costs = BC_Database::get_transfer_costs();
        return rest_ensure_response($costs);
    }

    public function upload_transfer_costs($request)
    {
        try {
            $files = $request->get_file_params();
            if (empty($files['csv_file'])) {
                return new WP_Error('no_file', 'No CSV file uploaded', array('status' => 400));
            }

            $file = $files['csv_file'];

            // Validate file type
            if ($file['type'] !== 'text/csv' && !str_ends_with($file['name'], '.csv')) {
                return new WP_Error('invalid_file', 'Please upload a valid CSV file', array('status' => 400));
            }

            // Parse CSV
            $csv_data = $this->parse_transfer_costs_csv($file['tmp_name']);

            if (is_wp_error($csv_data)) {
                return $csv_data;
            }

            // Clear existing data and insert new data
            BC_Database::clear_transfer_costs();
            BC_Database::insert_transfer_costs($csv_data);

            // Return success response with count and data
            $updated_costs = BC_Database::get_transfer_costs();

            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Transfer costs uploaded successfully',
                'count' => count($csv_data),
                'data' => $updated_costs
            ));

        } catch (Exception $e) {
            return new WP_Error('upload_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function download_transfer_costs($request)
    {
        try {
            $costs = BC_Database::get_transfer_costs();

            if (empty($costs)) {
                return new WP_Error('no_data', 'No transfer costs found', array('status' => 404));
            }

            // Generate CSV content
            $csv_content = $this->generate_transfer_costs_csv($costs);

            return new WP_REST_Response($csv_content, 200, array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="transfer-costs.csv"'
            ));

        } catch (Exception $e) {
            return new WP_Error('download_error', $e->getMessage(), array('status' => 500));
        }
    }

    // Bond Costs Methods
    public function get_bond_costs($request)
    {
        $costs = BC_Database::get_bond_costs();
        return rest_ensure_response($costs);
    }

    public function upload_bond_costs($request)
    {
        try {
            $files = $request->get_file_params();
            if (empty($files['csv_file'])) {
                return new WP_Error('no_file', 'No CSV file uploaded', array('status' => 400));
            }

            $file = $files['csv_file'];

            // Validate file type
            if ($file['type'] !== 'text/csv' && !str_ends_with($file['name'], '.csv')) {
                return new WP_Error('invalid_file', 'Please upload a valid CSV file', array('status' => 400));
            }

            // Parse CSV
            $csv_data = $this->parse_bond_costs_csv($file['tmp_name']);

            if (is_wp_error($csv_data)) {
                return $csv_data;
            }

            BC_Database::clear_bond_costs();
            BC_Database::insert_bond_costs($csv_data);

            $updated_costs = BC_Database::get_bond_costs();

            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Bond costs uploaded successfully',
                'count' => count($csv_data),
                'data' => $updated_costs
            ));

        } catch (Exception $e) {
            return new WP_Error('upload_error', $e->getMessage(), array('status' => 500));
        }
    }

    public function download_bond_costs($request)
    {
        try {
            $costs = BC_Database::get_bond_costs();

            if (empty($costs)) {
                return new WP_Error('no_data', 'No bond costs found', array('status' => 404));
            }

            $csv_content = $this->generate_bond_costs_csv($costs);

            return new WP_REST_Response($csv_content, 200, array(
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="bond-costs.csv"'
            ));

        } catch (Exception $e) {
            return new WP_Error('download_error', $e->getMessage(), array('status' => 500));
        }
    }

    private function parse_transfer_costs_csv($file_path)
    {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'CSV file not found', array('status' => 400));
        }

        $csv_data = array();
        $row = 1;

        if (($handle = fopen($file_path, "r")) !== false) {
            $headers = fgetcsv($handle, 1000, ",");

            $required_headers = ['purchase_price', 'attorney_fee', 'vat', 'transfer_duty', 'deeds_office_fee', 'total_cost'];
            $header_map = array();

            foreach ($headers as $index => $header) {
                $clean_header = strtolower(trim($header));
                $clean_header = str_replace([' ', '-'], '_', $clean_header);

                if (in_array($clean_header, $required_headers)) {
                    $header_map[$clean_header] = $index;
                }
            }

            foreach ($required_headers as $required) {
                if (!isset($header_map[$required])) {
                    return new WP_Error('invalid_headers', "Missing required column: $required", array('status' => 400));
                }
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $row++;

                if (empty(array_filter($data))) {
                    continue;
                }

                try {
                    $cost_entry = array();

                    foreach ($required_headers as $header) {
                        $value = isset($data[$header_map[$header]]) ? $data[$header_map[$header]] : '';

                        $clean_value = $this->clean_currency_value($value);

                        if ($clean_value === false) {
                            return new WP_Error('invalid_data', "Invalid numeric value in row $row, column $header: $value", array('status' => 400));
                        }

                        $cost_entry[$header] = $clean_value;
                    }

                    $csv_data[] = $cost_entry;

                } catch (Exception $e) {
                    return new WP_Error('parse_error', "Error parsing row $row: " . $e->getMessage(), array('status' => 400));
                }
            }
            fclose($handle);
        }

        if (empty($csv_data)) {
            return new WP_Error('empty_data', 'No valid data found in CSV file', array('status' => 400));
        }

        return $csv_data;
    }

    private function parse_bond_costs_csv($file_path)
    {
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', 'CSV file not found', array('status' => 400));
        }

        $csv_data = array();
        $row = 1;

        if (($handle = fopen($file_path, "r")) !== false) {
            $headers = fgetcsv($handle, 1000, ",");

            $required_headers = ['bond_amount', 'attorney_fee', 'vat', 'deeds_office_fee', 'total_cost'];
            $header_map = array();

            foreach ($headers as $index => $header) {
                $clean_header = strtolower(trim($header));
                $clean_header = str_replace([' ', '-'], '_', $clean_header);

                if (in_array($clean_header, $required_headers)) {
                    $header_map[$clean_header] = $index;
                }
            }

            foreach ($required_headers as $required) {
                if (!isset($header_map[$required])) {
                    return new WP_Error('invalid_headers', "Missing required column: $required", array('status' => 400));
                }
            }

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $row++;

                if (empty(array_filter($data))) {
                    continue;
                }

                try {
                    $cost_entry = array();

                    foreach ($required_headers as $header) {
                        $value = isset($data[$header_map[$header]]) ? $data[$header_map[$header]] : '';

                        $clean_value = $this->clean_currency_value($value);

                        if ($clean_value === false) {
                            return new WP_Error('invalid_data', "Invalid numeric value in row $row, column $header: $value", array('status' => 400));
                        }

                        $cost_entry[$header] = $clean_value;
                    }

                    $csv_data[] = $cost_entry;

                } catch (Exception $e) {
                    return new WP_Error('parse_error', "Error parsing row $row: " . $e->getMessage(), array('status' => 400));
                }
            }
            fclose($handle);
        }

        if (empty($csv_data)) {
            return new WP_Error('empty_data', 'No valid data found in CSV file', array('status' => 400));
        }

        return $csv_data;
    }

    private function generate_transfer_costs_csv($costs)
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['Purchase Price', 'Attorney Fee', 'VAT', 'Transfer Duty', 'Deeds Office Fee', 'Total Cost']);

        foreach ($costs as $cost) {
            fputcsv($output, [
                number_format($cost->purchase_price, 2, '.', ''),
                number_format($cost->attorney_fee, 2, '.', ''),
                number_format($cost->vat, 2, '.', ''),
                number_format($cost->transfer_duty, 2, '.', ''),
                number_format($cost->deeds_office_fee, 2, '.', ''),
                number_format($cost->total_cost, 2, '.', '')
            ]);
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }

    private function generate_bond_costs_csv($costs)
    {
        $output = fopen('php://temp', 'r+');

        fputcsv($output, ['Bond Amount', 'Attorney Fee', 'VAT', 'Deeds Office Fee', 'Total Cost']);

        foreach ($costs as $cost) {
            fputcsv($output, [
                number_format($cost->bond_amount, 2, '.', ''),
                number_format($cost->attorney_fee, 2, '.', ''),
                number_format($cost->vat, 2, '.', ''),
                number_format($cost->deeds_office_fee, 2, '.', ''),
                number_format($cost->total_cost, 2, '.', '')
            ]);
        }

        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        return $csv_content;
    }


    private function clean_currency_value($value)
    {
        if (is_numeric($value)) {
            return floatval($value);
        }

        $clean_value = preg_replace('/[R\s,]/', '', $value);

        if (is_numeric($clean_value)) {
            return floatval($clean_value);
        }

        return false;
    }
}
