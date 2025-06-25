# Maison Common Quick Order Plugin v1.3.0

## Neue modulare Architektur

Version 1.3.0 führt eine komplett neue modulare Architektur ein, die das Plugin in drei separate Komponenten aufteilt:

### Dateistruktur

```
maison-common-quick-order/
├── maison-common-quick-order.php     # Basis-Plugin mit gemeinsamen Funktionen
├── includes/
│   ├── class-mc-order-system.php     # Order-System (bestehende Funktionalität)
│   └── class-mc-sampling-system.php  # Sampling-System (neue Abmusterung)
├── assets/
│   ├── js/
│   │   ├── mc-collections-ajax.js    # Order-System JavaScript
│   │   └── mc-sampling-ajax.js       # Sampling-System JavaScript
│   └── css/
│       ├── mc-collections-style.css  # Order-System Styles
│       └── mc-sampling-style.css     # Sampling-System Styles
└── CHANGELOG.md
```

## Neue Features in v1.3.0

### Abmusterungs-System

Das neue Sampling-System ermöglicht EAN-basierte Abmusterung mit folgenden Features:

#### Shortcode: `[mc_sampling]`

```php
// Basis-Verwendung
[mc_sampling]

// Mit spezifischer Collection
[mc_sampling collection_id="123"]

// Nur Scanner ohne Collections-Navigation
[mc_sampling show_collections="false"]

// Nur Collections ohne Scanner
[mc_sampling show_scanner="false"]
```

#### EAN-Scanner-Interface

- **HID-Device-optimiert:** Funktioniert mit Standard-Bluetooth-Scannern
- **Auto-Submit:** Automatische Verarbeitung nach 13 Zeichen oder Enter
- **Fallback-Eingabe:** Manuelle EAN-Eingabe möglich
- **Scan-Historie:** Session-basierte Historie der gescannten Produkte

#### Workflow

1. **EAN scannen** → Variante finden → Parent-Produkt ermitteln → SKU als Artikel-ID
2. **Custom Cart Item** erstellen mit Metadaten:
   - `is_sampling: true`
   - `artikel_id: Parent-SKU`
   - `scanned_ean: Original-EAN`
   - `sampling_size: "Abmusterung"`
3. **Preis = 0** für alle Abmusterungs-Items
4. **Unterschrift** im Checkout erforderlich

### Collection-Management

#### Sampling-Collections markieren

Im WordPress-Backend unter **Produkte → Collections**:

1. Collection bearbeiten
2. Checkbox "Abmusterungs-Collection" aktivieren
3. Speichern

Nur markierte Collections erscheinen im Sampling-Interface.

### LSE-Integration vorbereitet

Abmusterungs-Bestellungen enthalten:

- **Artikel-ID** (Parent-SKU) für jedes Item
- **Unterschrift-Flag** als Order Meta
- **EAN-Codes** der gescannten Varianten
- **Timestamp** der Unterschrift

## Bestehende Features (Order-System)

Alle bisherigen Features bleiben unverändert:

- **Collections-Navigation** via AJAX
- **Cart Totals** mit EK/VK-Preisen
- **User Switching** Integration
- **Quick Order Plugin** Integration
- **WooCommerce-konforme** Formatierung

## Installation

1. **Plugin-Dateien** in `/wp-content/plugins/maison-common-quick-order/` hochladen
2. **Plugin aktivieren** im WordPress-Backend
3. **Collections markieren** als Sampling-Collections (optional)
4. **Shortcodes verwenden:**
   - `[mc_quick_order]` für Order-System
   - `[mc_sampling]` für Abmusterung
   - `[mc_cart_totals]` für Warenkorb-Summen

## Technische Anforderungen

- **WordPress** 5.0+
- **WooCommerce** 4.0+
- **PHP** 7.4+
- **Quick Order Plugin** für Order-System
- **User Switching Plugin** für Kundenwechsel (optional)

## EAN-Feld-Kompatibilität

Das Sampling-System sucht EAN-Codes in folgenden Meta-Feldern:

- `_ean` (Standard)
- `_ean13`
- `_barcode`
- `_gtin`

## Browser-Kompatibilität

- **Desktop:** Chrome, Firefox, Safari, Edge
- **Mobile:** iOS Safari, Chrome Mobile
- **Tablets:** iPad-optimiert für Touch-Bedienung

## Support

Bei Problemen oder Fragen:

1. **CHANGELOG.md** für Änderungen prüfen
2. **Browser-Konsole** auf JavaScript-Fehler prüfen
3. **WordPress Debug-Log** aktivieren für PHP-Fehler

## Entwicklung

### Debugging aktivieren

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### AJAX-Debugging

Alle AJAX-Calls werden in das WordPress Error-Log geschrieben:

- `MC Quick Order:` für Order-System
- `MC Sampling:` für Sampling-System

### Custom Hooks

```php
// Sampling-spezifische Hooks
do_action('mc_sampling_item_added', $cart_item_key, $product_data);
apply_filters('mc_sampling_ean_fields', $ean_fields);
apply_filters('mc_sampling_collections', $collections);
```

## Migration von v1.2.0

Die Migration erfolgt automatisch. Alle bestehenden Funktionen bleiben unverändert. Das neue Sampling-System ist optional und muss explizit über den `[mc_sampling]` Shortcode aktiviert werden.

## Roadmap

### Version 1.4.0 (geplant)

- **Verkaufsbuch-Generierung** aus Quick Order Tabellen
- **Erweiterte Unterschriften** mit Canvas-Integration
- **Barcode-Generierung** für Collections
- **Export-Funktionen** für LSE-Integration

### Version 1.5.0 (geplant)

- **Multi-Scanner-Support** für mehrere Geräte
- **Offline-Modus** für iPad-Nutzung
- **Erweiterte Reporting** für Abmusterungen
- **API-Integration** für externe Systeme
