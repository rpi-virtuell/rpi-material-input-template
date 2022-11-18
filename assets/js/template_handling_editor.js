/**
 * @author Joachim happel
 */
(function ($, wp, window) {

    wp.domReady(() => {

        $(document).ready(function () {

            //location back verhindern
            location.hash = 'gettemplates';
            location.hash = '';

            const post = wp.data.select('core/editor').getCurrentPost();
            //console.log(post.type, rpi_material_input_template.options.post_type);
            if (post.type != rpi_material_input_template.options.post_type) {
                return;
            }




            if (post.status == 'draft') {
                //verhindern, dass  user wieder in das formular zurück switchen
                $(window).bind('hashchange', function (e) {
                    if (location.hash == '#gettemplates') {
                        $('#template-config-toggle').click();
                    }
                });
                $('.edit-post-header > div:first-child').click(e=>{location.href='/meinprofil'; return false;});

            }else{
                $('.edit-post-header > div:first-child').click(e=>{location.href=wp.data.select('core/editor').getPermalink(); return false;});
            }
            //blockeditor ui aufräumen nicht core Zeugs ausblenden;

            //move kadence-toolbar autside
            $('.kadence-toolbar-design-library button').click(() => {
                return false;
            });
            $('.kadence-toolbar-design-library').css({'position': 'absolute', 'top': '-100px'});


            //smooth scrooling
            $(window).bind('hashchange', function (e) {
                e.preventDefault();
                const hash = location.hash.toString();
                if ($(hash).length > 0) {
                    $(hash)[0].scrollIntoView({behavior: "smooth"});
                }
            });

            /**
             * Bedonderheiten von ACF verhalten fixen
             */
            RpiMaterialInputTemplate.doWith_ACF_Fields($);



            if (post.status == 'draft'){
                $('.interface-pinned-items button').css({'display': 'none'});
                $('.interface-pinned-items button:first-child').css({'display': 'unset'});

                //hide
                $('.edit-post-header-toolbar > div').css({'opacity': 0});
                $('.edit-post-header-toolbar > div:first-child').css({'opacity': 1});
                $('.edit-post-header-toolbar > div.rpi-material-toolbar').css({'opacity': 1});

                //hide inserters
                $('.editor-styles-wrapper.block-editor-writing-flow').click(() => {
                    if (!wp.data.select('core/block-editor').getSelectedBlock()) {
                        $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true);
                        $('.block-editor-inserter').css({'visibility': 'hidden'});
                    }
                })
                //inspector schließen
                $('.interface-pinned-items button.is-pressed').click();


                //schreib fortschritt anzeigen
                $(window).on('editorBlocksChanged', () => RpiMaterialInputTemplate.displayWritingProgress());


                $('.block-editor-block-list__layout').on('click', function (e) {

                    RpiMaterialInputTemplate.setPermissions();
                });

                /**
                 * verhindern das ein Absatz auf der Obersten Dokumenteben gesetzt werden kann
                 * mit Hilde eine Document Observers, der bei Veränderung des Doms feuert
                 */
                $('.block-editor-block-list__layout').bind("DOMSubtreeModified", function (e) {

                    RpiMaterialInputTemplate.denyParagraphsInRootHierarchy($);
                });

                //progressbar content generation initialisieren
                $('.edit-post-visual-editor__content-area').on('click', (e) => {
                    RpiMaterialInputTemplate.displayWritingProgress();
                    RpiMaterialInputTemplate.isSetFeaturedImage();

                });

                //progressbar Meta fields initialisieren
                $('#postbox-container-2').on('click', (e) => {
                    RpiMaterialInputTemplate.displayMetaProgress();
                });


                setTimeout(() => {

                    $('.edit-post-visual-editor__content-area').click();
                    RpiMaterialInputTemplate.displayMetaProgress();
                    $('#rpi-material-meta-progress').on('click', (e) => {
                        RpiMaterialInputTemplate.displayMetaProgress();
                        location.hash = 'postbox-container-2'
                    });


                    console.log('Beitragsbild oder Kurzbeschreibung wählen');
                    const imgs = wp.data.select('core/block-editor').getBlocks().filter((b) => b.name == 'core/post-featured-image');
                    if (imgs.length > 0) {
                        wp.data.dispatch('core/block-editor').selectBlock(imgs.shift().clientId);
                        if (RpiMaterialInputTemplate.isSetFeaturedImage()) {
                            wp.data.dispatch('core/block-editor').selectNextBlock();
                            const id = '#block-' + wp.data.select('core/block-editor').getSelectedBlock().clientId;
                            $(id)[0].scrollIntoView({block: "end", behavior: "smooth"});

                        }
                    } else {
                        blocks = wp.data.select('core/block-editor').getBlocks();
                        if (blocks.length > 0 && blocks[0].clientId) {
                            wp.data.dispatch('core/block-editor').selectBlock(blocks[0].clientId);

                        }
                    }

                }, 2000);


                $('.edit-post-header-toolbar__inserter-toggle').ready(() => {
                    console.log('edit-post-header-toolbar__inserter-toggle ready');
                    RpiMaterialInputTemplate.addToolbar($);
                });



                wp.data.select("core/editor").getBlocks().forEach((b) => {
                    if (b.attributes.is_valid) {
                        //$('#block-' + b.clientId + ' .lazyblock').addClass('is_valid');
                        //console.log('first fetch', b.attributes);
                        $('#block-' + b.clientId + ' .lzb-content-controls').addClass('is_valid');
                    }
                });
            }
        });

        //acf-field-user mit wp author id setzen
        $('.acf-field-user select').ready(() => {
                $(window).trigger('authorChanged', wp.data.select('core/editor').getCurrentPostAttribute('author'));
            }
        );





        /**
         * Verschiedene Vorgänge via wp.data.subscribe abonnieren und als Events triggern
         */
        const editor = wp.data.select('core/block-editor');
        let blockList = editor.getClientIdsWithDescendants();
        let authorID = wp.data.select('core/editor').getCurrentPostAttribute('author');
        let time = () => Math.floor(Date.now() / 1000);
        var timer = time(),newTime;

        wp.data.subscribe(() => {

            newTime = time();


            if (wp.data.select('core/editor').isSavingPost() && !wp.data.select('core/editor').isAutosavingPost()) {
                jQuery(window).trigger('post_save', [wp.data.select('core/editor').getEditedPostAttribute('status')]);
            }
            const currentPostStatus = wp.data.select( 'core/editor' ).getEditedPostAttribute( 'status' );
            if ( 'publish' === currentPostStatus && RpiWorkflow.is_running) {
                if( wp.data.select('core/editor').didPostSaveRequestSucceed() ){

                   // console.log(wp.data.select('core/editor').getPermalink());

                    setTimeout(e=>{location.href=wp.data.select('core/editor').getPermalink();},1000);

                }

            }

            if (wp.data.select('core/editor').isTyping()) {
                if (newTime > timer +1) {

                    let currentBlock = editor.getSelectedBlock();
                    if (currentBlock) {
                        let targetBlock = editor.getBlock(editor.getBlockHierarchyRootClientId(currentBlock.clientId));
                        let target = document.getElementById('block-' + currentBlock.clientId);
                        if (target && targetBlock){

                            console.log('trigger typing');
                            if(RpiMaterialInputTemplate.is_watching ===false){
                                RpiMaterialInputTemplate.watchTyping(currentBlock, targetBlock, target);
                            }

                            //jQuery(window).trigger('typing', [currentBlock, targetBlock, target]);
                        }



                    }

                    timer = time();
                }
            }

            //notwendig um wp authorID mit acf-field-user synchronisieren
            if (wp.data.select('core/editor').getPostEdits().author && wp.data.select('core/editor').getPostEdits().author != authorID) {
                authorID = wp.data.select('core/editor').getPostEdits().author;
                jQuery(window).trigger('authorChanged', [authorID]);
            }


            if (editor.getSelectedBlock() !== null) {

                const newBlockList = editor.getClientIdsWithDescendants();
                const blockListChanged = newBlockList !== blockList;
                blockList = newBlockList;

                if (blockListChanged) {
                    jQuery(window).trigger('editorBlocksChanged');
                }
            }
        });
    });

    /**
     * blocks sie in Materialioptionen deaktiviert sind deregistrieren
     */
    wp.hooks.addFilter('editor.BlockEdit', 'namespace', function (fn) {
        const post = wp.data.select('core/editor').getCurrentPost();
        if (post.type != rpi_material_input_template.options.post_type) {
            return fn;
        }

        var deactivatedBlocks = rpi_material_input_template.options.deactivated_blocks;

        wp.blocks.getBlockTypes().forEach(function (blockType) {

            if (deactivatedBlocks.includes(blockType.name)) {
                wp.blocks.unregisterBlockType(blockType.name);
            }

        });

        return fn;
    });


}(jQuery, wp, window))



