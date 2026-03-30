<?php
/**
 * Combined Product Reviews Export Script v2
 * Exports WooCommerce products with full post content
 *
 * Content Processing:
 * - HTML converted to Markdown
 * - Links converted to plain text URLs
 * - Images kept as-is
 * - URLs converted from wpappsfomo.test to appsfomo.com
 */

$pdo = new PDO(
    'mysql:host=localhost;dbname=wpappsfomo_db;charset=utf8mb4',
    'root',
    '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$prefix = 'wp_';
$output_file = __DIR__ . '/combined_reviews_export_v2.csv';

echo "Starting combined export v2...\n";

/**
 * Replace local dev domain with production domain
 */
function fixDomain($text) {
    if (empty($text)) return $text;
    return str_replace('wpappsfomo.test', 'appsfomo.com', $text);
}

/**
 * Convert HTML to Markdown
 */
function htmlToMarkdown($html) {
    if (empty($html)) return '';
    $text = $html;

    // Convert headings
    $text = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "# $1\n\n", $text);
    $text = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "## $1\n\n", $text);
    $text = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "### $1\n\n", $text);
    $text = preg_replace('/<h4[^>]*>(.*?)<\/h4>/is', "#### $1\n\n", $text);
    $text = preg_replace('/<h5[^>]*>(.*?)<\/h5>/is', "##### $1\n\n", $text);
    $text = preg_replace('/<h6[^>]*>(.*?)<\/h6>/is', "###### $1\n\n", $text);

    // Convert <br> and <br/> to newlines
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

    // Convert <p> tags to double newlines
    $text = preg_replace('/<p[^>]*>/i', '', $text);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);

    // Convert <strong> and <b> to **bold**
    $text = preg_replace('/<(strong|b)[^>]*>(.*?)<\/(strong|b)>/is', '**$2**', $text);

    // Convert <em> and <i> to *italic*
    $text = preg_replace('/<(em|i)[^>]*>(.*?)<\/(em|i)>/is', '*$2*', $text);

    // Convert images to markdown
    $text = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*alt=["\']([^"\']*)["\'][^>]*>/i', function($matches) {
        $url = fixDomain($matches[1]);
        $alt = $matches[2] ?? '';
        return "![$alt]($url)";
    }, $text);
    $text = preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function($matches) {
        $url = fixDomain($matches[1]);
        return "![]($url)";
    }, $text);

    // Convert links to plain text URL format
    $text = preg_replace_callback('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', function($matches) {
        $url = fixDomain($matches[1]);
        $linkText = strip_tags($matches[2]);
        if (in_array(strtolower(trim($linkText)), ['image', 'img', 'screenshot', 'photo', 'picture', 'link', 'here', 'click here'])) {
            return $url;
        }
        if (trim($linkText) === $url || trim($linkText) === '') {
            return $url;
        }
        return "$linkText ($url)";
    }, $text);

    // Convert lists
    $text = preg_replace('/<ul[^>]*>/i', '', $text);
    $text = preg_replace('/<\/ul>/i', "\n", $text);
    $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $text);
    $text = preg_replace('/<ol[^>]*>/i', '', $text);
    $text = preg_replace('/<\/ol>/i', "\n", $text);

    // Remove div, span, figure, figcaption etc
    $text = preg_replace('/<(div|span|figure|figcaption|section|article|aside|header|footer|nav)[^>]*>/i', '', $text);
    $text = preg_replace('/<\/(div|span|figure|figcaption|section|article|aside|header|footer|nav)>/i', "\n", $text);

    // Strip remaining HTML tags
    $text = strip_tags($text);

    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Clean up escape sequences
    $text = str_replace(['\r\n', '\r', '\n'], "\n", $text);
    $text = str_replace(['\"', "\'"], ['"', "'"], $text);

    // Normalize multiple newlines to max 2
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
}

/**
 * Extract image URLs from HTML content
 */
