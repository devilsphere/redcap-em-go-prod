<?php

namespace Stanford\GoProd;

/** @var GoProd $module */

$name = $module->getJavascriptModuleObjectName();


echo $module->initializeJavascriptModuleObject();

// init REDCap VUEJS
//print loadJS('vue/vue-factory/dist/js/app.js');
    print loadJS('vue/components/dist/lib.umd.js');
$user = $module->framework->getUser();
?>
<style>
    .fade-enter-active, .fade-leave-active {
        transition: opacity .5s;
    }

    .fade-enter, .fade-leave-to /* .fade-leave-active below version 2.1.8 */
    {
        opacity: 0;
    }
</style>

<div id="app"></div>
<script>
    window.productionURL = <?php  echo json_encode(APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $module->getProjectId() . '&to_prod_plugin=1')?>;
    window.module = <?=$module->getJavascriptModuleObjectName()?>;
    window.notifications = <?php echo json_encode($module->getNotifications()) ?>;
    window.isSuperUser = <?=$user->isSuperUser()?1:0; ?>;
    window.emprojsettings = <?php echo json_encode($module->getProjectSettings()) ?>;

    function ChangeDateFormat(fldtype,fldnamelist) {
        let payload = {};
        payload.fldtype = fldtype;
        payload.fldnamelist = fldnamelist;
        module.ajax('datechange', payload).then(function(response) {
            var formattedResponse = response.replace(/\\n/g, '\n');
            alert(formattedResponse);

            //window.location.reload();
        }).catch(function(err) {
            alert('error: ' + err);
        });

    }
    function SaveUserCommentjs(pskey) {
        console.log('pskey',pskey);
        let payload = {};
        let psvalue = document.getElementById(pskey).value;
        console.log('psvalue',psvalue);
        payload.pskey = pskey;
        payload.psvalue = psvalue;
        module.ajax('saveusercomment', payload).then(function(response) {
            var formattedResponse = response.replace(/\\n/g, '\n');
            alert(formattedResponse);

        }).catch(function(err) {
            alert('error: ' + err);
        });

    }
    function SaveUserComment2(inputId, value){
        if (typeof value !== 'string') value = String(value ?? '');
        var trimmed = value.trim();
        if (!trimmed) {
            alert('Please enter a comment before saving.');
// Why: guide users back to the field
            try { document.getElementById(inputId)?.focus(); } catch(_){}
            return;
        }
        //console.log('Saving comment for', inputId, '=>', trimmed);
    }
    window.populateUserComments = function populateUserComments(settings){
        //console.log('Populating user comments from settings:', settings);
        if (!settings || typeof settings !== 'object') return;
        var nodes = document.querySelectorAll('.usercomment');
        console.log('nodes',nodes);
        if (!nodes || !nodes.length) return;


// Build id → element map once (why: O(n) instead of O(n*m))
        var byId = Object.create(null);
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            console.log('el',el);
            if (!el || !el.id) continue;
// Only text inputs or textareas
            var tag = (el.tagName || '').toUpperCase();
            var type = (el.type || '').toLowerCase();
            console.log('tag',tag,'type',type);
            if (tag === 'TEXTAREA' || (tag === 'INPUT' && (type === '' || type === 'text'))) {
                byId[el.id] = el;
            }
        }


// Assign values when keys match ids
        var keys = Object.keys(settings);
        for (var k = 0; k < keys.length; k++) {
            var key = keys[k];
            var target = byId[key];
            console.log('key',key,'target',target);
            if (!target) continue;
            var value = settings[key];
            if (value == null) continue;
            target.value = (typeof value === 'string') ? value : String(value);
        }
    };


    // Bind to click on #rclbtn (can run multiple times). No auto-run on load.
    (function bindPopulateOnRclClick(){
        function run(){
            //console.log('run');
// Wait until the page/components have rendered: when at least one .usercomment exists
            var tries = 0, max = 60, delay = 50; // ~3s max wait
            (function tick(){
                var ready = document.readyState === 'complete' || document.readyState === 'interactive';
                var hasTargets = document.querySelector('.usercomment');
                if ((ready && hasTargets) || tries >= max) {
                    if (window.emprojsettings) window.populateUserComments(window.emprojsettings);
                    return;
                }
                tries++;
                setTimeout(tick, delay);
            })();
        }
        var btn = document.getElementById('rclbtn');
        if (btn) {
            btn.addEventListener('click', run); // runs each click
        } else {
// Works even if #rclbtn is injected later (e.g., by Vue)
            document.addEventListener('click', function(e){
                if (e.target && e.target.id === 'rclbtn') run();
            });
        }
    })();

</script>
<script src="<?php echo $module->getUrl("frontend_3/public/js/bundle.js") ?>" defer></script>


