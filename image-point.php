<?php
/*
Plugin Name: Image Point
Plugin URI: http://saturnplugins.com
Description: A lightweight and responsive image map WordPress plugin
Author: SaturnPlugins
Version: 1.0.1
Author URI: http://saturnplugins.com/
*/

class ST_Image_Point
{
    public function __construct()
    {
        define('SIP_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
        define('SIP_URL', untrailingslashit(plugins_url('/', __FILE__)));
        define('SIP_VERSION', '1.0.12');

        if (is_admin()) {
            include_once(SIP_PATH . '/admin/sip-admin.php');
        }

        if (!is_admin()) {
            include_once(SIP_PATH . '/inc/sip-render.php');
        }

        load_plugin_textdomain('image-point', false, dirname(plugin_basename(__FILE__)) . '/languages/');

        add_action('init', array($this, 'register_post_type'));
        add_shortcode('image_point', array($this, 'shortcode'));
    }

    public function register_post_type()
    {
        register_post_type('image_point',
            array(
                'labels' => array(
                    'name' => __('مجموعات المعالم', 'saturnthemes-post-types'),
                    'singular_name' => __('مجموعة معالم', 'saturnthemes-post-types'),
                    'menu_name' => _x('تعرف على القدس', 'Admin menu name', 'saturnthemes-post-types'),
                    'add_new' => __('إضافة مجموعة معالم', 'saturnthemes-post-types'),
                    'add_new_item' => __('إضافة مجموعة معالم جديدة', 'saturnthemes-post-types'),
                    'edit' => __('تعديل', 'saturnthemes-post-types'),
                    'edit_item' => __('تعديل مجموعة المعالم', 'saturnthemes-post-types'),
                    'new_item' => __('مجموعة معالم جديدة', 'saturnthemes-post-types'),
                    'view' => __('عرض مجموعة المعالم', 'saturnthemes-post-types'),
                    'view_item' => __('عرض مجموعة المعالم', 'saturnthemes-post-types'),
                    'search_items' => __('بحث عن مجموعة معالم', 'saturnthemes-post-types'),
                    'not_found' => __('لم يتم العثور على مجموعات معالم', 'saturnthemes-post-types'),
                    'not_found_in_trash' => __('لا يوجد مجموعات معالم في سلة المهملات', 'saturnthemes-post-types'),
                ),
                'public' => true,
                "publicly_queryable" => true,
                'show_in_menu' => true,
                'show_ui' => true,
                'capability_type' => 'post',
                'map_meta_cap' => true,
                'exclude_from_search' => true,
                'hierarchical' => true,
                'has_archive' => "know-quds-archive",
                "rewrite" => array("slug" => "know-quds", "with_front" => true),
                'query_var' => true,
                'supports' => array('title', 'thumbnail', 'editor'),
                'show_in_nav_menus' => false,
                'menu_icon' => 'dashicons-location',
            )
        );

        $labels = array(
            "name" => __('تصنيفات تعرف على القدس', 'illdy'),
            "singular_name" => __('تصنيف', 'illdy'),
        );

        $args = array(
            "label" => __('تصنيفات تعرف على القدس', 'illdy'),
            "labels" => $labels,
            "public" => true,
            "hierarchical" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => array('slug' => 'knowquds', 'with_front' => true,),
            "show_admin_column" => true,
            "show_in_rest" => true,
            "rest_base" => "knowquds",
            "show_in_quick_edit" => false,
        );
        register_taxonomy("knowquds", array("image_point"), $args);
    }

    public function shortcode($atts)
    {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        /** @var WP_Post $image_point */
        $image_point = get_post($atts['id']);

        if (empty($image_point)) {
            return;
        }

        $content = $image_point->post_content;
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);


        $data = get_post_meta($image_point->ID, 'sip_image_point', true);
        $points = get_post_meta($image_point->ID, 'sip_points', true);

        if (empty($data) || empty($points)) {
            return;
        }

        wp_enqueue_style('sip-style');
        wp_enqueue_script('sip-script');

        ?>
        <div class="mt-3 mb-5">
            <?= $content ?>
        </div>
        <div class="sip-wrapper" id="sip-wrapper-<?php echo esc_attr($image_point->ID); ?>">
            <img src="<?php echo esc_url($data['image']); ?>" alt="<?php echo esc_attr($image_point->post_title); ?>"/>
            <?php foreach ($points as $point) : ?>
                <?php $point_type = !empty($point['icon_image']) ? 'image' : 'text'; ?>
                <?php $point_tag = ('link' == $point['popup_type']) ? 'a' : 'div'; ?>
                <a href="#point-details" class="sip-point sip-point-icon-<?= esc_attr($point_type); ?>"
                   data-left="<?= esc_attr($point['left']); ?>" data-top="<?= esc_attr($point['top']); ?>"
                   style="<?= (!empty($point['icon_color'])) ? esc_attr('border-color: ' . $point['icon_color'] . ';') : "" ?>
                   <?= (!empty($point['icon_background'])) ? esc_attr('background-color: ' . $point['icon_background'] . ';box-shadow: 0px 0px 10px 0px ' . $point['icon_background'] . ';') : "" ?>"
                   data-point-id="<?= $point['id'] ?>">
                    <div class="sip-popup sip-popup-<?php echo esc_attr($point['popup_type']); ?> sip-popup-<?php echo esc_attr(!empty($point['popup_position']) ? $point['popup_position'] : 'top'); ?>">
                        <div class="sip-popup-inner">
                            <?php if (!empty($point['description_images'])) : ?>
                                <div class="sip-image">
                                    <img src="<?= $point['description_images'][0]['thumb'] ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($point['popup_title'])) : ?>
                                <div class="sip-popup-title"><?php echo wp_kses_post($point['popup_title']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="sip-points-keymap" id="point-details">
            <ul>
                <li class="point-details card active">
                    <div class="card-body">
                        <div class="empty">
                            <i class="fa fa-exclamation-circle"></i> اختر نقطة من الخريطة
                        </div>
                    </div>
                </li>
                <?php foreach ($points as $point) : ?>
                    <li class="point-details card" id="detail-<?= $point['id'] ?>">
                        <div class="card-body">
                            <?php if (!empty($point['popup_title'])) : ?>
                                <h3><?= wp_kses_post($point['popup_title']); ?></h3>
                            <?php endif; ?>
                            <div class="row">
                                <div class="point-details-text col-md-9">
                                    <?php if (!empty($point['popup_content'])) : ?>
                                        <p><?= wpautop($point['popup_content']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($point['description_images'])) : ?>
                                    <div class="point-images-carousel col-md-3">
                                        <?php foreach ($point['description_images'] as $image) : ?>
                                            <a href="<?= $image['url'] ?>" class="point-images-slide-image"
                                               data-fancybox="point-<?= $point['id'] ?>"><img
                                                        src="<?= $image['thumb'] ?>" alt=""></a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}

new ST_Image_Point();