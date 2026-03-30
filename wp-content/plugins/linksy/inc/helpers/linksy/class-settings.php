<?php

namespace Linksy\Inc\Helpers\Linksy;


use Linksy\Inc\Helpers\Config;
use Linksy\Inc\Helpers\Database\Database;

class Settings extends Config {
    const TYPES_TO_SKIP = [
        'WORDS'      => 'words',
        'PARAGRAPHS' => 'paragraphs',
        'SENTENCES'  => 'sentences'
    ];

    const SUPPORTED_KEYWORDS_PROVIDER = [
        'linksy_focus_keyword',
        'rank_math_focus_keyword',
        '_yoast_wpseo_focuskw',
        '_seopress_analysis_target_kw',
        //'aioseo_posts', // another table
    ];

    public static function type($value) {
        if (is_array($value)) {
            $type = 'array';
        } else if (is_object($value)) {
            $type = 'object';
        } else if (is_bool($value)) {
            $type = 'boolean';
        } else if (is_numeric($value)) {
            $type = 'number';
        } else {
            $type = 'string';
        }

        return $type;
    }

    public static function value($value, $type = null) {
        $is_empty = is_null($value) || $value == "";

        if (is_null($type)) {
            $type = self::type($value);
        }

        switch ($type) {
            case 'number':
                $value = $is_empty? -1 : (is_numeric($value) ? $value + 0 : -1);
                break;
            case 'boolean':
                $value = (bool)$value;
                break;
            case 'array':
                $value =  $is_empty? [] : json_decode($value);
                break;
            case 'string':
                $value = "".$value;
                break;
            default:
                $value =  json_decode($value);
                break;
        }

        return $value;
    }

    public static function set( $key, $value = null, $reset = false ) {
        $type = self::type($value);

        if ($type == 'array' || $type == 'object') {
            $value = json_encode($value);
        }

        if ($type == 'boolean') {
            $value =  (int)$value;
        }
        
		$saved = Database::table("linksy_settings")->where('setting_key', $key)->one();

		$db = Database::table("linksy_settings");
        if ( $saved) {
            $db->where('setting_key', $key)->set('setting_value', $value)->set('setting_value_type', $type)->update();
        } else {
            $db->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_value_type' => $type
            ]);
        }

		return true;
	}

	public static function get( $key, $defaults = null ) {
		$query = Database::table("linksy_settings")->where('setting_key', $key)->one();

        if ($query && !is_null($query->setting_value) && $query->setting_value != "") {
            return self::value($query->setting_value, $query->setting_value_type);
        }

        return $defaults;
	}

    public static function many( $filter = [] ) {
        $res = [];
        foreach ($filter as $f) {
            $res[$f] = null;
        }

        $query = Database::table("linksy_settings")->select(['setting_key', 'setting_value', 'setting_value_type']);
        if (!empty($filter)) {
            $query->whereIn('setting_key', $filter);
        }
        $settings = $query->get();

        foreach ($settings as $setting) {
            $res[$setting->setting_key] = self::value($setting->setting_value, $setting->setting_value_type);
        }

		return $res;
	}
}