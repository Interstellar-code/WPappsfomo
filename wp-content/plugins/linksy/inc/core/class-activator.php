<?php

namespace Linksy\Inc\Core;

/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @link       http://laxusgee.com
 * @since      1.0.0
 *
 * @author     Linksy
 **/
class Activator {

	/**
	 * Short Description.
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_tables();
		self::create_options();


		update_option(LINKSY_OPTION_PLUGIN_VERSION, LINKSY_PLUGIN_VERSION);
	}

	/**
	 * Set up the database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		$collate      = $wpdb->get_charset_collate();
		$table_schema = [
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linksy_posts_migrations (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				batch INT(20) NOT NULL,
				pointer DATETIME NOT NULL,
				processed INT(11) NOT NULL DEFAULT '0',
				failed INT(11) NOT NULL DEFAULT '0',
				date DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $collate;",
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linksy_posts_migrations_failed (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				batch INT(20) NOT NULL,
				post_id INT(20) NOT NULL,
				last_error varchar(255) NOT NULL,
				date DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $collate;",
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linksy_links (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id INT(20) NOT NULL,
				post_type text,
				post_title text,
				clean_url text,
				raw_url text,
				host text,
				anchor text,
				score DECIMAL(10,2) NULL DEFAULT NULL,
				to_post_id INT(20) NULL DEFAULT NULL,
				is_internal tinyint(1) DEFAULT '0',
				is_broken tinyint(1) DEFAULT '0',
				rel VARCHAR(255) DEFAULT NULL,
				meta text,
				date DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id),
				KEY post_id (post_id),
				KEY clean_url (clean_url(500))
			) $collate;",
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linksy_settings (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(255) DEFAULT NULL,
				description varchar(255) DEFAULT NULL,
				section varchar(255) DEFAULT NULL,
				setting_group varchar(255) DEFAULT NULL,
				setting_key varchar(255) NOT NULL UNIQUE,
				setting_value text DEFAULT NULL,
				setting_value_type varchar(255) DEFAULT NULL, # boolean, string, numeric, date, datetime, mixed
				date DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $collate;",
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linksy_keywords (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_id INT(20) NOT NULL,
				keyword text  DEFAULT NULL,
				score DECIMAL(10,2) NULL DEFAULT NULL,
				provider varchar(255) NOT NULL,
				date DATETIME DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) $collate;",
		];

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $table_schema as $table ) {
			dbDelta( $table );
		}
	}

	/**
	 * Create options.
	 */
	private static function create_options() {
		if(!get_option(LINKSY_OPTION_API_KEY)){
		    add_option(LINKSY_OPTION_API_KEY, null);
		}
	}
}