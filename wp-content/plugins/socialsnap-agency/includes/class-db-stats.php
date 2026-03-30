<?php
/**
 * Statistics class. Holds statistics for sharing, followers, likes, click to tweets.
 *
 * @package    Social Snap
 * @author     Social Snap
 * @since      1.0.0
 * @license    GPL-3.0+
 * @copyright  Copyright (c) 2019, Social Snap LLC
 */
class SocialSnap_Stats extends SocialSnap_DB {

	/**
	 * Primary class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'socialsnap_stats';
		$this->primary_key = 'id';
	}

	/**
	 * Get table columns.
	 *
	 * @since 1.0.0
	 */
	public function get_columns() {

		return array(
			'id'         => '%d',
			'count'      => '%d',
			'post_id'    => '%d',
			'post_type'  => '%s',
			'date'       => '%s',
			'network'    => '%s',
			'type'       => '%s',
			'location'   => '%s',
			'url'        => '%s',
			'ip_address' => '%s',
		);
	}

	/**
	 * Default column values.
	 *
	 * @since 1.0.0
	 */
	public function get_column_defaults() {

		return array(
			'date' => gmdate( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Get stats from database
	 *
	 * @since 1.0.0
	 */
	public function get_stats( $args = array(), $count = false ) {

		global $wpdb;

		$defaults = array(
			'number'     => 30,
			'offset'     => 0,
			'id'         => 0,
			'post_id'    => 0,
			'post_type'  => '',
			'date'       => '',
			'network'    => '',
			'type'       => '',
			'count'      => '',
			'location'   => '',
			'url'        => '',
			'ip_address' => '',
			'date_from'  => '',
			'date_to'    => gmdate( 'Y-m-d H:i:s' ),
			'orderby'    => 'date',
			'order'      => 'DESC',
			'search'     => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = '';

		// Allowed int arg items
		$keys = array( 'id', 'post_id', 'count' );
		foreach ( $keys as $key ) {

			if ( ! empty( $args[ $key ] ) ) {
				if ( is_array( $args[ $key ] ) ) {
					$ids = implode( ',', array_map( 'intval', $args[ $key ] ) );
				} else {
					$ids = intval( $args[ $key ] );
				}
				$where .= empty( $where ) ? 'WHERE' : 'AND';
				$where .= " `{$key}` IN( {$ids} ) ";
			}
		}

		// Allowed string arg items
		$keys = array( 'url', 'network', 'location', 'type', 'ip_address', 'post_type', 'date' );
		foreach ( $keys as $key ) {

			if ( '' !== $args[ $key ] ) {
				$where .= empty( $where ) ? 'WHERE' : 'AND';
				$where .= " `{$key}` = '" . esc_sql( $args[ $key ] ) . "' ";
			}
		}

		// Time interval
		if ( '' !== $args['date_from'] && '' !== $args['date_to'] ) {
			$where .= empty( $where ) ? 'WHERE' : 'AND';
			$where .= " `date` > '" . esc_sql( $args['date_from'] ) . "' ";
			$where .= 'AND';
			$where .= " `date` < '" . esc_sql( $args['date_to'] ) . "' ";
		}

		// Orderby
		$orderby = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? $this->primary_key : $args['orderby'];

		// Order
		if ( 'ASC' === strtoupper( $args['order'] ) ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}

		// Check for primed cache
		if ( true === $count ) {
			$cache_key = md5( 'socialsnap_stats_count' . serialize( $args ) );
		} else {
			$cache_key = md5( 'socialsnap_stats_' . serialize( $args ) );
		}
		$results = wp_cache_get( $cache_key, 'socialsnap_stats' );

		if ( false === $results ) {

			if ( true === $count ) {

				if ( false === strpos( $args['type'], 'api' ) ) {
					$results = absint( $wpdb->get_var( "SELECT COUNT({$this->primary_key}) FROM {$this->table_name} {$where};" ) );
				} else {

					$results = 0;
					$records = $wpdb->get_results(
						"SELECT * FROM {$this->table_name} {$where}"
					);

					if ( is_array( $records ) && ! empty( $records ) ) {
						foreach ( $records as $record ) {
							if ( isset( $record->count ) ) {
								$results += $record->count;
							}
						}
					}
				}
			} else {

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d, %d;",
						absint( $args['offset'] ),
						absint( $args['number'] )
					)
				);
			}

			wp_cache_set( $cache_key, $results, 'socialsnap_stats', 3600 );
		}

		return $results;
	}

	/**
	 * Get top networks for selected type.
	 *
	 * @since 1.0.0
	 */
	public function get_top_networks( $args = array(), $number = 4 ) {

		global $wpdb;

		$defaults = array(
			'type'   => 'share',
			'number' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$networks = array();

		$result = $this->get_stats( $args );

		if ( is_array( $result ) && ! empty( $result ) ) {

			foreach ( $result as $row ) {

				if ( 'share_api' == $args['type'] ) {
					if ( isset( $networks[ $row->network ] ) ) {
						$networks[ $row->network ] += $row->count;
					} else {
						$networks[ $row->network ] = $row->count;
					}
				} else {
					if ( isset( $networks[ $row->network ] ) ) {
						$networks[ $row->network ]++;
					} else {
						$networks[ $row->network ] = 1;
					}
				}
			}
		}

		arsort( $networks );
		$return = array_slice( $networks, 0, $number, true );

		if ( $number ) {
			$return['other'] = array_sum( array_values( array_slice( $networks, $number, null, true ) ) );
		}

		return $return;
	}

	/**
	 * Get top content entries.
	 *
	 * @since 1.0.0
	 */
	public function get_top_content( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'     => 0,
			'offset'     => 0,
			'id'         => 0,
			'post_id'    => 0,
			'post_count' => 10,
			'post_type'  => '',
			'date'       => '',
			'network'    => '',
			'type'       => '',
			'count'      => '',
			'location'   => '',
			'url'        => '',
			'ip_address' => '',
			'date_from'  => '',
			'date_to'    => gmdate( 'Y-m-d H:i:s' ),
			'orderby'    => 'date',
			'order'      => 'DESC',
			'search'     => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$return = array();

		$result = $this->get_stats( $args );

		if ( is_array( $result ) && ! empty( $result ) ) {
			foreach ( $result as $row ) {
				if ( isset( $return[ $row->post_id ] ) ) {
					$return[ $row->post_id ]++;
				} else {
					$return[ $row->post_id ] = 1;
				}
			}
		}

		arsort( $return );

		return array_slice( $return, 0, $args['post_count'], true );
	}

	/**
	 * Create statistics database table.
	 *
	 * @since 1.0.0
	 */
	public function create_table() {

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			count bigint(20) NOT NULL,
			date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			network varchar(20) NOT NULL,
			post_type varchar(20) NOT NULL,
			type varchar(10) NOT NULL,
			location varchar(20) NOT NULL,
			ip_address varchar(45) NOT NULL,
			url varchar(2083) NOT NULL,
			UNIQUE KEY id (id)
		) {$charset_collate};";

		$db = dbDelta( $sql );
	}
}
