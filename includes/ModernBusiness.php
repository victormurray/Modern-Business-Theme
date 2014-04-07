<?php
/**
 * This class manages all functionality with our Modern Business theme.
 */
class ModernBusiness {
	const MB_VERSION = '1.0.3';

	private static $instance; // Keep track of the instance

	/**
	 * Function used to create instance of class.
	 * This is used to prevent over-writing of a variable (old method), i.e. $s = new ModernBusiness();
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) )
			self::$instance = new ModernBusiness;

		return self::$instance;
	}



	/**
	 * This function sets up all of the actions and filters on instance
	 */
	function __construct() {
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 20 ); // Register image sizes
		//add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) ); // Used to enqueue editor styles based on post type
		add_action( 'widgets_init', array( $this, 'widgets_init' ) ); // Register widgets
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); // Enqueue all stylesheets (Main Stylesheet, Fonts, etc...)
		add_action( 'wp_footer', array( $this, 'wp_footer' ) ); // Responsive navigation functionality

		// Gravity Forms
		add_filter( 'gform_field_input', array( $this, 'gform_field_input' ), 10, 5 ); // Add placholder to newsletter form
		add_filter( 'gform_confirmation', array( $this, 'gform_confirmation' ), 10, 4 ); // Change confirmation message on newsletter form
	}


	/************************************************************************************
	 *    Functions to correspond with actions above (attempting to keep same order)    *
	 ************************************************************************************/

	/**
	 * This function adds images sizes to WordPress.
	 */
	function after_setup_theme() {
		add_image_size( 'mb-780x300', 780, 300, true ); // Used for featured images on blog page and single posts
		add_image_size( 'mb-1200x475', 1200, 475, true ); // Used for featured images on full width pages

		// Remove footer nav which is registered in options panel
		unregister_nav_menu( 'footer_nav' );

		// Change default core markup for search form, comment form, and comments, etc... to HTML5
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list'
		) );
	}

	/**
	 * This function adds editor styles based on post type, before TinyMCE is initalized.
	 * It will also enqueue the correct color scheme stylesheet to better match front-end display.
	 */
	function pre_get_posts() {
		global $sds_theme_options, $post;

		// Admin only and if we have a post
		if ( is_admin() && ! empty( $post ) ) {
			add_editor_style( 'css/editor-style.css' );

			// Add correct color scheme if selected
			if ( function_exists( 'sds_color_schemes' ) && ! empty( $sds_theme_options['color_scheme'] ) && $sds_theme_options['color_scheme'] !== 'default' ) {
				$color_schemes = sds_color_schemes();
				add_editor_style( 'css/' . $color_schemes[$sds_theme_options['color_scheme']]['stylesheet'] );
			}

			// Fetch page template if any on Pages only
			if ( $post->post_type === 'page' )
				$wp_page_template = get_post_meta( $post->ID,'_wp_page_template', true );
		}

		// Admin only and if we have a post using our full page or landing page templates
		if ( is_admin() && ! empty( $post ) && ( isset( $wp_page_template ) && ( $wp_page_template === 'page-full-width.php' || $wp_page_template === 'page-landing-page.php' ) ) )
			add_editor_style( 'css/editor-style-full-width.css' );
	}

	/**
	 * This function registers widgets for use throughout Modern Business.
	 */
	function widgets_init() {
		// Register the Modern Business Address Widget (/includes/widget-address.php)
		register_widget( 'MB_Address_Widget' );

		// Register the Modern Business Hero Widget (/includes/widget-hero.php)
		register_widget( 'MB_Hero_Widget' );

		// Register the Modern Business CTA Widget (/includes/widget-call-to-action.php)
		register_widget( 'MB_CTA_Widget' );
	}

	/**
	 * This function enqueues all styles and scripts (Main Stylesheet, Fonts, etc...). Stylesheets can be conditionally included if needed
	 */
	function wp_enqueue_scripts() {
		global $sds_theme_options;

		$protocol = is_ssl() ? 'https' : 'http'; // Determine current protocol

		// ModernBusiness (main stylesheet)
		wp_enqueue_style( 'modern-business', get_template_directory_uri() . '/style.css', false, self::MB_VERSION );

		// Enqueue the child theme stylesheet only if a child theme is active
		if ( is_child_theme() )
			wp_enqueue_style( 'modern-business-child', get_stylesheet_uri(), array( 'modern-business' ), self::MB_VERSION );

		// Droid Sans, Bitter (include only if a web font is not selected in Theme Options)
		if ( ! function_exists( 'sds_web_fonts' ) || empty( $sds_theme_options['web_font'] ) )
			wp_enqueue_style( 'droid-sans-bitter-web-font', $protocol . '://fonts.googleapis.com/css?family=Droid+Sans|Bitter:400,700,400italic', false, self::MB_VERSION ); // Google WebFonts (Droid Sans, Bitter)

		// Font Awesome
		wp_enqueue_style( 'font-awesome-css-min', get_template_directory_uri() . '/includes/css/font-awesome.min.css' );

		// Ensure jQuery is loaded on the front end for our footer script (@see wp_footer() below)
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * This function outputs the necessary javascript for the responsive menus.
	 */
	function wp_footer() {
	?>
		<script type="text/javascript">
			// <![CDATA[
				jQuery( function( $ ) {
					// Mobile Nav
					$( '.mobile-nav-button' ).on( 'touch click', function ( e ) {
						e.stopPropagation();
						$( '.mobile-nav-button, .mobile-nav' ).toggleClass( 'open' );
					} );

					$( document ).on( 'touch click', function() {
						$( '.mobile-nav-button, .mobile-nav' ).removeClass( 'open' );
					} );
				} );
			// ]]>
		</script>
	<?php
	}


	/*****************
	 * Gravity Forms *
	 *****************/

	/**
	 * This function adds the HTML5 placeholder attribute to forms with a CSS class of the following:
	 * .mc-gravity, .mc_gravity, .mc-newsletter, .mc_newsletter classes
	 */
	function gform_field_input( $input, $field, $value, $lead_id, $form_id ) {
		$form_meta = RGFormsModel::get_form_meta( $form_id );

		// Ensure the current form has one of our supported classes and alter the field accordingly if we're not on admin
		if ( isset( $form['cssClass'] ) && ! is_admin() && in_array( $form_meta['cssClass'], array( 'mc-gravity', 'mc_gravity', 'mc-newsletter', 'mc_newsletter' ) ) )
			$input = '<div class="ginput_container"><input name="input_' . $field['id'] . '" id="input_' . $form_id . '_' . $field['id'] . '" type="text" value="" class="large" placeholder="' . $field['label'] . '" /></div>';

		return $input;
	}

	/**
	 * This function alters the confirmation message on forms with a CSS class of the following:
	 * .mc-gravity, .mc_gravity, .mc-newsletter, .mc_newsletter classes
	 */
	function gform_confirmation( $confirmation, $form, $lead, $ajax ) {
		// Confirmation message is set and form has one of our supported classes (alter the confirmation accordingly)
		if ( isset( $form['cssClass'] ) && $form['confirmation']['type'] === 'message' && in_array( $form['cssClass'], array( 'mc-gravity', 'mc_gravity', 'mc-newsletter', 'mc_newsletter' ) ) )
			$confirmation = '<section class="mc-gravity-confirmation mc_gravity-confirmation mc-newsletter-confirmation mc_newsletter-confirmation">' . $confirmation . '</section>';

		return $confirmation;
	}
}


function ModernBusinessInstance() {
	return ModernBusiness::instance();
}

// Starts ModernBusiness
ModernBusinessInstance();