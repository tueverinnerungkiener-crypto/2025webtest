<?php
require __DIR__ . '/../lib/utils.php';

try {
    $force = !empty($_GET['force']) || !empty($_GET['refresh']);
    $cache = get_inventory_cached($force);
    json_response($cache['data']);
} catch (Exception $e) {
    json_response(['error' => $e->getMessage()], 500);
}
