/**
 * @author Joachim happel
 */
RpiMaterialInputTemplate = {
    has_workflow:false,
    init:function (){
        this.getTemplates();
        this.init_workflow();

    },

    progressBar: function(label, status){

        const $ = jQuery;
        if($('#rpi-material-status').length === 0){
            $('.rpi-material-toolbar').append('<div id="rpi-material-status" class="components-button">Status: &nbsp; '+
                '<div id="rpi-material-status-bar" style="border:1px solid #ccc; width:150px; display: grid;grid-template-columns: 3fr 1fr;">'+
                '<div id="rpi-material-status-progress" style="width:10px; height:15px; background:greenyellow;"></div>'+
                '<div id="rpi-material-meta-progress" style="width:1px; height:15px; background:forestgreen;"></div>'+
                '</div></div>');
            $('.rpi-material-toolbar').append('<div class="components-button" style="border-right: 1px solid #ccc;">&nbsp;</div>');
            $('.rpi-material-toolbar').append('<button id="rpi-material-step" class="components-button is-primary">Speichern</button>');


            jQuery('.editor-post-publish-button__button').hide();
            jQuery('.block-editor-post-preview__dropdown').hide();
            jQuery('.editor-post-save-draft').hide();
            jQuery('#rpi-material-step').click((e)=>{
                wp.data.dispatch('core/editor').savePost();
                RpiMaterialInputTemplate.steps();
            });

        }

    },

    init_workflow: function(){
        if(!this.has_workflow){
            this.has_workflow = true;




            let status = wp.data.select('core/editor').getEditedPostAttribute('status');
            if(status == 'draft'){
                RpiMaterialInputTemplate.progressBar();
                RpiMaterialInputTemplate.steps();
            }
            this.resetStaticBlockPositions();

        }
    },
    end_workflow:function (){
        $('.rpi-material-toolbar').remove();
        jQuery('.editor-post-publish-button__button').show();
        jQuery('.block-editor-post-preview__dropdown').show();
        jQuery('.editor-post-save-draft').show();
    },


    checkMeta: function (){
        const $ = jQuery;
        const fieldsToCheck =[
            'lizenz',
            'materialtype',
            'alter',
            'kinderaktivitaten',
            'kinderfahrung'
        ];

        var filled =[], total=[], value;
        acf.getFields().forEach((field)=>{

            if(fieldsToCheck.includes(field.data.name)){
                if(! $('#acf-field-toCheck-'+field.data.name).length){
                    field.$el.css({position: 'relative'});
                    $('<div id="acf-field-toCheck-'+field.data.name+'" class="acf-field-toCheck"></div>').insertBefore(field.$el.find('label').first());
                }

                if(field.data.type == 'select' ||field.data.type == 'checkbox' || field.data.type == 'taxonomy' ){
                    total.push(field.data.name);
                    value = field.val()
                    if(value && value != ''){
                        filled.push(field.data.name);
                    }
                }
            }

        })
        return {percent: filled.length * 100 / total.length};
    },
    displayWritingProgress: function (){
        let total = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.attributes.minimum_characters>0);
        let ok = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.attributes.is_valid);
        let percent = ok.length * 100 /total.length;
        console.log(percent,ok,total);

        jQuery('#rpi-material-status-progress').css({'width': percent+'%', 'max.width':'100%'});
        jQuery('#rpi-material-status-progress').attr('title', percent+ '% begonnener Schreibprozess');
        return {percent:percent};
    },
    displayMetaProgress: function (){
        let progress = this.checkMeta();
        jQuery('#rpi-material-meta-progress').css({width:progress.percent+'%', 'max.width':'100%'});
        jQuery('#rpi-material-meta-progress').attr('title', progress.percent+ '% Metaangaben');
        return progress;
    },
    stepsComplete: function (){

        wp.data.dispatch('core/editor').editPost({status: 'publish'});
        wp.data.dispatch('core/editor').savePost();
        jQuery('#TB_closeWindowButton').click();
        this.end_workflow();

    },
    setWorkflow: function (step){

        //
        // if(wp.data.select('core/editor').getEditedPostAttribute('meta').workflow_step != step){
        //
        //     wp.data.dispatch('core/editor').editPost({meta: {'workflow_step': step}});
        //     wp.data.dispatch('core/editor').savePost();
        //
        // }

    },

    modal_fill_meta: function (html){

        RpiMaterialInputTemplate.dialog(html);
        if(jQuery('.acf-postbox.closed').length>0){
            jQuery('.acf-postbox .toggle-indicator').click();
        }


    },
    modal_extend_template: function (html){

        RpiMaterialInputTemplate.dialog(html+'<div id="template-reflexion-box"></div>', 'Vorlage anpassen');
        RpiMaterialInputTemplate.getTemplates('reflexion');


    },
    modal_checklist: function (html){
        RpiMaterialInputTemplate.dialog(html,'Kriterienliste für den Prüfbericht',500,400);

    },


    steps: function(){

        RpiWorkflow.init();
        return;

        const CONTENT = 1;
        const META = 2;
        const META_CONTENT = 3;
        const CONTENTREADY = 13;
        const READY = 23;

        const CONTENTPLUS = 10;
        const PROOF = 20;


        const status = wp.data.select('core/editor').getEditedPostAttribute('status');
        //let workflow = wp.data.select('core/editor').getEditedPostAttribute('meta').workflow_step;

        if(status == 'draft'){

            let step = 0;

            let meta_progress = this.displayMetaProgress();
            let write_progress = this.displayWritingProgress();

            if(meta_progress.percent > 66) {
                step = META;
            }
            if(write_progress.percent > 66) {
                step += CONTENT;
            }

            // if(wp.data.select('core/editor').getEditedPostAttribute('meta').workflow_step > 0){
            //     step += wp.data.select('core/editor').getEditedPostAttribute('meta').workflow_step;
            // }


            if(step>META_CONTENT &&step <CONTENTREADY){
                this.setWorkflow(CONTENTPLUS);
                step = CONTENTREADY;
            }
            console.log('step', step);

            switch (step){
                case CONTENT:

                    html= '<p><strong>Wie lässt sich dein Material am besten zuordnen?</strong></p>';
                    html += '<p>Unter der Inhaltseingabe findest du ein anhängendes Fomular mit mehreren Reitern (Urherberschaft, Formal, Inhalt).</p>';
                    html += '<p>Wenn du dort die passenden Kategorien auswählst oder ergänzt, wird dein Material besser gefunden.</p>';
                    html += '<a href="#postbox-container-2" onclick="jQuery(\'#TB_closeWindowButton\').click()">Ok, Mach ich!</a>';

                    this.modal_fill_meta(html);
                    break;

                case META:
                case META_CONTENT:

                    //dialog öffnen und fragen ob weitere Eingaben ok sind
                    html=   '<p>Durch Klick auf "Vorlage anpassen", kannst du dein Material erweitern. '+
                            'Leitfragen helfen dir beim Ausfüllen.</p>';

                    this.modal_extend_template(html);
                    RpiMaterialInputTemplate.setWorkflow(CONTENTPLUS);
                    jQuery(window).on('reflexionInserted',()=>{
                        RpiMaterialInputTemplate.setWorkflow(CONTENTPLUS);
                    });

                    break;
                case CONTENTREADY:

                    html= '<p>Du scheinst weitestgehend fertig zu sein. Du kannst dieses Fenster schließen wenn du noch weiter arbeiten möchtest. Die Redaktion wird deine Inhalte anhand folgender Kriterien prüfen.</p><ol>';

                    html += '<li>Kriterium 1</li>';
                    html += '<li>Kriterium 2</li>';
                    html += '<li>Kriterium 3</li>';
                    html += '<li>Kriterium 4</li>';
                    html += '<li>Kriterium 5</li>';
                    html += '</ol><p>Wenn du nichts mehr ändern willst, kannst du dein Material jetzt veröffentlichen</p>';
                    html += '<p><a class="button" href="javascript:RpiMaterialInputTemplate.setWorkflow(20);jQuery(\'#rpi-material-step\').html(\'Veröffentlichen\')">ich habe die Kriterien beachtet.</a></p>';

                    jQuery('#rpi-material-step').html('Prüfen');
                    jQuery('.editor-post-save-draft').show();

                    this.modal_checklist(html);
                    //RpiMaterialInputTemplate.setWorkflow(PROOF);
                    break;
                case READY:
                    jQuery('.editor-post-save-draft').show();
                    jQuery('#rpi-material-step').unbind();
                    jQuery('#rpi-material-step').on('click',()=>{
                        RpiMaterialInputTemplate.stepsComplete();
                    });
                    jQuery('#rpi-material-step').html('Veröffentlichen');
                    break;

            }


        }

    },

    dialog: function (content='', title='Nächster Schritt',w = 400,h = 300){
          tb_show(title, '#TB_inline?width='+w+'&height='+h);
          jQuery(document).find('#TB_window').width(TB_WIDTH).height(TB_HEIGHT).css('margin-left', - TB_WIDTH / 2);
          jQuery('#TB_ajaxContent').html(content);
    },

    setTemplateAttributes: function (e){
        const post = wp.data.select('core/editor').getCurrentPost();
        if(post.type == 'materialtyp_template'){
            const update = wp.data.dispatch('core/block-editor').updateBlockAttributes;
            const getBlocks = wp.data.select('core/block-editor').getBlocks;
            getBlocks().forEach((block)=>{
                update(block.clientId,{'template':post.slug})
            });
        }

    },

    insert:function (id, top=0){
        $=jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplate',
                id: id
            },
            function (response) {
                let contentpart = response;
                let new_blocks =[];
                let mark_blocks =[];
                let content = wp.data.select("core/editor").getCurrentPost().content;
                if(top == 1){
                    new_blocks = wp.blocks.parse( contentpart );
                    for (const newBlock of wp.data.select("core/editor").getBlocks()) {
                        new_blocks.push(newBlock)
                    }
                }else{
                    new_blocks=wp.data.select("core/editor").getBlocks();
                    for (const newBlock of wp.blocks.parse( contentpart )) {

                        new_blocks.push(newBlock);
                        mark_blocks.push(newBlock);
                    }
                }
                wp.data.dispatch( 'core/editor' ).resetBlocks( new_blocks );
                mark_blocks.forEach((b)=>{
                    $('#block-'+b.clientId).addClass('highlight');
                    location.hash = 'block-'+b.clientId;
                });
                setTimeout(()=>{
                    $('.highlight').removeClass('highlight');
                },10000);
                $('.highlight').on('click',(e)=>{
                    $(e.target).removeClass('highlight');
                });
                RpiMaterialInputTemplate.init();
                RpiMaterialInputTemplate.resetStaticBlockPositions();


                // if(jQuery('#TB_closeWindowButton').length>0){
                //     jQuery(window).trigger('reflexionInserted');
                //     jQuery('#TB_closeWindowButton').click();
                //     setTimeout(()=> {
                //         wp.data.select('core/block-editor').getBlocks().forEach((block,i)=>{
                //             if (i==p){
                //                 document.location.hash = 'block-'+block.clientId;
                //             }
                //         })
                //     },1000);
                //}
                RpiMaterialInputTemplate.displayWritingProgress();
            }

        );
    },

    getTemplates:function (term='checkbox'){
        $=jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplates',
                'term':term
            },
            function (response) {
                if(term=='checkbox'){
                    $('#template-config-box').html(response);
                }else {

                    $('#template-'+term+'-box').html(response);
                }
                let blocks = wp.data.select("core/editor").getBlocks();
                $('.reli-inserter').each((i,elem)=>{
                    let templ = $(elem).attr('data');
                    for(const b of blocks){
                        //console.log(b.attributes.template,templ);
                        if(b.attributes.template && templ == b.attributes.template){
                            $(elem).find('a').attr('href', "javascript:RpiMaterialInputTemplate.remove('"+templ+"')");
                            $(elem).addClass('remove');
                            $(elem).attr('title', 'entfernen')
                        }
                    }
                });

            },


        );
    },

    remove: function(template){
        $=jQuery;
        let blocks = wp.data.select("core/editor").getBlocks();
        for (const block of blocks) {
            // console.log('remove',blocksstr ,block.name, blocksstr.indexOf(block.name));
            if(template == block.attributes.template){
                // console.log('removes',block.clientId );
                wp.data.select('core/block-editor').getBlockSelectionEnd();
                wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId,{'lock':false})
                wp.data.dispatch('core/block-editor').removeBlock(block.clientId);
                wp.data.select('core/block-editor').getBlockSelectionStart();
            }
        }
        this.displayWritingProgress();
        this.init();
        this.resetStaticBlockPositions();

    },
    resetStaticBlockPositions: function (){

        let feature = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.name == 'core/post-featured-image')[0];
        let teaser = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.name == 'lazyblock/reli-leitfragen-kurzbeschreibung')[0];

        if(teaser){
            this.moveTop(teaser);
        }
        if(feature){
            this.moveTop(feature);
        }

        let anhang = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.name == 'lazyblock/reli-leitfragen-anhang')[0];
        if(anhang){
            this.moveBottom(anhang);
        }

    },
    moveBottom: function (block){
        const z = wp.data.select('core/block-editor').getBlocks().length;
        this.unlock(block);
        for(i=0;i<z;i++){
            wp.data.dispatch('core/block-editor').moveBlocksDown([block.clientId])
        }
        //this.lock(block);
    },
    moveTop: function (block){
        const z = wp.data.select('core/block-editor').getBlocks().length;
        this.unlock(block);
        for(i=0;i<z;i++){
            wp.data.dispatch('core/block-editor').moveBlocksUp([block.clientId])
        }
        this.lock(block);
    },
    lock: function (block){
        block.attributes.lock ={move:true,remove:true};
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId,block);
    },
    unlock: function (block){
        delete block.attributes.lock;
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId,block);
    },
    setPermissions: function (){
        const $ = jQuery;

        const blocksAllowInserter = [
            'core/columns',
            'core/column',
            'core/group',
            'kadence/tab',
            'kadence/row',
            'kadence/column'
        ];

        const types = wp.blocks.getBlockTypes();

        if (rpi_material_input_template.user.is_editor && location.hash == '#admin') {

            $('.block-editor-inserter').css('visibility', 'visible');
            $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", null);



            for (const blocktype of types) {
                if (blocktype.supports) {

                    blocktype.supports.inserter = true;
                    delete blocktype.supports.innerBlocks;

                }
            }

            let blocks = wp.data.select('core/block-editor').getBlocks()
            for (block of blocks) {
                delete block.attributes.lock;
                delete block.attributes.lock;
            }
            return;
        }


        // hide insert buttons
        $('.block-editor-inserter').css({'visibility': 'hidden'});
        $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true);


        var curr_block = wp.data.select('core/block-editor').getSelectedBlock();

        if (curr_block != null && curr_block.clientId) {

            //oberstes Eltern-Element ermitteln.
            const parentClientId = wp.data.select('core/block-editor').getBlockHierarchyRootClientId(curr_block.clientId);
            if (parentClientId) {
                curr_block = wp.data.select('core/block-editor').getBlock(parentClientId);
            }

            if (blocksAllowInserter.includes(curr_block.name) || curr_block.name.indexOf('lazyblock/') === 0) {

                 console.log('unlock inserter', curr_block.name)
                // show insert buttons
                $('.block-editor-inserter').css('visibility', 'visible');
                $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", null);

                // inserter wieder aktiv setzen
                for (const blocktype of types) {
                    if (blocktype.supports) {

                        blocktype.supports.inserter = true;
                        delete blocktype.supports.innerBlocks;

                    }
                }
            }

        }


    },
    denyInserts: function (){

        if (rpi_material_input_template.user.is_editor && location.hash == '#admin') return;

        //root blocks überprüfen ob sie einen Absatz enthalten
        let blocks = wp.data.select('core/block-editor').getBlocks()
        for (const block of blocks) {
            // console.log(block.name, block.name == 'core/paragraph', block.clientId);
            if (block.name == 'core/paragraph') {

                // wenn der Absatz keinen parent hat, löschen
                parentClientId = wp.data.select('core/block-editor').getBlockHierarchyRootClientId(block.clientId);
                if (parentClientId == block.clientId) {
                    wp.data.dispatch('core/block-editor').removeBlock(block.clientId);
                    wp.data.select('core/block-editor').getBlockSelectionStart();
                    // RpiMaterialInputTemplate.init();
                }


            }else{
                //deny delete on root blocks (works not as exspected)
                // block.attributes.lock = {remove: true};
            }

        }
        jQuery('.block-editor-default-block-appender[data-root-client-id=""]').css({'display':'none'});


        // hide insert buttons on start


        //$('.block-editor-inserter').css({'visibility': 'hidden'});
        //$('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true);



    },
    addToolbar: function($){
        /**
         * fügt eine zusätzliche Toolbar in die obere Werkzeugleiste des Editors ein
         */

        if(wp.data.select('core/editor').getCurrentPost().type != rpi_material_input_template.options.post_type){
            return;
        }

        var editorEl = document.getElementById( 'editor' );
        if( !editorEl ){ // do nothing if there's no gutenberg root element on page.
            return;
        }


        if ( $( '.rpi-material-toolbar' ).length ===0  ) {
            // prepare our custom link's html.
            var template_box_html = '' +
                '<div class="rpi-material-toolbar">' +
                '<button id="template-config-toggle" class="components-button is-tertiary">Vorlage anpassen</button>' +
                '<button id="display-post-btn" class="components-button is-tertiary">Beitrag ansehen</button>' +
                '</div>' +
                '<div id="template-config-box"></div>';


            var toolbalEl = editorEl.querySelector('.edit-post-header__toolbar');
            if (toolbalEl instanceof HTMLElement) {
                toolbalEl.insertAdjacentHTML('beforeend', template_box_html);
                var left = jQuery('#template-config-toggle').offset().left;
                $('#template-config-box').offset({left: left});
            }

            $('#template-config-toggle').click((e) => {
                jQuery('#template-config-box').slideToggle();
            });
            $('.interface-interface-skeleton__body').click((e) => {
                $('#template-config-box').slideUp();
            });
            $('#display-post-btn').click((e) => {
                location.href =  wp.data.select('core/editor').getCurrentPost().link;
            });
            RpiMaterialInputTemplate.init();
        }

    },
    doWith_ACF_Fields:function ($){

        //wp authorID mit acf-field-user synchronisieren
        $('.acf-field-user').on('change',(e)=>{
            wp.data.dispatch('core/editor').editPost({author:$('.acf-field-user select').val()})
        })
        let authorID = wp.data.select('core/editor').getPostEdits().author;
        $(window).on('authorChanged',(e, authorID)=>{
            if(authorID != $('.acf-field-user select').val()){
                wp.apiFetch( { path: '/wp/v2/users/'+authorID } ).then( author => {
                    $('.acf-field-user select option').remove();
                    $('.acf-field-user select').append(new Option(author.name, authorID ,false, true));
                });
            }
        });
    },
    onContentChange: function (e){

    }


}
jQuery(document).ready(($)=>{

    RpiWorkflow.addWorkflowStep(
        'writecontenet',
        [
            ()=>!wp.data.select('core/block-editor').hasSelectedBlock(),
            ()=>wp.data.select('core/editor').getEditedPostContent().length == wp.data.select('core/editor').getCurrentPostAttribute('content').length,
            ()=>RpiWorkflow.counter>1
        ],
        function (wfs){
            //Startdialog
            $dialog = RpiWorkflow.dialog({
                content:'Klicke auf einen der Inhaltsblöcke und beginne mit deiner Eingabe, Du kannst übrigens auch Bilder und einbinden, indem du sie auf die Eingabeaufforderung ziehst',
                w:200,
                h:100,
                button: 'Ok, verstanden'
            });
            console.log($dialog)
            if(!wfs.finished )
                setTimeout(()=>{ if(!$dialog.is_open()) wfs.started = false},20000 );
        },
        [
            ()=>wp.data.select('core/block-editor').hasSelectedBlock(),
            ()=>wp.data.select('core/editor').getEditedPostContent().length - wp.data.select('core/editor').getCurrentPostAttribute('content').length > 0
        ],
        function (wfs){
            //Startdialog
            $dialog =  RpiWorkflow.dialog({
                content:'Super, Su hast selbst herausgefunden, wo man Inhalte eingibt! Du kannst übrigens auch Bilder und einbinden, indem du sie auf die Eingabeaufforderung ziehst',
                w:300,
                h:200,
                button: 'War nicht schwer'
            });
            $dialog.btn.click((e)=> wfs.finish());
        },
    );

});

