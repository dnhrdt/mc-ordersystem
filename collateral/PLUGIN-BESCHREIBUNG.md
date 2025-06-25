# Maison Common Quick Order Plugin v1.3.1

WordPress-Plugin zur Erweiterung von WooCommerce um spezialisierte Bestellfunktionen und ein Abmusterungssystem.

## Überblick

Das Plugin besteht aus zwei Hauptkomponenten:

### Order-System
Erweitert die Standard-WooCommerce-Funktionalität um:
- Collections-Navigation über Sidebar-Widget
- Integration mit dem WooCommerce Quick Order Plugin
- Live-Berechnung von Einkaufs- und Verkaufspreisen
- Kundenwechsel-Funktionalität (User Switching Integration)

### Sampling-System
Spezielles System für Abmusterungsprozesse:
- EAN-Scanner-Integration für Bluetooth-HID-Geräte
- Automatische Produkterkennung über EAN-Codes
- Erstellung von kostenlosen Abmusterungs-Items (0€)
- Digitale Unterschriftenfunktion im Checkout
- Integration mit Quick Order Tabellen

## Funktionen

### Shortcodes

**`[mc_quick_order]`**
- Zeigt Collections-Navigation und Quick Order Tabelle
- Parameter: `collection_id`, `show_collections`

**`[mc_sampling]`**
- Zeigt EAN-Scanner-Interface und Quick Order Integration
- Parameter: `collection_id`, `show_collections`, `show_table`, `show_scanner`

**`[mc_cart_totals]`**
- Zeigt Warenkorb-Summen mit EK/VK-Preisen
- Parameter: `show_ek`, `show_vk`, `class`

### EAN-Scanner-Funktionalität
- Unterstützt Standard-Bluetooth-HID-Scanner
- Automatische Verarbeitung nach 13 Zeichen oder Enter-Taste
- Fallback für manuelle EAN-Eingabe
- Sucht in Meta-Feldern: `_ean`, `_ean13`, `_barcode`, `_gtin`

### Collections-Management
- Backend-Option zur Markierung von Sampling-Collections
- Automatische Erkennung der aktuellen Sampling-Collection
- AJAX-basierte Navigation zwischen Collections

### Preisintegration
- Zusätzliches EK-Preis-Feld für Produkte und Varianten
- Automatische Integration in Quick Order Plugin
- Live-Berechnung von Einkaufs- und Verkaufssummen

### Abmusterungs-Workflow
1. EAN-Code scannen oder Produkt aus Tabelle wählen
2. System ermittelt Parent-Produkt und erstellt Artikel-ID
3. Item wird mit 0€-Preis als "Abmusterung" in Warenkorb gelegt
4. Checkout erfordert digitale Unterschrift
5. Bestellung enthält Artikel-IDs für Warenwirtschafts-Export

## Technische Details

### Systemanforderungen
- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+

### Abhängigkeiten
- WooCommerce Quick Order Plugin (erforderlich)
- User Switching Plugin (optional)

### Browser-Unterstützung
- Moderne Browser mit JavaScript ES6+
- HID-Device-Unterstützung für Scanner

### Architektur
- Modularer Aufbau mit separaten Klassen für Order- und Sampling-System
- AJAX-basierte Kommunikation
- WordPress-Standard-Hooks und -Filter
- Responsive CSS-Framework

### AJAX-Endpoints
- `mc_load_collection` - Collection-Daten laden
- `mc_search_customers` - Kundensuche
- `mc_get_cart_totals` - Warenkorb-Summen
- `mc_sampling_add_item` - Abmusterungs-Item hinzufügen

### Datenstruktur
- Erweitert WooCommerce-Produkte um EK-Preis-Feld
- Nutzt Collection-Taxonomie für Gruppierung
- Speichert Abmusterungs-Metadaten in Cart-Items und Orders

## Installation

1. Plugin-Dateien in WordPress-Plugin-Verzeichnis kopieren
2. Plugin im Backend aktivieren
3. Collections als "Abmusterungs-Collections" markieren (optional)
4. Shortcodes auf gewünschten Seiten einbinden

## Konfiguration

### EK-Preise einrichten
- Produktebene: Feld "EK-Preis" in Produkt-Einstellungen
- Variantenebene: Individuelle EK-Preise pro Variante

### Sampling-Collections markieren
- Backend: Produkte → Collections → Collection bearbeiten
- Checkbox "Abmusterungs-Collection" aktivieren

### Hardware-Setup (Scanner)
- Bluetooth-HID-Scanner mit Computer koppeln
- Scanner auf EAN-13-Modus konfigurieren
- Automatische Erkennung durch Browser

## Ausgabe-Formate

### LSE-Integration
Abmusterungs-Bestellungen enthalten:
- Artikel-ID (Parent-SKU ohne Größenangabe)
- EAN-Codes der gescannten Varianten
- Unterschrift-Timestamp
- Bestellungs-Metadaten für Export

### Warenkorb-Integration
- Standard-WooCommerce-Cart-Items
- Erweiterte Metadaten für Abmusterungen
- Kompatibilität mit bestehenden Checkout-Prozessen
