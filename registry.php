<?php

/**
 * Plugin Name: Olla
 * Plugin URI: http://www.olla.io
 * Description: Olla core system
 * Version: 1.0.0
 * Author: Olla Digital
 * Author URI: http://www.olla.io
 * Text Domain: olla
 * Domain Path: /languages/
 * License: GPL2
 */

define( 'OLLA_VERSION', '1.0.0' );


class OllaCore	{
	public function __construct() {
		// Initialize the language files
		add_action('plugins_loaded', array($this, 'load_textdomain'));

		// Add default settings on plugin activation
		register_activation_hook( __FILE__, array($this, 'settings_defaults') );

		// Check and update version number option
		add_action('admin_init', array($this, 'set_version_number'));

		// Enqueue admin scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts_styles'));

		// Enqueue front end scripts and styles
		add_action('wp_enqueue_scripts', array($this, 'front_enqueue_scripts_styles'));

		// Add Mobile Detection script
		add_action('init', array($this, 'include_mobile_detect_function'));

		// Register Param Sections
		add_action('init', array($this, 'register_param_sections'));

		// Populate Style Params global
		add_action('init', array($this, 'init_style_global'));

		// Output Style
		add_action('wp_footer', array($this, 'output_style'), 11);

		// Add shortcodes
		add_shortcode('olla_row', array($this, 'row_shortcode'));
		add_shortcode('olla_row_inner', array($this, 'row_shortcode'));
		add_shortcode('olla_column', array($this, 'column_shortcode'));
		add_shortcode('olla_column_inner', array($this, 'column_shortcode'));

		// Replace Custom HTML entities
		add_filter('the_content', array($this, 'decode_custom_entities'), 12); //after shortcode parsing

		// Initialize the editor
		add_action('edit_form_after_title', array($this, 'render_editor'));

		// Initialize Screen Options
		add_action('load-post.php', array($this, 'add_screen_options'));

		// Filter Image Sizes
		add_filter('olla_selectable_image_sizes', array($this, 'selectable_image_sizes'));

		// Initialize AJAX modals
		add_action( 'wp_ajax_add_element_modal', array($this, 'render_add_element_modal'));
		add_action( 'wp_ajax_edit_row_modal', array($this, 'render_edit_row_modal'));
		add_action( 'wp_ajax_edit_column_modal', array($this, 'render_edit_column_modal'));

		// Update media previews
		add_action( 'wp_ajax_update_image_preview', array($this, 'update_image_preview') );
		add_action( 'wp_ajax_update_video_preview', array($this, 'update_video_preview') );

		// Lookup Posts from Select2 boxes
		add_action( 'wp_ajax_olla_posts_search', array($this, 'posts_search') );

		//add hi-res image size
		if ( function_exists( 'add_image_size' ) ) {
			add_image_size('hi-res', 2560, 9999);
			add_image_size('mobile', 640, 9999);
		}

	}

	/**
	 * Load Textdomain
	 *
	 * @since 1.2.4
	 *
	 */

	public function load_textdomain() {
		load_plugin_textdomain( 'olla', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Set default settings
	 *
	 * @since 1.0.0
	 *
	 */

	public function settings_defaults() {
		$options = get_option('olla_options');
		$current_version = get_option('olla_current_version');
		if (empty($options) && empty($current_version)) {
			//set default post types
			if (empty($options['olla_post_types'])) {
				$options['olla_post_types'] = array('post','page','template','component');
				update_option('olla_options', $options);
			}
			//check if using Fusion Base theme
			$template = get_option('template');
				if ($template != 'olla-base') {
				//enable front end bootstrap
				if (empty($options['olla_bootstrap_enable'])) {
					$options['olla_bootstrap_enable'] = 'on';
					update_option('olla_options', $options);
				}
				//enable fluid containers
				if (empty($options['olla_bootstrap_fluid'])) {
					$options['olla_bootstrap_fluid'] = 'on';
					update_option('olla_options', $options);
				}
			}
		}
		//set version number
		update_option('olla_current_version', FSN_VERSION);
	}

	/**
	 * Set version number
	 *
	 * Check the version number in the options table and, if not found or not a match, set the version number.
	 *
	 * @since 1.0.3
	 *
	 */

	public function set_version_number() {
		$current_version = get_option('olla_current_version');
		if (empty($current_version) || $current_version != FSN_VERSION) 	{
			update_option('olla_current_version', FSN_VERSION);
		}
	}

	/**
	 * Enqueue JavaScript and CSS on Admin pages.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */

	public function admin_enqueue_scripts_styles($hook_suffix) {
		global $post;

		$options = get_option('olla_options');
		$user_admin_color = get_user_option( 'admin_color' );
		$olla_post_types = !empty($options['olla_post_types']) ? $options['olla_post_types'] : '';

		// Editor scripts and styles
		if ( ($hook_suffix == 'post.php' || $hook_suffix == 'post-new.php') && ( (post_type_exists('notification') && $post->post_type == 'notification') || (!empty($olla_post_types) && is_array($olla_post_types) && in_array($post->post_type, $olla_post_types)) ) ) {
			//bootstrap
			wp_enqueue_script( 'bootstrap_admin', plugin_dir_url( __FILE__ ) . 'assets/admin/js/bootstrap.min.js', false, '3.3.5', true );

			//jQuery UI
			wp_enqueue_style( 'jquery-ui-custom', plugin_dir_url( __FILE__ ) . 'includes/css/jquery-ui-1.11.4.custom/jquery-ui.min.css', false, '1.11.4' );
			wp_enqueue_script('jquery-ui-sortable');
			wp_enqueue_script('jquery-ui-resizable');
			wp_enqueue_script('jquery-ui-tooltip');
			//WordPress Color Picker
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'wp-color-picker' );
			//plugin
			wp_enqueue_script( 'olla_core_admin', plugin_dir_url( __FILE__ ) . 'includes/js/olla-core-admin.js', array('jquery'), '1.3.0', true );
			wp_enqueue_style( 'olla_core_admin', plugin_dir_url( __FILE__ ) . 'includes/css/olla-core-admin.css', false, '1.3.0' );
			if ($user_admin_color != 'fresh') {
				wp_enqueue_style( 'olla_core_admin_color_scheme', plugin_dir_url( __FILE__ ) . 'includes/css/colors/'. $user_admin_color .'/colors.css', false, '1.3.0' );
			}
			wp_localize_script( 'olla_core_admin', 'ollaJS', array(
					'ollaEditNonce' => wp_create_nonce('olla-admin-edit')
				)
			);

			//add translation strings to script
			$translation_array = array(
				'error' => __('Oops, something went wrong. Please reload the page and try again.','olla'),
				'search' => __('Start typing to search...', 'olla'),
				'text_label' => __('Text', 'olla'),
				'edit' => __('Edit', 'olla'),
				'duplicate' => __('Duplicate', 'olla'),
				'delete' => __('Delete', 'olla'),
				'move_up' => __('Move Up', 'olla'),
				'move_down' => __('Move Down', 'olla'),
				'move_top' => __('Move to Top', 'olla'),
				'move_bottom' => __('Move to Bottom', 'olla'),
				'row_options' => __('Row Options', 'olla'),
				'row_edit' => __('Edit Row', 'olla'),
				'row_add' => __('Add Row', 'olla'),
				'column_options' => __('Column Options', 'olla'),
				'column_edit' => __('Edit Column', 'olla'),
				'column_add' => __('Add Column', 'olla'),
				'element_options' => __('Element Options', 'olla'),
				'element_edit' => __('Edit Element', 'olla'),
				'element_add' => __('Add Element', 'olla'),
				'tabs_options' => __('Tabs Options', 'olla'),
				'tabs_edit' => __('Edit Tabs', 'olla'),
				'tab_options' => __('Tab Options', 'olla'),
				'tab_edit' => __('Edit Tab', 'olla'),
				'tab_add' => __('Add Tab', 'olla'),
				'tab_new' => __('New Tab', 'olla'),
				'tabs_title' => __('Tabs', 'olla'),
				'tab_1_title' => __('Tab 1', 'olla'),
				'tab_2_title' => __('Tab 2', 'olla'),
				'template_save_success' => __('Template Saved Successfully.', 'olla'),
				'template_save_error' => __('There was an error saving the template. Please try again.', 'olla'),
				'template_delete_error' => __('There was an error deleting the template. Please try again.', 'olla'),
				'template_delete_all' => __('There are no saved templates remaining.', 'olla'),
				'template_options' => __('Template Options', 'olla'),
				'custom_list_item_collapse' => __('collapse', 'olla'),
				'custom_list_item_expand' => __('expand', 'olla'),
				'notice_dismiss' => __('Dismiss this notice.', 'olla'),
				'button_summary_type' => __('Type', 'olla'),
				'button_summary_external' => __('External Link', 'olla'),
				'button_summary_internal' => __('Internal Link', 'olla'),
				'button_summary_collapse' => __('Collapse', 'olla'),
				'button_summary_modal' => __('Modal', 'olla'),
				'button_summary_link' => __('Links to', 'olla'),
				'button_summary_label' => __('Label', 'olla'),
				'button_summary_target' => __('Opens in', 'olla'),
				'button_summary_target_blank' => __('New Window / Tab', 'olla'),
				'button_summary_target_parent' => __('Parent Frame', 'olla'),
				'button_summary_target_top' => __('Full Body of the Window', 'olla'),
				'button_summary_target_default' => __('Current Window / Tab', 'olla'),
				'button_summary_opens' => __('Opens', 'olla'),
				'button_summary_collapse_show' => __('Show Label', 'olla'),
				'button_summary_collapse_hide' => __('Hide Label', 'olla'),
				'media_image_select' => __('Select Image', 'olla'),
				'media_image_use' => __('Use This Image', 'olla'),
				'media_video_select' => __('Select Video', 'olla'),
				'media_video_use' => __('Use This Video', 'olla')
			);
			wp_localize_script('olla_core_admin', 'ollaL10n', $translation_array);
		}
		//olla core query
		wp_register_script( 'olla_core_query', plugin_dir_url( __FILE__ ) . 'includes/js/olla-core-query.js', array('jquery'), '1.3.0', true );
		wp_localize_script( 'olla_core_query', 'ollaQuery', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ollaQueryNonce' => wp_create_nonce('olla-query'),
				'ollaQueryError' => __('Oops, something went wrong with your query. Please reload the page and try again.','olla'),
			)
		);
		//select2
		wp_enqueue_script('select2', plugin_dir_url( __FILE__ ) . 'includes/utilities/select2/js/select2.min.js', array('jquery'), '4.0.3', true);
		wp_enqueue_style('select2', plugin_dir_url( __FILE__ ) . 'includes/utilities/select2/css/select2.min.css');
	}

