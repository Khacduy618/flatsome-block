<?php
/**
 * Shortcode Render Handler: [uxb_smart_product_slider] and [uxb_smart_product_tab]
 * Supports child tabs with custom titles, individual product sources, and custom filters.
 * Initial HTML only renders Tab 1; subsequent tabs are created on-demand in DOM upon click.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue CSS and JS assets for Smart Product Slider
 */
function uxb_enqueue_smart_slider_assets() {
	wp_enqueue_style(
		'uxb-smart-slider',
		FBM_URL . 'assets/css/smart-slider.css',
		array(),
		FBM_VERSION
	);

	wp_enqueue_script(
		'uxb-smart-slider',
		FBM_URL . 'assets/js/smart-slider.js',
		array( 'jquery' ),
		FBM_VERSION,
		true
	);

	wp_localize_script(
		'uxb-smart-slider',
		'uxb_smart_slider_params',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'uxb_smart_slider_nonce' ),
		)
	);
}

add_action( 'wp_enqueue_scripts', 'uxb_enqueue_smart_slider_assets' );
add_action( 'ux_builder_enqueue_scripts', 'uxb_enqueue_smart_slider_assets' );

/**
 * Child tab shortcode handler: [uxb_smart_product_tab]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Inner content.
 * @return string Empty string as child tabs are rendered by parent container.
 */
function uxb_smart_product_tab_shortcode( $atts, $content = '' ) {
	$a = shortcode_atts(
		array(
			'title'          => __( 'New Tab', 'flatsome-block-manager' ),
			'source'         => 'recent',
			'cat'            => '',
			'orderby'        => 'date',
			'order'          => 'DESC',
			'posts_per_page' => 8,
			'in_stock'       => 'false',
			'price_min'      => '',
			'price_max'      => '',
			'tags'           => '',
			'exclude'        => '',
			'animate'        => '',
			'animate_speed'  => '',
		),
		$atts,
		'uxb_smart_product_tab'
	);

	if ( isset( $GLOBALS['uxb_smart_tabs'] ) && is_array( $GLOBALS['uxb_smart_tabs'] ) ) {
		$GLOBALS['uxb_smart_tabs'][] = array(
			'title'          => sanitize_text_field( $a['title'] ),
			'source'         => sanitize_key( $a['source'] ),
			'cat'            => sanitize_text_field( $a['cat'] ),
			'orderby'        => sanitize_key( $a['orderby'] ),
			'order'          => sanitize_key( $a['order'] ),
			'posts_per_page' => max( 1, absint( $a['posts_per_page'] ) ),
			'in_stock'       => sanitize_text_field( $a['in_stock'] ),
			'price_min'      => sanitize_text_field( $a['price_min'] ),
			'price_max'      => sanitize_text_field( $a['price_max'] ),
			'tags'           => sanitize_text_field( $a['tags'] ),
			'exclude'        => sanitize_text_field( $a['exclude'] ),
			'animate'        => sanitize_text_field( $a['animate'] ),
			'animate_speed'  => sanitize_text_field( $a['animate_speed'] ),
		);
	}

	return '';
}

add_shortcode( 'uxb_smart_product_tab', 'uxb_smart_product_tab_shortcode' );

/**
 * Render shortcode [uxb_smart_product_slider]
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $content Inner content containing [uxb_smart_product_tab] elements.
 * @return string HTML output.
 */
