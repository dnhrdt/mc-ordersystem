## [1.5.0-dev] - 2025-06-27

### Hinzugefügt
- **🔧 Header-Bug Fix:** AJAX-Reinitialisierung der Such-/Filter-Header bei Kollektionswechsel
- **📱 EAN-Scanner Quick Order:** Vollständige Integration mit SKU-basierter Filterung
- **🛒 Sampling System Backend:** Funktionales Add-to-Cart mit variation_id Handling
- **🧹 Code-Cleanup:** Produktionsreifer Code ohne Debug-Ausgaben

### Verbessert
- **Collections-Navigation:** Vollständige Plugin-Reinitialisierung durch `removeData('plugin_quickOrder')`
- **EAN-Scanner Integration:** DataTables Custom Search Filter mit SKU-Pattern-Matching
- **Sampling Add-to-Cart:** WooCommerce-konforme Lösung mit erster Variation für Validierung
- **Memory Bank:** Vollständige Dokumentation und Status-Updates

### Technisch
- **SKU-Extraktion:** Regex-Pattern für Parent-SKU aus Varianten-SKU (`/-1-(\d+)-0-\d+$/`)
- **AJAX-Endpoint:** `mc_get_parent_id_for_ean` mit Fallback-Mechanismus
- **Cart Item Data:** Custom Sampling-Daten mit `is_sampling`, `artikel_id`, `sampling_parent_id`
- **Event-Delegation:** MutationObserver + Event-Delegation für dynamische Button-Bindung
- **Documentation:** Cart-Cleanup-Funktionen für v1.5.1 dokumentiert

### Funktional aber optimierungsbedürftig
- **⚠️ Sampling Frontend:** Funktional aber langsam (5-6s Verzögerung beim DOM-Update)
- **⏳ Timing-Problem:** Polling-Mechanismus wartet auf DataTables-Initialisierung
- **🔄 Daniel Barrenkamp Feedback:** Performance-Optimierung ausstehend

### Behoben
- **Header-Verschwinden:** Such-/Filter-Header bleiben nach Collections-Wechsel erhalten
- **EAN-Scanner Timing:** Verzögerte Initialisierung bis DataTables-Filter verfügbar
- **Sampling Backend:** WooCommerce add_to_cart Fehler durch variation_id Handling gelöst
- **Code-Qualität:** Alle Debug-Ausgaben entfernt, produktionsreif

### Nächste Schritte (v1.5.1)
- **Cart-Display-Optimierung:** Intelligente Unterscheidung Order- vs. Sampling-Items
- **Timing-Verbesserung:** DataTables createdRow Callback statt Polling-Mechanismus
- **Mixed-Cart-Detection:** Warnung bei gemischten Warenkorb-Inhalten

## [1.4.0] - 2025-06-25

### Hinzugefügt
- **🔄 AJAX Collections-Navigation:** Nahtlose Navigation zwischen Collections ohne Seitenreload
- **📱 EAN-Scanner Integration:** Erweiterte Scanner-Funktionalität mit Toggle-Modus und Live-Feedback
- **💰 Live-Preisberechnung:** Echtzeit-Updates der EK/VK-Totals bei Warenkorb-Änderungen
- **🎯 Enhanced User Experience:** Verbesserte Benutzerführung mit visuellen Feedback-Systemen

### Verbessert
- **Collections-Navigation:** AJAX-basierte Navigation mit Loading-Animationen und Browser-History-Support
- **EAN-Scanner-Interface:** Toggle-Button für Scanner-Aktivierung, automatische Produkterkennung und Sampling-Liste-Integration
- **Cart Totals:** Live-Updates ohne Seitenreload, erweiterte Event-Überwachung für WooCommerce-Kompatibilität
- **JavaScript-Performance:** Optimierte Event-Handler und verbesserte Fehlerbehandlung

