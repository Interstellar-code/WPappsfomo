<?php
/*
Plugin Name: RH WooCommerce Tools
Plugin URI: https://1.envato.market/YkOam
Description: Allow managing WooCommerce plugin with help of Rehub theme tools.
Version: 1.1.6
Author: Wpsoul.com
Author URI: http://wpsoul.com
Text Domain: rh-wctools
Domain Path: /lang/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* Define constants */
if( !defined( 'RHWCT_SLUG' ) ){
	define( 'RHWCT_SLUG', 'rh-woo-tools' );
}
if( !defined( 'RHWCT_FILE' ) ){
	define( 'RHWCT_FILE', __FILE__ );
}
if( !defined( 'RHWCT_REPO' ) ){
	define( 'RHWCT_REPO', 'http://wpsoul.net/plugins/' );
}

add_action( 'plugins_loaded', 'rh_woo_tools_init' );

function rh_woo_tools_init(){
	if(class_exists('WooCommerce')) {
		require plugin_dir_path( __FILE__ ) . 'includes/wc-settings-tab-class.php';
		add_action( 'admin_print_styles-woocommerce_page_wc-settings', 'rhwct_admin_styles' );
		add_filter( 'woocommerce_debug_tools', 'rhwct_add_button_wc_tools' );
		add_filter( 'woocommerce_get_related_product_cat_terms', 'rhwct_switch_related_products', 90, 2 );
		add_filter( 'woocommerce_get_related_product_tag_terms', 'rhwct_switch_related_products', 90, 2 );
		add_filter( 'woocommerce_product_tabs', 'rhwct_disable_desc_tab' );
		add_filter( 'woocommerce_product_tabs', 'rhwct_product_custom_tabs' );
		add_filter( 'wc_product_has_unique_sku', '__return_false' ); 
	    rhwct_check_update();
	} else {
	  add_action('admin_notices', 'rhwct_admin_notice');
	}	
}

/* Hide old prodcts with duplicated SKU */
function rhwct_hide_duplicate_sku(){
 	$args = array(
        'posts_per_page'   => 2,
        'post_type'        => 'product',
        'post_status'      => 'publish',
        'order'      => 'ASC',
        'meta_query' => array(
            'relation' => 'AND',
            'sku_clause' => array(
                'key'     => '_sku',
                'value'   => '',
                'compare' => '!=',
            ),
            'price_clause' => array(
                'key'     => '_price',
                'type'    => 'NUMERIC',                
                'value'   => '',
                'compare' => '!=',
            ),            
        ),
        'orderby' => array( 
            'sku_clause' => 'ASC',
            'price_clause' => 'ASC',
        ),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'exclude-from-catalog',
                'operator' => 'NOT IN',
            )
        )		
	);
	$pageposts = get_posts($args);
	$sku2 = '';
 	foreach($pageposts as $pagepost){
		$pid = $pagepost->ID;
		$sku1 = get_post_meta($pid,'_sku',true);
		//wp_remove_object_terms($pid, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility'); //restore product visibility
		if( $sku1 != $sku2 ){
			$sku2 = $sku1;
		} else {
			wp_set_post_terms($pid, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility');
		}
	}
	
	echo '<div class="updated"><p>' . __( 'Completed!', '' ) . '</p></div>';
}

/* Add 'Hide' button to WooCommerce -> Status -> Tools */
function rhwct_add_button_wc_tools($tools){
	$tools['hide_sku_duplicate'] = array(
		'name'    => __( 'Hide duplicated products', 'rh-wctools' ),
		'button'  => __( 'Hide', 'rh-wctools' ),
		'desc'    => __( 'This tool will hide products which have identical SKU after import via WPAI.', 'rh-wctools' ),
		'callback' => 'rhwct_hide_duplicate_sku'
	);
	return $tools;
}

