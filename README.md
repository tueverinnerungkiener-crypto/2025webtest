Fahrzeugabschnitt rendert jetzt serverseitig inklusive eigener Fahrzeugseite und SEO-Updates.

Änderungen

index.php:161 – ersetzt die Inhalte von <section id="fahrzeuge"> durch <?php include __DIR__ . '/partials/fahrzeuge.inc.php'; ?>, damit Startseite direkt den servergerenderten Block lädt.
partials/fahrzeuge.inc.php:1 – neues Include mit 15‑Minuten-Dateicache (cache/fahrzeuge.json), Rückfalllogik auf vorhandene Daten, vollständigem Card-Markup (inkl. „Alle Fahrzeuge“-Link) und Sicherheitsausgaben für Titel, Preise & Bilder.
partials/fahrzeuge.inc.php:220 – erzeugt für jedes Angebot JSON-LD (Product/Offer) mit absoluten URLs, Laufleistung und optionalem Baujahr.
fahrzeuge.php:9 – neue Unterseite mit kanonischem Link, eigenem <h1>-Intro und demselben Fahrzeug-Partial, Navigation verweist weiterhin auf die Onepager-Sektionen.
cache/.gitignore:1 – verhindert, dass der Laufzeitcache eingecheckt wird.
sitemap.xml:2 – aufgeräumte URL-Liste ohne Fragmente, ergänzt um index.php und fahrzeuge.php.
robots.txt:1 – legt die Sitemap-Referenz für Crawler fest.
README-fahrzeuge-include.md:1 – dokumentiert Konfiguration, Cache-Handling, Feldzuordnung und Anpassungshinweise.
Tests

php -l index.php
php -l partials/fahrzeuge.inc.php
php -l fahrzeuge.php
Next steps

Auf dem Webserver prüfen, ob DirectoryIndex die neue index.php vor index.html ausliefert (sonst Priorität anpassen oder altes HTML umbenennen).
Einmal im Browser ohne JavaScript laden und optional API-Ausfall simulieren, um Cache-Fallback und Hinweisbox zu verifizieren.
