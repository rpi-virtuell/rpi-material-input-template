/**
 * rpi Workflow Object
 * händelt einen Workflow
 * @type {{init: RpiWorkflow.init, onSave: RpiWorkflow.onSave, set: RpiWorkflow.set, workflow: *[], start: RpiWorkflow.start, is_met: (function(*): boolean), setMeta: RpiWorkflow.setMeta, counter: number, addWorkflowStep: RpiWorkflow.addWorkflowStep, do_nothing: RpiWorkflow.do_nothing, is_running: boolean, dialog: (function(*=): {is_open: function(): boolean, btn: *, content: *}), loop: RpiWorkflow.loop, find: ((function(*): (*|undefined))|*), get: (function(*, *): *), getMeta: ((function(*): (boolean|Promise<Animation>|*|boolean))|*), finish: RpiWorkflow.finish}}
 *
 * @author Joachim Happel
 */

/**
 * Workflowsteps initialisieren
 */
jQuery(document).ready(($)=>{


    $(window).on('post_save',(e, post_status)=>{
        RpiWorkflow.onSave(post_status);
    });

    wp.domReady(e=>{


        setTimeout(e=>{
            if(wp.data.select('core/editor').getCurrentPostAttribute('status')=='draft'){

                RpiWorkflow.init();
            }
        },1000)


    });


});
RpiWorkflow ={

    workflow:[],

    /**
     * @parem slug unique name of the worklfow step
     * @param startconditions   array of functions that return bool e.g: [(step)=>!step.percent]
     * @param startfn function  e.g. (step)=>{ RpiWorkflow.dialog('jetzt geht es los'); step.percent=1; }
     * @param endconditions     array of functions that return bool e.g: [(step)=>step.percent > 99]
     * @param endfn function    e.g. (step)=>RpiWorkflow.dialog('Du hast es geschafft')
     * @param type              string wether checked in interval loop or when clicked on SaveButton. Allowed: 'interval','onSaveButton'
     * @param properties        array of properties  eg ['percent']
     */
    addWorkflowStep: function (slug='',
                               type = 'interval',
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
                RpiWorkflow.setMeta(this);
                RpiWorkflow.getWorkflow();
            },
            confirm: function (){
                this.started = true;
            },

            type: type,
            started:false,
            finished:false,
            poperties: properties,
            /**
             *
             * @param dialogBtn
             * @param id
             * @param label
             * @param fn
             *
             * example: wfs.addButton(dialog.btn, 'cancel', 'Weiter arbeiten', tb_remove );
             */
            addButton: function (dialogBtn,id, label, fn){
                id = this.step +'-' + id;
                if(jQuery('#'+id).length===0){
                    var btn = jQuery('<button class="button is_secondary" id="'+id+'">'+label+'</button>');
                    btn.click(fn);
                    btn.insertAfter(dialog.btn);
                }
            }
        };
        this.workflow.push(wfs);
    },

    is_running: false,
    counter: 0,

    init: function (){
        if(!this.is_running){
            window.__RpiWorkflow = setInterval(()=>{
                this.loop('interval');

            },5000);
            setTimeout(e=>RpiWorkflow.getWorkflow(),5200);
            this.is_running =true;
        }

    },
    onSave:function() {
        this.loop('onSaveButton')
    },

    loop: function(type){
        RpiWorkflow.counter ++;

        for(const wfs of this.workflow.filter((wfs)=>wfs.type==type)){

            //console.log('check:',wfs.step);

            if(RpiWorkflow.counter<1){
                wfs.finished = RpiWorkflow.getMeta(wfs);
            }


            if(!wfs.finished && RpiWorkflow.getMeta(wfs)){
                wfs.finished = RpiWorkflow.getMeta(wfs);

                //continue;
            }
            if(wfs.finished){
                continue;
            }



            //check endconditions
            if(this.is_met(wfs.end, wfs.step, 'end')){
                wfs.do_end(wfs);
            }
            //check startconditions
            if(this.is_met(wfs.start, wfs.step, 'start') && !wfs.started){
                wfs.do_start(wfs);
            }
        }
    },

    is_met: function (conditions, step, position){
        var is_met = true;

        if (Array.isArray(conditions)){
            for(const condition of conditions){
                if(condition instanceof Function){
                    if(!condition()){
                        is_met = false;
                    }
                }else{
                    is_met = false;
                    console.log('type error:',step,position, condition.toString() + ' is not an function!' );
                }

            }
            //console.log('result:',step,position,is_met );
        }else {
            console.log('type error:',step,position, conditions.toString() + ' is not an array!' );
            is_met = false;
        }

        //all conditions should be true
        return is_met;
    },

    find:function (slug){
        for(wfs of this.workflow){
            if(wfs.step == slug){
                return(wfs);
            }

        };
    },

    get: function (slug, property){
        let wfs = this.find(slug);
        return wfs[property];
    },
    set: function (slug, property,value){
        let wfs = this.find(slug);
        wfs[property] = value;
    },

    start:function (wfs){
        wfs.do_start(wfs);
    },

    finish: function(wfs){
        if (typeof wfs === 'string' || wfs instanceof String){
            //console.log(wfs, this.workflow);
            wfs = this.find(wfs);
        }
        wfs.do_end(wfs);
    },

    dialog: function (args={title:'',content:'',w:400,h:300,button:'OK',step:null}){
        args = {
            title:args.title    ||'Hilfe' ,
            content:args.content||'',
            w:args.w            ||800,
            h:args.h            ||600,
            button:args.button  ||'OK',
            step:args.step      ||null
        };


        args.content = '<div id="dialog-'+args.step+'" class="dialog-content" style="margin: 19% 27% 0 24%;">'+args.content+'</div>';


        if(jQuery('#dialog-'+args.step).length===0){
            tb_show(args.title, '#TB_inline?width='+args.w+'&height='+args.h);
            jQuery(document).find('#TB_window').width(TB_WIDTH).height(TB_HEIGHT).css('margin-left', - TB_WIDTH / 2);
            jQuery('#TB_ajaxContent').html(args.content);

            jQuery('#TB_ajaxContent').css({
                'background-image':'url(https://test.rpi-virtuell.de/wp-content/plugins/rpi-material-input-template-main/assets/background.png)',
                'background-size': '100%',
                'background-repeat': 'no-repeat',
                'background-position': 'center'
            });

            if(jQuery('#tb_bottom_bar_btn').length === 0){

                jQuery('<div class="tb_bottom_bar"><button id="tb_bottom_bar_btn" class="button is_primary">'+args.button+'</button></div>').insertAfter(jQuery('#TB_ajaxContent'));

                jQuery('#tb_bottom_bar_btn').click((e)=>{

                    if(args.step !== null){
                        RpiWorkflow.finish(args.step);
                    }
                    tb_remove();
                });

            }
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
    getWorkflow: function (){

        this.loop('interval');
        this.loop('onSaveButton');

        let completed = [];
        for(step of this.workflow){
            if(step.finished){
                completed.push(step.step);
            }
        }
        return 'completed: ' + completed.join(', ');
    }



}
