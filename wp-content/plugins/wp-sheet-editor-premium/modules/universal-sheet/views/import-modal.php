<?php defined( 'ABSPATH' ) || exit;

/**
 * @var string $post_type
 */
?>
<!--Save changes modal-->
<div class="remodal import-csv-modal remodal-medium remodal-draggable" data-remodal-id="import-csv-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

	<div class="modal-content" x-data="vgseImport">
		<?php do_action( 'vg_sheet_editor/import/before_form', $post_type ); ?>
		<?php
		$is_not_supported = apply_filters( 'vg_sheet_editor/import/is_not_supported', null, $post_type );
		if ( ! is_null( $is_not_supported ) ) {
			$message = ( is_string( $is_not_supported ) ) ? $is_not_supported : esc_html__( 'The import feature is not compatible with your website. Make sure WordPress and all the plugins and themes are up to date.', 'vg_sheet_editor' );
			?>

			<h3><?php esc_html_e( 'Import', 'vg_sheet_editor' ); ?></h3>
			<p><?php echo wp_kses_post( $message ); ?></p>
			<button data-remodal-action="confirm" class="remodal-cancel"><?php esc_html_e( 'Cancel', 'vg_sheet_editor' ); ?></button>

			<?php
		} else {
			?>
			<?php do_action( 'vg_sheet_editor/import/before_form', $post_type ); ?>
			<form x-show="!isFormSubmitted" class="import-csv-form vgse-modal-form " id="import-csv-form" action="<?php echo esc_url( admin_url( 'admin.php?page=vgse_import_page' ) ); ?>" method="POST" @submit.prevent="submit" :class="isQuickSetupMode ? 'quick-setup-mode' : ''">
				<ul>
					<li class="step" x-show="step === 1">
						<h3><?php esc_html_e( 'Import', 'vg_sheet_editor' ); ?></h3>
						<?php do_action( 'vg_sheet_editor/import/before_data_sources', $post_type ); ?>
						<div x-show="!isQuickSetupMode">
							<label><?php esc_html_e( 'Source', 'vg_sheet_editor' ); ?></label>								
							<select name="source" class="source" :class="'source-'+taskArgs.source" x-model="taskArgs.source">
								<template x-for="(label, key) in getImportSources">
									<option :value="key" x-text="label"></option>
								</template>
							</select>						
						</div>

						<template x-if="worksheets.length > 0">
							<div class="field-wrap">
								<label><?php esc_html_e( 'Select the worksheet to import', 'vg_sheet_editor' ); ?></label>
								<select name="worksheet" x-model="selectedWorksheet">
									<template x-for="sheet in worksheets">
										<option :value="sheet" x-text="sheet"></option>
									</template>
								</select>
							</div>
						</template>

						<template x-if="taskArgs.source === 'csv_upload'">
						<div class="data-input csv_upload">
							<label><?php esc_html_e( 'CSV/Excel file', 'vg_sheet_editor' ); ?> </label>
							<input type="file" x-model="taskArgs.data" name="local_file" class="data" id="vgse-import-local-file"  /> 
							<button type="button" class="button button-primary button-primario vgse-upload-csv-file next-step step-nav"  data-type="local" @click.prevent="goToNextStep()"><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
						</div>
						</template>
						<template x-if="taskArgs.source === 'csv_url'">
						<div class="data-input csv_url">
							<label><?php esc_html_e( 'File URL', 'vg_sheet_editor' ); ?> </label>								
							<input type="text" x-model="taskArgs.data" name="file_url" placeholder="File URL" class="data" />
							<button type="button" class="button button-primary button-primario vgse-upload-csv-file next-step step-nav" data-type="url" @click.prevent="goToNextStep()"><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
						</div>
						</template>
						<template x-if="taskArgs.source === 'paste'">
						<div class="data-input paste">
							<label><?php esc_html_e( 'Copy and Paste into the spreadsheet below', 'vg_sheet_editor' ); ?></label>
							<p><?php esc_html_e( 'This is not recommended for large amounts of data.', 'vg_sheet_editor' ); ?></p>								
							<div class="handsontable-paste"></div>
							<button type="button" class="button button-primary button-primario vgse-upload-csv-file next-step step-nav" data-type="json" @click.prevent="goToNextStep()"><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
						</div>
						</template>
						<template x-if="taskArgs.source === 'server_file'">
						<div class="data-input server_file">
							<label><?php esc_html_e( 'CSV/Excel file location', 'vg_sheet_editor' ); ?> <a href="" data-wpse-tooltip="right" aria-label="
													<?php
														/* translators: %s: Folder path where CSV files should be uploaded */
														echo esc_attr( sprintf( esc_html__( 'You must enter a file name in this field (not full path) and upload the file to the folder %s', 'vg_sheet_editor' ), preg_replace( '/^.+(\/wp-content.+)$/', '$1', WPSE_CSV_API_Obj()->imports_dir ) ) );
													?>
							">( ? )</a> </label>
							<p>
							<?php
							$parts = explode( basename( WP_CONTENT_DIR ), WPSE_CSV_API_Obj()->imports_dir );
							echo esc_html( '/' . basename( WP_CONTENT_DIR ) . end( $parts ) );
							?>
							<input type="text" x-model="taskArgs.data" name="server_file" class="data" /></p>							 
							<button type="button" class="button button-primary button-primario vgse-upload-csv-file next-step step-nav"  data-type="server_file" @click.prevent="goToNextStep()"><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
						</div> 
						</template>
						<?php do_action( 'vg_sheet_editor/import/after_data_sources_fields', $post_type ); ?>			
						<template x-if="fileUploadError">
							<div class="alert alert-red file-upload-error" x-text="fileUploadError"></div>
						</template>			

						<label class="" x-show="!isQuickSetupMode"><input type="checkbox" x-model="showAdvancedOptions" name="enable_advanced_source_options" class="toggle-advanced-options"> <?php esc_html_e( 'Show advanced options', 'vg_sheet_editor' ); ?></label>
						<div class="advanced-options" x-show="showAdvancedOptions && !isQuickSetupMode">
							<div class="field">
								<label><?php esc_html_e( 'Separator', 'vg_sheet_editor' ); ?></label><br>
								<input type="text" x-model="taskArgs.separator" name="separator" class="separator" value="," />
							</div>
							<div class="field">
								<label><input type="checkbox" x-model="taskArgs.auto_column_names" name="auto_column_names" class="auto_column_names" value="yes" /> <?php esc_html_e( 'Automatically add column names to the CSV file?', 'vg_sheet_editor' ); ?></label>								
							</div>
						</div>
						<?php if ( empty( VGSE()->options['enable_simple_mode'] ) ) { ?>
							<p x-show="!isQuickSetupMode">
							<?php
								/* translators: %s: Documentation link URL */
								printf( __( 'Tip. You can use the "export" tool to download a CSV and see the available columns and format.<br>You can read our <a href="%s" target="_blank">documentation here</a>.', 'vg_sheet_editor' ), VGSE()->get_site_link( 'https://wpsheeteditor.com/blog/?s=import', 'importer-documentation' ) );
							?>
								</p>
						<?php } ?>

						<?php do_action( 'vg_sheet_editor/import/after_data_sources', $post_type ); ?>
					</li>
					<li class="map-columns step" x-show="step === 2">
						<h3><?php esc_html_e( 'Select columns to import', 'vg_sheet_editor' ); ?></h3>
						<p class="one-column-detected-tip alert alert-blue" x-show="showOneColumnWarning">
						<?php
						/* translators: %s: FAQ link URL for fixing column detection issues */
						printf( __( 'Important. We only detected one column in the CSV file. If this is incorrect, follow <a href="%s" target="_blank">these steps</a> to fix it', 'vg_sheet_editor' ), 'https://wpsheeteditor.com/documentation/faq/#1572924330879-e05ed559-f740234' );
						?>
						</p>
						<p class="import-auto-map-notice" x-show="showAutoMapCompleteUi"><?php esc_html_e( 'We automatically detected all the columns.', 'vg_sheet_editor' ); ?><br/><button type="button" class="button  next-step step-nav" @click.prevent="goToNextStep()"><?php esc_html_e( 'Import all the columns', 'vg_sheet_editor' ); ?></button> <?php esc_html_e( 'or', 'vg_sheet_editor' ); ?> <button type="button" class="button import-map-select-columns" @click.prevent="showAutoMapCompleteUi = !showAutoMapCompleteUi"><?php esc_html_e( 'Select individual columns to import', 'vg_sheet_editor' ); ?></button></p>
						<?php if ( empty( VGSE()->options['enable_simple_mode'] ) ) { ?>
							<p x-show="!isQuickSetupMode"><?php esc_html_e( 'Tip. If you edited information from this site, you should import the columns edited and record_id. Don\'t import columns that weren\'t modified', 'vg_sheet_editor' ); ?></p>
						<?php } ?>
						<div class="column-mapping-ui" x-show="!showAutoMapCompleteUi">
							<p class="import-column-bulk-actions"><span class="csv-column-list-header"></span><span class="wp-column-list-header">
								<select x-model="columnMappingBulkAction">
									<option value=""><?php esc_html_e( 'Bulk actions', 'vg_sheet_editor' ); ?></option>
									<option value="unselect"><?php esc_html_e( 'Unselect all columns', 'vg_sheet_editor' ); ?></option>
									<option value="map"><?php esc_html_e( 'Auto map all columns', 'vg_sheet_editor' ); ?></option>
									<option value="show_unmapped_only"><?php esc_html_e( 'Only show columns pending selection', 'vg_sheet_editor' ); ?></option>
								</select>
							</span></p>
							<p class="import-column-list-headers"><span class="csv-column-list-header"><?php esc_html_e( 'CSV Column', 'vg_sheet_editor' ); ?></span><span class="wp-column-list-header"><?php esc_html_e( 'WordPress field', 'vg_sheet_editor' ); ?></span></p>
							<template x-for="(csvHeader, index) in csvHeaders">
								<div class="map-template" x-show="!hiddenColumnsFromMapping[csvHeader] && ( columnMappingBulkAction !== 'show_unmapped_only' || columnMappingBulkAction === 'show_unmapped_only' && !taskArgs.sheet_editor_column[index])">
									<span class="csv-column-name-wrapper">
										<span class="csv-column-name-text" x-text="csvHeader" :title="csvHeader"></span>
										<small class="csv-column-name-example"><?php esc_html_e( 'Example: ', 'vg_sheet_editor' ); ?> <span x-text="csvFirstRow[csvHeader]"></span></small>
									</span>
									<span @click.prevent="hideColumnFromMapping(csvHeader, index)" class="dashicons dashicons-dismiss wpse-ignore-column-cross"></span> 
									<select :data-column-index="index" :id="'sheet-editor-column-mapping'+index" :data-selected="taskArgs.sheet_editor_column[index]" x-model="taskArgs.sheet_editor_column[index]" name="sheet_editor_column[]">
										<?php
										$this->render_wp_fields_import_options( $post_type );
										?>
									</select>
									<input class="csv-column-name-value" x-model="taskArgs.source_column[index]" name="source_column[]" type="hidden" />
								</div>	
							</template>

							<div class="dynamic-columns-ui">
								<template x-for="(dynamicColumn, index) in taskArgs.dynamic_columns" :key="'dynamic-' + index">
									<div class="map-template dynamic-column-template">
										<div class="dynamic-column-settings">
											<div class="field-wrap">
												<label><?php esc_html_e( 'Dynamic column name', 'vg_sheet_editor' ); ?></label>
												<input type="text" x-model="dynamicColumn.name" @input="updateDynamicColumnSource(index, $event.target.value)" required>
											</div>
											<div class="field-wrap">
												<label><?php esc_html_e( 'Column type', 'vg_sheet_editor' ); ?></label>
												<select x-model="dynamicColumn.type">
													<option value=""><?php esc_html_e( 'Select...', 'vg_sheet_editor' ); ?></option>
													<option value="set_value"><?php esc_html_e( 'Set value', 'vg_sheet_editor' ); ?></option>
													<option value="text_replace"><?php esc_html_e( 'Text replace', 'vg_sheet_editor' ); ?></option>
													<option value="combine_columns"><?php esc_html_e( 'Combine columns', 'vg_sheet_editor' ); ?></option>
													<option value="math_calculation"><?php esc_html_e( 'Math calculation', 'vg_sheet_editor' ); ?></option>
													<?php if ( VGSE()->helpers->user_can_manage_options() ) { ?>
													<option value="php_preprocessing"><?php esc_html_e( 'PHP preprocessing', 'vg_sheet_editor' ); ?></option>
													<?php } ?>
													<?php do_action( 'vg_sheet_editor/import/dynamic_columns/after_types', $post_type ); ?>
												</select>
											</div>

											<div x-show="dynamicColumn.type === 'set_value'">
												<div class="field-wrap">
													<label><?php esc_html_e( 'Value', 'vg_sheet_editor' ); ?></label>
													<input type="text" x-model="dynamicColumn.set_value" placeholder="<?php esc_attr_e( 'You can use variables like $CSV column name$.', 'vg_sheet_editor' ); ?>">
												</div>
											</div>
											<div x-show="dynamicColumn.type === 'text_replace'">
												<div class="field-wrap">
													<label><?php esc_html_e( 'Replace this', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_attr_e( 'Wrap it with / to make a regex replace. Use variables like $CSV column name$. The subject of the replacement will be the value of the first variable used here.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
													<input type="text" x-model="dynamicColumn.replace_this">
												</div>
												<div class="field-wrap">
													<label><?php esc_html_e( 'With this', 'vg_sheet_editor' ); ?></label>
													<input type="text" x-model="dynamicColumn.with_this">
												</div>
											</div>
											<div x-show="dynamicColumn.type === 'combine_columns'">
												<div class="field-wrap">
													<label><?php esc_html_e( 'Combine these columns', 'vg_sheet_editor' ); ?></label>
													<select :data-dynamic-combine-index="index" multiple x-model="dynamicColumn.combine_columns" x-init="initSelect2($el)" :data-full-model-path="'taskArgs.dynamic_columns['+index+'].combine_columns'" class="select2">
														<template x-for="csvHeader in csvHeaders">
															<option :value="csvHeader" x-text="csvHeader"></option>
														</template>
													</select>
												</div>
												<div class="field-wrap">
													<label><?php esc_html_e( 'Separator', 'vg_sheet_editor' ); ?></label>
													<input type="text" x-model="dynamicColumn.separator">
												</div>
											</div>
											<div x-show="dynamicColumn.type === 'math_calculation'">
												<div class="field-wrap">
													<label><?php esc_html_e( 'Enter your math formula', 'vg_sheet_editor' ); ?></label>
													<input type="text" x-model="dynamicColumn.formula" placeholder="<?php esc_attr_e( 'Example: $CSV column name1$ + $CSV column name2$ * 1.10', 'vg_sheet_editor' ); ?>">
												</div>
											</div>
											<?php if ( VGSE()->helpers->user_can_manage_options() ) { ?>
											<div x-show="dynamicColumn.type === 'php_preprocessing'">
												<div class="field-wrap">
													<label><?php esc_html_e( 'PHP function name', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_attr_e( 'Your function must accept one parameter $row with the array of key => values from the CSV, and return a string value for this dynamic column.', 'vg_sheet_editor' ); ?>">( ? )</a></label>
													<input type="text" x-model="dynamicColumn.php_function">
												</div>
											</div>
											<?php } ?>
											<?php do_action( 'vg_sheet_editor/import/dynamic_columns/after_settings', $post_type ); ?>
										</div>
										<span @click.prevent="removeDynamicColumn(index)" class="dashicons dashicons-dismiss wpse-ignore-column-cross"></span>
										<select x-model="dynamicColumn.wp_field" @change="updateDynamicColumnWpField(index, $event.target.value)">
											<?php
											$this->render_wp_fields_import_options( $post_type );
											?>
										</select>
									</div>
								</template>
								<button type="button" class="button" @click.prevent="addDynamicColumn"><?php esc_html_e( 'Add dynamic column', 'vg_sheet_editor' ); ?></button>
							</div>

							<p x-show="showAllColumnsIgnoredErrorMessage" class="wpse-all-columns-ignored-message" x-text="vgse_editor_settings.texts.import_all_columns_ignored"></p>
							
							<label class="remember-column-mapping"><input type="checkbox" x-model="taskArgs.remember_column_mapping" name="remember_column_mapping"> <?php esc_html_e( 'Remember this column mapping configuration?', 'vg_sheet_editor' ); ?></label>
							<button type="button" class="button button-primary button-primario prev-step step-nav" @click.prevent="goToPrevStep()" ><i class="fa fa-chevron-left"></i> <?php esc_html_e( 'Previous', 'vg_sheet_editor' ); ?></button>
							<button type="button" class="button button-primary button-primario next-step step-nav" @click.prevent="goToNextStep()" ><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
							<button type="button" x-show="showButtonToRestoreHiddenColumns" class="wpse-show-columns-again button button-primary button-primario" x-text="vgse_editor_settings.texts.import_show_all_columns_rows" @click.prevent="showAllHiddenColumns"></button>
						</div>
					</li>
					<li class="write-type step" x-show="step === 3">
						<h3 x-show="canShowWritingTypeSelection"><?php esc_html_e( 'Do you want to update or create items?', 'vg_sheet_editor' ); ?></h3>
						<select x-show="canShowWritingTypeSelection" x-model="taskArgs.writing_type" name="writing_type" required>
							<option value="">- -</option>
							<option value="both"><?php esc_html_e( 'Create new items and update existing items', 'vg_sheet_editor' ); ?></option>
							<option value="all_new"><?php esc_html_e( 'Import all rows as new', 'vg_sheet_editor' ); ?></option>
							<option value="only_new"><?php esc_html_e( 'Only create new items, ignore existing items', 'vg_sheet_editor' ); ?></option>
							<option value="only_update"><?php esc_html_e( 'Update existing items, ignore new items', 'vg_sheet_editor' ); ?></option>
						</select>	
						<div class="field-find-existing-columns" x-show="taskArgs.writing_type && taskArgs.writing_type !== 'all_new'">						
							<h4><?php esc_html_e( 'How do we find existing items to update?', 'vg_sheet_editor' ); ?></h4>

							<?php do_action( 'vg_sheet_editor/import/before_existing_wp_check_message', $post_type ); ?>

							<p class="wp-check-message">
								<?php esc_html_e( 'We find rows with the same value in the CSV Field and the WP Field.', 'vg_sheet_editor' ); ?>
								<br>
								<?php esc_html_e( 'I.e. Products with same SKU or ID.', 'vg_sheet_editor' ); ?>
							</p>

							<p x-show="showWarningWPFieldRequiresIgnoredColumn" class="wp-field-requires-ignored-column alert alert-blue">
								<?php esc_html_e( 'You selected a column from the CSV file below but the column is not being imported. Please go to the previous step and select the column to be imported.', 'vg_sheet_editor' ); ?>
								<br>
								<?php esc_html_e( 'Hypothetical example, if you want to update existing products with same ID, you need to import the ID column otherwise we don\'t have the IDs to find them.', 'vg_sheet_editor' ); ?>
							</p>
							<div class="field-wrapper">
								<label><?php esc_html_e( 'CSV Field', 'vg_sheet_editor' ); ?></label>
								<select x-model="taskArgs.existing_check_csv_field[0]" name="existing_check_csv_field[]" class="select2 existing-check-csv-field" x-init="initSelect2($el)">
									<option value="">- -</option>
									<template x-for="csvColumn in existingCheckCsvFieldOptions">
										<option :value="csvColumn" x-text="csvColumn"></option>
									</template>
								</select>	
							</div>
							<div class="field-wrapper">
								<label><?php esc_html_e( 'WordPress Field', 'vg_sheet_editor' ); ?></label>
								<select x-model="taskArgs.existing_check_wp_field[0]" name="existing_check_wp_field[]" class="select2 existing-check-wp-field" x-init="initSelect2($el)">
									<option value="">- -</option>
									<?php
									$wp_columns_to_search = implode(
										apply_filters(
											'vg_sheet_editor/import/wp_check/available_columns_options',
											VGSE()->helpers->get_post_type_columns_options(
												$post_type,
												array(
													'conditions' => array(
														'allow_search_during_import' => true,
													),
												),
												false,
												false
											),
											$post_type
										)
									);
									echo $wp_columns_to_search; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									if ( post_type_exists( $post_type ) ) {
										echo '<option value="post_name__in">' . esc_html__( 'Full URL', 'vg_sheet_editor' ) . '</option>';
									}
									?>
								</select>	
							</div>
						</div>
						<button type="button" class="button button-primary button-primario prev-step step-nav" @click.prevent="goToPrevStep()" ><i class="fa fa-chevron-left"></i> <?php esc_html_e( 'Previous', 'vg_sheet_editor' ); ?></button>								
						<button type="button" class="button button-primary button-primario next-step step-nav" @click.prevent="goToNextStep()" ><?php esc_html_e( 'Next', 'vg_sheet_editor' ); ?> <i class="fa fa-chevron-right"></i></button>
					</li>
					<li class="preview-step step" x-show="step === 4 && !isQuickSetupMode">
						<h3><?php esc_html_e( 'Final step', 'vg_sheet_editor' ); ?></h3>
						<p><?php esc_html_e( '1. Are we reading the file properly? Here is a preview of the first 5 rows from the file.', 'vg_sheet_editor' ); ?></p>
						<div id="hot-preview"></div>
						<p><?php esc_html_e( '2. Please make a backup before executing the import, so you can revert in case you used wrong settings or the file was wrong. The import will save the information directly.', 'vg_sheet_editor' ); ?></p>


						<div class="field" x-show="!extra || !extra.scheduleEnabled">
							<label><?php esc_html_e( 'Name', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Optional. Add a name to be able to run this import again with one click.', 'vg_sheet_editor' ); ?>">( ? )</a></label><br>
							<input type="text" x-model="taskArgs.name" name="name" class="name"/>
						</div>
						<label class=""><input type="checkbox" x-model="showAdvancedOptions" name="enable_advanced_source_options" class="toggle-advanced-options"> <?php esc_html_e( 'Show advanced options', 'vg_sheet_editor' ); ?></label>
						<div class="advanced-options" x-show="showAdvancedOptions">
							<div class="field">
								<label><?php esc_html_e( 'Number of rows to process per batch:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'Leave empty to use the global settings.', 'vg_sheet_editor' ); ?>">( ? )</a></label><br>
								<input type="number" x-model="taskArgs.per_page" name="per_page" class="per-page"/>								
							</div>
							<div class="field">
								<label><?php esc_html_e( 'Start from row number:', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'If you stop an import to edit your CSV file or change the import speed, you can start a new import and continue from where you left off.', 'vg_sheet_editor' ); ?>">( ? )</a></label><br>
								<input type="number" x-model="taskArgs.start_row" name="start_row" class="skip-rows"/>								
							</div>
							<div class="field">
								<label><input type="checkbox" x-model="taskArgs.decode_quotes" name="decode_quotes" class="decode-quotes"/> <?php esc_html_e( 'Decode quotes?', 'vg_sheet_editor' ); ?></label>								
							</div>
							<div class="field">
								<label><input type="checkbox" x-model="taskArgs.auto_retry_failed_batches" name="auto_retry_failed_batches" class="auto-retry-failed-batches"/> <?php esc_html_e( 'Auto retry failed batches?', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'We import the file in batches (i.e. 4 rows every few seconds). When one batch fails, we normally pause the import and ask you if you want to retry or cancel the import. Select this option to auto retry. Careful, you need to select the option to update existing rows in step 3 of the import, so we can retry and skip what was imported successfully and only retry what failed, if you dont select the option to update in step 3 of the import, every retry might duplicate some previously imported rows.', 'vg_sheet_editor' ); ?>">( ? )</a></label>								
							</div>
							<?php if ( VGSE()->helpers->get_current_provider()->is_post_type ) { ?>
								<div class="field">
									<label><input type="checkbox" x-model="taskArgs.pending_post_if_image_failed" name="pending_post_if_image_failed" class="pending-post-if-image-failed"/> <?php esc_html_e( 'Set post status to "pending" if featured image saving failed?', 'vg_sheet_editor' ); ?> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'This option works only if you are importing the featured image column and saving the featured image failed.', 'vg_sheet_editor' ); ?>">( ? )</a></label>								
								</div>
							<?php } ?>
							<?php do_action( 'vg_sheet_editor/import/after_advanced_options', $post_type ); ?>
						</div>
						<?php do_action( 'vg_sheet_editor/import/after_final_step_content', $post_type ); ?>
						<br>
						<button type="button" class="button button-primary button-primario prev-step step-nav step-nav" @click.prevent="goToPrevStep()" ><i class="fa fa-chevron-left"></i> <?php esc_html_e( 'Previous', 'vg_sheet_editor' ); ?></button>
					</li>
				</ul>
				<input type="hidden" x-model="taskArgs.import_file" name="import_file" class="import-file">
				<input type="hidden" x-model.number="taskArgs.total_rows" name="total_rows" class="total-rows">
				<input type="hidden" x-model="taskArgs.vgse_plain_mode" name="vgse_plain_mode" value="yes">
				<input type="hidden" x-model="taskArgs.import_type" name="import_type" value="csv">
				<input type="hidden" x-model="taskArgs.wpse_job_id" name="wpse_job_id" value="">
				<input type="hidden" x-model="taskArgs.action" name="action" value="vgse_import_csv">
				<input type="hidden" x-model="vgse_editor_settings.wpse_source_suffix" name="action" value="wpse_source_suffix">
				<input type="hidden" x-model="taskArgs.vgse_import" name="vgse_import" value="yes">
				<input type="hidden" x-model="taskArgs.nonce" name="nonce">
				<input type="hidden" x-model="taskArgs.post_type" value="<?php echo esc_attr( $post_type ); ?>" name="post_type">
				<button x-show="showSubmitButton" type="submit" class="remodal-confirm"><?php esc_html_e( 'The preview is fine, start import', 'vg_sheet_editor' ); ?></button>
				<button x-show="showCloseButton" type="button" data-remodal-action="confirm" class="remodal-cancel"><?php esc_html_e( 'Cancel import', 'vg_sheet_editor' ); ?></button>
			</form>
			<div class="import-step" x-show="isFormSubmitted">
				<h3><?php esc_html_e( 'Importing', 'vg_sheet_editor' ); ?></h3>


				<?php do_action( 'vg_sheet_editor/import/before_response', $post_type ); ?>
				<div x-show="Object.keys(progressLines).length" class="import-response">
						<template x-for="(line, lineIndex) in progressLines">
							<p x-html="line"></p>
						</template>
				</div>

				<p class="view-log" x-show="importLogUrl"><a :href="importLogUrl" class="button" target="_blank"><?php esc_html_e( 'View log', 'vg_sheet_editor' ); ?></a></p>
				<p x-show="isProcessInProgress" class="import-actions">
					<button x-show="!isProcessPaused" type="button" @click.prevent="pauseJob" class="button pause-export button-secondary"><i class="fa fa-pause"></i> <?php esc_html_e( 'Pause', 'vg_sheet_editor' ); ?></button>
					<button x-show="isProcessPaused" type="button" @click.prevent="resumeJob" class="button resume-export button-primary"><i class="fa fa-play"></i> <?php esc_html_e( 'Resume', 'vg_sheet_editor' ); ?></button>
				</p>
				<button type="button" data-remodal-action="confirm" class="remodal-cancel" x-show="!isProcessInProgress"><?php esc_html_e( 'Close', 'vg_sheet_editor' ); ?></button>
			</div >
			
			<?php do_action( 'vg_sheet_editor/import/after_content', $post_type ); ?>
		<?php } ?>
	</div>								
</div>