RpiWorkflow ={

    workflow:[],

    /**
     * @parem slug unique name of the worklfow step
     * @param startconditions   array of functions that return bool e.g: [(step)=>!step.percent]
     * @param startfn function  e.g. (step)=>{ RpiWorkflow.dialog('jetzt geht es los'); step.percent=1; }
     * @param endconditions     array of functions that return bool e.g: [(step)=>step.percent > 99]
     * @param endfn function    e.g. (step)=>RpiWorkflow.dialog('Du hast es geschafft')
     * @param properties        array of properties  eg ['percent']
     */
    addWorkflowStep: function (slug='',
                               startconditions=[()=>true],
                               startfn = (wfs)=>{ wfs.started =true },
                               endconditions=[()=>false],
                               endfn = (wfs)=>{wfs.finish()},
                               properties = []
    ){
        let wfs = {
            step: slug,

            start: startconditions,
            end: endconditions,

            do_start: function (){
                if(wfs.started || wfs.finished){
                    return;
                }
                startfn(wfs);

            },
            do_end: function (wfs){
                if(!wfs.finished){
                    endfn(wfs);
                }

            },
            finish: function (){
                this.finished = true;
                RpiWorkflow.setMeta(this)
            },

            started:false,
            finished:false,
            poperties: properties,
        };
        this.workflow.push(wfs);
    },

    is_running: false,
    counter: 0,

    init: function (){
        if(!this.is_running){
            window.__RpiWorkflow = setInterval(()=>{
                this.run();
            },5000);
            this.is_running =true;
        }
    },

    run: function(){
        RpiWorkflow.counter ++;

        for(const wfs of this.workflow){

            if(RpiWorkflow.counter<1){
                wfs.finished = RpiWorkflow.getMeta(wfs);
            }

            if(wfs.finished || RpiWorkflow.getMeta(wfs)){
                continue;
            }

            //check endconditions
            if(this.is_met(wfs.end)){
                wfs.do_end(wfs);
            }
            //check startconditions
            if(this.is_met(wfs.start) && !wfs.started){
                wfs.do_start(wfs);
            }
        }
    },

    is_met: function (conditions){
        var is_met = true;

        //all conditions should be true
        for(const condition of conditions){
            if(!condition()){
                is_met = false;
            }
        }
        return is_met;
    },

    _get:function (slug){
        for(wfs of this.workflow){
            if(wfs.step == slug){
                return(wfs);
            }

        };
    },

    get: function (slug, property){
        let wfs = this._get(slug);
        return wfs[property];
    },
    set: function (slug, property,value){
        let wfs = this._get(slug);
        wfs[property] = value;
    },

    start:function (wfs){
        wfs.do_start(wfs);
    },

    finish: function(wfs){
        if (typeof wfs === 'string' || wfs instanceof String){
            console.log(wfs, this.workflow);
            wfs = this._get(wfs);
            console.log(wfs);
        }
        wfs.do_end(wfs);
    },

    dialog: function (args={title:'',content:'',w:400,h:300,button:'OK',step:null}){
        args = {
            title:args.title    ||'Hilfe' ,
            content:args.content||'',
            w:args.w            ||400,
            h:args.h            ||300,
            button:args.button  ||'OK',
            step:args.step      ||null
        };


        tb_show(args.title, '#TB_inline?width='+args.w+'&height='+args.h);
        jQuery(document).find('#TB_window').width(TB_WIDTH).height(TB_HEIGHT).css('margin-left', - TB_WIDTH / 2);
        jQuery('#TB_ajaxContent').html(args.content);

        if(jQuery('#tb_bottom_bar_btn').length === 0){

            jQuery('<div class="tb_bottom_bar"><button id="tb_bottom_bar_btn" class="button is_primary">'+args.button+'</button></div>').insertAfter(jQuery('#TB_ajaxContent'));

            jQuery('#tb_bottom_bar_btn').click((e)=>{

                if(args.step !== null){
                    RpiWorkflow.finish(args.step);
                }
                tb_remove();
            });

        }
        return {btn:jQuery('#tb_bottom_bar_btn'),content:jQuery('#TB_ajaxContent'), is_open:()=>jQuery('#TB_window').css('visibility')=='visible'};

    },

    getMeta: function (wfs){
        let user = wp.data.select("core").getCurrentUser();
        let user_steps = user.meta.workflow_step;
        let post_id =  wp.data.select("core/editor").getCurrentPostId();
        for(const user_step of user_steps){
            if(user_step.step == wfs.step && user_step.post_id == post_id){
                return(user_step.finished);
            }
        };
        return false;
    },
    setMeta: function (wfs){
        let user = wp.data.select("core").getCurrentUser();
        let user_steps = user.meta.workflow_step;
        let post_id =  wp.data.select("core/editor").getCurrentPostId();
        updates = [];
        for(const user_step of user_steps){
            if(user_step.step == wfs.step && user_step.post_id == post_id){

            }else{
                updates.push(user_step)
            }
        }
        updates.push({post_id:post_id,step:wfs.step,finished:wfs.finished});
        user.meta.workflow_step = updates;
        wp.data.dispatch("core").saveUser(user);
    },
    do_nothing: function (){}

}