/* Trigger for Related Products section */
function rhwct_switch_related_products($terms, $pid){
	$incl = WC_Admin_Settings::get_option('rhwct_incl_related_products');
	$excl = WC_Admin_Settings::get_option('rhwct_excl_related_products');
	$sett = WC_Admin_Settings::get_option('rhwct_hide_related_products');
	$incl_arr = explode(',',$incl); 
	$excl_arr = explode(',',$excl);
	if('yes' === $sett){
		if(!empty($excl_arr) && in_array($pid, $excl_arr)){
			$terms = $terms;
		}else{
			$terms = array();
		}
	}
	if(!empty($incl_arr) && in_array($pid, $incl_arr)){
		$terms = array();
	}
	return $terms;
}

/* Removes Description tab in Tabs array */
function rhwct_disable_desc_tab($tabs) {
	$desc_tab = WC_Admin_Settings::get_option('rhwct_hide_desc_tab');
	if('yes' === $desc_tab) {
		unset($tabs['description']);
	}
	return $tabs;
}

/* Adds Custom Tabs the Product Page (see WC -> Settings -> ReHub Tools tab) */
function rhwct_product_custom_tabs( $tabs ) {

	$tab_titles = WC_Admin_Settings::get_option('rhwct_tab_product_titles');
	
	if( empty( $tab_titles ) )
		return $tabs;

	$tab_titles = array_map( 'trim', explode(';', $tab_titles) );
	$tab_orders = WC_Admin_Settings::get_option('rhwct_tab_product_orders');
	
	if( empty( $tab_orders ) )
		return $tabs;
	
	$tab_orders = array_map( 'trim', explode(';', $tab_orders) );
	$tab_contents = WC_Admin_Settings::get_option('rhwct_tab_product_contents');
	
	if( empty( $tab_contents ) )
		return $tabs;
	
	$tab_contents = array_map( 'trim', explode('EOT;', $tab_contents) );

	foreach( $tab_titles as $key => $tab_title ){
		$tabs['rhwct_tab_'.$key] = array(
			'title' => $tab_title,
			'priority' => $tab_orders[$key],
			'content' => nl2br( $tab_contents[$key] ),
			'callback'  => 'rhwct_product_custom_tab_content'
		);
	}

	return $tabs;
}

/* Callback function for Content of the Castom Tabs */
function rhwct_product_custom_tab_content( $key, $tab ){
	echo do_shortcode($tab['content']);
}

/* Update plugin */
function rhwct_check_update(){
	require plugin_dir_path( __FILE__ ) . 'includes/class-update-checker.php';
}

/* If the WooCommerce plugin is not installed show noutification */
function rhwct_admin_notice(){ ?>
	<div class="notice notice-warning">
		<p><?php _e( 'Sorry, but RH WooCommerce Tools works only with WooCommerce plugin.', 'rh-wctools' ); ?></p>
	</div>
	<?php
}

function rhwct_admin_styles() {
	$styles = '<style type="text/css">span.description{display:block;margin-top:5px;}.forminp-textarea p{margin-bottom:5px !important;}</style>';
	echo $styles;
}

