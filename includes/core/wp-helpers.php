<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wp_custom_theme_style_for_pages' ) ) {
	/**
	 * Custom Admin styles
	 * apply custom styles for pages section in the Dashboard section
	 */
	function wp_custom_theme_style_for_pages() {
		$screen = get_current_screen();
		if ( isset( $screen->post_type ) && $screen->post_type === 'page' ) {
			echo '<style>
              #the-list tr.level-0 td.title .row-title{ color: #024e7d; letter-spacing: 0.0125rem; }
              #the-list tr.level-1 td.title .row-title{ font-weight: 400; }
              #the-list tr.level-2 td.title .row-title{ font-weight: 400; color: #405d89; font-size: 95% !important; }
              #the-list tr.level-3 td.title .row-title { font-weight: 400; color: #6079a6; font-size: 90% !important; }
            </style>';
		}
	}
}
add_action('admin_head', 'wp_custom_theme_style_for_pages');


if ( ! function_exists( 'wp_custom_remove_more_jump_link' ) ) {
	/**
	 * Remove #more anchor from posts
	 */
	function wp_custom_remove_more_jump_link($link){
		$offset = strpos($link, '#more-');
		if ( $offset !== false ) {
			$end = strpos( $link, '"', $offset );
			if ( $end !== false ) {
				$link = substr_replace( $link, '', $offset, $end - $offset );
			}
		}
		return $link;
	}
}
add_filter('the_content_more_link', 'wp_custom_remove_more_jump_link');


if ( ! function_exists( 'wp_custom_excerpt_more' ) ) {
	/**
	 * Custom excerpt length read more
	 */
	function wp_custom_excerpt_more( $more ) {
		$link = sprintf(
			'<a class="" href="%1$s" aria-label="%2$s">%3$s</a>',
			esc_url( get_permalink( get_the_ID() ) ),
			esc_attr( sprintf( __( 'Continue reading "%s"', 'wp-theme' ), get_the_title() ) ),
			esc_html__( 'Continue Reading', 'wp-theme' )
		);
		
		return '… ' . $link;
	}
}
add_filter( 'excerpt_more', 'wp_custom_excerpt_more' );


if ( ! function_exists( 'wp_custom_excerpt_length' ) ) {
	/**
	 * Custom excerpt length
	 */
	function wp_custom_excerpt_length($length){
		return apply_filters('wp_custom_excerpt_length', 25);
	}
}
add_filter('excerpt_length', 'wp_custom_excerpt_length', 20);


if ( ! function_exists( 'wp_custom_admin_logo_custom_url' ) ) {
	/**
	 * wp-admin logo site URL
	 */
	function wp_custom_admin_logo_custom_url(){
		return esc_url( home_url() );
	}
}
add_filter('login_headerurl', 'wp_custom_admin_logo_custom_url');


if ( ! function_exists( 'wp_custom_wordpress_filter_login_head' ) ) {
	/**
	 * /wp-admin/ custom styling
	 */
	function wp_custom_wordpress_filter_login_head() {
		if ( has_custom_logo() ) {
			$logo     = wp_get_attachment_image_src( get_theme_mod( 'custom_logo' ), 'medium' );
			$bgImage  = esc_url( $logo[0] );
			$bgSize   = esc_url( $logo[1] ) . 'px auto';
			$bgWidth  = absint( $logo[1] ) . 'px';
			$bgHeight = absint( $logo[2] ) . 'px';
		} else {
			$logo     = apply_filters('wp_custom_wordpress_filter_login_head_logo', get_theme_file_uri( 'assets/images/custom-logo.png' ) );
			$bgImage  = esc_url( $logo );
			$bgSize   = 'contain';
			$bgWidth  = '300px';
			$bgHeight = '72px';
		}
		?>
		<style>
        body.login {
            background-color: oklch(96.8% 0.007 247.896);
            background-image: url('<?php echo get_background_image() ?>') !important;
            background-repeat: repeat;
            background-position: center center;
        }
        body.login h1 a {
            background-image: url(<?php echo $bgImage ?>);
            background-size: <?php echo $bgSize ?>;
            width: <?php echo $bgWidth ?>;
            height: <?php echo $bgHeight ?>
        }
		</style>
		<?php
	}
}
add_action( 'login_head', 'wp_custom_wordpress_filter_login_head', 100 );


