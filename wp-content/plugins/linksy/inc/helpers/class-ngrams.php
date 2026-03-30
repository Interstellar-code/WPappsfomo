<?php

namespace Linksy\Inc\Helpers;

/**
 * Hadndle incoming request.
 *
 * @since      1.0.1
 * @package    Linksy
 * @subpackage Linksy\admin
 * @author     Gbenga Medunoye <laxusgooee@gmail.com>
 */

/**
 * String class.
 */
class NGrams {
    public $sentence;

    function __construct($sentence) {
        $this->sentence = str_ireplace([', ', ': ', '; ', ' – ', ' (', ') ', ' {', '} '], " ", $sentence);
    }

    public function bigrams(){
        $ngrams = array();
        $words = explode(" ", $this->sentence);
        
        $len = count($words);

        for($i=0;$i+1<$len;$i++){
            $ngrams[$i]=$words[$i]." ".$words[$i+1];
        }

        return $ngrams;
    }

    public function trigrams(){
        $ngrams = array();
        $words = explode(" ", $this->sentence);
        
        $len = count($words);

        for($i=0;$i+2<$len;$i++){
            $ngrams[$i]=$words[$i]." ".$words[$i+1]." ".$words[$i+2];
        }
        return $ngrams;
    }

    public function grams($n=3){
        $ngrams = array();
        $words = explode(" ", $this->sentence);
        
        $len = count($words);
    
        for($i=0;$i+$n<=$len;$i++){
            $string="";
            for($j=0;$j<$n;$j++){ 
                $string = (empty($string)? "" : $string." ").$words[$j+$i]; 
            }
            $ngram[$i]=$string;
        }
        return $ngram;
    }
}