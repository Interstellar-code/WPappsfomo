<?php defined( 'ABSPATH' ) || exit; ?>
<div class="remodal remodal-previous-imports remodal-large" data-remodal-id="previous-imports-modal" data-remodal-options="closeOnOutsideClick: false, hashTracking: false">
	<div class="modal-content" x-data="vgsePreviousImports">
		<h3><?php esc_html_e( 'Run a previous import again', 'vg_sheet_editor' ); ?></h3>
		<div x-show="!runningImportSettings">
		    <p><?php esc_html_e( 'Here you can see the imports that you saved previously by giving them a name. You can run them again with one click.', 'vg_sheet_editor' ); ?></p>
			<table>
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'vg_sheet_editor' ); ?></th>
						<th><?php esc_html_e( 'Last run', 'vg_sheet_editor' ); ?></th>
						<th><?php esc_html_e( 'Author', 'vg_sheet_editor' ); ?></th>
						<th><?php esc_html_e( 'Imported fields', 'vg_sheet_editor' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'vg_sheet_editor' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<template x-for="(importData, hash) in previousImports" :key="hash">
						<tr>
							<td x-text="importData.name"></td>
							<td x-text="formatTimestamp(importData.timestamp)"></td>
							<td x-text="importData.user_login || 'N/A'"></td>
							<td :title="importData.source_column.join(', ')" x-text="importData.source_column.join(', ').substring(0, 200) + (importData.source_column.join(', ').length > 200 ? '...' : '')"></td>
							<td>
								<button class="button" @click.prevent="prepareToRunAgain(hash)" data-wpse-tooltip="down" aria-label="<?php esc_attr_e( 'Run again', 'vg_sheet_editor' ); ?>"><i class="fa fa-play"></i></button>
								<button class="button" x-show="isAuthor(importData.author)" @click.prevent="remove(hash)" data-wpse-tooltip="down" aria-label="<?php esc_attr_e( 'Remove', 'vg_sheet_editor' ); ?>"><i class="fa fa-trash"></i></button>
							</td>
						</tr>
					</template>
					<tr x-show="!Object.keys(previousImports).length">
						<td colspan="5"><?php esc_html_e( 'No saved imports found.', 'vg_sheet_editor' ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>

		<template x-if="runningImportSettings">
			<div x-ref="previousImportRunner" x-data="vgseImport">
				<div class="import-details">
					<h4><?php esc_html_e( 'Import name:', 'vg_sheet_editor' ); ?> <span x-text="runningImportSettings.name"></span></h4>
					<p><b><?php esc_html_e( 'Columns:', 'vg_sheet_editor' ); ?></b> <span x-text="runningImportSettings.source_column.join(', ')"></span></p>
                    <p><b><?php esc_html_e( 'Last run:', 'vg_sheet_editor' ); ?></b> <span x-text="formatTimestamp(runningImportSettings.timestamp)"></span></p>
                    <p><b><?php esc_html_e( 'Writing type:', 'vg_sheet_editor' ); ?></b> <span x-text="getWritingTypeLabel(runningImportSettings.writing_type)"></span></p>
                    <p x-show="runningImportSettings.existing_check_wp_field && runningImportSettings.existing_check_wp_field.length"><b><?php esc_html_e( 'Find existing items by:', 'vg_sheet_editor' ); ?></b> <span x-text="runningImportSettings.existing_check_csv_field.join(', ') + ' (CSV) = ' + runningImportSettings.existing_check_wp_field.join(', ') + ' (WP)'"></span></p>
                    <p x-show="runningImportSettings.start_row"><b><?php esc_html_e( 'Start row:', 'vg_sheet_editor' ); ?></b> <span x-text="runningImportSettings.start_row"></span></p>
                    <p x-show="runningImportSettings.per_page"><b><?php esc_html_e( 'Rows per batch:', 'vg_sheet_editor' ); ?></b> <span x-text="runningImportSettings.per_page"></span></p>
                    
				</div>                
				<div class="step">
						<label><?php esc_html_e( 'Source', 'vg_sheet_editor' ); ?></label>								
						<select name="source" class="source" :class="'source-'+taskArgs.source" x-model="taskArgs.source">
							<template x-for="(label, key) in getImportSources">
								<option :value="key" x-text="label"></option>
							</template>
						</select>						
						<template x-if="taskArgs.source === 'csv_upload'">
						<div class="data-input csv_upload">
							<label><?php esc_html_e( 'CSV file', 'vg_sheet_editor' ); ?> </label>
							<input type="file" name="local_file" class="data" id="vgse-rerun-import-local-file" /> 
						</div>
						</template>
						<template x-if="taskArgs.source === 'csv_url'">
						<div class="data-input csv_url">
							<label><?php esc_html_e( 'File URL', 'vg_sheet_editor' ); ?> </label>								
							<input type="text" x-model="taskArgs.data" name="file_url" placeholder="File URL" class="data" />
						</div>
						</template>
						<template x-if="taskArgs.source === 'paste'">
						<div class="data-input paste">
							<label><?php esc_html_e( 'Copy and Paste into the spreadsheet below', 'vg_sheet_editor' ); ?></label>
							<p><?php esc_html_e( 'This is not recommended for large amounts of data.', 'vg_sheet_editor' ); ?></p>								
							<div class="handsontable-paste"></div>
						</div>
						</template>
						<template x-if="taskArgs.source === 'server_file'">
						<div class="data-input server_file">
							<label><?php esc_html_e( 'CSV file location', 'vg_sheet_editor' ); ?> <a href="" data-wpse-tooltip="right" aria-label="<?php echo esc_attr( sprintf( esc_html__( 'You must enter a file name in this field (not full path) and upload the file to the folder %s', 'vg_sheet_editor' ), preg_replace( '/^.+(\/wp-content.+)$/', '$1', WPSE_CSV_API_Obj()->imports_dir ) ) ); ?>">( ? )</a> </label>
							<p>
							<?php
							$parts = explode( basename( WP_CONTENT_DIR ), WPSE_CSV_API_Obj()->imports_dir );
							echo esc_html( '/' . basename( WP_CONTENT_DIR ) . end( $parts ) );
							?>
							<input type="text" x-model="taskArgs.data" name="server_file" class="data" /></p>							 
						</div>
						</template>
						
						<?php do_action( 'vg_sheet_editor/import/after_data_sources_fields', $post_type ); ?>
				</div>
				<div x-show="Object.keys(progressLines).length" class="import-response">
					<h3><?php esc_html_e( 'Processing', 'vg_sheet_editor' ); ?></h3>
					<template x-for="(line, lineIndex) in progressLines">
						<p x-html="line"></p>
					</template>
				</div>
				<br>
				<button class="remodal-confirm" @click.prevent="startRerunImport($el)"><?php esc_html_e( 'Start import', 'vg_sheet_editor' ); ?></button>
				<button class="remodal-cancel" @click="cancelRerun()" x-show="importFinished"><?php esc_html_e( 'Close', 'vg_sheet_editor' ); ?></button>
			</div>
		</template>

		<button data-remodal-action="confirm" class="remodal-cancel" x-show="!runningImportSettings"><?php esc_html_e( 'Close', 'vg_sheet_editor' ); ?></button>
	</div>
</div>
