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

        //Autorenseiten
	    add_action('pre_get_posts',array($this, 'alter_author_posts'),999);
	    add_action( 'the_title',  array( $this,'the_title'),10,2 );
	    add_filter( 'get_usernumposts', array($this,'get_author_usernumposts'),10, 4 );

        add_filter( 'admin_init', array($this,'add_capabilities'),10, 4 );
	    //ajax
	    add_action( 'wp_ajax_getTemplate', array( 'RpiMaterialInputTemplate','getTemplate' ));
	    add_action( 'wp_ajax_getTemplates', array( 'RpiMaterialInputTemplate','getTemplates' ));


	    //hide taxonomy Metaboxes in Block-Editor
	    add_filter( 'rest_prepare_taxonomy',  array($this,'hide_taxonomy_metaboxes'),10, 3 );


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
            "exclude_from_search" => false,
            'capability_type' => array('material','materials'),
	        "capabilities" =>array(
		        'edit_post' => 'edit_material',
		        'edit_posts' => 'edit_materials',
		        'edit_others_posts' => 'edit_others_materials',
		        'delete_others_posts' => 'delete_others_materials',
		        'delete_published_posts' => 'delete_published_materials',
		        'read_private_posts' => 'read_private_materials',
		        'publish_posts' => 'publish_materials',
		        'read_post' => 'read_material',
		        'delete_post' => 'delete_material',
		        'delete_posts' => 'delete_materials',
	        ),
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => true,
            "rewrite" => ["slug" => "materialien", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-list-view",
            "supports" => [
                    'title',
                    "editor",
                    "thumbnail",
                    'excerpt',
                    'custom-fields',
                    'tracksbacks',
                    'comments',
                    'revisions',
                    'author'
            ],
            'taxonomies' => ['kinderaktivitaten','kinderfahrung','anlass','alter','schlagwort'],
            "show_in_graphql" => false,
        ];

        register_post_type("materialien", $args);

	    register_post_meta( 'materialien', 'workflow_step', array(
		    'show_in_rest' => true,
		    'single' => true,
		    'type' => 'number',
            'default'=> 0
	    ) );



	    $labels = [
		    "name" => __( "Materialvorlagen", "blocksy" ),
		    "singular_name" => __( "Materialvorlage", "blocksy" ),
	    ];

	    $args = [
		    "label" => __( "Materialvorlagen", "blocksy" ),
		    "labels" => $labels,
		    "description" => "",
		    "public" => true,
		    "publicly_queryable" => true,
		    "show_ui" => true,
		    "show_in_rest" => true,
		    "rest_base" => "",
		    "rest_controller_class" => "WP_REST_Posts_Controller",
		    "has_archive" => false,
		    "show_in_menu" => true,
		    "show_in_nav_menus" => true,
		    "delete_with_user" => false,
		    "exclude_from_search" => false,
		    "capability_type" => "post",
		    "map_meta_cap" => true,
		    "hierarchical" => false,
		    "can_export" => false,
		    "rewrite" => [ "slug" => "materialtyp_template", "with_front" => true ],
		    "query_var" => true,
		    "menu_icon" => "dashicons-welcome-widgets-menus",
		    "supports" => [ "title", "editor", "thumbnail","custom-fields" ],
		    "show_in_graphql" => false,
	    ];

	    register_post_type( "materialtyp_template", $args );


        /**
         * assign taxonomies **********************************************************************************************
         */



        /**
         * Taxonomy: Einrichtungen.
         * slug: organisation
         */

        $labels = [
            "name" => __( "Einrichtungen", "blocksy" ),
            "singular_name" => __( "Einrichtung", "blocksy" ),
        ];


        $args = [
            "label" => __( "Einrichtungen", "blocksy" ),
            "labels" => $labels,
            "public" => true,
            "publicly_queryable" => true,
            "hierarchical" => false,
            "show_ui" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "query_var" => true,
            "rewrite" => [ 'slug' => 'organisation', 'with_front' => true, ],
            "show_admin_column" => true,
            "show_in_rest" => true,
            "show_tagcloud" => false,
            "rest_base" => "organisation",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace" => "wp/v2",
            "show_in_quick_edit" => false,
            "sort" => false,
            "show_in_graphql" => false,
            "meta_box_cb" => false,
            "capabilities" => array(
                'manage_terms'=> 'manage_organisation',
                'edit_terms'=> 'edit_organisation',
                'delete_terms'=> 'delete_organisation',
                'assign_terms' => 'assign_organisation'
            ),
        ];
        register_taxonomy( "organisation", [ "materialien" ], $args );



    }

    public function add_capabilities(){

	    $roles = ['administrator', 'editor'];

	    foreach ($roles as $roleslug){

             $role = get_role( $roleslug );



            $role->add_cap( 'read_material' );
            $role->add_cap( 'edit_materials' );
            $role->add_cap( 'edit_published_materials' );
            $role->add_cap( 'delete_materials' );
            $role->add_cap( 'publish_materials' );

		    $role->add_cap( 'edit_others_materials' );
		    $role->add_cap( 'edit_published_materials' );
		    $role->add_cap( 'delete_published_materials' );
		    $role->add_cap( 'read_private_materials' );
		    $role->add_cap( 'edit_private_materials' );
		    $role->add_cap( 'delete_private_materials' );
		    $role->add_cap( 'delete_others_materials' );

		    $role->add_cap( 'manage_organisation' );
		    $role->add_cap( 'edit_organisation' );
		    $role->add_cap( 'assign_organisation' );
		    $role->add_cap( 'delete_organisation' );
        }

	    add_role( 'autorin', 'Autor:in');

            $role = get_role( 'autorin' );
            $role->add_cap( 'upload_files' );
            $role->add_cap( 'read' );
            $role->add_cap( 'level_2' );
            $role->add_cap( 'level_1' );
            $role->add_cap( 'level_0' );
            $role->add_cap( 'read_material' );
	        $role->add_cap( 'edit_materials' );
	        $role->add_cap( 'edit_published_materials' );
	        $role->add_cap( 'delete_materials' );
	        $role->add_cap( 'publish_materials' );

        /**
	     *  Rechte, Begriffe zu Taxonomien hinzuzufügen
	     */

            $role->add_cap( 'assign_organisation' );
	        $role->add_cap( 'manage_organisation' );



    }

    public function register_gravity_form()
    {
//             TODO:: DEBUG resource to create importable file for Gravity Forms
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
	        $post->post_content = '<!-- wp:post-featured-image /-->';
            foreach ($template_ids as $template_id) {
                $template = get_post($template_id);
                if (is_a($template, 'WP_Post')) {
                    $post->post_content .= $template->post_content;
                }
            }
	        $post->post_content .= '<!-- wp:paragraph {"className":"hidden"} -->'."\n".'<p class="hidden">/</p>'."\n".'<!-- /wp:paragraph -->';
	        wp_update_post($post);
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
            plugin_dir_url(__FILE__) . '/assets/js/template_handling_editor.js',
            array(),
            '1.0',
            true
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
        if(is_user_logged_in()){
            $is_editor = current_user_can('edit_other_posts')?'true':'false';
        }else{
	        $is_editor = 'false';
        }
        echo
        "<script>
                const rpi_material_input_template = 
                {
                    options:
                    {
                        allowed_blocks: JSON.parse('$this->allowed_block_types'),
                        post_type: JSON.parse('$post_type'),
                        
                    },
                    user:{
                        is_editor: $is_editor
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
	static function getTemplates(){
		$posts = get_posts([
			'post_status' => 'publish',
			'post_type'=> 'materialtyp_template',
            'numberposts' => 10,
			'orderby' => 'menu_order',
			'order' => 'ASC'

		]);
        if($posts && count($posts)===0){
            echo '';
            die();
        }

		?>
        <div class="controll-panel">
		    <div class="block-editor-block-inspector">
                <h2 class="components-panel__body-title">Materialvorlage ändern</h2>
                <div>
                    <p>Was soll in deinem Material vorkommen?</p>
                    <ul><?php
                        $i = 0;
                        foreach ($posts as $post){
	                        $i ++;
	                        $top = 0;
	                        if($i < 4){
		                        $top = 1;
	                        }
                            echo '<li class="reli-inserter" data="'.$post->post_name.'"><a href="javascript:RpiMaterialInputTemplate.insert('.$post->ID.','.$top.')"></a> <span>'.$post->post_title.'</span></li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
		</div>
		<?php
		die();
	}

    static function getTemplate(){
		$post_id = $_GET['id'];
		$post = get_post($post_id);
		echo $post->post_content;
		die();
	}

    function alter_author_posts($query) {
	    if ( $query->is_author() && $query->is_main_query() ) { // Run only on the homepage

		    $query->query_vars['post_type'] = array('post',get_field('template_post_type', 'option')); // Show all posts

		    $user = wp_get_current_user();
            if(is_user_logged_in() && $user->user_login == $query->query_vars['author_name']){
	            $query->query_vars['post_status'] = ['draft', 'planned', 'publish', 'pending'];
            }


	    }
    }
	function the_title($title, $id) {
		$status  = get_post_status($id);
		global $wp_post_statuses;
		$display_status= $wp_post_statuses[ $status ];

		if($status != 'publish'){
	        $title .= ' ('.$display_status->label.')';
        }
		return $title;
	}

	/**
     * Filter: Anzahl der Materialien und Beiträge eines Autors
	 * @param $count
	 * @param $userid
	 * @param $post_type
	 * @param $public_only
	 *
	 * @return string|null
	 */
    function get_author_usernumposts($count, $userid, $post_type, $public_only){

        global $wpdb;
        $where = get_posts_by_author_sql( array('post', get_field('template_post_type', 'option')), true, $userid, $public_only );
	    $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts $where" );
        return $count;
    }

	/**
     * hide MetaBoxes for custom Taxonomy if Metabox callback == false
     *
	 * @param $response
	 * @param WP_Taxonomy $taxonomy
	 * @param $request
	 *
	 * @return mixed
	 */
	function hide_taxonomy_metaboxes( $response, WP_Taxonomy $taxonomy, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		// Context is edit in the editor
		if ( $context === 'edit' && $taxonomy->meta_box_cb === false ) {
			$data_response                          = $response->get_data();
			$data_response['visibility']['show_ui'] = false;
			$response->set_data( $data_response );
		}

		return $response;
	}

}

new RpiMaterialInputTemplate();
