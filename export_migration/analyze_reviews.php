<?php
/**
 * Analyze all review sources in the database
 */

$pdo = new PDO('mysql:host=localhost;dbname=wpappsfomo_db;charset=utf8mb4', 'root', '');

echo "=== PRODUCT COUNT ===\n";
$result = $pdo->query('SELECT COUNT(*) FROM wp_posts WHERE post_type = "product" AND post_status = "publish"');
echo "Published products: " . $result->fetchColumn() . "\n";

$result = $pdo->query('SELECT COUNT(*) FROM wp_posts WHERE post_type = "product"');
echo "Total products (all status): " . $result->fetchColumn() . "\n";

echo "\n=== REHUB REVIEW META IN POSTMETA ===\n";
$metas = [
    'rehub_review_overall_score',
    'post_user_raitings',
    'post_user_average',
    'rehub_review_editor_score',
    '_wc_average_rating',
    '_wc_review_count',
    'review_post',
    'user_reviews_count'
];

foreach ($metas as $meta) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = ?');
    $stmt->execute([$meta]);
    $count = $stmt->fetchColumn();
    echo "$meta: $count entries\n";
}

echo "\n=== WOOCOMMERCE RATING DATA ===\n";
$result = $pdo->query('SELECT COUNT(*) FROM wp_postmeta WHERE meta_key = "_wc_review_count" AND CAST(meta_value AS UNSIGNED) > 0');
echo "Products with WC review count > 0: " . $result->fetchColumn() . "\n";

$result = $pdo->query('SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM wp_postmeta WHERE meta_key = "_wc_review_count"');
echo "Total WC review count sum: " . $result->fetchColumn() . "\n";

echo "\n=== JET REVIEWS DETAIL ===\n";
$result = $pdo->query('SELECT COUNT(*) FROM wp_jet_reviews');
echo "Jet Reviews count: " . $result->fetchColumn() . "\n";

$result = $pdo->query('SELECT * FROM wp_jet_reviews LIMIT 3');
$rows = $result->fetchAll(PDO::FETCH_ASSOC);
if (!empty($rows)) {
    foreach ($rows as $row) {
        print_r($row);
    }
}

echo "\n=== COMMENTS BY POST TYPE ===\n";
$result = $pdo->query('
    SELECT p.post_type, COUNT(*) as cnt
    FROM wp_comments c
    INNER JOIN wp_posts p ON c.comment_post_ID = p.ID
    GROUP BY p.post_type
    ORDER BY cnt DESC
');
foreach ($result as $row) {
    echo "  - {$row['post_type']}: {$row['cnt']}\n";
}

echo "\n=== CHECK FOR REVIEW POST TYPE ===\n";
$result = $pdo->query('SELECT COUNT(*) FROM wp_posts WHERE post_type = "review"');
echo "Posts with type 'review': " . $result->fetchColumn() . "\n";

$result = $pdo->query('SELECT COUNT(*) FROM wp_posts WHERE post_type LIKE "%review%"');
echo "Posts with type containing 'review': " . $result->fetchColumn() . "\n";

echo "\n=== ALL POST TYPES ===\n";
$result = $pdo->query('SELECT post_type, COUNT(*) as cnt FROM wp_posts GROUP BY post_type ORDER BY cnt DESC');
foreach ($result as $row) {
    echo "  - {$row['post_type']}: {$row['cnt']}\n";
}

echo "\n=== CHECK COMMENTMETA FOR REVIEW DATA ===\n";
$result = $pdo->query('SELECT meta_key, COUNT(*) as cnt FROM wp_commentmeta GROUP BY meta_key ORDER BY cnt DESC LIMIT 20');
echo "Top comment meta keys:\n";
foreach ($result as $row) {
    echo "  - {$row['meta_key']}: {$row['cnt']}\n";
}
