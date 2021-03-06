<?php
/**
 * Functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Webpack + Typescript + Sass + WordPress
 * @since 1.0.0
 */

require get_template_directory() . '/utils.php';

require get_template_directory() . '/classes/class-post-types.php';

require get_template_directory() . '/classes/class-theme-options.php';

require get_template_directory() . '/classes/class-menus.php';

require get_template_directory() . '/classes/class-widgets.php';

require get_template_directory() . '/classes/class-blocks.php';

require get_template_directory() . '/blocks/tabs.php';

require get_template_directory() . '/blocks/tab.php';

new Post_Types();

new Theme_Options();

new Menus();

new Widgets();

new Blocks();

function theme_support() {
	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	// Set content-width.
	global $content_width;
	if ( ! isset( $content_width ) ) {
		$content_width = 1170;
	}

	// Add support for responsive embeds.
	add_theme_support( 'responsive-embeds' );

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
	 */
	add_theme_support( 'post-thumbnails' );

	// Set post thumbnail size.
	set_post_thumbnail_size( 1200, 9999 );

	// Add custom image size used in Cover Template.
	add_image_size( 'fullscreen', 1980, 9999 );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'script',
			'style',
		)
	);

	// Make theme available for translation.
	load_theme_textdomain( 'wordpress-starter' );

	// Add support for full and wide align images.
	add_theme_support( 'align-wide' );

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	// Add theme support for custom colors
	$colors = array();
	try {
		$theme  = json_decode( file_get_contents( get_template_directory() . '/theme.json' ), true );
		$colors = array_map(
			function ( $k, $v ) {
				return array(
					'name'  => $k,
					'slug'  => $k,
					'color' => $v,
				);
			},
			array_keys( $theme['color-map'] ),
			$theme['color-map'],
		);

	} catch ( Exception $e ) {
		error_log( $e->getMessage() );
	}

	add_theme_support(
		'editor-color-palette',
		$colors,
	);
}

add_action( 'after_setup_theme', 'theme_support' );

/**
 * Register and Enqueue Scripts.
 */
function register_scripts() {
	$script_path       = get_template_directory_uri() . '/static/main.js';
	$script_asset_path = get_template_directory() . '/static/main.asset.php';

	if ( file_exists( $script_asset_path ) ) {
		$script_asset = require $script_asset_path;
		wp_enqueue_script( 'script', $script_path, $script_asset['dependencies'], $script_asset['version'], true );
		wp_enqueue_style( 'main', get_template_directory_uri() . '/static/main.css', array(), $script_asset['version'] );
	} else {
		wp_enqueue_script( 'script', $script_path, array(), null, true );
		wp_enqueue_style( 'main', get_template_directory_uri() . '/static/main.css', array(), null );
	}

	wp_enqueue_script( 'gsap', get_template_directory_uri() . '/assets/gsap.min.js' );
	wp_enqueue_script( 'gsap-draw-svg', get_template_directory_uri() . '/assets/DrawSVGPlugin.min.js' );
}

add_action( 'wp_enqueue_scripts', 'register_scripts' );

/**
 * Display page template name in pages tables
 */
add_filter( 'manage_pages_columns', 'page_column_views' );
add_action( 'manage_pages_custom_column', 'page_custom_column_views', 5, 2 );
function page_column_views( $defaults ) {
	$defaults['page-layout'] = __( 'Template' );
	return $defaults;
}

function page_custom_column_views( $column_name, $id ) {
	if ( 'page-layout' === $column_name ) {
		$set_template = get_post_meta( get_the_ID(), '_wp_page_template', true );
		if ( 'default' === $set_template ) {
			echo 'Default';
		}
		$templates = get_page_templates();
		ksort( $templates );
		foreach ( array_keys( $templates ) as $template ) :
			if ( $set_template === $templates[ $template ] ) {
				echo $template;
			}

		endforeach;
	}
}

/**
 * Wrap core blocks
 */
function wrap_core_blocks( $block_content, $block ) {
	global $wp_query;
	$block_name      = $block['blockName'];
	$attrs           = $block['attrs'];
	$container_class = 'container-fluid';

	if (
		strpos( $block_name, 'core/column' ) !== false ||
		strpos( $block_name, 'core/group' ) !== false ||
		strpos( $block_name, 'core/block' ) !== false ||
		strpos( $block_name, 'core/html' ) !== false ||
		strpos( $block_name, 'acf/contact' ) !== false
	) {
		return $block_content;
	}

	if (
		(
			strpos( $block_name, 'core/' ) !== false ||
			strpos( $block_name, 'core-embed' ) !== false ||
			strpos( $block_name, 'gravityforms/form' ) !== false
		)
		&&
		'full' !== $attrs['align']
	) {
		$class = $container_class;
		if ( 'center' === $attrs['align'] ) {
			$class .= ' text-center';
		}
		return '<div class="' . $class . '">' . $block_content . '</div>';
	}

	return $block_content;
}

add_filter( 'render_block', 'wrap_core_blocks', 10, 2 );

/**
 * Use custom ACF save point
 */
function acf_json_save_point( $path ) {
	$path = get_stylesheet_directory() . '/../acf-json';
	return $path;
}

add_filter( 'acf/settings/save_json', 'acf_json_save_point' );

/**
 * Use custom ACF load point
 */
function acf_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = get_stylesheet_directory() . '/../acf-json';
	return $paths;
}

add_filter( 'acf/settings/load_json', 'acf_json_load_point' );

/**
 * Add support for custom mime types
 */
function cc_mime_types( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';
	$mimes['webp'] = 'image/webp';
	return $mimes;
}

add_filter( 'upload_mimes', 'cc_mime_types', 1, 1 );

/**
 * Output a bootstrap button withe extra classes or an icon added
 */
function button( $link, $class = false, $icon = false ) {
	$class_name = 'btn';
	if ( $class ) {
		$class_name .= ' ' . $class;
	}
	?>
		<a class="<?php echo $class_name; ?>" href="<?php echo $link['url']; ?>" 
		<?php if ( isset( $link['target'] ) ) : ?>
			target="<?php echo $link['target']; ?>"<?php endif; ?>>
			<?php echo $link['title']; ?>

			<?php if ( $icon ) : ?>
				<i class="<?php echo $icon; ?>"></i>
			<?php endif; ?>
		</a>
	<?php
}

/**
 * Add styles to admin interface
 */
function admin_style() {
	wp_enqueue_style( 'admin-styles', get_template_directory_uri() . '/static/admin.css' );
}

add_action( 'admin_enqueue_scripts', 'admin_style' );

/**
 * Print plugin dependencies
 */
function theme_dependencies() {
	if ( ! is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) ) {
		echo '<div class="notice notice-error"><p>' . __( 'Warning: The Advanced Custom Fields PRO plugin is required for this theme.', 'wordpress-starter' ) . '</p></div>';
	}
}

add_action( 'admin_notices', 'theme_dependencies' );
