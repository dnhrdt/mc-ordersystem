<?php
/**
 * Plugin Name: Maison Common Quick Order
 * Plugin URI: https://maisoncommon.com
 * Description: AJAX-basierte Collections-Navigation mit Quick Order Tabellen und Abmusterungs-System für Maison Common
 * Version: 1.4.0
 * Author: Michael Deinhardt
 * License: GPL v2 or later
 * Text Domain: maison-common-quick-order
 * Domain Path: /languages
 * 
 * @package MC_Quick_Order
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MC_QUICK_ORDER_VERSION', '1.4.0');
define('MC_QUICK_ORDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MC_QUICK_ORDER_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Main Plugin Class - Basis-Funktionalität
 */
class MC_Quick_Order {
    
    /**
     * Order System Instance
     */
    public $order_system;
    
    /**
     * Sampling System Instance
     */
    public $sampling_system;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('maison-common-quick-order', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Load required files
        $this->load_dependencies();
        
        // Initialize systems
        $this->init_systems();
        
        // Initialize common functionality
        $this->init_common_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once MC_QUICK_ORDER_PLUGIN_PATH . 'includes/class-mc-order-system.php';
        require_once MC_QUICK_ORDER_PLUGIN_PATH . 'includes/class-mc-sampling-system.php';
    }
    
    /**
     * Initialize systems
     */
    private function init_systems() {
        $this->order_system = new MC_Order_System();
        $this->sampling_system = new MC_Sampling_System();
    }
    
