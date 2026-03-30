<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce Settings Tab
 */

class WC_Settings_Tab_RhTools {

    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public static function init() {
        add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
        add_action( 'woocommerce_settings_tabs_rhtools', __CLASS__ . '::settings_tab' );
        add_action( 'woocommerce_update_options_rhtools', __CLASS__ . '::update_settings' );
    }

    /**
     * Add a new settings tab to the WooCommerce settings tabs array.
     */
    public static function add_settings_tab( $settings_tabs ) {
        $settings_tabs['rhtools'] = __( 'ReHub Tools', 'rh-wctools' );
        return $settings_tabs;
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     */
    public static function settings_tab() {
        woocommerce_admin_fields( self::get_settings() );
    }

    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     */
    public static function update_settings() {
        woocommerce_update_options( self::get_settings() );
    }

    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {

		$settings = array();
		//Product Tabs setting
		$settings[] = array(
			'title' => __( 'Product Tabs', 'rh-wctools' ),
			'type' => 'title',
			'id' => 'rhwct_options',
		);
		$settings[] = array(
			'title' => __( 'Hide Description tab', 'rh-wctools' ),
			'desc' => __( 'The option disables Description tab on Product page', 'rh-wctools' ),
			'id' => 'rhwct_hide_desc_tab',
			'default' => 'no',
			'type' => 'checkbox',
		);
		$settings[] = array(
			'title'    => __( 'Names of the Custom tabs', 'rh-wctools' ),
			'id'       => 'rhwct_tab_product_titles',
			'desc'     => __( 'List of the Tabs names separated by semicolons.', 'rh-wctools' ),
			'type'     => 'text',
		);
		$settings[] = array(
			'title'    => __( 'Contents of the Custom tabs', 'rh-wctools' ),
			'id'       => 'rhwct_tab_product_contents',
			'desc'     => __( 'List of the Tabs contents separated by EOT symbols and semicolons like EOT; You can use HTML and shortcodes inside.', 'rh-wctools' ),
			'type'     => 'textarea',
		);
		$settings[] = array(
			'title'    => __( 'Orders of the Custom tabs', 'rh-wctools' ),
			'id'       => 'rhwct_tab_product_orders',
			'desc'     => __( 'List of the Tabs priorities separated by semicolons.', 'rh-wctools' ),
			'type'     => 'text',
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'rhwct_tools',
		);
		//Related Products settings
		$settings[] = array(
			'title' => __( 'Related products', 'rh-wctools' ),
			'type' => 'title',
			'desc' => '',
			'id' => 'rhwct_options',
		);
		$settings[] = array(
			'title' => __( 'Hide Related Products', 'rh-wctools' ),
			'desc' => __( 'The option disables Related Products section on Product page', 'rh-wctools' ),
			'id' => 'rhwct_hide_related_products',
			'default' => 'no',
			'type' => 'checkbox',
		);
		$settings[] = array(
			'title'    => __( 'Exlude products', 'rh-wctools' ),
			'desc'     => __( 'List product IDs separated by commas where you want to enable Related Products.', 'rh-wctools' ),
			'id'       => 'rhwct_excl_related_products',
			'default'  => '',
			'type'     => 'text',
			//'desc_tip' => true,
		);
		$settings[] = array(
			'title'    => __( 'Include products', 'rh-wctools' ),
			'desc'     => __( 'List product IDs separated by commas where you want to disable Related Products.', 'rh-wctools' ),
			'id'       => 'rhwct_incl_related_products',
			'default'  => '',
			'type'     => 'text',
			//'desc_tip' => true,
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id' => 'rhwct_tools',
		);

        return apply_filters( 'wc_settings_rhtools', $settings );
    }

}

WC_Settings_Tab_RhTools::init();