<?php

namespace Linksy\Inc\Helpers;

/**
 * Hadndle urls.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

/**
 * Links class.
 */
class Links {
    public static function is_image($link)
    {
        $imgExts = array("gif", "jpg", "jpeg", "png", "tiff", "tif");
        $urlExt = pathinfo($link, PATHINFO_EXTENSION);
        
        return in_array($urlExt, $imgExts);
    }

    public static function extract_from_html($content) 
    {
        $report_links = [];

        preg_match_all('/<a[^>]+href=\"(.*?)\"[^>]*>(.*?)<\/a>/', $content, $matches);
        if (!empty($matches[0])) {
            $dom = new \DOMDocument();

            foreach ($matches[0] as $k => $tag) {
                if (!empty($matches[2][$k])) {
                    $dom->loadHTML($matches[2][$k]);

                    $link = [
                        'text' => $dom->textContent,
                        'meta' => array(),
                        'rel' => '',
                    ];

                    $href = $matches[1][$k];
                    if (!empty($href)) {
                        $is_image_url = self::is_image($href);
                        $link['link'] = $href;

                        if ($is_image_url) {
                            $link['meta']['is_image_url'] = 1;
                        }
                    }

                    // todo: rel

                    $report_links[] = $link;
                }
            }
        }

        return $report_links;
    }
    
    public static function extract_from_html_dom($content)
    {
        $report_links = [];

        $content = Encoding::toUTF8($content);

        # replace all special characters 

        $doc = new \DOMDocument();
        @$doc->loadHTML( $content );  # mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        
        $anchors = $doc->getElementsByTagName('a');

        foreach ($anchors as $a) {
            $link = [
                'text' => $a->textContent,
                'meta' => array(),
                'rel' => '',
            ];

            $href = $a->getAttribute('href');
            if (!empty($href)) {
                $is_image_url = self::is_image($href);
                $link['link'] = $href;

                if ($is_image_url) {
                    $link['meta']['is_image_url'] = 1;
                }
            }

            $rel = $a->getAttribute('rel');
            if (!empty($rel)) {
                $link['rel'] = strtolower($rel);
            }

            $report_links[] = $link;
        }

        return $report_links;
    }
}
