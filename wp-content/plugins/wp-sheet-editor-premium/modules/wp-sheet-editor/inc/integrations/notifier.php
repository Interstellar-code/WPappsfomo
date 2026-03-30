<?php defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WPSE_Extension_Notifier' ) ) {

	class WPSE_Extension_Notifier {

		private static $instance = null;

		private function __construct() {        }

		public function init() {
			if ( ! is_admin() || ! current_user_can( 'install_plugins' ) || ! function_exists( 'freemius' ) || ! class_exists( 'FS_Options' ) ) {
				return;
			}
			add_filter( 'vg_sheet_editor/options_page/options', array( $this, 'add_settings_page_options' ) );
			add_action( 'wp_ajax_wpse_install_suggested_extensions', array( $this, 'install_suggested_extensions' ) );

			if ( ! empty( VGSE()->options['disable_important_extensions_toolbar'] ) ) {
				return;
			}
			if ( ! VGSE()->helpers->is_editor_page() ) {
				return;
			}
			$suggested_extensions = $this->get_suggested_extensions();
			if ( ! $suggested_extensions ) {
				return;
			}
			add_action( 'vg_sheet_editor/editor/before_init', array( $this, 'register_toolbar' ), 20 );
		}

		public function install_suggested_extensions() {
			if ( empty( $_POST['extensions'] ) || ! VGSE()->helpers->verify_nonce_from_request() || ! current_user_can( 'install_plugins' ) ) {
				wp_send_json_error( array( 'message' => __( 'Missing parameters or invalid permissions.', 'vg_sheet_editor' ) ) );
			}

			$extension_keys        = array_map( 'sanitize_text_field', $_POST['extensions'] );
			$all_extensions        = $this->get_suggested_extensions();
			$results               = array();
			$extensions_by_license = array();

			// Group extensions by license ID
			foreach ( $extension_keys as $key ) {
				if ( ! isset( $all_extensions[ $key ] ) ) {
					$results[ $key ] = array(
						'success' => false,
						'message' => __( 'Extension not found.', 'vg_sheet_editor' ),
					);
					continue;
				}
				$extension = $all_extensions[ $key ];
				$fs        = freemius( $extension['pluginIdFound'] );
				if ( ! $fs || ! $fs->is_registered() ) {
					$results[ $key ] = array(
						'success' => false,
						'message' => __( 'Freemius license not found or not active.', 'vg_sheet_editor' ),
					);
					continue;
				}
				$license_id = $fs->_get_license()->id;
				if ( ! isset( $extensions_by_license[ $license_id ] ) ) {
					$extensions_by_license[ $license_id ] = array();
				}
				$extensions_by_license[ $license_id ][] = $key;
			}

			foreach ( $extensions_by_license as $license_id => $keys ) {
				$api_url  = 'https://wpsheeteditor.com/wp-json/vgfs/v1/downloads/download-files-by-license-id';
				$response = wp_remote_post(
					$api_url,
					array(
						'body' => array(
							'license_id' => $license_id,
							'file_names' => $keys,
						),
					)
				);

				$body = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! is_wp_error( $response ) && ! empty( $body ) ) {
					foreach ( $body as $key => $download_url ) {
						if ( empty( $download_url ) ) {
							continue;
						}
						$result = $this->install_and_safely_activate_plugin_from_url( $download_url, $key );

						if ( is_wp_error( $result ) ) {
							$results[ $key ] = array(
								'success'    => false,
								'message'    => $result->get_error_message(),
								'license_id' => $license_id,
							);
						} else {
							$results[ $key ] = array( 'success' => true );
						}
					}
				}
			}
			wp_send_json_success( array( 'results' => $results ) );
		}

		/**
		 * Add fields to options page
		 * @param array $sections
		 * @return array
		 */
		public function add_settings_page_options( $sections ) {
			$sections['misc']['fields'][] = array(
				'id'    => 'disable_important_extensions_toolbar',
				'type'  => 'switch',
				'title' => esc_html__( 'Disable the popup that asks you to install important extensions?', 'vg_sheet_editor' ),
				'desc'  => esc_html__( 'We show a popup asking you to install free extensions when we detect that you are using a third-party plugin that requires special compatibility, which helps us prevent errors.', 'vg_sheet_editor' ),
			);
			return $sections;
		}

		public function is_extension_outdated( $folder_name, $official_version ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$php_files = VGSE()->helpers->get_files_list( WP_PLUGIN_DIR . '/' . $folder_name );
			$out       = false;
			foreach ( $php_files as $php_file ) {
				$plugin_data = get_plugin_data( $php_file );

				if ( ! empty( $plugin_data['Version'] ) && version_compare( $official_version, $plugin_data['Version'] ) === 1 ) {
					$out = true;
					break;
				}
			}
			return $out;
		}

		public function get_suggested_extensions() {
			$file_path = __DIR__ . '/extensions.json';
			$out       = array();
			if ( ! file_exists( $file_path ) || ! file_get_contents( $file_path ) ) {
				return $out;
			}
			$fs_options     = FS_Options::instance( WP_FS__ACCOUNTS_OPTION_NAME, true );
			$raw_fs_plugins = wp_list_filter( $fs_options->get_option( WP_FS__MODULE_TYPE_PLUGIN . 's' ), array( 'is_premium' => true ) );

			if ( empty( $raw_fs_plugins ) ) {
				return $out;
			}
			$fs_plugins = wp_list_pluck( $raw_fs_plugins, 'file', 'id' );
			$extensions = json_decode( file_get_contents( $file_path ), true );

			foreach ( $extensions as $index => $extension ) {
				if ( ! isset( $extension['file_name'] ) ) {
					continue;
				}
				// Skip extensions that are installed already if they are up to date
				if ( class_exists( $extension['className'] ) ) {
					if ( $this->is_extension_outdated( $extension['file_name'], $extension['version'] ) ) {
						$extension['requires_update'] = true;
					} else {
						continue;
					}
				} else {
					$extension['requires_update'] = false;
				}
				$extension_key = $extension['file_name'];
				if ( $extension['status'] !== 'publish' || empty( $extension['name'] ) || empty( $extension['description'] ) ) {
					continue;
				}

				// Skip if a required paid plan doesn't exists
				if ( ! empty( $extension['freemiusFunctionName'] ) && function_exists( $extension['freemiusFunctionName'] ) && ! $extension['freemiusFunctionName']()->can_use_premium_code__premium_only() ) {
					continue;
				}
				// Check if required fs plugins are found
				$all_plugins_exist = array();
				foreach ( $extension['requiredWPSEPlugin'] as $plugins ) {
					if ( is_array( $plugins ) ) {
						$plugin_is_valid = false;
						foreach ( $plugins as $plugin_id ) {
							if ( ! empty( $fs_plugins[ $plugin_id ] ) && is_plugin_active( $fs_plugins[ $plugin_id ] ) ) {
								$plugin_is_valid            = true;
								$extension['pluginIdFound'] = $plugin_id;
								break;
							}
						}
					} else {
						$plugin_is_valid            = ! empty( $fs_plugins[ $plugins ] ) && is_plugin_active( $fs_plugins[ $plugins ] );
						$extension['pluginIdFound'] = $plugins;
					}
					$all_plugins_exist[] = $plugin_is_valid;
				}

				// Skip if at least one check is false
				if ( in_array( false, $all_plugins_exist, true ) ) {
					continue;
				}

				// Check if required classes are valid
				$all_classes_exist = array();
				foreach ( $extension['dependencyClasses'] as $class ) {
					if ( is_array( $class ) ) {
						$class_is_valid = false;
						foreach ( $class as $class_name ) {
							if ( class_exists( $class_name ) ) {
								$class_is_valid = true;
								break;
							}
						}
					} else {
						$class_is_valid = class_exists( $class );
					}
					$all_classes_exist[] = $class_is_valid;
				}

				// Skip if at least one class check is false
				if ( in_array( false, $all_classes_exist, true ) ) {
					continue;
				}

				// Check if required functions are valid
				$all_functions_exist = array();
				foreach ( $extension['dependencyFunctions'] as $function ) {
					if ( is_array( $function ) ) {
						$function_is_valid = false;
						foreach ( $function as $function_name ) {
							if ( function_exists( $function_name ) ) {
								$function_is_valid = true;
								break;
							}
						}
					} else {
						$function_is_valid = function_exists( $function );
					}
					$all_functions_exist[] = $function_is_valid;
				}

				// Skip if at least one check is false
				if ( in_array( false, $all_functions_exist, true ) ) {
					continue;
				}
				// Check if required constants are valid
				$all_constants_exist = array();
				foreach ( $extension['dependencyConstants'] as $constant ) {
					if ( is_array( $constant ) ) {
						$constant_is_valid = false;
						foreach ( $constant as $constant_name ) {
							if ( defined( $constant_name ) ) {
								$constant_is_valid = true;
								break;
							}
						}
					} else {
						$constant_is_valid = defined( $constant );
					}
					$all_constants_exist[] = $constant_is_valid;
				}

				// Skip if at least one check is false
				if ( in_array( false, $all_constants_exist, true ) ) {
					continue;
				}
				$out[ $extension_key ] = $extension;
			}
			return $out;
		}

		public function register_toolbar( $editor ) {

			$post_types = $editor->args['enabled_post_types'];
			foreach ( $post_types as $post_type ) {
				// Skip if the current editor doesn't have a license toolbar with fs_id
				// because we need the fs object to get the buyer email
				$license_toolbar = $editor->args['toolbars']->get_item( 'wpse_license', $post_type, 'secondary' );
				if ( empty( $license_toolbar ) || empty( $license_toolbar['fs_id'] ) ) {
					continue;
				}
				$editor->args['toolbars']->register_item(
					'suggested_extensions',
					array(
						'type'                  => 'button',
						'content'               => esc_html__( 'Integrations', 'vg_sheet_editor' ),
						'extra_html_attributes' => 'data-remodal-target="modal-suggested-extensions"',
						'footer_callback'       => array( $this, 'render_suggested_extensions' ),
						'parent'                => 'extensions',
						'fs_id'                 => $license_toolbar['fs_id'],
					),
					$post_type
				);
			}
		}

		/**
		 * Render filters modal html
		 * @param string $current_post_type
		 */
		public function render_suggested_extensions( $current_post_type ) {
			$license_toolbar = VGSE()->helpers->get_provider_editor( $current_post_type )->args['toolbars']->get_item( 'wpse_license', $current_post_type, 'secondary' );
			$fs_id           = (int) $license_toolbar['fs_id'];
			$fs              = freemius( $fs_id );
			if ( ! $fs ) {
				return;
			}
			$user = $fs->get_user();
			if ( ! $user ) {
				return;
			}
			$email                = $user->email;
			$suggested_extensions = $this->get_suggested_extensions();
			if ( empty( $suggested_extensions ) ) {
				return;
			}
			$extensions_to_update  = wp_list_filter( $suggested_extensions, array( 'requires_update' => true ) );
			$extensions_to_install = wp_list_filter( $suggested_extensions, array( 'requires_update' => false ) );
			?>


			<div class="remodal suggested-extensions-modal" data-remodal-id="modal-suggested-extensions" data-remodal-options="closeOnOutsideClick: false" x-data="suggestedExtensionsApp()">

				<div class="modal-content">
					<template x-if="extensionsToInstall.length > 0">
						<div><h3><?php esc_html_e( 'Important extensions', 'vg_sheet_editor' ); ?></h3>
						<p><?php esc_html_e( 'Some of your plugins require special compatibility, and we have created these extensions that you can download for free if you purchased our plugin.', 'vg_sheet_editor' ); ?></p>
						<p><?php esc_html_e( 'It\'s important that you install them to prevent errors, but you can do it later if you want. Find this popup in the toolbar > extensions > Integrations.', 'vg_sheet_editor' ); ?></p>
						<p><?php esc_html_e( 'If you click on the "download" button, we will download the file from the official website: wpsheeteditor.com.', 'vg_sheet_editor' ); ?></p>
						<p><?php esc_html_e( 'If you don\'t want to install these extensinos, you can go to our advanced settings and enable the option "Disable the popup that asks you to install important extensions?".', 'vg_sheet_editor' ); ?></p>

						<?php do_action( 'vg_sheet_editor/suggested_extensions/above_list', $extensions_to_install, $current_post_type ); ?>

						<table>
							<thead>
								<tr>
									<th></th>
									<th class="extension-name"><?php esc_html_e( 'Extension name', 'vg_sheet_editor' ); ?></th>
									<th><?php esc_html_e( 'Description', 'vg_sheet_editor' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<template x-for="extension in extensionsToInstall" :key="extension.file_name">
									<tr>
										<td><input type="checkbox" class="extension-checkbox" :data-extension-key="extension.file_name" x-model="selectedExtensions[extension.file_name]"></td>
										<td class="extension-name" x-text="extension.name.replace('WP Sheet Editor - ', '')"></td>
										<td x-text="extension.description"></td>
									</tr>
								</template>
							</tbody>
						</table>
						</div>
					</template>

					<template x-if="extensionsToUpdate.length > 0">
					<div><h3><?php esc_html_e( 'Extensions that require update', 'vg_sheet_editor' ); ?></h3>
					<table>
						<thead>
							<tr>
								<th></th>
								<th class="extension-name"><?php esc_html_e( 'Extension name', 'vg_sheet_editor' ); ?></th>
								<th><?php esc_html_e( 'Description', 'vg_sheet_editor' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<template x-for="extension in extensionsToUpdate" :key="extension.file_name">
								<tr>
									<td><input type="checkbox" class="extension-checkbox" :data-extension-key="extension.file_name" x-model="selectedExtensions[extension.file_name]"></td>
									<td class="extension-name" x-text="extension.name.replace('WP Sheet Editor - ', '')"></td>
									<td x-text="extension.description"></td>
								</tr>
							</template>
						</tbody>
					</table>
					</div>
					</template>
					<div class="actions">
						<button class="button button-primary" @click="installSelectedExtensions()" :disabled="installing">
							<span x-show="!installing"><?php esc_html_e( 'Install selected extensions', 'vg_sheet_editor' ); ?></span>
							<span x-show="installing"><?php esc_html_e( 'Installing...', 'vg_sheet_editor' ); ?></span>
						</button>
					</div>
					<div id="installation-results" x-show="Object.keys(installationResults).length > 0">
						<template x-if="allSuccess">
							<p style="color: green"><strong><?php esc_html_e( 'All extensions installed successfully. Please reload the page.', 'vg_sheet_editor' ); ?></strong></p>
						</template>
						<template x-for="result in installationResults">
							<div>
								<p :style="{ color: result.success ? 'green' : 'red' }">
									<span x-text="result.message" x-show="!result.success"></span>
									<template x-if="!result.success && result.license_id">
										<div class="plugin-requires-manual-upload">
											<button class="button download-extension-fallback" @click="downloadFallback($event)" :data-license-id="result.license_id" :data-extension-key="key">
												<?php esc_html_e( 'Download manually', 'vg_sheet_editor' ); ?>
											</button>											
											<p><?php esc_html_e( 'When you finish downloading the plugins, you need to go to wp-admin > plugins > add new and install them.', 'vg_sheet_editor' ); ?></p>
										</div>
									</template>
								</p>
							</div>
						</template>
					</div>
					<p><?php esc_html_e( 'Do you need help?.', 'vg_sheet_editor' ); ?> <a target="_blank" href="<?php echo esc_url( VGSE()->get_support_links( 'contact_us', 'url', 'required-extension' ) ); ?>"><?php esc_html_e( 'Contact us', 'vg_sheet_editor' ); ?></a></p>

					<?php
					do_action( 'vg_sheet_editor/suggested_extensions/after_list', $current_post_type, $suggested_extensions );
					?>
					<button data-remodal-action="confirm" class="remodal-cancel" @click="installationResults = {}"><?php esc_html_e( 'Close', 'vg_sheet_editor' ); ?></button>
				</div>
				<br>
			</div>
			<script>
				document.addEventListener('alpine:init', () => {
					Alpine.data('suggestedExtensionsApp', () => ({
						extensionsToInstall: <?php echo json_encode( array_values( $extensions_to_install ) ); ?>,
						extensionsToUpdate: <?php echo json_encode( array_values( $extensions_to_update ) ); ?>,
						selectedExtensions: {},
						installing: false,
						installationResults: {},
						allSuccess: false,
						texts: {
							downloaded: '<?php esc_html_e( 'Downloaded', 'vg_sheet_editor' ); ?>',
							downloadManually: '<?php esc_html_e( 'Download manually', 'vg_sheet_editor' ); ?>',
							pleaseSelect: '<?php esc_html_e( 'Please select at least one extension to install.', 'vg_sheet_editor' ); ?>',
							installedSuccessfully: '<?php esc_html_e( 'Installed successfully.', 'vg_sheet_editor' ); ?>',
							serverError: '<?php esc_html_e( 'A server error occurred while trying to activate the plugins. Please try again. If it fails again, you can contact the WP Sheet Editor support team for assistance.', 'vg_sheet_editor' ); ?>',
						},
						init() {
							this.extensionsToInstall.forEach(ext => this.selectedExtensions[ext.file_name] = true);
							this.extensionsToUpdate.forEach(ext => this.selectedExtensions[ext.file_name] = true);

							// Auto open the popup after the rows loaded and if other popups aren't opened
							if (!window.location.hash) {
								setTimeout(() => {
									jQuery('[data-remodal-id="modal-suggested-extensions"]').remodal().open();
								}, 5000);
							}
						},
						downloadFallback(event) {
							const button = event.target;
							const licenseId = button.dataset.licenseId;
							const extensionKey = button.dataset.extensionKey;
							const apiUrl = 'https://wpsheeteditor.com/wp-json/vgfs/v1/downloads/download-file-by-license-id';
							
							button.textContent = '...';
							button.disabled = true;

							jQuery.post(apiUrl, {
								license_id: licenseId,
								file_name: extensionKey,
							}, (response) => {
								if (response.success) {
									button.textContent = this.texts.downloaded;
									window.location.href = response.download_url;
								} else {
									alert(response.message);
									button.textContent = this.texts.downloadManually;
									button.disabled = false;
								}
							});
						},
						installSelectedExtensions() {
							const extensionKeys = Object.keys(this.selectedExtensions).filter(key => this.selectedExtensions[key]);

							if (extensionKeys.length === 0) {
								alert(this.texts.pleaseSelect);
								return;
							}

							this.installing = true;
							this.installationResults = {};
							this.allSuccess = false;

							jQuery.post(ajaxurl, {
								action: 'wpse_install_suggested_extensions',
								nonce: vgse_editor_settings.nonce,
								extensions: extensionKeys
							}, (response) => {
								this.installing = false;
								if (response.success) {
									let allSuccess = true;
									let results = {};
									Object.entries(response.data.results).forEach(([key, result]) => {
										if (result.success) {
											results[key] = { success: true, message: this.texts.installedSuccessfully };
										} else {
											allSuccess = false;
											results[key] = { success: false, message: result.message, license_id: result.license_id };
										}
									});
									this.installationResults = results;
									this.allSuccess = allSuccess;
								}
							}).fail((jqXHR) => {
								this.installing = false;
								if (jqXHR.status >= 500) {
									let results = {};
									const extensionKeys = Object.keys(this.selectedExtensions).filter(key => this.selectedExtensions[key]);
									extensionKeys.forEach(key => {
										results[key] = { success: false, message: this.texts.serverError, license_id: null };
									});
									this.installationResults = results;
								}
							});
						}
					}));
					// Auto open the popup after the rows loaded and if other popups aren't opened
				});
			</script>
			<?php
		}

		/**
		 * Downloads, installs, and safely activates a WordPress plugin from a URL using Plugin_Upgrader.
		 *
		 * This function handles the entire process:
		 * 1. Uses Plugin_Upgrader to download and install the plugin from the provided URL.
		 * 2. Attempts to activate the plugin in a "sandbox" mode.
		 * 3. If the activation causes a fatal error, it automatically deactivates the plugin,
		 * preventing the "white screen of death," and returns an error.
		 *
		 * @param string $zip_url The direct URL to the plugin's ZIP file.
		 * @param string $plugin_folder The folder name of the plugin.
		 * @return true|WP_Error True on successful activation, or a WP_Error object on failure.
		 */
		public function install_and_safely_activate_plugin_from_url( $zip_url, $plugin_folder ) {
			// Include necessary WordPress files for plugin installation
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			// Set up the Plugin Upgrader
			$skin = new Automatic_Upgrader_Skin();

			if ( ! is_dir( WP_PLUGIN_DIR . '/' . $plugin_folder ) ) {
				// Using a quiet skin to prevent any output during the process
				$upgrader = new Plugin_Upgrader( $skin );

				// Install the plugin from the URL. The upgrader handles download and extraction.
				$install_result = $upgrader->install( $zip_url );

				if ( is_wp_error( $install_result ) ) {
					return new WP_Error(
						'plugin_install_failed',
						'Failed to install the plugin.',
						$install_result->get_error_message()
					);
				}

				// The install method returns null on success, so we check the skin for errors.
				if ( is_wp_error( $skin->result ) ) {
					return new WP_Error(
						'plugin_install_failed_skin',
						'An error occurred during plugin installation.',
						$skin->result->get_error_message()
					);
				}

				// Get the path of the plugin's main file (e.g., 'my-plugin/my-plugin.php')
				$plugin_to_activate = $upgrader->plugin_info();
				if ( ! $plugin_to_activate ) {
					return new WP_Error( 'plugin_info_failed', 'Could not retrieve plugin information after installation.' );
				}
			} else {
				$plugin_files       = glob( WP_PLUGIN_DIR . '/' . $plugin_folder . '/*.php' );
				$plugin_to_activate = null;
				foreach ( $plugin_files as $plugin_file ) {
					$plugin_data = get_plugin_data( $plugin_file, false, false );
					if ( ! empty( $plugin_data['Name'] ) ) {
						$plugin_to_activate = str_replace( WP_PLUGIN_DIR . '/', '', $plugin_file );
						break;
					}
				}

				if ( ! $plugin_to_activate ) {
					return new WP_Error( 'plugin_info_failed', 'Could not find a valid plugin file to activate in the existing folder.' );
				}
			}

			// Activate the plugin with the sandbox check to catch fatal errors.
			$activation_result = activate_plugin( $plugin_to_activate, '', false, true );

			// All steps were successful!
			return is_wp_error( $activation_result ) ? $activation_result : true;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		public static function get_instance() {
			if ( null == self::$instance ) {
				self::$instance = new WPSE_Extension_Notifier();
				self::$instance->init();
			}
			return self::$instance;
		}

		public function __set( $name, $value ) {
			$this->$name = $value;
		}

		public function __get( $name ) {
			return $this->$name;
		}
	}
}

if ( ! function_exists( 'WPSE_Extension_Notifier_Obj' ) ) {

	function WPSE_Extension_Notifier_Obj() {
		return WPSE_Extension_Notifier::get_instance();
	}
}

add_action( 'vg_sheet_editor/initialized', 'WPSE_Extension_Notifier_Obj' );
