/**
 * @author Joachim happel
 */
RpiMaterialInputTemplate = {
    init:function (){
        this.getTemplates();

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
    insert:function (id, top){
        $=jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplate',
                id: id
            },
            function (response) {
                let contentpart = response;
                let new_blocks =[];
                let content = wp.data.select("core/editor").getCurrentPost().content;
                if(top == 1){
                    new_blocks = wp.blocks.parse( contentpart );
                    for (const newBlock of wp.data.select("core/editor").getBlocks()) {
                        new_blocks.push(newBlock)
                    }
                }else{
                    new_blocks=wp.data.select("core/editor").getBlocks();
                    for (const newBlock of wp.blocks.parse( contentpart )) {
                        new_blocks.push(newBlock)
                    }
                }
                wp.data.dispatch( 'core/editor' ).resetBlocks( new_blocks );
                RpiMaterialInputTemplate.init();
                RpiMaterialInputTemplate.resetFeatureImage()
            }

        );
    },
    getTemplates:function (fn){
        $=jQuery;
        $.get(
            ajaxurl, {
                'action': 'getTemplates'

            },
            function (response) {
                $('#template-config-box').html(response);
                let blocks = wp.data.select("core/editor").getBlocks();
                $('.reli-inserter').each((i,elem)=>{
                    let templ = $(elem).attr('data');
                    for(const b of blocks){
                        console.log(b.attributes.template,templ);
                        if(templ == b.attributes.template){
                            $(elem).find('a').attr('href', "javascript:RpiMaterialInputTemplate.remove('"+templ+"')");
                            $(elem).addClass('remove');
                            $(elem).attr('title', 'entfernen')
                        }
                    }
                });
                if(fn)
                    fn();

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
        this.init();
        this.resetFeatureImage();
    },
    resetFeatureImage: function (){

        let block = wp.data.select('core/block-editor').getBlocks().filter((b)=>b.name == 'core/post-featured-image')[0];

        this.unlock(block);
        for(i=0;i<20;i++){
            wp.data.dispatch('core/block-editor').moveBlocksUp([block.clientId])
        }
        this.lock(block);

    },
    lock: function (block){
        block.attributes.lock ={insert:true,move:true,remove:true};
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId,block);
    },
    unlock: function (block){
        delete block.attributes.lock;
        wp.data.dispatch('core/block-editor').replaceBlock(block.clientId,block);
    }
}

wp.hooks.addAction('lzb.components.PreviewServerCallback.onChange','templates', function (props) {
    $=jQuery;
    $(window).on('editorBlocksChanged',RpiMaterialInputTemplate.setTemplateAttributes);

});

wp.hooks.addFilter('editor.BlockEdit', 'namespace', function (fn) {

    if (wp.data.select('core/editor').getCurrentPostType() != rpi_material_input_template.options.post_type) {
        return fn;
    }

    var allowedBlocks = rpi_material_input_template.options.allowed_blocks;


    wp.blocks.getBlockTypes().forEach(function (blockType) {
        if (allowedBlocks.indexOf(blockType.name) === -1) {
            if (blockType.name.indexOf('lazyblock/reli-') < 0) {
                wp.blocks.unregisterBlockType(blockType.name);
            }
        }
    });

    const blocksAllowInserter = [
        'core/columns',
        'core/column',
        'core/group',
        'kadence/tab',
        'kadence/row',
        'kadence/column'
    ];

    var post_id = wp.data.select("core/editor").getCurrentPostId();


    jQuery(document).ready(function ($) {



        //blockeditor ui aufräumen BlocksyConfig ausblenden;
        setTimeout(()=>{ $('.interface-pinned-items button:nth-child(2)').remove(); },2000 );

        // hide insert buttons on start
        $('.block-editor-inserter').css({'visibility': 'hidden'});
        $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true);

        //inspector ausblenden
        $('.interface-pinned-items button.is-pressed').click();


        //deny delete on root blocks

        let blocks = wp.data.select('core/block-editor').getBlocks();
        for (block of blocks) {
            // block.attributes.lock = {remove: true}
        }


        $('.block-editor-block-list__layout').on('click', function (e) {


            const types = wp.blocks.getBlockTypes();
            if (rpi_material_input_template.user.is_editor && location.hash == '#admin') {

                $('.block-editor-inserter').css('visibility', 'visible');
                $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", false)


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

            /**
             * default lock all
             */

            for (const blocktype of types) {
                if (blocktype.supports) {
                    blocktype.supports.inserter = false;
                    blocktype.supports.innerBlocks = false;
                }
            }
            // hide insert buttons
            $('.block-editor-inserter').css({'visibility': 'hidden'});
            $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", true)


            var curr_block = wp.data.select('core/block-editor').getSelectedBlock();

            if (curr_block != null && curr_block.clientId) {

                //oberstes Eltern-Element ermitteln.
                const parentClientId = wp.data.select('core/block-editor').getBlockHierarchyRootClientId(curr_block.clientId);
                if (parentClientId) {
                    curr_block = wp.data.select('core/block-editor').getBlock(parentClientId);
                }

                if (blocksAllowInserter.includes(curr_block.name) || curr_block.name.indexOf('lazyblock/') === 0) {

                    // console.log('unlock inserter', curr_block.name)
                    // show insert buttons
                    $('.block-editor-inserter').css('visibility', 'visible');
                    $('.edit-post-header-toolbar__inserter-toggle').prop("disabled", false)

                    // inserter wieder aktiv setzen
                    for (const blocktype of types) {
                        if (blocktype.supports) {

                            blocktype.supports.inserter = true;
                            delete blocktype.supports.innerBlocks;

                        }
                    }
                } else {
                    // console.log('lock inserter', curr_block.name);
                }

            }

        });

        /**
         * verhindern das ein Absatz auf der Obersten Dokumenteben gesetzt werden kann
         * mit Hilde eine Document Observers, der bei Veränderung des Doms feuert
         */

        $('.block-editor-block-list__layout').bind("DOMSubtreeModified", function (e) {

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
                        RpiMaterialInputTemplate.init();
                    }


                }
            }
        });
        //Event für Vorlage Button
        RpiMaterialInputTemplate.init();
        $('#template-config-toggle').ready(()=>{
            $('#template-config-toggle').on('click', (e)=>{
                $('#template-config-box').slideToggle();
            });
            $('.interface-interface-skeleton__body').click((e)=>{
                $('#template-config-box').slideUp();
            });
        });


    });

    return fn;
});


