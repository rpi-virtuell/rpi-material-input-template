<?php

/*
Plugin Name: Rpi Material Input Template
Plugin URI: https://github.com/rpi-virtuell/rpi-material-input-template
Description: Wordpress Plugin to ADD material post type and template
Version: 1.0
Author: Daniel Reintanz
Author URI: https://github.com/FreelancerAMP
License: A "Slug" license name e.g. GPL2
*/

class RpiMaterialInputTemplate
{
    function __construct()
    {
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('init', array($this, 'register_gravity_form'));
        add_action('gform_post_submission', array($this, 'add_template_and_redirect'), 10, 2);
    }

    public function register_custom_post_type()
    {

        /**
         * Post Type: Material.
         */

        $labels = [
            "name" => __("Materialien", "twentytwentytwo"),
            "singular_name" => __("Material", "twentytwentytwo"),
        ];

        $args = [
            "label" => __("Materialien", "twentytwentytwo"),
            "labels" => $labels,
            "description" => "",
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_rest" => true,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "has_archive" => true,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => true,
            "capability_type" => "page",
            "map_meta_cap" => true,
            "hierarchical" => true,
            "can_export" => false,
            "rewrite" => ["slug" => "materialien", "with_front" => true],
            "query_var" => true,
            "supports" => ["title",
                "editor",
                "thumbnail",
                'excerpt',
                'tracksbacks',
                'custom-fields',
                'comments',
                'page-attributes',
                'post-formats'],
            "show_in_graphql" => false,
        ];

        register_post_type("materialien", $args);
    }

    public function register_gravity_form()
    {
// TODO:: DEBUG resource to create inmport file
//        $form = GFAPI::get_form(1);
//       file_put_contents(__DIR__.'/form.dat', serialize($form));

        global $wpdb;
        $form_title = 'materialeingabe';
        $formssql = "SELECT ID FROM {$wpdb->prefix}gf_form WHERE title = %s and is_trash = 0;";
        if (empty($formId = $wpdb->get_var($wpdb->prepare($formssql, $form_title)))) {
            $form = unserialize(file_get_contents(__DIR__ . '/form.dat'));
            $formId = GFAPI::add_form($form);
        }

    }


    function add_template_and_redirect($entry, $form)
    {
        $post = get_post($entry['post_id']);
        if (is_a($post, 'WP_Post')) {
            $terms = wp_get_post_terms($post->ID, 'materialtype');
            foreach ($terms as $term) {
                if (is_a($term, 'WP_Term')) {
                    $post->post_content = file_get_contents(__DIR__ . '/templates/' . $term->slug . '.php');

                }
                wp_update_post($post);
            }
        }
        wp_redirect(get_site_url() . '/wp-admin/post.php?post=' . $entry['post_id'] . '&action=edit');
        exit();
    }

}

new RpiMaterialInputTemplate();