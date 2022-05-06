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

require_once('rpi-material-allowed-blocks.php');

class RpiMaterialInputTemplate
{

    private $allowed_block_types;

    function __construct()
    {
        $this->allowed_block_types = array();
        add_action('admin_menu', array('RpiMaterialAllowedBlocks', 'register_acf_fields'));
        add_action('admin_menu', array('RpiMaterialAllowedBlocks', 'register_template_settings_options_page'));
        add_action('init', array($this, 'register_custom_post_type'));
        add_action('init', array($this, 'register_gravity_form'));
        add_action('gform_after_submission', array($this, 'add_template_and_redirect'), 10, 2);
        add_action('enqueue_block_assets', array($this, 'blockeditor_js'));
        add_action('admin_head', array($this, 'supply_option_data_to_js'));

        add_action('admin_init', array($this, 'check_for_broken_blocks'));

        add_filter('gform_pre_render', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_pre_validation', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_pre_submission_filter', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_admin_pre_render', array($this, 'add_template_selectbox_to_form'));

		//ajax
	    add_action( 'wp_ajax_getTemplate', array( 'RpiMaterialInputTemplate','getTemplate' ));
	    add_action( 'wp_ajax_getTemplates', array( 'RpiMaterialInputTemplate','getTemplates' ));
    }

    public function add_template_selectbox_to_form($form)
    {

        foreach ($form['fields'] as &$field) {
            if ($field->type != 'checkbox') {
                continue;
            }
            $materialtyp_templates = get_posts(
                array(
                    'post_type' => 'materialtyp_template',
                    'numberposts' => -1
                ));
            $choices = array();
            foreach ($materialtyp_templates as $materialtyp_template) {
                $choices[] = array('text' => $materialtyp_template->post_title, 'value' => $materialtyp_template->ID);
            }
            $field->placeholder = 'In welchen Bereich fällt der Beitrag?';
            $field->choices = $choices;
        }
        return $form;
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
////             TODO:: DEBUG resource to create importable file for Gravity Forms
//                    $form = GFAPI::get_form(63);
//                   file_put_contents(__DIR__.'/form.dat', serialize($form));

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
        $template_ids = array();

        foreach ($_POST as $input_key => $input_value) {
            if (str_starts_with($input_key, 'input_9_')) {
                $template_ids[] = $input_value;
            }
        }

        $post = get_post($entry['post_id']);
        if (is_a($post, 'WP_Post') && !empty($template_ids)) {
            foreach ($template_ids as $template_id) {
                $template = get_post($template_id);
                if (is_a($template, 'WP_Post')) {
                    $post->post_content .= $template->post_content;
                }
                wp_update_post($post);
            }
        }

        wp_redirect(get_site_url() . '/wp-admin/post.php?post=' . $entry['post_id'] . '&action=edit');
        GFAPI::delete_entry($entry['id']);
        exit();
    }

    function blockeditor_js()
    {
        if (!is_admin()) return;
        wp_enqueue_script(
            'template_handling',
            plugin_dir_url(__FILE__) . '/assets/js/template_handling_editor.js'
        );
	    wp_enqueue_style(
		    'template_handling_style',
		    plugin_dir_url(__FILE__) . '/assets/js/template_handling_editor.css'
	    );
    }

    public function supply_option_data_to_js()
    {
        $this->allowed_block_types = json_encode(get_field('allowed_block_types', 'option'));
        $post_type = json_encode(get_field('template_post_type', 'option'));

        echo
        "<script>
                const rpi_material_input_template = 
                {
                    options:
                    {
                        allowed_blocks: JSON.parse('$this->allowed_block_types'),
                        post_type: '$post_type'
                    }
                }
        </script>";
    }

    public function check_for_broken_blocks()
    {
        if (isset($_GET['post'], $_GET['action']) && $_GET['action'] === 'edit') {
            $post = get_post($_GET['post']);
            if (is_a($post, 'WP_Post') && $post->post_type == get_field('template_post_type', 'option')) {
                $existing_blocks = array();
                $this->allowed_block_types = get_field('allowed_block_types', 'option');
                $blocks = parse_blocks($post->post_content);
                foreach ($blocks as $block_key => $block) {
                    if (!empty($block['blockName']) && !empty($this->allowed_block_types)) {
                        if (in_array($block['blockName'], $this->allowed_block_types) && !in_array($block['blockName'], $existing_blocks)) {
                            $existing_blocks[] = $block['blockName'];
                            continue;
                        }
                        $existing_blocks[] = $block['blockName'];
                        $blocks[$block_key]['blockName'] = 'lazyblock/reli-default-block';
                        $blocks[$block_key]['attrs']['blockUniqueClass'] = 'lazyblock/reli-default-block-' . $block['attrs']['blockId'];

                    }
                }
                $post->post_content = serialize_blocks($blocks);
                wp_update_post($post);
            }
        }

    }
	function getTemplates(){
		$posts = get_posts([
			'post_status' => 'publish',
			'post_type'=> 'materialtyp_template',
            'numberposts' => 10,
			'orderby' => 'meta_value_num post_id'

		]);
        if($posts && count($posts)===0){
            echo '';
            die();
        }

		?>
        <hr/>
        <div class="controll-panel">
		    <div class="block-editor-block-inspector" style="margin-left: 20px">

                <h2 class="components-panel__body-title">Materialvorlage ändern</h2>
                <div>
                    <p>Was soll in deinem Material vorkommen?</p>
                    <ul><?php
                        $i = 0;
	                    foreach ($posts as $post){
                            $i ++;
                            $blocks = parse_blocks($post->post_content);
		                    $attr = [];
                            foreach ($blocks as  $block){

                                if(strpos($block['blockName'], "lazyblock/reli")!==false){
	                                $attr[] = $block['blockName'];
                                }
                            }
	                        $data = implode(',',$attr);
		                    $top = 0;
                            if($i < 4){
	                            $top = 1;
		                    }
	                        echo '<li class="reli-inserter" data="'.$data.'"><a href="javascript:RpiMaterialInputTemplate.insert('.$post->ID.','.$top.')"></a> <span>'.$post->post_title.'</span></li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
		</div>
		<?php
		die();
	}
	function getTemplate(){
		$post_id = $_GET['id'];
		$post = get_post($post_id);
		echo $post->post_content;
		die();
	}
}

new RpiMaterialInputTemplate();