( function( window, wp ){

    /**
     * fügt einen zusätzlichen Button "Beitrag ansehen" in die obere Werkzeugleiste des Editors ein
     */

    // just to keep it cleaner - we refer to our link by id for speed of lookup on DOM.
    var link_id = 'template-config-toggle';
    // check if gutenberg's editor root element is present.
    var editorEl = document.getElementById( 'editor' );
    if( !editorEl ){ // do nothing if there's no gutenberg root element on page.
        return;
    }

    var template_box_html = '<div class="custom-toolbar"><button id="template-config-toggle" class="components-button is-tertiary">Vorlage anpassen</button> &nbsp; BUTTONS</div><div id="template-config-box"></div>';

    var unsubscribe = wp.data.subscribe( function () {
        setTimeout( function () {
            if ( !document.getElementById( link_id )  && wp.data.select('core/editor').getCurrentPost().type == rpi_material_input_template.options.post_type ) {

                // prepare our custom link's html.
                var link_html = '<button class="components-button is-tertiary" onclick="location.href=\''+wp.data.select('core/editor').getCurrentPost().link+'\'">Beitrag ansehen</button>';

                template_box_html = template_box_html.replace('BUTTONS', link_html);

                var toolbalEl = editorEl.querySelector( '.edit-post-header__toolbar' );
                if( toolbalEl instanceof HTMLElement ){
                    toolbalEl.insertAdjacentHTML( 'beforeend', template_box_html );
                    var l = jQuery('#template-config-toggle').offset().left;
                    jQuery('#template-config-box').offset({left:l});

                }

            }
        }, 1 )
    } );


    /**
     * create Observer
     * fire Events editorBlocksChanged and editorContentChanged
     */
    wp.domReady(() => {

        const editor = wp.data.select('core/block-editor');
        let blockList = editor.getClientIdsWithDescendants();
        let blockcontent = '';
        wp.data.subscribe(() => {

            if(editor.getSelectedBlock()!==null){
                const currblock = editor.getBlock(
                    editor.getBlockHierarchyRootClientId(editor.getSelectedBlockClientId())
                );

                const newHTML = jQuery('#block-'+currblock.clientId).html();
                const contentChanged = newHTML !== blockcontent;
                blockcontent = newHTML;

                const newBlockList = editor.getClientIdsWithDescendants();
                const blockListChanged = newBlockList !== blockList;
                blockList = newBlockList;


                if (blockListChanged) {
                    jQuery(window).trigger('editorBlocksChanged', [currblock, editor.getBlocks()]);
                }
                if(contentChanged){
                    jQuery(window).trigger('editorContentChanged',[currblock, newHTML]);
                }

            }




        });

    });


} )( window, wp )
