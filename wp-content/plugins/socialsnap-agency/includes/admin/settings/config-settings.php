<?php
$config = array(
	'ss_social_sharing'     => array(
		'type'   => 'group',
		'id'     => 'ss_social_sharing',
		'name'   => esc_html__( 'Social Sharing', 'socialsnap' ),
		'icon'   => 'share',
		'fields' => array(
			'ss_social_share_networks_display' => array(
				'id'          => 'ss_social_share_networks_display',
				'type'        => 'subgroup',
				'name'        => __( 'Manage Networks', 'socialsnap' ),
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'desc'        => __( 'Add and reorder social share providers. You can define network label and device visibility for each network. Drag networks to change their order.', 'socialsnap' ),
				'fields'      => array(
					'ss_social_share_networks'          => array(
						'id'      => 'ss_social_share_networks',
						'name'    => esc_html__( 'Networks', 'socialsnap' ),
						'type'    => 'social_share_networks',
						'default' => array(
							'order'    => 'facebook;twitter;linkedin',
							'facebook' => array(
								'text'               => __( 'Facebook', 'socialsnap' ),
								'desktop_visibility' => 'on',
								'mobile_visibility'  => 'on',
							),
							'twitter'  => array(
								'text'               => __( 'X (Twitter)', 'socialsnap' ),
								'desktop_visibility' => 'on',
								'mobile_visibility'  => 'on',
							),
							'linkedin' => array(
								'text'               => __( 'LinkedIn', 'socialsnap' ),
								'desktop_visibility' => 'on',
								'mobile_visibility'  => 'on',
							),
						),
					),
					'ss_ss_force_refresh_cache'         => array(
						'id'     => 'ss_ss_force_refresh_cache',
						'name'   => esc_html__( 'Share Count Cache', 'socialsnap' ),
						'text'   => __( 'Force Refresh Now', 'socialsnap' ),
						'type'   => 'button',
						'action' => 'socialsnap_ss_cache_refresh',
					),
					'ss_ss_facebook_count_provider'     => array(
						'id'      => 'ss_ss_facebook_count_provider',
						'name'    => esc_html__( 'Facebook Share Count Provider:', 'socialsnap' ),
						'desc'    => esc_html__( 'Choose a method to retrieve Facebook share counts.', 'socialsnap' ),
						'type'    => 'dropdown',
						'options' => array(
							// 'authorize'   => __( 'Social Snap App', 'socialsnap' ),
							'token'       => __( 'Facebook Graph API', 'socialsnap' ),
							'sharedcount' => __( 'SharedCount.com', 'socialsnap' ),
						),
						'default' => 'authorize',
					),
					'ss_ss_facebook_shared_count_api'   => array(
						'id'          => 'ss_ss_facebook_shared_count_api',
						'name'        => esc_html__( 'SharedCount.com API Key', 'socialsnap' ),
						'type'        => 'text',
						'placeholder' => esc_html__( 'API Key goes here...', 'socialsnap' ),
						'default'     => '',
						'dependency'  => array(
							'element' => 'ss_ss_facebook_count_provider',
							'value'   => 'sharedcount',
						),
					),
					'ss_ss_facebook_access_token'       => array(
						'id'          => 'ss_ss_facebook_access_token',
						'name'        => esc_html__( 'Facebook Access Token', 'socialsnap' ),
						'type'        => 'text',
						'placeholder' => esc_html__( 'Access Token goes here...', 'socialsnap' ),
						'default'     => '',
						'dependency'  => array(
							'element' => 'ss_ss_facebook_count_provider',
							'value'   => 'token',
						),
					),
					'ss_ss_fb_setup_note'               => array(
						'id'   => 'ss_ss_fb_setup_note',
						'name' => esc_html__( 'Note:', 'socialsnap' ),
						// translators: %1$s is the opening anchor tag, %2$s is the closing anchor tag.
						'desc' => sprintf( __( 'Follow our %1$sstep-by-step tutorial%2$s on how to set up Facebook Share Provider.', 'socialsnap' ), '<a href="https://socialsnap.com/help/features/how-to-setup-facebook-share-provider/">', '</a>' ),
						'type' => 'note',
					),
					'ss_ss_twitter_count_provider'      => array(
						'id'      => 'ss_ss_twitter_count_provider',
						'name'    => esc_html__( 'Twitter Share Count Provider:', 'socialsnap' ),
						'desc'    => esc_html__( 'Choose if you want to connect with a third party service to track your Twitter share counts or use our button click tracking method.', 'socialsnap' ),
						'pro'     => true,
						'type'    => 'dropdown',
						'options' => array(
							'click_tracking'  => __( 'Click Tracking', 'socialsnap' ),
							'opensharecounts' => __( 'OpenShareCount.com', 'socialsnap' ),
							'twitcount'       => __( 'TwitCount.com', 'socialsnap' ),
						),
						'default' => 'click_tracking',
					),
					'ss_ss_share_click_tracking'        => array(
						'id'      => 'ss_ss_share_click_tracking',
						'name'    => esc_html__( 'Track share clicks to get share counts for networks without APIs?', 'socialsnap' ),
						'type'    => 'toggle',
						'pro'     => true,
						'default' => true,
					),
					'ss_ss_pinterest_browser_extension' => array(
						'id'      => 'ss_ss_pinterest_browser_extension',
						'name'    => esc_html__( 'Pinterest Browser Extension', 'socialsnap' ),
						'desc'    => esc_html__( 'Adds a hidden image which will be shared when Pinterest browser extension is used.', 'socialsnap' ),
						'type'    => 'toggle',
						'pro'     => true,
						'default' => true,
					),
				),
			),
			'ss_social_share_floating_sidebar' => array(
				'id'          => 'ss_social_share_floating_sidebar',
				'name'        => esc_html__( 'Floating Sidebar', 'socialsnap' ),
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'type'        => 'subgroup',
				'fields'      => array(

					'ss_ss_sidebar_enabled'            => array(
						'id'      => 'ss_ss_sidebar_enabled',
						'name'    => esc_html__( 'Enable Sidebar', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => true,
					),
					'ss_ss_sidebar_position'           => array(
						'id'         => 'ss_ss_sidebar_position',
						'name'       => esc_html__( 'Sidebar Position', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'left'  => __( 'Left Sidebar', 'socialsnap' ),
							'right' => __( 'Right Sidebar', 'socialsnap' ),
						),
						'default'    => 'left',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_button_shape'       => array(
						'id'         => 'ss_ss_sidebar_button_shape',
						'name'       => esc_html__( 'Button Shape', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'rounded'   => __( 'Rounded', 'socialsnap' ),
							'circle'    => __( 'Circle', 'socialsnap' ),
							'rectangle' => __( 'Rectangle', 'socialsnap' ),
						),
						'default'    => 'rounded',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_button_size'        => array(
						'id'         => 'ss_ss_sidebar_button_size',
						'name'       => esc_html__( 'Button Size', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'large'   => __( 'Large', 'socialsnap' ),
							'regular' => __( 'Regular', 'socialsnap' ),
							'small'   => __( 'Small', 'socialsnap' ),
						),
						'default'    => 'regular',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_post_types'         => array(
						'id'         => 'ss_ss_sidebar_post_types',
						'name'       => esc_html__( 'Display on', 'socialsnap' ),
						'type'       => 'checkbox_group',
						'source'     => array( 'post_type', 'taxonomies' ),
						'options'    => array(
							'home' => array(
								'title' => __( 'Home', 'socialsnap' ),
							),
							'blog' => array(
								'title' => __( 'Posts Page', 'socialsnap' ),
							),
							'post' => array(
								'title' => __( 'Post', 'socialsnap' ),
							),
							'page' => array(
								'title' => __( 'Page', 'socialsnap' ),
							),
						),
						'default'    => array(
							'home' => 'on',
							'post' => 'on',
						),
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_button_spacing'     => array(
						'id'         => 'ss_ss_sidebar_button_spacing',
						'name'       => esc_html__( 'Button Spacing', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Show additional spacing between share buttons.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_all_networks'       => array(
						'id'         => 'ss_ss_sidebar_all_networks',
						'name'       => esc_html__( 'All Networks', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Enable button that allows users to choose from all available networks.', 'socialsnap' ),
						'default'    => true,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_label_tooltip'      => array(
						'id'         => 'ss_ss_sidebar_label_tooltip',
						'name'       => esc_html__( 'Network Label Tooltips', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Show network labels when hovering social buttons.', 'socialsnap' ),
						'default'    => true,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_total_count'        => array(
						'id'         => 'ss_ss_sidebar_total_count',
						'name'       => esc_html__( 'Total Share Count', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Display total share count from all social networks.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_share_count'        => array(
						'id'         => 'ss_ss_sidebar_share_count',
						'name'       => esc_html__( 'Share Counts', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Display share counts on share buttons. Not available on Archive pages', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_hide_on_mobile'     => array(
						'id'         => 'ss_ss_sidebar_hide_on_mobile',
						'name'       => esc_html__( 'Hide on Mobile', 'socialsnap' ),
						'type'       => 'toggle',
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_view_count'         => array(
						'id'         => 'ss_ss_sidebar_view_count',
						'name'       => esc_html__( 'View Count', 'socialsnap' ),
						'type'       => 'toggle',
						'pro'        => true,
						'desc'       => __( 'Display unique view count of the current post/page.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_min_count'          => array(
						'id'         => 'ss_ss_sidebar_min_count',
						'name'       => esc_html__( 'Min Share Count', 'socialsnap' ),
						'type'       => 'text',
						'pro'        => true,
						'value_type' => 'number',
						'desc'       => __( 'Hide share counts if lower than this value.', 'socialsnap' ),
						'default'    => '0',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_position_offset'    => array(
						'id'         => 'ss_ss_sidebar_position_offset',
						'name'       => esc_html__( 'Vertical Offset', 'socialsnap' ),
						'type'       => 'text',
						'pro'        => true,
						'desc'       => __( 'Enter negative value to move up and positive value to move down. Values are treated as “px”.', 'socialsnap' ),
						'default'    => '0',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_entrance_animation' => array(
						'id'         => 'ss_ss_sidebar_entrance_animation',
						'name'       => esc_html__( 'Entrance Animation', 'socialsnap' ),
						'type'       => 'dropdown',
						'pro'        => true,
						'options'    => array(
							'none'   => __( 'None', 'socialsnap' ),
							'fade'   => __( 'Fade In', 'socialsnap' ),
							'slide'  => __( 'Slide In', 'socialsnap' ),
							'bounce' => __( 'Bounce In', 'socialsnap' ),
							'flip'   => __( 'Flip In', 'socialsnap' ),
						),
						'default'    => 'fade',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_hover_animation'    => array(
						'id'         => 'ss_ss_sidebar_hover_animation',
						'name'       => esc_html__( 'Button Hover Animation', 'socialsnap' ),
						'type'       => 'dropdown',
						'pro'        => true,
						'options'    => array(
							'ss-hover-animation-fade' => __( 'Fade', 'socialsnap' ),
							'ss-hover-animation-1'    => __( 'Slide Background', 'socialsnap' ),
							'ss-hover-animation-2'    => __( 'Slide Icon', 'socialsnap' ),
							'ss-hover-animation-1 ss-hover-animation-2' => __( 'Slide Icon & Background', 'socialsnap' ),
						),
						'default'    => 'ss-hover-animation-fade',
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_light_counter'      => array(
						'id'         => 'ss_ss_sidebar_light_counter',
						'name'       => esc_html__( 'Total Share Count: Light Text', 'socialsnap' ),
						'type'       => 'toggle',
						'pro'        => true,
						'desc'       => __( 'Use this option if your website has a dark background.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_sidebar_custom_colors'      => array(
						'id'         => 'ss_ss_sidebar_custom_colors',
						'name'       => esc_html__( 'Custom Colors', 'socialsnap' ),
						'type'       => 'toggle',
						'pro'        => true,
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_sidebar_enabled',
							'value'   => 'true',
						),
					),
				),
			),
			'ss_social_share_inline_content'   => array(
				'id'          => 'ss_social_share_inline_content',
				'name'        => esc_html__( 'Inline Buttons', 'socialsnap' ),
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'type'        => 'subgroup',
				'fields'      => array(

					'ss_ss_inline_content_enabled'         => array(
						'id'      => 'ss_ss_inline_content_enabled',
						'name'    => esc_html__( 'Enable Inline Buttons', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => true,
					),
					'ss_ss_inline_content_location'        => array(
						'id'         => 'ss_ss_inline_content_location',
						'name'       => esc_html__( 'Position', 'socialsnap' ),
						'type'       => 'dropdown',
						'options'    => array(
							'above' => __( 'Above Content', 'socialsnap' ),
							'below' => __( 'Below Content', 'socialsnap' ),
							'both'  => __( 'Above + Below', 'socialsnap' ),
						),
						'default'    => 'below',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_position'        => array(
						'id'         => 'ss_ss_inline_content_position',
						'name'       => esc_html__( 'Alignment', 'socialsnap' ),
						'type'       => 'dropdown',
						'desc'       => __( 'Choose share buttons alignment.', 'socialsnap' ),
						'options'    => array(
							'left'      => __( 'Left', 'socialsnap' ),
							'center'    => __( 'Center', 'socialsnap' ),
							'right'     => __( 'Right', 'socialsnap' ),
							'stretched' => __( 'Stretched', 'socialsnap' ),
						),
						'default'    => 'left',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_share_label'     => array(
						'id'         => 'ss_ss_inline_content_share_label',
						'name'       => esc_html__( 'Share label', 'socialsnap' ),
						'type'       => 'text',
						'desc'       => __( 'Text shown above inline share buttons. Leave empty to ignore.', 'socialsnap' ),
						'default'    => __( 'Share via:', 'socialsnap' ),
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_button_shape'    => array(
						'id'         => 'ss_ss_inline_content_button_shape',
						'name'       => esc_html__( 'Button Shape', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'rounded'   => __( 'Rounded', 'socialsnap' ),
							'circle'    => __( 'Circle', 'socialsnap' ),
							'rectangle' => __( 'Rectangle', 'socialsnap' ),
							'slanted'   => __( 'Slanted', 'socialsnap' ),
						),
						'default'    => 'rounded',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_button_size'     => array(
						'id'         => 'ss_ss_inline_content_button_size',
						'name'       => esc_html__( 'Button Size', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'small'   => __( 'Small', 'socialsnap' ),
							'regular' => __( 'Regular', 'socialsnap' ),
							'large'   => __( 'Large', 'socialsnap' ),
						),
						'default'    => 'small',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_button_label'    => array(
						'id'         => 'ss_ss_inline_content_button_label',
						'name'       => esc_html__( 'Button Labels', 'socialsnap' ),
						'desc'       => esc_html__( 'Share counts are not available on Archive pages.', 'socialsnap' ),
						'type'       => 'radio',
						'options'    => array(
							'none'  => __( 'None', 'socialsnap' ),
							'label' => __( 'Network Label', 'socialsnap' ),
							'count' => __( 'Share Count', 'socialsnap' ),
							'both'  => __( 'Both', 'socialsnap' ),
						),
						'default'    => 'label',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_post_types'      => array(
						'id'         => 'ss_ss_inline_content_post_types',
						'name'       => esc_html__( 'Display on', 'socialsnap' ),
						'desc'       => __( 'Select where to display inline share buttons.', 'socialsnap' ),
						'type'       => 'checkbox_group',
						'source'     => array( 'post_type', 'taxonomies' ),
						'options'    => array(
							'home' => array(
								'title' => __( 'Home', 'socialsnap' ),
							),
							'blog' => array(
								'title' => __( 'Posts Page', 'socialsnap' ),
							),
							'post' => array(
								'title' => __( 'Post', 'socialsnap' ),
							),
							'page' => array(
								'title' => __( 'Page', 'socialsnap' ),
							),
						),
						'default'    => array(
							'post' => 'on',
						),
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_full_content'    => array(
						'id'         => 'ss_ss_inline_content_full_content',
						'name'       => esc_html__( 'Blog displays full post instead of post excerpt?', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Enable this option if you are seeing share buttons code in post summaries.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_button_spacing'  => array(
						'id'         => 'ss_ss_inline_content_button_spacing',
						'name'       => esc_html__( 'Button Spacing', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Show additional spacing between share buttons.', 'socialsnap' ),
						'default'    => true,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_all_networks'    => array(
						'id'         => 'ss_ss_inline_content_all_networks',
						'name'       => esc_html__( 'All Networks', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Enable button that allows users to share on all available networks.', 'socialsnap' ),
						'default'    => true,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_all_networks_label' => array(
						'id'         => 'ss_ss_inline_content_all_networks_label',
						'name'       => esc_html__( 'All Networks label', 'socialsnap' ),
						'type'       => 'text',
						'default'    => __( 'More', 'socialsnap' ),
						'dependency' => array(
							'element' => 'ss_ss_inline_content_all_networks',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_hide_on_mobile'  => array(
						'id'         => 'ss_ss_inline_content_hide_on_mobile',
						'name'       => esc_html__( 'Hide on Mobile', 'socialsnap' ),
						'type'       => 'toggle',
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_total_count'     => array(
						'id'         => 'ss_ss_inline_content_total_count',
						'name'       => esc_html__( 'Total Shares', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Enable Total Shares counter.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_total_share_style' => array(
						'id'         => 'ss_ss_inline_content_total_share_style',
						'name'       => esc_html__( 'Total Shares Style', 'socialsnap' ),
						'type'       => 'dropdown',
						'pro'        => true,
						'desc'       => __( 'Choose Total Shares style.', 'socialsnap' ),
						'options'    => array(
							'none'      => __( 'Default', 'socialsnap' ),
							'icon'      => __( 'With Icon', 'socialsnap' ),
							'separator' => __( 'With Separator', 'socialsnap' ),
							'both'      => __( 'With Icon + Separator', 'socialsnap' ),
						),
						'default'    => 'separator',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_total_count',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_total_share_placement' => array(
						'id'         => 'ss_ss_inline_content_total_share_placement',
						'name'       => esc_html__( 'Total Shares Placement', 'socialsnap' ),
						'type'       => 'dropdown',
						'pro'        => true,
						'desc'       => __( 'Choose between left and right placement for Total Shares counter.', 'socialsnap' ),
						'options'    => array(
							'left'  => __( 'Left', 'socialsnap' ),
							'right' => __( 'Right', 'socialsnap' ),
						),
						'default'    => 'left',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_total_count',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_min_count'       => array(
						'id'         => 'ss_ss_inline_content_min_count',
						'name'       => esc_html__( 'Min Share Count', 'socialsnap' ),
						'type'       => 'text',
						'pro'        => true,
						'value_type' => 'number',
						'desc'       => __( 'This is the minimum number of shares a page needs to have before share counter is shown.', 'socialsnap' ),
						'default'    => '0',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_hover_animation' => array(
						'id'         => 'ss_ss_inline_content_hover_animation',
						'name'       => esc_html__( 'Button Hover Animation', 'socialsnap' ),
						'type'       => 'dropdown',
						'pro'        => true,
						'desc'       => __( 'Choose share buttons hover animation.', 'socialsnap' ),
						'options'    => array(
							'ss-hover-animation-fade' => __( 'Fade', 'socialsnap' ),
							'ss-hover-animation-1'    => __( 'Slide Background', 'socialsnap' ),
							'ss-reveal-label'         => __( 'Reveal Label', 'socialsnap' ),
						),
						'default'    => 'ss-hover-animation-fade',
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_light_counter'   => array(
						'id'         => 'ss_ss_inline_content_light_counter',
						'name'       => esc_html__( 'Text Color Scheme: Light Text', 'socialsnap' ),
						'type'       => 'toggle',
						'pro'        => true,
						'desc'       => __( 'Use this option if your website has a dark background.', 'socialsnap' ),
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_inline_content_custom_colors'   => array(
						'id'         => 'ss_ss_inline_content_custom_colors',
						'name'       => esc_html__( 'Custom Colors', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Customize share buttons colors.', 'socialsnap' ),
						'default'    => false,
						'pro'        => true,
						'dependency' => array(
							'element' => 'ss_ss_inline_content_enabled',
							'value'   => 'true',
						),
					),
				),
			),

			'ss_social_share_on_media'         => array(
				'id'          => 'ss_social_share_on_media',
				'name'        => 'On Media',
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'type'        => 'subgroup',
				'fields'      => array(
					'ss_ss_on_media_enabled'          => array(
						'id'      => 'ss_ss_on_media_enabled',
						'name'    => esc_html__( 'Enable On Media', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),

					'ss_ss_on_media_type'             => array(
						'id'         => 'ss_ss_on_media_type',
						'name'       => esc_html__( 'Choose Type', 'socialsnap' ),
						'type'       => 'radio',
						'desc'       => __( 'Choose between Pinterest Save button or share buttons set in Networks section.', 'socialsnap' ),
						'options'    => array(
							'pin_it'        => __( 'Pinterest Button', 'socialsnap' ),
							'share_buttons' => __( 'Share Buttons', 'socialsnap' ),
						),
						'default'    => 'pin_it',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_button_shape'     => array(
						'id'         => 'ss_ss_on_media_button_shape',
						'name'       => esc_html__( 'Button Shape', 'socialsnap' ),
						'type'       => 'radio',
						'desc'       => '',
						'options'    => array(
							'rounded'   => __( 'Rounded', 'socialsnap' ),
							'circle'    => __( 'Circle', 'socialsnap' ),
							'rectangle' => __( 'Rectangle', 'socialsnap' ),
						),
						'default'    => 'circle',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_button_size'      => array(
						'id'         => 'ss_ss_on_media_button_size',
						'name'       => esc_html__( 'Button Size', 'socialsnap' ),
						'type'       => 'radio',
						'desc'       => '',
						'options'    => array(
							'large'   => __( 'Large', 'socialsnap' ),
							'regular' => __( 'Regular', 'socialsnap' ),
							'small'   => __( 'Small', 'socialsnap' ),
						),
						'default'    => 'regular',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_hover'            => array(
						'id'         => 'ss_ss_on_media_hover',
						'name'       => esc_html__( 'Visibility', 'socialsnap' ),
						'type'       => 'radio',
						'desc'       => '',
						'options'    => array(
							'always' => __( 'Always Visible', 'socialsnap' ),
							'hover'  => __( 'On Hover', 'socialsnap' ),
						),
						'default'    => 'always',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_position'         => array(
						'id'         => 'ss_ss_on_media_position',
						'name'       => esc_html__( 'Choose Position', 'socialsnap' ),
						'type'       => 'dropdown',
						'desc'       => '',
						'options'    => array(
							'top-left'     => __( 'Top Left', 'socialsnap' ),
							'top-right'    => __( 'Top Right', 'socialsnap' ),
							'center'       => __( 'Center', 'socialsnap' ),
							'bottom-left'  => __( 'Bottom Left', 'socialsnap' ),
							'bottom-right' => __( 'Bottom Right', 'socialsnap' ),
						),
						'default'    => 'top-left',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_minwidth'         => array(
						'id'         => 'ss_ss_on_media_minwidth',
						'name'       => esc_html__( 'Min Image Width:', 'socialsnap' ),
						'type'       => 'text',
						'value_type' => 'number',
						'min'        => '0',
						'desc'       => __( 'Hide the buttons on images that are smaller than specified.', 'socialsnap' ),
						'default'    => '250',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_minheight'        => array(
						'id'         => 'ss_ss_on_media_minheight',
						'name'       => esc_html__( 'Min Image Height:', 'socialsnap' ),
						'type'       => 'text',
						'value_type' => 'number',
						'min'        => '0',
						'desc'       => __( 'Hide the buttons on images that are smaller than specified.', 'socialsnap' ),
						'default'    => '250',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_post_types'       => array(
						'id'         => 'ss_ss_on_media_post_types',
						'name'       => esc_html__( 'Display on', 'socialsnap' ),
						'type'       => 'checkbox_group',
						'source'     => array( 'post_type', 'taxonomies' ),
						'options'    => array(
							'home' => array(
								'title' => __( 'Home', 'socialsnap' ),
							),
							'blog' => array(
								'title' => __( 'Posts Page', 'socialsnap' ),
							),
							'post' => array(
								'title' => __( 'Post', 'socialsnap' ),
							),
							'page' => array(
								'title' => __( 'Page', 'socialsnap' ),
							),
						),
						'default'    => array(
							'post' => 'on',
							'page' => 'on',
						),
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_button_spacing'   => array(
						'id'         => 'ss_ss_on_media_button_spacing',
						'name'       => esc_html__( 'Button Spacing', 'socialsnap' ),
						'type'       => 'toggle',
						'desc'       => __( 'Show additional spacing around share buttons.', 'socialsnap' ),
						'default'    => true,
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),

					'ss_ss_on_media_hide_on_mobile'   => array(
						'id'         => 'ss_ss_on_media_hide_on_mobile',
						'name'       => esc_html__( 'Hide on Mobile', 'socialsnap' ),
						'type'       => 'toggle',
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_pinterest_image_src'       => array(
						'id'         => 'ss_ss_pinterest_image_src',
						'name'       => esc_html__( 'Pinterest Image Source', 'socialsnap' ),
						'desc'       => esc_html__( 'Choose image source to use for Pinterest sharing.', 'socialsnap' ),
						'pro'        => true,
						'type'       => 'dropdown',
						'options'    => array(
							'image'  => __( 'Image under the button', 'socialsnap' ),
							'custom' => __( 'Custom from post/page settings', 'socialsnap' ),
						),
						'default'    => 'image',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_pinterest_description_src' => array(
						'id'         => 'ss_ss_pinterest_description_src',
						'name'       => esc_html__( 'Pinterest Description Source', 'socialsnap' ),
						'desc'       => esc_html__( 'Choose description source to use for Pinterest sharing.', 'socialsnap' ),
						'pro'        => true,
						'type'       => 'dropdown',
						'options'    => array(
							'image'  => __( 'Image description set in Media Library', 'socialsnap' ),
							'custom' => __( 'Custom from post/page settings', 'socialsnap' ),
						),
						'default'    => 'image',
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),
					'ss_ss_on_media_custom_colors'    => array(
						'id'         => 'ss_ss_on_media_custom_colors',
						'name'       => esc_html__( 'Custom Colors', 'socialsnap' ),
						'type'       => 'toggle',
						'pro'        => true,
						'default'    => false,
						'dependency' => array(
							'element' => 'ss_ss_on_media_enabled',
							'value'   => 'true',
						),
					),
				),
			),

			'ss_social_share_share_hub'        => array(
				'id'          => 'ss_social_share_share_hub',
				'name'        => 'Share Hub',
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'type'        => 'subgroup',
				'pro'         => true,
				'fields'      => array(),
			),

			'ss_social_share_sticky_bar'       => array(
				'id'          => 'ss_social_share_sticky_bar',
				'name'        => esc_html__( 'Sticky Bar', 'socialsnap' ),
				'parent_name' => __( 'Social Share', 'socialsnap' ),
				'type'        => 'subgroup',
				'pro'         => true,
				'fields'      => array(),
			),
		),
	),
	'ss_social_follow'      => array(
		'type'                    => 'group',
		'id'                      => 'ss_social_follow',
		'name'                    => esc_html__( 'Social Followers', 'socialsnap' ),
		'icon'                    => 'followers',
		'fields'                  => array(

			'ss_social_follow_networks_display' => array(
				'id'          => 'ss_social_follow_networks_display',
				'type'        => 'subgroup',
				'name'        => __( 'Manage Networks', 'socialsnap' ),
				'parent_name' => __( 'Social Follow', 'socialsnap' ),
				'desc'        => __( 'Setup and reorder social networks that you want to use in shortcodes and widgets. Drag networks to change their order.', 'socialsnap' ),
				'fields'      => array(
					'ss_social_follow_connect_networks' => array(
						'id'      => 'ss_social_follow_connect_networks',
						'name'    => esc_html__( 'Networks', 'socialsnap' ),
						'type'    => 'social_follow_networks',
						'default' => array(
							'facebook'  => array(
								'profile'          => array(
									'username' => '',
									'url'      => '',
								),
								'label'            => __( 'Follow us on Facebook', 'socialsnap' ),
								'manual_followers' => '',
							),
							'twitter'   => array(
								'profile'          => array(
									'username' => '',
									'url'      => '',
								),
								'label'            => __( 'Follow us on Twitter', 'socialsnap' ),
								'manual_followers' => '',
							),
							'pinterest' => array(
								'profile'          => array(
									'username' => '',
									'url'      => '',
								),
								'label'            => __( 'Follow us on Pinterest', 'socialsnap' ),
								'manual_followers' => '',
							),
							'instagram' => array(
								'profile'          => array(
									'username' => '',
									'url'      => '',
								),
								'label'            => __( 'Follow us on Instagram', 'socialsnap' ),
								'manual_followers' => '',
							),
							'tumblr'    => array(
								'profile'          => array(
									'username' => '',
									'url'      => '',
								),
								'label'            => __( 'Follow us on Tumblr', 'socialsnap' ),
								'manual_followers' => '',
							),
						),
					),
					'ss_follow_update_info'             => array(
						'id'   => 'ss_follow_update_info',
						'name' => esc_html__( 'Note:', 'socialsnap' ),
						'desc' => __( 'Followers count is updated once daily.', 'socialsnap' ),
						'type' => 'note',
					),
				),
			),

			'ss_social_follow_default_settings' => array(
				'id'          => 'ss_social_follow_default_settings',
				'type'        => 'subgroup',
				'name'        => __( 'Default Settings', 'socialsnap' ),
				'parent_name' => __( 'Social Follow', 'socialsnap' ),
				'desc'        => __( 'Default settings for the Social Follow element.', 'socialsnap' ),
				'fields'      => array(
					'ss_sf_button_size'      => array(
						'id'      => 'ss_sf_button_size',
						'name'    => esc_html__( 'Button Size', 'socialsnap' ),
						'type'    => 'radio',
						'desc'    => '',
						'options' => array(
							'large'   => __( 'Large', 'socialsnap' ),
							'regular' => __( 'Regular', 'socialsnap' ),
							'small'   => __( 'Small', 'socialsnap' ),
						),
						'default' => 'regular',
					),
					'ss_sf_button_columns'   => array(
						'id'      => 'ss_sf_button_columns',
						'name'    => esc_html__( 'Button Columns', 'socialsnap' ),
						'type'    => 'dropdown',
						'desc'    => '',
						'options' => array(
							'1' => __( '1 Column', 'socialsnap' ),
							'2' => __( '2 Columns', 'socialsnap' ),
							'3' => __( '3 Columns', 'socialsnap' ),
							'4' => __( '4 Columns', 'socialsnap' ),
							'5' => __( '5 Columns', 'socialsnap' ),
						),
						'default' => '1',
					),
					'ss_sf_button_spacing'   => array(
						'id'      => 'ss_sf_button_spacing',
						'name'    => esc_html__( 'Button Spacing', 'socialsnap' ),
						'desc'    => esc_html__( 'Show additional spacing between follow buttons.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_sf_button_vertical'  => array(
						'id'      => 'ss_sf_button_vertical',
						'name'    => esc_html__( 'Vertical Layout', 'socialsnap' ),
						'desc'    => esc_html__( 'Use vertical button style.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_sf_total_followers'  => array(
						'id'      => 'ss_sf_total_followers',
						'name'    => esc_html__( 'Total Followers', 'socialsnap' ),
						'desc'    => esc_html__( 'Display Total Follower count from all follow networks.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_sf_button_followers' => array(
						'id'      => 'ss_sf_button_followers',
						'name'    => esc_html__( 'Network Followers', 'socialsnap' ),
						'desc'    => esc_html__( 'Display follow count on each network if possible.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => true,
					),
					'ss_sf_button_labels'    => array(
						'id'      => 'ss_sf_button_labels',
						'name'    => esc_html__( 'Network Labels', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => true,
					),
					'ss_sf_button_scheme'    => array(
						'id'      => 'ss_sf_button_scheme',
						'name'    => esc_html__( 'Color Scheme', 'socialsnap' ),
						'type'    => 'radio',
						'desc'    => '',
						'options' => array(
							'default' => __( 'Network Colors', 'socialsnap' ),
							'light'   => __( 'Light', 'socialsnap' ),
							'dark'    => __( 'Dark', 'socialsnap' ),
						),
						'default' => 'default',
					),
				),
			),
		),

		'ss_social_follow_widget' => array(
			'type'   => 'subgroup',
			'id'     => 'ss_social_follow_widget',
			'name'   => esc_html__( 'Widget', 'socialsnap' ),
			'fields' => array(),
		),
	),
	'ss_click_tweet'        => array(
		'id'     => 'ss_click_tweet',
		'name'   => esc_html__( 'Click to Tweet', 'socialsnap' ),
		'desc'   => esc_html__( 'Default settings for the Click To Tweet element.', 'socialsnap' ),
		'icon'   => 'twitter-outline',
		'fields' => array(
			'ss_ctt_include_via'   => array(
				'id'      => 'ss_ctt_include_via',
				'name'    => esc_html__( 'Include via @username', 'socialsnap' ),
				'desc'    => esc_html__( 'Twitter username from Social Identity tab will be appended to the end of the Tweet with the text “via @username”.', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => true,
			),
			'ss_ctt_include_link'  => array(
				'id'      => 'ss_ctt_include_link',
				'name'    => esc_html__( 'Include page link', 'socialsnap' ),
				'desc'    => esc_html__( 'Page link where the Click to Tweet is located will be appended to the Tweet.', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => true,
			),
			'ss_ctt_related'       => array(
				'id'      => 'ss_ctt_related',
				'name'    => esc_html__( 'Related Accounts', 'socialsnap' ),
				'desc'    => esc_html__( 'Suggest up to two Twitter @usernames related to the Tweet. Twitter may suggest these accounts to follow after the user posts their Tweet.', 'socialsnap' ),
				'type'    => 'ctt_related',
				'default' => true,
			),
			'ss_ctt_hide_mobile'   => array(
				'id'      => 'ss_ctt_hide_mobile',
				'name'    => esc_html__( 'Hide On Mobile', 'socialsnap' ),
				'type'    => 'toggle',
				'default' => false,
			),
			'ss_ctt_preview_style' => array(
				'id'      => 'ss_ctt_preview_style',
				'name'    => esc_html__( 'Default Style', 'socialsnap' ),
				'desc'    => __( 'Set default style for Click to Tweet. You can also choose individual style per shortcode / block.', 'socialsnap' ),
				'type'    => 'radio',
				'options' => array(
					'1' => __( 'Style 1', 'socialsnap' ),
					'2' => __( 'Style 2', 'socialsnap' ),
					'3' => __( 'Style 3', 'socialsnap' ),
					'4' => __( 'Style 4', 'socialsnap' ),
					'5' => __( 'Style 5', 'socialsnap' ),
					'6' => __( 'Style 6', 'socialsnap' ),
				),
				'default' => '1',
			),
		),
	),
	'ss_meta_tags'          => array(
		'id'       => 'ss_meta_tags',
		'name'     => esc_html__( 'Social Meta', 'socialsnap' ),
		'desc'     => esc_html__( 'If enabled, adds Twitter Cards and Open Graph meta tags for more effective and efficient social sharing, search results and better SEO.', 'socialsnap' ),
		'pro'      => true,
		'pro_info' => esc_html__( 'Control how your posts look when shared on social media. Specify custom images, titles and descriptions.', 'socialsnap' ),
		'icon'     => 'api',
		'fields'   => array(),
	),
	'ss_social_identity'    => array(
		'id'     => 'ss_social_identity',
		'name'   => esc_html__( 'Social Identity', 'socialsnap' ),
		'icon'   => 'social-id',
		'type'   => 'group',
		'fields' => array(
			'ss_social_identity_twitter' => array(
				'id'          => 'ss_social_identity_twitter',
				'name'        => __( 'X (Twitter)', 'socialsnap' ),
				'parent_name' => __( 'Social Identity', 'socialsnap' ),
				'desc'        => __( 'Enter your Twitter info here.', 'socialsnap' ),
				'fields'      => array(
					'ss_twitter_username' => array(
						'id'          => 'ss_twitter_username',
						'name'        => esc_html__( 'X (Twitter) Username', 'socialsnap' ),
						'desc'        => __( 'Enter your Twitter @username. This is used for Twitter share functionality (via @username).', 'socialsnap' ),
						'type'        => 'text',
						'placeholder' => '@username',
					),
				),
			),
		),
	),
	'ss_advanced_settings'  => array(
		'id'     => 'ss_advanced_settings',
		'name'   => esc_html__( 'Advanced', 'socialsnap' ),
		'icon'   => 'general',
		'type'   => 'group',
		'fields' => array(
			'ss_analytics_tracking'   => array(
				'id'          => 'ss_analytics_tracking',
				'name'        => esc_html__( 'Analytics Tracking', 'socialsnap' ),
				'desc'        => __( 'Connect social share buttons with Google Analytics to obtain additional insights on how users interact with your website.', 'socialsnap' ),
				'pro'         => true,
				'parent_name' => __( 'Advanced Settings', 'socialsnap' ),
				'fields'      => array(),
			),
			'ss_share_count_recovery' => array(
				'id'          => 'ss_share_count_recovery',
				'name'        => esc_html__( 'Share Count Recovery', 'socialsnap' ),
				'desc'        => __( 'Recover your social share count after changing link structure or switching to SSL.', 'socialsnap' ),
				'parent_name' => __( 'Advanced Settings', 'socialsnap' ),
				'type'        => 'subgroup',
				'pro'         => true,
				'fields'      => array(),
			),
			'ss_link_shortening'      => array(
				'id'          => 'ss_link_shortening',
				'name'        => esc_html__( 'Link Shortening', 'socialsnap' ),
				'desc'        => __( 'Automatically shorten all links on share buttons. ', 'socialsnap' ),
				'pro'         => true,
				'parent_name' => __( 'Advanced Settings', 'socialsnap' ),
				'type'        => 'subgroup',
				'fields'      => array(),
			),
			'ss_plugin_migration'     => array(
				'id'          => 'ss_plugin_migration',
				'name'        => __( 'Plugin Migration', 'socialsnap' ),
				'desc'        => __( 'Import settings from other social plugins. ', 'socialsnap' ),
				'parent_name' => __( 'Advanced', 'socialsnap' ),
				'fields'      => apply_filters( 'socialsnap_plugin_migration', array() ),
			),
			'ss_plugin_data'          => array(
				'id'          => 'ss_plugin_data',
				'name'        => __( 'Plugin Data', 'socialsnap' ),
				'parent_name' => __( 'Advanced', 'socialsnap' ),
				'type'        => 'subgroup',
				'fields'      => array(
					'ss_remove_signature'        => array(
						'id'      => 'ss_remove_signature',
						'name'    => esc_html__( 'Remove “Powered by Social Snap” link', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => true,
					),
					'ss_remove_notices'          => array(
						'id'      => 'ss_remove_notices',
						'name'    => esc_html__( 'Hide plugin announcements and update details.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_uninstall_delete'        => array(
						'id'      => 'ss_uninstall_delete',
						'name'    => esc_html__( 'Remove Social Snap related data on plugin deletion', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_uninstall_complete_note' => array(
						'id'      => 'ss_uninstall_complete_note',
						'name'    => esc_html__( 'Warning: ', 'socialsnap' ),
						'desc'    => esc_html__( 'This will remove all stored data.', 'socialsnap' ),
						'type'    => 'note',
						'default' => false,
					),
				),
			),
			'ss_gdpr'                 => array(
				'id'          => 'ss_gdpr',
				'name'        => __( 'GDPR Compliance', 'socialsnap' ),
				'parent_name' => __( 'Advanced', 'socialsnap' ),
				'fields'      => array(
					'ss_remove_cookies'   => array(
						'id'      => 'ss_remove_cookies',
						'name'    => esc_html__( 'Disable User Cookies', 'socialsnap' ),
						'desc'    => __( 'Check this to disable user cookies. This will cause innacurate view counts.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
					'ss_remove_user_data' => array(
						'id'      => 'ss_remove_user_data',
						'name'    => esc_html__( 'Disable User Data', 'socialsnap' ),
						'desc'    => __( 'Check this to disable storing the user IP address. This will cause innacurate click share counts.', 'socialsnap' ),
						'type'    => 'toggle',
						'default' => false,
					),
				),
			),

		),
	),
	'ss_import'             => array(
		'id'     => 'ss_import',
		'name'   => esc_html__( 'Import / Export', 'socialsnap' ),
		'icon'   => 'import',
		'fields' => array(
			array(
				'id'   => 'ss_ie_export',
				'name' => esc_html__( 'Export Settings', 'socialsnap' ),
				'desc' => '',
				'type' => 'export',
			),
			array(
				'id'   => 'ss_ie_import',
				'name' => esc_html__( 'Import Settings', 'socialsnap' ),
				'desc' => '',
				'type' => 'import',
			),
			array(
				'id'   => 'ss_ie_restore',
				'name' => esc_html__( 'Reset Settings', 'socialsnap' ),
				'desc' => __( 'Reset Social Snap settings to default values.', 'socialsnap' ),
				'type' => 'restore',
			),
			array(
				'id'   => 'ss_ie_info',
				'name' => esc_html__( 'Note:', 'socialsnap' ),
				'desc' => __( 'The export contains plugin settings only.', 'socialsnap' ),
				'type' => 'note',
			),
		),
	),
	'ss_social_login'       => array(
		'id'     => 'ss_social_login',
		'name'   => esc_html__( 'Social Login', 'socialsnap' ),
		'type'   => 'subgroup',
		'desc'   => __( 'Allow your visitors to log in using their favorite social accounts.', 'socialsnap' ),
		'pro'    => true,
		'icon'   => 'login',
		'fields' => array(
			'ss_social_login_note' => array(
				'id'   => 'ss_social_login_note',
				'name' => esc_html__( 'Note:', 'socialsnap' ),
				// translators: %1$s and %2$s are opening and closing anchor tags.
				'desc' => sprintf( __( 'Install Social Login from %1$sAddons%2$s.', 'socialsnap' ), '<a href="' . admin_url( 'admin.php?page=socialsnap-addons' ) . '">', '</a>' ),
				'type' => 'note',
			),
		),
	),
	'ss_boost_old_posts'    => array(
		'id'     => 'ss_boost_old_posts',
		'name'   => esc_html__( 'Boost Old Posts', 'socialsnap' ),
		'desc'   => __( 'Revive your old posts by automatically sharing them on Twitter and LinkedIn.', 'socialsnap' ),
		'icon'   => 'analytics',
		'pro'    => true,
		'fields' => array(
			'ss_bop_note' => array(
				'id'   => 'ss_bop_note',
				'name' => esc_html__( 'Note:', 'socialsnap' ),
				// translators: %1$s and %2$s are opening and closing anchor tags.
				'desc' => sprintf( __( 'Install Boost Old Posts from %1$sAddons%2$s.', 'socialsnap' ), '<a href="' . admin_url( 'admin.php?page=socialsnap-addons' ) . '">', '</a>' ),
				'type' => 'note',
			),
		),
	),
	'ss_social_auto_poster' => array(
		'id'     => 'ss_social_auto_poster',
		'name'   => esc_html__( 'Social Auto-Poster', 'socialsnap' ),
		'desc'   => __( 'Automatically share your new posts to Twitter and LinkedIn.', 'socialsnap' ),
		'icon'   => 'plus',
		'pro'    => true,
		'fields' => array(
			'ss_bop_note' => array(
				'id'   => 'ss_bop_note',
				'name' => esc_html__( 'Note:', 'socialsnap' ),
				// translators: %1$s and %2$s are opening and closing anchor tags.
				'desc' => sprintf( __( 'Install Social Auto-Poster from %1$sAddons%2$s.', 'socialsnap' ), '<a href="' . admin_url( 'admin.php?page=socialsnap-addons' ) . '">', '</a>' ),
				'type' => 'note',
			),
		),
	),
);

if ( ! socialsnap()->pro ) {
	$config['ss_social_follow']['fields']['ss_social_follow_networks_display']['fields']['ss_follow_update_pro_notice'] = array(
		'id'   => 'ss_follow_update_pro_notice',
		'name' => esc_html__( 'Go Premium', 'socialsnap' ),
		'desc' => __( 'Get access to 40+ networks, automatic counters and more awesome features.', 'socialsnap' ),
		'type' => 'info',
	);
}

return apply_filters( 'socialsnap_settings_config', $config );