	/**
	 * Enqueue JavaScript and CSS on Front End pages.
	 *
	 * @since 1.0.0
	 *
	 */

	public function front_enqueue_scripts_styles() {
		//bootstrap
		$options = get_option('olla_options');
		$bootstrap_enable = !empty($options['olla_bootstrap_enable']) ? $options['olla_bootstrap_enable'] : '';
		if (!empty($bootstrap_enable)) {
			wp_enqueue_script( 'bootstrap', plugin_dir_url( __FILE__ ) . 'includes/bootstrap/front/js/bootstrap.min.js', false, '3.3.5', true );
			wp_enqueue_style( 'bootstrap', plugin_dir_url( __FILE__ ) . 'includes/bootstrap/front/css/bootstrap.min.css', false, '3.3.5' );
			wp_enqueue_style( 'olla_bootstrap', plugin_dir_url( __FILE__ ) . 'includes/css/olla-bootstrap.css', 'bootstrap', '1.3.0' );
		}
		//modernizr
		wp_enqueue_script( 'modernizr', plugin_dir_url( __FILE__ ) . 'includes/js/modernizr-3.3.1-respond-1.4.2.min.js', false, '3.3.1');
		//imagesLoaded
		wp_enqueue_script('images_loaded', plugin_dir_url( __FILE__ ) .'includes/utilities/imagesloaded/imagesloaded.pkgd.min.js', array('jquery'), '3.1.8', true);
		//plugin
		wp_enqueue_script( 'olla_core', plugin_dir_url( __FILE__ ) . 'includes/js/olla-core.js', array('jquery','modernizr','images_loaded'), '1.3.0', true );
		wp_enqueue_style( 'olla_core', plugin_dir_url( __FILE__ ) . 'includes/css/olla-core.css', false, '1.3.0' );

		//setup front end script for use with AJAX
		wp_localize_script( 'olla_core', 'ollaAjax', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'pluginurl' =>  plugin_dir_url( __FILE__ )
			)
		);
		//olla core query
		wp_register_script( 'olla_core_query', plugin_dir_url( __FILE__ ) . 'includes/js/olla-core-query.js', array('jquery'), '1.3.0', true );
		wp_localize_script( 'olla_core_query', 'ollaQuery', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'ollaQueryNonce' => wp_create_nonce('olla-query'),
				'ollaQueryError' => __('Oops, something went wrong with your query. Please reload the page and try again.','olla'),
			)
		);
	}

	/**
	 * Add Mobile Detect Script
	 *
	 * @since 1.0.0
	 *
	 */

	public function include_mobile_detect_function() {
		if (!class_exists('Mobile_Detect')) {
			include 'includes/utilities/mobile-detect/Mobile_Detect.php';
		}
	}

	/**
	 * Register Param sections
	 *
	 * Register the sections in which element params can be added
	 *
	 * @since 1.0.0
	 *
	 */

	public function register_param_sections() {
		global $olla_param_sections;
		$olla_param_sections = array(
			array(
				'id' => 'general',
				'name' => __('General', 'olla')
			),
			array(
				'id' => 'advanced',
				'name' => __('Advanced', 'olla')
			),
			array(
				'id' => 'style',
				'name' => __('Style', 'olla')
			),
			array(
				'id' => 'animation',
				'name' => __('Animation', 'olla')
			)
		);
		$olla_param_sections = apply_filters('olla_param_sections', $olla_param_sections);
	}

	/**
	 * Populate Style params global
	 *
	 * @since 1.0.0
	 *
	 */

	public function init_style_global() {
		if (is_admin()) {
			global $olla_style_params;
			$olla_style_params = array(
				array(
					'type' => 'box',
					'param_name' => 'margin',
					'label' => __('Margins', 'olla'),
					'help' => __('e.g. 15px', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'box',
					'param_name' => 'margin_xs',
					'label' => __('Mobile Margins', 'olla'),
					'help' => __('e.g. 15px', 'olla'),
					'section' => 'style',
					'dependency' => array(
						'param_name' => 'margin_xs_custom',
						'not_empty' => true
					)
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'margin_xs_custom',
					'label' => __('Customize Mobile Margins', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'box',
					'param_name' => 'padding',
					'label' => __('Padding', 'olla'),
					'help' => __('e.g. 15px', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'box',
					'param_name' => 'padding_xs',
					'label' => __('Mobile Padding', 'olla'),
					'help' => __('e.g. 15px', 'olla'),
					'section' => 'style',
					'dependency' => array(
						'param_name' => 'padding_xs_custom',
						'not_empty' => true
					)
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'padding_xs_custom',
					'label' => __('Customize Mobile Padding', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'select',
					'options' => array(
						'' => __('Choose text alignment.', 'olla'),
						'left' => __('Left', 'olla'),
						'center' => __('Center', 'olla'),
						'right' => __('Right', 'olla')
					),
					'param_name' => 'text_align',
					'label' => __('Text Align', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'select',
					'options' => array(
						'' => __('Choose text alignment.', 'olla'),
						'left' => __('Left', 'olla'),
						'center' => __('Center', 'olla'),
						'right' => __('Right', 'olla')
					),
					'param_name' => 'text_align_xs',
					'label' => __('Mobile Text Align', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'text',
					'param_name' => 'font_size',
					'label' => __('Font Size', 'olla'),
					'help' => __('e.g. 15px', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'colorpicker',
					'param_name' => 'color',
					'label' => __('Text Color', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'colorpicker',
					'param_name' => 'background_color',
					'label' => __('Background Color', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'text',
					'param_name' => 'background_color_opacity',
					'label' => __('Background Color Opacity', 'olla'),
					'help' => __('Value between 0 and 1.', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'hidden_xs',
					'label' => __('Hide on Mobile', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'checkbox',
					'param_name' => 'visible_xs',
					'label' => __('Hide on Desktop and Tablet', 'olla'),
					'section' => 'style'
				),
				array(
					'type' => 'text',
					'param_name' => 'user_classes',
					'label' => __('CSS Classes', 'olla'),
					'help' => __('Separate multiple classes with a space.', 'olla'),
					'section' => 'style'
				)
			);
		}
	}

	/**
	 * Output Style
	 *
	 * @since 1.0.0
	 *
	 */

	public function output_style() {
		global $olla_style_output;
		echo '<style>';
			if (!empty($olla_style_output)) {
				foreach($olla_style_output as $key => $value) {
					if (!empty($value)) {
						$selector = '.'. $key;
						echo $selector . ' {';
							if (!empty($value['margin'])) {
								$margin = json_decode($value['margin'], true);
								echo !empty($margin['top']) ? 'margin-top:'. $margin['top'] .';' : '';
								echo !empty($margin['right']) ? 'margin-right:'. $margin['right'] .';' : '';
								echo !empty($margin['bottom']) ? 'margin-bottom:'. $margin['bottom'] .';' : '';
								echo !empty($margin['left']) ? 'margin-left:'. $margin['left'] .';' : '';
							}
							if (!empty($value['padding'])) {
								$padding = json_decode($value['padding'], true);
								echo !empty($padding['top']) ? 'padding-top:'. $padding['top'] .';' : '';
								echo !empty($padding['right']) ? 'padding-right:'. $padding['right'] .';' : '';
								echo !empty($padding['bottom']) ? 'padding-bottom:'. $padding['bottom'] .';' : '';
								echo !empty($padding['left']) ? 'padding-left:'. $padding['left'] .';' : '';
							}
							if (!empty($value['text_align'])) {
								$text_align = $value['text_align'];
								echo 'text-align:'. $text_align .';';
							}
							if (!empty($value['font_size'])) {
								$font_size = $value['font_size'];
								echo 'font-size:'. $font_size .';';
							}
							if (!empty($value['color'])) {
								$color = $value['color'];
								echo 'color:'. $color .';';
							}
							if (!empty($value['background_color'])) {
								$background_color = $value['background_color'];
								if (!empty($value['background_color_opacity'])) {
									$background_color_opacity = $value['background_color_opacity'];
									$rgb = olla_hex2rgb($background_color);
									echo 'background-color:'. $background_color .';';
									echo 'background-color:rgba('. $rgb[0] .','. $rgb[1] .','. $rgb[2] .','. $background_color_opacity .');';
								} else {
									echo 'background-color:'. $background_color .';';
								}
							}
							do_action('olla_style_append_delcaration_block', $value);
						echo '}';
						if ( (!empty($value['margin_xs_custom']) && !empty($value['margin_xs'])) || (!empty($value['padding_xs_custom']) && !empty($value['padding_xs']) || !empty($value['text_align_xs'])) ) {
							$selector = '.'. $key;
							echo '@media (max-width: 767px) {'. $selector . '{';
								if (!empty($value['margin_xs_custom']) && !empty($value['margin_xs'])) {
									$margin_xs = json_decode($value['margin_xs'], true);
									echo !empty($margin_xs['top']) ? 'margin-top:'. $margin_xs['top'] .';' : '';
									echo !empty($margin_xs['right']) ? 'margin-right:'. $margin_xs['right'] .';' : '';
									echo !empty($margin_xs['bottom']) ? 'margin-bottom:'. $margin_xs['bottom'] .';' : '';
									echo !empty($margin_xs['left']) ? 'margin-left:'. $margin_xs['left'] .';' : '';
								}
								if (!empty($value['padding_xs_custom']) && !empty($value['padding_xs'])) {
									$padding_xs = json_decode($value['padding_xs'], true);
									echo !empty($padding_xs['top']) ? 'padding-top:'. $padding_xs['top'] .';' : '';
									echo !empty($padding_xs['right']) ? 'padding-right:'. $padding_xs['right'] .';' : '';
									echo !empty($padding_xs['bottom']) ? 'padding-bottom:'. $padding_xs['bottom'] .';' : '';
									echo !empty($padding_xs['left']) ? 'padding-left:'. $padding_xs['left'] .';' : '';
								}
								if (!empty($value['text_align_xs'])) {
									$text_align = $value['text_align_xs'];
									echo 'text-align:'. $text_align .';';
								}
							echo '}}';
						}
					}
				}
			}
			/**
			 * Append Style Output
			 *
			 * @since 1.1.11
			 *
			 */
			do_action('olla_style_append');
		echo '</style>';
	}

	/**
	 * The Row shortcode.
	 *
	 * Output a row into the content area. Rows contain columns which contain content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attr Attributes attributed to the shortcode.
	 * @param string $content Optional. Shortcode content.
	 * @return string
	 */

	public function row_shortcode($atts, $content = null) {

		extract( shortcode_atts( array(
			'row_style' => 'light',
			'row_function' => '',
			'row_width' => 'container',
			'seamless' => '',
			'background_image' => '',
			'background_repeat' => 'repeat',
			'background_position' => 'left top',
			'background_position_custom' => '',
			'background_attachment' => 'scroll',
			'background_size' => 'auto',
			'background_image_xs' => 'show',
			'id' => false
		), $atts ) );

		//if running AJAX, get action being run
		$ajax_action = false;
		if (defined('DOING_AJAX') && DOING_AJAX) {
			if (!empty($_POST['action'])) {
				$ajax_action = sanitize_text_field($_POST['action']);
			}
		}

		//build output
		if ( is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX || (!empty($ajax_action) && $ajax_action == 'load_template' || $ajax_action == 'components_modal')) ) {
			$shortcode_atts_data = '';
			if (!empty($atts)) {
				foreach($atts as $key => $value) {
					$att_name = str_replace('_','-', $key);
					$shortcode_atts_data .= ' data-'. esc_attr($att_name) .'="'. esc_attr($value) .'"';
				}
			}
			$output = '';
			$output .= '<div class="row-container clearfix">';
				$output .= '<div class="row-header">';
					$output .= '<div class="row-controls">';
						$output .= '<span class="row-controls-toggle" title="'. __('Row Options', 'olla') .'"><i class="material-icons md-18">&#xE5D3;</i></span>';
						$output .= '<div class="row-controls-dropdown collapsed">';
							$output .= '<a href="#" class="edit-row">'. __('Edit', 'olla') .'</a>';
							$output .= '<a href="#" class="duplicate-row">'. __('Duplicate', 'olla') .'</a>';
							$output .= '<hr>';
							$output .= '<a href="#" class="move-row" data-move="up">'. __('Move Up', 'olla') .'</a>';
							$output .= '<a href="#" class="move-row" data-move="down">'. __('Move Down', 'olla') .'</a>';
							$output .= '<a href="#" class="move-row" data-move="top">'. __('Move to Top', 'olla') .'</a>';
							$output .= '<a href="#" class="move-row" data-move="bottom">'. __('Move to Bottom', 'olla') .'</a>';
							$output .= '<hr>';
							$output .= '<a href="#" class="delete-row">'. __('Delete', 'olla') .'</a>';
						$output .= '</div>';
						$output .= '<a href="#" class="control-icon edit-row" title="'. __('Edit Row', 'function') .'"><i class="material-icons md-18">&#xE3C9;</i></a>';
					$output .= '</div>';
					$output .= '<a href="#" class="olla-add-row" title="'. __('Add Row', 'olla') .'"><i class="material-icons md-18">&#xE147;</i></a>';
				$output .= '</div>';
				$output .= '<div class="row-wrapper">';
					$output .= '<div class="row"'. $shortcode_atts_data .'>'. do_shortcode($content) .'</div>';
				$output .= '</div>';
			$output .= '</div>';

		} else {

			//build style
			$style = '';

			//background image
			if (!empty($background_image)) {
				$image_attrs = wp_get_attachment_image_src($background_image, 'hi-res');
				$style .= 'background-image:url('. $image_attrs[0] .');';
			}
			//background repeat
			if (!empty($background_repeat)) {
				$style .= 'background-repeat:'. $background_repeat .';';
			}
			//background position
			if (!empty($background_position)) {
				if ($background_position == 'custom' && !empty($background_position_custom)) {
					$style .= 'background-position:'. $background_position_custom .';';
				} else {
					$style .= 'background-position:'. $background_position .';';
				}
			}
			//background attachment
			if (!empty($background_attachment)) {
				$style .= 'background-attachment:'. $background_attachment .';';
			}
			//background size
			if (!empty($background_size)) {
				$style .= 'background-size:'. $background_size .';';
			}

			//filter for modifying style
			$style = apply_filters('olla_row_style', $style, $atts);

			//build classes
			$classes_array = array();

			//row style
			if (!empty($row_style)) {
				$classes_array[] = $row_style;
			}

			//row function
			if (!empty($row_function)) {
				$classes_array[] = $row_function;
			}

			//seamless rows
			if (!empty($seamless)) {
				$classes_array[] = 'seamless';
			}

			//hide mobile background
			if ($background_image_xs == 'hide') {
				$classes_array[] = 'background-image-hidden-xs';
			}

			//filter for adding classes
			$classes_array = apply_filters('olla_row_classes', $classes_array, $atts);

			if (!empty($classes_array)) {
				$classes = implode(' ', $classes_array);
			}

			$output = '';

			//open row container
			if ($row_width == 'container') {
				$output .= '<div '. (!empty($id) ? 'id="'. esc_attr($id) .'" ' : '') .'class="olla-row full-width-row '. olla_style_params_class($atts) . (!empty($classes) ? ' '. esc_attr($classes) : '') .'"'. (!empty($style) ? ' style="'. esc_attr($style) .'"' : '') .'>';
					//action executed before the front-end row shortcode container output
					ob_start();
					do_action('olla_before_row_container', $atts);
					$output .= ob_get_clean();
					//open fluid or defined container
					$options = get_option('olla_options');
					if (empty($options['olla_bootstrap_fluid'])) {
						$output .= '<div class="container">';
					} else {
						$output .= '<div class="container-fluid">';
					}
			} elseif ($row_width == 'full-width') {
				$output .= '<div '. (!empty($id) ? 'id="'. esc_attr($id) .'" ' : '') .' class="olla-row full-width-container '. olla_style_params_class($atts) . (!empty($classes) ? ' '. esc_attr($classes) : '') .'"'. (!empty($style) ? ' style="'. esc_attr($style) .'"' : '') .'>';
			}

			//action executed before the front-end row shortcode output
			ob_start();
			do_action('olla_before_row', $atts);
			$output .= ob_get_clean();

			$output .= '<div class="row">'. do_shortcode($content) .'</div>';

			//action executed after the front-end row shortcode output
			ob_start();
			do_action('olla_after_row', $atts);
			$output .= ob_get_clean();

			//close row container
			if ($row_width == 'container') {
					$output .= '</div>'; //close container
					//action executed after the front-end row shortcode container output
					ob_start();
					do_action('olla_after_row_container', $atts);
					$output .= ob_get_clean();
				$output .= '</div>'; //close full width row
			} elseif ($row_width == 'full-width') {
				$output .= '</div>'; //close full width container
			}
		}

		return $output;
	}

	/**
	 * The Column shortcode.
	 *
	 * Output a column into a row. Columns contain content.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attr Attributes attributed to the shortcode.
	 * @param string $content Optional. Shortcode content.
	 * @return string
	 */

	public function column_shortcode($atts, $content = null) {
		extract( shortcode_atts( array(
			'width' => '12',
			'offset' => false,
			'column_style' => 'light'
		), $atts ) );

		//if running AJAX, get action being run
		$ajax_action = false;
		if (defined('DOING_AJAX') && DOING_AJAX) {
			if (!empty($_POST['action'])) {
				$ajax_action = sanitize_text_field($_POST['action']);
			}
		}

		//build output
		if ( is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX || (!empty($ajax_action) && $ajax_action == 'load_template' || $ajax_action == 'components_modal')) ) {
			$shortcode_atts_data = '';
			if (!empty($atts)) {
				foreach($atts as $key => $value) {
					$att_name = str_replace('_','-', $key);
					$shortcode_atts_data .= ' data-'. esc_attr($att_name) .'="'. esc_attr($value) .'"';
				}
			}
			$output = '';
			$output .= '<div class="col-sm-'. esc_attr($width) . (!empty($offset) ? ' col-sm-offset-'. esc_attr($offset) : '') .'"'. $shortcode_atts_data .'>';
				$output .= '<div class="column-container clearfix">';
					$output .= '<div class="column-header">';
						$output .= '<div class="column-controls">';
							$output .= '<span class="column-controls-toggle" title="'. __('Column Options', 'olla') .'"><i class="material-icons md-18">&#xE5D3;</i></span>';
							$output .= '<div class="column-controls-dropdown collapsed">';
								$output .= '<a href="#" class="edit-col">'. __('Edit', 'olla') .'</a>';
								$output .= '<a href="#" class="delete-col">'. __('Delete', 'olla') .'</a>';
							$output .= '</div>';
							$output .= '<a href="#" class="control-icon edit-col" title="'. __('Edit Column', 'olla') .'"><i class="material-icons md-18">&#xE3C9;</i></a>';
						$output .= '</div>';
						$output .= '<h3 class="column-title"><span class="column-width">'. esc_attr($width) .'</span> / 12</h3>';
					$output .= '</div>';
					$output .= '<div class="column-wrapper">';
						$output .= do_shortcode($content);
					$output .= '</div>';
					$output .= '<a href="#" class="olla-add-element" data-container="column" title="'. __('Add Element', 'olla') .'"><i class="material-icons md-18">&#xE147;</i></a>';
				$output .= '</div>';
			$output .= '</div>';
		} else {

			//build style
			$style = '';

			//filter for modifying style
			$style = apply_filters('olla_column_style', $style, $atts);

			//build classes
			$classes_array = array();

			//column style
			if (!empty($column_style)) {
				$classes_array[] = $column_style;
			}

			//filter for adding classes
			$classes_array = apply_filters('olla_column_classes', $classes_array, $atts);

			if (!empty($classes_array)) {
				$classes = implode(' ', $classes_array);
			}

			$output = '';
			//action executed before the front-end column shortcode output
			ob_start();
			do_action('olla_before_column', $atts);
			$output .= ob_get_clean();

			$output .= '<div class="col-sm-'. esc_attr($width) . (!empty($offset) ? ' col-sm-offset-'. esc_attr($offset) : '') .'"><div class="olla-column-inner '. olla_style_params_class($atts) . (!empty($classes) ? ' '. esc_attr($classes) : '') .'"'. (!empty($style) ? ' style="'. esc_attr($style) .'"' : '') .'>'. do_shortcode($content) .'</div></div>';

			//action executed after the front-end column shortcode output
			ob_start();
			do_action('olla_after_column', $atts);
			$output .= ob_get_clean();
		}

		return $output;
	}

	/**
	 * Decode Custom HTML Entities
	 *
	 * Replace the Custom HTML Entities needed to preserve the integrity of Fusion shortcodes in light of TinyMCE inflexibilities
	 *
	 * @since 1.0.0
	 *
	 * @param string $content the content to be modified
	 * @return string
	 */

	public static function decode_custom_entities($content) {

		$custom_entities = array('#ollaquot;','#ollasqbl;','#ollasqbr;','#ollalt;','#ollagt;');
		$html_entities = array('"','[',']','<','>');

		$content = str_replace($custom_entities, $html_entities, $content);

		return $content;
	}

	/**
	 * Render Fusion editor
	 *
	 * Output editor on select post types.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post The post object.
	 */

	public function render_editor($post) {
		$options = get_option('olla_options');
		$olla_post_types = !empty($options['olla_post_types']) ? $options['olla_post_types'] : '';
		if (!empty($olla_post_types) && is_array($olla_post_types) && in_array($post->post_type, $olla_post_types)) {
			echo '<a href="#" class="button button-primary olla-toggle-editor"><div class="olla-toggle-editor-default">'. __('Wordpress Editor', 'olla') .'</div><div class="olla-toggle-editor-olla">'. __('Switch To Fusion Editor', 'olla') .'</div></a>';
			echo '<div class="olla-editor wp-editor-container">';
				echo '<div class="olla-main-controls">';
					if ($post->post_type != 'template') {
						echo '<a href="#" class="button olla-save-template">'. __('Save Template', 'olla') .'</a>';
					}
					echo '<a href="#" class="button olla-load-template" style="margin-left:5px;">'. __('Load Template', 'olla') .'</a>';
					//echo '<a href="#" class="button olla-toggle-previews" style="margin-left:5px;">'. __('Hide Element Previews', 'olla') .'</a>';
				echo '</div>';
				echo '<div class="olla-interface-container">';
					//output grid content
					echo '<div id="olla-main-ui" class="olla-interface-grid">';
						echo do_shortcode($post->post_content);
					echo '</div>';
				echo '</div>';
			echo '</div>';
		}
	}

	/**
	 * Add Screen Options
	 *
	 * Add screen options for configuring olla on a per-user basis
	 *
	 * @since 1.0.0
	 */

	public function add_screen_options() {
		$current_screen = get_current_screen();
		$options = get_option('olla_options');
		$olla_post_types = !empty($options['olla_post_types']) ? $options['olla_post_types'] : '';
		if ( !empty($olla_post_types) && is_array($olla_post_types) && in_array($current_screen->post_type, $olla_post_types) ) {
			add_filter( 'screen_settings', array($this, 'filter_screen_settings'), 10, 2 );
		}
	}

	public function filter_screen_settings($screen_settings, $screen_object) {
		$expand = '<fieldset class="editor-expand"><legend>' . __('Fusion settings', 'olla') . '</legend><label for="olla_disable_tooltips">';
		$expand .= '<input type="checkbox" id="olla_disable_tooltips"' . checked( get_user_setting( 'olla_disable_tooltips', false ), 'on', false ) . ' />';
		$expand .= __('Disable Fusion tooltips.', 'olla') . '</label></fieldset>';
		$screen_settings .= $expand;
		return $screen_settings;
	}

	/**
	 * Filter image sizes
	 *
	 * Filter out image sizes that should not be user-selectable
	 *
	 * @since 1.0.0
	 */

	public function selectable_image_sizes($olla_selectable_image_sizes) {
		//unset WordPress medium large image size
		unset($olla_selectable_image_sizes['medium_large']);
		return $olla_selectable_image_sizes;
	}

	/**
	 * Render add content modal.
	 *
	 * @since 1.0.0
	 */

	public function render_add_element_modal() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
			die( '-1' );

		//get elements global
		global $olla_elements;
		$nesting_level = intval($_POST['nesting_level']);
		$tabs_nesting_level = intval($_POST['tabs_nesting_level']);
		?>
		<div class="modal fade" id="addElementModal" tabindex="-1" role="dialog" aria-labelledby="ollaModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title" id="ollaModalLabel"><?php _e('Add Element', 'olla'); ?></h4>
						<a href="#" class="close" data-dismiss="modal" aria-label="<?php _e('Close', 'olla'); ?>"><span aria-hidden="true"><i class="material-icons">&#xE5CD;</i></span></a>
					</div>
					<div class="modal-body">
						<div class="element-grid">
							<?php if ($nesting_level === 1) : ?>
								<div class="element-grid-item">
									<a href="#" class="element-item" data-element-type="row"><i class="material-icons">reorder</i> <span class="element-name"><?php _e('Row', 'olla'); ?></span></a>
								</div>
							<?php endif; ?>
							<?php if ($tabs_nesting_level === 0 && $nesting_level === 1) : ?>
								<div class="element-grid-item">
									<a href="#" class="element-item" data-element-type="tabs"><i class="material-icons">tab</i> <span class="element-name"><?php _e('Tabs', 'olla'); ?></span></a>
								</div>
							<?php endif; ?>
							<?php if (!empty($olla_elements)) {
								//output all elements
								foreach($olla_elements as $olla_element) {
									echo '<div class="element-grid-item">';
										echo '<a href="#" class="element-item" data-element-type="'. esc_attr($olla_element->shortcode_tag) .'">'. (!empty($olla_element->icon) ? '<i class="material-icons">'. esc_html($olla_element->icon) .'</i> ' : '') .'<span class="element-name">'. esc_html($olla_element->name) .'</span></a>';
									echo '</div>';
								}
							} ?>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="button" data-dismiss="modal"><?php _e('Close', 'olla'); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		exit;
	}

	/**
	 * Render edit row modal.
	 *
	 * @since 1.0.0
	 */

	public function render_edit_row_modal() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
			die( '-1' );

		$saved_values = !empty($_POST['saved_values']) ? $_POST['saved_values'] : '';
		if (empty($saved_values)) {
			$saved_values = array();
		} else {
			foreach($saved_values as $key => $value) {
				$saved_values[$key] = wp_filter_post_kses($value);
			}
		}

    $row_function_options = array(
      '' => __('Choose row function.', 'olla'),
      'collapse' => __('Collapse', 'olla')
    );
		$row_function_options = apply_filters('olla_row_function_options', $row_function_options);

		$row_style_options = array(
			'light' => __('Light', 'olla'),
			'dark' => __('Dark', 'olla')
		);
		$row_style_options = apply_filters('olla_row_style_options', $row_style_options);

		//map row parameters
		$params = array(
			array(
				'type' => 'radio',
				'options' => array(
					'container' => __('Container', 'olla'),
					'full-width' => __('Full Width','olla')
				),
				'param_name' => 'row_width',
				'label' => __('Width', 'olla'),
				'help' => __('Choose whether Row is wrapped in container (default) or is full width.', 'olla')
			),
			array(
				'type' => 'checkbox',
				'param_name' => 'seamless',
				'label' => __('Seamless', 'olla'),
				'help' => __('Check to enable seamless Columns with no left/right margins for Row.', 'olla')
			),
			array(
				'type' => 'select',
				'options' => $row_function_options,
				'param_name' => 'row_function',
				'label' => __('Function', 'olla'),
				'help' => __('"Collapse" will hide row and allow it to be triggered and revealed by a button.', 'olla'),
				'section' => 'advanced'
			),array(
				'type' => 'text',
				'param_name' => 'id',
				'label' => __('ID', 'olla'),
				'help' => __('Input row ID. Rows can be targeted by their ID for triggering collapsed Rows or anchor links.', 'olla'),
				'section' => 'advanced'
			),
			array(
				'type' => 'select',
				'options' => $row_style_options,
				'param_name' => 'row_style',
				'label' => __('Theme', 'olla'),
				'help' => __('Choose Row theme. Light will inherit globally set text color. Dark will adopt text color set within this Row for headlines and links.', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'image',
				'param_name' => 'background_image',
				'label' => __('Background Image', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'select',
				'options' => array(
					'repeat' => __('Repeat','olla'),
					'no-repeat' => __('No Repeat', 'olla')
				),
				'param_name' => 'background_repeat',
				'label' => __('Background Image Repeat', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'select',
				'options' => array(
					'left top' => __('Top Left', 'olla'),
					'center top' => __('Top Center', 'olla'),
					'right top' => __('Top Right', 'olla'),
					'left center' => __('Center Left', 'olla'),
					'center center' => __('Center Center', 'olla'),
					'right center' => __('Center Right', 'olla'),
					'left bottom' => __('Bottom Left', 'olla'),
					'center bottom' => __('Bottom Center', 'olla'),
					'right bottom' => __('Bottom Right', 'olla'),
					'custom' => __('Custom', 'olla')
				),
				'param_name' => 'background_position',
				'label' => __('Background Image Position', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'text',
				'param_name' => 'background_position_custom',
				'label' => __('Custom Background Image Position', 'olla'),
				'help' => __('Input background image x-y position (e.g. 20px 20px).', 'olla'),
				'section' => 'style',
				'dependency' => array(
					'param_name' => 'background_position',
					'value' => 'custom'
				)
			),
			array(
				'type' => 'select',
				'options' => array(
					'scroll' => __('Scroll', 'olla'),
					'fixed' => __('Fixed', 'olla')
				),
				'param_name' => 'background_attachment',
				'label' => __('Background Image Attachment', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'select',
				'options' => array(
					'auto' => __('Auto', 'olla'),
					'cover' => __('Cover', 'olla'),
					'contain' => __('Contain', 'olla')
				),
				'param_name' => 'background_size',
				'label' => __('Background Image Size', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'select',
				'options' => array(
					'show' => __('Show','olla'),
					'hide' => __('Hide', 'olla')
				),
				'param_name' => 'background_image_xs',
				'label' => __('Background Image Mobile', 'olla'),
				'section' => 'style'
			),
			array(
				'type' => 'note',
				'help' => __('Set left and right margins or padding on Columns.', 'olla'),
				'section' => 'style'
			)
		);

		//filter row params
		$params = apply_filters('olla_row_params', $params);

		//add style params
		global $olla_style_params;
		$style_params = $olla_style_params;
		$params = array_merge_recursive($params, $style_params);

		//sort params into sections
		$olla_param_sections = olla_get_sorted_param_sections($params);
		$tabset_id = uniqid();
		?>
		<div class="modal fade" id="editRowModal" tabindex="-1" role="dialog" aria-labelledby="ollaModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header has-tabs">
						<h4 class="modal-title" id="ollaModalLabel"><?php _e('Row', 'olla'); ?></h4>
						<a href="#" class="close" data-dismiss="modal" aria-label="<?php _e('Close', 'olla'); ?>"><span aria-hidden="true"><i class="material-icons">&#xE5CD;</i></span></a>
						<?php
						echo '<ul class="nav nav-tabs" role="tablist">';
							$active_tab = true;
							for($i=0; $i < count($olla_param_sections); $i++) {
								if (count($olla_param_sections[$i]['params']) > 0) {
							    	echo '<li role="presentation"'. ($active_tab == true ? ' class="active"' : '') .'><a href="#'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" aria-controls="options" role="tab" data-toggle="tab">'. esc_html($olla_param_sections[$i]['name']) .'</a></li>';
							    	$active_tab = false;
						    	} else {
							    	echo '<li role="presentation" style="display:none;"><a href="#'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" aria-controls="options" role="tab" data-toggle="tab">'. esc_html($olla_param_sections[$i]['name']) .'</a></li>';
						    	}
							}
						echo '</ul>';
						?>
					</div>
					<div class="modal-body">
						<form role="form">
							<?php
							echo '<div class="tab-content">';
								$active_tab = true;
								for($i=0; $i < count($olla_param_sections); $i++) {
									if (count($olla_param_sections[$i]['params']) > 0) {
										echo '<div role="tabpanel" class="tab-pane'. ($active_tab == true ? ' active' : '') .'" id="'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" data-section-id="'. esc_attr($olla_param_sections[$i]['id']) .'">';
											foreach($olla_param_sections[$i]['params'] as $param) {
												//check for saved values
												if (!empty($param['param_name'])) {
													if (!isset($param['content_field']) && $param['param_name'] == 'ollacontent') {
														$param['content_field'] = true;
													} elseif (empty($param['content_field'])) {
														$param['content_field'] = false;
													}
													$data_attribute_name = str_replace('_', '-', $param['param_name']);
													if ( array_key_exists($data_attribute_name, $saved_values) ) {
														$param_value = stripslashes($saved_values[$data_attribute_name]);
														if (!empty($param['encode_base64'])) {
															$param_value = wp_strip_all_tags($param_value);
															$param_value = htmlentities(base64_decode($param_value));
														} else if (!empty($param['encode_url'])) {
															$param_value = wp_strip_all_tags($param_value);
															$param_value = urldecode($param_value);
														}
														//decode custom entities
														$param_value = FusionCore::decode_custom_entities($param_value);
													} else {
														$param_value = '';
													}
												} else {
													$param_value = '';
												}
												//check for dependency
												$dependency = !empty($param['dependency']) ? true : false;
												if ($dependency === true) {
													$depends_on_param = $param['dependency']['param_name'];
													$depends_on_not_empty = !empty($param['dependency']['not_empty']) ? $param['dependency']['not_empty'] : false;
													if (!empty($param['dependency']['value']) && is_array($param['dependency']['value'])) {
														$depends_on_value = json_encode($param['dependency']['value']);
													} else if (!empty($param['dependency']['value'])) {
														$depends_on_value = $param['dependency']['value'];
													} else {
														$depends_on_value = '';
													}
													$dependency_callback = !empty($param['dependency']['callback']) ? $param['dependency']['callback'] : '';
													$dependency_string = ' data-dependency-param="'. esc_attr($depends_on_param) .'"'. ($depends_on_not_empty === true ? ' data-dependency-not-empty="true"' : '') . (!empty($depends_on_value) ? ' data-dependency-value="'. esc_attr($depends_on_value) .'"' : '') . (!empty($dependency_callback) ? ' data-dependency-callback="'. esc_attr($dependency_callback) .'"' : '');
												}
												echo '<div class="form-group'. ( !empty($param['class']) ? ' '. esc_attr($param['class']) : '' ) .'"'. ( $dependency === true ? $dependency_string : '' ) .'>';
													echo self::get_input_field($param, $param_value);
												echo '</div>';
											}
										echo '</div>';
										$active_tab = false;
									} else {
										echo '<div role="tabpanel" class="tab-pane" id="'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" data-section-id="'. esc_attr($olla_param_sections[$i]['id']) .'"></div>';
									}
								}
							echo '</div>';
							?>
						</form>
					</div>
					<div class="modal-footer">
						<span class="save-notice"><?php _e('Changes will be saved on close.', 'olla'); ?></span>
						<button type="button" class="button" data-dismiss="modal"><?php _e('Close', 'olla'); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		exit;
	}

	/**
	 * Render edit column modal.
	 *
	 * @since 1.0.0
	 */

	public function render_edit_column_modal() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
			die( '-1' );

		$saved_values = !empty($_POST['saved_values']) ? $_POST['saved_values'] : '';
		if (empty($saved_values)) {
			$saved_values = array();
		} else {
			foreach($saved_values as $key => $value) {
				$saved_values[$key] = wp_filter_post_kses($value);
			}
		}
		$column_style_options = array(
			'light' => __('Light', 'olla'),
			'dark' => __('Dark', 'olla')
		);
		$column_style_options = apply_filters('olla_column_style_options', $column_style_options);
		//map column parameters
		$params = array(
			array(
				'type' => 'select',
				'options' => $column_style_options,
				'param_name' => 'column_style',
				'label' => __('Theme', 'olla'),
				'help' => __('Choose Column theme. Light will inherit globally set text color. Dark will adopt text color set within this Column for headlines and links.', 'olla'),
				'section' => 'style'
			)
		);

		//filter column params
		$params = apply_filters('olla_column_params', $params);

		//add style params
		global $olla_style_params;
		$style_params = $olla_style_params;
		$params = array_merge_recursive($params, $style_params);

		//sort params into sections
		$olla_param_sections = olla_get_sorted_param_sections($params);
		$tabset_id = uniqid();
		?>
		<div class="modal fade" id="editColModal" tabindex="-1" role="dialog" aria-labelledby="ollaModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg">
				<div class="modal-content">
					<div class="modal-header has-tabs">
						<h4 class="modal-title" id="ollaModalLabel"><?php _e('Column', 'olla'); ?></h4>
						<a href="#" class="close" data-dismiss="modal" aria-label="<?php _e('Close', 'olla'); ?>"><span aria-hidden="true"><i class="material-icons">&#xE5CD;</i></span></a>
						<?php
						echo '<ul class="nav nav-tabs" role="tablist">';
							$active_tab = true;
							for($i=0; $i < count($olla_param_sections); $i++) {
								if (count($olla_param_sections[$i]['params']) > 0) {
							    	echo '<li role="presentation"'. ($active_tab == true ? ' class="active"' : '') .'><a href="#'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" aria-controls="options" role="tab" data-toggle="tab">'. esc_html($olla_param_sections[$i]['name']) .'</a></li>';
							    	$active_tab = false;
						    	} else {
							    	echo '<li role="presentation" style="display:none;"><a href="#'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" aria-controls="options" role="tab" data-toggle="tab">'. esc_html($olla_param_sections[$i]['name']) .'</a></li>';
						    	}
							}
						echo '</ul>';
						?>
					</div>
					<div class="modal-body">
						<form role="form">
							<?php
							echo '<div class="tab-content">';
								$active_tab = true;
								for($i=0; $i < count($olla_param_sections); $i++) {
									if (count($olla_param_sections[$i]['params']) > 0) {
										echo '<div role="tabpanel" class="tab-pane'. ($active_tab == true ? ' active' : '') .'" id="'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" data-section-id="'. esc_attr($olla_param_sections[$i]['id']) .'">';
											foreach($olla_param_sections[$i]['params'] as $param) {
												//check for saved values
												if (!empty($param['param_name'])) {
													if (!isset($param['content_field']) && $param['param_name'] == 'ollacontent') {
														$param['content_field'] = true;
													} elseif (empty($param['content_field'])) {
														$param['content_field'] = false;
													}
													$data_attribute_name = str_replace('_', '-', $param['param_name']);
													if ( array_key_exists($data_attribute_name, $saved_values) ) {
														$param_value = stripslashes($saved_values[$data_attribute_name]);
														if (!empty($param['encode_base64'])) {
															$param_value = wp_strip_all_tags($param_value);
															$param_value = htmlentities(base64_decode($param_value));
														} else if (!empty($param['encode_url'])) {
															$param_value = wp_strip_all_tags($param_value);
															$param_value = urldecode($param_value);
														}
														//decode custom entities
														$param_value = FusionCore::decode_custom_entities($param_value);
													} else {
														$param_value = '';
													}
												} else {
													$param_value = '';
												}
												//check for dependency
												$dependency = !empty($param['dependency']) ? true : false;
												if ($dependency === true) {
													$depends_on_param = $param['dependency']['param_name'];
													$depends_on_not_empty = !empty($param['dependency']['not_empty']) ? $param['dependency']['not_empty'] : false;
													if (!empty($param['dependency']['value']) && is_array($param['dependency']['value'])) {
														$depends_on_value = json_encode($param['dependency']['value']);
													} else if (!empty($param['dependency']['value'])) {
														$depends_on_value = $param['dependency']['value'];
													} else {
														$depends_on_value = '';
													}
													$dependency_callback = !empty($param['dependency']['callback']) ? $param['dependency']['callback'] : '';
													$dependency_string = ' data-dependency-param="'. esc_attr($depends_on_param) .'"'. ($depends_on_not_empty === true ? ' data-dependency-not-empty="true"' : '') . (!empty($depends_on_value) ? ' data-dependency-value="'. esc_attr($depends_on_value) .'"' : '') . (!empty($dependency_callback) ? ' data-dependency-callback="'. esc_attr($dependency_callback) .'"' : '');
												}
												echo '<div class="form-group'. ( !empty($param['class']) ? ' '. esc_attr($param['class']) : '' ) .'"'. ( $dependency === true ? $dependency_string : '' ) .'>';
													echo self::get_input_field($param, $param_value);
												echo '</div>';
											}
										echo '</div>';
										$active_tab = false;
									} else {
										echo '<div role="tabpanel" class="tab-pane" id="'. esc_attr($olla_param_sections[$i]['id']) .'-'. esc_attr($tabset_id) .'" data-section-id="'. esc_attr($olla_param_sections[$i]['id']) .'"></div>';
									}
								}
							echo '</div>';
							?>
						</form>
					</div>
					<div class="modal-footer">
						<span class="save-notice"><?php _e('Changes will be saved on close.', 'olla'); ?></span>
						<button type="button" class="button" data-dismiss="modal"><?php _e('Close', 'olla'); ?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		exit;
	}

	/**
	 * Get input field for compontent modals.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The type of input field to get.
	 * @param string $param_name The name to be assigned to the input field.
	 * @param string $param_value The input field's value (if already set).
	 */

	public static function get_input_field($param, $param_value = '') {
		if (!isset($param['content_field'])) {
			$param['content_field'] = false;
		}
		$input = '';
		switch($param['type']) {
			case 'text':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<input type="text" class="form-control element-input'. (!empty($param['nested']) ? ' nested' : '') . (!empty($param['encode_base64']) ? ' encode-base64' : '') . (!empty($param['encode_url']) ? ' encode-url' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'"'. (!empty($param['placeholder']) ? ' placeholder="'. esc_attr($param['placeholder']) .'"' : '') .'>';
				break;
			case 'textarea':
				if (!empty($param['encode_base64']) || !empty($param['encode_url'])) {
					$param_value = $param_value;
				} elseif (!empty($param['content_field']) && empty($param['encode_base64']) && empty($param['encode_url'])) {
					$param_value = esc_textarea(olla_unautop($param_value));
				} else {
					$param_value = esc_textarea($param_value);
				}
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<textarea class="form-control element-input'. (!empty($param['content_field']) ? ' content-field' : '') .  (!empty($param['nested']) ? ' nested' : '') . (!empty($param['encode_base64']) ? ' encode-base64' : '') . (!empty($param['encode_url']) ? ' encode-url' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" rows="5">'. $param_value .'</textarea>';
				break;
			case 'checkbox':
				$input .= '<div class="checkbox">';
					$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">';
						$input .= '<input type="checkbox" class="element-input'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'"'. checked( $param_value, 'on', false ) .'>';
						$input .= esc_html($param['label']);
					$input .= '</label>';
				$input .= '</div>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				break;
			case 'radio':
				$input .= '<label>'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				//select fist radio button option if value is not set
				if (empty($param_value)) {
					$option_keys = array_keys($param['options']);
					$param_value = $option_keys[0];
				}
				foreach($param['options'] as $key => $value) {
					$input .= '<div class="radio">';
						$input .= '<label>';
							$input .= '<input type="radio" class="element-input'. (!empty($param['nested']) ? ' nested' : '') .'" value="'. esc_attr($key) .'" name="'. esc_attr($param['param_name']) .'"'. checked( $param_value, $key, false ) .'>';
							$input .= $value;
						$input .= '</label>';
			    	$input .= '</div>';
		    	}
		    	break;
			case 'select':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<select class="form-control element-input'. (!empty($param['nested']) ? ' nested' : '') .'" name="'. esc_attr($param['param_name']) .'">';
					foreach($param['options'] as $key => $value) {
						$input .= '<option value="'. esc_attr($key) .'"'. selected( $param_value, $key, false ) .'>'. esc_html($value) .'</option>';
					}
				$input .= '</select>';
				break;
			case 'select_post':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<select class="form-control element-input select2-posts-element'. (!empty($param['nested']) ? ' nested' : '') .'" name="'. esc_attr($param['param_name']) .'" style="width:100%;" data-post-type="'. (!empty($param['post_type']) ? esc_attr(json_encode($param['post_type'])) : 'post' ) .'" data-placeholder="'. __('Choose an Option.', 'olla') .'">';
					$input .= '<option></option>';
					if (!empty($param_value)) {
						$input .= '<option value="'. $param_value .'" selected>'. get_the_title($param_value) .'</option>';
					}
				$input .= '</select>';
				break;
			case 'textarea_rte':
				$input .= '<label>'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				if ($param['content_field'] == false) {
					ob_start();
					wp_editor($param_value, 'ollacontent', array('editor_class' => 'element-input'));
					$input .= ob_get_clean();
				} else {
					ob_start();
					wp_editor($param_value, 'ollacontent');
					$input .= ob_get_clean();
				}
				break;
			case 'colorpicker':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<input type="text" class="form-control element-input olla-color-picker'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'">';
				break;
			case 'image':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<input type="hidden" class="form-control element-input'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'">';
				if ( !empty($param_value) ) {
			    	$image_attrs = wp_get_attachment_image_src($param_value, 'medium');
			    	$input .= '<img src="'. esc_url($image_attrs[0]) .'" class="image-field-preview" alt="">';
				}
				$button_verb_empty = __('Add', 'olla');
				$button_verb_isset = __('Edit', 'olla');
				$button_verb = !empty($param_value) ? $button_verb_isset : $button_verb_empty;
				$input .= '<a href="#" class="olla_upload_image button-secondary" data-empty="'. esc_attr($button_verb_empty) .'" data-isset="'. esc_attr($button_verb_isset) .'"><span class="button-verb">'. $button_verb .'</span> '. __('Image', 'olla') .'</a>';
				$input .= '<a href="#" class="olla-remove-image button-secondary'. (empty($param_value) ? ' deactivated' : '') .'">'. __('Remove Image', 'olla') .'</a>';
				break;
			case 'video':
				$input .= '<label for="olla_'. esc_attr($param['param_name']) .'">'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<input type="hidden" class="form-control element-input'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'">';
				if ( !empty($param_value) ) {
			    	$image_attrs = wp_get_attachment_image_src($param_value, 'thumbnail', true);
			    	$input .= '<img src="'. esc_url($image_attrs[0]) .'" class="video-field-preview" alt="">';
				}
				$button_verb_empty = __('Add', 'olla');
				$button_verb_isset = __('Edit', 'olla');
				$button_verb = !empty($param_value) ? $button_verb_isset : $button_verb_empty;
				$input .= '<a href="#" class="olla_upload_video button-secondary" data-empty="'. esc_attr($button_verb_empty) .'" data-isset="'. esc_attr($button_verb_isset) .'"><span class="button-verb">'. $button_verb .'</span> '. __('Video', 'olla') .'</a>';
				$input .= '<a href="#" class="olla-remove-video button-secondary'. (empty($param_value) ? ' deactivated' : '') .'">'. __('Remove Video', 'olla') .'</a>';
				break;
			case 'button':
				if (!empty($param_value)) {
					$button_array = json_decode($param_value);
					$saved_button_link = !empty($button_array->link) ? $button_array->link : '';
					$saved_button_label = !empty($button_array->label) ? $button_array->label : '';
					$saved_button_attached_id = !empty($button_array->attachedID) ? $button_array->attachedID : '';
					$saved_button_target = !empty($button_array->target) ? $button_array->target : '';
					$saved_button_type = !empty($button_array->type) ? $button_array->type : '';
					$saved_button_collapse_id = !empty($button_array->collapseID) ? $button_array->collapseID : '';
					$saved_button_collapse_label_show = !empty($button_array->collapseLabelShow) ? $button_array->collapseLabelShow : '';
					$saved_button_collapse_label_hide = !empty($button_array->collapseLabelHide) ? $button_array->collapseLabelHide : '';
					$saved_button_component_id = !empty($button_array->componentID) ? $button_array->componentID : '';
				}
				$input .= '<label>'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<div class="button-summary">';
					if (!empty($param_value)) {
						switch($saved_button_type) {
							case 'external':
								$input .= '<p>'. __('Type', 'olla') .': <strong>'. __('External Link', 'olla') .'</strong></p>';
								$input .= !empty($saved_button_link) ? '<p>'. __('Links to', 'olla') .': <strong>'. esc_html($saved_button_link) .'</strong></p>' : '';
								$input .= !empty($saved_button_label) ? '<p>'. __('Label', 'olla') .': <strong>'. esc_html($saved_button_label) .'</strong></p>' : '';
								switch($saved_button_target) {
									case '_blank':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('New Window / Tab', 'olla') .'</strong></p>';
										break;
									case '_parent':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Parent Frame', 'olla') .'</strong></p>';
										break;
									case '_top':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Full Body of the Window', 'olla') .'</strong></p>';
										break;
									default:
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Current Window / Tab', 'olla') .'</strong></p>';
								}
								break;
							case 'internal':
								$input .= '<p>'. __('Type', 'olla') .': <strong>'. __('Internal Link', 'olla') .'</strong></p>';
								$input .= !empty($saved_button_attached_id) ? '<p>'. __('Links to', 'olla') .': <strong>'. get_the_title($saved_button_attached_id) .'</strong></p>' : '';
								$input .= !empty($saved_button_label) ? '<p>'. __('Label', 'olla') .': <strong>'. esc_html($saved_button_label) .'</strong></p>' : '';
								switch($saved_button_target) {
									case '_blank':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('New Window / Tab', 'olla') .'</strong></p>';
										break;
									case '_parent':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Parent Frame', 'olla') .'</strong></p>';
										break;
									case '_top':
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Full Body of the Window', 'olla') .'</strong></p>';
										break;
									default:
										$input .= '<p>'. __('Opens in', 'olla') .': <strong>'. __('Current Window / Tab', 'olla') .'</strong></p>';
								}
								break;
							case 'collapse':
								if (!empty($saved_button_component_id)) {
									$saved_button_collapse_id = get_the_title($saved_button_component_id);
								}
								$input .= '<p>'. __('Type', 'olla') .': <strong>'. __('Collapse', 'olla') .'</strong></p>';
								$input .= !empty($saved_button_collapse_id) ? '<p>'. __('Opens', 'olla') .': <strong>'. esc_html($saved_button_collapse_id) .'</strong></p>' : '';
								$input .= !empty($saved_button_collapse_label_show) ? '<p>'. __('Show Label', 'olla') .': <strong>'. esc_html($saved_button_collapse_label_show) .'</strong></p>' : '';
								$input .= !empty($saved_button_collapse_label_hide) ? '<p>'. __('Hide Label', 'olla') .': <strong>'. esc_html($saved_button_collapse_label_hide) .'</strong></p>' : '';
								break;
							case 'modal':
								if (!empty($saved_button_component_id)) {
									$saved_button_modal_id = get_the_title($saved_button_component_id);
								}
								$input .= '<p>'. __('Type', 'olla') .': <strong>'. __('Modal', 'olla') .'</strong></p>';
								$input .= !empty($saved_button_modal_id) ? '<p>'. __('Opens', 'olla') .': <strong>'. esc_html($saved_button_modal_id) .'</strong></p>' : '';
								$input .= !empty($saved_button_label) ? '<p>'. __('Label', 'olla') .': <strong>'. esc_html($saved_button_label) .'</strong></p>' : '';
								break;
						}
					}
				$input .= '</div>';
				$button_verb_empty = __('Add', 'olla');
				$button_verb_isset = __('Edit', 'olla');
				$button_verb = !empty($param_value) ? $button_verb_isset : $button_verb_empty;
				$input .= '<a href="#" class="olla-add-edit-button button-secondary" data-empty="'. esc_attr($button_verb_empty) .'" data-isset="'. esc_attr($button_verb_isset) .'"><span class="button-verb">'. $button_verb .'</span> '. __('Button', 'olla') .'</a>';
				$input .= '<a href="#" class="olla-remove-button button-secondary'. (empty($param_value) ? ' deactivated' : '') .'">'. __('Remove Button', 'olla') .'</a>';
				$input .= '<input type="hidden" class="form-control element-input button-string'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'">';
				break;
			case 'box':
				if (!empty($param_value)) {
					$box_array = json_decode($param_value);
					$box_top = !empty($box_array->top) ? $box_array->top : '';
					$box_right = !empty($box_array->right) ? $box_array->right : '';
					$box_bottom = !empty($box_array->bottom) ? $box_array->bottom : '';
					$box_left = !empty($box_array->left) ? $box_array->left : '';
				}
				$input .= '<label>'. esc_html($param['label']) .'</label>';
				$input .= !empty($param['help']) ? '<p class="help-block">'. esc_html($param['help']) .'</p>' : '';
				$input .= '<div class="olla-box-form">';
					$input .= '<label for="olla_'. esc_attr($param['param_name']) .'_top">'. __('Top', 'olla') .'</label>';
					$input .= '<input type="text" class="form-control box-top" id="olla_'. esc_attr($param['param_name']) .'_top" name="'. esc_attr($param['param_name']) .'_top" value="'. (!empty($box_top) ? esc_attr($box_top) : '') .'">';
					$input .= '<label for="olla_'. esc_attr($param['param_name']) .'_right">'. __('Right', 'olla') .'</label>';
					$input .= '<input type="text" class="form-control box-right" id="olla_'. esc_attr($param['param_name']) .'_right" name="'. esc_attr($param['param_name']) .'_right" value="'. (!empty($box_right) ? esc_attr($box_right) : '') .'">';
					$input .= '<label for="olla_'. esc_attr($param['param_name']) .'_bottom">'. __('Bottom', 'olla') .'</label>';
					$input .= '<input type="text" class="form-control box-bottom" id="olla_'. esc_attr($param['param_name']) .'_bottom" name="'. esc_attr($param['param_name']) .'_bottom" value="'. (!empty($box_bottom) ? esc_attr($box_bottom) : '') .'">';
					$input .= '<label for="olla_'. esc_attr($param['param_name']) .'_left">'. __('Left', 'olla') .'</label>';
					$input .= '<input type="text" class="form-control box-left" id="olla_'. esc_attr($param['param_name']) .'_left" name="'. esc_attr($param['param_name']) .'_left" value="'. (!empty($box_left) ? esc_attr($box_left) : '') .'">';
				$input .= '</div>';
				$input .= '<input type="hidden" class="form-control element-input box-string'. (!empty($param['nested']) ? ' nested' : '') .'" id="olla_'. esc_attr($param['param_name']) .'" name="'. esc_attr($param['param_name']) .'" value="'. esc_attr($param_value) .'">';
				break;
			case 'note':
				$input .= !empty($param['label']) ? '<h3 class="olla-element-note-heading">'. esc_html($param['label']) .'</h3>' : '';
				$input .= !empty($param['help']) ? '<p class="olla-element-note-description description">'. esc_html($param['help']) .'</p>' : '';
				break;
		}

		$input = apply_filters('olla_input_types', $input, $param, $param_value);

		return $input;
	}

	/**
	 * Update image field preview
	 *
	 * Updates preview thumbnail for images inserted from the media library
	 *
	 * @since 1.0.0
	 *
	 */

	public function update_image_preview() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if (!empty($_POST['post_id'])) {
			if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
				die( '-1' );
		} else {
			if ( !current_user_can( 'edit_theme_options' ) )
				die( '-1' );
		}

		$attachment_id = intval($_POST['id']);
		$image_attrs = wp_get_attachment_image_src($attachment_id, 'medium');
    	echo '<img src="'. esc_url($image_attrs[0]) .'" class="image-field-preview" alt="">';

		exit;
	}

	/**
	 * Update video field preview
	 *
	 * Updates preview thumbnail for videos inserted from the media library
	 *
	 * @since 1.0.0
	 *
	 */

	public function update_video_preview() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if (!empty($_POST['post_id'])) {
			if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
				die( '-1' );
		} else {
			if ( !current_user_can( 'edit_theme_options' ) )
				die( '-1' );
		}

		$attachment_id = intval($_POST['id']);
		$image_attrs = wp_get_attachment_image_src($attachment_id, 'thumbnail', true);
    	echo '<img src="'. esc_url($image_attrs[0]) .'" class="video-field-preview" alt="">';

		exit;
	}

	/**
	 * Posts Search
	 *
	 * Query the database with AJAX and return a JSON array of results
	 *
	 * @since 1.1.0
	 *
	 */

	public function posts_search() {
		//verify nonce
		check_ajax_referer( 'olla-admin-edit', 'security' );

		//verify capabilities
		if (!empty($_POST['post_id'])) {
			if ( !current_user_can( 'edit_post', intval($_POST['post_id']) ) )
				die( '-1' );
		} else {
			if ( !current_user_can( 'edit_theme_options' ) )
				die( '-1' );
		}

		$posts_per_page = !empty($_POST['posts_per_page']) ? $_POST['posts_per_page'] : get_option('posts_per_page');
		$paged = !empty($_POST['page']) ? intval($_POST['page']) : 1;
		$post_type = !empty($_POST['postType']) ? $_POST['postType'] : 'post';
		if (is_array($post_type)) {
			foreach($post_type as $key => $value) {
				$post_type[sanitize_text_field($key)] = sanitize_text_field($value);
			}
		} else {
			sanitize_text_field($post_type);
		}

		if (!empty($_POST['q'])) {
			global $wpdb;
			$search = esc_sql( $wpdb->esc_like( sanitize_text_field($_POST['q']) ) );
			add_filter('posts_where', function( $where ) use ($search) {
				$where .= (" AND post_title LIKE '%" . $search . "%'");
				return $where;
			});
		}

		$query_args = array(
			'post_type' => $post_type,
			'post_status' => 'publish',
			'posts_per_page' => $posts_per_page,
			'paged' => $paged,
			'orderby' => 'title',
			'order' => 'ASC',
			'fields' => 'id=>parent'
		);

		$matching_items = new WP_Query($query_args);

		$result = array(
			'items' => array(),
			'total_count' => 0
		);

		if (!empty($matching_items->posts)) {
			$result['items'] = array();
			if (!empty($_POST['post_id']) && !empty($_POST['hierarchical'])) {
				$attached_items = array();
				$nonattached_items = array();
				foreach ($matching_items->posts as $item) {
					if ($item->post_parent == $_POST['post_id']) {
						$attached_items[] = array(
							'id' => $item->ID,
							'text' => get_the_title($item->ID)
						);
					} else {
						$nonattached_items[] = array(
							'id' => $item->ID,
							'text' => get_the_title($item->ID)
						);
					}
				}
				if (!empty($attached_items)) {
					$result['items'][] = array(
						'text' => __('Items Attached to this Post', 'olla'),
						'children' => $attached_items
					);
				}
				if (!empty($nonattached_items)) {
					$result['items'][] = array(
						'text' => __('Other Items', 'olla'),
						'children' => $nonattached_items
					);
				}
			} else {
				foreach($matching_items->posts as $item) {
					$result['items'][] = array(
						'id' => $item->ID,
						'text' => get_the_title($item->ID)
					);
				}
			}
			$result['total_count'] = $matching_items->found_posts;
		}

		echo json_encode($result);

		exit;
	}

}

$olla_core = new OllaCore();
