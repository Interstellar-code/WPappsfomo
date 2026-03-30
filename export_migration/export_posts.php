<?php
/**
 * Export Posts (Deals/Products) to CSV
 *
 * Exports all posts (post_type='post') with their meta fields,
 * categories, tags, and affiliate store relationships.
 *
 * Run: php export_posts.php
 */

// Load WordPress
require_once dirname(__DIR__) . '/wp-load.php';

// CSV output file
$csv_file = __DIR__ . '/posts_export.csv';

echo "=== Posts Export Script ===\n\n";

// Get all published posts
$args = [
    'post_type' => 'post',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft'],
    'orderby' => 'date',
    'order' => 'DESC',
];

$query = new WP_Query($args);

if (!$query->have_posts()) {
    echo "No posts found.\n";
    exit(1);
}

echo "Found {$query->found_posts} posts to export.\n";

// Define the columns we want to export (in order)
$standard_columns = [
    'ID',
    'title',
    'slug',
    'status',
    'date',
    'modified_date',
    'author',
    'excerpt',
    'content',
    'featured_image_url',
    'permalink',
];

// Key meta fields for deals
$deal_meta_keys = [
    'rehub_offer_name',
    'rehub_offer_product_url',
    'rehub_offer_product_price',
    'rehub_offer_product_price_old',
    'rehub_offer_product_desc',
    'rehub_offer_disclaimer',
    'rehub_offer_product_thumb',
    'rehub_offer_product_coupon',
    'rehub_offer_coupon_date',
    'rehub_offer_btn_text',
    'is_editor_choice',
];

// Additional meta fields
$additional_meta_keys = [
    'rehub_main_product_price',
    'rehub_views',
    'post_hot_count',
    'post_wish_count',
    're_post_expired',
    '_post_layout',
    'show_featured_image',
    'rh_post_image_videos',
    'video_post',
    'gallery_post',
    'review_post',
];

// Taxonomy columns
$taxonomy_columns = [
    'categories',           // category taxonomy (names)
    'category_slugs',       // category taxonomy (slugs)
    'tags',                 // post_tag taxonomy (names)
    'tag_slugs',            // post_tag taxonomy (slugs)
    'affiliate_store_name', // dealstore taxonomy (name)
    'affiliate_store_slug', // dealstore taxonomy (slug)
    'affiliate_store_id',   // dealstore taxonomy (term_id for relationship)
];

// Image columns
$image_columns = [
    'content_images',  // All image URLs found in post content
];

// Combine all columns
$all_columns = array_merge(
    $standard_columns,
    $deal_meta_keys,
    $additional_meta_keys,
    $taxonomy_columns,
    $image_columns
);

// Collect all posts data
$posts_data = [];
$count = 0;

while ($query->have_posts()) {
    $query->the_post();
    $post_id = get_the_ID();
    $count++;

    if ($count % 100 === 0) {
        echo "Processing post {$count}/{$query->found_posts}...\n";
    }

    // Get featured image URL
    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_image_url = $featured_image_id ? wp_get_attachment_url($featured_image_id) : '';

    // Get post content and extract image URLs
    $content = get_the_content();
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $content_images);
    $content_image_urls = !empty($content_images[1]) ? implode(' | ', $content_images[1]) : '';

    // Get categories
    $categories = wp_get_post_terms($post_id, 'category');
    $category_names = [];
    $category_slugs = [];
    if (!is_wp_error($categories)) {
        foreach ($categories as $cat) {
            $category_names[] = $cat->name;
            $category_slugs[] = $cat->slug;
        }
    }

    // Get tags
    $tags = wp_get_post_terms($post_id, 'post_tag');
    $tag_names = [];
    $tag_slugs = [];
    if (!is_wp_error($tags)) {
        foreach ($tags as $tag) {
            $tag_names[] = $tag->name;
            $tag_slugs[] = $tag->slug;
        }
    }

    // Get affiliate store (dealstore taxonomy)
    $stores = wp_get_post_terms($post_id, 'dealstore');
    $store_name = '';
    $store_slug = '';
    $store_id = '';
    if (!is_wp_error($stores) && !empty($stores)) {
        // Take the first store (usually posts have one store)
        $store_name = $stores[0]->name;
        $store_slug = $stores[0]->slug;
        $store_id = $stores[0]->term_id;
    }

    // Build post data array
    $post_data = [
        'ID' => $post_id,
        'title' => get_the_title(),
        'slug' => get_post_field('post_name', $post_id),
        'status' => get_post_status(),
        'date' => get_the_date('Y-m-d H:i:s'),
        'modified_date' => get_the_modified_date('Y-m-d H:i:s'),
        'author' => get_the_author(),
        'excerpt' => get_the_excerpt(),
        'content' => $content,
        'featured_image_url' => $featured_image_url,
        'permalink' => get_permalink(),
    ];

    // Add deal meta fields
    foreach ($deal_meta_keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        $post_data[$key] = is_array($value) ? json_encode($value) : $value;
    }

    // Add additional meta fields
    foreach ($additional_meta_keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if (is_array($value)) {
            $post_data[$key] = json_encode($value);
        } elseif (is_serialized($value)) {
            $post_data[$key] = json_encode(maybe_unserialize($value));
        } else {
            $post_data[$key] = $value;
        }
    }

    // Add taxonomy data
    $post_data['categories'] = implode(' | ', $category_names);
    $post_data['category_slugs'] = implode(' | ', $category_slugs);
    $post_data['tags'] = implode(' | ', $tag_names);
    $post_data['tag_slugs'] = implode(' | ', $tag_slugs);
    $post_data['affiliate_store_name'] = $store_name;
    $post_data['affiliate_store_slug'] = $store_slug;
    $post_data['affiliate_store_id'] = $store_id;

    // Add content images
    $post_data['content_images'] = $content_image_urls;

    $posts_data[] = $post_data;
}

wp_reset_postdata();

echo "\nProcessed all {$count} posts.\n";

// Write CSV
$fp = fopen($csv_file, 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header
fputcsv($fp, $all_columns);

// Write data
foreach ($posts_data as $post) {
    $row = [];
    foreach ($all_columns as $col) {
        $value = isset($post[$col]) ? $post[$col] : '';
        // Clean up the value for CSV (replace newlines with spaces for single-line cells)
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
echo "Total posts exported: " . count($posts_data) . "\n";
echo "\nColumns included (" . count($all_columns) . " columns):\n";

echo "\n-- Standard Fields --\n";
foreach ($standard_columns as $col) {
    echo "  - {$col}\n";
}

echo "\n-- Deal Meta Fields --\n";
foreach ($deal_meta_keys as $col) {
    echo "  - {$col}\n";
}

echo "\n-- Additional Meta Fields --\n";
foreach ($additional_meta_keys as $col) {
    echo "  - {$col}\n";
}

echo "\n-- Taxonomy Fields --\n";
foreach ($taxonomy_columns as $col) {
    echo "  - {$col}\n";
}

echo "\n-- Image Fields --\n";
foreach ($image_columns as $col) {
    echo "  - {$col}\n";
}