wp.hooks.addAction('lzb.components.PreviewServerCallback.onChange','templates', function (props) {

   jQuery(window).on('editorBlocksChanged',RpiMaterialInputTemplate.setTemplateAttributes);
   jQuery(window).on('editorContentChanged',RpiMaterialInputTemplate.onContentChange);

});

wp.hooks.addFilter('editor.BlockEdit', 'namespace', function (fn) {

    if (wp.data.select('core/editor').getCurrentPostType() != rpi_material_input_template.options.post_type) {
        return fn;
    }

    var deactivatedBlocks = rpi_material_input_template.options.deactivated_blocks;


    wp.blocks.getBlockTypes().forEach(function (blockType) {

        if(deactivatedBlocks.includes(blockType.name)){
            wp.blocks.unregisterBlockType(blockType.name);
        }

    });



    var post_id = wp.data.select("core/editor").getCurrentPostId();

    jQuery(document).ready(function ($) {

        location.hash='gettemplates';
        location.hash='';

        $(window).bind( 'hashchange', function(e) {
            if(location.hash=='#gettemplates'){
                $('#template-config-toggle').click();
            }
        });

        //blockeditor ui aufräumen nicht core Zeugs ausblenden;

        $('.interface-pinned-items button').css({'display':'none'});
        $('.interface-pinned-items button:first-child').css({'display':'unset'});

        //hide
        $('.edit-post-header-toolbar > div').css({'opacity':0});
        $('.edit-post-header-toolbar > div:first-child').css({'opacity':1});
        $('.edit-post-header-toolbar > div.rpi-material-toolbar').css({'opacity':1});

        //move kadence-toolbar autside
        $('.kadence-toolbar-design-library button').click(()=>{return false;});
        $('.kadence-toolbar-design-library').css({'position':'absolute','top':'-100px'});

        //inspector schließen
        $('.interface-pinned-items button.is-pressed').click();


        $('#postbox-container-2').on('click',(e)=>{
            RpiMaterialInputTemplate.displayMetaProgress();
        });
        $('.edit-post-visual-editor__content-area').on('click',(e)=>{
            RpiMaterialInputTemplate.displayWritingProgress();
        });

        $('.editor-styles-wrapper.block-editor-writing-flow').click(()=>{
            if(!wp.data.select('core/block-editor').getSelectedBlock()){
                $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true);
                $('.block-editor-inserter').css({'visibility': 'hidden'});
            }
        })

        $('.block-editor-block-list__layout').on('click', function (e) {

            RpiMaterialInputTemplate.setPermissions();
        });

        /**
         * verhindern das ein Absatz auf der Obersten Dokumenteben gesetzt werden kann
         * mit Hilde eine Document Observers, der bei Veränderung des Doms feuert
         */
        $('.block-editor-block-list__layout').bind("DOMSubtreeModified", function (e) {

            RpiMaterialInputTemplate.denyInserts($);

        });
        /**
         * Bedonderheiten von ACF verhalten fixen
         */
        RpiMaterialInputTemplate.doWith_ACF_Fields($);


        $(window).on('keyup',(e)=>{

            var block =  wp.data.select('core/editor').getSelectedBlock();
            if(!block){
                return;
            }

            var parent_id = wp.data.select('core/block-editor').getBlockHierarchyRootClientId(block.clientId);
            var main_block = wp.data.select('core/editor').getBlock(parent_id);

            $(window).trigger('typing',[parent_id,main_block]);

            /**
             * excerpt aus Block schreiben, wenn is_teaser == true
             */

            if(main_block.attributes.is_teaser){
                var text = jQuery('#block-'+ parent_id +' .block-editor-inner-blocks').html().replace(/(<[^>]*>)/gi,'');
                var post_id = wp.data.select('core/editor').getCurrentPost().id;
                wp.data.dispatch('core/editor').editPost({'id':post_id,'excerpt':text });
            }

            /**
             * Blockeingabe überprüfen und Fortschritt im übergeordneten Lazyblock anzeigen
             */
            if(main_block.attributes.minimum_characters && !main_block.attributes.is_valid && main_block.attributes.minimum_characters >0){

                //$el = zugehöriger html block als jQuery Element
                var $el = jQuery('#block-'+ parent_id +' .lazyblock');

                //innerhalb des editierbaren bereiches prüfen
                if(typeof e.target.attributes.contenteditable.value != "undefined"){

                    //temporäre Block Eigenschaft in der die Zeichenlängen aller innerBlocks gespeichert werden
                    if(!main_block.contentBlocks) {
                        main_block.contentBlocks = {};
                    }
                    let text = e.target.innerHTML.replace(/(<[^>]*>)/ig,'');
                    main_block.contentBlocks[block.clientId]=text.length;
                }
                //Zeichenlängen aller innerBlocks summieren
                //https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Array/Reduce
                var len = Object.values(main_block.contentBlocks).reduce((pre, curr) => pre + curr);

                //Berechnung des Fortschritts anhand der aktuellen Zeichenlänge und der in der Leitfrage
                //gesetzten minimalen Zeichenlänge
                var percent = len * 100 /main_block.attributes.minimum_characters;
                if(percent>100) percent =100;

                //ein div zum anzeigen eines Fortschrittbalkens am oberen Rand des Blocks hinzufügen
                if(jQuery('#progress-'+ parent_id).length===0){
                    jQuery('<div id="progress-'+ parent_id +'" class="block-progress"></div>')
                        .insertBefore( $el );
                }
                jQuery('#progress-'+ parent_id).css({'border-bottom':'3px solid green','width':percent+'%'});

                //Wenn 100% Fortschritt erreicht sind:
                if(percent == 100){
                    $(window).trigger('write_progress',[$el[0],$(window).trigger('typing',[parent_id,main_block])]);
                    $el.addClass('is_valid');
                    wp.data.dispatch('core/block-editor').updateBlockAttributes(parent_id,{'is_valid':true});
                    jQuery('#progress-'+ parent_id).remove();
                }
            }

        });


        wp.data.select("core/editor").getBlocks().forEach((b)=>{
            if(b.attributes.is_valid){
                console.log('first fetch',b.attributes);
                $('#block-'+b.clientId +' .lazyblock').addClass('is_valid');
            }
        });
    });

    //acf-field-user mit wp author id setzen
    jQuery('.acf-field-user select').ready(($)=>{
        $(window).trigger('authorChanged',wp.data.select('core/editor').getCurrentPostAttribute('author') ); }
    );

    jQuery('.edit-post-header-toolbar__inserter-toggle').ready(($)=> {
        console.log('edit-post-header-toolbar__inserter-toggle ready');
        RpiMaterialInputTemplate.addToolbar($);
    });
    return fn;
});

