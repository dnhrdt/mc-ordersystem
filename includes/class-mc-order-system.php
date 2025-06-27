<?php
/**
 * Order System Class
 * 
 * Handles the existing Quick Order functionality including:
 * - Collections navigation
 * - Cart totals
 * - User switching
 * - AJAX handlers
 * 
 * @package MC_Quick_Order
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MC Order System Class
 */
class MC_Order_System {
    
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
        // Register shortcodes
        add_shortcode('mc_quick_order', array($this, 'quick_order_shortcode'));
        add_shortcode('mc_cart_totals', array($this, 'cart_totals_shortcode'));
        
        // Register AJAX handlers early and globally
        add_action('wp_ajax_mc_load_collection', array($this, 'ajax_load_collection'));
        add_action('wp_ajax_nopriv_mc_load_collection', array($this, 'ajax_load_collection'));
        add_action('wp_ajax_mc_search_customers', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_nopriv_mc_search_customers', array($this, 'ajax_search_customers'));
        add_action('wp_ajax_mc_get_cart_totals', array($this, 'ajax_get_cart_totals'));
        add_action('wp_ajax_nopriv_mc_get_cart_totals', array($this, 'ajax_get_cart_totals'));
        add_action('wp_ajax_mc_get_parent_id_for_ean', array($this, 'ajax_get_parent_id_for_ean'));
        add_action('wp_ajax_nopriv_mc_get_parent_id_for_ean', array($this, 'ajax_get_parent_id_for_ean'));
        
        // Add parent ID to table rows
        add_filter('woocommerce_quick_order_table_row_attributes', array($this, 'add_parent_id_to_row_attributes'), 10, 2);

        // Only load on frontend
        if (!is_admin()) {
            add_action('template_redirect', array($this, 'init_quick_order_system'), 30);
        }
    }

    /**
     * Add data-parent-id attribute to quick order table rows using a filter.
     */
    public function add_parent_id_to_row_attributes($attributes, $product) {
        if ($product && $product->is_type('variation')) {
            $attributes['data-parent-id'] = $product->get_parent_id();
        }
        return $attributes;
    }
    
