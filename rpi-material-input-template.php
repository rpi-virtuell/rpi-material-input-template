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

require_once('inc/rpi-material-deactivted-blocks.php');

class RpiMaterialInputTemplate
{

    private $deactivated_block_types;

    function __construct()
    {
        $this->deactivated_block_types = array();
        add_action('admin_menu', array('RpiMaterialDeactivatedBlocks', 'register_acf_fields'));
        add_action('admin_menu', array('RpiMaterialDeactivatedBlocks', 'register_template_settings_options_page'));
        add_action('init', array($this, 'register_custom_post_types'));
        add_action('init', array($this, 'register_custom_user_field'));
        //add_action('init', array($this, 'add_custom_capabilities'));
	    add_filter('admin_init', array($this, 'add_custom_capabilities'));
        add_action('init', array($this, 'force_create_material_with_form'));
        add_action('init', array($this, 'add_custom_taxonomies'));
        add_action('init', array($this, 'register_gravity_form'));
        add_action('enqueue_block_assets', array($this, 'blockeditor_js'));
        add_action('admin_head', array($this, 'blockeditor_head_scripts'));

        add_action('admin_head', array($this, 'supply_option_data_to_js'),20);

	    add_action('admin_init', array($this, 'check_for_broken_blocks'));
        add_action('save_post', array($this, 'add_template_att_to_blocks'), 10, 3);
        add_action('post_updated', array($this, 'on_publish_material'), 10, 3);

        add_filter('gform_pre_render', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_pre_validation', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_pre_submission_filter', array($this, 'add_template_selectbox_to_form'));
        add_filter('gform_admin_pre_render', array($this, 'add_template_selectbox_to_form'));


        add_filter('gform_pre_render', array($this, 'preselect_bundesland_in_form'),999);
        add_filter('gform_pre_render', array($this, 'prepare_anmeldung_form'),999);
        add_action('gform_after_submission', array($this, 'add_template_and_redirect'), 10, 2);

        //kriterien
        add_filter( 'acf/load_field/name=kriterien', [ $this, 'acf_load_kriterien' ] );

        //Autorenseiten
        add_action('pre_get_posts', array($this, 'alter_author_posts'), 999);
        add_action('the_title', array($this, 'the_title'), 10, 2);
        add_filter('get_usernumposts', array($this, 'get_author_usernumposts'), 10, 4);


        //ajax
        add_action('wp_ajax_getTemplate', array('RpiMaterialInputTemplate', 'getTemplate'));
        add_action('wp_ajax_getTemplates', array('RpiMaterialInputTemplate', 'getTemplates'));
        add_action('wp_ajax_getCriteria', array('RpiMaterialInputTemplate', 'getCriteria'));


        //hide taxonomy Metaboxes in Block-Editor
        add_filter('rest_prepare_taxonomy', array($this, 'hide_taxonomy_metaboxes'), 10, 3);

        //placeholder for empty paragraphs -> need to change in css too

        add_filter('write_your_story', function (string $text, WP_Post $post) {
            if ($post->post_type == get_field('template_post_type', 'option')) {
                return 'Tippe oder füge deine Inhalte hier ein. (Du kannst auch Dateien/Bilder hier her ziehen)';
            } else {
                return $text;
            }
        }, 10, 2);
    }



