<?php


class RpiMaterialDeactivatedBlocks
{
    static public function register_template_settings_options_page()
    {

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title' => 'Material Einstellungen',
                'menu_title' => 'Material Einstellungen',
                'menu_slug' => 'material_template_settings',
                'capability' => 'manage_options',
                "position" => "",
                "parent_slug" => "options-general.php",
                'redirect' => true,
                'post_id' => 'options'
            ));
        }
    }


    static public function register_acf_fields()
    {
        $choices = array();
        $default_value = array();
        foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_Type) {
	        if ($block_Type->parent != null)
				continue;


            if (!isset($block_Type_Module)) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
                //$default_value[] = $block_Type->name;

            }
            if (!is_bool($block_Type_Module) && !str_starts_with($block_Type->name, $block_Type_Module[0])) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
            }
	        if(empty($block_Type->title)){
		        $title = isset($block_Type->attributes['title']['default']) ?$block_Type->attributes['title']['default'] : ucfirst(str_replace('',' ',preg_replace('#[^/]*/(reli-)?#','',$block_Type->name)));
	        }else{
				$title = $block_Type->title;
	        }

            $choices[$block_Type->name] = $block_Type->name . ' | ' . $title;
            //$default_value[] = $block_Type->name;


        }
	    //var_dump($block_Type->attributes);die();


        //TODO needs to be refactored =>  redundancy
        $postTypes = get_post_types();
        $type_choices = array();
        $type_default_value = array();
        foreach ($postTypes as $postType) {
			$type_choices[$postType] = $postType;
            $type_default_value[] = $postType;
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
                        'default_value' => $type_default_value,
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
