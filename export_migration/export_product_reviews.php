<?php
/**
 * Product Reviews Export Script
 * Exports WordPress/WooCommerce product reviews to CSV for Apps Fomo migration
 *
 * Content Processing:
 * - content_raw: Original HTML
 * - content_markdown: Converted to Markdown
 * - content_images: JSON array of extracted image URLs (kept as-is)
 * - Links converted to plain text URLs
 */

// Database configuration
$db_config = [
    'host' => 'localhost',
    'name' => 'wpappsfomo_db',
    'user' => 'root',
    'pass' => '',
    'prefix' => 'wp_'
];

// Output file
$output_file = __DIR__ . '/product_reviews_export.csv';

// Connect to database
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "Connected to database successfully.\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$prefix = $db_config['prefix'];

/**
 * Convert HTML to Markdown
 */
function htmlToMarkdown($html) {
    if (empty($html)) return '';

    $text = $html;

    // Convert <br> and <br/> to newlines
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

    // Convert <p> tags to double newlines
    $text = preg_replace('/<p[^>]*>/i', '', $text);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);

    // Convert <strong> and <b> to **bold**
    $text = preg_replace('/<(strong|b)[^>]*>(.*?)<\/(strong|b)>/is', '**$2**', $text);

    // Convert <em> and <i> to *italic*
    $text = preg_replace('/<(em|i)[^>]*>(.*?)<\/(em|i)>/is', '*$2*', $text);

    // Convert links to plain text URL format: text (url)
    $text = preg_replace_callback('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', function($matches) {
        $url = $matches[1];
        $linkText = strip_tags($matches[2]);

        // If link text is just "image" or similar, just show the URL
        if (in_array(strtolower(trim($linkText)), ['image', 'img', 'screenshot', 'photo', 'picture', 'link', 'here', 'click here'])) {
            return $url;
        }

        // If link text equals URL, just show URL once
        if (trim($linkText) === $url || trim($linkText) === '') {
            return $url;
        }

        return "$linkText ($url)";
    }, $text);

    // Convert <ul> and <li> to markdown lists
    $text = preg_replace('/<ul[^>]*>/i', '', $text);
    $text = preg_replace('/<\/ul>/i', "\n", $text);
    $text = preg_replace('/<li[^>]*>(.*?)<\/li>/is', "- $1\n", $text);

    // Convert <ol> and numbered <li>
    $text = preg_replace('/<ol[^>]*>/i', '', $text);
    $text = preg_replace('/<\/ol>/i', "\n", $text);

    // Convert headings
    $text = preg_replace('/<h1[^>]*>(.*?)<\/h1>/is', "# $1\n", $text);
    $text = preg_replace('/<h2[^>]*>(.*?)<\/h2>/is', "## $1\n", $text);
    $text = preg_replace('/<h3[^>]*>(.*?)<\/h3>/is', "### $1\n", $text);

    // Remove remaining HTML tags (span, div, etc.)
    $text = strip_tags($text);

    // Decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Clean up escape sequences
    $text = str_replace(['\r\n', '\r', '\n'], "\n", $text);
    $text = str_replace(['\"', "\'"], ['"', "'"], $text);

    // Normalize multiple newlines to max 2
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    // Trim whitespace
    $text = trim($text);

    return $text;
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
        $images = array_merge($images, $imgMatches[1]);
    }

    // Extract from <a> tags that link to images
    preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $linkMatches);
    if (!empty($linkMatches[1])) {
        foreach ($linkMatches[1] as $url) {
            // Check if URL is an image
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i', $url) ||
                preg_match('/d\.pr\/i\//i', $url) ||  // Droplr images
                preg_match('/imgur\.com/i', $url) ||
                preg_match('/cloudinary\.com/i', $url) ||
                preg_match('/wp-content\/uploads/i', $url)) {
                $images[] = $url;
            }
        }
    }

    // Remove duplicates
    $images = array_unique($images);

    return json_encode(array_values($images));
}

/**
 * Get taxonomy terms for a post
 */
