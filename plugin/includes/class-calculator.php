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
        // Only enqueue on pages with our shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'transfer_cost_calculator') || has_shortcode($post->post_content, 'bond_cost_calculator'))) {
            wp_enqueue_script(
                'bc-frontend',
                BC_PLUGIN_URL . 'dist/calculator.js',
                array(),
                '1.0.0',
                true
            );

            wp_enqueue_style(
                'bc-frontend',
                BC_PLUGIN_URL . 'dist/calculator.css',
                array(),
                '1.0.0'
            );

            // Localize script with REST API data
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
        // Calculate transfer cost
        register_rest_route('bond-calculator/v1', '/calculate-transfer-cost', array(
            'methods' => 'POST',
            'callback' => array($this, 'calculate_transfer_cost'),
            'permission_callback' => '__return_true'
        ));

        // Calculate bond cost
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

        // Add detailed breakdown
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

        // Add detailed breakdown
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
            $type = $request->get_param('type'); // 'transfer' or 'bond'
            $data = $request->get_param('data');
            $breakdown = $request->get_param('breakdown');

            if (!$type || !$data) {
                return new WP_Error('missing_data', 'Missing required data', array('status' => 400));
            }

            // Get PDF template
            $template = get_option('bc_pdf_template', $this->get_default_pdf_template());

            // Replace placeholders in template
            $html = $this->populate_pdf_template($template, $type, $data, $breakdown);

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

    private function populate_pdf_template($template, $type, $data, $breakdown)
    {
        $replacements = array(
            '[DATE]' => date('d/m/Y')
        );

        if ($type === 'transfer') {
            $replacements['[TRANSFER_SECTION_START]'] = '';
            $replacements['[TRANSFER_SECTION_END]'] = '';
            $replacements['[BOND_SECTION_START]'] = '<!-- ';
            $replacements['[BOND_SECTION_END]'] = ' -->';
            $replacements['[TRANSFER_AMOUNT]'] = 'R' . number_format($data['purchase_price'], 2);
            $replacements['[TRANSFER_COSTS]'] = $this->format_breakdown_for_pdf($breakdown, 'transfer');
            $replacements['[BOND_AMOUNT]'] = '';
            $replacements['[BOND_COSTS]'] = '';
            $replacements['[TOTAL]'] = 'R' . number_format($data['total'], 2);
        } else {
            $replacements['[TRANSFER_SECTION_START]'] = '<!-- ';
            $replacements['[TRANSFER_SECTION_END]'] = ' -->';
            $replacements['[BOND_SECTION_START]'] = '';
            $replacements['[BOND_SECTION_END]'] = '';
            $replacements['[TRANSFER_AMOUNT]'] = '';
            $replacements['[TRANSFER_COSTS]'] = '';
            $replacements['[BOND_AMOUNT]'] = 'R' . number_format($data['bond_amount'], 2);
            $replacements['[BOND_COSTS]'] = $this->format_breakdown_for_pdf($breakdown, 'bond');
            $replacements['[TOTAL]'] = 'R' . number_format($data['total'], 2);
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function format_breakdown_for_pdf($breakdown, $type)
    {
        $html = '';

        foreach ($breakdown as $section_key => $section) {
            if ($section_key === 'government_costs') {
                $html .= '<strong>Government Costs:</strong><br>';
            } elseif ($section_key === 'attorney_costs') {
                $html .= '<strong>Attorneys Costs:</strong><br>';
            }

            if (is_array($section)) {
                foreach ($section as $item) {
                    if (isset($item['label']) && isset($item['amount'])) {
                        $html .= $item['label'] . ' - R' . number_format($item['amount'], 2) . '<br>';
                    }
                }
            } else {
                $html .= $section['label'] . ' - R' . number_format($section['amount'], 2) . '<br>';
            }
        }

        return $html;
    }

    private function generate_pdf_via_api2pdf($html, $api_key)
    {
        $response = wp_remote_post('https://v2.api2pdf.com/chrome/pdf/html', array(
            'headers' => array(
                'Authorization' => $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'html' => $html,
                'options' => array(
                    'landscape' => false,
                    'printBackground' => true,
                    'format' => 'A4'
                )
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['pdf']) || !$data['success']) {
            return new WP_Error('pdf_generation_failed', 'Failed to generate PDF', array('status' => 500));
        }

        return $data['pdf'];
    }

    private function send_email_via_brevo($email, $type, $pdf_url, $api_key)
    {
        $calculator_type = ($type === 'transfer') ? 'Transfer Cost' : 'Bond Cost';
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
        $calculator_type = ($type === 'transfer') ? 'Transfer Cost' : 'Bond Cost';

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

// Initialize the calculator
new BC_Calculator();
