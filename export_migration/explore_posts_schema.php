<?php
/**
 * Explore Posts and Affiliate Store Schema
 *
 * This script analyzes the WordPress database structure for:
 * - Posts (post_type='post') and their custom meta fields
 * - Affiliate Store taxonomy and its term meta
 */

// Load WordPress
require_once dirname(__DIR__) . '/wp-load.php';

global $wpdb;

echo "==========================================================\n";
echo "WORDPRESS DATABASE SCHEMA EXPLORATION\n";
echo "==========================================================\n\n";

// 1. Count posts
$post_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'");
echo "Total Published Posts: {$post_count}\n\n";

// 2. Find all custom taxonomies associated with posts
echo "==========================================================\n";
echo "TAXONOMIES ASSOCIATED WITH POSTS\n";
echo "==========================================================\n\n";

$taxonomies = get_object_taxonomies('post', 'objects');
foreach ($taxonomies as $tax_name => $tax_obj) {
    $term_count = wp_count_terms(['taxonomy' => $tax_name, 'hide_empty' => false]);
    echo "- {$tax_name}: {$tax_obj->label} (Terms: {$term_count})\n";
    echo "  Hierarchical: " . ($tax_obj->hierarchical ? 'Yes' : 'No') . "\n";
    echo "  Public: " . ($tax_obj->public ? 'Yes' : 'No') . "\n\n";
}

// 3. Find all unique meta keys for posts
echo "==========================================================\n";
echo "ALL UNIQUE META KEYS FOR POSTS (post_type='post')\n";
echo "==========================================================\n\n";

