# Maison Common Quick Order Plugin v1.5.0-dev

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-4.0%2B-purple.svg)](https://woocommerce.com/)

Ein WordPress-Plugin für WooCommerce, das eine erweiterte Quick Order Funktionalität mit EAN-Scanner und Abmusterungs-System für Maison Common bereitstellt.

## 🚀 Features

- **Quick Order System** - Schnelle Bestellabwicklung mit Collection-Navigation
- **EAN-Scanner Integration** - Bluetooth-Scanner für Abmusterungen
- **Sampling System** - Vollständiges Abmusterungs-Workflow
- **Cart Totals** - Live EK/VK-Preise im Warenkorb
- **User Switching** - Kundenbestellungen im Namen von Kunden
- **Signature Integration** - Digitale Unterschriften für Abmusterungen

## 📋 Quick Start

```php
// Order-System für normale Bestellungen
[mc_quick_order]

// Sampling-System für Abmusterungen
[mc_sampling]

// Cart Totals mit EK/VK-Preisen
[mc_cart_totals]
```

## Neue modulare Architektur

Version 1.3.1 führt eine komplett neue modulare Architektur ein, die das Plugin in drei separate Komponenten aufteilt:

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

## Neue Features in v1.5.0-dev

### 🔧 Header-Bug Fix
- **AJAX-Reinitialisierung:** Such-/Filter-Header bleiben nach Collections-Wechsel erhalten
- **Plugin-Reinitialisierung:** Vollständige Neu-Initialisierung durch `removeData('plugin_quickOrder')`
- **Nahtlose Navigation:** Keine Header-Verluste mehr bei Collection-Navigation

### 📱 EAN-Scanner Quick Order Integration
- **SKU-basierte Filterung:** Parent-SKU-Extraktion mit Regex-Pattern (`/-1-(\d+)-0-\d+$/`)
- **DataTables Custom Search:** Intelligente Filterung der Quick Order Tabelle
- **Fallback-Mechanismus:** Automatische SKU-Extraktion wenn Parent keine SKU hat
- **Filter-Reset:** "Filter zurücksetzen" Button für vollständige Tabellen-Anzeige

### 🛒 Sampling System Backend
- **WooCommerce-konforme Lösung:** Verwendet erste Variation für add_to_cart Validierung
- **Custom Cart Data:** Sampling-spezifische Metadaten (`is_sampling`, `artikel_id`, `sampling_parent_id`)
- **Event-Delegation:** MutationObserver + Event-Delegation für dynamische Button-Bindung
- **Produktionsreifer Code:** Alle Debug-Ausgaben entfernt

### ⚠️ Bekannte Optimierungsbedarfe
- **Timing-Problem:** Sampling Frontend funktional aber langsam (5-6s Verzögerung)
- **Polling-Mechanismus:** Wartet auf DataTables-Initialisierung (Daniel Barrenkamp Feedback ausstehend)
- **Cart-Display:** Reset für gezielte v1.5.1 Optimierung durchgeführt

### 📋 Dokumentation & Vorbereitung
- **Memory Bank:** Vollständige Status-Updates und Dokumentation
- **Cart-Cleanup-Funktionen:** Für v1.5.1 dokumentiert (`memory_bank/cart-cleanup-functions-v1.5.1.md`)
- **Archivierung:** Obsolete Dateien in `archiv/` verschoben

## Features aus v1.4.0

### 🔄 AJAX Collections-Navigation
- **Nahtlose Navigation:** Wechsel zwischen Collections ohne Seitenreload
- **Loading-Animationen:** Visuelles Feedback während des Ladens
- **Browser-History-Support:** URL-Updates und Back/Forward-Button-Kompatibilität
- **Vollständige Reinitialisierung:** Quick Order Tabelle wird komplett neu geladen

### 📱 Enhanced EAN-Scanner
- **Toggle-Button:** Scanner-Aktivierung/Deaktivierung per Klick
- **Live-Feedback:** Echtzeit-Status-Anzeige und visuelles Feedback
- **Automatische Produkterkennung:** Sofortige EAN-Validierung und Produktsuche
- **Nahtlose Integration:** Direkte Verbindung mit Sampling-Liste

### 💰 Live-Preisberechnung
- **Echtzeit-Updates:** EK/VK-Totals aktualisieren sich automatisch bei Warenkorb-Änderungen
- **WooCommerce-Event-Monitoring:** Integration mit allen WooCommerce-Cart-Events
- **Fragment-Updates:** Automatische Synchronisation ohne Seitenreload
- **Erweiterte Kompatibilität:** Verbesserte Event-Überwachung für alle Themes

### 🎯 Enhanced User Experience
- **Verbesserte Benutzerführung:** Intuitive Bedienung mit visuellen Feedback-Systemen
- **Mobile-Optimierung:** Touch-freundliche Bedienung auf allen Geräten
- **Responsive Layouts:** Optimierte Darstellung für Desktop, Tablet und Mobile
- **Performance-Optimierung:** Effiziente Update-Mechanismen und Event-Handler

## Features aus v1.3.1

### Abmusterungs-System

Das neue Sampling-System ermöglicht EAN-basierte Abmusterung mit folgenden Features:

#### Shortcode: `[mc_sampling]`

```php
// Basis-Verwendung (automatische Collection-Erkennung)
[mc_sampling]

// Mit spezifischer Collection für Vorführungen
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

#### Quick Order Tabelle Integration

- **Vollständige Tabelle:** Zeigt die komplette Quick Order Tabelle statt einfacher Liste
- **Automatische Collection-Erkennung:** Findet aktuelle Sampling-Collection automatisch
- **Duale Eingabemethoden:** EAN-Scanner UND Quick Order Tabelle parallel nutzbar
- **Intelligente Fehlerbehandlung:** Benutzerfreundliche Meldungen bei Collection-Problemen

#### Workflow

1. **EAN scannen** → Variante finden → Parent-Produkt ermitteln → SKU als Artikel-ID
2. **Quick Order Tabelle** → Produkt auswählen → Automatisch als Abmusterungs-Item
3. **Custom Cart Item** erstellen mit Metadaten:
   - `is_sampling: true`
   - `artikel_id: Parent-SKU`
   - `scanned_ean: Original-EAN` (nur bei Scanner)
   - `sampling_size: "Abmusterung"`
4. **Preis = 0** für alle Abmusterungs-Items
5. **Unterschrift** im Checkout erforderlich

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

## Anleitung für Maison Common Mitarbeiterinnen

### Order-System (Normale Bestellungen)

1. **Seite aufrufen** mit `[mc_quick_order]` Shortcode
2. **Kunde auswählen** über Sidebar (falls User Switching aktiv)
3. **Collection wählen** über Sidebar-Navigation
4. **Produkte bestellen** über Quick Order Tabelle
5. **Warenkorb prüfen** - EK/VK-Summen werden automatisch angezeigt
6. **Checkout** wie gewohnt

### Sampling-System (Abmusterung)

1. **Seite aufrufen** mit `[mc_sampling]` Shortcode
2. **Collection wird automatisch geladen** (neueste Sampling-Collection)
3. **Zwei Eingabemöglichkeiten:**
   
   **Option A: EAN-Scanner**
   - EAN-Code scannen oder manuell eingeben
   - "Hinzufügen" klicken
   - Produkt wird automatisch mit 0€ in Warenkorb gelegt
   
   **Option B: Quick Order Tabelle**
   - Produkt in der Tabelle suchen
   - "In Warenkorb" klicken
   - Produkt wird automatisch als Abmusterung (0€) behandelt

4. **Weitere Produkte** nach Bedarf hinzufügen
5. **Checkout** - Unterschrift ist erforderlich
6. **Bestellung abschließen**

### Fehlerbehebung

**"Keine Abmusterungs-Collection gefunden"**
1. Gehen Sie zu **Produkte → Collections**
2. Wählen Sie eine Collection aus
3. Klicken Sie auf **Bearbeiten**
4. Aktivieren Sie **"Abmusterungs-Collection"**
5. Klicken Sie **Speichern**

**"Mehrere Abmusterungs-Collections gefunden"**
- Das System verwendet automatisch die neueste Collection
- Entfernen Sie die Markierung bei älteren Collections für saubere Konfiguration

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

### Version 1.5.0 (geplant)

- **Erweiterte Analytics:** Tracking von Scanner-Nutzung und Collection-Navigation
- **Performance-Optimierung:** Caching für häufig geladene Collections
- **Mobile-App-Integration:** PWA-Features für bessere Mobile-Experience
- **Erweiterte Barcode-Unterstützung:** QR-Codes und andere Barcode-Formate

### Version 1.6.0 (geplant)

- **Multi-Scanner-Support** für mehrere Geräte
- **Offline-Modus** für iPad-Nutzung
- **Erweiterte Reporting** für Abmusterungen
- **API-Integration** für externe Systeme
