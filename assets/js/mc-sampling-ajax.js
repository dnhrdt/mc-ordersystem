/**
 * Maison Cormont Sampling AJAX Handler
 * 
 * Behandelt die AJAX-Funktionalität für das Sampling System
 * Inkludiert EAN-Scanner Integration
 * 
 * @version 1.1.0
 */

jQuery(document).ready(function($) {
    
    // EAN Scanner Integration
    let scannerActive = false;
    let scanBuffer = '';
    let scanTimeout = null;
    
    /**
     * EAN Scanner Initialization
     */
    function initEANScanner() {
        console.log('MC Sampling: Initializing EAN Scanner');
        
        // Keyboard event listener for scanner input
        $(document).on('keypress', function(e) {
            if (!scannerActive) return;
            
            // Clear previous timeout
            if (scanTimeout) {
                clearTimeout(scanTimeout);
            }
            
            // Add character to buffer
            if (e.which >= 32) { // Printable characters
                scanBuffer += String.fromCharCode(e.which);
            }
            
            // Set timeout to process scan (scanners typically input very fast)
            scanTimeout = setTimeout(function() {
                if (scanBuffer.length >= 8) { // Minimum EAN length
                    processScanResult(scanBuffer.trim());
                }
                scanBuffer = '';
            }, 100); // 100ms timeout
            
            // Prevent default if we're in scan mode
            if (scanBuffer.length > 0) {
                e.preventDefault();
            }
        });
        
        // Enter key handling for scanner
        $(document).on('keydown', function(e) {
            if (!scannerActive) return;
            
            if (e.which === 13 && scanBuffer.length > 0) { // Enter key
                e.preventDefault();
                if (scanTimeout) {
                    clearTimeout(scanTimeout);
                }
                processScanResult(scanBuffer.trim());
                scanBuffer = '';
            }
        });
    }
    
    /**
     * Process scanned EAN code
     */
    function processScanResult(eanCode) {
        console.log('MC Sampling: EAN scanned:', eanCode);
        
        // Validate EAN format (basic check)
        if (!/^\d{8,14}$/.test(eanCode)) {
            showScanFeedback('Ungültiger EAN-Code: ' + eanCode, 'error');
            return;
        }
        
        // Show scanning feedback
        showScanFeedback('EAN gescannt: ' + eanCode, 'success');
        
        // Search for product and add to sampling list
        searchAndAddProduct(eanCode);
    }
    
    /**
     * Search for product by EAN and add to sampling
     */
    function searchAndAddProduct(eanCode) {
        $.ajax({
            url: mc_sampling_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_search_product_by_ean',
                ean_code: eanCode,
                nonce: mc_sampling_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const product = response.data.product;
                    
                    // Add product to sampling list
                    addProductToSampling(product);
                    
                    showScanFeedback(`Produkt hinzugefügt: ${product.name}`, 'success');
                } else {
                    showScanFeedback(`Produkt nicht gefunden: ${eanCode}`, 'error');
                }
            },
            error: function() {
                showScanFeedback('Fehler beim Suchen des Produkts', 'error');
            }
        });
    }
    
    /**
     * Add product to sampling list
     */
    function addProductToSampling(product) {
        const samplingList = $('#mc-sampling-list tbody');
        
        // Check if product already exists
        const existingRow = samplingList.find(`tr[data-product-id="${product.id}"]`);
        if (existingRow.length > 0) {
            // Update quantity
            const qtyInput = existingRow.find('.sampling-quantity');
            const currentQty = parseInt(qtyInput.val()) || 1;
            qtyInput.val(currentQty + 1);
            
            // Highlight updated row
            existingRow.addClass('updated-row');
            setTimeout(() => existingRow.removeClass('updated-row'), 2000);
            return;
        }
        
        // Create new row
        const newRow = $(`
            <tr data-product-id="${product.id}" class="new-row">
                <td>
                    <img src="${product.image}" alt="${product.name}" style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td>
                    <strong>${product.name}</strong><br>
                    <small>EAN: ${product.ean}</small>
                </td>
                <td>
                    <input type="number" class="sampling-quantity" name="sampling_products[${product.id}][quantity]" 
                           value="1" min="1" max="10" style="width: 60px;">
                </td>
                <td>
                    <button type="button" class="button remove-sampling-item" data-product-id="${product.id}">
                        Entfernen
                    </button>
                </td>
            </tr>
        `);
        
        samplingList.append(newRow);
        
        // Remove highlight after animation
        setTimeout(() => newRow.removeClass('new-row'), 2000);
        
        // Update sampling count
        updateSamplingCount();
    }
    
    /**
     * Show scan feedback to user
     */
    function showScanFeedback(message, type = 'info') {
        // Remove existing feedback
        $('.scan-feedback').remove();
        
        const feedbackClass = type === 'error' ? 'notice-error' : 'notice-success';
        const feedback = $(`
            <div class="scan-feedback notice ${feedbackClass} is-dismissible" style="margin: 10px 0;">
                <p>${message}</p>
            </div>
        `);
        
        // Insert feedback at top of sampling form
        $('#mc-sampling-form').prepend(feedback);
        
        // Auto-remove after 3 seconds
        setTimeout(() => feedback.fadeOut(() => feedback.remove()), 3000);
    }
    
    /**
     * Toggle scanner mode
     */
    function toggleScanner() {
        scannerActive = !scannerActive;
        const button = $('#toggle-scanner');
        
        if (scannerActive) {
            button.text('Scanner deaktivieren').addClass('scanner-active');
            showScanFeedback('EAN-Scanner aktiviert - Scannen Sie jetzt Produkte', 'success');
        } else {
            button.text('EAN-Scanner aktivieren').removeClass('scanner-active');
            showScanFeedback('EAN-Scanner deaktiviert', 'info');
        }
        
        // Clear any pending scan
        scanBuffer = '';
        if (scanTimeout) {
            clearTimeout(scanTimeout);
        }
    }
    
    /**
     * Update sampling count display
     */
    function updateSamplingCount() {
        const count = $('#mc-sampling-list tbody tr').length;
        $('#sampling-count').text(count);
    }
    
    // Initialize scanner on page load
    initEANScanner();
    
    // Scanner toggle button handler
    $(document).on('click', '#toggle-scanner', toggleScanner);
    
    // Remove sampling item handler
    $(document).on('click', '.remove-sampling-item', function() {
        const productId = $(this).data('product-id');
        $(this).closest('tr').fadeOut(() => {
            $(this).closest('tr').remove();
            updateSamplingCount();
        });
    });
    'use strict';
    
    // EAN input handling
    $('#mc-ean-input').on('keypress', function(e) {
        // Auto-submit on Enter or when 13 characters are entered
        if (e.which === 13 || $(this).val().length >= 12) {
            setTimeout(function() {
                $('#mc-scan-button').click();
            }, 100);
        }
        
        // Only allow digits
        if (e.which < 48 || e.which > 57) {
            e.preventDefault();
        }
    });
    
    // Auto-focus EAN input
    $('#mc-ean-input').focus();
    
    // Scan button click handler
    $('#mc-scan-button').on('click', function() {
        var ean = $('#mc-ean-input').val().trim();
        
        if (ean.length !== 13) {
            showScanFeedback('error', mc_sampling_ajax.strings.invalid_ean);
            return;
        }
        
        scanEAN(ean);
    });
    
    // Collection navigation
    $(document).on('click', '.mc-sampling-collection-link', function(e) {
        e.preventDefault();
        
        var collectionId = $(this).data('collection-id');
        loadSamplingCollection(collectionId);
        
        // Update active state
        $('.mc-sampling-collection-link').removeClass('active');
        $(this).addClass('active');
    });
    
    /**
     * Scan EAN code
     */
    function scanEAN(ean) {
        var $button = $('#mc-scan-button');
        var $input = $('#mc-ean-input');
        
        // Show loading state
        $button.prop('disabled', true).text(mc_sampling_ajax.strings.scanning);
        showScanFeedback('info', mc_sampling_ajax.strings.scanning);
        
        $.ajax({
            url: mc_sampling_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_scan_ean',
                ean: ean,
                nonce: mc_sampling_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showScanFeedback('success', response.data.message);
                    addToScanHistory(response.data);
                    // highlightScannedProductInTable(ean); // Highlight feature removed as per new requirements
                    $input.val('').focus(); // Clear input and refocus
                } else {
                    showScanFeedback('error', response.data || mc_sampling_ajax.strings.scan_error);
                }
            },
            error: function() {
                showScanFeedback('error', mc_sampling_ajax.strings.scan_error);
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false).text('Hinzufügen');
            }
        });
    }
    
    /**
     * Load sampling collection
     */
    function loadSamplingCollection(collectionId) {
        var $content = $('#mc-sampling-content');
        
        // Show loading
        $content.html('<div class="mc-loading"><p>Lade Collection...</p></div>');
        
        $.ajax({
            url: mc_sampling_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_load_sampling_collection',
                collection_id: collectionId,
                nonce: mc_sampling_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $content.html(response.data.content);
                } else {
                    $content.html('<div class="woocommerce-error"><p>' + (response.data || 'Fehler beim Laden der Collection') + '</p></div>');
                }
            },
            error: function() {
                $content.html('<div class="woocommerce-error"><p>Fehler beim Laden der Collection</p></div>');
            }
        });
    }
    
    /**
     * Show scan feedback
     */
    function showScanFeedback(type, message) {
        var $feedback = $('#mc-scan-feedback');
        var cssClass = 'mc-scan-' + type;
        
        $feedback
            .removeClass('mc-scan-success mc-scan-error mc-scan-info')
            .addClass(cssClass)
            .html('<p>' + message + '</p>')
            .show();
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $feedback.fadeOut();
            }, 3000);
        }
    }
    
    /**
     * Add item to scan history
     */
    function addToScanHistory(data) {
        var $historyList = $('#mc-scan-history-list');
        var timestamp = new Date().toLocaleTimeString();
        
        var historyItem = $('<li class="mc-scan-history-item">')
            .html(
                '<strong>' + data.product_name + '</strong><br>' +
                '<small>Artikel-ID: ' + data.artikel_id + ' | ' + timestamp + '</small>'
            );
        
        $historyList.prepend(historyItem);
        
        // Limit history to 10 items
        $historyList.children().slice(10).remove();
        
        // Show history section if it was hidden
        $('#mc-scan-history').show();
        
        // Animate new item
        historyItem.hide().fadeIn();
    }
    
    /**
     * Debounce function for input handling
     */
    function debounce(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Auto-focus EAN input when page loads
    setTimeout(function() {
        $('#mc-ean-input').focus();
    }, 500);
    
    // Prevent form submission on Enter in EAN input
    $('#mc-ean-input').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
        }
    });
    
    // Clear feedback when starting to type new EAN
    $('#mc-ean-input').on('input', function() {
        if ($(this).val().length === 0) {
            $('#mc-scan-feedback').hide();
        }
    });

    /**
     * Robust DataTables integration using MutationObserver and proper event delegation
     */
    function initializeTableButtonHandler() {
        console.log('MC DEBUG: Initializing robust table button handler');
        
        // Use MutationObserver to detect when the table is added/modified
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    var samplingTable = document.querySelector('.mc-sampling-quick-order-table table.woocommerce-quick-order');
                    if (samplingTable && $.fn.DataTable.isDataTable(samplingTable)) {
                        console.log('MC DEBUG: Sampling table detected, processing rows...');
                        processTableRows();
                        // Stop observing once we've processed the table
                        observer.disconnect();
                    }
                }
            });
        });
        
        // Start observing
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        // Also try immediately in case table is already there
        setTimeout(function() {
            var samplingTable = document.querySelector('.mc-sampling-quick-order-table table.woocommerce-quick-order');
            if (samplingTable && $.fn.DataTable.isDataTable(samplingTable)) {
                console.log('MC DEBUG: Table already present, processing immediately');
                processTableRows();
                observer.disconnect();
            }
        }, 100);
    }
    
    /**
     * Process table rows and replace dropdowns with simple buttons
     */
    function processTableRows() {
        var $table = $('.mc-sampling-quick-order-table table.woocommerce-quick-order');
        if (!$table.length) return;
        
        console.log('MC DEBUG: Processing table rows for sampling buttons');
        
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            var cartCell = $row.find('.quick-order-table-value-ca');
            var variableForm = cartCell.find('.woocommerce-quick-order-variable-add-to-cart');
            
            // Skip if already processed or not a variable product row
            if (cartCell.find('.mc-add-sampling-button').length > 0 || variableForm.length === 0) {
                return;
            }
            
            // Extract product ID from row ID
            var productId = $row.attr('id');
            if (productId) {
                productId = productId.replace('quick-order-table-row-', '');
            }
            
            if (!productId) {
                console.warn('MC DEBUG: Could not extract product ID from row');
                return;
            }
            
            console.log('MC DEBUG: Converting row for product ID:', productId);
            
            // Create our simple button
            var newButton = '<button type="button" class="button mc-add-sampling-button" data-product-id="' + productId + '">Hinzufügen</button>';
            
            // Replace the dropdown form with our button
            cartCell.html(newButton);
        });
        
        console.log('MC DEBUG: Table row processing complete');
    }
    
    // Set up delegated event handler for sampling buttons (this runs once)
    $(document).on('click', '.mc-add-sampling-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var productId = $button.data('product-id');
        
        console.log('MC DEBUG: Sampling button clicked for product:', productId);
        
        if (!productId) {
            console.error('MC Sampling: Could not find product ID for button');
            alert('Fehler: Produkt-ID nicht gefunden');
            return;
        }
        
        // Update button state
        $button.prop('disabled', true).addClass('loading').text('Wird hinzugefügt...');
        
        $.ajax({
            url: mc_sampling_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mc_add_sampling_from_table',
                product_id: productId,
                nonce: mc_sampling_ajax.nonce
            },
            success: function(response) {
                console.log('MC DEBUG: AJAX response:', response);
                
                if (response.success) {
                    $button.text('✓ Hinzugefügt').addClass('added').removeClass('loading');
                    
                    // Trigger WooCommerce cart update
                    $(document.body).trigger('added_to_cart');
                    
                    // Reset button after 3 seconds
                    setTimeout(function() {
                        $button.prop('disabled', false).removeClass('added').text('Hinzufügen');
                    }, 3000);
                    
                } else {
                    console.error('MC DEBUG: Server returned error:', response.data);
                    $button.text('Fehler').removeClass('loading');
                    alert('Fehler: ' + (response.data || 'Unbekannter Fehler'));
                    
                    // Reset button after 2 seconds
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Hinzufügen');
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('MC DEBUG: AJAX error:', status, error);
                $button.text('Verbindungsfehler').removeClass('loading');
                alert('Verbindungsfehler. Bitte versuchen Sie es erneut.');
                
                // Reset button after 2 seconds
                setTimeout(function() {
                    $button.prop('disabled', false).text('Hinzufügen');
                }, 2000);
            }
        });
    });
    
    // Initialize the table handler
    initializeTableButtonHandler();
});