    /**
     * Initialize the quick order system
     */
    public function init_quick_order_system() {
        // Check if we're on a page with the quick order shortcode or collection taxonomy
        if (!$this->is_quick_order_page() && !is_tax('collection') && !is_shop()) {
            return;
        }
        
        // Force sidebar layout for Astra
        add_filter('astra_get_site_layout', array($this, 'force_sidebar_layout'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Replace main content
        add_action('woocommerce_before_main_content', array($this, 'display_collections_interface'), 30);
        
        // Prevent standard WooCommerce product loop
        add_filter('woocommerce_product_loop_start', '__return_empty_string');
        add_filter('woocommerce_product_loop_end', '__return_empty_string');
        add_action('pre_get_posts', array($this, 'empty_shop_query'), 999);
        
        // Add login widget and collections widget to WooCommerce sidebar
        add_action('woocommerce_sidebar', array($this, 'add_login_widget'), 5);
        add_action('woocommerce_sidebar', array($this, 'add_collections_sidebar_widget'), 10);
    }
    
    /**
     * Force sidebar layout for Astra theme
     */
    public function force_sidebar_layout($layout) {
        if (is_shop() || is_tax('collection') || $this->is_quick_order_page()) {
            return 'right-sidebar';
        }
        return $layout;
    }
    
    /**
     * Empty the shop query to prevent product loading
     */
    public function empty_shop_query($query) {
        if (!is_admin() && $query->is_main_query() && (is_shop() || is_tax('collection'))) {
            $query->set('post__in', array(0));
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (is_shop() || is_tax('collection') || $this->is_quick_order_page() || is_home()) {
            wp_enqueue_script('jquery');
            
            wp_enqueue_script(
                'mc-collections-ajax',
                MC_QUICK_ORDER_PLUGIN_URL . 'assets/js/mc-collections-ajax.js',
                array('jquery'),
                MC_QUICK_ORDER_VERSION,
                true
            );
            
            wp_localize_script('mc-collections-ajax', 'mc_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mc_collections_nonce')
            ));
            
            wp_enqueue_style(
                'mc-collections-style',
                MC_QUICK_ORDER_PLUGIN_URL . 'assets/css/mc-collections-style.css',
                array(),
                MC_QUICK_ORDER_VERSION
            );
        }
    }
    
    /**
     * Display collections interface
     */
    public function display_collections_interface() {
        // Get current collection
        $current_collection_id = 0;
        $current_collection_name = '';
        
        if (is_tax('collection')) {
            $queried_object = get_queried_object();
            if (isset($queried_object->term_id)) {
                $current_collection_id = $queried_object->term_id;
                $current_collection_name = $queried_object->name;
            }
        } else {
            // Load latest collection as default (highest menu_order = newest)
            $collections = get_terms(array(
                'taxonomy' => 'collection',
                'hide_empty' => false,
                'orderby' => 'menu_order',
                'order' => 'DESC',
                'number' => 1
            ));
            
            if (!empty($collections)) {
                $current_collection_id = $collections[0]->term_id;
                $current_collection_name = $collections[0]->name;
            }
        }
        
        echo '<div id="mc-collections-container">';
        
        // Loading animation
        echo '<div id="mc-loading" style="display: none;">';
            echo '<p>' . __('Lade Collection...', 'maison-common-quick-order') . '</p>';
        echo '</div>';
        
        echo '<div id="mc-quick-order-content">';
        
        if ($current_collection_id > 0) {
            echo do_shortcode('[woocommerce_quick_order_table taxonomy="collection" categories="' . $current_collection_id . '" order="DESC" orderby="menu_order" only_on_stock="no"]');
        } else {
            echo '<div class="woocommerce-info">';
            echo __('Bitte wählen Sie eine Collection aus der Sidebar.', 'maison-common-quick-order');
            echo '</div>';
        }
        
        echo '</div>'; // #mc-quick-order-content
        echo '</div>'; // #mc-collections-container
    }
    
    /**
     * AJAX handler for loading collections
     */
    public function ajax_load_collection() {
        // Debug logging
        error_log('MC Quick Order: ajax_load_collection called');
        error_log('MC Quick Order: POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_collections_nonce')) {
            error_log('MC Quick Order: Nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        $collection_id = intval($_POST['collection_id']);
        error_log('MC Quick Order: Processing collection_id: ' . $collection_id);
        
        if ($collection_id <= 0) {
            error_log('MC Quick Order: Invalid collection ID: ' . $collection_id);
            wp_send_json_error('Invalid collection ID');
            return;
        }
        
        // Get collection data
        $collection = get_term($collection_id, 'collection');
        
        if (is_wp_error($collection) || !$collection) {
            error_log('MC Quick Order: Collection not found: ' . $collection_id);
            wp_send_json_error('Collection not found');
            return;
        }
        
        // Generate quick order table
        $quick_order_content = do_shortcode('[woocommerce_quick_order_table taxonomy="collection" categories="' . $collection_id . '" order="DESC" orderby="menu_order" only_on_stock="no"]');
        
        // Add cart totals below the table
        $quick_order_content .= '<div class="mc-cart-totals-below-table">';
        $quick_order_content .= do_shortcode('[mc_cart_totals]');
        $quick_order_content .= '</div>';
        
        error_log('MC Quick Order: Generated content length: ' . strlen($quick_order_content));
        
        wp_send_json_success(array(
            'collection_name' => $collection->name,
            'collection_id' => $collection_id,
            'quick_order_content' => $quick_order_content
        ));
    }
    
    /**
     * AJAX handler for getting parent product SKU for a given EAN.
     */
    public function ajax_get_parent_id_for_ean() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_collections_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $ean = sanitize_text_field($_POST['ean']);
        
        if (empty($ean)) {
            wp_send_json_error('EAN is missing');
            return;
        }
        
        // Query for the product variation by EAN
        $args = array(
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_alg_ean',
                    'value' => $ean,
                    'compare' => '='
                )
            )
        );
        