### Technisch
- **AJAX-Endpoints:** `mc_load_collection`, `mc_search_product_by_ean`, `mc_get_cart_totals`
- **Enhanced Scanner:** Keyboard-Event-Handling, automatische EAN-Validierung, Produktsuche-Integration
- **Live-Updates:** WooCommerce-Event-Monitoring, Fragment-Updates, automatische Totals-Aktualisierung
- **Browser-Integration:** History API Support, URL-Updates, Back/Forward-Button-Kompatibilität
- **Responsive Design:** Mobile-optimierte Scanner-Bedienung, Touch-freundliche Navigation

### Behoben
- **Collections-Reload:** Vollständige Reinitialisierung der Quick Order Tabelle nach AJAX-Navigation
- **Scanner-Konflikte:** Saubere Event-Trennung zwischen Scanner-Modi und normaler Eingabe
- **Cart-Synchronisation:** Zuverlässige Live-Updates der Preissummen bei allen Warenkorb-Änderungen
- **Mobile-Kompatibilität:** Verbesserte Touch-Bedienung und responsive Layouts

## [1.3.1] - 2025-06-17

### Verbessert
- **Quick Order Tabelle Integration:** Sampling-System zeigt jetzt die vollständige Quick Order Tabelle statt einfacher Produktliste
- **Automatische Collection-Erkennung:** Findet automatisch die aktuelle Sampling-Collection (neueste mit `_is_sampling_collection = true`)
- **Duale Eingabemethoden:** EAN-Scanner UND Quick Order Tabelle parallel nutzbar - beide führen zu Abmusterungs-Items (0€)
- **Intelligente Fehlerbehandlung:** Benutzerfreundliche Meldungen bei fehlenden oder mehreren Sampling-Collections
- **Shortcode-Flexibilität:** `collection_id` Parameter für Vorführungen, `show_table="false"` für nur Scanner

### Technisch
- **Automatische Sampling-Erkennung:** Produkte in Sampling-Collections werden automatisch als Abmusterungs-Items behandelt
- **Quick Order Integration:** Einbettung des `[woocommerce_quick_order_table]` Shortcodes mit Collection-Filter
- **Erweiterte Cart Item Detection:** `is_product_in_sampling_collection()` prüft Collection-Zugehörigkeit
- **Verbesserte Shortcode-Parameter:** `show_table` ersetzt `show_collections` für bessere Kontrolle
- **Debug-Logging:** Erweiterte Protokollierung für Sampling-Context-Erkennung

### Behoben
- **Fehlende Quick Order Tabelle:** Sampling-System zeigt jetzt vollständige Tabelle statt nur Liste
- **Collections-Navigation entfernt:** Automatische Erkennung macht manuelle Auswahl überflüssig
- **Workflow-Optimierung:** EAN-Scanner + Quick Order Tabelle für optimalen Abmusterungs-Workflow

## [1.3.0] - 2025-06-17

### Hinzugefügt
- **Abmusterungs-System:** Neuer Shortcode `[mc_sampling]` für EAN-basierte Abmusterung
- **EAN-Scanner-Interface:** HID-Device-optimierte EAN-Eingabe mit Auto-Submit-Funktionalität
- **Custom Cart Items:** Spezielle Warenkorb-Items für Abmusterungen ohne Größenangabe
- **Artikel-ID-Integration:** Verwendet Parent-Produkt SKU als Artikel-ID für LSE-Export
- **Collection-Filter:** Custom Field `_is_sampling_collection` für Abmusterungs-Collections
- **Unterschriften-Integration:** Checkbox-basiertes Unterschriftenfeld im Checkout
- **Modulare Plugin-Architektur:** Aufgeteilt in Order-System und Sampling-System

### Technisch
- **Drei-Datei-Struktur:** Basis-Plugin, Order-System-Klasse, Sampling-System-Klasse
- **EAN-zu-Parent-Lookup:** Automatische Ermittlung des Parent-Produkts über EAN-Scan
- **Custom Cart Item Data:** Spezielle Metadaten für Abmusterungs-Items (Artikel-ID, EAN, etc.)
- **AJAX-Endpoints:** `mc_scan_ean`, `mc_load_sampling_collection` für Sampling-Funktionalität
- **Collection Meta Fields:** Backend-Integration für Sampling-Collection-Markierung
- **Nullpreis-Handling:** Automatische Preissetzung auf 0 für Abmusterungs-Items
- **Order Meta Integration:** Speicherung von Artikel-ID und Unterschrift in Bestellungen

