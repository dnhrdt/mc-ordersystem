/**
 * Maison Cormont Collections AJAX Handler
 * 
 * Behandelt die AJAX-Navigation zwischen Collections
 * 
 * @version 1.0.0
 */

jQuery(document).ready(function($) {
    // Debug: Check if mc_ajax is loaded
    console.log('MC Quick Order: JavaScript loaded');
    console.log('MC Quick Order: mc_ajax object:', mc_ajax);
    
    if (typeof mc_ajax === 'undefined') {
        console.error('MC Quick Order: mc_ajax object not found! AJAX will not work.');
        return;
    }
    
    // Collection-Link Click Handler
    $(document).on('click', '.mc-collection-link', function(e) {
        e.preventDefault();
        
        var collectionId = $(this).data('collection-id');
        var $clickedLink = $(this);
        
        console.log('MC Quick Order: Collection link clicked, ID:', collectionId);
        
        if (!collectionId) {
            console.error('MC Quick Order: Keine Collection ID gefunden');
            return;
        }
        
        // Loading-Animation anzeigen
        $('#mc-loading').show();
        $('#mc-quick-order-content').hide();
        
        // Aktive Klasse entfernen und neue setzen
        $('.mc-collections-list li').removeClass('active');
        $clickedLink.closest('li').addClass('active');
        
        // AJAX-Request
        $.ajax({
            url: mc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_load_collection',
                collection_id: collectionId,
                nonce: mc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('MC Debug: AJAX success, updating content...');
                    
                    // Quick Order Inhalt aktualisieren
                    $('#mc-quick-order-content').html(response.data.quick_order_content);
                    
                    console.log('MC Debug: Content updated, checking if table exists...');
                    console.log('MC Debug: Table exists after HTML update:', $('#woocommerce-quick-order').length > 0);
                    
                    // TIMING-FIX: Warten bis DOM vollständig aktualisiert ist
                    setTimeout(function() {
                        console.log('MC Debug: Starting delayed reinitialization...');
                        console.log('MC Debug: Table exists before reinit:', $('#woocommerce-quick-order').length > 0);
                        
                        // DataTables und andere JavaScript-Komponenten reinitialisieren
                        reinitializeQuickOrderTable();
                    }, 50); // 50ms Delay für DOM-Update
                    
                    // URL aktualisieren (optional, für bessere UX)
                    if (history.pushState) {
                        var newUrl = window.location.protocol + "//" + window.location.host + 
                                   window.location.pathname + '?collection=' + collectionId;
                        history.pushState({collectionId: collectionId}, '', newUrl);
                    }
                    
                } else {
                    console.error('Fehler beim Laden der Collection:', response.data);
                    alert('Fehler beim Laden der Collection. Bitte versuchen Sie es erneut.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX-Fehler:', error);
                alert('Verbindungsfehler. Bitte versuchen Sie es erneut.');
            },
            complete: function() {
                // Loading-Animation ausblenden
                $('#mc-loading').hide();
                $('#mc-quick-order-content').show();
            }
        });
    });
    
    // Browser Back/Forward Button Support
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.collectionId) {
            // Collection über AJAX laden ohne URL zu ändern
            loadCollectionById(event.state.collectionId, false);
        }
    });
    
    /**
     * Lädt eine Collection über AJAX
     * 
     * @param {number} collectionId - Die Collection ID
     * @param {boolean} updateUrl - Ob die URL aktualisiert werden soll
     */
    function loadCollectionById(collectionId, updateUrl = true) {
        $('#mc-loading').show();
        $('#mc-quick-order-content').hide();
        
        // Aktive Klasse setzen
        $('.mc-collections-list li').removeClass('active');
        $('.mc-collection-link[data-collection-id="' + collectionId + '"]').closest('li').addClass('active');
        
        $.ajax({
            url: mc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_load_collection',
                collection_id: collectionId,
                nonce: mc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('MC Debug: loadCollectionById AJAX success, updating content...');
                    
                    $('#mc-quick-order-content').html(response.data.quick_order_content);
                    
                    console.log('MC Debug: loadCollectionById content updated, checking if table exists...');
                    console.log('MC Debug: loadCollectionById table exists after HTML update:', $('#woocommerce-quick-order').length > 0);
                    
                    // TIMING-FIX: Warten bis DOM vollständig aktualisiert ist
                    setTimeout(function() {
                        console.log('MC Debug: loadCollectionById starting delayed reinitialization...');
                        console.log('MC Debug: loadCollectionById table exists before reinit:', $('#woocommerce-quick-order').length > 0);
                        
                        // DataTables und andere JavaScript-Komponenten reinitialisieren
                        reinitializeQuickOrderTable();
                    }, 50); // 50ms Delay für DOM-Update
                    
                    if (updateUrl && history.pushState) {
                        var newUrl = window.location.protocol + "//" + window.location.host + 
                                   window.location.pathname + '?collection=' + collectionId;
                        history.pushState({collectionId: collectionId}, '', newUrl);
                    }
                }
            },
            complete: function() {
                $('#mc-loading').hide();
                $('#mc-quick-order-content').show();
            }
        });
    }
    
    // Smooth Scrolling für bessere UX (optional)
    $(document).on('click', '.mc-collection-link', function() {
        $('html, body').animate({
            scrollTop: $('#mc-collections-container').offset().top - 50
        }, 300);
    });
    
    // Initial State für Browser History
    if (history.replaceState) {
        var currentCollectionId = $('.mc-collections-list li.active .mc-collection-link').data('collection-id');
        if (currentCollectionId) {
            history.replaceState({collectionId: currentCollectionId}, '');
        }
    }
    
    /**
     * Initialize cart totals monitoring for live updates
     */
    function initCartTotalsMonitoring() {
        console.log('MC Quick Order: Initializing cart totals monitoring');
        
        // Monitor WooCommerce cart events
        $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
            console.log('MC Quick Order: added_to_cart event fired', {event, fragments, cart_hash, button});
            updateCartTotals();
        });
        
        $(document.body).on('removed_from_cart', function(event, fragments, cart_hash) {
            console.log('MC Quick Order: removed_from_cart event fired', {event, fragments, cart_hash});
            updateCartTotals();
        });
        
        // Also monitor for cart updates (quantity changes, etc.)
        $(document.body).on('updated_cart_totals', function() {
            console.log('MC Quick Order: updated_cart_totals event fired');
            updateCartTotals();
        });
        
        // Monitor for WooCommerce fragments update (more reliable)
        $(document.body).on('wc_fragments_refreshed', function() {
            console.log('MC Quick Order: wc_fragments_refreshed event fired');
            updateCartTotals();
        });
        
        // Monitor for cart hash changes
        $(document.body).on('wc_cart_hash_changed', function() {
            console.log('MC Quick Order: wc_cart_hash_changed event fired');
            updateCartTotals();
        });
        
        // Monitor for any AJAX success that might indicate cart changes
        $(document).ajaxSuccess(function(event, xhr, settings) {
            // Check if this is a WooCommerce add to cart AJAX request
            if (settings.data && typeof settings.data === 'string') {
                if (settings.data.indexOf('add-to-cart') !== -1 || 
                    settings.data.indexOf('woocommerce_add_to_cart') !== -1 ||
                    settings.data.indexOf('woocommerce_quick_order') !== -1) {
                    console.log('MC Quick Order: WooCommerce AJAX detected, updating totals', settings);
                    setTimeout(updateCartTotals, 500); // Small delay to ensure cart is updated
                }
            }
        });
        
        // Fallback: Monitor for any button clicks that might add to cart
        $(document).on('click', '.add_to_cart_button, .single_add_to_cart_button, .woocommerce-quick-order-single-add-to-cart-button', function() {
            console.log('MC Quick Order: Add to cart button clicked, will update totals after delay');
            setTimeout(updateCartTotals, 1000); // Delay to allow cart update
        });
    }
    
    /**
     * Update cart totals display via AJAX
     */
    function updateCartTotals() {
        // Find all cart totals elements
        var cartTotalsElements = $('.mc-cart-totals, #mc-cart-totals');
        
        if (cartTotalsElements.length === 0) {
            console.log('MC Quick Order: No cart totals elements found');
            return;
        }
        
        // Make AJAX request to get updated totals
        $.ajax({
            url: mc_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_get_cart_totals',
                nonce: mc_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log('MC Quick Order: Cart totals updated', response.data);
                    
                    // Update all cart totals displays
                    cartTotalsElements.each(function() {
                        var $element = $(this);
                        
                        // Update EK total
                        $element.find('.mc-ek-total').html('EK: ' + response.data.formatted.ek);
                        
                        // Update VK total  
                        $element.find('.mc-vk-total').html('VK: ' + response.data.formatted.vk);
                    });
                    
                } else {
                    console.error('MC Quick Order: Failed to update cart totals', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('MC Quick Order: AJAX error updating cart totals', error);
            }
        });
    }
    
    /**
     * Reinitialisiert die Quick Order Tabelle nach AJAX-Reload
     * Stellt sicher, dass DataTables und andere JS-Komponenten funktionieren
     */
    function reinitializeQuickOrderTable() {
        console.log('MC Quick Order: Reinitializing Quick Order table components');

        // WooCommerce Quick Order Plugin komplett reinitialisieren
        if (typeof $.fn.quickOrder !== 'undefined' && typeof woocommerce_quick_order_options !== 'undefined') {
            
            var quickOrderTable = $('#woocommerce-quick-order');

            // Zerstöre eine eventuell noch vorhandene DataTable-Instanz.
            // Sicher ist sicher, auch wenn die vorherigen Logs zeigten, dass keine gefunden wurde.
            if (quickOrderTable.length > 0 && $.fn.DataTable.isDataTable(quickOrderTable)) {
                quickOrderTable.DataTable().destroy();
                console.log('MC Quick Order: Destroyed existing DataTable instance.');
            }

            // *** DER ENTSCHEIDENDE FIX ***
            // Entferne die Initialisierungs-Markierung des Plugins vom Body.
            // Dies zwingt das Plugin, sich komplett neu zu initialisieren.
            $("body").removeData('plugin_quickOrder');
            console.log('MC Quick Order: Removed plugin instance data from body.');

            // Stelle sicher, dass die Tabelle die vom Plugin erwartete Klasse hat.
            if (quickOrderTable.length > 0 && !quickOrderTable.hasClass('datatables')) {
                console.log('MC Debug: Adding missing "datatables" class to table');
                quickOrderTable.addClass('datatables');
            }
            
            // Rufe das Plugin neu auf. Es wird sich jetzt initialisieren,
            // da die "plugin_quickOrder" Daten nicht mehr am Body hängen.
            console.log('MC Quick Order: Reinitializing WooCommerce Quick Order Plugin.');
            $("body").quickOrder(woocommerce_quick_order_options);
            
            // Kurzer Check nach der Reinitialisierung
            setTimeout(function() {
                var headerExistsAfter = $('.dataTables_filter, .dataTables_length, .dataTables_info').length > 0;
                console.log('MC Quick Order: Check after reinit - Header elements exist:', headerExistsAfter);
                if (headerExistsAfter) {
                    console.log('%cMC Quick Order: SUCCESS! Headers have been reinitialized.', 'color: green; font-weight: bold;');
                } else {
                    console.error('%cMC Quick Order: FAILED! Headers still missing after fix.', 'color: red; font-weight: bold;');
                }
            }, 100);

        } else {
            console.error('MC Quick Order: WooCommerce Quick Order Plugin or options not available.');
        }
        
        // Cart Totals Monitoring für neue Elemente reinitialisieren
        initCartTotalsMonitoring();
        
        console.log('MC Quick Order: Table reinitialization complete');
    }
    
    
    // Cart Totals Live Update Functionality - Initialize after all functions are defined
    initCartTotalsMonitoring();
});
