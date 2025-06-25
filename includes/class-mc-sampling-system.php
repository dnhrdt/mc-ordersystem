<?php
/**
 * Sampling System Class
 * 
 * Handles the new sampling functionality including:
 * - EAN scanner interface
 * - Custom cart items for sampling
 * - Collection filtering for sampling
 * - Signature field integration
 * 
 * @package MC_Quick_Order
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MC Sampling System Class
 */
class MC_Sampling_System {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register sampling shortcode
        add_shortcode('mc_sampling', array($this, 'sampling_shortcode'));
        
        // Register AJAX handlers for sampling
        add_action('wp_ajax_mc_scan_ean', array($this, 'ajax_scan_ean'));
        add_action('wp_ajax_nopriv_mc_scan_ean', array($this, 'ajax_scan_ean'));
        add_action('wp_ajax_mc_load_sampling_collection', array($this, 'ajax_load_sampling_collection'));
        add_action('wp_ajax_nopriv_mc_load_sampling_collection', array($this, 'ajax_load_sampling_collection'));
        add_action('wp_ajax_mc_search_product_by_ean', array($this, 'ajax_search_product_by_ean'));
        add_action('wp_ajax_nopriv_mc_search_product_by_ean', array($this, 'ajax_search_product_by_ean'));
        
        // Custom cart item handling
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_sampling_cart_item_data'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_sampling_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_sampling_order_item_data'), 10, 4);
        
        // Hook into cart addition to detect sampling context
        add_action('woocommerce_add_to_cart', array($this, 'check_sampling_context_on_add_to_cart'), 10, 6);
        
        // Signature field integration
        add_action('woocommerce_review_order_before_submit', array($this, 'add_signature_field'));
        add_action('woocommerce_checkout_process', array($this, 'validate_signature_field'));
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_signature_field'));
        
        // Custom field for collections
        add_action('collection_add_form_fields', array($this, 'add_sampling_collection_field'));
        add_action('collection_edit_form_fields', array($this, 'edit_sampling_collection_field'));
        add_action('edited_collection', array($this, 'save_sampling_collection_field'));
        add_action('create_collection', array($this, 'save_sampling_collection_field'));
    }
    
    /**
     * Sampling Shortcode
     */
    public function sampling_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'collection_id' => 0,
            'show_scanner' => 'true',
            'show_collections' => 'true'
        ), $atts, 'mc_sampling');
        
        // Enqueue sampling assets
        $this->enqueue_sampling_assets();
        
        // Get sampling interface HTML
        return $this->get_sampling_interface($atts);
    }
    
    /**
     * Enqueue sampling assets
     */
    private function enqueue_sampling_assets() {
        wp_enqueue_script('jquery');
        
        // Enqueue sampling-specific JavaScript
        wp_enqueue_script(
            'mc-sampling-ajax',
            MC_QUICK_ORDER_PLUGIN_URL . 'assets/js/mc-sampling-ajax.js',
            array('jquery'),
            MC_QUICK_ORDER_VERSION,
            true
        );
        
        wp_localize_script('mc-sampling-ajax', 'mc_sampling_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mc_sampling_nonce'),
            'strings' => array(
                'scanning' => __('Scanne EAN...', 'maison-common-quick-order'),
                'scan_success' => __('Produkt erfolgreich hinzugefügt', 'maison-common-quick-order'),
                'scan_error' => __('Fehler beim Scannen', 'maison-common-quick-order'),
                'product_not_found' => __('Produkt nicht gefunden', 'maison-common-quick-order'),
                'invalid_ean' => __('Ungültige EAN', 'maison-common-quick-order')
            )
        ));
        
        // Enqueue sampling-specific CSS
        wp_enqueue_style(
            'mc-sampling-style',
            MC_QUICK_ORDER_PLUGIN_URL . 'assets/css/mc-sampling-style.css',
            array(),
            MC_QUICK_ORDER_VERSION
        );
    }
    
    /**
     * Get sampling interface HTML
     */
    private function get_sampling_interface($atts) {
        ob_start();
        
        echo '<div id="mc-sampling-container" class="mc-sampling-container">';
        
        // Scanner interface
        if ($atts['show_scanner'] === 'true') {
            $this->render_scanner_interface();
        }
        
        // Sampling content area (Quick Order table)
        if ($atts['show_collections'] === 'true') {
            echo '<div id="mc-sampling-content" class="mc-sampling-content">';
            
            if ($atts['collection_id'] > 0) {
                // Use specific collection ID
                $this->render_sampling_collection_content($atts['collection_id']);
            } else {
                // Auto-detect current sampling collection
                $current_collection = $this->get_current_sampling_collection();
                if ($current_collection) {
                    $this->render_sampling_collection_content($current_collection->term_id);
                } else {
                    $this->render_no_sampling_collection_message();
                }
            }
            
            echo '</div>'; // #mc-sampling-content
        }
        
        echo '</div>'; // #mc-sampling-container
        
        return ob_get_clean();
    }
    
    /**
     * Render scanner interface
     */
    private function render_scanner_interface() {
        echo '<div class="mc-scanner-interface">';
        echo '<h3>' . __('EAN Scanner', 'maison-common-quick-order') . '</h3>';
        
        echo '<div class="mc-scanner-input-group">';
        echo '<label for="mc-ean-input">' . __('EAN-Code scannen oder eingeben:', 'maison-common-quick-order') . '</label>';
        echo '<input type="text" id="mc-ean-input" class="mc-ean-input" placeholder="' . __('EAN-Code (13 Zeichen)', 'maison-common-quick-order') . '" maxlength="13" />';
        echo '<button type="button" id="mc-scan-button" class="button mc-scan-button">' . __('Hinzufügen', 'maison-common-quick-order') . '</button>';
        echo '</div>';
        
        echo '<div id="mc-scan-feedback" class="mc-scan-feedback"></div>';
        echo '<div id="mc-scan-history" class="mc-scan-history">';
        echo '<h4>' . __('Gescannte Produkte:', 'maison-common-quick-order') . '</h4>';
        echo '<ul id="mc-scan-history-list"></ul>';
        echo '</div>';
        
        echo '</div>'; // .mc-scanner-interface
    }
    
    /**
     * Get current sampling collection
     */
    private function get_current_sampling_collection() {
        // Get sampling collections
        $collections = get_terms(array(
            'taxonomy' => 'collection',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_is_sampling_collection',
                    'value' => '1',
                    'compare' => '='
                )
            ),
            'orderby' => 'menu_order',
            'order' => 'DESC' // Get newest first
        ));
        
        if (empty($collections)) {
            return false;
        }
        
        // Return the first (newest) sampling collection
        return $collections[0];
    }
    
    /**
     * Render no sampling collection message
     */
    private function render_no_sampling_collection_message() {
        // Get all sampling collections to check the situation
        $collections = get_terms(array(
            'taxonomy' => 'collection',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => '_is_sampling_collection',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ));
        
        if (empty($collections)) {
            // No sampling collections found
            echo '<div class="woocommerce-error">';
            echo '<h3>' . __('⚠️ Keine Abmusterungs-Collection gefunden!', 'maison-common-quick-order') . '</h3>';
            echo '<p>' . __('Bitte markieren Sie eine Collection als "Abmusterungs-Collection" im WordPress-Backend:', 'maison-common-quick-order') . '</p>';
            echo '<ol>';
            echo '<li>' . __('Gehen Sie zu <strong>Produkte → Collections</strong>', 'maison-common-quick-order') . '</li>';
            echo '<li>' . __('Wählen Sie eine Collection aus und klicken Sie auf <strong>Bearbeiten</strong>', 'maison-common-quick-order') . '</li>';
            echo '<li>' . __('Aktivieren Sie die Checkbox <strong>"Abmusterungs-Collection"</strong>', 'maison-common-quick-order') . '</li>';
            echo '<li>' . __('Klicken Sie auf <strong>Speichern</strong>', 'maison-common-quick-order') . '</li>';
            echo '</ol>';
            echo '</div>';
        } elseif (count($collections) > 1) {
            // Multiple sampling collections found
            echo '<div class="woocommerce-info">';
            echo '<h3>' . __('⚠️ Mehrere Abmusterungs-Collections gefunden!', 'maison-common-quick-order') . '</h3>';
            echo '<p>' . sprintf(__('Es sind %d Collections als Abmusterung markiert. Wir verwenden automatisch die neueste Collection.', 'maison-common-quick-order'), count($collections)) . '</p>';
            echo '<p>' . __('<strong>Tipp:</strong> Entfernen Sie die Markierung bei älteren Collections im Backend für eine saubere Konfiguration.', 'maison-common-quick-order') . '</p>';
            
            // Show which collection is being used
            $current = $this->get_current_sampling_collection();
            if ($current) {
                echo '<p>' . sprintf(__('Aktuell verwendete Collection: <strong>%s</strong>', 'maison-common-quick-order'), esc_html($current->name)) . '</p>';
                // Still render the content for the current collection
                $this->render_sampling_collection_content($current->term_id);
            }
            echo '</div>';
        }
    }
    
    /**
     * Render sampling collection content with Quick Order table
     */
    private function render_sampling_collection_content($collection_id) {
        $collection = get_term($collection_id, 'collection');
        
        if (is_wp_error($collection) || !$collection) {
            echo '<div class="woocommerce-error">';
            echo __('Collection nicht gefunden.', 'maison-common-quick-order');
            echo '</div>';
            return;
        }
        
        // Check if this is a sampling collection
        $is_sampling = get_term_meta($collection_id, '_is_sampling_collection', true);
        if ($is_sampling !== '1') {
            echo '<div class="woocommerce-error">';
            echo __('Diese Collection ist nicht für Abmusterung freigegeben.', 'maison-common-quick-order');
            echo '</div>';
            return;
        }
        
        echo '<h3>' . sprintf(__('Abmusterung: %s', 'maison-common-quick-order'), esc_html($collection->name)) . '</h3>';
        
        // Render Quick Order table for this collection
        $this->render_quick_order_table($collection_id);
    }
    
    /**
     * Render Quick Order table for sampling collection
     */
    private function render_quick_order_table($collection_id) {
        // Create the Quick Order shortcode for this collection
        $shortcode = sprintf(
            '[woocommerce_quick_order_table taxonomy="collection" categories="%d" order="DESC" orderby="menu_order" only_on_stock="no"]',
            $collection_id
        );
        
        // Execute the shortcode and display the table
        echo '<div class="mc-sampling-quick-order-table">';
        echo do_shortcode($shortcode);
        echo '</div>';
        
        // Add note about sampling items
        echo '<div class="mc-sampling-note woocommerce-info">';
        echo '<p><strong>' . __('Hinweis:', 'maison-common-quick-order') . '</strong> ';
        echo __('Alle über diese Tabelle hinzugefügten Produkte werden automatisch als Abmusterungs-Items (0€) behandelt.', 'maison-common-quick-order');
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * AJAX handler for EAN scanning
     */
    public function ajax_scan_ean() {
        // Debug logging
        error_log('MC Sampling: ajax_scan_ean called');
        error_log('MC Sampling: POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_sampling_nonce')) {
            error_log('MC Sampling: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        $ean = sanitize_text_field($_POST['ean']);
        error_log('MC Sampling: Processing EAN: ' . $ean);
        
        // Validate EAN format (13 digits)
        if (!preg_match('/^\d{13}$/', $ean)) {
            wp_send_json_error(__('Ungültige EAN. Bitte 13 Ziffern eingeben.', 'maison-common-quick-order'));
            return;
        }
        
        // Find product variant by EAN
        $variant = $this->find_product_by_ean($ean);
        
        if (!$variant) {
            wp_send_json_error(__('Produkt mit dieser EAN nicht gefunden.', 'maison-common-quick-order'));
            return;
        }
        
        // Get parent product
        $parent_product = wc_get_product($variant->get_parent_id());
        
        if (!$parent_product) {
            wp_send_json_error(__('Parent-Produkt nicht gefunden.', 'maison-common-quick-order'));
            return;
        }
        
        // Get parent SKU (Artikel-ID)
        $artikel_id = $parent_product->get_sku();
        
        if (empty($artikel_id)) {
            wp_send_json_error(__('Artikel-ID (SKU) nicht gefunden.', 'maison-common-quick-order'));
            return;
        }
        
        // Add to cart as sampling item
        $cart_item_data = array(
            'is_sampling' => true,
            'artikel_id' => $artikel_id,
            'scanned_ean' => $ean,
            'original_variant_id' => $variant->get_id(),
            'sampling_size' => 'Abmusterung'
        );
        
        $cart_item_key = WC()->cart->add_to_cart(
            $parent_product->get_id(),
            1, // quantity
            0, // variation_id (we use parent product)
            array(), // variation attributes
            $cart_item_data
        );
        
        if ($cart_item_key) {
            wp_send_json_success(array(
                'message' => sprintf(__('Produkt "%s" zur Abmusterung hinzugefügt', 'maison-common-quick-order'), $parent_product->get_name()),
                'product_name' => $parent_product->get_name(),
                'artikel_id' => $artikel_id,
                'cart_item_key' => $cart_item_key
            ));
        } else {
            wp_send_json_error(__('Fehler beim Hinzufügen zum Warenkorb.', 'maison-common-quick-order'));
        }
    }
    
    /**
     * Find product variant by EAN
     */
    private function find_product_by_ean($ean) {
        global $wpdb;
        
        // Search for EAN in product meta
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_ean' AND meta_value = %s 
             LIMIT 1",
            $ean
        ));
        
        if ($product_id) {
            return wc_get_product($product_id);
        }
        
        // Alternative: Search in other possible EAN fields
        $ean_fields = array('_ean13', '_barcode', '_gtin');
        
        foreach ($ean_fields as $field) {
            $product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = %s AND meta_value = %s 
                 LIMIT 1",
                $field,
                $ean
            ));
            
            if ($product_id) {
                return wc_get_product($product_id);
            }
        }
        
        return false;
    }
    
    /**
     * AJAX handler for loading sampling collection
     */
    public function ajax_load_sampling_collection() {
        // Debug logging
        error_log('MC Sampling: ajax_load_sampling_collection called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_sampling_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $collection_id = intval($_POST['collection_id']);
        
        if ($collection_id <= 0) {
            wp_send_json_error('Invalid collection ID');
            return;
        }
        
        // Get collection content
        ob_start();
        $this->render_sampling_collection_content($collection_id);
        $content = ob_get_clean();
        
        wp_send_json_success(array(
            'content' => $content,
            'collection_id' => $collection_id
        ));
    }
    
    /**
     * AJAX handler for searching product by EAN (for new scanner integration)
     */
    public function ajax_search_product_by_ean() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_sampling_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $ean_code = sanitize_text_field($_POST['ean_code']);
        
        // Validate EAN format
        if (!preg_match('/^\d{8,14}$/', $ean_code)) {
            wp_send_json_error('Invalid EAN format');
            return;
        }
        
        // Find product by EAN
        $product = $this->find_product_by_ean($ean_code);
        
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }
        
        // Get product data for frontend
        $product_data = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'ean' => $ean_code,
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail')
        );
        
        wp_send_json_success(array(
            'product' => $product_data
        ));
    }
    
    /**
     * Add sampling data to cart item
     */
    public function add_sampling_cart_item_data($cart_item_data, $product_id, $variation_id) {
        // Check if we're in a sampling context (product belongs to sampling collection)
        if ($this->is_product_in_sampling_collection($product_id)) {
            // Get parent product for SKU
            $product = wc_get_product($product_id);
            if ($product) {
                $artikel_id = $product->get_sku();
                
                // Add sampling data
                $cart_item_data['is_sampling'] = true;
                $cart_item_data['artikel_id'] = $artikel_id;
                $cart_item_data['sampling_size'] = 'Abmusterung';
                $cart_item_data['added_via'] = 'quick_order_table';
                
                error_log('MC Sampling: Product added via Quick Order table as sampling item - SKU: ' . $artikel_id);
            }
        }
        
        return $cart_item_data;
    }
    
    /**
     * Check if product belongs to a sampling collection
     */
    private function is_product_in_sampling_collection($product_id) {
        // Get product terms for collection taxonomy
        $terms = wp_get_post_terms($product_id, 'collection');
        
        if (empty($terms) || is_wp_error($terms)) {
            return false;
        }
        
        // Check if any of the product's collections are marked as sampling collections
        foreach ($terms as $term) {
            $is_sampling = get_term_meta($term->term_id, '_is_sampling_collection', true);
            if ($is_sampling === '1') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check sampling context when item is added to cart
     */
    public function check_sampling_context_on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        // This runs after the item is added to cart
        // We can use this to modify the cart item if needed
        if (isset($cart_item_data['is_sampling']) && $cart_item_data['is_sampling']) {
            error_log('MC Sampling: Sampling item added to cart - Key: ' . $cart_item_key);
        }
    }
    
    /**
     * Display sampling data in cart
     */
    public function display_sampling_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
            $item_data[] = array(
                'key' => __('Typ', 'maison-common-quick-order'),
                'value' => __('Abmusterung', 'maison-common-quick-order')
            );
            
            if (isset($cart_item['artikel_id'])) {
                $item_data[] = array(
                    'key' => __('Artikel-ID', 'maison-common-quick-order'),
                    'value' => $cart_item['artikel_id']
                );
            }
            
            if (isset($cart_item['sampling_size'])) {
                $item_data[] = array(
                    'key' => __('Größe', 'maison-common-quick-order'),
                    'value' => $cart_item['sampling_size']
                );
            }
        }
        
        return $item_data;
    }
    
    /**
     * Save sampling data to order item
     */
    public function save_sampling_order_item_data($item, $cart_item_key, $values, $order) {
        if (isset($values['is_sampling']) && $values['is_sampling']) {
            $item->add_meta_data('_is_sampling', 'yes');
            
            if (isset($values['artikel_id'])) {
                $item->add_meta_data('_artikel_id', $values['artikel_id']);
            }
            
            if (isset($values['scanned_ean'])) {
                $item->add_meta_data('_scanned_ean', $values['scanned_ean']);
            }
            
            if (isset($values['sampling_size'])) {
                $item->add_meta_data('_sampling_size', $values['sampling_size']);
            }
            
            // Set price to 0 for sampling items
            $item->set_total(0);
            $item->set_subtotal(0);
        }
    }
    
    /**
     * Add signature field to checkout
     */
    public function add_signature_field() {
        // Check if cart contains sampling items
        if (!$this->cart_contains_sampling_items()) {
            return;
        }
        
        echo '<div id="mc-signature-field" class="mc-signature-field">';
        echo '<h3>' . __('Unterschrift erforderlich', 'maison-common-quick-order') . '</h3>';
        echo '<p>' . __('Für Abmusterungen ist eine Unterschrift erforderlich.', 'maison-common-quick-order') . '</p>';
        
        woocommerce_form_field('mc_signature_provided', array(
            'type' => 'checkbox',
            'class' => array('form-row-wide'),
            'label' => __('Unterschrift wurde geleistet', 'maison-common-quick-order'),
            'required' => true
        ), WC()->checkout->get_value('mc_signature_provided'));
        
        echo '</div>';
    }
    
    /**
     * Validate signature field
     */
    public function validate_signature_field() {
        // Check if cart contains sampling items
        if (!$this->cart_contains_sampling_items()) {
            return;
        }
        
        if (!$_POST['mc_signature_provided']) {
            wc_add_notice(__('Für Abmusterungen ist eine Unterschrift erforderlich.', 'maison-common-quick-order'), 'error');
        }
    }
    
    /**
     * Save signature field to order
     */
    public function save_signature_field($order_id) {
        if (!empty($_POST['mc_signature_provided'])) {
            update_post_meta($order_id, '_mc_signature_provided', 'yes');
            update_post_meta($order_id, '_mc_signature_timestamp', current_time('mysql'));
        }
    }
    
    /**
     * Check if cart contains sampling items
     */
    private function cart_contains_sampling_items() {
        if (!WC()->cart) {
            return false;
        }
        
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add sampling collection field
     */
    public function add_sampling_collection_field() {
        ?>
        <div class="form-field">
            <label for="is_sampling_collection"><?php _e('Abmusterungs-Collection', 'maison-common-quick-order'); ?></label>
            <input type="checkbox" name="is_sampling_collection" id="is_sampling_collection" value="1" />
            <p class="description"><?php _e('Markieren Sie diese Collection als Abmusterungs-Collection.', 'maison-common-quick-order'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit sampling collection field
     */
    public function edit_sampling_collection_field($term) {
        $is_sampling = get_term_meta($term->term_id, '_is_sampling_collection', true);
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="is_sampling_collection"><?php _e('Abmusterungs-Collection', 'maison-common-quick-order'); ?></label>
            </th>
            <td>
                <input type="checkbox" name="is_sampling_collection" id="is_sampling_collection" value="1" <?php checked($is_sampling, '1'); ?> />
                <p class="description"><?php _e('Markieren Sie diese Collection als Abmusterungs-Collection.', 'maison-common-quick-order'); ?></p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save sampling collection field
     */
    public function save_sampling_collection_field($term_id) {
        if (isset($_POST['is_sampling_collection'])) {
            update_term_meta($term_id, '_is_sampling_collection', '1');
        } else {
            delete_term_meta($term_id, '_is_sampling_collection');
        }
    }
}
