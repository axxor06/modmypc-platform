<?php
// get_products.php — Returns DB products in componentData JSON format
// Place in: htdocs/modmypc/get_products.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/db.php'; // provides $conn via central config.php

// Category key and icon mapping
function get_cat_key($category) {
    $c = strtolower(trim($category));
    if (strpos($c,'processor')!==false || strpos($c,'cpu')!==false) return 'processor';
    if (strpos($c,'ram')!==false || strpos($c,'memory')!==false)   return 'ram';
    if (strpos($c,'graphics')!==false || strpos($c,'gpu')!==false || strpos($c,'graphic')!==false) return 'graphics';
    if (strpos($c,'cabinet')!==false || strpos($c,'case')!==false) return 'cabinet';
    if (strpos($c,'storage')!==false || strpos($c,'ssd')!==false || strpos($c,'hdd')!==false) return 'storage';
    if (strpos($c,'psu')!==false || strpos($c,'power supply')!==false) return 'psu';
    if (strpos($c,'ups')!==false) return 'ups';
    if (strpos($c,'monitor')!==false) return 'monitor';
    if (strpos($c,'mouse')!==false) return 'mouse';
    if (strpos($c,'keyboard')!==false) return 'keyboard';
    if (strpos($c,'mousepad')!==false || strpos($c,'mouse pad')!==false) return 'mousepad';
    if (strpos($c,'headphone')!==false || strpos($c,'headset')!==false) return 'headphone';
    if (strpos($c,'laptop')!==false) return 'laptop';
    if (strpos($c,'desktop')!==false) return 'desktop';
    if (strpos($c,'printer')!==false) return 'printer';
    if (strpos($c,'service')!==false) return 'service';
    if (strpos($c,'cooler')!==false) return 'cooler';
    if (strpos($c,'os')!==false || strpos($c,'operating')!==false) return 'os';
    // Default: use lowercase category name with spaces replaced
    return strtolower(preg_replace('/[^a-z0-9]/', '_', $c));
}

function get_cat_icon($key) {
    $icons = array(
        'processor' => 'microchip',
        'ram'       => 'memory',
        'graphics'  => 'draw-polygon',
        'cabinet'   => 'server',
        'storage'   => 'hdd',
        'psu'       => 'bolt',
        'ups'       => 'battery-full',
        'monitor'   => 'tv',
        'mouse'     => 'mouse',
        'keyboard'  => 'keyboard',
        'mousepad'  => 'border-none',
        'headphone' => 'headphones',
        'laptop'    => 'laptop',
        'desktop'   => 'desktop',
        'printer'   => 'print',
        'service'   => 'toolbox',
        'cooler'    => 'fan',
        'os'        => 'windows',
    );
    return isset($icons[$key]) ? $icons[$key] : 'box';
}

// Fetch all in-stock products from DB
$result = mysqli_query($conn, "SELECT id, category, name, stock, price, COALESCE(image,'') as image FROM modmypc WHERE stock > 0 ORDER BY category, name ASC");

$data = array();

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $cat_raw  = $row['category'];
        $key      = get_cat_key($cat_raw);
        $name     = $row['name'];
        $price    = intval($row['price']);

        if (!isset($data[$key])) {
            $data[$key] = array(
                'label' => $cat_raw,
                'icon'  => get_cat_icon($key),
                'items' => array()
            );
        }

        // Image: look for a file matching the product name in category folder
        // Default to empty (site will show fallback SVG)
        $img = $row['image'];
        // Use relative path from site root — works with the JS fetch
        $img_url = ($img !== '') ? $img : '';
        $data[$key]['items'][$name] = array(
            'price' => $price,
            'image' => $img_url
        );
    }
}

mysqli_close($conn);

echo json_encode($data);
?>
