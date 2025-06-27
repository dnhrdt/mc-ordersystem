# Technischer Kontext
Version: 1.01
Timestamp: 2025-06-26 13:52 CET

## Kerntechnologien
- WordPress
- WooCommerce
- Astra Theme
- WooCommerce Quick Order Plugin
- jQuery (für Frontend-Interaktionen und AJAX)
- DataTables.js (für die Quick Order Tabelle)

## Frontend-Technologien
- **jQuery:** Umfangreiche Nutzung für DOM-Manipulation, Event-Handling und AJAX-Aufrufe.
- **DataTables.js:** Wird vom WooCommerce Quick Order Plugin verwendet, um die Produkttabelle zu rendern und zu verwalten. Das Suchfeld (`.dataTables_filter`) und die Einträge-Anzeige (`.dataTables_length`) sind Teil dieser Bibliothek.
- **AJAX:** Asynchrone Kommunikation mit dem Backend für das Laden von Collections, das Abrufen von EAN-Informationen und die Aktualisierung von Warenkorb-Summen.

## Backend-Technologien
- **PHP:** Serverseitige Logik für WordPress-Plugins, Shortcodes und AJAX-Handler.
- **WordPress Hooks & Filters:** Umfangreiche Nutzung zur Integration in WooCommerce und zur Modifikation des Standardverhaltens.
- **WooCommerce API:** Interaktion mit WooCommerce-Produkten, Varianten und Warenkorb.

## AJAX-Implementierung
- **`mc-collections-ajax.js`:** Haupt-JavaScript-Datei für Frontend-AJAX-Logik.
- **`admin-ajax.php`:** Standard-WordPress-Endpunkt für AJAX-Anfragen.
- **Nonces:** Verwendung von Nonces (`mc_collections_nonce`) zur Sicherung von AJAX-Anfragen.
- **AJAX-Handler in PHP:** Funktionen wie `ajax_load_collection`, `ajax_get_parent_id_for_ean`, `ajax_get_cart_totals` in `includes/class-mc-order-system.php` verarbeiten die Frontend-Anfragen.

## CSS-Layout-Strategien
- **Flexbox:** Aktuelle Strategie für das Layout der DataTables-Steuerelemente und des EAN-Scanners. `display: flex` auf dem `.dataTables_wrapper` ermöglicht eine flexible Anordnung von `dataTables_length`, `dataTables_filter` und `mc-ean-scanner-container`.
- **Responsive Design:** Media Queries werden verwendet, um das Layout auf kleineren Bildschirmen anzupassen (z.B. Elemente untereinander statt nebeneinander).
- **BEM-Notation:** (Angedeutet in `systemPatterns.md`) für konsistente CSS-Klassen.

## Timing-Probleme und Lösungen
- **Häufiges Problem:** Timing-Race-Conditions zwischen DOM-Updates (insbesondere durch DataTables/WooCommerce Quick Order Plugin) und der Initialisierung/Platzierung von JavaScript-Komponenten.
- **Lösungsansatz ("TIMING-FIX PATTERN"):**
    - **Verzögerte Initialisierung:** Verwendung von `setTimeout` und rekursiven Prüffunktionen (`checkAndInitializeEanScanner()`) in JavaScript, um sicherzustellen, dass Elemente im DOM vorhanden sind, bevor sie manipuliert werden.
    - **Plugin-State-Reset:** Entfernen von Plugin-Initialisierungs-Markierungen (`$("body").removeData('plugin_quickOrder')`) vor der Re-Initialisierung von Plugins, um eine saubere Neu-Initialisierung zu erzwingen.
    - **Entfernung von statischem HTML:** Identifizierung und Entfernung von HTML-Code, der zu früh oder doppelt in PHP-Templates gerendert wird, um Konflikte mit dynamisch erzeugten Elementen zu vermeiden.

## Technische Einschränkungen
- Astra Theme steuert die Sidebar.
- WooCommerce rendert die Produktkacheln.
- Quick Order Plugin generiert die Tabelle über Shortcode.
- Abhängigkeit von der Initialisierungsreihenfolge von DataTables und dem WooCommerce Quick Order Plugin.
