<?php
/**
 * This class manages all functionality with our Modern Business theme.
 */
class ModernBusiness {
	const MB_VERSION = '1.1.4';

	private static $instance; // Keep track of the instance

	/**
	 * Function used to create instance of class.
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
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) ); // Add Meta Boxes
		//add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) ); // Used to enqueue editor styles based on post type
		add_action( 'widgets_init', array( $this, 'widgets_init' ) ); // Register widgets
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); // Enqueue all stylesheets (Main Stylesheet, Fonts, etc...)
		add_filter( 'the_content_more_link', array( $this, 'the_content_more_link' ) ); // Remove default more link
		add_action( 'wp_footer', array( $this, 'wp_footer' ) ); // Responsive navigation functionality

		// Theme Customizer
		add_action( 'customize_register', array( $this, 'customize_register' ), 20 ); // Customize Register
		add_action( 'customize_controls_print_styles', array( $this, 'customize_controls_print_styles' ), 20 ); // Customizer Styles
		add_filter( 'theme_mod_content_color', array( $this, 'theme_mod_content_color' ) ); // Set the default content color

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
		global $content_width;

		/**
		 * Set the Content Width for embedded items.
		 */
		if ( ! isset( $content_width ) )
			$content_width = 780;

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

		// Custom Background (color/image)
		add_theme_support( 'custom-background', array(
			'default-color' => '#ffffff'
		) );

		// Theme textdomain
		load_theme_textdomain( 'modern-business', get_template_directory() . '/languages' );
	}

	/**
	 * This function runs when meta boxes are added.
	 */
	function add_meta_boxes() {
		// Post types
		$post_types = get_post_types(
			array(
				'public' => true,
				'_builtin' => false
			)
		);
		$post_types[] = 'post';
		$post_types[] = 'page';

		// Add the metabox for each type
		foreach ( $post_types as $type ) {
			add_meta_box(
				'modern-business-us-metabox',
				__( 'Layout Settings', 'modern-business' ),
				array( $this, 'modern_business_us_metabox' ),
				$type,
				'side',
				'default'
			);
		}
	}

	/**
	 * This function renders a metabox.
	 */
	function modern_business_us_metabox( $post ) {
		// Get the post type label
		$post_type = get_post_type_object( $post->post_type );
		$label = ( isset( $post_type->labels->singular_name ) ) ? $post_type->labels->singular_name : __( 'Post' );

		echo '<p class="howto">';
		printf(
			__( 'Looking to configure a unique layout for this %1$s? %2$s.', 'modern-business' ),
			esc_html( strtolower( $label ) ),
			sprintf(
				'<a href="%1$s" target="_blank">Upgrade to Pro</a>',
				esc_url( sds_get_pro_link( 'metabox-layout-settings' ) )
			)
		);
		echo '</p>';
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
	 * This function removes the default "more link".
	 */
	function the_content_more_link( $more_link ) {
		return false;
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


	/********************
	 * Theme Customizer *
	 ********************/

	/**
	 * This function is run when the Theme Customizer is loaded.
	 */
	function customize_register( $wp_customize ) {
		$wp_customize->add_section( 'modern_business_us', array(
			'title' => __( 'Upgrade Modern Business', 'modern-business' ),
			'priority' => 1
		) );

		$wp_customize->add_setting(
			'modern_business_us', // IDs can have nested array keys
			array(
				'default' => false,
				'type' => 'modern_business_us'
			)
		);

		$wp_customize->add_control(
			new WP_Customize_US_Control(
				$wp_customize,
				'modern_business_us',
				array(
					'content'  => sprintf(
						__( '<strong>Premium support</strong>, more Customizer options, color schemes, web fonts, and more! %s.', 'modern-business' ),
						sprintf(
							'<a href="%1$s" target="_blank">%2$s</a>',
							esc_url( sds_get_pro_link( 'customizer' ) ),
							__( 'Upgrade to Pro', 'modern-business' )
						)
					),
					'section' => 'modern_business_us',
				)
			)
		);

		$wp_customize->get_section( 'colors' )->description = sprintf(
			__( 'Looking for more color customizations? %s.', 'modern-business' ),
			sprintf(
				'<a href="%1$s" target="_blank">%2$s</a>',
				esc_url( sds_get_pro_link( 'customizer-colors' ) ),
				__( 'Upgrade to Pro', 'modern-business' )
			)
		);
	}

	/**
	 * This function is run when the Theme Customizer is printing styles.
	 */
	function customize_controls_print_styles() {
	?>
		<style type="text/css">
			#accordion-section-modern_business_us .accordion-section-title,
			#customize-theme-controls #accordion-section-modern_business_us .accordion-section-title:focus,
			#customize-theme-controls #accordion-section-modern_business_us .accordion-section-title:hover,
			#customize-theme-controls #accordion-section-modern_business_us .control-section.open .accordion-section-title,
			#customize-theme-controls #accordion-section-modern_business_us:hover .accordion-section-title,
			#accordion-section-modern_business_us .accordion-section-title:active {
				background: #444;
				color: #fff;
			}

			#accordion-section-modern_business_us .accordion-section-title:after,
			#customize-theme-controls #accordion-section-modern_business_us .accordion-section-title:focus::after,
			#customize-theme-controls #accordion-section-modern_business_us .accordion-section-title:hover::after,
			#customize-theme-controls #accordion-section-modern_business_us.open .accordion-section-title::after,
			#customize-theme-controls #accordion-section-modern_business_us:hover .accordion-section-title::after {
				color: #fff;
			}
		</style>
	<?php
	}

	/**
	 * This function sets the default color for the content area in the Theme Customizer.
	 */
	function theme_mod_content_color( $color ) {
		// Return the current color if set
		if ( $color )
			return $color;

		// Return the selected color scheme content color if set
		if ( $selected_color_scheme = sds_get_color_scheme() )
			return $selected_color_scheme['content_color'];

		// Load all color schemes for this theme
		$color_schemes = sds_color_schemes();

		// Return the default color scheme content color
		return $color_schemes['default']['content_color'];
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