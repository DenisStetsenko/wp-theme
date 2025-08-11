<?php
/**
 * The header for our theme
 *
 * Includes the <head> section and starts the main page wrapper.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<!--[if IE]><meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'><![endif]-->
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="profile" href="http://gmpg.org/xfn/11">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

	<!-- Eliminate render-blocking resources google fonts FIX -->
	<link
			rel="preload"
			href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
			as="style"
			onload="this.onload=null;this.rel='stylesheet'">
	<noscript>
		<link
				href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap"
				rel="stylesheet"
				type="text/css">
	</noscript>
	<!-- / Eliminate render-blocking resources google fonts FIX -->

  <?php wp_head(); ?>
</head>

<body <?php body_class(); ?> itemscope itemtype="https://schema.org/WebPage" >
	<?php wp_body_open(); ?>

	<div id="page" class="min-h-screen">
		<a href="#wrapper" class="sr-only skip-link screen-reader-text">
			<?php
			/* translators: Hidden accessibility text. */
			esc_html_e( 'Skip to content', 'wp-theme' );
			?>
		</a>
	
		<?php
			get_template_part( 'template-parts/header/site', 'header' );
			get_template_part( 'template-parts/header/header', 'banner' );
		?>

		<div id="wrapper" class="site-content">