# Fahrzeuge-Block (Server-Render)

## Konfiguration
- API-URL: Wird aus `lib/config.php` übernommen (`$API_URL = $cfg['BASE_URL']`).
- Header: `auth_headers()` nutzt Benutzername/Passwort aus `lib/config.php` und sendet `Authorization: Basic ...`, `Accept` sowie `User-Agent`.
- Cache-Dauer: `$CACHE_TTL = 900` Sekunden (15 Minuten).

## Cache-Verhalten
- Cache-Datei: `cache/fahrzeuge.json` (JSON mit `ts` und `data`).
- Beim Seitenaufruf wird zuerst der Cache geprüft. Solange `time() - ts < CACHE_TTL`, werden die Daten direkt übernommen.
- Ist der Cache veraltet oder fehlt er, wird `get_inventory_cached(true)` aus `lib/utils.php` aufgerufen (frischer Abruf inklusive bestehender Mobile.de-Authentifizierung).
- Bei Abruf-Fehlern wird – falls vorhanden – der bestehende Cache genutzt; andernfalls erscheint eine Hinweisbox für Besucher.
- Cache manuell invalidieren: Datei `cache/fahrzeuge.json` löschen.

## Feld-Mapping
Das Partial erwartet pro Fahrzeug ein Array mit folgenden Feldern:
- `title` → `.title` Überschrift im Card-Header.
- `priceLabel` → `.pricePill` (z. B. "€ 15.900").
- `specs` → `.meta` (Aufzählung Kilometer, Kraftstoff, etc.).
- `year` → optionale Zeile `Baujahr ...`.
- `km` → wird für JSON-LD (`mileageFromOdometer`) genutzt.
- `fuel` → Anzeige in `specs` und JSON-LD (`fuelType`).
- `url` → Button "Details" (`target="_blank" rel="noopener"`).
- `img` → `<img>` Quelle (fällt auf `assets/placeholder/car.jpg` zurück, falls leer).
- `adId` → geht als `sku` in die JSON-LD-Ausgabe.

## Markup-Anpassungen
- Das Karten-Grid befindet sich in `partials/fahrzeuge.inc.php` unter `<div id="vehiclesGrid">`.
- Klassen, Struktur und IDs sollten beibehalten werden, damit `assets/js/main.js` (Filter & Reveal-Animation) unverändert funktioniert.
- Zusätzliche Inhalte können innerhalb des Cards (`.body`) ergänzt werden; immer Werte mit `htmlspecialchars` ausgeben.
- JSON-LD wird am Ende des Partials erzeugt. Bei Layout-Anpassungen keine Script-Tags entfernen, damit strukturierte Daten erhalten bleiben.

## Testing
- Ohne JavaScript (DevTools → Scripts deaktivieren) muss der Abschnitt vollständig sichtbar bleiben.
- `api/vehicles.php?force=1` kann genutzt werden, um neue Daten zu erzwingen, wenn der Cache bereits befüllt ist.