if ( ! function_exists( 'wp_custom_theme_embed_handler_html' ) ) {
	/**
	 * Filter <iframe> CSS classes
	 */
	function wp_custom_theme_embed_handler_html( $cached_html, $url ) {
		$classes = apply_filters('wp_custom_theme_embed_handler_html_classes', [ 'aspect-video' ] );
		
		if ( str_contains( $url, 'vimeo.com' ) ) {
			$classes[] = 'vimeo';
		}
		
		if ( str_contains( $url, 'youtube.com' ) ) {
			$classes[] = 'youtube';
		}
		
		$class_string = implode( ' ', $classes );
		
		if ( preg_match( '/<iframe.*class=["\']([^"\']*)["\']/', $cached_html, $matches ) ) {
			// Append classes
			$new_class = $matches[1] . ' ' . $class_string;
			$cached_html = str_replace( $matches[0], str_replace( $matches[1], $new_class, $matches[0] ), $cached_html );
		} else {
			// Add class attribute
			$cached_html = preg_replace( '/<iframe /', '<iframe class="' . esc_attr( $class_string ) . '" ', $cached_html, 1 );
		}
		
		return '<div class="' . esc_attr( $class_string ) . '">' . $cached_html . '</div>';
	}
}
add_filter('embed_oembed_html', 'wp_custom_theme_embed_handler_html', 100, 4);


if ( ! function_exists( 'wp_custom_gallery_grid' ) ) {
	/**
	 * Custom gallery classes format
	 */
	function wp_custom_gallery_grid( $output, $attrs, $instance ) {
		$attrs = array_merge( [ 'columns' => 3 ], $attrs );
		
		$columns = (int) $attrs['columns'];
		$images  = explode( ',', $attrs['ids'] );
		$size    = ! empty( $attrs['size'] ) ? $attrs['size'] : 'thumbnail';
		
		// Map WP columns to Tailwind grid-cols classes
		$column_classes = [
			1 => 'grid-cols-1',
			2 => 'grid-cols-2',
			3 => 'grid-cols-3',
			4 => 'grid-cols-4',
			5 => 'grid-cols-5',
			6 => 'grid-cols-6',
		];
		$grid_class = $column_classes[ $columns ] ?? 'grid-cols-3';
		
		$gallery_id = ( $instance < 10 ) ? 'gallery-0' . $instance : 'gallery-' . $instance;
		
		$gallery = '<div class="gallery" id="' . esc_attr( $gallery_id ) . '">';
		$gallery .= '<div class="grid gap-4 ' . esc_attr( $grid_class ) . '">';
		
		foreach ( $images as $image_id ) {
			$thumb_src = wp_get_attachment_image_src( $image_id, $size );
			if ( ! $thumb_src ) {
				continue;
			}
			
			$thumb_alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			$caption   = get_post( $image_id )->post_excerpt;
			
			switch ( $attrs['link'] ?? '' ) {
				case 'file':
					$link = wp_get_attachment_image_src( $image_id, 'large' );
					$attrs = [
						'href'         => esc_url( $link[0] ),
						'target'       => '_blank',
						'data-gallery' => 'gallery',
					];
					break;
				
				case 'none':
					$attrs = false;
					break;
				
				default:
					$attrs = [
						'href' => get_attachment_link( $image_id ),
					];
					break;
			}
			
			// Image block
			$gallery .= '<figure class="figure">';
			$gallery .= wp_custom_gallery_item( $thumb_src[0], $thumb_alt, $attrs );
			
			if ( $caption ) {
				$gallery .= '<figcaption class="figcaption">' . esc_html( $caption ) . '</figcaption>';
			}
			
			$gallery .= '</figure>';
		}
		
		$gallery .= '</div></div>';
		
		return $gallery;
	}
}
add_filter('post_gallery', 'wp_custom_gallery_grid', 10, 3);

function wp_custom_gallery_item( $src, $alt = '', $link_attrs = false ) {
	$custom_gallery_item_class = apply_filters('wp_custom_gallery_item_classes', 'img-fluid' );
	$img = '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" class="'. esc_attr( $custom_gallery_item_class ) .'" />';
	
	if ( $link_attrs && ! empty( $link_attrs['href'] ) ) {
		$attr_string = '';
		foreach ( $link_attrs as $name => $value ) {
			$attr_string .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}
		
		return '<a' . $attr_string . '>' . $img . '</a>';
	}
	
	return $img;
}



if ( ! function_exists( 'wp_custom_body_classes' ) ) {
	/**
	 * Adds custom classes to the array of body classes.
	 *
	 * @param array $classes Classes for the body element.
	 * @return array
	 */
	function wp_custom_body_classes( $classes ) {

		// Adds `singular` to singular pages, and `hfeed` to all other pages.
		$classes[] = is_singular() ? 'singular' : 'hfeed';
		
		return $classes;
	}
}
add_filter( 'body_class', 'wp_custom_body_classes' );