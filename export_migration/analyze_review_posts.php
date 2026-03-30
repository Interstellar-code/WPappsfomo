<?php
/**
 * Analyze review posts (blog posts that are reviews)
 */

$pdo = new PDO('mysql:host=localhost;dbname=wpappsfomo_db;charset=utf8mb4', 'root', '');

echo "=== POSTS WITH REVIEW CRITERIA ===\n";

// Find posts that have review criteria (these are review posts)
$result = $pdo->query('
    SELECT COUNT(DISTINCT post_id)
    FROM wp_postmeta
    WHERE meta_key = "_review_post_criteria"
');
echo "Posts with _review_post_criteria: " . $result->fetchColumn() . "\n";

$result = $pdo->query('
    SELECT COUNT(DISTINCT post_id)
    FROM wp_postmeta
    WHERE meta_key = "_review_score_criteria_1"
');
echo "Posts with _review_score_criteria_1: " . $result->fetchColumn() . "\n";

echo "\n=== SAMPLE REVIEW POST DATA ===\n";

$reviewMetaKeys = [
    '_review_heading',
    '_review_post_criteria',
    '_review_score_criteria_1',
    '_review_score_criteria_2',
    '_review_score_criteria_3',
    '_review_score_criteria_4',
    '_review_score_criteria_5',
    '_review_post_pros_text',
    '_review_post_cons_text',
    '_review_post_summary_text',
    '_review_post_score_manual',
    'rehub_review_overall_score'
];

// Get posts that have review data
$result = $pdo->query('
    SELECT DISTINCT p.ID, p.post_title, p.post_type
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE pm.meta_key = "_review_post_criteria"
    LIMIT 5
');

$posts = $result->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as $post) {
    echo "\n--- Post ID: {$post['ID']} ---\n";
    echo "Title: {$post['post_title']}\n";
    echo "Type: {$post['post_type']}\n";

    // Get all review meta for this post
    $stmt = $pdo->prepare('
        SELECT meta_key, meta_value
        FROM wp_postmeta
        WHERE post_id = ?
        AND meta_key IN ("' . implode('","', $reviewMetaKeys) . '")
    ');
    $stmt->execute([$post['ID']]);
    $metas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($metas as $meta) {
        $value = $meta['meta_value'];
        // Check if serialized
        if (@unserialize($value) !== false || $value === 'b:0;') {
            $unserialized = @unserialize($value);
            echo "  {$meta['meta_key']}: " . json_encode($unserialized) . "\n";
        } else {
            echo "  {$meta['meta_key']}: $value\n";
        }
    }
}

echo "\n=== PRODUCTS WITH DIRECT REVIEW SCORES ===\n";

// Products may have review scores directly
$result = $pdo->query('
    SELECT p.ID, p.post_title,
           MAX(CASE WHEN pm.meta_key = "rehub_review_overall_score" THEN pm.meta_value END) as overall_score,
           MAX(CASE WHEN pm.meta_key = "rehub_review_editor_score" THEN pm.meta_value END) as editor_score
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = "product"
    AND pm.meta_key IN ("rehub_review_overall_score", "rehub_review_editor_score")
    GROUP BY p.ID, p.post_title
    HAVING overall_score IS NOT NULL OR editor_score IS NOT NULL
    LIMIT 10
');

foreach ($result as $row) {
    echo "Product {$row['ID']}: {$row['post_title']}\n";
    echo "  Overall: {$row['overall_score']}, Editor: {$row['editor_score']}\n";
}

echo "\n=== COUNT PRODUCTS WITH REVIEW DATA ===\n";
$result = $pdo->query('
    SELECT COUNT(DISTINCT p.ID)
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = "product"
    AND pm.meta_key = "rehub_review_overall_score"
');
echo "Products with rehub_review_overall_score: " . $result->fetchColumn() . "\n";

echo "\n=== JET REVIEW DATA ON PRODUCTS ===\n";
$jetReviewKeys = [
    'jet-review-items',
    'jet-review-summary-text',
    'jet-review-title'
];

$result = $pdo->query('
    SELECT COUNT(DISTINCT post_id)
    FROM wp_postmeta
    WHERE meta_key = "jet-review-items"
');
echo "Posts with jet-review-items: " . $result->fetchColumn() . "\n";

// Sample jet review data
$result = $pdo->query('
    SELECT p.ID, p.post_title, pm.meta_key, pm.meta_value
    FROM wp_posts p
    INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
    WHERE p.post_type = "product"
    AND pm.meta_key LIKE "jet-review%"
    LIMIT 20
');

$jetProducts = [];
foreach ($result as $row) {
    $pid = $row['ID'];
    if (!isset($jetProducts[$pid])) {
        $jetProducts[$pid] = ['title' => $row['post_title'], 'meta' => []];
    }
    $jetProducts[$pid]['meta'][$row['meta_key']] = $row['meta_value'];
}

foreach (array_slice($jetProducts, 0, 2, true) as $pid => $data) {
    echo "\nProduct ID: $pid - {$data['title']}\n";
    foreach ($data['meta'] as $key => $value) {
        $unserialized = @unserialize($value);
        if ($unserialized !== false) {
            echo "  $key: " . json_encode($unserialized) . "\n";
        } else {
            $display = strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value;
            echo "  $key: $display\n";
        }
    }
}
