<?php


class RpiMaterialAllowedBlocks
{
    public function register_template_settings_options_page()
    {

        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'page_title' => 'Material Template Settings',
                'menu_title' => 'Material Template Einstellungen',
                'menu_slug' => 'material_template_settings',
                'capability' => 'manage_options',
                "position" => "",
                "parent_slug" => "options-general.php",
                'redirect' => true,
                'post_id' => 'options'
            ));
        }
    }


    public function register_acf_fields()
    {
        $choices = array();
        $default_value = array();
        foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $block_Type) {
            if ($block_Type->parent != null)
                continue;
            if (!isset($block_Type_Module)) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
                $default_value[] = $block_Type->name;
            }
            if (!is_bool($block_Type_Module) && !str_starts_with($block_Type->name, $block_Type_Module[0])) {
                $block_Type_Module = explode('/', $block_Type->name, 2);
                $choices['## ' . $block_Type_Module[0]] = '## ' . $block_Type_Module[0];
            }

            $choices[$block_Type->name] = !empty($block_Type->title) ? $block_Type->title : $block_Type->name;
            $default_value[] = $block_Type->name;

        }

        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_template_post_type',
                'title' => 'Templates anwenden auf Post Type',
                'name' => 'template_post_type',
                'fields' => array(
                    array(
                        'key' => 'field_template_post_type',
                        'label' => 'Template Post type',
                        'name' => 'template_post_type',
                        'type' => 'post_type',
                        'instructions' => '',
                        'required' => 1,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => 'materialien',
                        'post_type_options' => '',
                        'field_type' => 'select',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'choices' => array(),
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                        'return_format' => 'value',
                        'layout' => 'vertical',
                        'other_choice' => 0,
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
                'key' => 'group_material_allowed_blocks',
                'title' => 'Material erlaubte Blöcke',
                'fields' => array(
                    array(
                        'key' => 'field_allowed_block_types',
                        'label' => 'allowed_block_types',
                        'name' => 'allowed_block_types',
                        'type' => 'checkbox',
                        'instructions' => '',
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
                        'default_value' => $default_value,
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