    public function register_custom_post_types()
    {
        /**
         * Post Type: materialien.
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
            'capability_type' => array('material', 'materials'),
            "capabilities" => array(
                'edit_post' => 'edit_material',
                'edit_posts' => 'edit_materials',
                'edit_others_posts' => 'edit_others_materials',
                'read_private_posts' => 'read_private_materials',
                'publish_posts' => 'publish_materials',
                'read_post' => 'read_material',
                'delete_others_posts' => 'delete_others_materials',
                'delete_published_posts' => 'delete_published_materials',
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
            'taxonomies' => ['kinderaktivitaten', 'kinderfahrung', 'anlass', 'alter', 'schlagwort', 'bundesland'],
            "show_in_graphql" => false,
        ];

        register_post_type("materialien", $args);



	    /********************************************************************************
	     * Post Type: materialtyp_template
	     */

        $labels = [
            "name" => __("Vorlagen Interview", "blocksy"),
            "singular_name" => __("Vorlage", "blocksy"),
        ];

        $args = [
            "label" => __("Vorlagen Interview", "blocksy"),
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
            "rewrite" => ["slug" => "materialtyp_template", "with_front" => true],
            "query_var" => true,
            "menu_icon" => "dashicons-yes-alt",
            "supports" => ["title", "editor", "thumbnail"],
            "show_in_graphql" => false,
            'taxonomies' => ['template_type']
        ];

        register_post_type("materialtyp_template", $args);

	/**
	 * Post Type: Kriterien.
	 */

	$labels = [
		"name" => esc_html__( "Kriterien", "blocksy" ),
		"singular_name" => esc_html__( "Kriterium", "blocksy" ),
	];

	$args = [
		"label" => esc_html__( "Kriterien", "blocksy" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "page",
		"map_meta_cap" => true,
		"hierarchical" => true,
		"can_export" => true,
		"rewrite" => [ "slug" => "material_criteria", "with_front" => true ],
		"query_var" => true,
		"menu_position" => 50,
		"menu_icon" => "dashicons-editor-spellcheck",
		"supports" => [ "title", "editor", "page-attributes" ],
		"taxonomies" => [ "version" ],
		"show_in_graphql" => false,
	];

	register_post_type( "material_criteria", $args );

	/**
	 * Post Type: Eingabehilfen.
	 */

	$labels = [
		"name" => esc_html__( "Eingabehilfen", "blocksy" ),
		"singular_name" => esc_html__( "Eingabehilfe", "blocksy" ),
	];

	$args = [
		"label" => esc_html__( "Eingabehilfen", "blocksy" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => false,
		"rewrite" => [ "slug" => "workflow", "with_front" => true ],
		"query_var" => true,
		"menu_position" => 50,
		"menu_icon" => "dashicons-format-status",
		"supports" => [ "title", "page-attributes" ],
		"show_in_graphql" => false,
	];

	register_post_type( "workflow", $args );

	/**
	 * Post Type: Organisationen.
	 */

	$labels = [
		"name" => esc_html__( "Organisationen", "blocksy" ),
		"singular_name" => esc_html__( "Organisation", "blocksy" ),
	];

	$args = [
		"label" => esc_html__( "Organisationen", "blocksy" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => true,
		"can_export" => false,
		"rewrite" => [ "slug" => "organisation", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "revisions", "author" ],
		"show_in_graphql" => false,
	];

	register_post_type( "organisation", $args );

	/**
	 * Post Type: Fortbildungen.
	 */

	$labels = [
		"name" => esc_html__( "Fortbildungen", "blocksy" ),
		"singular_name" => esc_html__( "Fortbildung", "blocksy" ),
	];

	$args = [
		"label" => esc_html__( "Fortbildungen", "blocksy" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => true,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		 'capability_type' => array('fortbildung', 'fortbildungs'),
            "capabilities" => array(
                'edit_post' => 'edit_fortbildung',
                'edit_posts' => 'edit_fortbildungs',
                'edit_published_posts' => 'edit_published_fortbildungs',
                'edit_others_posts' => 'edit_others_fortbildungs',
                'read_private_posts' => 'read_private_fortbildungs',
                'publish_posts' => 'publish_fortbildungs',
                'read_post' => 'read_fortbildung',
                'delete_others_posts' => 'delete_others_fortbildungs',
                'delete_published_posts' => 'delete_published_fortbildungs',
                'delete_posts' => 'delete_fortbildungs',
            ),
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => false,
		"rewrite" => [ "slug" => "fortbildung", "with_front" => true ],
		"query_var" => true,
		"supports" => [ "title", "editor", "thumbnail", "excerpt", "comments", "revisions", "author" ],
		"taxonomies" => [ "bundesland" ],
		"show_in_graphql" => false,
	];

	register_post_type( "fortbildung", $args );

	/**
	 * Post Type: Anmeldungen.
	 */

	$labels = [
		"name" => esc_html__( "Anmeldungen", "blocksy" ),
		"singular_name" => esc_html__( "Anmeldung", "blocksy" ),
	];

	$args = [
		"label" => esc_html__( "Anmeldungen", "blocksy" ),
		"labels" => $labels,
		"description" => "",
		"public" => true,
		"publicly_queryable" => true,
		"show_ui" => true,
		"show_in_rest" => true,
		"rest_base" => "",
		"rest_controller_class" => "WP_REST_Posts_Controller",
		"rest_namespace" => "wp/v2",
		"has_archive" => false,
		"show_in_menu" => true,
		"show_in_nav_menus" => true,
		"delete_with_user" => false,
		"exclude_from_search" => false,
		"capability_type" => "post",
		"map_meta_cap" => true,
		"hierarchical" => false,
		"can_export" => false,
		"rewrite" => [ "slug" => "anmeldung", "with_front" => true ],
		"query_var" => true,
		"menu_position" => 100,
		"menu_icon" => "dashicons-star-empty",
		"supports" => [ "title" ],
		"show_in_graphql" => false,
	];

	register_post_type( "anmeldung", $args );
}


	public function register_custom_user_field(){

		register_meta('user', 'workflow_step', array(
			'single' => true,
			'type' => 'array',
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'object',
						'properties' => array(
							'post_id'=>['type'=>'number'],
							'step'=>['type'=>'string'],
							'finished'=>['type'=>'boolean'],
						),
						'additionalProperties' => true
					),
				)
			),
		));
        register_meta('post', 'workflow_steps', array(
			'single' => true,
			'type' => 'array',
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'object',
						'properties' => array(
							'step'=>['type'=>'string'],
							'finished'=>['type'=>'boolean'],
						),
						'additionalProperties' => true
					),
				)
			),
		));

	}

	/**
	 * assign taxonomies **********************************************************************************************
	 */
	public function add_custom_taxonomies(){


		/**
		 * Taxonomy: Einrichtungen.
		 * slug: organisation
		 */

		$labels = [
			"name" => __("Einrichtungen", "blocksy"),
			"singular_name" => __("Einrichtung", "blocksy"),
		];


		$args = [
			"label" => __("Einrichtungen", "blocksy"),
			"labels" => $labels,
			"public" => true,
			"publicly_queryable" => true,
			"hierarchical" => false,
			"show_ui" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => true,
			"query_var" => true,
			"rewrite" => ['slug' => 'organisation', 'with_front' => true,],
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
				'manage_terms' => 'manage_organisation',
				'edit_terms' => 'edit_organisation',
				'delete_terms' => 'delete_organisation',
				'assign_terms' => 'assign_organisation'
			),
		];
		register_taxonomy("organisation", ["materialien"], $args);
	}

    public function add_custom_capabilities()
    {

        $roles = ['administrator', 'editor'];

        foreach ($roles as $roleslug) {

            $role = get_role($roleslug);


            $role->add_cap('read_material');
            $role->add_cap('edit_materials');
            $role->add_cap('edit_published_materials');
            $role->add_cap('delete_materials');
            $role->add_cap('publish_materials');

            $role->add_cap('edit_others_materials');
            $role->add_cap('edit_published_materials');
            $role->add_cap('delete_published_materials');
            $role->add_cap('read_private_materials');
            $role->add_cap('edit_private_materials');
            $role->add_cap('delete_private_materials');
            $role->add_cap('delete_others_materials');

            $role->add_cap('manage_organisation');
            $role->add_cap('edit_organisation');
            $role->add_cap('assign_organisation');
            $role->add_cap('delete_organisation');

            $role->add_cap('edit_fortbildung');
            $role->add_cap('edit_fortbildungs');
            $role->add_cap('edit_others_fortbildungs');
            $role->add_cap('read_private_fortbildungs');
            $role->add_cap('publish_fortbildungs');
            $role->add_cap('read_fortbildung');
            $role->add_cap('delete_others_fortbildungs');
            $role->add_cap('delete_published_fortbildungs');
            $role->add_cap('delete_fortbildungs');

        }

        add_role('anbieterin', 'Anbieter:in');

        $role= get_role('anbieterin');
        $role->add_cap('upload_files');
        $role->add_cap('read');
        $role->add_cap('level_2');
        $role->add_cap('level_1');
        $role->add_cap('level_0');
        $role->add_cap('read_material');
        $role->add_cap('edit_materials');
        $role->add_cap('edit_material');
        $role->add_cap('edit_published_materials');
        $role->add_cap('delete_materials');
        $role->add_cap('publish_materials');

        $role->add_cap('edit_fortbildung');
        $role->add_cap('edit_fortbildungs');
        $role->add_cap('edit_published_fortbildungs');
        $role->add_cap('publish_fortbildungs');
        $role->add_cap('read_fortbildung');
        $role->add_cap('delete_fortbildungs');

            $role->add_cap('manage_organisation');
            $role->add_cap('edit_organisation');
            $role->add_cap('assign_organisation');
            $role->add_cap('delete_organisation');

        add_role('autorin', 'Autor:in');

        $role = get_role('autorin');
        $role->add_cap('upload_files');
        $role->add_cap('read');
        $role->add_cap('level_2');
        $role->add_cap('level_1');
        $role->add_cap('level_0');
        $role->add_cap('read_material');
        $role->add_cap('edit_materials');
        $role->add_cap('edit_material');
        $role->add_cap('edit_published_materials');
        $role->add_cap('delete_materials');
        $role->add_cap('publish_materials');

        $role->add_cap('read_fortbildung');


        /**
         *  Rechte, Begriffe zu Taxonomien hinzuzufügen
         */



    }

    /**
	 * Stellt sicher, das ein Material nur über das formular erstellt werden kann
     * /post-new.php?post_type=materialien -> /eingabeformular
	 */
    public function force_create_material_with_form(){
	    if(strpos($_SERVER['SCRIPT_NAME'],'post-new.php')>0 && $_GET['post_type']===get_field('template_post_type', 'option')){
		    wp_redirect(home_url().'/neues-material-eingeben');
	    }
    }

    public function register_gravity_form()
    {
//             TODO:: DEBUG resource to create importable file for Gravity Forms
//                    $form = GFAPI::get_form(63);
//                   file_put_contents(__DIR__.'/backup/form.dat', serialize($form));

        global $wpdb;
        $form_title = 'materialeingabe';
        $formssql = "SELECT ID FROM {$wpdb->prefix}gf_form WHERE title = %s and is_trash = 0;";
        if (empty($formId = $wpdb->get_var($wpdb->prepare($formssql, $form_title)))) {
            $form = unserialize(file_get_contents(__DIR__ . '/backup/form.dat'));
            $formId = GFAPI::add_form($form);
        }

    }
    public function prepare_anmeldung_form($form){


        if("Fortbildungsanmeldung" === $form['title']){
            $fobi = null;
            if(isset($_GET['fobi'])){

                $fobi = get_post($_GET['fobi']);

            }
            if(!is_a($fobi,'WP_Post')){
                echo  "Aufruf unzulässig. ID fehlt oder Fortbildung existiert nicht";
                return ;

            }

            $args = [
                'post_type' => 'anmeldung',
                'meta_query'=>[
                    'relation' => 'AND',
                    [
                       'key'=>'user',
                       'value'=> get_current_user_id(),
                       'compare'=>'=',
                       'type' => 'NUMERIC'
                    ],
                    [
                       'key'=>'fobi',
                       'value'=> intval($_GET['fobi']),
                       'compare'=>'=',
                       'type' => 'NUMERIC'
                    ]
                ]
            ];
            $posts = get_posts($args);
            if(count($posts)>0){

                $msg =  "Du hast dich bereits für die Fortbildung angemeldet";

                wp_redirect(get_permalink($_GET['fobi'])."?error_msg=".$msg);

                return ;

            }

            foreach ($form['fields'] as $no=>&$field) {
                if ('Titel der Fortbildung' === $field->label) {
                    if(is_user_logged_in()){
                        $field->defaultValue = $fobi->post_title.' : '.wp_get_current_user()->display_name;
                    }else{
                        $field->defaultValue = $fobi->post_title;
                    }
                }
                if ('AnmeldungTitelBlock' === $field->label) {

                    $html = '<div>Ich möchte mich anmelden zu der Fortbildung: <strong>%s</strong><hr></div>';
                    $field->content = sprintf($html,$fobi->post_title);

                }

            }

        }

        return $form;


    }
    public function preselect_bundesland_in_form($form){

        foreach ($form['fields'] as &$field) {
            if ($field->type != 'select') {
                continue;
            }elseif ($field->adminLabel = "Bundesland"){
                $field->defaultValue= get_user_meta(get_current_user_id(),'bundesland_id',false) ;
            }
        }
        return $form;

    }

    public function add_template_selectbox_to_form($form)
    {
        foreach ($form['fields'] as &$field) {
            if ($field->type != 'checkbox' && $field->type != 'radio') {
                continue;
            }

            $term = $field->type == 'radio' ? 'radio' : 'checkbox';

            if($term == 'checkbox'){
                $term = $field->adminLabel;
            }
            $args = array(
                'post_type' => 'materialtyp_template',
                'numberposts' => -1,
                'orderby'=>'menu_order',
                'order'=>'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'template_type',
                        'field' => 'slug',
                        'terms' => $term,
                        'include_children' => true,
                        'operator' => 'IN'
                    )
                )
            );
            $materialtyp_templates = get_posts($args);

            $choices = array();
            foreach ($materialtyp_templates as $materialtyp_template) {
                $choices[] = array('text' => $materialtyp_template->post_title, 'value' => $materialtyp_template->ID);
            }
            $field->placeholder = 'In welchen Bereich fällt der Beitrag?';
            $field->choices = $choices;
        }

        return $form;
    }

    function add_template_and_redirect($entry, $form)
    {

        if("Materialeingabe" !== $form['title']){
            return;
        }
        $template_ids = array();

        foreach ($_POST as $input_key => $input_value) {
            if (str_starts_with($input_key, 'input_9_') or $input_key == 'input_14' or str_starts_with($input_key, 'input_25_')){
                $template_ids[] = $input_value;
            }
        }


        $post = get_post($entry['post_id']);

        if (is_a($post, 'WP_Post') && !empty($template_ids)) {
            $post->post_content =
                '<!-- wp:post-featured-image {"height":"350px","scale":"contain","lock":{"insert":true,"move":true,"remove":true}} /-->' . "\n\n" .
                '<!-- wp:lazyblock/reli-leitfragen-kurzbeschreibung {"is_teaser":true, "lock":{"move":true, "remove":true} } /-->';
            foreach ($template_ids as $template_id) {
                $template = get_post($template_id);
                if (is_a($template, 'WP_Post')) {
                    $post->post_content .= $template->post_content;
                }
            }
            $post->post_content .= '<!-- wp:lazyblock/reli-quellennachweis /-->';
	        //$post->post_content .= '<!-- wp:lazyblock/reli-leitfragen-anhang /-->';
	        $post->post_content .= '<!-- wp:paragraph {"className":"hidden"} -->' . "\n" . '<p class="hidden">/</p>' . "\n" . '<!-- /wp:paragraph -->';
            wp_update_post($post);


            $bundesland = '';
            /* bundesland_id im usermeta speichern, um beim nächsten material das Bundesland des Users vor auszuwählen */
            $terms = wp_get_post_terms($post->ID,'bundesland');
            foreach ($terms as $term){
                if(is_a($term,'WP_Term')){
                    update_user_meta(get_current_user_id(),'bundesland_id',$term->term_id);
                    $bundesland = $term->name;
                }

            }

            do_action('new_material_created', ['post' => $post, 'user'=>wp_get_current_user(), 'bundesland'=>$bundesland]);



        }

        wp_redirect(get_site_url() . '/wp-admin/post.php?post=' . $entry['post_id'] . '&action=edit');
        GFAPI::delete_entry($entry['id']);
        exit();
    }

    function blockeditor_js()
    {
        if (!is_admin()) return;

        if('materialien'===get_post_type()){
            wp_enqueue_script(
                'template_handling',
                plugin_dir_url(__FILE__) . '/assets/js/template_handling_editor.js',
                array(),
                '1.0',
                true
            );
            wp_enqueue_style(
                'template_handling_style',
                plugin_dir_url(__FILE__) . '/assets/css/template_handling_editor.css'
            );
        }
        wp_enqueue_style(
            'customizing_style',
            plugin_dir_url(__FILE__) . '/assets/css/customizing.css'
        );


    }

    function blockeditor_head_scripts(){
        ?>
        <style>
            :root {
                --reli-modal-background-url: url('<?php echo plugin_dir_url(__FILE__)?>/assets/background.png');
                }
        </style>
        <?php
    }

    public function supply_option_data_to_js()
    {

        //ermitteln, ob user Redakteur ist
        $this->deactivated_block_types = json_encode(get_field('deactivated_block_types', 'option'));
        $post_type = json_encode(get_field('template_post_type', 'option'));
        if (is_user_logged_in()) {
            $is_editor = current_user_can('edit_other_materials') ? 'true' : 'false';
        } else {
            $is_editor = 'false';
        }
        $path = plugin_dir_url(__FILE__);
        //globale javascript variable "rpi_material_input_template" befüllen
        echo
        "<script>
                const rpi_material_input_template = 
                {
                    url: '$path',
                    options:
                    {
                        deactivated_blocks: JSON.parse('$this->deactivated_block_types'),
                        post_type: JSON.parse('$post_type'),
                        
                    },
                    user:{
                        is_editor: $is_editor
                    },
                }
        </script>";

        if (get_post_type(get_the_ID()) != get_field('template_post_type', 'option')) {
            return;
        }
	    if(isset(get_option('theme_mods_blocksy')['narrowContainerWidth'])){
            $narrowContainerWidth = get_option('theme_mods_blocksy')['narrowContainerWidth'];
		    //set content width in editor <=>
		    echo "<script>
                jQuery(document).ready(($)=>{
                    setTimeout(()=>$('.editor-styles-wrapper').css({'max-width':'{$narrowContainerWidth}px'},1)); //,'transform': 'scale(1.1)','margin':'9em auto'
                });
             </script>";
        }



	    //blaue inserter Linie zwischen den Block verbergen, weil verwirrend
	    echo '<style>
            /* Main column width */
                       
            .block-editor-block-list__insertion-point-popover.is-without-arrow .is-with-inserter
            { 
                opacity: 0;
            }
            .block-editor-default-block-appender[data-root-client-id=""]
            {
                opacity: 0;
            }
        </style>';


    }

    public function add_template_att_to_blocks($post_ID, $post, $update){
        if (is_a($post, 'WP_Post') && $post->post_type === 'materialtyp_template')
        {
            $blocks = parse_blocks($post->post_content);
            foreach ($blocks as $block_key => $block)
            {
                $blocks[$block_key]['attrs']['template'] = $post->post_name;
            }
            $post->post_content = serialize_blocks($blocks);
        }
    }

    public function on_publish_material($post_ID, $post, $post_old){


        if (is_a($post, 'WP_Post') && $post->post_status == 'publish' && $post->post_type === get_field('template_post_type', 'option')){

            if($post_old->post_status != 'publish'){

                /**
                *  do_action('new_material_created', WP_POST $post, WP_USER $user, string $bundesland);
                 */
                $term_id = get_field('bundesland_id', 'user_'.get_current_user_id() );
                $bundesland = get_term($term_id);

                do_action('new_material_published',  ['post' => $post, 'user'=>wp_get_current_user(), 'bundesland'=>$bundesland->name]);

            }

        }

    }


    public function check_for_broken_blocks()
    {
        if (isset($_GET['post'], $_GET['action']) && $_GET['action'] === 'edit') {
            $post = get_post($_GET['post']);
            if (is_a($post, 'WP_Post') && $post->post_type == get_field('template_post_type', 'option')) {
                //$existing_blocks = array();
                $this->deactivated_block_types = get_field('deactivated_block_types', 'option');
                if (!empty($this->deactivated_block_types)) {

                    $blocks = parse_blocks($post->post_content);
                    foreach ($blocks as $block_key => $block) {
                        if (!empty($block['blockName'])) {
                            if (in_array($block['blockName'], $this->deactivated_block_types) && $block['blockName'] != 'lazyblock/reli-default-block') {
                                //  && !in_array($block['blockName'], $existing_blocks)
                                //  $existing_blocks[] = $block['blockName'];
                                $blocks[$block_key]['blockName'] = 'lazyblock/reli-default-block';
                                $blocks[$block_key]['attrs']['blockUniqueClass'] = 'lazyblock/reli-default-block-' . $block['attrs']['blockId'];

                            }
                            //$existing_blocks[] = $block['blockName'];
                            continue;

                        }
                    }
                    $post->post_content = serialize_blocks($blocks);
                    // wp_update_post($post);
                }

            }
        }

    }

     public function acf_load_kriterien($field){

        $args = [
		    'post_type' => 'material_criteria',
		    'numberposts' => -1,
            'orderby'=>'menu_order',
            'order'=>'ASC',
		    'tax_query' => array(
			    array(
				    'taxonomy' => 'version',
				    'field' => 'slug',
				    'terms' => get_option('current_criteria_version','v1') ,
				    'include_children' => true,
				    'operator' => 'IN'
			    )
		    )
	    ];

        $crits = get_posts($args);
	    if ($crits !== false) {
             foreach ($crits as $crit){
                 $field['choices'][ $crit->post_name ] = '<strong>'.$crit->post_title .'</strong> ('.wp_trim_words(wp_strip_all_tags($crit->post_content),15).')' ;
             }
	    }

        return $field;
    }


    static function getCriteria(){
	    $version = isset($_GET['version']) ? $_GET['version'] : 'v1';

	    $query = new WP_Query([
		    'post_type' => 'material_criteria',
		    'numberposts' => -1,
            'orderby'=>'menu_order',
            'order'=>'ASC',
		    'tax_query' => array(
			    array(
				    'taxonomy' => 'version',
				    'field' => 'slug',
				    'terms' => $version,
				    'include_children' => true,
				    'operator' => 'IN'
			    )
		    )
	    ]);
	    if ($query->post_count === 0) {
		    echo '<li>noch keine Prüfkriterien vorhanden';
            wp_reset_postdata();
		    die();
	    }
	    ?>
        <div class="controll-panel">
            <div class="criteria-list">
                <ol>
                <?php
                    $i = 0;
                    while ($query->have_posts()) {
                        $crit = $query->the_post();

                        echo '<li class="reli-criterium" data="' . $crit->post_name . '">
                                    <input id="crit-'.get_the_ID().'" type="checkbox" name="criteria" value="'.get_the_ID().'">
                                    <label for="crit-'.get_the_ID().'"><strong>' . get_the_title() . '</strong><dl>' . get_the_content() . '</dl></label>
                              </li>';
                    }
                    ?>
                </ol>

        </div>
	    <?php
	    wp_reset_postdata();
	    die();

    }


    static function getTemplates()
    {

        $term = isset($_GET['term']) ? $_GET['term'] : 'checkbox';


        if($term=='checkbox'){
            //alle Materialerweiterungen
            $term = array($term, 'relpaed');
        }

        $posts = get_posts([
            'post_type' => 'materialtyp_template',
            'numberposts' => -1,
            'tax_query' => array(
                array(
                    'taxonomy' => 'template_type',
                    'field' => 'slug',
                    'terms' => $term,
                    'include_children' => true,
                    'operator' => 'IN'
                )
            )
        ]);
        if ($posts && count($posts) === 0) {
            echo '';
            die();
        }

        ?>
        <div class="controll-panel">
            <div class="block-editor-block-inspector">
                <div>
                    <h2>Was soll in deinem Material sonst noch vorkommen?</h2>
                    <ul><?php
                        $i = 0;
                        foreach ($posts as $post) {
                            echo '<li class="reli-inserter" data="' . $post->post_name . '"><a href="javascript:RpiMaterialInputTemplate.insert(' . $post->ID . ')"></a> <span>' . $post->post_title . '</span></li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php
        die();
    }

    static function getTemplate()
    {
        $post_id = $_GET['id'];
        $post = get_post($post_id);
        echo $post->post_content;
        die();
    }

    function alter_author_posts($query)
    {
        if ($query->is_author() && $query->is_main_query()) { // Run only on the homepage

            $query->query_vars['post_type'] = array('post', get_field('template_post_type', 'option')); // Show all posts

            $user = wp_get_current_user();
            if (is_user_logged_in() && $user->user_login == $query->query_vars['author_name']) {
                $query->query_vars['post_status'] = ['draft', 'planned', 'publish', 'pending'];
            }


        }
    }

    function the_title($title, $id)
    {
        $status = get_post_status($id);
        global $wp_post_statuses;
        $display_status = $wp_post_statuses[$status];

        if ($status != 'publish') {
            $title .= ' (' . $display_status->label . ')';
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
    function get_author_usernumposts($count, $userid, $post_type, $public_only)
    {

        global $wpdb;
        $where = get_posts_by_author_sql(array('post', get_field('template_post_type', 'option')), true, $userid, $public_only);
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts $where");
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
    function hide_taxonomy_metaboxes($response, WP_Taxonomy $taxonomy, $request)
    {
        $context = !empty($request['context']) ? $request['context'] : 'view';
        // Context is edit in the editor
        if ($context === 'edit' && $taxonomy->meta_box_cb === false) {
            $data_response = $response->get_data();
            $data_response['visibility']['show_ui'] = false;
            $response->set_data($data_response);
        }

        return $response;
    }




}

new RpiMaterialInputTemplate();

require_once ("inc/rpi-workflow.php");
