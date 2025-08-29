<?php

    namespace Stanford\GoProd;

    /** @var GoProd $module */

    $name = $module->getJavascriptModuleObjectName();
    $rcpid = $module->getProjectId();
    echo $module->initializeJavascriptModuleObject();
    $rfpresult = $module->query(
        'SELECT * FROM jhu_project_metrics WHERE project_id = ?',
        [
            $rcpid
        ]
    );

    if (!$rfpresult || $rfpresult->num_rows == 0) {
        echo json_encode(['error' => "No project metrics found for project ID: $rcpid"]);

    }
    $row = $rfpresult->fetch_assoc();

    $prjtier = $module->getTierIcon($row['service_tier']) ?? null;
    $prjSupportTeam = $row['primary_support'] ?? null;
    $prjRecCount = $row['record_count'] ?? 0;
    $prjDataCount = $row['datapoint_count'] ?? 0;
    $prjDocCount = $row['doc_count'] ?? 0;
    $prjDocStore = $row['doc_storage_mb'] ?? 0;
    $prjLastWrite = $row['last_logged_write_event'] ?? 0;
    $prjParentPID = $row['parent_pid'] ?? 0;
    $prjClass = $row['project_class'] ?? 1;
    $prjPIDList = $row['pid_list'] ?? $rcpid;

    // init REDCap VUEJS
    //print loadJS('vue/vue-factory/dist/js/app.js');
    print loadJS('vue/components/dist/lib.umd.js'); //above file was missing, I chose the next logical file after a few trials and errors.
    $user = $module->framework->getUser();

?>
<div class="project_info">
    <details>
        <summary>Project Info</summary>
        <table class="project_info_table">
            <tr>
                <th>PID</th>
                <th>Support Team</th>
                <th>Tier</th>
                <th>Title</th>
                <th>PI Name</th>
                <th>PI Email</th>
                <th>IRB Number</th>
                <th>Type</th>
            </tr>
            <tr>
                <td><?php echo !empty($Proj->project['project_id']) ? $Proj->project['project_id'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($prjSupportTeam) ? $prjSupportTeam : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($prjtier) ? $prjtier : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['app_title']) ? $Proj->project['app_title'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['project_pi_firstname']) || !empty($Proj->project['project_pi_lastname']) ? $Proj->project['project_pi_firstname'] . " " . $Proj->project['project_pi_lastname'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['project_pi_email']) ? $Proj->project['project_pi_email'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['project_irb_number']) ? $Proj->project['project_irb_number'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo $module->getClass($prjClass,$prjParentPID,$prjPIDList,$rcpid); ?></td>
            </tr>
        </table>
    </details>
    <details>
    <summary>Project Data Info</summary>
    <table class="project_info_table">
        <tr>
            <th>Record Count</th>
            <th>Datapoint Count</th>
            <th>Document Count</th>
            <th>Document Storage(mb)</th>
            <th>Last Logged Event</th>
            <th>Last Write Event</th>
        </tr>
        <tr>
            <td><?php echo !empty($prjRecCount) ? $prjRecCount : '<span>0</span>'; ?></td>
            <td><?php echo !empty($prjDataCount) ? $prjDataCount : '<span>0</span>'; ?></td>
            <td><?php echo !empty($prjDocCount) ? $prjDocCount : '<span>0</span>'; ?></td>
            <td><?php echo !empty($prjDocStore) ? $prjDocStore.' mb' : '<span>0 mb</span>'; ?></td>
            <td><?php echo !empty($Proj->project['last_logged_event']) ? $Proj->project['last_logged_event'] : '<span class="missing">Missing</span>'; ?></td>
            <td><?php echo !empty($prjLastWrite) ? $prjLastWrite : '<span class="missing">Missing</span>'; ?></td>
        </tr>
    </table>
    </details>
    <details>
        <summary>Project Configuration</summary>
        <table class="project_info_table">
            <tr>
                <th>Data Table</th>
                <th>Log Table</th>
                <th>Language</th>
                <th>Status</th>
                <th>Purpose</th>
                <th>Data Entry Trigger</th>
            </tr>
            <tr>
                <td><?php echo !empty($Proj->project['data_table']) ? $Proj->project['data_table'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['log_event_table']) ? $Proj->project['log_event_table'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo !empty($Proj->project['project_language']) ? $Proj->project['project_language'] : '<span class="missing">Missing</span>'; ?></td>
                <td><?php echo $module->getProjectStatus($Proj->project['project_id']); ?></td>
                <td><?php echo $module->getPurpose($Proj->project['purpose']); ?></td>
                <td><?php echo empty($Proj->project['data_entry_trigger_url']) ? '<span>None</span>' : $Proj->project['data_entry_trigger_url']; ?></td>
            </tr>
        </table>
    </details>
</div>
<style>
    .project_info_table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        margin-bottom: 1em;
    }
    .project_info_table th, .project_info_table td {
        padding: 8px 12px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .project_info_table th {
        background-color: #f2f2f2;
    }
    .project_info_table tr:nth-child(even) {
        background-color: #fafafa;
    }
    .missing {
        color: red;
        font-weight: bold;
    }
    .tier-badge {
        display: inline-flex; /* keep icon + text together */
        align-items: center;
        gap: 0.4em;
        padding: 4px 6px;
        margin: 5px;
        border: 1px solid currentColor; /* matches text color */
        white-space: nowrap; /* replaces invalid nowrap attribute */
        line-height: 1.2; /* avoid tall boxes */
    }
    .tier-badge .fa-medal { /* minimal tweak only */
        font-size: 0.95em;
    }


    /* Theme colors */
    .tier-gold { color: #d4af37; }
    .tier-silver { color: #bdbdbd; }
    .tier-bronze { color: #cd7f32; }
    .tier-none { color: #61f0f5; }
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

    function ShowUserCommentjs(pskey) { // show textarea and save button, hide show button not in use yet, still working out the best way
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
