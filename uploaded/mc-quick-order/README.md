# Maison Common Quick Order Plugin

Ein WordPress-Plugin für AJAX-basierte Collections-Navigation mit Quick Order Tabellen für das B2B Order-System von Maison Common.

## Features

- **Shortcode-basiert:** Flexibel auf jeder Seite einsetzbar mit `[mc_quick_order]`
- **Auto-Filter:** Lädt automatisch die neueste Collection (höchste ID)
- **AJAX-Navigation:** Nahtloser Wechsel zwischen Collections ohne Seitenreload
- **Sidebar-Integration:** Login-Widget und Collections-Navigation in der Sidebar
- **Performance-optimiert:** Keine unnötigen Produktladungen
- **Responsive Design:** Funktioniert auf allen Geräten
- **Browser History Support:** Back/Forward Buttons funktionieren
- **User Switching Ready:** Vorbereitet für "Shop as Client" Plugin-Integration

## Installation

### 1. Plugin hochladen
```bash
# Plugin-Verzeichnis nach /wp-content/plugins/ kopieren
cp -r maison-common-quick-order /wp-content/plugins/
```

### 2. Plugin aktivieren
- WordPress Admin → Plugins → "Maison Common Quick Order" aktivieren

### 3. Seite erstellen
1. Neue Seite erstellen (z.B. "Quick Order" oder "B2B Bestellungen")
2. Shortcode einfügen: `[mc_quick_order]`
3. Seite veröffentlichen

### 4. Sidebar-Layout
Das Plugin erzwingt automatisch ein Sidebar-Layout für Seiten mit dem Shortcode.

## Verwendung

### Shortcode
```
[mc_quick_order]
```

Dieser Shortcode:
- Lädt automatisch die neueste Collection
- Zeigt die Quick Order Tabelle an
- Aktiviert die Sidebar mit Login- und Collections-Widgets
- Ermöglicht AJAX-Navigation zwischen Collections

### Voraussetzungen
- **Collections-Taxonomie:** `collection` muss mit PODS angelegt sein
- **WooCommerce Quick Order Plugin:** Mit Collections-Support erforderlich
- **Astra Theme:** Für optimale Sidebar-Integration (empfohlen)

## Technische Details

### Auto-Filter Logik
```php
// Ermittelt automatisch die neueste Collection (höchste ID)
$collections = get_terms(array(
    'taxonomy' => 'collection',
    'orderby' => 'term_id',
    'order' => 'DESC',
    'number' => 1
));
```

### AJAX-Endpoint
- **Endpoint:** `mc_load_collection`
- **Security:** WordPress Nonces
- **Parameter:** `collection_id`, `nonce`

### Shortcode-Integration
- **Erkennung:** `has_shortcode($post->post_content, 'mc_quick_order')`
- **Assets:** Automatisches Laden von CSS/JS nur bei Bedarf
- **Sidebar:** Automatische Widget-Integration

## Sidebar-Widgets

### 1. Login/User Switching Widget
- **Position:** Oben in der Sidebar
- **Zweck:** Benutzer wechseln für B2B-Bestellungen
- **Integration:** Bereit für "Shop as Client for WooCommerce" Plugin

### 2. Collections-Navigation Widget
- **Position:** Unter dem Login-Widget
- **Sortierung:** Nach `menu_order` (ASC)
- **AJAX:** Nahtloser Wechsel zwischen Collections

## User Switching Integration

Das Plugin ist vorbereitet für die Integration mit User Switching Plugins:

### Empfohlenes Plugin
**"Shop as Client for WooCommerce"**
- Ermöglicht Bestellungen im Namen von Kunden
- "Request payment by email" Funktion
- User Switching Integration
- Automatisches Ausfüllen von Kundendaten

### Workflow
1. Maison Cormont Mitarbeiter meldet sich an
2. Wechselt zum Kundenprofil über Sidebar-Widget
3. Bestellt als dieser Kunde die neueste Kollektion
4. Kunde erhält Zahlungslink per E-Mail

## Dateien

```
maison-common-quick-order/
├── maison-common-quick-order.php    # Haupt-Plugin-Datei
├── assets/
│   ├── js/
│   │   └── mc-collections-ajax.js   # AJAX-Funktionalität
│   └── css/
│       └── mc-collections-style.css # Styling
└── README.md                        # Diese Datei
```

## Konfiguration

### Collections-Setup
1. PODS-Taxonomie `collection` muss existieren
2. Collections mit `menu_order` für Sortierung
3. Produkte müssen Collections zugeordnet sein

### Quick Order Plugin
Das WooCommerce Quick Order Plugin muss Collections unterstützen:
```
[woocommerce_quick_order_table taxonomy="collection" categories="ID" order="DESC" orderby="menu_order" only_on_stock="no"]
```

## Troubleshooting

### Plugin funktioniert nicht
- Prüfen: Collections-Taxonomie `collection` existiert?
- Prüfen: WooCommerce Quick Order Plugin aktiviert?
- Prüfen: Shortcode `[mc_quick_order]` korrekt eingefügt?

### Sidebar erscheint nicht
- Prüfen: Astra Theme verwendet?
- Prüfen: Seite hat Sidebar-Layout?
- Plugin erzwingt automatisch `right-sidebar` Layout

### AJAX funktioniert nicht
- Browser-Konsole auf JavaScript-Fehler prüfen
- Prüfen: jQuery geladen?
- Prüfen: AJAX-URL korrekt?

### Keine Collections sichtbar
- Prüfen: Collections-Taxonomie `collection` mit PODS angelegt?
- Prüfen: Collections haben `menu_order` Werte?
- Prüfen: Collections sind nicht leer (`hide_empty` => false)?

## Performance

### Optimierungen
- **Lazy Loading:** Assets nur bei Bedarf laden
- **Auto-Filter:** Nur neueste Collection initial laden
- **AJAX:** Keine Seitenreloads bei Collection-Wechsel
- **Caching:** WordPress-Standard Caching kompatibel

### Empfehlungen
- Caching-Plugin verwenden
- CDN für Assets
- Bildoptimierung für Produktbilder

## Support

Bei Problemen:
1. WordPress Debug-Log prüfen
2. Browser-Konsole auf Fehler prüfen
3. Plugin-Kompatibilität testen
4. Collections-Setup verifizieren

## Changelog

### Version 1.0.0
- Shortcode-basierte Implementierung
- Auto-Filter für neueste Collection
- Sidebar-Integration mit Login- und Collections-Widgets
- AJAX-Navigation zwischen Collections
- User Switching Plugin Vorbereitung
- Performance-Optimierungen
