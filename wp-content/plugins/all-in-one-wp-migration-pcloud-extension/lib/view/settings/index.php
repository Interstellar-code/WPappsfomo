<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}
?>

<div class="ai1wm-container">
	<div class="ai1wm-row">
		<div class="ai1wm-left">
			<div class="ai1wm-holder">
				<h1><i class="ai1wm-icon-gear"></i> <?php _e( 'pCloud Settings', AI1WMPE_PLUGIN_NAME ); ?></h1>
				<br />
				<br />

				<div class="ai1wm-field">
					<?php if ( $token ) : ?>
						<p id="ai1wmpe-pcloud-details">
							<?php _e( 'Retrieving pCloud account details..', AI1WMPE_PLUGIN_NAME ); ?>
						</p>

						<div id="ai1wmpe-pcloud-progress">
							<div id="ai1wmpe-pcloud-progress-bar"></div>
						</div>

						<p id="ai1wmpe-pcloud-space"></p>

						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=ai1wmpe_pcloud_revoke' ) ); ?>">
							<button type="submit" class="ai1wm-button-red" name="ai1wmpe_pcloud_logout" id="ai1wmpe-pcloud-logout">
								<i class="ai1wm-icon-exit"></i>
								<?php _e( 'Sign Out from your pCloud account', AI1WMPE_PLUGIN_NAME ); ?>
							</button>
						</form>

					<?php else : ?>

						<form method="post" action="<?php echo esc_url( AI1WMPE_REDIRECT_CREATE_URL ); ?>">
							<input type="hidden" name="ai1wmpe_client" id="ai1wmpe-client" value="<?php echo esc_url( wp_nonce_url( network_admin_url( 'admin.php?page=ai1wmpe_settings' ) ) ); ?>" />
							<input type="hidden" name="ai1wmpe_purchase_id" id="ai1wmpe-purchase-id" value="<?php echo esc_attr( get_option( 'ai1wmpe_plugin_key' ) ); ?>" />
							<input type="hidden" name="ai1wmpe_site_url" id="ai1wmpe-site-url" value="<?php echo esc_attr( site_url() ); ?>" />
							<input type="hidden" name="ai1wmpe_admin_email" id="ai1wmpe-admin-email" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
							<button type="submit" class="ai1wm-button-blue" name="ai1wmpe_pcloud_link" id="ai1wmpe-pcloud-link">
								<i class="ai1wm-icon-enter"></i>
								<?php _e( 'Link your pCloud account', AI1WMPE_PLUGIN_NAME ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $token ) : ?>
				<div id="ai1wmpe-backups" class="ai1wm-holder">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=ai1wmpe_pcloud_settings' ) ); ?>">
						<h1><i class="ai1wm-icon-gear"></i> <?php _e( 'pCloud Backups', AI1WMPE_PLUGIN_NAME ); ?></h1>
						<br />
						<br />

						<?php if ( Ai1wm_Message::has( 'settings' ) ) : ?>
							<div class="ai1wm-message ai1wm-success-message">
								<p><?php echo Ai1wm_Message::get( 'settings' ); ?></p>
							</div>
						<?php endif; ?>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Configure your backup plan', AI1WMPE_PLUGIN_NAME ); ?></h3>

							<p>
								<label for="ai1wmpe-pcloud-cron-timestamp">
									<?php _e( 'Backup time:', AI1WMPE_PLUGIN_NAME ); ?>
									<input type="text" name="ai1wmpe_pcloud_cron_timestamp" id="ai1wmpe-pcloud-cron-timestamp" value="<?php echo esc_attr( get_date_from_gmt( date( 'Y-m-d H:i:s', $pcloud_cron_timestamp ), 'g:i a' ) ); ?>" autocomplete="off" />
									<code><?php echo ai1wm_get_timezone_string(); ?></code>
								</label>
							</p>

							<ul id="ai1wmpe-pcloud-cron">
								<li>
									<label for="ai1wmpe-pcloud-cron-hourly">
										<input type="checkbox" name="ai1wmpe_pcloud_cron[]" id="ai1wmpe-pcloud-cron-hourly" value="hourly" <?php echo in_array( 'hourly', $pcloud_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every hour', AI1WMPE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmpe-pcloud-cron-daily">
										<input type="checkbox" name="ai1wmpe_pcloud_cron[]" id="ai1wmpe-pcloud-cron-daily" value="daily" <?php echo in_array( 'daily', $pcloud_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every day', AI1WMPE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmpe-pcloud-cron-weekly">
										<input type="checkbox" name="ai1wmpe_pcloud_cron[]" id="ai1wmpe-pcloud-cron-weekly" value="weekly" <?php echo in_array( 'weekly', $pcloud_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every week', AI1WMPE_PLUGIN_NAME ); ?>
									</label>
								</li>
								<li>
									<label for="ai1wmpe-pcloud-cron-monthly">
										<input type="checkbox" name="ai1wmpe_pcloud_cron[]" id="ai1wmpe-pcloud-cron-monthly" value="monthly" <?php echo in_array( 'monthly', $pcloud_backup_schedules ) ? 'checked' : null; ?> />
										<?php _e( 'Every month', AI1WMPE_PLUGIN_NAME ); ?>
									</label>
								</li>
							</ul>

							<p>
								<?php _e( 'Last backup date:', AI1WMPE_PLUGIN_NAME ); ?>
								<strong>
									<?php echo $last_backup_date; ?>
								</strong>
							</p>

							<p>
								<?php _e( 'Next backup date:', AI1WMPE_PLUGIN_NAME ); ?>
								<strong>
									<?php echo $next_backup_date; ?>
								</strong>
							</p>

							<p>
								<label for="ai1wmpe-pcloud-incremental">
									<input type="checkbox" name="ai1wmpe_pcloud_incremental" id="ai1wmpe-pcloud-incremental" value="1" <?php echo empty( $incremental ) ? null : 'checked'; ?> />
									<?php _e( 'Enable incremental backups (optimize backup file size)', AI1WMPE_PLUGIN_NAME ); ?>
								</label>
							</p>

							<p>
								<label for="ai1wmpe-pcloud-ssl">
									<input type="checkbox" name="ai1wmpe_pcloud_ssl" id="ai1wmpe-pcloud-ssl" value="1" <?php echo empty( $ssl ) ? 'checked' : null; ?> />
									<?php _e( 'Disable connecting to pCloud via SSL (only if export is failing)', AI1WMPE_PLUGIN_NAME ); ?>
								</label>
							</p>
						</article>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Destination folder', AI1WMPE_PLUGIN_NAME ); ?></h3>
							<p id="ai1wmpe-pcloud-folder-details">
								<span class="spinner" style="visibility: visible;"></span>
								<?php _e( 'Retrieving pCloud folder details..', AI1WMPE_PLUGIN_NAME ); ?>
							</p>
							<p>
								<input type="hidden" name="ai1wmpe_pcloud_folder_id" id="ai1wmpe-pcloud-folder-id" />
								<input type="hidden" name="ai1wmpe_pcloud_incremental_folder_id" id="ai1wmpe-pcloud-incremental-folder-id" />

								<button type="button" class="ai1wm-button-gray" name="ai1wmpe_pcloud_change" id="ai1wmpe-pcloud-change">
									<i class="ai1wm-icon-folder"></i>
									<?php _e( 'Change', AI1WMPE_PLUGIN_NAME ); ?>
								</button>
							</p>
						</article>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Notification settings', AI1WMPE_PLUGIN_NAME ); ?></h3>
							<p>
								<label for="ai1wmpe-pcloud-notify-toggle">
									<input type="checkbox" id="ai1wmpe-pcloud-notify-toggle" name="ai1wmpe_pcloud_notify_toggle" <?php echo empty( $notify_ok_toggle ) ? null : 'checked'; ?> />
									<?php _e( 'Send an email when a backup is completed', AI1WMPE_PLUGIN_NAME ); ?>
								</label>
							</p>

							<p>
								<label for="ai1wmpe-pcloud-notify-error-toggle">
									<input type="checkbox" id="ai1wmpe-pcloud-notify-error-toggle" name="ai1wmpe_pcloud_notify_error_toggle" <?php echo empty( $notify_error_toggle ) ? null : 'checked'; ?> />
									<?php _e( 'Send an email if a backup fails', AI1WMPE_PLUGIN_NAME ); ?>
								</label>
							</p>

							<p>
								<label for="ai1wmpe-pcloud-notify-email">
									<?php _e( 'Email address', AI1WMPE_PLUGIN_NAME ); ?>
									<br />
									<input class="ai1wmpe-email" style="width: 15rem;" type="email" id="ai1wmpe-pcloud-notify-email" name="ai1wmpe_pcloud_notify_email" value="<?php echo esc_attr( $notify_email ); ?>" />
								</label>
							</p>
						</article>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Retention settings', AI1WMPE_PLUGIN_NAME ); ?></h3>
							<p>
								<div class="ai1wm-field">
									<label for="ai1wmpe-pcloud-backups">
										<?php _e( 'Keep the most recent', AI1WMPE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmpe_pcloud_backups" id="ai1wmpe-pcloud-backups" value="<?php echo intval( $backups ); ?>" />
									</label>
									<?php _e( 'backups. <small>Default: <strong>0</strong> unlimited</small>', AI1WMPE_PLUGIN_NAME ); ?>
								</div>

								<div class="ai1wm-field">
									<label for="ai1wmpe-pcloud-total">
										<?php _e( 'Limit the total size of backups to', AI1WMPE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmpe_pcloud_total" id="ai1wmpe-pcloud-total" value="<?php echo intval( $total ); ?>" />
									</label>
									<select style="margin-top: -2px;" name="ai1wmpe_pcloud_total_unit" id="ai1wmpe-pcloud-total-unit">
										<option value="MB" <?php echo strpos( $total, 'MB' ) !== false ? 'selected="selected"' : null; ?>><?php _e( 'MB', AI1WMPE_PLUGIN_NAME ); ?></option>
										<option value="GB" <?php echo strpos( $total, 'GB' ) !== false ? 'selected="selected"' : null; ?>><?php _e( 'GB', AI1WMPE_PLUGIN_NAME ); ?></option>
									</select>
									<?php _e( '<small>Default: <strong>0</strong> unlimited</small>', AI1WMPE_PLUGIN_NAME ); ?>
								</div>

								<div class="ai1wm-field">
									<label for="ai1wmpe-pcloud-days">
										<?php _e( 'Remove backups older than ', AI1WMPE_PLUGIN_NAME ); ?>
										<input style="width: 4.5em;" type="number" min="0" name="ai1wmpe_pcloud_days" id="ai1wmpe-pcloud-days" value="<?php echo intval( $days ); ?>" />
									</label>
									<?php _e( 'days. <small>Default: <strong>0</strong> off</small>', AI1WMPE_PLUGIN_NAME ); ?>
								</div>
							</p>
						</article>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Security settings', AI1WMPE_PLUGIN_NAME ); ?></h3>
							<p>
								<label for="ai1wmpe-pcloud-lock-mode">
									<input type="checkbox" id="ai1wmpe-pcloud-lock-mode" name="ai1wmpe_pcloud_lock_mode" <?php echo empty( $lock_mode ) ? null : 'checked'; ?> />
									<?php printf( __( 'Lock this page for all users except <strong>%s</strong>. <a href="https://help.servmask.com/knowledgebase/lock-settings-page/" target="_blank">More details</a>', AI1WMPE_PLUGIN_NAME ), $user_display_name ); ?>
								</label>
							</p>
						</article>

						<article class="ai1wmpe-article">
							<h3><?php _e( 'Transfer settings', AI1WMPE_PLUGIN_NAME ); ?></h3>
							<div class="ai1wm-field">
								<label><?php _e( 'Slow Internet (Home)', AI1WMPE_PLUGIN_NAME ); ?></label>
								<input name="ai1wmpe_pcloud_file_chunk_size" min="5242880" max="20971520" step="5242880" type="range" value="<?php echo $file_chunk_size; ?>" id="ai1wmpe-pcloud-file-chunk-size" />
								<label><?php _e( 'Fast Internet (Internet Servers)', AI1WMPE_PLUGIN_NAME ); ?></label>
							</div>
						</article>

						<p>
							<button type="submit" class="ai1wm-button-blue" name="ai1wmpe_pcloud_update" id="ai1wmpe-pcloud-update">
								<i class="ai1wm-icon-database"></i>
								<?php _e( 'Update', AI1WMPE_PLUGIN_NAME ); ?>
							</button>
						</p>
					</form>
				</div>
			<?php endif; ?>

			<?php do_action( 'ai1wmpe_settings_left_end' ); ?>

		</div>
		<div class="ai1wm-right">
			<div class="ai1wm-sidebar">
				<div class="ai1wm-segment">
					<?php if ( ! AI1WM_DEBUG ) : ?>
						<?php include AI1WM_TEMPLATES_PATH . '/common/share-buttons.php'; ?>
					<?php endif; ?>

					<h2><?php _e( 'Leave Feedback', AI1WMPE_PLUGIN_NAME ); ?></h2>

					<?php include AI1WM_TEMPLATES_PATH . '/common/leave-feedback.php'; ?>
				</div>
			</div>
		</div>
	</div>
</div>