    /**
     * Initialize common hooks
     */
    private function init_common_hooks() {
        // Add custom fields for EK price (shared functionality)
        add_action('woocommerce_product_options_pricing', array($this, 'add_ek_price_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_ek_price_field'));
        add_action('woocommerce_variation_options_pricing', array($this, 'add_ek_price_variation_field'), 10, 3);
        add_action('woocommerce_save_product_variation', array($this, 'save_ek_price_variation_field'), 10, 2);
        
        // Add _ek_price to Quick Order Plugin meta keys
        add_filter('woocommerce_quick_order_meta_keys', array($this, 'add_ek_price_to_quick_order_meta_keys'));
        
        // Ensure _ek_price is available in Quick Order Plugin
        add_action('admin_init', array($this, 'ensure_ek_price_in_quick_order'));
        add_action('wp_loaded', array($this, 'ensure_ek_price_in_quick_order'));
        
        // Format _ek_price display in Quick Order Plugin
        add_filter('woocommerce_quick_order_table_data', array($this, 'modify_quick_order_table_data'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create plugin assets directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/maison-common-quick-order';
        
        if (!file_exists($plugin_dir)) {
            wp_mkdir_p($plugin_dir);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Add EK price field to product pricing options
     */
    public function add_ek_price_field() {
        woocommerce_wp_text_input(array(
            'id' => '_ek_price',
            'label' => __('EK-Preis (Einkaufspreis)', 'maison-common-quick-order') . ' (' . get_woocommerce_currency_symbol() . ')',
            'placeholder' => '',
            'description' => __('Einkaufspreis für Cart Totals Berechnung', 'maison-common-quick-order'),
            'type' => 'number',
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        ));
    }
    
    /**
     * Save EK price field
     */
    public function save_ek_price_field($post_id) {
        $ek_price = $_POST['_ek_price'];
        if (!empty($ek_price)) {
            update_post_meta($post_id, '_ek_price', esc_attr($ek_price));
        } else {
            delete_post_meta($post_id, '_ek_price');
        }
    }
    
    /**
     * Add EK price field to product variations
     */
    public function add_ek_price_variation_field($loop, $variation_data, $variation) {
        woocommerce_wp_text_input(array(
            'id' => '_ek_price[' . $variation->ID . ']',
            'label' => __('EK-Preis', 'maison-common-quick-order') . ' (' . get_woocommerce_currency_symbol() . ')',
            'placeholder' => '',
            'description' => __('Einkaufspreis für Cart Totals Berechnung', 'maison-common-quick-order'),
            'type' => 'number',
            'value' => get_post_meta($variation->ID, '_ek_price', true),
            'custom_attributes' => array(
                'step' => 'any',
                'min' => '0'
            )
        ));
    }
    
    /**
     * Save EK price field for variations
     */
    public function save_ek_price_variation_field($post_id, $i) {
        $ek_price = $_POST['_ek_price'][$post_id];
        if (!empty($ek_price)) {
            update_post_meta($post_id, '_ek_price', esc_attr($ek_price));
        } else {
            delete_post_meta($post_id, '_ek_price');
        }
    }
    
    /**
     * Add _ek_price to Quick Order Plugin meta keys
     */
    public function add_ek_price_to_quick_order_meta_keys($meta_keys) {
        // Add _ek_price if it's not already in the list
        if (!in_array('_ek_price', $meta_keys)) {
            $meta_keys[] = '_ek_price';
        }
        return $meta_keys;
    }
    
    /**
     * Ensure _ek_price is available in Quick Order Plugin
     * This function adds _ek_price to the transient cache used by the Quick Order Plugin
     */
    public function ensure_ek_price_in_quick_order() {
        // Get the transient name used by Quick Order Plugin
        $transient_name = 'woocommerce_quick_order_meta_keys';
        $meta_keys = get_transient($transient_name);
        
        // If transient exists and _ek_price is not in it, add it
        if ($meta_keys !== false && !in_array('_ek_price', $meta_keys)) {
            $meta_keys[] = '_ek_price';
            set_transient($transient_name, $meta_keys, WEEK_IN_SECONDS);
        }
        
        // If transient doesn't exist, create it with _ek_price
        if ($meta_keys === false) {
            $meta_keys = array('_ek_price');
            set_transient($transient_name, $meta_keys, WEEK_IN_SECONDS);
        }
    }
    
    /**
     * Modify Quick Order table data to format _ek_price values
     */
    public function modify_quick_order_table_data($data) {
        // Use output buffering to modify the final HTML output
        add_action('woocommerce_quick_order_after_table', array($this, 'format_ek_price_in_output'));
        
        return $data;
    }
    
    /**
     * Format EK price values in the final HTML output using JavaScript
     */
    public function format_ek_price_in_output() {
        // Get WooCommerce currency settings
        $currency_symbol = get_woocommerce_currency_symbol();
        $currency_pos = get_option('woocommerce_currency_pos');
        $decimal_separator = wc_get_price_decimal_separator();
        $thousand_separator = wc_get_price_thousand_separator();
        $decimals = wc_get_price_decimals();
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // WooCommerce currency settings from PHP
            var currencySettings = {
                symbol: '<?php echo esc_js($currency_symbol); ?>',
                position: '<?php echo esc_js($currency_pos); ?>',
                decimalSeparator: '<?php echo esc_js($decimal_separator); ?>',
                thousandSeparator: '<?php echo esc_js($thousand_separator); ?>',
                decimals: <?php echo intval($decimals); ?>
            };
            
            // Find all _ek_price cells and format them
            $('.quick-order-table-value-meta__ek_price').each(function() {
                var $cell = $(this);
                var rawValue = $cell.text().trim();
                
                // Check if it's a numeric value (not already formatted)
                if (rawValue && !isNaN(rawValue) && !rawValue.includes(currencySettings.symbol)) {
                    var numValue = parseFloat(rawValue);
                    
                    // Format the number according to WooCommerce settings
                    var formattedNumber = numValue.toFixed(currencySettings.decimals);
                    
                    // Replace decimal separator if needed
                    if (currencySettings.decimalSeparator !== '.') {
                        formattedNumber = formattedNumber.replace('.', currencySettings.decimalSeparator);
                    }
                    
                    // Add thousand separators if needed
                    if (currencySettings.thousandSeparator && numValue >= 1000) {
                        var parts = formattedNumber.split(currencySettings.decimalSeparator);
                        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currencySettings.thousandSeparator);
                        formattedNumber = parts.join(currencySettings.decimalSeparator);
                    }
                    
                    // Apply currency symbol position
                    var formattedPrice = '';
                    switch (currencySettings.position) {
                        case 'left':
                            formattedPrice = currencySettings.symbol + formattedNumber;
                            break;
                        case 'right':
                            formattedPrice = formattedNumber + currencySettings.symbol;
                            break;
                        case 'left_space':
                            formattedPrice = currencySettings.symbol + ' ' + formattedNumber;
                            break;
                        case 'right_space':
                            formattedPrice = formattedNumber + ' ' + currencySettings.symbol;
                            break;
                        default:
                            formattedPrice = currencySettings.symbol + formattedNumber;
                    }
                    
                    $cell.html('<span class="woocommerce-Price-amount amount">' + formattedPrice + '</span>');
                }
            });
        });
        </script>
        <?php
    }
}

// Initialize the plugin
new MC_Quick_Order();