function uxb_smart_product_slider_shortcode( $atts, $content = '' ) {
	uxb_enqueue_smart_slider_assets();

	$a = shortcode_atts(
		array(
			// Slider Layout
			'autoplay_speed'   => 2000,
			'columns'          => 4,
			'columns__md'      => 3,
			'columns__sm'      => 2,
			'slider_nav_style' => 'reveal',
			'slider_bullets'   => 'false',
			'animate'          => 'none',
			'animate_speed'    => 0.7,

			// Fallback options if no child tabs provided
			'source'           => 'recent',
			'cat'              => '',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'in_stock'         => 'false',
			'price_min'        => '',
			'price_max'        => '',
			'tags'             => '',
			'exclude'          => '',
			'posts_per_page'   => 8,

			// Advanced
			'class'            => '',
			'visibility'       => '',
		),
		$atts,
		'uxb_smart_product_slider'
	);

	$autoplay_speed = absint( $a['autoplay_speed'] );
	if ( $autoplay_speed <= 0 ) {
		$autoplay_speed = 2000;
	}

	$columns    = max( 1, min( 6, absint( $a['columns'] ? $a['columns'] : 4 ) ) );
	$columns_md = max( 1, min( 4, absint( $a['columns__md'] ? $a['columns__md'] : 3 ) ) );
	$columns_sm = max( 1, min( 3, absint( $a['columns__sm'] ? $a['columns__sm'] : 2 ) ) );

	$layout_params = array(
		'columns'          => $columns,
		'columns__md'      => $columns_md,
		'columns__sm'      => $columns_sm,
		'autoplay_speed'   => $autoplay_speed,
		'slider_nav_style' => $a['slider_nav_style'],
		'slider_bullets'   => $a['slider_bullets'],
		'animate'          => sanitize_text_field( $a['animate'] ),
		'animate_speed'    => ! empty( $a['animate_speed'] ) ? floatval( $a['animate_speed'] ) : 0.7,
	);

	// Reset child tab registry and execute inner shortcodes
	$GLOBALS['uxb_smart_tabs'] = array();
	if ( ! empty( $content ) ) {
		do_shortcode( $content );
	}

	$wrapper_id = 'uxb-sps-' . wp_rand();

	$wrapper_classes = array( 'uxb-smart-slider-wrapper' );
	if ( ! empty( $a['class'] ) ) {
		foreach ( preg_split( '/\s+/', (string) $a['class'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
			$wrapper_classes[] = sanitize_html_class( $c );
		}
	}
	if ( ! empty( $a['visibility'] ) ) {
		foreach ( preg_split( '/\s+/', (string) $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $v ) {
			$wrapper_classes[] = sanitize_html_class( $v );
		}
	}

	$tab_items = array();

	// Case 1: Custom Child Tabs Mode
	if ( ! empty( $GLOBALS['uxb_smart_tabs'] ) ) {
		foreach ( $GLOBALS['uxb_smart_tabs'] as $idx => $tab_data ) {
			if ( empty( $tab_data['animate'] ) ) {
				$tab_data['animate'] = $layout_params['animate'];
			}
			if ( empty( $tab_data['animate_speed'] ) ) {
				$tab_data['animate_speed'] = $layout_params['animate_speed'];
			}
			$tab_params = array_merge( $layout_params, $tab_data );
			$tab_items[] = array(
				'id'     => $wrapper_id . '-tab-' . $idx,
				'title'  => ! empty( $tab_data['title'] ) ? $tab_data['title'] : sprintf( __( 'Tab %d', 'flatsome-block-manager' ), $idx + 1 ),
				'params' => $tab_params,
			);
		}
	} else {
		// Case 2: Fallback Category Tabs Mode
		$cat_raw = array_filter( array_map( 'trim', explode( ',', (string) $a['cat'] ) ) );
		$terms   = array();

		if ( ! empty( $cat_raw ) ) {
			foreach ( $cat_raw as $c ) {
				$term = is_numeric( $c ) ? get_term( (int) $c, 'product_cat' ) : get_term_by( 'slug', $c, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$terms[] = $term;
				}
			}
		} else {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'number'     => 4,
				)
			);
		}

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			if ( current_user_can( 'edit_posts' ) ) {
				return '<div class="uxb-no-products"><p>' . esc_html__( 'Smart Product Slider: Please add tabs or select categories.', 'flatsome-block-manager' ) . '</p></div>';
			}
			return '';
		}

		$fallback_query_params = array_merge(
			$layout_params,
			array(
				'source'         => $a['source'],
				'orderby'        => $a['orderby'],
				'order'          => $a['order'],
				'in_stock'       => $a['in_stock'],
				'price_min'      => $a['price_min'],
				'price_max'      => $a['price_max'],
				'tags'           => $a['tags'],
				'exclude'        => $a['exclude'],
				'posts_per_page' => max( 1, absint( $a['posts_per_page'] ) ),
			)
		);

		foreach ( array_values( $terms ) as $idx => $term ) {
			$tab_params = array_merge(
				$fallback_query_params,
				array(
					'cat'    => (string) $term->term_id,
					'cat_id' => $term->term_id,
				)
			);
			$tab_items[] = array(
				'id'     => $wrapper_id . '-cat-' . $term->term_id,
				'title'  => $term->name,
				'params' => $tab_params,
			);
		}
	}

	ob_start();
	?>
	<div id="<?php echo esc_attr( $wrapper_id ); ?>" 
		 class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>">
		
		<!-- 1. Tabs Navigation Bar -->
		<div class="uxb-tab-nav-wrapper">
			<ul class="uxb-tab-nav" role="tablist">
				<?php foreach ( $tab_items as $index => $item ) : 
					$is_first = ( 0 === $index );
				?>
					<li class="uxb-tab-item" role="presentation">
						<button type="button" 
								class="uxb-tab-btn<?php echo $is_first ? ' active' : ''; ?>" 
								role="tab" 
								aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>" 
								aria-controls="<?php echo esc_attr( $item['id'] ); ?>" 
								data-target="#<?php echo esc_attr( $item['id'] ); ?>"
								data-tab-params="<?php echo esc_attr( wp_json_encode( $item['params'] ) ); ?>">
							<span class="uxb-tab-title"><?php echo esc_html( $item['title'] ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- 2. Slider Panels Container (Only Tab 1 pre-loaded; subsequent tabs are created on-demand in DOM upon click) -->
		<div class="uxb-tab-panels">
			<?php if ( ! empty( $tab_items ) ) : 
				$first_tab     = $tab_items[0];
				$first_animate = ! empty( $first_tab['params']['animate'] ) && 'none' !== $first_tab['params']['animate'] ? $first_tab['params']['animate'] : '';
				$first_speed   = ! empty( $first_tab['params']['animate_speed'] ) ? floatval( $first_tab['params']['animate_speed'] ) : 0.7;
				$animate_attr  = $first_animate ? ' data-animate="' . esc_attr( $first_animate ) . '"' : '';
				$speed_style   = $first_animate && $first_speed ? ' style="--uxb-anim-speed: ' . esc_attr( $first_speed ) . 's;"' : '';
			?>
				<div id="<?php echo esc_attr( $first_tab['id'] ); ?>" 
					 class="uxb-tab-panel active" 
					 data-loaded="true" 
					<?php echo $animate_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $speed_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					 role="tabpanel">
					<?php echo uxb_render_product_slider_markup( $first_tab['params'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

	</div>
	<?php
	return ob_get_clean();
}

add_shortcode( 'uxb_smart_product_slider', 'uxb_smart_product_slider_shortcode' );
