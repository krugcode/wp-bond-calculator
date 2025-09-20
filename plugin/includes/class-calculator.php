<?php

class BC_Calculator
{
    public function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function init()
    {
        // Register shortcodes
        add_shortcode('transfer_cost_calculator', array($this, 'transfer_cost_shortcode'));
        add_shortcode('bond_cost_calculator', array($this, 'bond_cost_shortcode'));
    }

    public function enqueue_frontend_scripts()
    {
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'transfer_cost_calculator') || has_shortcode($post->post_content, 'bond_cost_calculator'))) {
            $js_file = BC_PLUGIN_PATH . 'dist/calculator.js';
            $css_file = BC_PLUGIN_PATH . 'dist/app.css';

            if (file_exists($js_file)) {
                wp_enqueue_script(
                    'bc-frontend',
                    BC_PLUGIN_URL . 'dist/calculator.js',
                    array(),
                    filemtime($js_file),
                    true
                );

                add_filter('script_loader_tag', function ($tag, $handle) {
                    if ($handle === 'bc-frontend') {
                        return str_replace('<script ', '<script type="module" ', $tag);
                    }
                    return $tag;
                }, 10, 2);

            } else {
                if (current_user_can('manage_options')) {
                    add_action('wp_footer', function () {
                        echo '<script>console.warn("Bond Calculator: Frontend JavaScript not found. Run: npm run build");</script>';
                    });
                }
                return;
            }

            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'bc-frontend-css',
                    BC_PLUGIN_URL . 'dist/app.css',
                    array(),
                    filemtime($css_file)
                );
            }

            wp_localize_script('bc-frontend', 'bcAjax', array(
                'apiUrl' => rest_url('bond-calculator/v1/'),
                'nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }

    public function transfer_cost_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 'transfer-cost-calculator-' . uniqid()
        ), $atts);

        return '<div id="' . esc_attr($atts['id']) . '" class="bc-transfer-calculator"></div>';
    }

    public function bond_cost_shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 'bond-cost-calculator-' . uniqid()
        ), $atts);

        return '<div id="' . esc_attr($atts['id']) . '" class="bc-bond-calculator"></div>';
    }

    public function register_routes()
    {
        // NEW: Combined calculation endpoint
        register_rest_route('bond-calculator/v1', '/calculate-combined-cost', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_combined_cost'),
            'permission_callback' => '__return_true'
        ));

        // Existing separate endpoints (keep for backward compatibility)
        register_rest_route('bond-calculator/v1', '/calculate-transfer-cost', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_transfer_cost'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('bond-calculator/v1', '/calculate-bond-cost', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_bond_cost'),
            'permission_callback' => '__return_true'
        ));

        // Generate PDF
        register_rest_route('bond-calculator/v1', '/generate-pdf', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_pdf'),
            'permission_callback' => '__return_true'
        ));

        // Send email
        register_rest_route('bond-calculator/v1', '/send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_email'),
            'permission_callback' => '__return_true'
        ));
    }

    // NEW: Combined calculation method
    public function calculate_combined_cost($request)
    {
        $purchase_price = floatval($request->get_param('purchase_price'));
        $bond_amount = floatval($request->get_param('bond_amount'));

        if ($purchase_price <= 0) {
            return new WP_Error('invalid_price', 'Invalid purchase price', array('status' => 400));
        }

        // Calculate transfer cost (required)
        $transfer_cost_data = BC_Database::calculate_transfer_cost($purchase_price);
        if (!$transfer_cost_data) {
            return new WP_Error('no_transfer_data', 'No transfer cost data found for this price range', array('status' => 404));
        }

        $transfer_breakdown = $this->get_transfer_cost_breakdown($transfer_cost_data, $purchase_price);

        $result = array(
            'success' => true,
            'purchase_price' => $purchase_price,
            'cost_data' => $transfer_cost_data,
            'breakdown' => $transfer_breakdown,
            'total' => $transfer_cost_data->total_cost
        );

        // Calculate bond cost if provided
        if ($bond_amount > 0) {
            $bond_cost_data = BC_Database::calculate_bond_cost($bond_amount);
            if ($bond_cost_data) {
                $bond_breakdown = $this->get_bond_cost_breakdown($bond_cost_data, $bond_amount);

                $result['bond_amount'] = $bond_amount;
                $result['bond_cost_data'] = $bond_cost_data;
                $result['bond_breakdown'] = $bond_breakdown;
                $result['bond_total'] = $bond_cost_data->total_cost;
                $result['grand_total'] = $transfer_cost_data->total_cost + $bond_cost_data->total_cost;
            }
        }

        if (!isset($result['grand_total'])) {
            $result['grand_total'] = $result['total'];
        }

        return rest_ensure_response($result);
    }

    public function calculate_transfer_cost($request)
    {
        $purchase_price = floatval($request->get_param('purchase_price'));

        if ($purchase_price <= 0) {
            return new WP_Error('invalid_price', 'Invalid purchase price', array('status' => 400));
        }

        $cost_data = BC_Database::calculate_transfer_cost($purchase_price);

        if (!$cost_data) {
            return new WP_Error('no_data', 'No cost data found for this price range', array('status' => 404));
        }

        $breakdown = $this->get_transfer_cost_breakdown($cost_data, $purchase_price);

        return rest_ensure_response(array(
            'success' => true,
            'purchase_price' => $purchase_price,
            'cost_data' => $cost_data,
            'breakdown' => $breakdown,
            'total' => $cost_data->total_cost
        ));
    }

    public function calculate_bond_cost($request)
    {
        $bond_amount = floatval($request->get_param('bond_amount'));

        if ($bond_amount <= 0) {
            return new WP_Error('invalid_amount', 'Invalid bond amount', array('status' => 400));
        }

        $cost_data = BC_Database::calculate_bond_cost($bond_amount);

        if (!$cost_data) {
            return new WP_Error('no_data', 'No cost data found for this bond amount', array('status' => 404));
        }

        $breakdown = $this->get_bond_cost_breakdown($cost_data, $bond_amount);

        return rest_ensure_response(array(
            'success' => true,
            'bond_amount' => $bond_amount,
            'cost_data' => $cost_data,
            'breakdown' => $breakdown,
            'total' => $cost_data->total_cost
        ));
    }



    public function generate_pdf($request)
    {
        try {
            $type = $request->get_param('type');
            $data = $request->get_param('data');
            $breakdown = $request->get_param('breakdown');
            $bond_breakdown = $request->get_param('bond_breakdown');

            if (!$type || !$data) {
                return new WP_Error('missing_data', 'Missing required data', array('status' => 400));
            }

            // Get PDF template
            $template = get_option('bc_pdf_template', $this->get_default_pdf_template());

            // Replace placeholders in template
            $html = $this->populate_pdf_template($template, $type, $data, $breakdown, $bond_breakdown);

            // Get API2PDF settings
            $api_key = get_option('bc_api2pdf_key');
            if (!$api_key) {
                return new WP_Error('no_api_key', 'API2PDF key not configured', array('status' => 500));
            }

            // Generate PDF via API2PDF
            $pdf_url = $this->generate_pdf_via_api2pdf($html, $api_key);

            if (is_wp_error($pdf_url)) {
                return $pdf_url;
            }

            return rest_ensure_response(array(
                'success' => true,
                'pdf_url' => $pdf_url
            ));

        } catch (Exception $e) {
            return new WP_Error('pdf_error', $e->getMessage(), array('status' => 500));
        }
    }

    private function populate_pdf_template($template, $type, $data, $breakdown, $bond_breakdown = null)
    {
        $replacements = array(
            '[DATE]' => date('d/m/Y')
        );

        // Handle combined type (both transfer and bond)
        if ($type === 'combined' && isset($data['bond_amount']) && $data['bond_amount'] > 0) {
            // Both transfer and bond sections visible
            $replacements['[TRANSFER_SECTION_START]'] = '';
            $replacements['[TRANSFER_SECTION_END]'] = '';
            $replacements['[BOND_SECTION_START]'] = '';
            $replacements['[BOND_SECTION_END]'] = '';

            $replacements['[TRANSFER_AMOUNT]'] = 'R' . number_format($data['purchase_price'], 2);
            $replacements['[TRANSFER_COSTS]'] = $this->format_breakdown_for_pdf($breakdown);
            $replacements['[BOND_AMOUNT]'] = 'R' . number_format($data['bond_amount'], 2);
            $replacements['[BOND_COSTS]'] = $bond_breakdown ? $this->format_breakdown_for_pdf($bond_breakdown) : '';
            $replacements['[TOTAL]'] = 'R' . number_format($data['grand_total'], 2);

        } elseif ($type === 'combined' || $type === 'transfer') {
            // Transfer only (bond section hidden)
            $replacements['[TRANSFER_SECTION_START]'] = '';
            $replacements['[TRANSFER_SECTION_END]'] = '';
            $replacements['[BOND_SECTION_START]'] = '<!-- ';
            $replacements['[BOND_SECTION_END]'] = ' -->';

            $replacements['[TRANSFER_AMOUNT]'] = 'R' . number_format($data['purchase_price'], 2);
            $replacements['[TRANSFER_COSTS]'] = $this->format_breakdown_for_pdf($breakdown);
            $replacements['[BOND_AMOUNT]'] = '';
            $replacements['[BOND_COSTS]'] = '';
            $replacements['[TOTAL]'] = 'R' . number_format($data['total'], 2);

        } else {
            // Bond only (transfer section hidden)
            $replacements['[TRANSFER_SECTION_START]'] = '<!-- ';
            $replacements['[TRANSFER_SECTION_END]'] = ' -->';
            $replacements['[BOND_SECTION_START]'] = '';
            $replacements['[BOND_SECTION_END]'] = '';

            $replacements['[TRANSFER_AMOUNT]'] = '';
            $replacements['[TRANSFER_COSTS]'] = '';
            $replacements['[BOND_AMOUNT]'] = 'R' . number_format($data['bond_amount'], 2);
            $replacements['[BOND_COSTS]'] = $this->format_breakdown_for_pdf($breakdown);
            $replacements['[TOTAL]'] = 'R' . number_format($data['total'], 2);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function format_breakdown_for_pdf($breakdown)
    {
        $html = '';

        foreach ($breakdown as $section_key => $section) {
            if ($section_key === 'government_costs') {
                $html .= '<strong>Government Costs:</strong><br>';
                foreach ($section as $item) {
                    if (isset($item['label']) && isset($item['amount'])) {
                        $html .= $item['label'] . ' - R' . number_format($item['amount'], 2) . '<br>';
                    }
                }
            } elseif ($section_key === 'attorney_costs') {
                $html .= '<strong>Attorneys Costs:</strong><br>';
                foreach ($section as $item) {
                    if (isset($item['label']) && isset($item['amount'])) {
                        $html .= $item['label'] . ' - R' . number_format($item['amount'], 2) . '<br>';
                    }
                }
            } elseif ($section_key === 'vat' && isset($section['label']) && isset($section['amount'])) {
                $html .= $section['label'] . ' - R' . number_format($section['amount'], 2) . '<br>';
            }
        }

        return $html;
    }

    private function generate_pdf_via_api2pdf($html, $api_key)
    {
        $payload = array(
            'html' => $html,
            'options' => array(
                'landscape' => false,
                'printBackground' => true,
                'format' => 'A4'
            )
        );

        $response = wp_remote_post('https://v2.api2pdf.com/chrome/pdf/html', array(
            'headers' => array(
                'Authorization' => trim($api_key),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error('api2pdf_http_error', 'API2PDF HTTP Error: ' . $response_code, array('status' => $response_code));
        }

        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON from API2PDF', array('status' => 500));
        }

        // API2PDF returns "Success" (capital S) and "FileUrl"
        if (!isset($data['Success']) || !$data['Success']) {
            $error = isset($data['Error']) ? $data['Error'] : 'Unknown API2PDF error';
            return new WP_Error('api2pdf_failed', 'API2PDF failed: ' . $error, array('status' => 500));
        }

        if (!isset($data['FileUrl'])) {
            return new WP_Error('no_pdf_url', 'No PDF URL returned from API2PDF', array('status' => 500));
        }

        return $data['FileUrl'];
    }

    public function send_email($request)
    {
        try {
            $email = sanitize_email($request->get_param('email'));
            $type = $request->get_param('type');
            $pdf_url = $request->get_param('pdf_url');

            if (!is_email($email)) {
                return new WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
            }

            if (!$pdf_url) {
                return new WP_Error('missing_pdf', 'PDF URL required', array('status' => 400));
            }

            // Get Brevo API key
            $brevo_key = get_option('bc_brevo_api_key');
            if (!$brevo_key) {
                return new WP_Error('no_brevo_key', 'Brevo API key not configured', array('status' => 500));
            }

            // Send email via Brevo
            $result = $this->send_email_via_brevo($email, $type, $pdf_url, $brevo_key);

            if (is_wp_error($result)) {
                return $result;
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => 'Email sent successfully'
            ));

        } catch (Exception $e) {
            return new WP_Error('email_error', $e->getMessage(), array('status' => 500));
        }
    }

    private function get_transfer_cost_breakdown($cost_data, $purchase_price)
    {
        return array(
            'government_costs' => array(
                'transfer_duty' => array(
                    'label' => 'Transfer Duty',
                    'amount' => $cost_data->transfer_duty
                ),
                'deeds_office_fee' => array(
                    'label' => 'Deeds Office Fee',
                    'amount' => $cost_data->deeds_office_fee
                )
            ),
            'attorney_costs' => array(
                'attorney_fee' => array(
                    'label' => 'Attorney Fee',
                    'amount' => $cost_data->attorney_fee
                ),
                'additional_fees' => array(
                    'label' => 'To Transaction Fee',
                    'amount' => 200.00
                ),
                'doc_generation' => array(
                    'label' => 'Electronic Doc Generation Fee',
                    'amount' => 200.00
                ),
                'rates_clearance' => array(
                    'label' => 'To Rates Clearance Certificate fee',
                    'amount' => 350.00
                ),
                'electronic_rates' => array(
                    'label' => 'To Electronic Rates fee',
                    'amount' => 442.00
                ),
                'deeds_search' => array(
                    'label' => 'Deeds Office Search Fee',
                    'amount' => 250.00
                ),
                'fica_verification' => array(
                    'label' => 'Fica Verification Fee',
                    'amount' => 500.00
                ),
                'post_petties' => array(
                    'label' => 'Post & Petties',
                    'amount' => 2000.00
                )
            ),
            'vat' => array(
                'label' => 'VAT',
                'amount' => $cost_data->vat
            )
        );
    }

    private function get_bond_cost_breakdown($cost_data, $bond_amount)
    {
        return array(
            'government_costs' => array(
                'deeds_office_fee' => array(
                    'label' => 'Deeds Office Fee',
                    'amount' => $cost_data->deeds_office_fee
                )
            ),
            'attorney_costs' => array(
                'attorney_fee' => array(
                    'label' => 'Attorney Fee',
                    'amount' => $cost_data->attorney_fee
                ),
                'deeds_search' => array(
                    'label' => 'Deeds Office Search Fee',
                    'amount' => 250.00
                ),
                'electronic_instruction' => array(
                    'label' => 'Electronic Instruction & Generation fee',
                    'amount' => 1750.00
                ),
                'post_petties' => array(
                    'label' => 'Post & Petties',
                    'amount' => 2000.00
                )
            ),
            'vat' => array(
                'label' => 'VAT',
                'amount' => $cost_data->vat
            )
        );
    }







    private function send_email_via_brevo($email, $type, $pdf_url, $api_key)
    {
        $calculator_types = array(
            'transfer' => 'Transfer Cost',
            'bond' => 'Bond Cost',
            'combined' => 'Transfer & Bond Cost'
        );

        $calculator_type = isset($calculator_types[$type]) ? $calculator_types[$type] : 'Calculator';
        $subject_template = get_option('bc_pdf_subject_line', 'Your [CALCULATOR_TYPE] Calculator Results');
        $subject = str_replace('[CALCULATOR_TYPE]', $calculator_type, $subject_template);

        $response = wp_remote_post('https://api.brevo.com/v3/smtp/email', array(
            'headers' => array(
                'api-key' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'to' => array(array('email' => $email)),
                'subject' => $subject,
                'htmlContent' => $this->get_email_template($type, $pdf_url),
                'sender' => array(
                    'name' => get_option('bc_pdf_sender_name', 'Bond Calculator'),
                    'email' => get_option('bc_pdf_sender_email', get_option('admin_email'))
                )
            ))
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 201) {
            return new WP_Error('email_send_failed', 'Failed to send email', array('status' => 500));
        }

        return true;
    }

    private function get_email_template($type, $pdf_url)
    {
        $calculator_types = array(
            'transfer' => 'Transfer Cost',
            'bond' => 'Bond Cost',
            'combined' => 'Transfer & Bond Cost'
        );

        $calculator_type = isset($calculator_types[$type]) ? $calculator_types[$type] : 'Calculator';

        return "
        <h2>Your {$calculator_type} Calculator Results</h2>
        <p>Thank you for using our calculator. Your results are attached as a PDF.</p>
        <p><a href='{$pdf_url}' target='_blank'>Download PDF Results</a></p>
        <p>Best regards,<br>The Team</p>
        ";
    }

    private function get_default_pdf_template()
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Calculator Results</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .header { text-align: center; margin-bottom: 30px; }
                .section { margin-bottom: 20px; }
                .total { font-weight: bold; font-size: 18px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>[DATE]</h1>
                <h2>Transfer Bond Costs</h2>
            </div>
            
            [TRANSFER_SECTION_START]
            <div class="section">
                <h3>TRANSFER COST ON: [TRANSFER_AMOUNT]</h3>
                [TRANSFER_COSTS]
            </div>
            [TRANSFER_SECTION_END]
            
            [BOND_SECTION_START]
            <div class="section">
                <h3>BOND COST ON: [BOND_AMOUNT]</h3>
                [BOND_COSTS]
            </div>
            [BOND_SECTION_END]
            
            <div class="total">
                Total: [TOTAL]
            </div>
            
            <div style="margin-top: 40px; font-size: 12px;">
                <p><strong>PROVISION MUST BE MADE FOR THE FOLLOWING AMOUNTS:</strong></p>
                <ul>
                    <li>Bank admin and initiation fees of approximately R6,037.50</li>
                    <li>Levies for up to 12 months (normally 3 months)</li>
                    <li>Transfer of an Exclusive Use Area amount of approximately R2,000.00 per Exclusive Use Area</li>
                    <li>Insurance Certificate for Sectional Title transfers in the sum of approx. R750.00</li>
                    <li>Please note with Sectional Title that there are additional charges for extra Units and Exclusive Use Areas</li>
                </ul>
            </div>
        </body>
        </html>';
    }
}

new BC_Calculator();