RpiMaterialInputTemplate = {
    has_progress: false,
    is_watching:false,
    init: function () {

        this.resetStaticBlockPositions();
        this.getTemplates();

    },
    addToolbar: function ($) {
        /**
         * fügt eine zusätzliche Toolbar in die obere Werkzeugleiste des Editors ein
         */

        if (wp.data.select('core/editor').getCurrentPost().type != rpi_material_input_template.options.post_type) {
            return;
        }

        var editorEl = document.getElementById('editor');
        if (!editorEl) { // do nothing if there's no gutenberg root element on page.
            return;
        }
        let status = wp.data.select('core/editor').getEditedPostAttribute('status');


        if ($('.rpi-material-toolbar').length === 0) {

            if (status == 'draft') {
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
            }else{
                // prepare our custom link's html.
                var template_box_html = '' +
                    '<div class="rpi-material-toolbar">' +
                    '<button id="display-post-btn" class="components-button is-tertiary">Beitrag ansehen</button>' +
                    '</div>';

                var toolbalEl = editorEl.querySelector('.edit-post-header__toolbar');
                if (toolbalEl instanceof HTMLElement) {
                    toolbalEl.insertAdjacentHTML('beforeend', template_box_html);
                    if(jQuery('#template-config-toggle').length>0){
                        var left = jQuery('#template-config-toggle').offset().left;
                        $('#template-config-box').offset({left: left});
                    }

                }

            }



            $('#display-post-btn').click((e) => {
                location.href = wp.data.select('core/editor').getCurrentPost().link;
            });


            this.addProgressBar();
            this.init();
        }

    },
    addProgressBar: function (label, status) {

        if('draft' !== wp.data.select('core/editor').getCurrentPostAttribute('status')){
            return;
        }

        const $ = jQuery;
        if ($('#rpi-material-status').length === 0) {
            $('.rpi-material-toolbar').append('<div id="rpi-material-status" class="components-button">Status: &nbsp; ' +
                '<div id="rpi-material-status-bar" style="border:1px solid #ccc; width:150px; display: grid;grid-template-columns: 3fr 1fr;">' +
                '<div id="rpi-material-status-progress" style="width:10px; height:15px; background:greenyellow;"></div>' +
                '<div id="rpi-material-meta-progress" style="width:1px; height:15px; background:forestgreen;"></div>' +
                '</div></div>');
            $('.rpi-material-toolbar').append('<div class="components-button" style="border-right: 1px solid #ccc;">&nbsp;</div>');
            $('.rpi-material-toolbar').append('<button id="rpi-material-step" class="components-button is-primary">Speichern</button>');


            jQuery('.editor-post-publish-button__button').hide();
            jQuery('.block-editor-post-preview__dropdown').hide();
            jQuery('.editor-post-save-draft').hide();
            jQuery('#rpi-material-step').click((e) => {
                wp.data.dispatch('core/editor').savePost();
            });

        }

    },

    isSetFeaturedImage: function () {
        //detect wether is set featured Image
        if (jQuery('.wp-block-post-featured-image .block-editor-media-placeholder').length === 0) {
            jQuery('.wp-block-post-featured-image').addClass('is_valid');
            return true;
        }
        return false;
    },

    watchTyping: function(block, main_block, target){

        if (!block) {
            return;
        }

        this.is_watching = true;

        console.log('main_block',main_block);

        var parent_id = main_block.clientId;

        //$el = zugehöriger html block als jQuery Element
        //var $el = jQuery('#block-' + parent_id + '.lazyblock');
        var $el = jQuery('#block-' + parent_id + ' .lzb-content-controls');

        console.log($el);

        /**
         * Blockeingabe überprüfen und Fortschritt im übergeordneten Lazyblock anzeigen
         */
        if(main_block.attributes.is_valid && main_block.attributes.minimum_characters > 0){
            $el.addClass('is_valid');
        }else
            if (main_block.attributes.minimum_characters && !main_block.attributes.is_valid && main_block.attributes.minimum_characters > 0) {


            //innerhalb des editierbaren bereiches prüfen

            if (jQuery(target).attr('contenteditable') && typeof target.attributes.contenteditable.value != "undefined") {

                //temporäre Block Eigenschaft in der die Zeichenlängen aller innerBlocks gespeichert werden
                if (!main_block.contentBlocks) {
                    main_block.contentBlocks = {};
                }
                let text = target.innerHTML.replace(/(<[^>]*>)/ig, '');
                main_block.contentBlocks[block.clientId] = text.length;
                //console.log('target', target, main_block.contentBlocks);


            }


            if (typeof main_block.contentBlocks != 'undefined') {

                //console.log('main_block.contentBlocks', main_block.contentBlocks);

                var len = Object.values(main_block.contentBlocks).reduce((pre, curr) => pre + curr);

                //Berechnung des Fortschritts anhand der aktuellen Zeichenlänge und der in der Leitfrage
                //gesetzten minimalen Zeichenlänge
                var percent = len * 100 / main_block.attributes.minimum_characters;
                if (percent > 100) percent = 100;


                //ein div zum anzeigen eines Fortschrittbalkens am oberen Rand des Blocks hinzufügen
                if (jQuery('#progress-' + parent_id).length === 0) {
                    jQuery('<div id="progress-' + parent_id + '" class="block-progress" style="color:#ccc;font-size: xx-small;white-space: nowrap; margin-top: 20px;">Schreibe mindestens '+main_block.attributes.minimum_characters+' Zeichen</div>')
                        .insertAfter($el);
                    jQuery('#progress-' + parent_id).animate({'width':'1%'},{'duration':20});
                }
                //jQuery('#progress-0d6f3a3d-65c8-4b53-8d70-0cdd380abe5c').animate({'width':"30%"},{'duration':5000});
                jQuery('#progress-' + parent_id).css({'border-top': '6px solid #adff2f'});
                jQuery('#progress-' + parent_id).animate({'width':+percent+'%'},{'duration':1000});

                //Wenn 100% Fortschritt erreicht sind:
                if (percent === 100) {
                    $el.addClass('is_valid');
                    if (!main_block.attributes.is_valid){
                        wp.data.dispatch('core/block-editor').updateBlockAttributes(parent_id, {'is_valid': true});
                    }
                    jQuery('#progress-' + parent_id).remove();
                }
                this.is_watching = false;
                return;
            }
        }
        if (main_block.attributes.is_teaser) {
            RpiMaterialInputTemplate.writeExcerpt(main_block);
        }
        this.is_watching = false;
    },

    checkContent: function () {
        let total = wp.data.select('core/block-editor').getBlocks().filter((b) => b.attributes.minimum_characters > 0).length;
        let done = wp.data.select('core/block-editor').getBlocks().filter((b) => b.attributes.is_valid == true).length;
        return {
            todo: total - done,
            total: total,
            length: done
        };

    },

    checkMeta: function () {
        const $ = jQuery;
        const fieldsToCheck = [
            'lizenz',
            'materialtype',
            'alter',
            'kinderaktivitaten',
            'kinderfahrung'
        ];

        var filled = [], total = [], value;
        acf.getFields().forEach((field) => {

            if (fieldsToCheck.includes(field.data.name)) {
                if (!$('#acf-field-toCheck-' + field.data.name).length) {
                    field.$el.css({position: 'relative'});
                    $('<div id="acf-field-toCheck-' + field.data.name + '" class="acf-field-toCheck"></div>').insertBefore(field.$el.find('label').first());
                }

                if (field.data.type == 'select' || field.data.type == 'checkbox' || field.data.type == 'taxonomy') {
                    total.push(field.data.name);
                    value = field.val()
                    if (value && value != '') {
                        filled.push(field.data.name);
                    }
                }
            }

        })

        return {percent: filled.length * 100 / total.length};
    },

    displayWritingProgress: function () {
        let total = wp.data.select('core/block-editor').getBlocks().filter((b) => b.attributes.minimum_characters > 0).length;
        let ok = wp.data.select('core/block-editor').getBlocks().filter((b) => b.attributes.is_valid === true).length;
        let percent = ok * 100 / total;
        //console.log('displayWritingProgress', percent,ok,total);


        if(percent>0){
            jQuery('#rpi-material-status-progress').html('');
            jQuery('#rpi-material-status-progress').css({'width': percent + '%', 'max.width': '100%'});
            jQuery('#rpi-material-status-progress').attr('title', percent + '% begonnener Schreibprozess');
            jQuery('#rpi-material-status-progress-text').remove();
        }else{
            if(jQuery('#rpi-material-status-progress-text').length === 0){
                jQuery('#rpi-material-status-progress')
                    .after(jQuery('<div id ="rpi-material-status-progress-text" style="position: absolute; top:8px;margin-left:15px;font-size:xx-small;">Texteingabe fehlt</div>'));
            }

        }

        return {percent: percent};
    },
    displayMetaProgress: function () {
        let progress = this.checkMeta();
        jQuery('#rpi-material-meta-progress').css({width: progress.percent + '%', 'max.width': '100%'});
        jQuery('#rpi-material-meta-progress').attr('title', progress.percent + '% Metaangaben');
        return progress;
    },

    writeExcerpt: function (block) {

        const parent_id = block.clientId;
        var text = jQuery('#block-' + parent_id + ' .block-editor-inner-blocks').html().replace(/(<[^>]*>)/gi, '');
        var post_id = wp.data.select('core/editor').getCurrentPost().id;
        wp.data.dispatch('core/editor').editPost({'id': post_id, 'excerpt': text});

    },

    /**
     * Zeigt zusätzliche Blöcke für Vorlagen Interwies in Vorlagen Verwalten oder
     * im Modal Window unter dem WorkFlowstep Reflexion an
     * @param term
     */
    getTemplates: function (term = 'checkbox') {
        $ = jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplates',
                'term': term
            },
            function (response) {
                if (term == 'checkbox') {
                    $('#template-config-box').html(response);
                } else {

                    $('#template-' + term + '-box').html(response);
                }
                let blocks = wp.data.select("core/editor").getBlocks();
                $('.reli-inserter').each((i, elem) => {
                    let templ = $(elem).attr('data');
                    for (const b of blocks) {
                        //console.log(b.attributes.template,templ);
                        if (b.attributes.template && templ == b.attributes.template) {
                            $(elem).find('a').attr('href', "javascript:RpiMaterialInputTemplate.remove('" + templ + "')");
                            $(elem).addClass('remove');
                            $(elem).attr('title', 'entfernen')
                        }
                    }
                });

            },
        );
    },
    /**
     * Fügt Blocks einer Vorlage im editor ein
     * @param id
     * @param top 1:  am anfang 0: am Ende
     */
    insert: function (id, top = 0) {
        $ = jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplate',
                id: id
            },
            function (response) {
                let contentpart = response;
                let new_blocks = [];
                let mark_blocks = [];
                let content = wp.data.select("core/editor").getCurrentPost().content;
                if (top == 1) {
                    new_blocks = wp.blocks.parse(contentpart);
                    for (const newBlock of wp.data.select("core/editor").getBlocks()) {
                        new_blocks.push(newBlock)
                    }
                } else {
                    new_blocks = wp.data.select("core/editor").getBlocks();
                    for (const newBlock of wp.blocks.parse(contentpart)) {

                        new_blocks.push(newBlock);
                        mark_blocks.push(newBlock);
                    }
                }
                wp.data.dispatch('core/editor').resetBlocks(new_blocks);
                mark_blocks.forEach((b) => {
                    $('#block-' + b.clientId).addClass('highlight');
                    location.hash = 'block-' + b.clientId;
                });
                setTimeout(() => {
                    $('.highlight').removeClass('highlight');
                }, 10000);
                $('.highlight').on('click', (e) => {
                    $(e.target).removeClass('highlight');
                });
                RpiMaterialInputTemplate.init();
                RpiMaterialInputTemplate.resetStaticBlockPositions();
                RpiMaterialInputTemplate.displayWritingProgress();

            }
        );
    },
    /**
     * entfernt Blöcke einer Vorlage
     * @param template
     */
    remove: function (template) {
        $ = jQuery;
        let blocks = wp.data.select("core/editor").getBlocks();
        for (const block of blocks) {
            // console.log('remove',blocksstr ,block.name, blocksstr.indexOf(block.name));
            if (template == block.attributes.template) {
                // console.log('removes',block.clientId );
                wp.data.select('core/block-editor').getBlockSelectionEnd();
                wp.data.dispatch('core/block-editor').updateBlockAttributes(block.clientId, {'lock': false})
                wp.data.dispatch('core/block-editor').removeBlock(block.clientId);
                wp.data.select('core/block-editor').getBlockSelectionStart();
            }
        }
        this.displayWritingProgress();
        this.init();
        this.resetStaticBlockPositions();

    },
    /**
     * hält bestimmte Elemente am Anfang (beitragsbild, Exerpt) oder
     * am Ende (Quellennachweis, Anhang) alle Blocks
     */
    resetStaticBlockPositions: function () {

        let feature = wp.data.select('core/block-editor').getBlocks().filter((b) => b.name == 'core/post-featured-image')[0];
        let teaser = wp.data.select('core/block-editor').getBlocks().filter((b) => b.name == 'lazyblock/reli-leitfragen-kurzbeschreibung')[0];

        if (teaser) {
            this.moveTop(teaser);
        }
        if (feature) {
            this.moveTop(feature);
        }

        let anhang = wp.data.select('core/block-editor').getBlocks().filter((b) => b.name == 'lazyblock/reli-leitfragen-anhang')[0];
        if (anhang) {
            this.moveBottom(anhang);
        }
        let quellennachweis = wp.data.select('core/block-editor').getBlocks().filter((b) => b.name == 'lazyblock/reli-quellennachweis')[0];
        if (quellennachweis) {
            this.moveBottom(quellennachweis);
        }

    },
    /**
     * bewegt einen  Block ans untere Ende
     * @param block
     */
    moveBottom: function (block) {
        const z = wp.data.select('core/block-editor').getBlocks().length;
        this.unlock(block);
        for (i = 0; i < z; i++) {
            wp.data.dispatch('core/block-editor').moveBlocksDown([block.clientId])
        }
        //this.lock(block);
    },
    /**
     * bewegt einen  Block ans obere Ende
     * @param block
     */
    moveTop: function (block) {
        const z = wp.data.select('core/block-editor').getBlocks().length;
        this.unlock(block);
        for (i = 0; i < z; i++) {
            wp.data.dispatch('core/block-editor').moveBlocksUp([block.clientId])
        }
        this.lock(block);
    },
    /**
     * sperrt einen Block
     * @param block
     */
    lock: function (block) {
        block.attributes.lock = {move: true, remove: true, insert: false};
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId, block);
    },
    /**
     * enstperrt einen Block
     * @param block
     */
    unlock: function (block) {
        delete block.attributes.lock;
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId, block);
    },
    /**
     * schänkt bearbeitungsrechte und blockauswahl im block edtior ein
     */
    setPermissions: function () {
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
    /**
     *
     */
    denyParagraphsInRootHierarchy: function () {

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
                }
            }
        }
        jQuery('.block-editor-default-block-appender[data-root-client-id=""]').css({'display': 'none'});


    },

    /**
     * synchronisiert das Userfield von ACF mit dem wp-post-author
     * @param $
     */
    doWith_ACF_Fields: function ($) {

        //wp authorID mit acf-field-user synchronisieren
        $('.acf-field-user').on('change', (e) => {
            wp.data.dispatch('core/editor').editPost({author: $('.acf-field-user select').val()})
        })
        let authorID = wp.data.select('core/editor').getPostEdits().author;
        $(window).on('authorChanged', (e, authorID) => {
            if (authorID != $('.acf-field-user select').val()) {
                wp.apiFetch({path: '/wp/v2/users/' + authorID}).then(author => {
                    $('.acf-field-user select option').remove();
                    $('.acf-field-user select').append(new Option(author.name, authorID, false, true));
                });
            }
        });
    },

}
