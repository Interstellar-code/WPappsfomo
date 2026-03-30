<?php
$config = array(
	'ss-socialsnap-main' => array(
		'title'     => esc_html__( 'Social Snap Options', 'socialsnap' ),
		'id'        => 'ss-socialsnap-main',
		'post_type' => socialsnap_get_post_types_ids(),
		'context'   => 'normal',
		'priority'  => 'high',
		'options'   => array(
			'ss-ss-metabox' => array(
				'title'   => esc_html__( 'Social Sharing', 'socialsnap' ),
				'id'      => 'ss-ss-metabox',
				'icon'    => 'share',
				'options' => array(
					'ss_social_share_disable' => array(
						'id'      => 'ss_social_share_disable',
						'name'    => esc_html__( 'Disable Share Buttons', 'socialsnap' ),
						'type'    => 'editor_toggle',
						'desc'    => __( 'Hide share buttons on this page. This will not hide the Social Share shortcode.', 'socialsnap' ),
						'default' => false,
					),
				),
			),
		),
	),
);

return apply_filters( 'socialsnap_metaboxes_config', $config );
