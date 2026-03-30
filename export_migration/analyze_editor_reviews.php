<?php
/**
 * Analyze editor reviews structure in ReHub
 */

$pdo = new PDO('mysql:host=localhost;dbname=wpappsfomo_db;charset=utf8mb4', 'root', '');

echo "=== EDITOR REVIEW META KEYS ===\n";

// Find all rehub review related meta keys
$result = $pdo->query('
    SELECT DISTINCT meta_key
    FROM wp_postmeta
    WHERE meta_key LIKE "%rehub%" OR meta_key LIKE "%review%" OR meta_key LIKE "%rating%" OR meta_key LIKE "%criteria%"
    ORDER BY meta_key
');

foreach ($result as $row) {
    echo "  - {$row['meta_key']}\n";
}

echo "\n=== SAMPLE PRODUCT WITH EDITOR REVIEW ===\n";

// Get a product with rehub_review_overall_score
$result = $pdo->query('
    SELECT p.ID, p.post_title, pm.meta_key, pm.meta_value
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = "product"
    AND pm.meta_key IN (
        "rehub_review_overall_score",
        "rehub_review_editor_score",
        "review_post",
        "rehub_main_product_url",
        "rehub_review_criteria_score",
        "rehub_review_criterias",
        "_product_attributes"
    )
    LIMIT 30
');

$products = [];
foreach ($result as $row) {
    $pid = $row['ID'];
    if (!isset($products[$pid])) {
        $products[$pid] = ['title' => $row['post_title'], 'meta' => []];
    }
    $products[$pid]['meta'][$row['meta_key']] = $row['meta_value'];
}

foreach (array_slice($products, 0, 3, true) as $pid => $data) {
    echo "\nProduct ID: $pid - {$data['title']}\n";
    foreach ($data['meta'] as $key => $value) {
        $display = strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value;
        echo "  $key: $display\n";
    }
}

echo "\n=== REVIEW_POST ANALYSIS ===\n";
// The review_post meta links products to review posts
$result = $pdo->query('
    SELECT pm.post_id as product_id, pm.meta_value as review_post_id, p.post_title as product_name
    FROM wp_postmeta pm
    INNER JOIN wp_posts p ON pm.post_id = p.ID
    WHERE pm.meta_key = "review_post"
    AND p.post_type = "product"
    LIMIT 10
');

echo "Products linked to review posts:\n";
foreach ($result as $row) {
    echo "  Product {$row['product_id']} ({$row['product_name']}) -> Review Post {$row['review_post_id']}\n";
}

// Count unique review posts
$result = $pdo->query('SELECT COUNT(DISTINCT meta_value) FROM wp_postmeta WHERE meta_key = "review_post"');
echo "\nUnique review posts linked: " . $result->fetchColumn() . "\n";

echo "\n=== REVIEW POST CONTENT SAMPLE ===\n";
// Get a sample review post
$result = $pdo->query('
    SELECT p.ID, p.post_title, p.post_content, p.post_type
    FROM wp_posts p
    WHERE p.ID IN (SELECT DISTINCT meta_value FROM wp_postmeta WHERE meta_key = "review_post")
    LIMIT 2
');

foreach ($result as $row) {
    echo "\nReview Post ID: {$row['ID']}\n";
    echo "Title: {$row['post_title']}\n";
    echo "Type: {$row['post_type']}\n";
    echo "Content (first 500 chars): " . substr($row['post_content'], 0, 500) . "...\n";
}
