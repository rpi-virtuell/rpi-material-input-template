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
        add_action('init', array($this, 'register_block_template'));
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

    public function register_block_template()
    {
        $post_type_object = get_post_type_object('materialien');

        $post_type_object->template = array(

//            array('lazyblock/tab-leitfrage', array(), array( array('core/columns', array() , array(array('core/column'))) ) ),

            array('lazyblock/tab-leitfrage', array(), array( array('kadence/column')) )

        );


        $post_type_object->template_lock = 'all';
    }

}

new RpiMaterialInputTemplate();