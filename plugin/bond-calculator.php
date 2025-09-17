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

        // PDF Settings endpoints
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

    public function get_pdf_settings($request)
    {
        $api_key = get_option('bc_pdf_api_key', '');
        $template = get_option('bc_pdf_template', $this->get_default_template());

        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'api_key' => $api_key,
                'template_html' => $template
            )
        ));
    }

    public function save_pdf_settings($request)
    {
        $json = $request->get_json_params();

        if (!isset($json['api_key']) || !isset($json['template_html'])) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Missing required fields'
            ));
        }

        update_option('bc_pdf_api_key', sanitize_text_field($json['api_key']));
        update_option('bc_pdf_template', wp_kses_post($json['template_html']));

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Settings saved successfully'
        ));
    }

    public function generate_example_pdf($request)
    {
        $api_key = get_option('bc_pdf_api_key', '');
        $template = get_option('bc_pdf_template', $this->get_default_template());

        if (empty($api_key)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => 'Please configure your API2PDF API key first'
            ));
        }

        // Sample data for example
        $sample_data = array(
            'transfer_amount' => 'R300,000.00',
            'bond_amount' => 'R4,000,000.00',
            'attorney_fee' => 'R10,880.00',
            'total_fee' => 'R89,191.30',
            'date' => date('d/m/Y'),
            'government_costs' => 'R0.00',
            'deeds_office_fee' => 'R721.00',
            'to_transaction_fee' => 'R200.00',
            'electronic_doc_fee' => 'R200.00',
            'rates_clearance_fee' => 'R350.00',
            'electronic_rates_fee' => 'R442.00',
            'deeds_search_fee' => 'R259.00',
            'fica_verification_fee' => 'R500.00',
            'post_petties' => 'R2,000.00',
            'vat_amount' => 'R2,233.30',
            'sub_total' => 'R17,772.30',
            'bond_deeds_office_fee' => 'R2,281.00',
            'bond_attorney_fee' => 'R56,120.00',
            'bond_deeds_search_fee' => 'R259.00',
            'bond_electronic_fee' => 'R1,750.00',
            'bond_post_petties' => 'R2,000.00',
            'bond_vat' => 'R9,018.00',
            'bond_sub_total' => 'R71,419.00'
        );

        $html = $this->replace_template_placeholders($template, $sample_data);
        $pdf_url = $this->generate_pdf_with_api2pdf($html, $api_key);

        if ($pdf_url) {
            // Download the PDF and return it
            $pdf_content = file_get_contents($pdf_url);
            if ($pdf_content) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="bond-calculator-example.pdf"');
                echo $pdf_content;
                exit;
            }
        }

        return rest_ensure_response(array(
            'success' => false,
            'message' => 'Failed to generate PDF'
        ));
    }

    private function replace_template_placeholders($template, $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    private function generate_pdf_with_api2pdf($html, $api_key)
    {
        $api_url = 'https://v2.api2pdf.com/wkhtml/pdf/html';

        $data = array(
            'html' => $html,
            'options' => array(
                'orientation' => 'Portrait',
                'page-size' => 'A4',
                'margin-top' => '0.75in',
                'margin-bottom' => '0.75in',
                'margin-left' => '0.75in',
                'margin-right' => '0.75in',
                'print-media-type' => true,
                'disable-smart-shrinking' => true,
                'zoom' => '1.0'
            ),
            'fileName' => 'bond-calculator-' . date('Y-m-d-H-i-s') . '.pdf'
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => $api_key
            ),
            'body' => json_encode($data),
            'timeout' => 45
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['FileUrl'])) {
            return $result['FileUrl'];
        }

        return false;
    }

    private function get_default_template()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Transfer Bond Costs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #000;
            margin: 0;
            padding: 0;
        }
        
        .container {
            width: 100%;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ccc;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            background-color: #8B4513;
            color: white;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            line-height: 60px;
            margin: 0 auto 10px auto;
        }
        
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 5px;
        }
        
        .company-tagline {
            font-size: 9px;
            color: #8B4513;
            font-style: italic;
            margin-bottom: 15px;
        }
        
        .title-row {
            width: 100%;
            margin-bottom: 20px;
        }
        
        .title-row table {
            width: 100%;
        }
        
        .document-title {
            font-size: 16px;
            font-weight: bold;
            text-align: left;
        }
        
        .document-date {
            font-size: 11px;
            font-weight: bold;
            text-align: right;
        }
        
        .section {
            margin-bottom: 20px;
        }
        
        .section-header {
            background-color: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            border-left: 3px solid #8B4513;
        }
        
        .category-header {
            font-weight: bold;
            margin: 10px 0 5px 0;
            font-size: 11px;
        }
        
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        
        .cost-table td {
            padding: 2px 5px;
            font-size: 10px;
            vertical-align: top;
        }
        
        .cost-table .label-col {
            width: 65%;
            text-align: left;
        }
        
        .cost-table .amount-col {
            width: 35%;
            text-align: right;
            font-weight: bold;
        }
        
        .subtotal-row td {
            border-top: 1px solid #333;
            font-weight: bold;
            padding-top: 4px;
        }
        
        .total-section {
            margin: 20px 0;
            text-align: center;
        }
        
        .total-box {
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            padding: 12px;
            display: inline-block;
            min-width: 200px;
        }
        
        .total-text {
            font-size: 14px;
            font-weight: bold;
        }
        
        .provision-section {
            margin: 25px 0;
            padding: 12px;
            background-color: #fffacd;
            border: 1px solid #ddd;
        }
        
        .provision-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 11px;
        }
        
        .provision-list {
            margin: 8px 0 0 15px;
            padding: 0;
        }
        
        .provision-list li {
            margin-bottom: 4px;
            font-size: 10px;
            line-height: 1.4;
        }
        
        .footer-notes {
            margin-top: 20px;
            font-size: 9px;
            color: #555;
            border-top: 1px solid #ddd;
            padding-top: 12px;
        }
        
        .footer-notes p {
            margin: 0 0 6px 0;
        }
        
        .disclaimer {
            font-weight: bold;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-logo">dvh</div>
            <div class="company-name">DYKES VAN HEERDEN GROUP OF COMPANIES</div>
            <div class="company-tagline">professionals striving for excellence</div>
        </div>

        <div class="title-row">
            <table>
                <tr>
                    <td class="document-title">Transfer Bond Costs</td>
                    <td class="document-date">{{date}}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header">TRANSFER COST ON: {{transfer_amount}}</div>
            
            <div class="category-header">Government Costs</div>
            <table class="cost-table">
                <tr>
                    <td class="label-col">Transfer Duty</td>
                    <td class="amount-col">{{government_costs}}</td>
                </tr>
                <tr>
                    <td class="label-col">Deeds Office Fee</td>
                    <td class="amount-col">{{deeds_office_fee}}</td>
                </tr>
            </table>

            <div class="category-header">Attorneys Costs</div>
            <table class="cost-table">
                <tr>
                    <td class="label-col">Attorney Fee</td>
                    <td class="amount-col">{{attorney_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">To Transaction Fee</td>
                    <td class="amount-col">{{to_transaction_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">Electronic Doc Generation Fee</td>
                    <td class="amount-col">{{electronic_doc_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">To Rates Clearance Certificate fee</td>
                    <td class="amount-col">{{rates_clearance_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">To Electronic Rates fee</td>
                    <td class="amount-col">{{electronic_rates_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">Deeds Office Search Fee</td>
                    <td class="amount-col">{{deeds_search_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">Fica Verification Fee</td>
                    <td class="amount-col">{{fica_verification_fee}}</td>
                </tr>
                <tr>
                    <td class="label-col">Post & Petties</td>
                    <td class="amount-col">{{post_petties}}</td>
                </tr>
                <tr>
                    <td class="label-col">VAT</td>
                    <td class="amount-col">{{vat_amount}}</td>
                </tr>
                <tr class="subtotal-row">
                    <td class="label-col">Sub Total</td>
                    <td class="amount-col">{{sub_total}}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-header">BOND COST ON: {{bond_amount}}</div>
            
            <div class="category-header">Government Costs</div>
            <table class="cost-table">
                <tr>
                    <td class="label-col">Deeds Office Fee</td>
                    <td class="amount-col">R2,281.00</td>
                </tr>
            </table>

            <div class="category-header">Attorneys Costs</div>
            <table class="cost-table">
                <tr>
                    <td class="label-col">Attorney Fee</td>
                    <td class="amount-col">R56,120.00</td>
                </tr>
                <tr>
                    <td class="label-col">Deeds Office Search Fee</td>
                    <td class="amount-col">R259.00</td>
                </tr>
                <tr>
                    <td class="label-col">Electronic Instruction & Generation fee</td>
                    <td class="amount-col">R1,750.00</td>
                </tr>
                <tr>
                    <td class="label-col">Post & Petties</td>
                    <td class="amount-col">R2,000.00</td>
                </tr>
                <tr>
                    <td class="label-col">VAT</td>
                    <td class="amount-col">R9,018.00</td>
                </tr>
                <tr class="subtotal-row">
                    <td class="label-col">Sub Total</td>
                    <td class="amount-col">R71,419.00</td>
                </tr>
            </table>
        </div>

        <div class="total-section">
            <div class="total-box">
                <div class="total-text">Total: {{total_fee}}</div>
            </div>
        </div>

        <div class="provision-section">
            <div class="provision-title">PROVISION MUST BE MADE FOR THE FOLLOWING AMOUNTS:-</div>
            <ul class="provision-list">
                <li>Bank\'s admin and initiation fees of approximately R6,037.50</li>
                <li>Levies for up to 12 months (normally 3 months)</li>
                <li>Transfer of an Exclusive Use Area amount of approximately R2,000.00 per Exclusive Use Area</li>
                <li>Insurance Certificate for Sectional Title transfers in the sum of approx. R750.00</li>
                <li>Please note with Sectional Title that there are additional charges for extra Units and Exclusive Use Areas</li>
            </ul>
        </div>

        <div class="footer-notes">
            <p><strong>Please note fees here are calculated up to R500,000,000.00</strong></p>
            <p>For quotes in excess of R500,000,000.00, and for more accurate calculations, please contact us.</p>
            <p class="disclaimer">Disclaimer: All estimated calculations done here are provided for general information purposes only and do not constitute professional advice. We do not warrant the correctness of this information. For more accurate calculations, please contact us.</p>
        </div>
    </div>
</body>
</html>';
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

        // Create templates directory
        $templates_dir = BC_PLUGIN_PATH . 'templates';
        if (!file_exists($templates_dir)) {
            wp_mkdir_p($templates_dir);
        }

        // Set default template if not exists
        if (!get_option('bc_pdf_template')) {
            update_option('bc_pdf_template', $this->get_default_template());
        }
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
