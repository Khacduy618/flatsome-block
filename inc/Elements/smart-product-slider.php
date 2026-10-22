<?php
/**
 * Register Custom UX Builder Elements:
 * - Parent Container: [uxb_smart_product_slider]
 * - Child Tab Element: [uxb_smart_product_tab]
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register [uxb_smart_product_slider] and [uxb_smart_product_tab] in UX Builder
 */
function uxb_register_smart_product_slider_element() {
	if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
		return;
	}

	// 1. Parent Element: Smart Product Slider (Container)
	add_ux_builder_shortcode(
		'uxb_smart_product_slider',
		array(
			'type'      => 'container',
			'name'      => __( 'Smart Product Tabs Slider', 'flatsome-block-manager' ),
			'category'  => __( 'Shop', 'flatsome-block-manager' ),
			'priority'  => 10,
			'thumbnail' => function_exists( 'flatsome_ux_builder_thumbnail' ) ? flatsome_ux_builder_thumbnail( 'tabs' ) : '',
			'allow'     => array( 'uxb_smart_product_tab' ),
			'template'  => '<div class="uxb-smart-slider-wrapper {{ shortcode.options.class }}"><content></content></div>',
			'children'  => array(
				'draggable'     => true,
				'addable_spots' => array( 'center' ),
			),
			'toolbar'   => array(
				'show_children_selector' => true,
				'show_on_child_active'   => true,
			),
			'presets'   => array(
				array(
					'name'    => __( '3 Custom Tabs', 'flatsome-block-manager' ),
					'content' => '[uxb_smart_product_slider columns="4" autoplay_speed="2000"]'
						. '[uxb_smart_product_tab title="Flash Sale" source="sale" posts_per_page="8"][/uxb_smart_product_tab]'
						. '[uxb_smart_product_tab title="Best Sellers" source="best_selling" posts_per_page="8"][/uxb_smart_product_tab]'
						. '[uxb_smart_product_tab title="New Arrivals" source="recent" posts_per_page="8"][/uxb_smart_product_tab]'
						. '[/uxb_smart_product_slider]',
				),
			),
			'options'   => array(
				'slider_layout' => array(
					'type'    => 'group',
					'heading' => __( 'Slider Layout', 'flatsome-block-manager' ),
					'options' => array(
						'autoplay_speed'   => array(
							'type'        => 'slider',
							'heading'     => __( 'Autoplay Speed (ms)', 'flatsome-block-manager' ),
							'param_name'  => 'autoplay_speed',
							'default'     => 2000,
							'min'         => 500,
							'max'         => 10000,
							'step'        => 100,
						),
						'columns'          => array(
							'type'       => 'slider',
							'heading'    => __( 'Columns (Desktop)', 'flatsome-block-manager' ),
							'param_name' => 'columns',
							'default'    => 4,
							'min'        => 1,
							'max'        => 6,
							'step'       => 1,
						),
						'columns__md'      => array(
							'type'       => 'slider',
							'heading'    => __( 'Columns (Tablet)', 'flatsome-block-manager' ),
							'param_name' => 'columns__md',
							'default'    => 3,
							'min'        => 1,
							'max'        => 4,
							'step'       => 1,
						),
						'columns__sm'      => array(
							'type'       => 'slider',
							'heading'    => __( 'Columns (Mobile)', 'flatsome-block-manager' ),
							'param_name' => 'columns__sm',
							'default'    => 2,
							'min'        => 1,
							'max'        => 3,
							'step'       => 1,
						),
						'slider_nav_style' => array(
							'type'       => 'select',
							'heading'    => __( 'Nav Arrow Style', 'flatsome-block-manager' ),
							'param_name' => 'slider_nav_style',
							'default'    => 'reveal',
							'options'    => array(
								'reveal' => __( 'Reveal on Hover', 'flatsome-block-manager' ),
								'circle' => __( 'Circle', 'flatsome-block-manager' ),
								'simple' => __( 'Simple', 'flatsome-block-manager' ),
							),
						),
						'slider_bullets'   => array(
							'type'       => 'checkbox',
							'heading'    => __( 'Show Bullets / Dots', 'flatsome-block-manager' ),
							'param_name' => 'slider_bullets',
							'default'    => 'false',
						),
						'animate'          => array(
							'type'       => 'select',
							'heading'    => __( 'Animate', 'flatsome-block-manager' ),
							'param_name' => 'animate',
							'default'    => 'none',
							'options'    => array(
								'none'         => __( 'None', 'flatsome-block-manager' ),
								'fadeInLeft'   => __( 'Fade In Left', 'flatsome-block-manager' ),
								'fadeInRight'  => __( 'Fade In Right', 'flatsome-block-manager' ),
								'fadeInUp'     => __( 'Fade In Up', 'flatsome-block-manager' ),
								'fadeInDown'   => __( 'Fade In Down', 'flatsome-block-manager' ),
								'bounceIn'     => __( 'Bounce In', 'flatsome-block-manager' ),
								'bounceInUp'   => __( 'Bounce In Up', 'flatsome-block-manager' ),
								'bounceInDown' => __( 'Bounce In Down', 'flatsome-block-manager' ),
								'bounceInLeft' => __( 'Bounce In Left', 'flatsome-block-manager' ),
								'bounceInRight'=> __( 'Bounce In Right', 'flatsome-block-manager' ),
								'blurIn'       => __( 'Blur In', 'flatsome-block-manager' ),
								'flipInX'      => __( 'Flip In X', 'flatsome-block-manager' ),
								'flipInY'      => __( 'Flip In Y', 'flatsome-block-manager' ),
							),
						),
						'animate_speed'    => array(
							'type'        => 'slider',
							'heading'     => __( 'Animate Speed', 'flatsome-block-manager' ),
							'param_name'  => 'animate_speed',
							'default'     => 0.7,
							'min'         => 0.1,
							'max'         => 3,
							'step'        => 0.1,
							'unit'        => 's',
							'conditions'  => 'animate !== "none"',
						),
					),
				),
				'advanced_options' => array(
					'type'    => 'group',
					'heading' => __( 'Advanced', 'flatsome-block-manager' ),
					'options' => array(
						'class'      => array(
							'type'       => 'textfield',
							'heading'    => __( 'CSS Class', 'flatsome-block-manager' ),
							'param_name' => 'class',
							'default'    => '',
						),
						'visibility' => array(
							'type'       => 'select',
							'heading'    => __( 'Visibility', 'flatsome-block-manager' ),
							'param_name' => 'visibility',
							'default'    => '',
							'options'    => array(
								''                 => __( 'All Devices', 'flatsome-block-manager' ),
								'hide-for-small'   => __( 'Hide on Mobile', 'flatsome-block-manager' ),
								'hide-for-medium'  => __( 'Hide on Tablet & Mobile', 'flatsome-block-manager' ),
								'show-for-small'   => __( 'Show on Mobile Only', 'flatsome-block-manager' ),
								'show-for-medium'  => __( 'Show on Tablet Only', 'flatsome-block-manager' ),
							),
						),
					),
				),
			),
		)
	);

	// 2. Child Element: Individual Tab Slide
	add_ux_builder_shortcode(
		'uxb_smart_product_tab',
		array(
			'type'     => 'container',
			'name'     => __( 'Product Tab Slide', 'flatsome-block-manager' ),
			'category' => __( 'Shop', 'flatsome-block-manager' ),
			'require'  => array( 'uxb_smart_product_slider' ),
			'hidden'   => false,
			'wrap'     => false,
			'template' => '<div class="uxb-admin-tab-preview" style="padding: 12px 16px; margin: 6px 0; border: 1px solid #e0e0e0; background: #ffffff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"><strong style="color: #222; font-size: 14px;"><i class=\"icon-tag\"></i> {{ shortcode.options.title || "New Tab" }}</strong> &bull; <span style="color: #666; font-size: 12px;">Source: {{ shortcode.options.source || "recent" }}</span> &bull; <span style="color: #666; font-size: 12px;">Count: {{ shortcode.options.posts_per_page || 8 }}</span><span ng-if="shortcode.options.animate"> &bull; <span style="color: #0073aa; font-size: 12px; font-weight: 600;"><i class=\"icon-star\"></i> {{ shortcode.options.animate }}</span></span></div>',
			'info'     => '{{ title }} ({{ source }})<span ng-if="animate"> &bull; {{ animate }}</span>',
			'options'  => array(
				// Tab Title
				'title'          => array(
					'type'       => 'textfield',
					'heading'    => __( 'Tab Title (Custom Name)', 'flatsome-block-manager' ),
					'param_name' => 'title',
					'default'    => __( 'New Tab', 'flatsome-block-manager' ),
					'auto_focus' => true,
				),

				// Product Source for this slide
				'source'         => array(
					'type'       => 'select',
					'heading'    => __( 'Product Source', 'flatsome-block-manager' ),
					'param_name' => 'source',
					'default'    => 'recent',
					'options'    => array(
						'recent'       => __( 'Recent / Latest', 'flatsome-block-manager' ),
						'featured'     => __( 'Featured', 'flatsome-block-manager' ),
						'sale'         => __( 'On Sale', 'flatsome-block-manager' ),
						'best_selling' => __( 'Best Selling', 'flatsome-block-manager' ),
						'top_rated'    => __( 'Top Rated', 'flatsome-block-manager' ),
					),
				),

				// Category Filter for this slide
				'cat'            => array(
					'type'        => 'select',
					'heading'     => __( 'Filter by Category', 'flatsome-block-manager' ),
					'description' => __( 'Leave empty to include all categories', 'flatsome-block-manager' ),
					'param_name'  => 'cat',
					'default'     => '',
					'config'      => array(
						'multiple'    => true,
						'placeholder' => __( 'Select categories...', 'flatsome-block-manager' ),
						'termSelect'  => array(
							'post_type'  => 'product',
							'taxonomies' => 'product_cat',
						),
					),
				),

				// Sorting criteria for this slide
				'orderby'        => array(
					'type'       => 'select',
					'heading'    => __( 'Order By', 'flatsome-block-manager' ),
					'param_name' => 'orderby',
					'default'    => 'date',
					'options'    => array(
						'date'       => __( 'Date (Newest)', 'flatsome-block-manager' ),
						'price'      => __( 'Price', 'flatsome-block-manager' ),
						'popularity' => __( 'Popularity / Sales', 'flatsome-block-manager' ),
						'rating'     => __( 'Average Rating', 'flatsome-block-manager' ),
						'title'      => __( 'Title', 'flatsome-block-manager' ),
						'menu_order' => __( 'Menu Order', 'flatsome-block-manager' ),
						'rand'       => __( 'Random', 'flatsome-block-manager' ),
					),
				),
				'order'          => array(
					'type'       => 'select',
					'heading'    => __( 'Sort Direction', 'flatsome-block-manager' ),
					'param_name' => 'order',
					'default'    => 'DESC',
					'options'    => array(
						'DESC' => __( 'Descending', 'flatsome-block-manager' ),
						'ASC'  => __( 'Ascending', 'flatsome-block-manager' ),
					),
				),

				// Quantity for this slide
				'posts_per_page' => array(
					'type'       => 'slider',
					'heading'    => __( 'Products to Show', 'flatsome-block-manager' ),
					'param_name' => 'posts_per_page',
					'default'    => 8,
					'min'        => 2,
					'max'        => 36,
					'step'       => 1,
				),

				'animate'        => array(
					'type'        => 'select',
					'heading'     => __( 'Animate (Tab Override)', 'flatsome-block-manager' ),
					'description' => __( 'Select animation or inherit from parent slider', 'flatsome-block-manager' ),
					'param_name'  => 'animate',
					'default'     => '',
					'options'     => array(
						''             => __( 'Inherit from Slider', 'flatsome-block-manager' ),
						'none'         => __( 'None', 'flatsome-block-manager' ),
						'fadeInLeft'   => __( 'Fade In Left', 'flatsome-block-manager' ),
						'fadeInRight'  => __( 'Fade In Right', 'flatsome-block-manager' ),
						'fadeInUp'     => __( 'Fade In Up', 'flatsome-block-manager' ),
						'fadeInDown'   => __( 'Fade In Down', 'flatsome-block-manager' ),
						'bounceIn'     => __( 'Bounce In', 'flatsome-block-manager' ),
						'bounceInUp'   => __( 'Bounce In Up', 'flatsome-block-manager' ),
						'bounceInDown' => __( 'Bounce In Down', 'flatsome-block-manager' ),
						'bounceInLeft' => __( 'Bounce In Left', 'flatsome-block-manager' ),
						'bounceInRight'=> __( 'Bounce In Right', 'flatsome-block-manager' ),
						'blurIn'       => __( 'Blur In', 'flatsome-block-manager' ),
						'flipInX'      => __( 'Flip In X', 'flatsome-block-manager' ),
						'flipInY'      => __( 'Flip In Y', 'flatsome-block-manager' ),
					),
				),
				'animate_speed'  => array(
					'type'        => 'slider',
					'heading'     => __( 'Animate Speed (Override)', 'flatsome-block-manager' ),
					'description' => __( 'Tốc độ hiệu ứng xuất hiện riêng cho tab này (giây)', 'flatsome-block-manager' ),
					'param_name'  => 'animate_speed',
					'default'     => '',
					'min'         => 0.1,
					'max'         => 3,
					'step'        => 0.1,
					'unit'        => 's',
					'conditions'  => 'animate !== "" && animate !== "none"',
				),

				// Advanced Filters for this slide
				'filter_group'   => array(
					'type'    => 'group',
					'heading' => __( 'Filters for this Tab', 'flatsome-block-manager' ),
					'options' => array(
						'in_stock'  => array(
							'type'       => 'checkbox',
							'heading'    => __( 'Only In-Stock', 'flatsome-block-manager' ),
							'param_name' => 'in_stock',
							'default'    => 'false',
						),
						'price_min' => array(
							'type'        => 'textfield',
							'heading'     => __( 'Min Price', 'flatsome-block-manager' ),
							'param_name'  => 'price_min',
							'default'     => '',
							'placeholder' => 'e.g. 50',
						),
						'price_max' => array(
							'type'        => 'textfield',
							'heading'     => __( 'Max Price', 'flatsome-block-manager' ),
							'param_name'  => 'price_max',
							'default'     => '',
							'placeholder' => 'e.g. 200',
						),
						'tags'      => array(
							'type'       => 'select',
							'heading'    => __( 'Filter by Tag', 'flatsome-block-manager' ),
							'param_name' => 'tags',
							'default'    => '',
							'config'     => array(
								'multiple'    => true,
								'placeholder' => __( 'Select tags...', 'flatsome-block-manager' ),
								'termSelect'  => array(
									'post_type'  => 'product',
									'taxonomies' => 'product_tag',
								),
							),
						),
						'exclude'   => array(
							'type'        => 'textfield',
							'heading'     => __( 'Exclude IDs', 'flatsome-block-manager' ),
							'description' => __( 'Comma-separated product IDs to exclude', 'flatsome-block-manager' ),
							'param_name'  => 'exclude',
							'default'     => '',
						),
					),
				),
			),
		)
	);
}

add_action( 'ux_builder_setup', 'uxb_register_smart_product_slider_element', 20 );