add_action('wp_ajax_rhwct_hide_duplicate_sku_process','rhwct_hide_duplicate_sku_process');
function rhwct_hide_duplicate_sku_process(){
	check_ajax_referer( 'rh-woo', 'security' );
	
	if(!isset($_REQUEST['paged'])){$_REQUEST['paged']=1;}
	
   	$args = array(
        'posts_per_page'   => 100,
        'post_type'        => 'product',
        'post_status'      => 'publish',
		'paged'   => $_REQUEST['paged'],
        'order'      => 'ASC',
		'meta_key' => '_sku',
        'meta_query' => array(
            array(
                'key'     => '_price',
				'compare' => 'EXISTS',
            ),            
        ),
        'orderby' => 'meta_value',
	);
	
	$pageposts = new WP_Query( $args );
	$pricearray = array();
	if ( $pageposts->have_posts() ) {
		while ($pageposts->have_posts() ) {
			$pageposts->the_post();
			global $post;
			$pid = $post->ID;
			$sku = get_post_meta($pid,'_sku',true);
          	$price = get_post_meta($pid,'_price',true);
          	$pricearray[$sku][$pid] = $price; 
		}
		wp_reset_query();
	}
    foreach($pricearray as $skukey){
      	$minprice = min($skukey);
      	$minpricekey = array_search($minprice, $skukey);
      	foreach($skukey as $pricekey=>$pricevalue){
          	if($pricekey==$minpricekey){
              	wp_remove_object_terms($pricekey, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility');
          	}else{
              	wp_set_post_terms($pricekey, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility');
          	}
      	}
    }

	if(isset($_REQUEST['time']) && $_REQUEST['time']<=0){$_REQUEST['time']=(100/$pageposts->max_num_pages)/100;}
	echo json_encode(array("page_num"=>$pageposts->max_num_pages,"post_count"=>$pageposts->post_count,"total_count"=>$pageposts->found_posts,"paged"=>$_REQUEST['paged']+1,"time"=>$_REQUEST['time']+(100/$pageposts->max_num_pages)/100));
	exit;
}

add_action('admin_footer', 'trigger_loading_for_hide_sku');
function trigger_loading_for_hide_sku(){
	if(isset($_REQUEST['page']) && $_REQUEST['page']=='wc-status'){
	$ajax_nonce = wp_create_nonce( "rh-woo" );	
	?>
	<div id='progress' style='width:200px;display:none;'></div>
	<p class="finished" style='display:none;'>Process is finished.</p>
	
	<script src='<?php echo plugins_url('js/progressbar.js',__FILE__); ?>'></script>
	<script>
	jQuery(document).ready(function(){
	jQuery('<a href="#" class="trigger">trigger</a>').insertAfter('.button.hide_sku_duplicate');
	jQuery('#progress').insertAfter('.button.hide_sku_duplicate');
	jQuery('.finished').insertAfter('.button.hide_sku_duplicate');
	});
	
	var bar = new ProgressBar.Line('#progress', {
  strokeWidth: 4,
  easing: 'easeInOut',
  duration: 1400,
  color: '#7aa93c',
  trailColor: '#eee',
  trailWidth: 1,
  svgStyle: {width: '100%', height: '100%'}, 
  from: {color: '#7aa93c'},
  to: {color: '#ED6A5A'}
  
});
	
	jQuery(document).ready(function(){
		
	jQuery('.button.hide_sku_duplicate').click(function(){
		jQuery('.finished').hide();
		jQuery('#progress').show();
		jQuery.ajax({
		'url':ajaxurl+'?action=rhwct_hide_duplicate_sku_process&security=<?php echo $ajax_nonce; ?>',
		'success':function(data){
			d = jQuery.parseJSON(data);
			
			if(d.post_count == 100){
				jQuery('.trigger').attr({'data-count':d.paged});
				jQuery('.trigger').attr({'data-time':d.time});
				jQuery('.trigger').trigger('click');
                bar.animate(d.time); 				
			 } else {
				jQuery('#progress').hide(); 								
				jQuery('.finished').show();
				bar.animate('0');
			 }
		    }
		});		
		return false;
	});
	
	
	jQuery('.trigger').click(function(){
		 jQuery.ajax({				
				'url':ajaxurl+'?action=rhwct_hide_duplicate_sku_process&security=<?php echo $ajax_nonce; ?>&paged='+jQuery(this).attr('data-count')+'&time='+jQuery(this).attr('data-time'),				'success':function(data){
						d = jQuery.parseJSON(data);		
						if(d.post_count == 100){
						jQuery('.trigger').attr({'data-count':d.paged});
						jQuery('.trigger').attr({'data-time':d.time});
						jQuery('.trigger').trigger('click');
						  bar.animate(d.time); 
						} else {
						jQuery('#progress').hide(); 
						jQuery('.finished').show();						
				        bar.animate('0');
						}
		       }
		     });
	});
		
	});
	</script>
	
	<style>
	 
	 .trigger{display:none;}
	 .progress {
                height: 30px;				
            }
			
     .progress > svg {               
                display: block;				
            }
	</style>
	
<?php	
	}
}