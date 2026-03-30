<?php
/**
 * Template used for the post type setup page.
 */
defined( 'ABSPATH' ) || exit;
$nonce = wp_create_nonce('bep-nonce');
?>
<div class="remodal-bg quick-setup-page-content post-type-setup-wizard" id="vgse-wrapper">
	<div class="">
		<div class="">
			<h2 class="hidden"><?php esc_html_e('Sheet Editor', 'vg_sheet_editor' ); ?></h2>
			<a href="https://wpsheeteditor.com/?utm_source=wp-admin&utm_medium=spreadsheet-setup-logo" target="_blank"><img src="<?php echo esc_url(VGSE()->logo_url); ?>" class="vg-logo"></a>
		</div>
		<h2><?php esc_html_e('Set up Spreadsheet', 'vg_sheet_editor' ); ?></h2>
		<div class="setup-screen">
			<?php do_action('vg_sheet_editor/post_type_setup_page/quick_setup_screen/before_content'); ?>

			<p><?php esc_html_e('You can start using the spreadsheet editor in just 5 minutes. Please follow these steps.', 'vg_sheet_editor' ); ?></p>

			<?php
			ob_start();
			require VGSE_DIR . '/views/post-types-form.php';
			$post_types_form = str_replace(array(
				'type="checkbox"',
				'name="append" value="no"',
				'button button-primary hidden save-trigger',
				'<form',
					), array(
				'type="radio"',
				'name="append" value="yes"',
				'button button-primary save-trigger',
				'<form data-callback="vgsePostTypeSetupPostTypesSaved" data-custom-post-types="' . $custom_post_types . '" data-confirm-delete="' . esc_html__('Are you sure you want to delete the post type? The posts of the post type will remain in the database', 'vg_sheet_editor' ) . '" ',
					), ob_get_clean());
			$steps = array();

			$steps['enable_post_types'] = '<p>' . esc_html__('Select the information that you want to edit with the spreadsheet editor.', 'vg_sheet_editor' ) . '</p>';

			if (class_exists('WP_Sheet_Editor_CPTs')) {
				$steps['enable_post_types'] .= '<form class="inline-add" action="' . esc_url(admin_url('admin-ajax.php')) . '" method="POST" data-callback="vgsePostTypeSaved"><input type="hidden" name="action" value="vgse_register_post_type" /><input type="hidden" name="nonce" value="' . $nonce . '" /><input type="text" class="vgse-new-post-type" name="post_type" placeholder="' . esc_html__('Add new post type', 'vg_sheet_editor' ) . '"/><button class="button"><i class="fa fa-plus"></i></button></form> ';
			}
			$steps['enable_post_types'] .= $post_types_form;

			// Columns visibility section. Blank content because the columns manager html will load with ajax
			if (class_exists('WP_Sheet_Editor_Columns_Visibility')) {
				$steps['setup_columns'] = '';
			}


			$steps = apply_filters('vg_sheet_editor/post_type_setup_page/setup_steps', $steps);

			if (!empty($steps)) {
				echo '<ol class="steps">';
				foreach ($steps as $key => $step_content) {
					?>
					<li class="<?php echo esc_attr($key); ?>"><?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $step_content; // WPCS: XSS ok. ?></li>		
					<?php
				}

				echo '</ol>';
			}
			?>

			<?php do_action('vg_sheet_editor/post_type_setup_page/quick_setup_screen/after_content'); ?>
		</div>

		<?php do_action('vg_sheet_editor/post_type_setup_page/after_content'); ?>
	</div>
</div>
			<?php
		