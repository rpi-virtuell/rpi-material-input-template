<?php


class RpiMaterialDeactivatedBlocks
{
    static public function register_template_settings_options_page()
    {

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title' => 'Material Einstellungen',
                'menu_title' => 'Einstellungen',
                'menu_slug' => 'material_template_settings',
                'capability' => 'manage_options',
                "position" => "",
                //"parent_slug" => "options-general.php",
                "parent_slug" => "edit.php?post_type=materialien",
                'redirect' => true,
                'post_id' => 'options'
            ));
        }
    }


    static public function register_acf_fields()
    {
		//get all blocktypes
        $choices = array();





	    foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_Type) {
	        if ($block_Type->parent != null)
				continue;

            if (!isset($block_Type_Module)) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
            }
            if (!is_bool($block_Type_Module) && !str_starts_with($block_Type->name, $block_Type_Module[0])) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
            }
	        if(empty($block_Type->title) && function_exists('lazyblocks')){
		        $lazyblock = lazyblocks()->blocks()->get_block( $block_Type->name );
				if($lazyblock)
					$title = $lazyblock['title'];
				else
					$title = ucfirst(str_replace('',' ',preg_replace('#[^/]*/(reli-)?#','',$block_Type->name)));
				if($title == 'Form'){
					$title = 'Formular';
				}
	        }else{
				$title = $block_Type->title;
	        }
            $choices[$block_Type->name] = $block_Type->name . ' | ' . $title;
        }


		//get all public custom post_types as choices
        $postTypes = get_post_types(['public'=>true, 'show_in_rest'=>true]);
	    unset( $postTypes['attachment'] );
	    unset( $postTypes['page'] );
		if(isset($postTypes['ct_content_block'])){
			unset( $postTypes['ct_content_block'] );
        }
        $type_choices = array();
        foreach ($postTypes as $postType) {
			$type_choices[$postType] = $postType;
		 }

        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_template_post_type',
                'title' => 'Beitragstyp bestimmen',
                'fields' => array(
                    array(
                        'key' => 'field_template_post_type',
                        'label' => 'Welcher Beittragstyp soll für die Eingabe von Materialien verwendet werden?',
                        'name' => 'template_post_type',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => $type_choices,
                        'default_value' => 'material',
                        'allow_null' => 0,
                        'multiple' => 0,
                        'ui' => 0,
                        'return_format' => 'value',
                        'ajax' => 0,
                        'placeholder' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'material_template_settings',
                        ),
                    ),
                ),
                'menu_order' => 1,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => 1,
                'description' => '',
            ));

            acf_add_local_field_group(array(
                'key' => 'group_material_deactivated_blocks',
                'title' => 'Material Blöcke deaktivieren',
                'fields' => array(
                    array(
                        'key' => 'field_deactivated_block_types',
                        'label' => 'Deaktivierte Editor Blöcke',
                        'name' => 'deactivated_block_types',
                        'type' => 'checkbox',
                        'instructions' => 'Markiere alle Blöcke, die bei der Eingabe neuer Materialien nicht zur Auswahl stehen sollen',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'frontend_admin_display_mode' => 'edit',
                        'only_front' => 0,
                        'choices' => $choices,
                        'allow_custom' => 0,
                        'default_value' => [] , //$default_value,
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'return_format' => 'value',
                        'save_custom' => 0,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'material_template_settings',
                        ),
                    ),
                ),
                'menu_order' => 2,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => 1,
                'description' => '',
            ));
        }
    }
}
