<?php

class RpiWorkflow {
	/**
	 * Ermöglicht den Autor bei der Eingabe im Gutenberg Blockeditor durch Popupfenster
     * schrittweise zu unterstützen
	 */

	public function __construct() {

		add_action('save_post', array($this, 'theWorkflow'),10,3);
		add_action('enqueue_block_assets', array($this, 'blockeditor_js'));
		add_action( 'init', array($this, 'cptui_register_cpt_workflow') );
		add_action( 'init', array($this, 'addCustomFieldsToWorkflow') );

	}
	/**
	 * save_post action
	 *
	 * Schreibt /assets/js/workflow_steps.js mit den in  im post_type workflow hinterlegten Eingabehilfen
	 */
	function theWorkflow(int $post_ID, WP_Post $post, bool $update){

		if('workflow' !== $post->post_type){
			return;
		}

		ob_start();

		$laststep = false;

		$workflow = get_posts(array(
			'post_type'=>'workflow',
			'numberposts' => -1,
			'orderby'=>'menu_order',
			'order'=>'ASC',
		));




		//echo "<script>";
		//start--jQuery(document).ready------------------------------->
		echo "jQuery(document).ready(function($){ ";


		foreach ($workflow as  $wfs){   //------------- start foreach----------->
			$step = (object) get_fields($wfs->ID, true);

			//validate startcondition
			if(!$step->start_after_custom_code || empty($step->startcondition) ){
				if($laststep){
					$step->startcondition = "[()=>RpiWorkflow.find('".$laststep."').finished === true]" ;
				}else{
					$step->startcondition = "[()=>true]" ;
				}
			}

			$laststep = $wfs->post_name;

			//validate endcondition
			if(empty($step->endcondition) ){
				$step->endcondition = "[()=>false]" ;
			}


			/**
			 * generiert folgenden Javascript Befehl -> siehe rpi-worklfow js -> RpiWorkflow.addWorkflowStep()
			 *
			 * addWorkflowStep(
			 *   slug='',
			 *   type = 'interval',
			 *   startconditions=[()=>true],
			 *   startfn = (wfs)=>{ wfs.started =true },
			 *   endconditions=[()=>false]
			 *   endfn = (wfs)=>{wfs.finish()}
			 * )
			 */

			//---------------------------addWorkflowStep-->
			?>
            RpiWorkflow.addWorkflowStep(
            '<?php echo $wfs->post_name;?>',
            '<?php echo $step->type;?>',
			<?php echo $step->startcondition;?>,
			<?php


			echo "  (wfs)=>{"; //startfn ----------------------------------------------------------------- >
			if($step->has_start_dialog){
				?>dialog = RpiWorkflow.dialog({
                content: <?php echo json_encode($step->startdialog['content']) ;?>,
                title: <?php echo json_encode($step->startdialog['title']) ;?>,
                button: <?php echo json_encode($step->startdialog['button']) ;?>,
                w:<?php echo $step->startdialog['width'] ;?>,
                h:<?php echo $step->startdialog['height'] ;?>
                });
				<?php
				switch($step->startdialog['ok_btn_select']){
					case 'confirm':
						echo "      dialog.btn.click(()=>{console.log(wfs);wfs.confirm();});";
						break;
					case 'finish':
						echo "      dialog.btn.click(()=>wfs.finish());";
						break;
					case 'code':
						echo "      dialog.btn.click(()=>{".$step->startdialog['ok_btn_onclick']."});";
						break;
				}
			}
			echo $step->startfn;
			echo "},\n"; //-- startfn <-----------------------------------------------------------------



			echo $step->endcondition.",\n";



			echo "(wfs)=>{"; //endfn ----------------------------------------------------------------- >

			if($step->has_end_dialog){
				?>dialog = RpiWorkflow.dialog({
                content: <?php echo json_encode($step->enddialog['content']) ;?>,
                title: <?php echo json_encode($step->enddialog['title']) ;?>,
                button: <?php echo json_encode($step->enddialog['button']) ;?>,
                w:<?php echo $step->enddialog['width'] ;?>,
                h:<?php echo $step->enddialog['height'] ;?>
                });
				<?php
				switch($step->enddialog['ok_btn_select']){
					case 'finish':
						echo "      dialog.btn.click(()=>wfs.finish());";
						break;
					case 'code':
						echo "      dialog.btn.click(()=>{".$step->enddialog['ok_btn_onclick']."});";
						break;
				}

			}
			echo $step->endfn;

			echo "}"; //-- endfn <----------------------------------------------------------------- /



			echo ");\n";  //<---------------------------addWorkflowStep--


		}   //<------------- end foreach-----------

		echo "});"; //end--jQuery(document).ready------------------------------->

		$script = ob_get_clean();
		file_put_contents(plugin_dir_path(__DIR__).'/assets/js/workflow_steps.js',$script);

	}

	function cptui_register_cpt_workflow() {

		/**
		 * Post Type: Eingabehilfen.
		 */

		$labels = [
			"name" => __( "Eingabehilfen", "blocksy" ),
			"singular_name" => __( "Eingabehilfe", "blocksy" ),
		];

		$args = [
			"label" => __( "Eingabehilfen", "blocksy" ),
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
	}

    public function addCustomFieldsToWorkflow(){
	    if( function_exists('acf_add_local_field_group') ):

		    acf_add_local_field_group(array(
			    'key' => 'group_62a5b6c32c8d1',
			    'title' => 'Eingabehilfe Konfiguration',
			    'fields' => array(
				    array(
					    'key' => 'field_62a5fb096caff',
					    'label' => 'Bedingungen überprüfen',
					    'name' => 'type',
					    'type' => 'select',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'choices' => array(
						    'interval' => 'Automatisch',
						    'onSaveButton' => 'Beim Speichern',
					    ),
					    'default_value' => 'onSaveButton',
					    'allow_null' => 0,
					    'multiple' => 0,
					    'ui' => 0,
					    'return_format' => 'value',
					    'ajax' => 0,
					    'placeholder' => '',
				    ),
				    array(
					    'key' => 'field_62a5cb65e7abf',
					    'label' => 'Schritt Starten',
					    'name' => '',
					    'type' => 'tab',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'placement' => 'top',
					    'endpoint' => 0,
				    ),
				    array(
					    'key' => 'field_62a5d0ac7bf0b',
					    'label' => 'Hat einen Startdialog',
					    'name' => 'has_start_dialog',
					    'type' => 'true_false',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'message' => '',
					    'default_value' => 1,
					    'ui' => 0,
					    'ui_on_text' => '',
					    'ui_off_text' => '',
				    ),
				    array(
					    'key' => 'field_62a5d28cad127',
					    'label' => 'Startdialog',
					    'name' => 'startdialog',
					    'type' => 'group',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => array(
						    array(
							    array(
								    'field' => 'field_62a5d0ac7bf0b',
								    'operator' => '==',
								    'value' => '1',
							    ),
						    ),
					    ),
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'layout' => 'block',
					    'acfe_seamless_style' => 0,
					    'acfe_group_modal' => 0,
					    'sub_fields' => array(
						    array(
							    'key' => 'field_62a5b76aa3265',
							    'label' => 'Dialogtitel',
							    'name' => 'title',
							    'type' => 'text',
							    'instructions' => 'Wird als Titel des Dialogfensters angezeigt',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 'Hinweis',
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'maxlength' => '',
						    ),
						    array(
							    'key' => 'field_62a5cc4dd0dc8',
							    'label' => 'Inhalt',
							    'name' => 'content',
							    'type' => 'acfe_code_editor',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '',
							    'placeholder' => '',
							    'mode' => 'text/html',
							    'lines' => 1,
							    'indent_unit' => 4,
							    'maxlength' => '',
							    'rows' => 4,
							    'max_rows' => '',
							    'return_entities' => 0,
						    ),
						    array(
							    'key' => 'field_62a5b7aca3266',
							    'label' => 'Button Beschriftung',
							    'name' => 'button',
							    'type' => 'text',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '',
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'maxlength' => '',
						    ),
						    array(
							    'key' => 'field_62a5b81ea3267',
							    'label' => 'Breite des Dialogs in px',
							    'name' => 'width',
							    'type' => 'number',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 1000,
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'min' => '',
							    'max' => '',
							    'step' => '',
						    ),
						    array(
							    'key' => 'field_62a5b84fa3268',
							    'label' => 'Höhe des Dialogs in px',
							    'name' => 'height',
							    'type' => 'number',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 650,
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'min' => '',
							    'max' => '',
							    'step' => '',
						    ),
						    array(
							    'key' => 'field_62a5b891a3269',
							    'label' => 'Bei Klick auf Button',
							    'name' => 'ok_btn_select',
							    'type' => 'select',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'choices' => array(
								    'confirm' => 'Bestätigen, dass der Schritt angenommen wird',
								    'finish' => 'Schritt abschließen',
								    'code' => 'Benutzerdefinierter Code',
							    ),
							    'default_value' => false,
							    'allow_null' => 0,
							    'multiple' => 0,
							    'ui' => 0,
							    'return_format' => 'value',
							    'ajax' => 0,
							    'placeholder' => '',
						    ),
						    array(
							    'key' => 'field_62a5ceb6e54ca',
							    'label' => 'Benutzedefinirter Code',
							    'name' => 'ok_btn_onclick',
							    'type' => 'acfe_code_editor',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => array(
								    array(
									    array(
										    'field' => 'field_62a5b891a3269',
										    'operator' => '==',
										    'value' => 'code',
									    ),
								    ),
							    ),
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '',
							    'placeholder' => '',
							    'mode' => 'text/html',
							    'lines' => 1,
							    'indent_unit' => 4,
							    'maxlength' => '',
							    'rows' => 4,
							    'max_rows' => '',
							    'return_entities' => 0,
						    ),
					    ),
				    ),
				    array(
					    'key' => 'field_62a5c3f967193',
					    'label' => 'Deaktiviere "Nächster Schritt"',
					    'name' => 'start_after_custom_code',
					    'type' => 'true_false',
					    'instructions' => 'Automatischer Start dieses Schrittes nach Abschluss des vorgehenden deaktivieren',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'message' => 'Eigene Bedingungen für den Start diesen Schrittes als benutzerdefinierten Code formulieren',
					    'default_value' => 0,
					    'ui' => 0,
					    'ui_on_text' => '',
					    'ui_off_text' => '',
				    ),
				    array(
					    'key' => 'field_62a6d019ad9bd',
					    'label' => 'Starten wenn...',
					    'name' => 'startcondition',
					    'type' => 'acfe_code_editor',
					    'instructions' => 'Benutzerdefinierte Code: Array mit Startbedingungen, die jeweils einen boolchen Wert zurückgeben',
					    'required' => 0,
					    'conditional_logic' => array(
						    array(
							    array(
								    'field' => 'field_62a5c3f967193',
								    'operator' => '==',
								    'value' => '1',
							    ),
						    ),
					    ),
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'default_value' => '[()=> wahr == ausdruck]',
					    'placeholder' => '',
					    'mode' => 'javascript',
					    'lines' => 1,
					    'indent_unit' => 4,
					    'maxlength' => '',
					    'rows' => 4,
					    'max_rows' => '',
					    'return_entities' => 0,
				    ),
				    array(
					    'key' => 'field_62a5c8075aaa3',
					    'label' => 'Startfunktion',
					    'name' => 'startfn',
					    'type' => 'acfe_code_editor',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'default_value' => '',
					    'placeholder' => '',
					    'mode' => 'javascript',
					    'lines' => 1,
					    'indent_unit' => 4,
					    'maxlength' => '',
					    'rows' => 4,
					    'max_rows' => '',
					    'return_entities' => 0,
				    ),
				    array(
					    'key' => 'field_62a5cb93e7ac0',
					    'label' => 'Schritt Beenden',
					    'name' => '',
					    'type' => 'tab',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'placement' => 'top',
					    'endpoint' => 0,
				    ),
				    array(
					    'key' => 'field_62a5d0ec7bf0c',
					    'label' => 'Hat eine Abschkussdialog',
					    'name' => 'has_end_dialog',
					    'type' => 'true_false',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'message' => '',
					    'default_value' => 0,
					    'ui' => 0,
					    'ui_on_text' => '',
					    'ui_off_text' => '',
				    ),
				    array(
					    'key' => 'field_62a5d15ef535d',
					    'label' => 'Abschlussdialog',
					    'name' => 'enddialog',
					    'type' => 'group',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => array(
						    array(
							    array(
								    'field' => 'field_62a5d0ec7bf0c',
								    'operator' => '==',
								    'value' => '1',
							    ),
						    ),
					    ),
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'layout' => 'block',
					    'acfe_seamless_style' => 0,
					    'acfe_group_modal' => 0,
					    'sub_fields' => array(
						    array(
							    'key' => 'field_62a5cc14d0dc7',
							    'label' => 'Dialogtitel',
							    'name' => 'title',
							    'type' => 'text',
							    'instructions' => 'Wird als Titel des Dialogfensters angezeigt',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 'Hinweis',
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'maxlength' => '',
						    ),
						    array(
							    'key' => 'field_62a5b6e8a3264',
							    'label' => 'Inhalt',
							    'name' => 'content',
							    'type' => 'textarea',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '',
							    'placeholder' => 'Wozu möchtest du den User auffordern?',
							    'maxlength' => '',
							    'rows' => '',
							    'new_lines' => '',
							    'acfe_textarea_code' => 0,
						    ),
						    array(
							    'key' => 'field_62a5d2d5d71e1',
							    'label' => 'OK Button Beschriftung',
							    'name' => 'button',
							    'type' => 'text',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '',
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'maxlength' => '',
						    ),
						    array(
							    'key' => 'field_62a5cc9eda337',
							    'label' => 'Breite des Dialogs in px',
							    'name' => 'width',
							    'type' => 'number',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 1000,
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'min' => '',
							    'max' => '',
							    'step' => '',
						    ),
						    array(
							    'key' => 'field_62a5ccc27e701',
							    'label' => 'Höhe des Dialogs in px',
							    'name' => 'height',
							    'type' => 'number',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => 650,
							    'placeholder' => '',
							    'prepend' => '',
							    'append' => '',
							    'min' => '',
							    'max' => '',
							    'step' => '',
						    ),
						    array(
							    'key' => 'field_62a5cfed28da3',
							    'label' => 'Bei Klick auf den OK Button',
							    'name' => 'ok_btn_select',
							    'type' => 'select',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => 0,
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'choices' => array(
								    'finish' => 'Schritt abschließen',
								    'code' => 'Benutzerdefinierter Code',
							    ),
							    'default_value' => false,
							    'allow_null' => 0,
							    'multiple' => 0,
							    'ui' => 0,
							    'return_format' => 'value',
							    'ajax' => 0,
							    'placeholder' => '',
						    ),
						    array(
							    'key' => 'field_62a5cd5401961',
							    'label' => 'Benutzerdifinierter Code',
							    'name' => 'ok_btn_onclick',
							    'type' => 'acfe_code_editor',
							    'instructions' => '',
							    'required' => 0,
							    'conditional_logic' => array(
								    array(
									    array(
										    'field' => 'field_62a5cfed28da3',
										    'operator' => '==',
										    'value' => 'code',
									    ),
								    ),
							    ),
							    'wrapper' => array(
								    'width' => '',
								    'class' => '',
								    'id' => '',
							    ),
							    'default_value' => '()=>{$=jQuery;}',
							    'placeholder' => '',
							    'mode' => 'javascript',
							    'lines' => 1,
							    'indent_unit' => 4,
							    'maxlength' => '',
							    'rows' => 4,
							    'max_rows' => '',
							    'return_entities' => 0,
						    ),
					    ),
				    ),
				    array(
					    'key' => 'field_62a5c55467be6',
					    'label' => 'Beenden, wenn...',
					    'name' => 'endcondition',
					    'type' => 'acfe_code_editor',
					    'instructions' => 'Array mit Bedingungen, die jeweils einen boolchen Wert zurückgeben',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'default_value' => '',
					    'placeholder' => '',
					    'mode' => 'javascript',
					    'lines' => 1,
					    'indent_unit' => 4,
					    'maxlength' => '',
					    'rows' => 4,
					    'max_rows' => '',
					    'return_entities' => 0,
				    ),
				    array(
					    'key' => 'field_62a5ce21f68cd',
					    'label' => 'Endfunktion',
					    'name' => 'endfn',
					    'type' => 'acfe_code_editor',
					    'instructions' => '',
					    'required' => 0,
					    'conditional_logic' => 0,
					    'wrapper' => array(
						    'width' => '',
						    'class' => '',
						    'id' => '',
					    ),
					    'default_value' => 'wfs.finish();',
					    'placeholder' => '',
					    'mode' => 'javascript',
					    'lines' => 1,
					    'indent_unit' => 4,
					    'maxlength' => '',
					    'rows' => 4,
					    'max_rows' => '',
					    'return_entities' => 0,
				    ),
			    ),
			    'location' => array(
				    array(
					    array(
						    'param' => 'post_type',
						    'operator' => '==',
						    'value' => 'workflow',
					    ),
				    ),
			    ),
			    'menu_order' => 0,
			    'position' => 'normal',
			    'style' => 'default',
			    'label_placement' => 'left',
			    'instruction_placement' => 'label',
			    'hide_on_screen' => '',
			    'active' => true,
			    'description' => '',
			    'show_in_rest' => 0,
			    'acfe_display_title' => '',
			    'acfe_autosync' => '',
			    'acfe_form' => 0,
			    'acfe_meta' => '',
			    'acfe_note' => '',
		    ));

	    endif;
    }

	function blockeditor_js()
	{
		if (!is_admin()) return;
		wp_enqueue_script(
			'workflow_handling',
			plugin_dir_url(__DIR__) . '/assets/js/rpi-workflow.js',
			array(),
			'1.0',
			true
		);
		wp_enqueue_script(
			'workflow_steps',
			plugin_dir_url(__DIR__) . '/assets/js/workflow_steps.js',
			array(),
			'1.0',
			true
		);

	}
}
new RpiWorkflow();