$meta_keys = $wpdb->get_results("
    SELECT DISTINCT pm.meta_key, COUNT(*) as usage_count
    FROM {$wpdb->postmeta} pm
    INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE p.post_type = 'post'
    GROUP BY pm.meta_key
    ORDER BY pm.meta_key
");

echo "Found " . count($meta_keys) . " unique meta keys:\n\n";

foreach ($meta_keys as $key) {
    echo "- {$key->meta_key} (used {$key->usage_count} times)\n";
}

// 4. Find Affiliate Store taxonomy details
echo "\n==========================================================\n";
echo "AFFILIATE STORE TAXONOMY ANALYSIS\n";
echo "==========================================================\n\n";

// Look for affiliate-related taxonomies
$affiliate_taxonomies = [];
foreach ($taxonomies as $tax_name => $tax_obj) {
    if (stripos($tax_name, 'affiliate') !== false || stripos($tax_name, 'store') !== false) {
        $affiliate_taxonomies[$tax_name] = $tax_obj;
    }
}

if (empty($affiliate_taxonomies)) {
    echo "Looking for custom taxonomies in database...\n";

    // Check term_taxonomy table for all taxonomies
    $all_taxonomies = $wpdb->get_results("
        SELECT DISTINCT taxonomy, COUNT(*) as term_count
        FROM {$wpdb->term_taxonomy}
        GROUP BY taxonomy
    ");

    echo "All taxonomies in database:\n";
    foreach ($all_taxonomies as $tax) {
        echo "- {$tax->taxonomy}: {$tax->term_count} terms\n";
    }
}

// 5. Find all term meta keys for each taxonomy
echo "\n==========================================================\n";
echo "TERM META KEYS BY TAXONOMY\n";
echo "==========================================================\n\n";

$term_meta_by_taxonomy = $wpdb->get_results("
    SELECT
        tt.taxonomy,
        tm.meta_key,
        COUNT(*) as usage_count
    FROM {$wpdb->termmeta} tm
    INNER JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
    INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
    GROUP BY tt.taxonomy, tm.meta_key
    ORDER BY tt.taxonomy, tm.meta_key
");

$current_taxonomy = '';
foreach ($term_meta_by_taxonomy as $row) {
    if ($row->taxonomy !== $current_taxonomy) {
        if ($current_taxonomy !== '') {
            echo "\n";
        }
        echo "Taxonomy: {$row->taxonomy}\n";
        echo str_repeat('-', 50) . "\n";
        $current_taxonomy = $row->taxonomy;
    }
    echo "  - {$row->meta_key} (used {$row->usage_count} times)\n";
}

// 6. Get a sample post with all its meta
echo "\n==========================================================\n";
echo "SAMPLE POST WITH ALL META DATA\n";
echo "==========================================================\n\n";

$sample_post = $wpdb->get_row("
    SELECT * FROM {$wpdb->posts}
    WHERE post_type = 'post' AND post_status = 'publish'
    ORDER BY ID DESC
    LIMIT 1
");

if ($sample_post) {
    echo "Post ID: {$sample_post->ID}\n";
    echo "Title: {$sample_post->post_title}\n";
    echo "Slug: {$sample_post->post_name}\n";
    echo "Status: {$sample_post->post_status}\n";
    echo "Date: {$sample_post->post_date}\n";
    echo "Modified: {$sample_post->post_modified}\n";
    echo "Author: {$sample_post->post_author}\n";

    echo "\nContent (first 500 chars):\n";
    echo substr($sample_post->post_content, 0, 500) . "...\n";

    echo "\nExcerpt:\n";
    echo $sample_post->post_excerpt . "\n";

    echo "\n--- POST META ---\n";
    $post_meta = get_post_meta($sample_post->ID);
    foreach ($post_meta as $key => $values) {
        echo "\n{$key}:\n";
        foreach ($values as $value) {
            $display_value = maybe_unserialize($value);
            if (is_array($display_value) || is_object($display_value)) {
                echo "  " . json_encode($display_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                $display_value = strlen($value) > 200 ? substr($value, 0, 200) . '...' : $value;
                echo "  {$display_value}\n";
            }
        }
    }

    echo "\n--- TAXONOMIES ---\n";
    foreach ($taxonomies as $tax_name => $tax_obj) {
        $terms = wp_get_post_terms($sample_post->ID, $tax_name);
        if (!empty($terms) && !is_wp_error($terms)) {
            echo "\n{$tax_name}:\n";
            foreach ($terms as $term) {
                echo "  - {$term->name} (ID: {$term->term_id}, Slug: {$term->slug})\n";
            }
        }
    }

    echo "\n--- FEATURED IMAGE ---\n";
    $thumbnail_id = get_post_thumbnail_id($sample_post->ID);
    if ($thumbnail_id) {
        $thumbnail_url = wp_get_attachment_url($thumbnail_id);
        echo "Thumbnail ID: {$thumbnail_id}\n";
        echo "URL: {$thumbnail_url}\n";
    } else {
        echo "No featured image\n";
    }
}

// 7. Get a sample Affiliate Store term with all its meta
echo "\n==========================================================\n";
echo "SAMPLE AFFILIATE STORE TERM WITH ALL META\n";
echo "==========================================================\n\n";

// First, identify the affiliate store taxonomy
$store_taxonomy = null;
foreach ($taxonomies as $tax_name => $tax_obj) {
    if (stripos($tax_name, 'store') !== false || stripos($tax_name, 'affiliate') !== false) {
        $store_taxonomy = $tax_name;
        break;
    }
}

// If not found by name, look for taxonomy with most term meta
if (!$store_taxonomy) {
    $tax_with_meta = $wpdb->get_var("
        SELECT tt.taxonomy
        FROM {$wpdb->termmeta} tm
        INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
        WHERE tt.taxonomy NOT IN ('category', 'post_tag', 'nav_menu')
        GROUP BY tt.taxonomy
        ORDER BY COUNT(DISTINCT tm.meta_key) DESC
        LIMIT 1
    ");
    if ($tax_with_meta) {
        $store_taxonomy = $tax_with_meta;
    }
}

echo "Using taxonomy: {$store_taxonomy}\n\n";

if ($store_taxonomy) {
    $sample_term = $wpdb->get_row($wpdb->prepare("
        SELECT t.*, tt.*
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        WHERE tt.taxonomy = %s
        ORDER BY t.term_id DESC
        LIMIT 1
    ", $store_taxonomy));

    if ($sample_term) {
        echo "Term ID: {$sample_term->term_id}\n";
        echo "Name: {$sample_term->name}\n";
        echo "Slug: {$sample_term->slug}\n";
        echo "Description: " . substr($sample_term->description, 0, 300) . "\n";
        echo "Count: {$sample_term->count}\n";

        echo "\n--- TERM META ---\n";
        $term_meta = get_term_meta($sample_term->term_id);
        foreach ($term_meta as $key => $values) {
            echo "\n{$key}:\n";
            foreach ($values as $value) {
                $display_value = maybe_unserialize($value);
                if (is_array($display_value) || is_object($display_value)) {
                    echo "  " . json_encode($display_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                } else {
                    $display_value = strlen($value) > 500 ? substr($value, 0, 500) . '...' : $value;
                    echo "  {$display_value}\n";
                }
            }
        }

        // Get posts associated with this term
        echo "\n--- POSTS IN THIS TERM ---\n";
        $posts_in_term = get_posts([
            'post_type' => 'post',
            'tax_query' => [
                [
                    'taxonomy' => $store_taxonomy,
                    'terms' => $sample_term->term_id,
                ]
            ],
            'posts_per_page' => 5,
        ]);

        echo "Found " . count($posts_in_term) . " posts (showing up to 5):\n";
        foreach ($posts_in_term as $p) {
            echo "  - [{$p->ID}] {$p->post_title}\n";
        }
    }
}

// 8. Get ALL unique term meta keys with sample values
echo "\n==========================================================\n";
echo "ALL TERM META KEYS WITH SAMPLE VALUES\n";
echo "==========================================================\n\n";

if ($store_taxonomy) {
    $all_term_meta_keys = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT tm.meta_key
        FROM {$wpdb->termmeta} tm
        INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
        WHERE tt.taxonomy = %s
        ORDER BY tm.meta_key
    ", $store_taxonomy));

    echo "Taxonomy: {$store_taxonomy}\n";
    echo "Total unique meta keys: " . count($all_term_meta_keys) . "\n\n";

    foreach ($all_term_meta_keys as $meta) {
        // Get a sample value
        $sample_value = $wpdb->get_var($wpdb->prepare("
            SELECT tm.meta_value
            FROM {$wpdb->termmeta} tm
            INNER JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
            WHERE tt.taxonomy = %s AND tm.meta_key = %s AND tm.meta_value != ''
            LIMIT 1
        ", $store_taxonomy, $meta->meta_key));

        echo "Meta Key: {$meta->meta_key}\n";
        if ($sample_value) {
            $display = maybe_unserialize($sample_value);
            if (is_array($display) || is_object($display)) {
                echo "Sample: " . json_encode($display, JSON_UNESCAPED_SLASHES) . "\n";
            } else {
                echo "Sample: " . (strlen($sample_value) > 200 ? substr($sample_value, 0, 200) . '...' : $sample_value) . "\n";
            }
        }
        echo "\n";
    }
}

// 9. Get multiple sample posts to understand the data better
echo "\n==========================================================\n";
echo "MULTIPLE SAMPLE POSTS (5 posts)\n";
echo "==========================================================\n\n";

$sample_posts = $wpdb->get_results("
    SELECT ID, post_title, post_name, post_date
    FROM {$wpdb->posts}
    WHERE post_type = 'post' AND post_status = 'publish'
    ORDER BY RAND()
    LIMIT 5
");

foreach ($sample_posts as $sp) {
    echo "\n--- Post: {$sp->post_title} (ID: {$sp->ID}) ---\n";

    // Get key meta fields
    $deal_url = get_post_meta($sp->ID, 'deal_url', true);
    $price = get_post_meta($sp->ID, 'price', true);
    $original_price = get_post_meta($sp->ID, 'original_price', true);
    $discount = get_post_meta($sp->ID, 'discount', true);
    $coupon_code = get_post_meta($sp->ID, 'coupon_code', true);

    echo "Deal URL: " . ($deal_url ?: 'N/A') . "\n";
    echo "Price: " . ($price ?: 'N/A') . "\n";
    echo "Original Price: " . ($original_price ?: 'N/A') . "\n";
    echo "Discount: " . ($discount ?: 'N/A') . "\n";
    echo "Coupon Code: " . ($coupon_code ?: 'N/A') . "\n";

    // Get store
    if ($store_taxonomy) {
        $stores = wp_get_post_terms($sp->ID, $store_taxonomy);
        if (!empty($stores) && !is_wp_error($stores)) {
            echo "Store: " . $stores[0]->name . " (ID: " . $stores[0]->term_id . ")\n";
        }
    }
}

// 10. Schema Summary
echo "\n==========================================================\n";
echo "SCHEMA SUMMARY\n";
echo "==========================================================\n\n";

echo "POSTS TABLE (wp_posts):\n";
echo "- Total posts (post_type='post'): {$post_count}\n";
echo "- Unique meta keys: " . count($meta_keys) . "\n\n";

echo "POST META KEYS (grouped by category):\n";
$categorized_keys = [
    'ACF/Custom Fields' => [],
    'WordPress Core' => [],
    'SEO' => [],
    'Other' => [],
];

foreach ($meta_keys as $key) {
    if (strpos($key->meta_key, '_') === 0) {
        $categorized_keys['WordPress Core'][] = $key->meta_key;
    } elseif (strpos($key->meta_key, 'rank_math') !== false || strpos($key->meta_key, 'yoast') !== false || strpos($key->meta_key, '_seo') !== false) {
        $categorized_keys['SEO'][] = $key->meta_key;
    } else {
        $categorized_keys['ACF/Custom Fields'][] = $key->meta_key;
    }
}

foreach ($categorized_keys as $category => $keys) {
    if (!empty($keys)) {
        echo "\n{$category}:\n";
        foreach ($keys as $k) {
            echo "  - {$k}\n";
        }
    }
}

echo "\n\nScript completed successfully!\n";
