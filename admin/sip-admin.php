<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class SIP_Admin
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 0);

        add_action('save_post_image_point', array($this, 'save'), 10, 2);
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'), 10);
    }

    public function enqueue_scripts()
    {
        global $post_type;
        global $post;

        if (empty($post_type) || 'image_point' != $post_type) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style('wp-color-picker');

        wp_enqueue_script('wp-color-picker-alpha', SIP_URL . '/admin/js/wp-color-picker-alpha.js', array('wp-color-picker'), SIP_VERSION . "1", true);

        if (defined('WC_VERSION')) {
            wp_register_script('select2', WC()->plugin_url() . '/assets/js/select2/select2.full.min.js', array('jquery'), '4.0.3');
            wp_enqueue_script('wc-enhanced-select', WC()->plugin_url() . '/assets/js/admin/wc-enhanced-select.js', array('jquery', 'select2'), WC_VERSION);
            wp_localize_script('wc-enhanced-select', 'wc_enhanced_select_params', array(
                'i18n_no_matches'           => _x('No matches found', 'enhanced select', 'woocommerce'),
                'i18n_ajax_error'           => _x('Loading failed', 'enhanced select', 'woocommerce'),
                'i18n_input_too_short_1'    => _x('Please enter 1 or more characters', 'enhanced select', 'woocommerce'),
                'i18n_input_too_short_n'    => _x('Please enter %qty% or more characters', 'enhanced select', 'woocommerce'),
                'i18n_input_too_long_1'     => _x('Please delete 1 character', 'enhanced select', 'woocommerce'),
                'i18n_input_too_long_n'     => _x('Please delete %qty% characters', 'enhanced select', 'woocommerce'),
                'i18n_selection_too_long_1' => _x('You can only select 1 item', 'enhanced select', 'woocommerce'),
                'i18n_selection_too_long_n' => _x('You can only select %qty% items', 'enhanced select', 'woocommerce'),
                'i18n_load_more'            => _x('Loading more results&hellip;', 'enhanced select', 'woocommerce'),
                'i18n_searching'            => _x('Searching&hellip;', 'enhanced select', 'woocommerce'),
                'ajax_url'                  => admin_url('admin-ajax.php'),
                'search_products_nonce'     => wp_create_nonce('search-products'),
                'search_customers_nonce'    => wp_create_nonce('search-customers'),
            ));
        }
        //wp_enqueue_editor();
        wp_enqueue_script('sip-tinymce', 'https://cloud.tinymce.com/stable/tinymce.min.js', [], SIP_VERSION);

        wp_enqueue_style('sip-style', SIP_URL . '/admin/css/editor.css', array(), SIP_VERSION);
        wp_enqueue_script('sip-script', SIP_URL . '/admin/js/editor.js', array('jquery', 'sip-tinymce', 'underscore', 'wp-util', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-tooltip'), SIP_VERSION);

        $sip_params = array(
            'delete_point_confirm' => __('Are you sure want to remove this point?', 'image-point'),
            'choose_image'         => __('Choose Image', 'image-point'),
            'image_point_data'     => array(),
            'points_data'          => array(),
        );

        if (!empty($post) && !empty($post->ID)) {
            $image_point_data = get_post_meta($post->ID, 'sip_image_point', true);
            if ($image_point_data) {
                $sip_params['image_point_data'] = $image_point_data;
            }

            $points_data = get_post_meta($post->ID, 'sip_points', true);
            if ($points_data) {

                if (defined('WC_VERSION')) {
                    // Prepare name for product
                    foreach ($points_data as &$point) {
                        if (!empty($point['product'])) {
                            $product = wc_get_product($point['product']);

                            $point['product_name'] = $product->get_name();
                        }
                    }
                }

                $sip_params['points_data'] = $points_data;
//				var_dump($points_data);
//				exit;
            }
        }

        wp_localize_script('sip-script', 'sip_params', $sip_params);
    }

    public function add_meta_boxes()
    {
        add_meta_box('sip-preview', __('Preview', 'image-point'), array($this, 'output_metabox_preview'), 'image_point', 'normal', 'high');
        add_meta_box('sip-points', __('Points', 'image-point'), array($this, 'output_metabox_points'), 'image_point', 'normal', 'high');
        add_meta_box('sip-shortcode', __('Shortcode', 'image-point'), array($this, 'output_metabox_shortcode'), 'image_point', 'side', 'high');
    }

    public function output_metabox_preview()
    {
        include(SIP_PATH . '/admin/templates/metabox-preview.php');
    }

    public function output_metabox_points()
    {
        include(SIP_PATH . '/admin/templates/metabox-points.php');
    }

    public function output_metabox_shortcode()
    {
        include(SIP_PATH . '/admin/templates/metabox-shortcode.php');
    }

    public function save($ID, $post)
    {
        if (empty($_POST['sip_image_point']) || empty($_POST['sip_points'])) {
            return true;
        }

        $image_point = stripslashes($_POST['sip_image_point']);
        $points = stripslashes($_POST['sip_points']);


        $image_point = json_decode($image_point, true);
        $points = json_decode($points, true);


        update_post_meta($ID, 'sip_image_point', $image_point);
        update_post_meta($ID, 'sip_points', $points);
    }
}

return new SIP_Admin();