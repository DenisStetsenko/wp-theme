<?php
/**
 * Displays the site header.
 */

$wrapper_classes  = 'site-header';
$wrapper_classes .= has_custom_logo() ? ' has-custom-logo' : '';
$wrapper_classes .= is_front_page() 	? ' front-page-header' : ' inner-page-header ';
?>

<header id="masthead" class="<?php echo esc_attr( $wrapper_classes ) ?>">
	<nav id="main-nav" class="navbar">

		<div class="container">
			<?php get_template_part('template-parts/header/site-branding'); ?>
			
			<?php if ( has_nav_menu( 'header-primary' ) ) :
				wp_nav_menu( array(
					'theme_location'  => 'header-primary',
					'container' 			=> false,
					'container_class' => '',
					'menu_class'      => 'navbar-nav',
					'fallback_cb'     => false,
					'menu_id'         => 'header-primary-menu',
					'depth'           => 2
				) );
			endif;
			?>
		</div>

	</nav>
</header>
