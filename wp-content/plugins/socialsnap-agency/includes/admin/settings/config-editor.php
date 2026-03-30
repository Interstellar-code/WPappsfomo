<?php
$ss_social_share = array(
	'title'   => esc_html__( 'Social Share', 'socialsnap' ),
	'id'      => 'ss-social-share-editor',
	'icon'    => 'share',
	'options' => array(
		'ss_ss_networks'       => array(
			'id'      => 'ss_ss_networks',
			'name'    => esc_html__( 'Social Networks', 'socialsnap' ),
			'desc'    => __( 'Semicolon separated list of networks. Leave empty to use selected networks from settings panel. Find the list of all networks <a href="https://socialsnap.com/help/features/available-social-networks/" target="_blank">here</a>.<br/><br/><em>Example: facebook;twitter;linkedin</em>.', 'socialsnap' ),
			'type'    => 'editor_text',
			'default' => '',
		),
		'ss_ss_button_align'   => array(
			'id'      => 'ss_ss_button_align',
			'name'    => esc_html__( 'Alignment', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'left'      => __( 'Left', 'socialsnap' ),
				'center'    => __( 'Center', 'socialsnap' ),
				'right'     => __( 'Right', 'socialsnap' ),
				'stretched' => __( 'Stretched', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_position' ),
		),
		'ss_ss_button_shape'   => array(
			'id'      => 'ss_ss_button_shape',
			'name'    => esc_html__( 'Button Shape', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'rounded'   => __( 'Rounded', 'socialsnap' ),
				'circle'    => __( 'Circle', 'socialsnap' ),
				'rectangle' => __( 'Rectangle', 'socialsnap' ),
				'slanted'   => __( 'Slanted', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_button_shape' ),
		),
		'ss_ss_button_size'    => array(
			'id'      => 'ss_ss_button_size',
			'name'    => esc_html__( 'Button Size', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'small'   => __( 'Small', 'socialsnap' ),
				'regular' => __( 'Regular', 'socialsnap' ),
				'large'   => __( 'Large', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_button_size' ),
		),
		'ss_ss_button_labels'  => array(
			'id'      => 'ss_ss_button_labels',
			'name'    => esc_html__( 'Button Labels', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'none'  => __( 'None', 'socialsnap' ),
				'label' => __( 'Network Label', 'socialsnap' ),
				'count' => __( 'Share Count', 'socialsnap' ),
				'both'  => __( 'Both', 'socialsnap' ),
			),
			'default' => socialsnap_settings( 'ss_ss_inline_content_button_label' ),
		),
		'ss_ss_button_spacing' => array(
			'id'      => 'ss_ss_button_spacing',
			'desc'    => __( 'Additional spacing between buttons.', 'socialsnap' ),
			'name'    => esc_html__( 'Button Spacing', 'socialsnap' ),
			'type'    => 'editor_toggle',
			'default' => socialsnap_settings( 'ss_ss_inline_content_button_spacing' ),
		),
		'ss_ss_all_networks'   => array(
			'id'      => 'ss_ss_all_networks',
			'desc'    => __( 'Enable a button that allows users to choose from all available networks.', 'socialsnap' ),
			'name'    => esc_html__( 'All Networks', 'socialsnap' ),
			'type'    => 'editor_toggle',
			'default' => socialsnap_settings( 'ss_ss_inline_content_all_networks' ),
		),
		'ss_ss_hide_on_mobile' => array(
			'id'      => 'ss_ss_hide_on_mobile',
			'desc'    => __( 'Hide buttons on mobile devices.', 'socialsnap' ),
			'name'    => esc_html__( 'Hide on Mobile', 'socialsnap' ),
			'type'    => 'editor_toggle',
			'default' => socialsnap_settings( 'ss_ss_inline_content_hide_on_mobile' ),
		),
		'ss_ss_total_count'    => array(
			'id'      => 'ss_ss_total_count',
			'desc'    => __( 'Enable total shares counter.', 'socialsnap' ),
			'name'    => esc_html__( 'Total Shares', 'socialsnap' ),
			'type'    => 'editor_toggle',
			'default' => socialsnap_settings( 'ss_ss_inline_content_total_count' ),
		),
	),
);

$ss_click_to_tweet = array(
	'title'   => esc_html__( 'Click to Tweet', 'socialsnap' ),
	'id'      => 'ss-click-to-tweet-editor',
	'icon'    => 'twitter',
	'options' => array(
		'ss_ctt_tweet_content' => array(
			'id'        => 'ss_ctt_tweet_content',
			'name'      => esc_html__( 'Tweet Content', 'socialsnap' ),
			'desc'      => esc_html__( 'Text that will be posted on Twitter.', 'socialsnap' ),
			'type'      => 'editor_textarea',
			'default'   => '',
			'countchar' => 280,
			'ctt'       => true,
		),
		'ss_ctt_quote_content' => array(
			'id'      => 'ss_ctt_quote_content',
			'name'    => esc_html__( 'Quote Content', 'socialsnap' ),
			'desc'    => esc_html__( 'Text that will be shown on your website, inside the Click to Tweet.', 'socialsnap' ),
			'type'    => 'editor_textarea',
			'default' => '',
		),
		'ss_ctt_post_link'     => array(
			'id'      => 'ss_ctt_post_link',
			'name'    => esc_html__( 'Include Page Link', 'socialsnap' ),
			'desc'    => esc_html__( 'Link to this post will be appended to the Tweet.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_ctt_include_via'   => array(
			'id'      => 'ss_ctt_include_via',
			'name'    => esc_html__( 'Include via @username', 'socialsnap' ),
			'desc'    => esc_html__( 'Twitter username from Social Identity tab will be appended to the end of the Tweet with the text “via @username”', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_ctt_style'         => array(
			'id'      => 'ss_ctt_style',
			'name'    => esc_html__( 'Style', 'socialsnap' ),
			'desc'    => esc_html__( 'Choose Click to Tweet style.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Style 1', 'socialsnap' ),
				'2'       => __( 'Style 2', 'socialsnap' ),
				'3'       => __( 'Style 3', 'socialsnap' ),
				'4'       => __( 'Style 4', 'socialsnap' ),
				'5'       => __( 'Style 5', 'socialsnap' ),
				'6'       => __( 'Style 6', 'socialsnap' ),
			),
			'default' => 'default',
		),
	),
);

$ss_social_follow = array(
	'title'   => esc_html__( 'Social Follow', 'socialsnap' ),
	'id'      => 'ss-social-follow-editor',
	'icon'    => 'followers',
	'options' => array(
		'ss_sf_networks'         => array(
			'id'      => 'ss_sf_networks',
			'name'    => esc_html__( 'Social Networks', 'socialsnap' ),
			'desc'    => __( 'Semicolon separated list of networks. Leave empty to use all configured networks. Find the list of all networks <a href="https://socialsnap.com/help/features/available-social-networks/" target="_blank">here</a>.<br/><br/><em>Example: facebook;twitter;instagram</em>', 'socialsnap' ),
			'type'    => 'editor_text',
			'default' => '',
		),
		'ss_sf_button_size'      => array(
			'id'      => 'ss_sf_button_size',
			'name'    => esc_html__( 'Button Size', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'desc'    => '',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'small'   => __( 'Small', 'socialsnap' ),
				'regular' => __( 'Regular', 'socialsnap' ),
				'large'   => __( 'Large', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_columns'   => array(
			'id'      => 'ss_sf_button_columns',
			'name'    => esc_html__( 'Button Columns', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'desc'    => '',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( '1 Column', 'socialsnap' ),
				'2'       => __( '2 Columns', 'socialsnap' ),
				'3'       => __( '3 Columns', 'socialsnap' ),
				'4'       => __( '4 Columns', 'socialsnap' ),
				'5'       => __( '5 Columns', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_spacing'   => array(
			'id'      => 'ss_sf_button_spacing',
			'name'    => esc_html__( 'Button Spacing', 'socialsnap' ),
			'desc'    => esc_html__( 'Show additional spacing between follow buttons.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'With Spacing', 'socialsnap' ),
				'0'       => __( 'Without Spacing', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_vertical'  => array(
			'id'      => 'ss_sf_button_vertical',
			'name'    => esc_html__( 'Vertical Layout', 'socialsnap' ),
			'desc'    => esc_html__( 'Use vertical button style.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_total_followers'  => array(
			'id'      => 'ss_sf_total_followers',
			'name'    => esc_html__( 'Total Followers', 'socialsnap' ),
			'desc'    => esc_html__( 'Display Total Follower count from all follow networks.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_followers' => array(
			'id'      => 'ss_sf_button_followers',
			'name'    => esc_html__( 'Network Followers', 'socialsnap' ),
			'desc'    => esc_html__( 'Display follow count on each network if possible.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_labels'    => array(
			'id'      => 'ss_sf_button_labels',
			'name'    => esc_html__( 'Network Labels', 'socialsnap' ),
			'desc'    => esc_html__( 'Display the network label.', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'1'       => __( 'Yes', 'socialsnap' ),
				'0'       => __( 'No', 'socialsnap' ),
			),
			'default' => 'default',
		),
		'ss_sf_button_scheme'    => array(
			'id'      => 'ss_sf_button_scheme',
			'name'    => esc_html__( 'Color Scheme', 'socialsnap' ),
			'type'    => 'editor_dropdown',
			'desc'    => '',
			'options' => array(
				'default' => __( 'Default', 'socialsnap' ),
				'network' => __( 'Network Colors', 'socialsnap' ),
				'light'   => __( 'Light', 'socialsnap' ),
				'dark'    => __( 'Dark', 'socialsnap' ),
			),
			'default' => 'default',
		),
	),
);

$config = array(
	'ss-social-share-editor'   => $ss_social_share,
	'ss-social-follow-editor'  => $ss_social_follow,
	'ss-click-to-tweet-editor' => $ss_click_to_tweet,
);

return apply_filters( 'socialsnap_editor_config', $config );
