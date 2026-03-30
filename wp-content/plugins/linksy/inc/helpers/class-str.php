<?php

namespace Linksy\Inc\Helpers;

/**
 * Hadndle Strings.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

/**
 * String class.
 */
class Str {
    public static function word_count($string)
    {
        return sizeof(explode(" ", $string));
    }

    public static function strip_tags($string)
    {
        return wp_strip_all_tags($string);
    }

    public static function strip_comments($string)
    {
        return preg_replace('/<!--(.|\s)*?-->/', '', $string);
    }

    public static function strip_shortcodes( $string ) 
    {
        $shortcodes_to_trim = [
            'fusion_builder_container',
            'fusion_builder_row',
            'fusion_builder_column',
            'fusion_text',
            'fusion_checklist',
            'fusion_li_item',
            'fusion_toggle'
        ];
        
        preg_match_all( '@\[([^<>&/\[\]\x00-\x20=]++)@', $string, $matches );
    
        foreach ($matches[1] as $code) {
            if (in_array($code, $shortcodes_to_trim)) {
                foreach ([ '['.$code, '[/'.$code ] as $v) {
                    $start = Str::position($string, $v);
                    if ( false === $start) {
                        continue;
                    }
                    
                    $tag_end = Str::position(substr($string, $start), ']');
                    if ($tag_end === false) {
                       continue;
                    }
                    $end = $start + $tag_end;
                    
                    $string = trim(substr_replace($string, '', $start, ($end - $start) + 1));
                }
            } else  {
                $start = Str::position($string, '['.$code);
                if ( false === $start) {
                    continue;
                }
                
                $end = Str::position(substr($string, $start), '[/'.$code.']');
                if ( false === $end ) {
                    $tag_end = Str::position(substr($string, $start), ']');
                } else {
                    $tag_end = Str::position(substr($string, $start + $end), ']'); 
                }
                if ($tag_end === false) {
                    continue;
                }
                $end += $start + $tag_end;
                
                if ($start > 0 && $string[$start - 1] == ' ') {
                    $start -= 1;
                }
                
                $string = trim(substr_replace($string, '', $start, ($end - $start) + 1));
            }
        }
        
        return $string;
    }

	public static function strip_emojis($string)
    {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);
    
        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);
    
        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);
    
        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);
        
        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);
    
        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);
    
        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        // illumiish
        $regex_illuminish = '/[\x{13080}-\x{13089}]/u';
        $clear_string = preg_replace($regex_illuminish, '', $clear_string);
    
        return $clear_string;
    }

    public static function strip_unicode($string)
    {   
        $special_chars   = '%81,%7F,%8D,%8F,%C2%90,%C2,%90,%9D,%C2%A0,%A0,%C2%AD,%AD,%08,%09,%0A,%0D';
        $special_chars_array = explode(',', $special_chars );

        $string  = urlencode($string);
        foreach($special_chars_array as $v){
            $string  =  str_replace($v, ' ', $string);
        }
        $string = urldecode($string);

        return $string;
    }

    public static function strip_quotes($string)
    {
        if (substr($string, 0, 1) == '"' || substr($string, 0, 1) == "'") {
            $string = substr($string, 1);
        }

        if (substr($string, -1) == '"' || substr($string, -1) == "'") {
            $string = substr($string, 0,  -1);
        }
        return $string;
    }

    public static function delete_tags($string, $tags) {
        foreach ($tags as $tag) {
          $pattern = "/<{$tag}[^>]*>(.*?)<\/{$tag}>|<{$tag}[^>]*\/?>/is";
          while (preg_match($pattern, $string)) {
            $string = preg_replace($pattern, '', $string);
          }
        }

        return $string;
    }

    public static function text_to_paragraphs($content)
    {
        $replace_unicode = array(
            ['\u003c', '\u003e', '\u0022', '&nbsp;'],
            ['<', '>', '"', ' ']
        );

        $content = str_ireplace($replace_unicode[0], $replace_unicode[1], $content);

        $replace = [
            ['<div', '<br', '<li', '<p', '<h1', '<h2', '<h3', '<h4', '<h5', '<h6'],
            ["\n<div", "\n<br", "\n<li", "\n<p", "\n<h1", "\n<h2", "\n<h3", "\n<h4", "\n<h5", "\n<h6"]
        ];

        $content = str_ireplace($replace[0], $replace[1], $content);
        $content = preg_replace('|\.([A-Z]{1})|', ".\n$1", $content);
        $content = preg_replace('|\[[^\]]+\]|i', "\n", $content);

        $output = [];
        $sentences = explode("\n", $content);
        foreach ($sentences as $key => $sentence)
        {
            if (empty($sentence)) {
                continue;
            }

            // remove empty tags
            if (empty(trim(strip_tags($sentence)))) {
                continue;
            }

            // remove comments
            if (substr($sentence, 0, 4) == '<!--' && substr($sentence, -3) == '-->') {
                continue;
            }

            // todo: remove numbering

            // remove empty
            $sentence = trim($sentence);
            if (empty($sentence) || $sentence == "") {
                continue;
            }

            $output[] = $sentence;
        }

        return $output;
    }

    public static function text_to_sentences($content, $min_length = 1)
    {
        $content = trim($content);
        if (in_array(substr($content, -1), ['.', ',', '!', '?'])) {
            $content = trim(substr($content, 0, -1));
        }

        if (empty($content) || str_word_count($content) < $min_length) {
            return array();
        }

        $replace = [".\r", '. ', '!', '?']; //[', ', ': ', '; ', ' – ', ' (', ') ', ' {', '} ', '—', '”'];
        
        //change divided symbols inside tags to special codes
        preg_match_all('|<[^>]+>|', $content, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $tag) {
                $tag_replaced = $tag;
                foreach ($replace as $key => $value) {
                    if (strpos($tag, $value) !== false) {
                        $tag_replaced = str_replace($value, "[rp$key]", $tag_replaced);
                    }
                }

                if ($tag_replaced != $tag) {
                    $content = str_replace($tag, $tag_replaced, $content);
                }
            }
        }

        //divide sentence to phrases
        $content = str_ireplace($replace, "\n", $content);

        //change special codes to divided symbols inside tags
        foreach ($replace as $key => $value) {
            $content = trim(str_replace("[rp$key]", $value, $content));
        }

        return array_filter( explode("\n", $content), function($phrase) use ($min_length) {
            return str_word_count($phrase) > $min_length;
        });
    }

    public static function position($content, $str, $whole_word = false)
    {
        // $content = trim($content); todo: confirm importance

        if ($whole_word) {
            if (preg_match( '#\b' . preg_quote( $str, '#' ) . '\b#i', $content ) !== 1) {
                return false;
            }
        }
        
        return strpos(strtolower($content), strtolower($str));
    }

    public static function contains($content, $str, $whole_word = false)
    {
        return self::position($content, $str, $whole_word) !== false;
    }

    public static function compare($a, $b, $key, $dir) {
        if ($dir == 'desc') {
            $temp = $a;
            $a = $b;
            $b = $temp;
        }

        if ($a[$key] == $b[$key]) return 0;
        return $a[$key] < $b[$key] ? -1 : 1;
    }
}
