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
    .fade-enter-active, .fade-leave-active { transition: opacity .5s; }
    .fade-enter, .fade-leave-to { opacity: 0; }
</style>

<div id="app"></div>
<script>
    window.productionURL = <?php  echo json_encode(APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $module->getProjectId() . '&to_prod_plugin=1')?>;
    window.module = <?=$module->getJavascriptModuleObjectName()?>;
    window.notifications = <?php echo json_encode($module->getNotifications()) ?>;
    window.isSuperUser = <?=$user->isSuperUser()?1:0; ?>;
    window.emprojsettings = <?php echo json_encode($module->getProjectSettings()) ?>;

    function ChangeDateFormat(fldtype,fldnamelist) {
        let payload = { fldtype, fldnamelist };
        module.ajax('datechange', payload).then(function(response) {
            var formattedResponse = response.replace(/\\n/g, '\n');
            alert(formattedResponse);
        }).catch(function(err) { alert('error: ' + err); });
    }

    function SaveUserCommentjs(pskey) {
        let payload = {}; let psvalue = document.getElementById(pskey).value;
        payload.pskey = pskey; payload.psvalue = psvalue;
        module.ajax('saveusercomment', payload).then(function(response) {
            var formattedResponse = response.replace(/\\n/g, '\n');
            alert(formattedResponse);
        }).catch(function(err) { alert('error: ' + err); });
    }

    function ShowUserCommentjs(pskey) {
        let savebtnname = pskey + "_save";
        let showbtnname = pskey + "_addcomment";
        let savebtn = document.getElementById(savebtnname);
        if (savebtn) savebtn.style.display = 'block';
        let showbtn = document.getElementById(showbtnname);
        if (showbtn) savebtn.style.display = 'none';
        let textbox = document.getElementById(pskey);
        if (textbox) { textbox.style.display = 'block'; textbox.focus(); }
    }

    // --- Sorting helpers (WHY: table is created only after populateUserComments runs) ---
    var __ISSUE_ORDER__ = { danger: 0, warning: 1, info: 2 };

    function __findIssuesTable() {
        return document.querySelector('table.table.table-striped');
    }

    function __typeColIndex(table) {
        var thead = table.tHead || table.querySelector('thead');
        if (!thead) return 1;
        var ths = Array.prototype.slice.call(thead.querySelectorAll('th'));
        for (var i = 0; i < ths.length; i++) {
            var txt = (ths[i].textContent || '').trim().toLowerCase();
            if (txt.indexOf('type') !== -1) return i;
        }
        return 1; // fallback
    }

    function __normalizeType(cell) {
        var node = cell ? (cell.querySelector('[class*="badge-"]') || cell) : null;
        var txt = (node && node.textContent ? node.textContent : '').trim().toLowerCase();
        var m = txt.match(/danger|warning|info/);
        return m ? m[0] : '';
    }

    function sortIssuesTable() {
        var table = __findIssuesTable();
        if (!table) return false;
        var tbody = table.tBodies[0] || table.querySelector('tbody');
        if (!tbody) return false;
        var idx = __typeColIndex(table);
        var rows = Array.prototype.filter.call(tbody.rows, function(tr){ return tr && tr.parentElement === tbody; });
        var decorated = rows.map(function(tr, i){
            var t = __normalizeType(tr.cells[idx]);
            var rank = Object.prototype.hasOwnProperty.call(__ISSUE_ORDER__, t) ? __ISSUE_ORDER__[t] : 3;
            return { tr: tr, rank: rank, i: i };
        });
        decorated.sort(function(a,b){ return (a.rank - b.rank) || (a.i - b.i); });
        var frag = document.createDocumentFragment();
        decorated.forEach(function(d){ frag.appendChild(d.tr); });
        tbody.appendChild(frag);
        return true;
    }

    function waitAndSortIssuesTable(opts) {
        opts = opts || {}; var timeout = typeof opts.timeout === 'number' ? opts.timeout : 5000;
        var start = Date.now();
        return new Promise(function(resolve){
            if (sortIssuesTable()) return resolve(true);
            var mo = new MutationObserver(function(){ if (sortIssuesTable()) { mo.disconnect(); resolve(true); } });
            mo.observe(document, { childList: true, subtree: true });
            var timer = setInterval(function(){
                if (Date.now() - start >= timeout) { clearInterval(timer); mo.disconnect(); resolve(sortIssuesTable()); }
                else sortIssuesTable();
            }, 120);
        });
    }

    window.sortIssuesTable = sortIssuesTable; // manual re-run if needed

    window.populateUserComments = function populateUserComments(settings){
        if (!settings || typeof settings !== 'object') return;
        var nodes = document.querySelectorAll('.usercomment');
        if (!nodes || !nodes.length) return;

        var byId = Object.create(null);
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (!el || !el.id) continue;
            var tag = (el.tagName || '').toUpperCase();
            var type = (el.type || '').toLowerCase();
            if (tag === 'TEXTAREA' || (tag === 'INPUT' && (type === '' || type === 'text'))) {
                byId[el.id] = el;
            }
        }

        var keys = Object.keys(settings);
        for (var k = 0; k < keys.length; k++) {
            var key = keys[k];
            var target = byId[key];
            if (!target) continue;
            var value = settings[key];
            if (value == null) continue;
            target.value = (typeof value === 'string') ? value : String(value);
        }

        // IMPORTANT: Sorting is triggered here because rows are only complete after comments populate.
        Promise.resolve().then(function(){ waitAndSortIssuesTable({ timeout: 6000 }); });
    };

    // Bind to click on #rclbtn (can run multiple times). No auto-run on load.
    (function bindPopulateOnRclClick(){
        function run(){
            var tries = 0, max = 60, delay = 50; // ~3s max wait
            (function tick(){
                var ready = document.readyState === 'complete' || document.readyState === 'interactive';
                var hasTargets = document.querySelector('.usercomment');
                if ((ready && hasTargets) || tries >= max) {
                    if (window.emprojsettings) window.populateUserComments(window.emprojsettings);
                    return;
                }
                tries++; setTimeout(tick, delay);
            })();
        }
        var btn = document.getElementById('rclbtn');
        if (btn) { btn.addEventListener('click', run); }
        else {
            document.addEventListener('click', function(e){ if (e.target && e.target.id === 'rclbtn') run(); });
        }
    })();

    // Fallback: if table arrives without manual trigger, sort once the page settles
    window.addEventListener('load', function(){ waitAndSortIssuesTable({ timeout: 6000 }); });
</script>
<script src="<?php echo $module->getUrl("frontend_3/public/js/bundle.js") ?>" defer></script>
