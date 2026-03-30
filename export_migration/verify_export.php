<?php
$fp = fopen('combined_reviews_export_v2.csv', 'r');
fgetcsv($fp, 0, ',', '"', '\\'); // skip header

$withContent = 0;
$withImages = 0;
$total = 0;
$maxContentLen = 0;
$maxImagesCount = 0;

while (($row = fgetcsv($fp, 0, ',', '"', '\\')) !== false) {
    $total++;

    // post_content_html is column index 45
    if (!empty($row[45])) {
        $withContent++;
        $len = strlen($row[45]);
        if ($len > $maxContentLen) $maxContentLen = $len;
    }

    // inline_image_urls is column index 47
    $imgs = json_decode($row[47] ?? '[]', true);
    if (!empty($imgs)) {
        $withImages++;
        $count = count($imgs);
        if ($count > $maxImagesCount) $maxImagesCount = $count;
    }
}
fclose($fp);

echo "=== Export Verification ===\n";
echo "Total products: $total\n";
echo "With post_content_html: $withContent\n";
echo "With inline images: $withImages\n";
echo "Max content length: $maxContentLen chars\n";
echo "Max images in a product: $maxImagesCount\n";

// Check domain replacement
echo "\n=== Domain Check ===\n";
$content = file_get_contents('combined_reviews_export_v2.csv');
$localCount = substr_count($content, 'wpappsfomo.test');
$prodCount = substr_count($content, 'appsfomo.com');
echo "wpappsfomo.test occurrences: $localCount\n";
echo "appsfomo.com occurrences: $prodCount\n";
