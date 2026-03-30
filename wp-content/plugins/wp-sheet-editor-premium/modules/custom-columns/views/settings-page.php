<?php
/**
 * Template used for the settings page.
 */
defined( 'ABSPATH' ) || exit;
?>

<div class="remodal-bg custom-columns-page-content custom-columns-page-content-alpine " id="vgse-wrapper"  x-data="vgseCustomColumnForm">
	<div class="">
		<div class="">
			<h2 class="hidden"><?php esc_html_e( 'Sheet Editor', 'vg_sheet_editor' ); ?></h2>
			<a href="https://wpsheeteditor.com/?utm_source=wp-admin&utm_medium=custom-columns-logo" target="_blank"><img src="<?php echo esc_url( VGSE()->logo_url ); ?>" class="vg-logo"></a>
		</div>
		<h2><?php esc_html_e( 'Add New Columns to the Spreadsheet', 'vg_sheet_editor' ); ?></h2>

		<p><?php esc_html_e( 'You can enter an existing field key from your database to edit a compatible field that wasn\'t recognized by our plugin automatically, or you can enter new meta keys for private columns.', 'vg_sheet_editor' ); ?></p>
		<p><a class="button help-button" href="<?php echo esc_url( VGSE()->get_support_links( 'contact_us', 'url', 'custom-columns-help' ) ); ?>" target="_blank" ><i class="fa fa-envelope"></i> <?php esc_html_e( 'Need help? Contact us', 'vg_sheet_editor' ); ?></a></p>

		<?php do_action( 'vg_sheet_editor/custom_columns/settings_page/before_form' ); ?>
		<form class="repeater custom-columns-form" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" @submit.prevent="save">
			<input type="hidden" name="action" value="vgse_save_columns">
			<div class="columns-wrapper">
				<template x-for="(column, columnIndex) in columns">
					<div class="column-wrapper">
						<h3 @click.prevent="visibleColumns[columnIndex] = !visibleColumns[columnIndex]" x-show="column.title"><span x-text="column.title"></span> <i class="fa fa-chevron-circle-down"></i></h3>
						<div class="column-fields-wrapper" x-show="visibleColumns[columnIndex]">
							<?php do_action( 'vg_sheet_editor/custom_columns/settings_page/before_template_fields' ); ?>
							<div class="field-container field-container-name">
								<label><?php esc_html_e( 'Column name', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'The column name displayed in the spreadsheet', 'vg_sheet_editor' ); ?>">( ? )</a></label>
								<input type="text" name="name" x-model="column.title" class="name-field"/>
							</div>
							<div class="field-container field-container-key">
								<label><?php esc_html_e( 'Database field key', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'For example, the key of the meta field. We only accept letters, numbers, underscores, and hyphens; and we require a minimum of 4 characters', 'vg_sheet_editor' ); ?>">( ? )</a></label>
								<input type="text" @input="$el.value = $el.value.replace(/[^a-zA-Z0-9_\-]/g, '')" name="key" x-model="column.key" class="key-field" pattern="[a-zA-Z0-9_=\-]{2,}"/>
							</div>
							<div class="field-container field-container-data-source">
								<label><?php esc_html_e( 'Where is the field stored in the database', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Select the kind of information used in the cells of this column.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
								<select name="data_source" x-model="column.data_type">
									<option value="post_data" :selected="column.data_type ==='post_data'"><?php esc_html_e( 'Post data', 'vg_sheet_editor' ); ?></option>
									<option value="post_meta" :selected="column.data_type ==='post_meta'"><?php esc_html_e( 'Meta data (i.e. metaboxes)', 'vg_sheet_editor' ); ?></option>
									<option value="post_terms":selected="column.data_type === 'post_terms'"><?php esc_html_e( 'Post terms (i.e. categories)', 'vg_sheet_editor' ); ?></option>
								</select>
							</div>
							
							<div class="field-container field-container-post-types">
								<label><?php esc_html_e( 'Spreadsheet(s)', 'vg_sheet_editor' ); ?></label>

								<?php
								$post_types = VGSE()->helpers->get_allowed_post_types();
								?>
								
								<select name="post_types[]" x-init="initSelect2($el)" multiple class="select2" :data-full-model-path="'columns['+columnIndex+'].provider'" x-model="column.provider">
									<?php
									if ( ! empty( $post_types ) ) {
										foreach ( $post_types as $key => $post_type_name ) {
											if ( is_numeric( $key ) ) {
												$key = $post_type_name;
											}
											?>
											<option value="<?php echo esc_attr( $key ); ?>">
												<?php echo esc_html( $post_type_name ); ?>
											</option>
											<?php
										}
									}
									?>
								</select>
							</div>
							<div class="field-container field-container-is-locked">
							<label><input type="checkbox" x-model="column.is_read_only" name="is_read_only" value="yes"/>  <?php esc_html_e( 'Is read only?', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'If you enable this option, this column will be read-only.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
							</div>
							<?php do_action( 'vg_sheet_editor/custom_columns/settings_page/after_column_fields' ); ?>
							<button @click.prevent="removeColumn(columnIndex)" class="button remove-column"><?php esc_attr_e( 'Remove Column', 'vg_sheet_editor' ); ?></button> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'The field data will still exist in the database, and our plugin might recognize the field in the database and show a generic column for it. You\'re just removing the custom column configuration here, not the field values.', 'vg_sheet_editor' ); ?>">( ? )</a>
						</div>

					</div>
				</template>
			</div>
			<?php do_action( 'vg_sheet_editor/custom_columns/settings_page/before_form_submit' ); ?>
			<input @click.prevent="addColumn" type="button" value="<?php esc_attr_e( 'Add new column', 'vg_sheet_editor' ); ?>" class="button add-column"/>
			<button class="button button-primary button-primary save" type="submit"><?php esc_html_e( 'Save', 'vg_sheet_editor' ); ?></button>

		</form>

		<?php do_action( 'vg_sheet_editor/custom_columns/settings_page/after_content' ); ?>
	</div>
</div>
			<?php