function extractImageUrls($html) {
    $images = [];
    if (empty($html)) return json_encode($images);

    // Extract from <img> tags
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $imgMatches);
    if (!empty($imgMatches[1])) {
        foreach ($imgMatches[1] as $url) {
            $images[] = fixDomain($url);
        }
    }

    // Extract from <a> tags that link to images
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $linkMatches);
    if (!empty($linkMatches[1])) {
        foreach ($linkMatches[1] as $url) {
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', $url) ||
                preg_match('/d\.pr\/i\//i', $url) ||
                preg_match('/imgur\.com/i', $url) ||
                preg_match('/wp-content\/uploads/i', $url)) {
                $images[] = fixDomain($url);
            }
        }
    }

    return json_encode(array_values(array_unique($images)));
}

/**
 * Get taxonomy terms for a post
 */
function getPostTerms($pdo, $prefix, $postId, $taxonomy) {
    $sql = "SELECT t.name FROM {$prefix}terms t
            INNER JOIN {$prefix}term_taxonomy tt ON t.term_id = tt.term_id
            INNER JOIN {$prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tr.object_id = :post_id AND tt.taxonomy = :taxonomy";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['post_id' => $postId, 'taxonomy' => $taxonomy]);
    return implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Get product attribute value
 */
function getProductAttribute($pdo, $prefix, $postId, $attributeSlug) {
    $taxonomy = 'pa_' . $attributeSlug;
    $sql = "SELECT t.name FROM {$prefix}terms t
            INNER JOIN {$prefix}term_taxonomy tt ON t.term_id = tt.term_id
            INNER JOIN {$prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tr.object_id = :post_id AND tt.taxonomy = :taxonomy";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['post_id' => $postId, 'taxonomy' => $taxonomy]);
    return implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/**
 * Get post meta value
 */
function getPostMeta($pdo, $prefix, $postId, $metaKey) {
    $sql = "SELECT meta_value FROM {$prefix}postmeta WHERE post_id = :post_id AND meta_key = :meta_key LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['post_id' => $postId, 'meta_key' => $metaKey]);
    return $stmt->fetchColumn() ?: '';
}

/**
 * Get featured image URL
 */
function getFeaturedImageUrl($pdo, $prefix, $postId) {
    $thumbnailId = getPostMeta($pdo, $prefix, $postId, '_thumbnail_id');
    if (empty($thumbnailId)) return '';
    $sql = "SELECT guid FROM {$prefix}posts WHERE ID = :id AND post_type = 'attachment'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $thumbnailId]);
    $url = $stmt->fetchColumn() ?: '';
    return fixDomain($url);
}

/**
 * Get gallery image URLs
 */
function getGalleryUrls($pdo, $prefix, $postId) {
    $galleryIds = getPostMeta($pdo, $prefix, $postId, '_product_image_gallery');
    if (empty($galleryIds)) return '';
    $ids = explode(',', $galleryIds);
    $urls = [];
    foreach ($ids as $attachmentId) {
        $attachmentId = trim($attachmentId);
        if (empty($attachmentId)) continue;
        $sql = "SELECT guid FROM {$prefix}posts WHERE ID = :id AND post_type = 'attachment'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $attachmentId]);
        $url = $stmt->fetchColumn();
        if ($url) $urls[] = fixDomain($url);
    }
    return implode(', ', $urls);
}

// CSV Headers with descriptions
$csvHeaders = [
    'review_type (editor|user_comment)',
    'product_id (WooCommerce product ID)',
    'product_name (Product title)',
    'product_url (Product URL)',
    'review_id (Comment ID for user comments)',
    'author_name (AppsFomo Editorial or user name)',
    'author_email (User email)',
    'user_id (WordPress user ID)',
    'author_ip (IP address)',
    'review_heading (Editor review title)',
    'review_summary (Editor review summary)',
    'content_raw (Original HTML - user comments)',
    'content_markdown (Markdown - user comments)',
    'content_images (JSON array of image URLs)',
    'has_images (true|false)',
    'image_count (Number of images)',
    'overall_rating (Score e.g. 9.1)',
    'criteria_ratings (Serialized criteria data)',
    'pros_raw (Original pros - newline separated)',
    'pros_markdown (Pros in Markdown)',
    'cons_raw (Original cons - newline separated)',
    'cons_markdown (Cons in Markdown)',
    'helpful_votes (Upvotes - user comments)',
    'unhelpful_votes (Downvotes - user comments)',
    'approval_status (publish|0|1)',
    'created_date (Creation timestamp)',
    'product_categories (Comma-separated)',
    'product_store (AppSumo|Stacksocial etc)',
    'product_tags (Comma-separated)',
    'featured_image_url (Featured image URL)',
    'gallery_image_urls (Comma-separated gallery URLs)',
    'attr_review_date (Review Date)',
    'attr_company_name (Company Name)',
    'attr_appsfomo_rating (Appsfomo Rating)',
    'attr_country (Country)',
    'attr_marketplace (Marketplace)',
    'attr_free_version (Free Version)',
    'attr_documentation (Documentation)',
    'attr_affiliate_program (Affiliate Program)',
    'attr_mobile_app (Mobile app)',
    'attr_payment_options (Payment Options)',
    'attr_lifetime_deal (Lifetime Deal)',
    'attr_money_back (Money-Back)',
    'attr_support (Support)',
    'attr_ltd_platform (LTD Platform)',
    // NEW COLUMNS for full post content
    'post_content_html (Full article HTML from wp_posts)',
    'post_content_markdown (Markdown conversion of article)',
    'inline_image_urls (JSON array of images from post_content)'
];

// Open CSV file
$fp = fopen($output_file, 'w');
fwrite($fp, "\xEF\xBB\xBF"); // UTF-8 BOM
fputcsv($fp, $csvHeaders, ',', '"', '\\');

$totalRows = 0;
$editorReviews = 0;

// ============================================
// Export WooCommerce Products with Full Content
// ============================================
echo "Exporting WooCommerce products with full content...\n";

// Include post_content in the query
$sql = "SELECT p.ID, p.post_title, p.guid, p.post_date, p.post_status, p.post_content
        FROM {$prefix}posts p
        WHERE p.post_type = 'product'
        ORDER BY p.ID";

$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($products) . " products.\n";

foreach ($products as $product) {
    $postId = $product['ID'];

    // Get the full post content
    $postContentHtml = fixDomain($product['post_content']);
    $postContentMarkdown = htmlToMarkdown($postContentHtml);
    $inlineImageUrls = extractImageUrls($postContentHtml);

    // Get editor review data
    $overallScore = getPostMeta($pdo, $prefix, $postId, 'rehub_review_overall_score');
    $manualScore = getPostMeta($pdo, $prefix, $postId, '_review_post_score_manual');
    $reviewHeading = getPostMeta($pdo, $prefix, $postId, '_review_heading');
    $reviewSummary = getPostMeta($pdo, $prefix, $postId, '_review_post_summary_text');
    $prosRaw = getPostMeta($pdo, $prefix, $postId, '_review_post_pros_text');
    $consRaw = getPostMeta($pdo, $prefix, $postId, '_review_post_cons_text');
    $criteriaJson = getPostMeta($pdo, $prefix, $postId, '_review_post_criteria');

    // Use overall score or manual score
    $rating = $overallScore ?: $manualScore;

    // Get taxonomies
    $categories = getPostTerms($pdo, $prefix, $postId, 'product_cat');
    $store = getPostTerms($pdo, $prefix, $postId, 'dealstore');
    $tags = getPostTerms($pdo, $prefix, $postId, 'product_tag');

    // Get gallery
    $featuredImage = getFeaturedImageUrl($pdo, $prefix, $postId);
    $galleryUrls = getGalleryUrls($pdo, $prefix, $postId);

    // Get product attributes
    $attrs = [
        'review-date' => getProductAttribute($pdo, $prefix, $postId, 'review-date'),
        'company-name' => getProductAttribute($pdo, $prefix, $postId, 'company-name'),
        'appsfomo-rating' => getProductAttribute($pdo, $prefix, $postId, 'appsfomo-rating'),
        'country' => getProductAttribute($pdo, $prefix, $postId, 'country'),
        'marketplace' => getProductAttribute($pdo, $prefix, $postId, 'marketplace'),
        'free-version' => getProductAttribute($pdo, $prefix, $postId, 'free-version'),
        'documentation' => getProductAttribute($pdo, $prefix, $postId, 'documentation'),
        'affiliate-program' => getProductAttribute($pdo, $prefix, $postId, 'affiliate-program'),
        'mobile-app' => getProductAttribute($pdo, $prefix, $postId, 'mobile-app'),
        'payment-options' => getProductAttribute($pdo, $prefix, $postId, 'payment-options'),
        'lifetime-deal' => getProductAttribute($pdo, $prefix, $postId, 'lifetime-deal'),
        'money-back' => getProductAttribute($pdo, $prefix, $postId, 'money-back'),
        'support' => getProductAttribute($pdo, $prefix, $postId, 'support'),
        'ltd-platform' => getProductAttribute($pdo, $prefix, $postId, 'ltd-platform'),
    ];

    // Process pros/cons content
    $prosMarkdown = htmlToMarkdown($prosRaw);
    $consMarkdown = htmlToMarkdown($consRaw);

    // Calculate images from post content
    $inlineImagesArray = json_decode($inlineImageUrls, true);
    $hasImages = !empty($inlineImagesArray) ? 'true' : 'false';
    $imageCount = count($inlineImagesArray);

    $row = [
        'editor',                              // review_type
        $postId,                               // product_id
        $product['post_title'],                // product_name
        fixDomain($product['guid']),           // product_url
        '',                                    // review_id (N/A for editor)
        'AppsFomo Editorial',                  // author_name
        '',                                    // author_email
        '',                                    // user_id
        '',                                    // author_ip
        $reviewHeading,                        // review_heading
        $reviewSummary,                        // review_summary
        '',                                    // content_raw (for user comments)
        '',                                    // content_markdown (for user comments)
        $inlineImageUrls,                      // content_images - now from post_content
        $hasImages,                            // has_images
        $imageCount,                           // image_count
        $rating,                               // overall_rating
        $criteriaJson,                         // criteria_ratings
        $prosRaw,                              // pros_raw
        $prosMarkdown,                         // pros_markdown
        $consRaw,                              // cons_raw
        $consMarkdown,                         // cons_markdown
        '',                                    // helpful_votes
        '',                                    // unhelpful_votes
        $product['post_status'],               // approval_status
        $product['post_date'],                 // created_date
        $categories,                           // product_categories
        $store,                                // product_store
        $tags,                                 // product_tags
        $featuredImage,                        // featured_image_url
        $galleryUrls,                          // gallery_image_urls
        $attrs['review-date'],
        $attrs['company-name'],
        $attrs['appsfomo-rating'],
        $attrs['country'],
        $attrs['marketplace'],
        $attrs['free-version'],
        $attrs['documentation'],
        $attrs['affiliate-program'],
        $attrs['mobile-app'],
        $attrs['payment-options'],
        $attrs['lifetime-deal'],
        $attrs['money-back'],
        $attrs['support'],
        $attrs['ltd-platform'],
        // NEW COLUMNS
        $postContentHtml,                      // post_content_html
        $postContentMarkdown,                  // post_content_markdown
        $inlineImageUrls,                      // inline_image_urls
    ];

    fputcsv($fp, $row, ',', '"', '\\');
    $totalRows++;
    $editorReviews++;

    if ($editorReviews % 50 == 0) {
        echo "Processed $editorReviews products...\n";
    }
}

fclose($fp);

echo "\n========================================\n";
echo "Export completed!\n";
echo "Products exported: $editorReviews\n";
echo "Total rows: $totalRows\n";
echo "Output file: $output_file\n";
echo "========================================\n";

$fileSize = round(filesize($output_file) / 1024, 2);
$fileSizeMB = round(filesize($output_file) / 1024 / 1024, 2);
echo "File size: {$fileSize} KB ({$fileSizeMB} MB)\n";

// Verify a sample row
echo "\n=== Sample Verification ===\n";
$stmt = $pdo->query("SELECT ID, post_title, LENGTH(post_content) as content_length FROM {$prefix}posts WHERE post_type = 'product' AND LENGTH(post_content) > 1000 ORDER BY content_length DESC LIMIT 3");
foreach ($stmt as $row) {
    echo "Product {$row['ID']}: {$row['post_title']} - Content length: {$row['content_length']} chars\n";
}