        $variations = get_posts($args);
        
        if (empty($variations)) {
            wp_send_json_error('Product not found for the given EAN');
            return;
        }
        
        // Get the parent product ID and SKU
        $parent_id = $variations[0]->post_parent;
        $parent_product = wc_get_product($parent_id);
        
        if (!$parent_product) {
            wp_send_json_error('Parent product not found');
            return;
        }
        
        $parent_sku = $parent_product->get_sku();
        
        // If parent has no SKU, extract it from the variation SKU
        if (empty($parent_sku)) {
            $variation_product = wc_get_product($variations[0]->ID);
            $variation_sku = $variation_product->get_sku();
            
            if (!empty($variation_sku)) {
                // Extract parent SKU from variation SKU
                // Pattern: 1-241-1533604-1-490-0-34 -> 1-241-1533604-490
                if (preg_match('/^(\d+-\d+-\d+)-\d+-(\d+)-\d+-\d+$/', $variation_sku, $matches)) {
                    $parent_sku = $matches[1] . '-' . $matches[2];
                } else {
                    wp_send_json_error('Could not extract parent SKU from variation SKU: ' . $variation_sku);
                    return;
                }
            } else {
                wp_send_json_error('Neither parent nor variation has SKU');
                return;
            }
        }
        
        wp_send_json_success(array(
            'parent_id' => $parent_id,
            'parent_sku' => $parent_sku
        ));
    }
    
    /**
     * Add collections sidebar widget
     */
    public function add_collections_sidebar_widget() {
        if (is_shop() || is_tax('collection') || $this->is_quick_order_page()) {
            echo '<div class="widget mc-collections-widget">';
            echo '<h2 class="widget-title">' . __('Collections', 'maison-common-quick-order') . '</h2>';
            $this->display_collections_navigation();
            echo '</div>';
        }
    }
    
    /**
     * Display collections navigation
     */
    private function display_collections_navigation() {
        // Get current collection
        $current_collection_id = 0;
        if (is_tax('collection')) {
            $queried_object = get_queried_object();
            if (isset($queried_object->term_id)) {
                $current_collection_id = $queried_object->term_id;
            }
        }
        
        // Get collections
        $collections = get_terms(array(
            'taxonomy' => 'collection',
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        if (empty($collections)) {
            echo '<p>' . __('Keine Collections gefunden.', 'maison-common-quick-order') . '</p>';
            return;
        }
        
        echo '<div class="mc-collections-nav">';
        echo '<ul class="mc-collections-list">';
        
        foreach ($collections as $collection) {
            $is_active = ($collection->term_id == $current_collection_id) ? ' class="active"' : '';
            echo '<li' . $is_active . '>';
            echo '<a href="#" data-collection-id="' . $collection->term_id . '" class="mc-collection-link">';
            echo esc_html($collection->name);
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
        echo '</div>'; // .mc-collections-nav
    }
    
    /**
     * Check if current page has the quick order shortcode
     */
    private function is_quick_order_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Check if the post content contains our shortcode
        return has_shortcode($post->post_content, 'mc_quick_order');
    }
    
    /**
     * Quick Order Shortcode
     */
    public function quick_order_shortcode($atts) {
        // Force sidebar layout for this page
        add_filter('astra_get_site_layout', array($this, 'force_sidebar_layout'));
        
        // Enqueue scripts and styles
        $this->enqueue_shortcode_assets();
        
        // Add sidebar widgets using a more direct approach
        add_action('wp_footer', array($this, 'inject_sidebar_widgets'));
        
        // Return the collections interface
        return $this->get_collections_interface();
    }
    
    /**
     * Enqueue assets for shortcode
     */
    private function enqueue_shortcode_assets() {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'mc-collections-ajax',
            MC_QUICK_ORDER_PLUGIN_URL . 'assets/js/mc-collections-ajax.js',
            array('jquery'),
            MC_QUICK_ORDER_VERSION,
            true
        );
        
        wp_localize_script('mc-collections-ajax', 'mc_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mc_collections_nonce')
        ));
        
        wp_enqueue_style(
            'mc-collections-style',
            MC_QUICK_ORDER_PLUGIN_URL . 'assets/css/mc-collections-style.css',
            array(),
            MC_QUICK_ORDER_VERSION
        );
    }
    
    /**
     * Get collections interface HTML
     */
    private function get_collections_interface() {
        ob_start();
        
        // Get latest collection as default (highest menu_order = newest)
        $collections = get_terms(array(
            'taxonomy' => 'collection',
            'hide_empty' => false,
            'orderby' => 'menu_order',
            'order' => 'DESC',
            'number' => 1
        ));
        
        $current_collection_id = 0;
        $current_collection_name = '';
        
        if (!empty($collections)) {
            $current_collection_id = $collections[0]->term_id;
            $current_collection_name = $collections[0]->name;
        }
        
        echo '<div id="mc-collections-container">';
        
        // Loading animation
        echo '<div id="mc-loading" style="display: none;">';
            echo '<p>' . __('Lade Collection...', 'maison-common-quick-order') . '</p>';
        echo '</div>';
        
        // Quick order container
        echo '<div id="mc-quick-order-content">';
        
        if ($current_collection_id > 0) {
            echo do_shortcode('[woocommerce_quick_order_table taxonomy="collection" categories="' . $current_collection_id . '" order="DESC" orderby="menu_order" only_on_stock="no"]');
            
            // Add cart totals below the table
            echo '<div class="mc-cart-totals-below-table">';
            echo do_shortcode('[mc_cart_totals]');
            echo '</div>';
        } else {
            echo '<div class="woocommerce-info">';
            echo __('Keine Collections gefunden.', 'maison-common-quick-order');
            echo '</div>';
        }
        
        echo '</div>'; // #mc-quick-order-content
        echo '</div>'; // #mc-collections-container
        
        return ob_get_clean();
    }
    
    /**
     * Add user switching widget with frontend integration
     */
    public function add_user_switching_widget() {
        echo '<div class="widget mc-user-switching-widget">';
        echo '<h2 class="widget-title">' . __('Benutzer wechseln', 'maison-common-quick-order') . '</h2>';
        
        // Check if User Switching plugin is available
        if (class_exists('user_switching') && method_exists('user_switching', 'maybe_switch_url')) {
            
            // Check if we're currently switched
            if (method_exists('user_switching', 'get_old_user')) {
                $old_user = user_switching::get_old_user();
                if ($old_user) {
                    // Show switch back link
                    echo '<div class="mc-current-user">';
                    echo '<p><strong>' . __('Aktuell eingeloggt als:', 'maison-common-quick-order') . '</strong><br>';
                    echo esc_html(wp_get_current_user()->display_name) . '</p>';
                    
                    printf(
                        '<p><a href="%1$s" class="button">%2$s</a></p>',
                        esc_url(user_switching::switch_back_url($old_user)),
                        sprintf(__('Zurück zu %s', 'maison-common-quick-order'), esc_html($old_user->display_name))
                    );
                    echo '</div>';
                }
            }
            
            // Customer search form
            echo '<div class="mc-customer-search">';
            echo '<p><label for="mc-customer-search">' . __('Kunde suchen:', 'maison-common-quick-order') . '</label></p>';
            echo '<input type="text" id="mc-customer-search" placeholder="' . __('Name oder E-Mail eingeben...', 'maison-common-quick-order') . '" />';
            echo '<div id="mc-customer-results"></div>';
            echo '</div>';
            
            // Add customer search JavaScript
            $this->add_customer_search_script();
            
        } else {
            echo '<div class="mc-user-switching-notice">';
            echo '<p>' . __('User Switching Plugin nicht gefunden.', 'maison-common-quick-order') . '</p>';
            echo '<p><small>' . __('Bitte installieren Sie das "User Switching" Plugin für die Benutzer-Wechsel-Funktionalität.', 'maison-common-quick-order') . '</small></p>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Add customer search JavaScript
     */
    private function add_customer_search_script() {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let searchTimeout;
            
            $('#mc-customer-search').on('input', function() {
                const searchTerm = $(this).val();
                const resultsDiv = $('#mc-customer-results');
                
                clearTimeout(searchTimeout);
                
                if (searchTerm.length < 2) {
                    resultsDiv.empty();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: mc_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'mc_search_customers',
                            search: searchTerm,
                            nonce: mc_ajax.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                let html = '<ul class="mc-customer-list">';
                                response.data.forEach(function(customer) {
                                    html += '<li>';
                                    html += '<a href="' + customer.switch_url + '" class="mc-customer-link">';
                                    html += '<strong>' + customer.display_name + '</strong>';
                                    if (customer.email) {
                                        html += '<br><small>' + customer.email + '</small>';
                                    }
                                    html += '</a>';
                                    html += '</li>';
                                });
                                html += '</ul>';
                                resultsDiv.html(html);
                            } else {
                                resultsDiv.html('<p><small>' + response.data + '</small></p>');
                            }
                        }
                    });
                }, 300);
            });
        });
        </script>
        <?php
    }
    
    /**
     * Inject sidebar widgets using JavaScript
     */
    public function inject_sidebar_widgets() {
        if (!$this->is_quick_order_page()) {
            return;
        }
        
        ob_start();
        $this->add_user_switching_widget();
        $this->add_collections_sidebar_widget();
        $widgets_html = ob_get_clean();
        
        // Escape the HTML for JavaScript
        $widgets_html = json_encode($widgets_html);
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Find the sidebar and prepend our widgets
            var sidebar = $('#secondary, .widget-area, #sidebar-1, .sidebar');
            if (sidebar.length > 0) {
                console.log('DEBUG: Found sidebar, adding widgets');
                sidebar.prepend(<?php echo $widgets_html; ?>);
            } else {
                console.log('DEBUG: No sidebar found');
                // Fallback: add to body with absolute positioning
                $('body').append('<div id="mc-fallback-sidebar" style="position: fixed; top: 100px; right: 20px; width: 300px; background: white; border: 1px solid #ccc; padding: 20px; z-index: 1000;">' + <?php echo $widgets_html; ?> + '</div>');
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for customer search
     */
    public function ajax_search_customers() {
        // Debug logging
        error_log('MC Quick Order: ajax_search_customers called');
        error_log('MC Quick Order: POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_collections_nonce')) {
            error_log('MC Quick Order: Customer search nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Fix: The inline JavaScript sends 'search', not 'search_term'
        $search_term = sanitize_text_field($_POST['search']);
        error_log('MC Quick Order: Searching for customers with term: ' . $search_term);
        
        if (strlen($search_term) < 2) {
            wp_send_json_error(__('Suchbegriff zu kurz', 'maison-common-quick-order'));
            return;
        }
        
        // Check if User Switching is available
        if (!class_exists('user_switching') || !method_exists('user_switching', 'maybe_switch_url')) {
            wp_send_json_error(__('User Switching Plugin nicht verfügbar', 'maison-common-quick-order'));
            return;
        }
        
        // Search for customers
        $users = get_users(array(
            'search' => '*' . $search_term . '*',
            'search_columns' => array('display_name', 'user_email', 'user_login'),
            'role__in' => array('customer', 'subscriber'),
            'number' => 10,
            'orderby' => 'display_name'
        ));
        
        if (empty($users)) {
            wp_send_json_error(__('Keine Kunden gefunden', 'maison-common-quick-order'));
            return;
        }
        
        $customers = array();
        $current_page_url = wp_get_referer() ?: home_url();
        
        foreach ($users as $user) {
            // Generate switch URL with redirect back to current page
            $switch_url = user_switching::maybe_switch_url($user);
            if ($switch_url) {
                $switch_url = add_query_arg(
                    'redirect_to',
                    rawurlencode($current_page_url),
                    $switch_url
                );
                
                $customers[] = array(
                    'id' => $user->ID,
                    'display_name' => $user->display_name,
                    'email' => $user->user_email,
                    'switch_url' => $switch_url
                );
            }
        }
        
        if (empty($customers)) {
            wp_send_json_error(__('Keine verfügbaren Kunden gefunden', 'maison-common-quick-order'));
            return;
        }
        
        wp_send_json_success($customers);
    }
    
    /**
     * Cart Totals Shortcode
     */
    public function cart_totals_shortcode($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'show_labels' => 'true',
            'separator' => ' | ',
            'class' => 'mc-cart-totals'
        ), $atts, 'mc_cart_totals');
        
        // Get current cart totals
        $totals = $this->calculate_cart_totals();
        
        // Build output HTML
        $output = '<div class="' . esc_attr($atts['class']) . '" id="mc-cart-totals">';
        
        if ($atts['show_labels'] === 'true') {
            $output .= '<span class="mc-ek-total">EK: ' . $totals['formatted']['ek'] . '</span>';
            $output .= esc_html($atts['separator']);
            $output .= '<span class="mc-vk-total">VK: ' . $totals['formatted']['vk'] . '</span>';
        } else {
            $output .= '<span class="mc-ek-total">' . $totals['formatted']['ek'] . '</span>';
            $output .= esc_html($atts['separator']);
            $output .= '<span class="mc-vk-total">' . $totals['formatted']['vk'] . '</span>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Calculate cart totals for EK and VK prices
     */
    private function calculate_cart_totals() {
        $ek_total = 0;
        $vk_total = 0;
        
        // Check if WooCommerce is available
        if (!function_exists('WC') || !WC()->cart) {
            return array(
                'ek_total' => 0,
                'vk_total' => 0,
                'formatted' => array(
                    'ek' => wc_price(0),
                    'vk' => wc_price(0)
                )
            );
        }
        
        // Get cart contents
        $cart_contents = WC()->cart->get_cart();
        
        if (empty($cart_contents)) {
            return array(
                'ek_total' => 0,
                'vk_total' => 0,
                'formatted' => array(
                    'ek' => wc_price(0),
                    'vk' => wc_price(0)
                )
            );
        }
        
        // Calculate totals
        foreach ($cart_contents as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            
            if (!$product) {
                continue;
            }
            
            // Check if this is a sampling item
            if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
                // Skip sampling items in cart totals (they have price 0)
                continue;
            }
            
            // Get EK price from _ek_price custom field
            $ek_price = get_post_meta($product->get_id(), '_ek_price', true);
            if (empty($ek_price)) {
                $ek_price = 0;
            }
            
            // Get VK price from _regular_price meta field
            $vk_price = get_post_meta($product->get_id(), '_regular_price', true);
            if (empty($vk_price)) {
                $vk_price = $product->get_regular_price();
            }
            
            // Add to totals
            $ek_total += floatval($ek_price) * $quantity;
            $vk_total += floatval($vk_price) * $quantity;
        }
        
        return array(
            'ek_total' => $ek_total,
            'vk_total' => $vk_total,
            'formatted' => array(
                'ek' => wc_price($ek_total),
                'vk' => wc_price($vk_total)
            )
        );
    }
    
    /**
     * AJAX handler for getting cart totals
     */
    public function ajax_get_cart_totals() {
        // Debug logging
        error_log('MC Quick Order: ajax_get_cart_totals called');
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mc_collections_nonce')) {
            error_log('MC Quick Order: Cart totals nonce verification failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Calculate current cart totals
        $totals = $this->calculate_cart_totals();
        
        wp_send_json_success($totals);
    }
}
