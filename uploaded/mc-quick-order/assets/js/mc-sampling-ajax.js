/**
 * Maison Common Sampling AJAX JavaScript
 * 
 * Handles EAN scanning, collection navigation, and sampling functionality
 * 
 * @package MC_Quick_Order
 */

jQuery(document).ready(function($) {
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
});