function getPostTerms($pdo, $prefix, $postId, $taxonomy) {
    $sql = "SELECT t.name
            FROM {$prefix}terms t
            INNER JOIN {$prefix}term_taxonomy tt ON t.term_id = tt.term_id
            INNER JOIN {$prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tr.object_id = :post_id AND tt.taxonomy = :taxonomy";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['post_id' => $postId, 'taxonomy' => $taxonomy]);
    $terms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return implode(', ', $terms);
}

/**
 * Get product attribute value
 */
function getProductAttribute($pdo, $prefix, $postId, $attributeSlug) {
    $taxonomy = 'pa_' . $attributeSlug;

    $sql = "SELECT t.name
            FROM {$prefix}terms t
            INNER JOIN {$prefix}term_taxonomy tt ON t.term_id = tt.term_id
            INNER JOIN {$prefix}term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            WHERE tr.object_id = :post_id AND tt.taxonomy = :taxonomy";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['post_id' => $postId, 'taxonomy' => $taxonomy]);
    $values = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return implode(', ', $values);
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
 * Get comment meta value
 */
function getCommentMeta($pdo, $prefix, $commentId, $metaKey) {
    $sql = "SELECT meta_value FROM {$prefix}commentmeta WHERE comment_id = :comment_id AND meta_key = :meta_key LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['comment_id' => $commentId, 'meta_key' => $metaKey]);
    return $stmt->fetchColumn() ?: '';
}

/**
 * Get gallery image URLs
 */
function getGalleryUrls($pdo, $prefix, $postId) {
    // Get gallery IDs
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
        if ($url) $urls[] = $url;
    }

    return implode(', ', $urls);
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
    return $stmt->fetchColumn() ?: '';
}

// Define CSV headers
$csvHeaders = [
    // Review Core Fields
    'review_id',
    'product_id',
    'product_name',
    'product_url',

    // Reviewer Info
    'author_name',
    'author_email',
    'user_id',
    'author_ip',

    // Review Content (Structured)
    'content_raw',
    'content_markdown',
    'content_images',
    'has_images',
    'image_count',

    // Review Ratings
    'overall_rating',
    'criteria_ratings',
    'pros',
    'cons',

    // Engagement
    'helpful_votes',
    'unhelpful_votes',

    // Status & Dates
    'approval_status',
    'created_date',
    'updated_date',

    // Product Categories & Taxonomies
    'product_categories',
    'product_store',
    'product_tags',

    // Product Gallery
    'featured_image_url',
    'gallery_image_urls',

    // Product Attributes
    'attr_review_date',
    'attr_company_name',
    'attr_appsfomo_rating',
    'attr_country',
    'attr_marketplace',
    'attr_free_version',
    'attr_documentation',
    'attr_affiliate_program',
    'attr_mobile_app',
    'attr_payment_options',
    'attr_lifetime_deal',
    'attr_money_back',
    'attr_support',
    'attr_ltd_platform'
];

// Main query to get reviews
$sql = "SELECT
            c.comment_ID,
            c.comment_post_ID,
            c.comment_author,
            c.comment_author_email,
            c.user_id,
            c.comment_author_IP,
            c.comment_content,
            c.comment_approved,
            c.comment_date,
            c.comment_date_gmt,
            p.post_title,
            p.guid as product_url
        FROM {$prefix}comments c
        INNER JOIN {$prefix}posts p ON c.comment_post_ID = p.ID
        WHERE p.post_type = 'product'
        AND c.comment_type IN ('review', 'comment', '')
        ORDER BY c.comment_date DESC";

echo "Querying reviews...\n";
$stmt = $pdo->query($sql);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalReviews = count($reviews);
echo "Found {$totalReviews} reviews.\n";

// Open CSV file
$fp = fopen($output_file, 'w');
if (!$fp) {
    die("Could not create output file: {$output_file}\n");
}

// Write UTF-8 BOM for Excel compatibility
fwrite($fp, "\xEF\xBB\xBF");

// Write headers
fputcsv($fp, $csvHeaders, ',', '"', '\\');

// Process each review
$processed = 0;
foreach ($reviews as $review) {
    $processed++;

    if ($processed % 50 == 0) {
        echo "Processing review {$processed}/{$totalReviews}...\n";
    }

    $commentId = $review['comment_ID'];
    $postId = $review['comment_post_ID'];
    $rawContent = $review['comment_content'];

    // Process content
    $markdownContent = htmlToMarkdown($rawContent);
    $contentImages = extractImageUrls($rawContent);
    $imagesArray = json_decode($contentImages, true);
    $hasImages = !empty($imagesArray) ? 'true' : 'false';
    $imageCount = count($imagesArray);

    // Get comment meta
    $overallRating = getCommentMeta($pdo, $prefix, $commentId, 'user_average');
    $criteriaRatings = getCommentMeta($pdo, $prefix, $commentId, 'user_criteria');
    $pros = getCommentMeta($pdo, $prefix, $commentId, 'pros_review');
    $cons = getCommentMeta($pdo, $prefix, $commentId, 'cons_review');
    $helpfulVotes = getCommentMeta($pdo, $prefix, $commentId, 'recomm_plus') ?: '0';
    $unhelpfulVotes = getCommentMeta($pdo, $prefix, $commentId, 'recomm_minus') ?: '0';

    // Get taxonomies
    $categories = getPostTerms($pdo, $prefix, $postId, 'product_cat');
    $store = getPostTerms($pdo, $prefix, $postId, 'dealstore');
    $tags = getPostTerms($pdo, $prefix, $postId, 'product_tag');

    // Get gallery
    $featuredImage = getFeaturedImageUrl($pdo, $prefix, $postId);
    $galleryUrls = getGalleryUrls($pdo, $prefix, $postId);

    // Get product attributes
    $attrReviewDate = getProductAttribute($pdo, $prefix, $postId, 'review-date');
    $attrCompanyName = getProductAttribute($pdo, $prefix, $postId, 'company-name');
    $attrAppsfomoRating = getProductAttribute($pdo, $prefix, $postId, 'appsfomo-rating');
    $attrCountry = getProductAttribute($pdo, $prefix, $postId, 'country');
    $attrMarketplace = getProductAttribute($pdo, $prefix, $postId, 'marketplace');
    $attrFreeVersion = getProductAttribute($pdo, $prefix, $postId, 'free-version');
    $attrDocumentation = getProductAttribute($pdo, $prefix, $postId, 'documentation');
    $attrAffiliateProgram = getProductAttribute($pdo, $prefix, $postId, 'affiliate-program');
    $attrMobileApp = getProductAttribute($pdo, $prefix, $postId, 'mobile-app');
    $attrPaymentOptions = getProductAttribute($pdo, $prefix, $postId, 'payment-options');
    $attrLifetimeDeal = getProductAttribute($pdo, $prefix, $postId, 'lifetime-deal');
    $attrMoneyBack = getProductAttribute($pdo, $prefix, $postId, 'money-back');
    $attrSupport = getProductAttribute($pdo, $prefix, $postId, 'support');
    $attrLtdPlatform = getProductAttribute($pdo, $prefix, $postId, 'ltd-platform');

    // Build row
    $row = [
        $commentId,
        $postId,
        $review['post_title'],
        $review['product_url'],

        $review['comment_author'],
        $review['comment_author_email'],
        $review['user_id'],
        $review['comment_author_IP'],

        $rawContent,
        $markdownContent,
        $contentImages,
        $hasImages,
        $imageCount,

        $overallRating,
        $criteriaRatings,
        $pros,
        $cons,

        $helpfulVotes,
        $unhelpfulVotes,

        $review['comment_approved'],
        $review['comment_date'],
        $review['comment_date_gmt'],

        $categories,
        $store,
        $tags,

        $featuredImage,
        $galleryUrls,

        $attrReviewDate,
        $attrCompanyName,
        $attrAppsfomoRating,
        $attrCountry,
        $attrMarketplace,
        $attrFreeVersion,
        $attrDocumentation,
        $attrAffiliateProgram,
        $attrMobileApp,
        $attrPaymentOptions,
        $attrLifetimeDeal,
        $attrMoneyBack,
        $attrSupport,
        $attrLtdPlatform
    ];

    fputcsv($fp, $row, ',', '"', '\\');
}

fclose($fp);

echo "\n========================================\n";
echo "Export completed!\n";
echo "Total reviews exported: {$totalReviews}\n";
echo "Output file: {$output_file}\n";
echo "========================================\n";

// Show file size
$fileSize = filesize($output_file);
$fileSizeKB = round($fileSize / 1024, 2);
echo "File size: {$fileSizeKB} KB\n";
