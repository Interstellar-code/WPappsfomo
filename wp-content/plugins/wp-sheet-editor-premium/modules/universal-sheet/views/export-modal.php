<?php defined( 'ABSPATH' ) || exit; ?>
<!--Save changes modal-->
<div class="remodal export-csv-modal remodal-fixed" data-remodal-id="export-csv-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">

	<div class="modal-content">
		<?php
		$is_not_supported = apply_filters( 'vg_sheet_editor/export/is_not_supported', null, $post_type );
		if ( ! is_null( $is_not_supported ) ) {
			$message = ( is_string( $is_not_supported ) ) ? $is_not_supported : esc_html__( 'The export feature is not compatible with your website. Make sure WordPress and all the plugins and themes are up to date.', 'vg_sheet_editor' );
			?>

			<h3><?php esc_html_e( 'Export', 'vg_sheet_editor' ); ?></h3>
			<p><?php echo wp_kses_post( $message ); ?></p>
			<button data-remodal-action="confirm" class="remodal-cancel"><?php esc_html_e( 'Cancel', 'vg_sheet_editor' ); ?></button>

			<?php
		} else {
			?>
			<?php do_action( 'vg_sheet_editor/export/before_form', $post_type ); ?>
			<form class="export-csv-form vgse-modal-form" method="POST" x-data="vgseExport" @submit.prevent="submit">
				<h3><?php esc_html_e( 'Export', 'vg_sheet_editor' ); ?></h3>

				<div class="fields-to-export">
					<div class="field-wrap">
						<label><?php esc_html_e( 'What columns do you want to export?', 'vg_sheet_editor' ); ?></label>
						<select x-init="initSelect2($el)" :disabled="isProcessInProgress" x-ref="selectedColumns" name="export_columns[]" required data-placeholder="<?php esc_html_e( 'Select column...', 'vg_sheet_editor' ); ?>" class="select2 export-columns" data-full-model-path="taskArgs.selectedColumns" multiple x-model="taskArgs.selectedColumns">
							<option></option>
							<template x-for="(columnLabel, columnKey) in allColumns" :key="columnKey + Date.now()">
								<option :value="columnKey" x-text="columnLabel"></option>
							</template>
						</select>
						<br/>
						<button class="select-active button" @click.prevent="selectColumns('active')"><?php esc_html_e( 'Select active columns', 'vg_sheet_editor' ); ?></button> 
						<button class="select-all button" @click.prevent="selectColumns('all')"><?php esc_html_e( 'Select all', 'vg_sheet_editor' ); ?></button> 
						<button class="unselect-all button" @click.prevent="selectColumns('none')"><?php esc_html_e( 'Unselect  all', 'vg_sheet_editor' ); ?></button>
					</div>

					<?php if ( empty( VGSE()->options['enable_simple_mode'] ) ) { ?>
						<div class="field-wrap">
							<label><?php esc_html_e( 'Which rows do you want to export?', 'vg_sheet_editor' ); ?></label>
							<select required class="wpse-select-rows-options" x-model="taskArgs.rowsSelectionType">
								<option value="">--</option>
								<option value="current_search"><?php esc_html_e( 'All the rows from my current search', 'vg_sheet_editor' ); ?></option>
								<option value="selected"><?php esc_html_e( 'Rows that I selected manually with the checkbox', 'vg_sheet_editor' ); ?></option>
							</select>
						</div>

						<div class="field-wrap">
							<label class="excel-compatibility-container"><?php esc_html_e( 'What app will you use to edit this file? (optional)', 'vg_sheet_editor' ); ?><br>
								<select name="target_software" x-model="taskArgs.targetSoftware">
									<?php
									foreach ( vgse_universal_sheet()->get_target_software_options() as $key => $options ) {
										foreach ( $options as $option_key => $option ) {
											?>
											<option value="<?php echo esc_attr( $option_key ); ?>"><?php echo esc_html( $option ); ?></option>
											<?php
										}
									}
									?>
								</select>
							</label>
							<?php do_action( 'vg_sheet_editor/export/after_target_software_field', $post_type ); ?>
						</div>
					<?php } ?>
					<?php if ( VGSE()->helpers->user_can_manage_options() ) { ?>
						<div class="field-wrap" x-show="taskArgs.targetSoftware.indexOf('addon') < 0">
							<label class="save-for-later-container"><span x-text="extra.scheduleEnabled ? vgse_editor_settings.texts.export_name_field_label_required : vgse_editor_settings.texts.export_name_field_label_optional"></span> <a href="#" data-wpse-tooltip="right" aria-label="<?php esc_html_e( 'We will save the current search query and export settings, and you can execute this export with one click in the future using the dropdown in the export menu', 'vg_sheet_editor' ); ?>">( ? )</a></label>
							<input type="text"  name="save_for_later_name" x-model="jobName" :required="extra.scheduleEnabled === true">
						</div>
					<?php } ?>
					<div x-show="taskArgs.targetSoftware.indexOf('addon') < 0">
						<?php do_action( 'vg_sheet_editor/export/after_form_fields', $post_type ); ?>
					</div>
				</div>

				<?php do_action( 'vg_sheet_editor/export/before_response', $post_type ); ?>
				<div id="be-export-nanobar-container" x-show="nanobar && showNanobar"></div>
				<div x-show="Object.keys(progressLines).length" class="export-response">
						<template x-for="(line, lineIndex) in progressLines">
							<p x-html="line"></p>
						</template>
				</div>

				<p x-show="isProcessInProgress" class="export-actions">
					<button x-show="!isProcessPaused" type="button" @click.prevent="pauseJob" class="button pause-export button-secondary"><i class="fa fa-pause"></i> <?php esc_html_e( 'Pause', 'vg_sheet_editor' ); ?></button>
					<button x-show="isProcessPaused" type="button" @click.prevent="resumeJob" class="button resume-export button-primary"><i class="fa fa-play"></i> <?php esc_html_e( 'Resume', 'vg_sheet_editor' ); ?></button>
				</p>

				<input type="hidden" value="vgse_export_csv" name="action">
				<button x-show="( !isProcessInProgress || isProcessPaused ) && taskArgs.targetSoftware.indexOf('addon') < 0" type="submit" class="remodal-confirm vgse-trigger-export"><?php esc_html_e( 'Start new export', 'vg_sheet_editor' ); ?></button>
				<button x-show="!isProcessInProgress || isProcessPaused" data-remodal-action="confirm" class="remodal-cancel"><?php esc_html_e( 'Cancel', 'vg_sheet_editor' ); ?></button>

			</form>
		<?php } ?>
	</div>								
</div>