( function( window, wp ){

    /**
     * create Observer
     * fire Events editorBlocksChanged and editorContentChanged
     */
    let __editor_content_loaded = false

    wp.domReady(() => {

        return;

        const editor = wp.data.select('core/block-editor');
        let blockList = editor.getClientIdsWithDescendants();
        let blockcontent = wp.data.select('core/editor').getCurrentPostAttribute('content');
        let authorID =wp.data.select('core/editor').getCurrentPostAttribute('author');
        wp.data.subscribe(() => {

            //notwendig um wp authorID mit acf-field-user synchronisieren
            if(wp.data.select('core/editor').getPostEdits().author && wp.data.select('core/editor').getPostEdits().author != authorID){
                authorID = wp.data.select('core/editor').getPostEdits().author;
                jQuery(window).trigger('authorChanged', [authorID]);
            }

            if(editor.getSelectedBlock()!==null){
                const currblock = editor.getBlock(
                    editor.getBlockHierarchyRootClientId(editor.getSelectedBlockClientId())
                );

                const newHTML = wp.data.select('core/editor').getEditedPostContent();
                const contentChanged = (newHTML != blockcontent);
                blockcontent = newHTML;

                const newBlockList = editor.getClientIdsWithDescendants();
                const blockListChanged = newBlockList !== blockList;
                blockList = newBlockList;


                if (blockListChanged) {
                    jQuery(window).trigger('editorBlocksChanged', [currblock, editor.getBlocks()]);
                    RpiMaterialInputTemplate.displayWritingProgress();
                }
                if(contentChanged){
                    console.log('contentChanged',currblock.clientId )
                    //jQuery(window).trigger('editorContentChanged',[currblock, newHTML]);
                }

            }else{
                if(editor.getBlocks().length > 0 && !__editor_content_loaded){
                    console.log('__editor_content_loaded');
                    __editor_content_loaded = true;
                    RpiMaterialInputTemplate.denyInserts(jQuery);
                    RpiMaterialInputTemplate.setPermissions();
                }
            }




        });

    });


} )( window, wp )
