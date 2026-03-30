<?php

namespace Linksy\Inc\Helpers\Linksy;


use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Database\Database;

class Semantic {

    public static function scoreToTag($score) {
        if ($score == null) {
            return 'no keyword';
        }
    
        $score = round($score * 100);
    
        if ($score  < 30) {
            return 'poor';
        } else if ($score  >= 30 && $score < 50) {
            return 'average';
        } else if ($score >= 50 && $score < 70) {
            return 'good';
        } else {
            return 'great';
        }
    }

    public static  function tagToScore($tag) {
        $score = null;
        switch (strtolower($tag)) {
            case 'poor':
                $score = [0, 29];
                break;
            case 'average':
                $score = [30, 49];
                break;
            case 'good':
                $score = [50, 69];
                break;
            case 'great':
                $score = [70, 100];
                break;
        }
    
        return $score;
    }
}