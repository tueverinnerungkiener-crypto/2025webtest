<?php
// === Konfiguration ===
require_once __DIR__ . '/../lib/utils.php';

$cfg = cfg();
$API_URL   = $cfg['BASE_URL'];
$HEADERS   = auth_headers();
$CACHE_FILE = __DIR__ . '/../cache/fahrzeuge.json';
$CACHE_TTL  = 900; // 15 Minuten

$vehicles = [];
$errorBox = null;
$cachePayload = null;

if (is_readable($CACHE_FILE)) {
    $raw = file_get_contents($CACHE_FILE);
    $decoded = json_decode($raw, true);
    if (is_array($decoded) && isset($decoded['ts'], $decoded['data']) && is_array($decoded['data'])) {
        $cachePayload = $decoded;
    }
}

$now = time();
if ($cachePayload && ($now - (int) $cachePayload['ts']) < $CACHE_TTL) {
    $vehicles = $cachePayload['data'];
} else {
    try {
        $result = get_inventory_cached(true);
        $vehicles = isset($result['data']) && is_array($result['data']) ? $result['data'] : [];
        $payload = ['ts' => $now, 'data' => $vehicles];
        $dir = dirname($CACHE_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($CACHE_FILE, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } catch (Throwable $e) {
        if ($cachePayload && isset($cachePayload['data']) && is_array($cachePayload['data'])) {
            $vehicles = $cachePayload['data'];
        } else {
            $errorBox = 'Aktuell stehen leider keine Fahrzeugdaten zur Verfügung. Bitte versuchen Sie es später erneut.';
        }
    }
}

if (!function_exists('fahrzeuge_render_price_value')) {
    function fahrzeuge_render_price_value(?string $label): ?string
    {
        if ($label === null) {
            return null;
        }
        $normalized = preg_replace('/[^0-9,\.]/', '', $label);
        if ($normalized === null || $normalized === '') {
            return null;
        }
        $normalized = str_replace('.', '', $normalized);
        $normalized = str_replace(',', '.', $normalized);
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }
        return number_format((float) $normalized, 2, '.', '');
    }
}

if (!function_exists('fahrzeuge_absolute_url')) {
    function fahrzeuge_absolute_url(string $value): string
    {
        if ($value === '') {
            return $value;
        }
        if (strpos($value, '//') === 0) {
            return 'https:' . $value;
        }
        if ($value[0] === '/') {
            return 'https://www.azkiener.de' . $value;
        }
        return $value;
    }
}

if (!function_exists('fahrzeuge_jsonld')) {
    function fahrzeuge_jsonld(array $vehicle): string
    {
        $name       = $vehicle['title'] ?? 'Fahrzeug';
        $url        = $vehicle['url'] ?? '';
        $image      = $vehicle['img'] ?? '';
        $priceLabel = $vehicle['priceLabel'] ?? null;
        $price      = fahrzeuge_render_price_value($priceLabel);

        if ($image) {
            $image = fahrzeuge_absolute_url($image);
        }
        if ($url) {
            $url = fahrzeuge_absolute_url($url);
        }

        $offers = [
            '@type'         => 'Offer',
            'priceCurrency' => 'EUR',
        ];
        if ($price !== null) {
            $offers['price'] = $price;
        }
        if (!empty($vehicle['url'])) {
            $offers['url'] = $url;
        }
        if (!empty($vehicle['adId'])) {
            $offers['sku'] = (string) $vehicle['adId'];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Product',
            'name'     => $name,
            'url'      => $url,
            'image'    => $image,
            'offers'   => $offers,
        ];

        if (!empty($vehicle['specs'])) {
            $data['description'] = $vehicle['specs'];
        }
        if (!empty($vehicle['fuel'])) {
            $data['fuelType'] = $vehicle['fuel'];
        }
        if (!empty($vehicle['km'])) {
            $data['mileageFromOdometer'] = [
                '@type'    => 'QuantitativeValue',
                'value'    => (int) $vehicle['km'],
                'unitCode' => 'KMT',
            ];
        }
        if (!empty($vehicle['year'])) {
            $data['modelDate'] = (string) $vehicle['year'];
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
?>
<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 reveal" data-reveal="up">
    <h2 id="fahrzeuge-title" class="text-2xl sm:text-3xl font-semibold">Aktuelle Highlights</h2>
    <div class="flex gap-2">
        <button id="resetFilters" class="btn-outline text-sm">Filter zurücksetzen</button>
        <a href="/fahrzeuge.php" class="text-sm text-gray-600 hover:text-[#ed804d] self-center">Alle Fahrzeuge</a>
    </div>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-3 reveal" data-reveal="up" data-reveal-delay="80">
    <input id="searchInput" type="search" class="input" placeholder="Suchen nach Marke/Modell ..." aria-label="Fahrzeugsuche">
    <select id="fuelFilter" class="input" aria-label="Kraftstoff filtern">
        <option value="">Alle Kraftstoffe</option>
        <option>Benzin</option>
        <option>Diesel</option>
        <option>Hybrid</option>
        <option>Elektrisch</option>
    </select>
    <select id="sortSelect" class="input" aria-label="Sortierung auswählen">
        <option value="price-asc">Preis ↑</option>
        <option value="price-desc">Preis ↓</option>
        <option value="km-asc">Kilometer ↑</option>
        <option value="km-desc">Kilometer ↓</option>
        <option value="year-desc">Baujahr ↓</option>
        <option value="year-asc">Baujahr ↑</option>
    </select>
</div>

<div id="vehiclesGrid" class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" data-reveal-group="80">
<?php if ($errorBox): ?>
    <div class="rounded border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800"><?php echo htmlspecialchars($errorBox, ENT_QUOTES, 'UTF-8'); ?></div>
<?php elseif (!$vehicles): ?>
    <div class="text-sm text-gray-600">Aktuell sind keine Fahrzeuge verfügbar.</div>
<?php else: ?>
<?php foreach ($vehicles as $index => $vehicle):
    $title = htmlspecialchars($vehicle['title'] ?? 'Fahrzeug', ENT_QUOTES, 'UTF-8');
    $priceLabel = htmlspecialchars($vehicle['priceLabel'] ?? '', ENT_QUOTES, 'UTF-8');
    $meta = htmlspecialchars($vehicle['specs'] ?? '', ENT_QUOTES, 'UTF-8');
    $url = htmlspecialchars($vehicle['url'] ?? '#', ENT_QUOTES, 'UTF-8');
    $img = $vehicle['img'] ?? '';
    $img = $img !== '' ? $img : 'assets/placeholder/car.jpg';
    $img = htmlspecialchars($img, ENT_QUOTES, 'UTF-8');
    $delay = (int) $index * 80;
?>
    <article class="vehicle-card group reveal" data-reveal="up"<?php if ($delay): ?> data-reveal-delay="<?php echo $delay; ?>"<?php endif; ?>>
        <div class="thumb">
            <img src="<?php echo $img; ?>" loading="lazy" decoding="async" width="800" height="450" alt="<?php echo $title; ?>">
        </div>
        <div class="body">
            <div class="header">
                <h3 class="title"><?php echo $title; ?></h3>
                <span class="pricePill"><?php echo $priceLabel; ?></span>
            </div>
            <p class="meta"><?php echo $meta; ?></p>
            <?php if (!empty($vehicle['year'])): ?>
                <p class="year-line">Baujahr <?php echo htmlspecialchars((string) $vehicle['year'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <div class="footer">
                <span class="text-sm text-gray-500">Scheckheft &middot; Garantie</span>
                <a class="details font-medium transition-colors" href="<?php echo $url; ?>" target="_blank" rel="noopener">Details</a>
            </div>
        </div>
    </article>
<?php endforeach; ?>
<?php endif; ?>
</div>

<div class="mt-8 text-center">
    <button id="loadMoreBtn" class="btn-outline reveal" data-reveal="up" style="display:none;">Mehr laden</button>
</div>
<div class="mt-4 text-center">
    <a href="/fahrzeuge.php" class="btn-outline inline-flex items-center justify-center">Alle Fahrzeuge</a>
</div>

<?php if ($vehicles): ?>
<?php foreach ($vehicles as $vehicle):
    $jsonLd = fahrzeuge_jsonld($vehicle);
    if (!$jsonLd) {
        continue;
    }
?>
    <script type="application/ld+json"><?php echo $jsonLd; ?></script>
<?php endforeach; ?>
<?php endif; ?>
