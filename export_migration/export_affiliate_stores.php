<?php
/**
 * Export Affiliate Stores to CSV
 *
 * Exports all affiliate stores (dealstore taxonomy terms) with their term meta.
 *
 * Run: php export_affiliate_stores.php
 */

// Load WordPress
require_once dirname(__DIR__) . '/wp-load.php';

// CSV output file
$csv_file = __DIR__ . '/affiliate_stores_export.csv';

echo "=== Affiliate Stores Export Script ===\n\n";

// Get all terms from dealstore taxonomy
$stores = get_terms([
    'taxonomy' => 'dealstore',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC',
]);

if (is_wp_error($stores) || empty($stores)) {
    echo "No affiliate stores found.\n";
    exit(1);
}

echo "Found " . count($stores) . " affiliate stores to export.\n\n";

// Define columns for store export
$columns = [
    'term_id',
    'name',
    'slug',
    'description',
    'parent_id',
    'parent_name',
    'post_count',
    // Term meta fields
    'brand_url',
    'brandimage',
    'brand_short_description',
    'brand_second_description',
    'rehub_user_rate',
    'rehub_users_num',
    // Additional possible meta
    'heading_title',
    'cashback_notice',
];

// Collect all stores data
$stores_data = [];

foreach ($stores as $store) {
    echo "Processing store: {$store->name} (ID: {$store->term_id})\n";

    // Get parent name if exists
    $parent_name = '';
    if ($store->parent > 0) {
        $parent_term = get_term($store->parent, 'dealstore');
        if (!is_wp_error($parent_term)) {
            $parent_name = $parent_term->name;
        }
    }

    // Get all term meta
    $all_meta = get_term_meta($store->term_id);

    // Build store data array
    $store_data = [
        'term_id' => $store->term_id,
        'name' => $store->name,
        'slug' => $store->slug,
        'description' => $store->description,
        'parent_id' => $store->parent,
        'parent_name' => $parent_name,
        'post_count' => $store->count,
        // Term meta fields
        'brand_url' => get_term_meta($store->term_id, 'brand_url', true),
        'brandimage' => get_term_meta($store->term_id, 'brandimage', true),
        'brand_short_description' => get_term_meta($store->term_id, 'brand_short_description', true),
        'brand_second_description' => get_term_meta($store->term_id, 'brand_second_description', true),
        'rehub_user_rate' => get_term_meta($store->term_id, 'rehub_user_rate', true),
        'rehub_users_num' => get_term_meta($store->term_id, 'rehub_users_num', true),
        // Additional possible meta (mapped from UI field names)
        'heading_title' => get_term_meta($store->term_id, 'heading_title', true) ?: get_term_meta($store->term_id, 'set_heading_title', true),
        'cashback_notice' => get_term_meta($store->term_id, 'cashback_notice', true) ?: get_term_meta($store->term_id, 'set_short_notice', true),
    ];

    // Check for any additional meta keys we might have missed
    $additional_meta = [];
    foreach ($all_meta as $key => $value) {
        // Skip internal keys and already captured keys
        if (strpos($key, '_') === 0) continue;
        if (isset($store_data[$key])) continue;
        if (in_array($key, ['brand_url', 'brandimage', 'brand_short_description', 'brand_second_description', 'rehub_user_rate', 'rehub_users_num', 'heading_title', 'cashback_notice', 'set_heading_title', 'set_short_notice', 'rank_math_focus_keyword', 'rank_math_robots'])) continue;

        $val = is_array($value) && count($value) === 1 ? $value[0] : $value;
        if (!empty($val)) {
            $additional_meta[$key] = is_array($val) ? json_encode($val) : $val;
        }
    }

    if (!empty($additional_meta)) {
        $store_data['additional_meta'] = json_encode($additional_meta);
    }

    $stores_data[] = $store_data;
}

// Check if we need to add additional_meta column
$has_additional_meta = false;
foreach ($stores_data as $store) {
    if (!empty($store['additional_meta'])) {
        $has_additional_meta = true;
        break;
    }
}

if ($has_additional_meta) {
    $columns[] = 'additional_meta';
}

// Write CSV
$fp = fopen($csv_file, 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header
fputcsv($fp, $columns);

// Write data
foreach ($stores_data as $store) {
    $row = [];
    foreach ($columns as $col) {
        $value = isset($store[$col]) ? $store[$col] : '';
        // Clean up the value for CSV
        if (is_string($value)) {
            $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        }
        $row[] = $value;
    }
    fputcsv($fp, $row);
}

fclose($fp);

echo "\n=== Export Complete ===\n";
echo "CSV file saved to: {$csv_file}\n";
echo "Total stores exported: " . count($stores_data) . "\n";
echo "\nColumns included (" . count($columns) . " columns):\n";
foreach ($columns as $col) {
    echo "  - {$col}\n";
}

// Print summary of stores
echo "\n=== Store Summary ===\n";
echo str_pad("Name", 25) . str_pad("Slug", 20) . str_pad("Posts", 8) . "URL\n";
echo str_repeat("-", 100) . "\n";
foreach ($stores_data as $store) {
    $url = substr($store['brand_url'], 0, 45);
    echo str_pad($store['name'], 25) . str_pad($store['slug'], 20) . str_pad($store['post_count'], 8) . $url . "\n";
}
