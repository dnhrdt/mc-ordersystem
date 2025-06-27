# Cart Cleanup Functions - Dokumentation für Version 1.5.1

## Übersicht
Diese Funktionen wurden aus Version 1.5.0 entfernt und für Version 1.5.1 dokumentiert, um gezielteren Cart-Display-Cleanup für Sampling-Items zu implementieren.

## Entfernte WooCommerce-Filter (aus init_hooks())
```php
// Cart display cleanup for sampling items
add_filter('woocommerce_cart_item_name', array($this, 'clean_sampling_cart_item_name'), 10, 3);
add_filter('woocommerce_cart_item_permalink', array($this, 'remove_sampling_cart_item_permalink'), 10, 3);
add_filter('woocommerce_is_attribute_in_product_name', array($this, 'hide_sampling_attributes_in_name'), 10, 3);
```

## Entfernte Funktionen

### 1. clean_sampling_cart_item_name()
**Zweck:** Produktname ohne Größenangaben für Sampling-Items anzeigen

```php
/**
 * Clean cart item name for sampling items - remove size indicators and use parent product name
 */
public function clean_sampling_cart_item_name($product_name, $cart_item, $cart_item_key) {
    if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
        // Use the stored parent product name if available
        if (isset($cart_item['sampling_parent_name'])) {
            return esc_html($cart_item['sampling_parent_name']);
        }
        
        // Fallback: Get parent product and use its name
        if (isset($cart_item['sampling_parent_id'])) {
            $parent_product = wc_get_product($cart_item['sampling_parent_id']);
            if ($parent_product) {
                return esc_html($parent_product->get_name());
            }
        }
        
        // Last fallback: strip variation info from existing name
        // Remove common size patterns like "- XS", "- S", "- 36", etc.
        $clean_name = preg_replace('/\s*[-–]\s*(XS|S|M|L|XL|XXL|\d{2,3})\s*$/', '', $product_name);
        return $clean_name;
    }
    
    return $product_name;
}
```

### 2. remove_sampling_cart_item_permalink()
**Zweck:** Produktlinks für Sampling-Items entfernen (verhindert "please choose product options" Warnungen)

```php
/**
 * Remove permalink for sampling items to prevent "please choose product options" warnings
 */
public function remove_sampling_cart_item_permalink($product_permalink, $cart_item, $cart_item_key) {
    if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
        return '';
    }
    
    return $product_permalink;
}
```

### 3. hide_sampling_attributes_in_name()
**Zweck:** Varianten-Attribute in Produktnamen für Sampling-Items ausblenden

```php
/**
 * Hide variation attributes in product name for sampling items
 */
public function hide_sampling_attributes_in_name($show, $attribute_name, $product) {
    // Check if we're in a cart context with sampling items
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
                // If this product matches a sampling item, hide attributes in name
                if ($cart_item['product_id'] == $product->get_id() || 
                    (isset($cart_item['sampling_parent_id']) && $cart_item['sampling_parent_id'] == $product->get_id())) {
                    return false;
                }
            }
        }
    }
    
    return $show;
}
```

## Änderung an display_sampling_cart_item_data()
**Original (zeigt alle Daten):**
```php
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
```

**Vereinfacht (nur Artikel-ID):**
```php
public function display_sampling_cart_item_data($item_data, $cart_item) {
    if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
        // Only show Artikel-ID for sampling items as requested by user
        if (isset($cart_item['artikel_id'])) {
            $item_data[] = array(
                'key' => __('Artikel-ID', 'maison-common-quick-order'),
                'value' => $cart_item['artikel_id']
            );
        }
    }
    
    return $item_data;
}
```

## Implementierungsplan für Version 1.5.1

### Problem-Identifikation
- **Order-System Items:** Brauchen detaillierte Warenkorb-Informationen
- **Sampling-System Items:** Brauchen minimale Warenkorb-Informationen  
- **Mixed Cart:** Beide Item-Typen gleichzeitig → Verwirrung

### Lösungsansatz
1. **Smart Cart Display:** Unterscheidung zwischen Order- und Sampling-Items
2. **Bedingte Filter:** Nur für Sampling-Items anwenden
3. **Mixed-Cart-Detection:** Warnung bei gemischten Warenkorb-Inhalten
4. **Cart-Reset-Funktion:** Schneller Warenkorb-Reset

### Technische Umsetzung
```php
// Beispiel für bedingte Filter-Anwendung
public function clean_sampling_cart_item_name($product_name, $cart_item, $cart_item_key) {
    // Nur für Sampling-Items anwenden
    if (isset($cart_item['is_sampling']) && $cart_item['is_sampling']) {
        // Cleanup-Logik hier
    }
    
    // Order-System Items unverändert lassen
    return $product_name;
}
```

## Zusätzliche Features für Version 1.5.1
- **Mixed-Cart-Detection System**
- **Cart-Reset-Funktionalität**  
- **Erweiterte Cart-Display-Unterscheidung**
- **Workflow-Sicherheitsfeatures**

## Status
- **Version 1.5.0:** Funktionen entfernt (Reset durchgeführt)
- **Version 1.5.1:** Geplante Wiedereinführung mit intelligenter Unterscheidung
- **Dokumentation:** Vollständig für Reimplementierung verfügbar