### Behoben
- **Modulare Struktur:** Bessere Wartbarkeit durch Aufteilung in separate Klassen
- **Code-Organisation:** Klare Trennung zwischen Order- und Sampling-Funktionalität
- **Asset-Loading:** Separate JavaScript- und CSS-Dateien für Sampling-System

## [1.2.0] - 2025-06-17

### Hinzugefügt
- **Quick Order Plugin Integration:** `_ek_price` Custom Field automatisch im Quick Order Plugin verfügbar
- **WooCommerce-konforme Formatierung:** EK-Preise werden identisch zu VK-Preisen formatiert
- **Dynamische Währungsformatierung:** Berücksichtigt alle WooCommerce-Währungseinstellungen
- **Automatische Transient-Verwaltung:** `_ek_price` wird automatisch zur Quick Order Meta-Keys-Liste hinzugefügt

### Technisch
- **JavaScript-basierte Formatierung:** Client-seitige Preisformatierung nach WooCommerce-Standards
- **Währungsposition-Support:** Unterstützt alle WooCommerce-Währungspositionen (left, right, left_space, right_space)
- **Dezimal- und Tausendertrennzeichen:** Dynamische Übernahme der WooCommerce-Einstellungen
- **WooCommerce CSS-Integration:** Verwendet `woocommerce-Price-amount amount` Klassen für konsistentes Styling
- **Transient Cache Manipulation:** Automatische Registrierung von `_ek_price` im Quick Order Plugin Cache

### Behoben
- **jQuery-Kompatibilität:** Live-Update Funktionalität funktioniert jetzt korrekt ohne JavaScript-Fehler
- **EK-Preis Sichtbarkeit:** `_ek_price` erscheint automatisch in Quick Order Plugin Konfiguration
- **Währungsformatierung:** EK-Preise werden jetzt mit korrektem Währungszeichen und Nachkommastellen angezeigt

## [1.1.0] - 2025-06-17

### Hinzugefügt
- **Cart Totals Feature:** Shortcode `[mc_cart_totals]` für Header-Integration
- **EK/VK-Preise Anzeige:** Zeigt Einkaufs- und Verkaufspreise-Summen an
- **Live-Berechnung:** JavaScript-basierte Warenkorb-Summen-Updates ohne Seitenreload
- **Automatische Platzierung:** Cart Totals werden automatisch unter Quick Order Tabellen angezeigt
- **Custom EK-Preis Field:** Eigenes `_ek_price` Meta-Field für Einkaufspreise
- **WooCommerce Backend Integration:** EK-Preis-Felder in Produkt- und Variations-Bearbeitung
- **AJAX-Endpoint:** `mc_get_cart_totals` für Live-Updates der Summen

### Technisch
- **Custom Fields:** `_ek_price` für Einkaufspreise (Produkte und Variationen)
- **Backend Integration:** WooCommerce Pricing-Optionen erweitert
- Shortcode-Attribute: `show_labels`, `separator`, `class` für flexible Anpassung
- **Erweiterte JavaScript-Überwachung:** Multiple Event-Listener für zuverlässige Live-Updates
- **Fallback-Mechanismen:** AJAX-Success-Monitoring und Button-Click-Überwachung
- WooCommerce Cart Events: `added_to_cart`, `removed_from_cart`, `wc_fragments_refreshed`
- Währungsformatierung über WooCommerce `wc_price()` Funktion
- Fallback-Verhalten: Zeigt "EK: €0,00 | VK: €0,00" bei leerem Warenkorb

### Behoben
- **Sale Price Konflikt:** Verwendet jetzt eigenes `_ek_price` Field statt `_sale_price`
- **Live-Update Zuverlässigkeit:** Erweiterte Event-Überwachung für bessere Kompatibilität
