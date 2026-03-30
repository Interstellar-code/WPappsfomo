<?php
/**
 * Export Hall of Shame Blog Posts to CSV
 *
 * Run this script from command line:
 * php export_hall_of_shame.php
 */

// Load WordPress
require_once dirname(__DIR__) . '/wp-load.php';

// CSV output file
$csv_file = __DIR__ . '/hall_of_shame_export.csv';

// First, let's find the "Hall of Shame" term
// Check common taxonomies for blog posts
$taxonomies_to_check = ['category', 'post_tag', 'blog_category', 'blog-category', 'blog_categories'];

$hall_of_shame_term = null;
$taxonomy_found = null;

foreach ($taxonomies_to_check as $tax) {
    $term = get_term_by('name', 'Hall of Shame', $tax);
    if ($term) {
        $hall_of_shame_term = $term;
        $taxonomy_found = $tax;
        break;
    }
    // Also try slug
    $term = get_term_by('slug', 'hall-of-shame', $tax);
    if ($term) {
        $hall_of_shame_term = $term;
        $taxonomy_found = $tax;
        break;
    }
}

// If not found in predefined taxonomies, search all taxonomies
if (!$hall_of_shame_term) {
    $all_taxonomies = get_taxonomies([], 'names');
    foreach ($all_taxonomies as $tax) {
        $term = get_term_by('name', 'Hall of Shame', $tax);
        if ($term) {
            $hall_of_shame_term = $term;
            $taxonomy_found = $tax;
            break;
        }
        $term = get_term_by('slug', 'hall-of-shame', $tax);
        if ($term) {
            $hall_of_shame_term = $term;
            $taxonomy_found = $tax;
            break;
        }
    }
}

if (!$hall_of_shame_term) {
    echo "Error: Could not find 'Hall of Shame' category/taxonomy term.\n";
    echo "Available taxonomies:\n";
    print_r(get_taxonomies([], 'names'));
    exit(1);
}

echo "Found 'Hall of Shame' in taxonomy: {$taxonomy_found} (Term ID: {$hall_of_shame_term->term_id})\n";

// Find the post type associated with this taxonomy
$post_types_to_check = ['post', 'blog', 'blog_post', 'blog-post', 'blogs'];
$post_type_found = null;

// Get posts from the Hall of Shame category
$args = [
    'post_type' => 'any',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'tax_query' => [
        [
            'taxonomy' => $taxonomy_found,
            'field' => 'term_id',
            'terms' => $hall_of_shame_term->term_id,
        ],
    ],
];

$query = new WP_Query($args);

if (!$query->have_posts()) {
    echo "No posts found in 'Hall of Shame' category.\n";
    exit(1);
}

echo "Found {$query->found_posts} posts in 'Hall of Shame' category.\n";

// Collect all posts data
$posts_data = [];
$all_meta_keys = [];

while ($query->have_posts()) {
    $query->the_post();
    $post_id = get_the_ID();

    // Get all meta data for this post
    $meta = get_post_meta($post_id);

    // Collect all unique meta keys
    foreach (array_keys($meta) as $key) {
        if (!in_array($key, $all_meta_keys) && strpos($key, '_') !== 0) {
            $all_meta_keys[] = $key;
        }
    }

    // Get featured image
    $featured_image = get_the_post_thumbnail_url($post_id, 'full');

    // Get post content and extract image URLs
    $content = get_the_content();
    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/', $content, $content_images);
    $content_image_urls = !empty($content_images[1]) ? implode(' | ', $content_images[1]) : '';

    // Also check for images in meta fields
    $meta_images = [];
    foreach ($meta as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $v) {
                if (is_string($v) && (strpos($v, '.jpg') !== false || strpos($v, '.png') !== false || strpos($v, '.gif') !== false || strpos($v, '.webp') !== false || strpos($v, 'wp-content/uploads') !== false)) {
                    $meta_images[$key] = $v;
                }
            }
        }
    }

    $post_data = [
        'ID' => $post_id,
        'title' => get_the_title(),
        'slug' => get_post_field('post_name', $post_id),
        'date' => get_the_date('Y-m-d H:i:s'),
        'author' => get_the_author(),
        'content' => $content,
        'excerpt' => get_the_excerpt(),
        'featured_image' => $featured_image,
        'content_images' => $content_image_urls,
        'permalink' => get_permalink(),
        'post_type' => get_post_type(),
    ];

    // Add all meta fields
    foreach ($meta as $key => $value) {
        if (strpos($key, '_') !== 0) { // Skip internal meta keys starting with _
            $post_data['meta_' . $key] = is_array($value) ? implode(' | ', array_map(function($v) {
                return is_array($v) ? json_encode($v) : $v;
            }, $value)) : $value;
        }
    }

    // Add meta images
    if (!empty($meta_images)) {
        $post_data['meta_images'] = implode(' | ', $meta_images);
    }

    $posts_data[] = $post_data;
}

wp_reset_postdata();

// Determine all columns
$all_columns = [];
foreach ($posts_data as $post) {
    foreach (array_keys($post) as $key) {
        if (!in_array($key, $all_columns)) {
            $all_columns[] = $key;
        }
    }
}

// Sort columns to have standard fields first
$standard_fields = ['ID', 'title', 'slug', 'date', 'author', 'content', 'excerpt', 'featured_image', 'content_images', 'permalink', 'post_type'];
$meta_fields = array_diff($all_columns, $standard_fields);
sort($meta_fields);
$all_columns = array_merge($standard_fields, $meta_fields);

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
        // Clean up the value for CSV
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $row[] = $value;
    }
    fputcsv($fp, $row);
}

fclose($fp);

echo "\nExport complete!\n";
echo "CSV file saved to: {$csv_file}\n";
echo "Total posts exported: " . count($posts_data) . "\n";
echo "\nColumns included:\n";
foreach ($all_columns as $col) {
    echo "  - {$col}\n";
}
