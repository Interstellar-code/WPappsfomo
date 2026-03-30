<?php
/**
 * Export WordPress Blog Articles to CSV
 * Format matching hall_of_shame_export.csv
 */

// Database configuration
$db_host = 'localhost';
$db_name = 'wpappsfomo_db';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get all blog posts (publish, draft, pending)
$sql = "
SELECT
    p.ID,
    p.post_title,
    p.post_name,
    p.post_date,
    p.post_status,
    p.post_content,
    p.post_excerpt,
    p.guid,
    u.display_name as author
FROM wp_posts p
LEFT JOIN wp_users u ON p.post_author = u.ID
WHERE p.post_type = 'blog'
AND p.post_status IN ('publish', 'draft', 'pending')
ORDER BY p.post_status DESC, p.post_date DESC
";

$posts = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($posts) . " articles to export\n";

// Function to get post meta
function getPostMeta($pdo, $postId) {
    $sql = "SELECT meta_key, meta_value FROM wp_postmeta WHERE post_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$postId]);
    $meta = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $meta[$row['meta_key']] = $row['meta_value'];
    }
    return $meta;
}

// Function to get featured image URL
function getFeaturedImage($pdo, $postId) {
    $sql = "
    SELECT pm2.meta_value as url
    FROM wp_postmeta pm
    JOIN wp_postmeta pm2 ON pm.meta_value = pm2.post_id AND pm2.meta_key = '_wp_attached_file'
    WHERE pm.post_id = ? AND pm.meta_key = '_thumbnail_id'
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$postId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        return 'https://wpappsfomo.test/wp-content/uploads/' . $result['url'];
    }
    return '';
}

// Function to extract images from content
function extractContentImages($content) {
    $images = [];
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $matches);
    if (!empty($matches[1])) {
        $images = array_unique($matches[1]);
    }
    return implode(' | ', $images);
}

// Define CSV headers (matching hall_of_shame format)
$headers = [
    'ID',
    'title',
    'slug',
    'status',
    'date',
    'author',
    'content',
    'excerpt',
    'featured_image',
    'content_images',
    'permalink',
    'post_type',
    // Hall of Shame specific meta (may be empty for regular articles)
    'meta_hos_ans',
    'meta_hos_discuss_url',
    'meta_hos_founder',
    'meta_hos_official_site_url',
    'meta_hos_reported_by',
    'meta_hos_reported_on',
    'meta_hos_sales_page_url',
    'meta_hos_store',
    // Common meta fields
    'meta_images',
    'meta_post_hot_count',
    'meta_re_post_expired',
    'meta_rehub_main_product_price',
    'meta_rehub_min_woo_price',
    'meta_rehub_offer_name',
    'meta_rehub_offer_product_desc',
    'meta_rehub_offer_product_url',
    'meta_rehub_post_fields',
    'meta_rehub_views',
    'meta_rehub_views_day',
    'meta_rehub_views_mon',
    'meta_rehub_views_year',
    // Social share meta
    'meta_ss_image_pinterest',
    'meta_ss_pinterest_description',
    'meta_ss_smt_description',
    'meta_ss_smt_image',
    'meta_ss_smt_title',
    'meta_ss_smt_video',
    'meta_ss_social_share_disable',
    'meta_ss_ss_button_target',
    'meta_ss_ss_custom_tweet',
    'meta_ss_view_count',
    // Other meta
    'meta_wpfepp_copyscape_status',
    'meta_wpfepp_submit_with_form_id',
    'meta_wpwhpro_create_post_temp_status_pabbly-for-beamer'
];

// Open CSV file
$outputFile = __DIR__ . '/articles_export.csv';
$fp = fopen($outputFile, 'w');

// Add BOM for Excel UTF-8 compatibility
fwrite($fp, "\xEF\xBB\xBF");

// Write headers
fputcsv($fp, $headers);

// Process each post
$count = 0;
foreach ($posts as $post) {
    $meta = getPostMeta($pdo, $post['ID']);
    $featuredImage = getFeaturedImage($pdo, $post['ID']);
    $contentImages = extractContentImages($post['post_content']);

    // Build permalink
    $permalink = 'https://wpappsfomo.test/blog/' . $post['post_name'] . '/';

    $row = [
        $post['ID'],
        $post['post_title'],
        $post['post_name'],
        $post['post_status'],
        $post['post_date'],
        $post['author'],
        $post['post_content'],
        $post['post_excerpt'],
        $featuredImage,
        $contentImages,
        $permalink,
        'blog',
        // HOS meta
        $meta['hos_ans'] ?? '',
        $meta['hos_discuss_url'] ?? '',
        $meta['hos_founder'] ?? '',
        $meta['hos_official_site_url'] ?? '',
        $meta['hos_reported_by'] ?? '',
        $meta['hos_reported_on'] ?? '',
        $meta['hos_sales_page_url'] ?? '',
        $meta['hos_store'] ?? '',
        // Common meta
        $meta['_images'] ?? '',
        $meta['post_hot_count'] ?? '',
        $meta['re_post_expired'] ?? '',
        $meta['rehub_main_product_price'] ?? '',
        $meta['_min_woo_price'] ?? '',
        $meta['rehub_offer_name'] ?? '',
        $meta['rehub_offer_product_desc'] ?? '',
        $meta['rehub_offer_product_url'] ?? '',
        $meta['rehub_post_fields'] ?? '',
        $meta['rehub_views'] ?? '',
        $meta['rehub_views_day'] ?? '',
        $meta['rehub_views_mon'] ?? '',
        $meta['rehub_views_year'] ?? '',
        // Social share meta
        $meta['ss_image_pinterest'] ?? '',
        $meta['ss_pinterest_description'] ?? '',
        $meta['ss_smt_description'] ?? '',
        $meta['ss_smt_image'] ?? '',
        $meta['ss_smt_title'] ?? '',
        $meta['ss_smt_video'] ?? '',
        $meta['ss_social_share_disable'] ?? '',
        $meta['ss_ss_button_target'] ?? '',
        $meta['ss_ss_custom_tweet'] ?? '',
        $meta['ss_view_count'] ?? '',
        // Other meta
        $meta['wpfepp_copyscape_status'] ?? '',
        $meta['wpfepp_submit_with_form_id'] ?? '',
        $meta['wpwhpro_create_post_temp_status_pabbly-for-beamer'] ?? ''
    ];

    fputcsv($fp, $row);
    $count++;

    if ($count % 10 === 0) {
        echo "Processed $count articles...\n";
    }
}

fclose($fp);

echo "\n✅ Export complete!\n";
echo "Total articles exported: $count\n";
echo "Output file: $outputFile\n";
