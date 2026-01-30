<?php

    namespace Stanford\GoProd;

    /** @var GoProd $module */
    $goproggo = true;
    $name = $module->getJavascriptModuleObjectName();
    $rcpid = $module->getProjectId();
    echo $module->initializeJavascriptModuleObject();
    $superuserlink = $module->getMoveToProductionSuperUserLink($rcpid);
    \REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'Project Object for '.$rcpid, json_encode($superuserlink));

    $rfpresult = $module->query(
        'SELECT * FROM jhu_project_metrics WHERE project_id = ?',
        [
            $rcpid
        ]
    );
    //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'rfpresult for '.$rcpid, json_encode($rfpresult));
    if (!$rfpresult || $rfpresult->num_rows == 0) {
        echo json_encode(['error' => "No project metrics found for project ID: $rcpid"]);
        $goproggo = false;
    }else{
        $row = $rfpresult->fetch_assoc();
        //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'Project Object for '.$rcpid, json_encode($Proj));
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
    }


    function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

    function normalize_emevents($emevents): array
    {
        if (is_array($emevents)) {
            return $emevents;
        }
        if (is_object($emevents)) {
            return json_decode(json_encode($emevents), true) ?: [];
        }
        if (is_string($emevents)) {
            $decoded = json_decode($emevents, true);
            return (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }
        return [];
    }
    function renderRepeatingFormsByArm($emevents, $module): string
    {
        $data = normalize_emevents($emevents);


// If no arms at all, show None to avoid an empty cell
        if (empty($data)) {
            return '<span class="missing">None</span>';
        }

        $esc = static function ($value): string {
            return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        };


        $armItems = [];


        foreach ($data as $armKey => $arm) {
            $armName = $arm['name'] ?? ('Arm ' . (string) $armKey);
            $events = $arm['events'] ?? [];


            $eventItems = [];
            foreach ($events as $eventId => $eventMeta) {
                $eventId = (int) $eventId;
                $eventLabel = $eventMeta['descrip'] ?? ('Event ' . $eventId);


                $forms = (array) $module->getRepeatingForms($eventId);
                $forms = array_values(array_unique(array_filter($forms, 'strlen')));


                if (empty($forms)) {
                    continue; // skip events with no repeating forms
                }


                $escapedForms = array_map($esc, $forms);


                $eventItems[] = '<li>' . $esc($eventLabel) . ': <ul class="forms"><li>'
                    . implode('</li><li>', $escapedForms) . '</li></ul></li>';
            }


            if (!empty($eventItems)) {
                $armItems[] = '<li><strong>' . $esc($armName) . '</strong><ul class="events">'
                    . implode('', $eventItems) . '</ul></li>';
            }
        }


        if (empty($armItems)) {
// No forms anywhere across arms
            return '<span class="missing">None</span>';
        }


        return '<ul class="arms">' . implode('', $armItems) . '</ul>';
    }


    // init REDCap VUEJS
    //print loadJS('vue/vue-factory/dist/js/app.js');
    print loadJS('vue/components/dist/lib.umd.js'); //above file was missing, I chose the next logical file after a few trials and errors.
    $user = $module->framework->getUser();
if($goproggo === true){ //go logic
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
    <details>
        <summary>Users, Forms, Modules</summary>
        <table class="project_info_table">
            <tr>
                <th>Repeating Forms</th>
                <th>Users</th>
                <th>Public Survey</th>
                <th>Additional Enabled Modules</th>
            </tr>
            <tr>
                <td>
                    <?php
                        }   //end go logic
                        $emevents = $Proj->events;
                        $users = $module->framework->getProject($rcpid)->getUsers();
                        if($goproggo === true){ //go logic
                        echo renderRepeatingFormsByArm($emevents, $module);                    ?>
                </td>
                <td>
                    <?php


                        if (!empty($users)) {
                            echo '<ul style="padding-left:1.2em;">';
                            foreach ($users as $user) {
                                $rights = $user->getRights($Proj->project['project_id']);
                                $username = htmlspecialchars($user->getUsername());
                                $userrole = htmlspecialchars($rights['role_name']) ?? '';
                                $userdagid = htmlspecialchars($rights['group_id']) ?? '';
                                $userDagName = '';
                                if ($userdagid != '') {
                                    $userDagName = \REDCap::getGroupNames(true, $userdagid);
                                }

                                $icons = [];

                                if (!empty($rights['api_token'])) {
                                    $token = htmlspecialchars($rights['api_token']);
                                    $icons[] = '<span title="Token - '.$token.'">📡</span>';
                                }

                                if (!empty($rights['user_rights']) && $rights['user_rights'] == '1') {
                                    $icons[] = '<span title="User Rights">⚙️</span>';
                                }

                                if (!empty($rights['design']) && $rights['design'] == '1') {
                                    $icons[] = '<span title="Design Rights">👷</span>';
                                }

                                if (!empty($rights['api_import']) && $rights['api_import'] == '1') {
                                    $icons[] = '<span title="API Import Rights">📥</span>';
                                }

                                if (!empty($rights['api_export']) && $rights['api_export'] == '1') {
                                    $icons[] = '<span title="API Export Rights">📤</span>';
                                }

                                if (!empty($rights['file_repository']) && $rights['file_repository'] == '1') {
                                    $icons[] = '<span title="File Repo">🗃️</span>';
                                }

                                if ($userrole != '') {
                                    $icons[] = '<span title="User Role" style="color:purple;">[' . $userrole . ']</span>';
                                }

                                if ($userdagid != '') {
                                    $userDagName = \REDCap::getGroupNames(true, $userdagid);
                                    $icons[] = '<span title="Data Access Group" style="color:blue;">[' . htmlspecialchars($userDagName) . ']</span>';
                                }

                                echo '<li>' . $username . ' ' . implode(' ', $icons) . '</li>';
                            }
                            echo '</ul>';

                            // Legend/key for icons + colors
                            echo '
                                    <div style="font-size:0.9em; margin-top:0.5em;">
                                        <strong>Legend:</strong>
                                        <div>📡 = Has API token</div>
                                        <div>⚙️ = User Rights</div>
                                        <div>👷 = Design Rights</div>
                                        <div>📥 = API Import</div>
                                        <div>📤 = API Export</div>
                                        <div>🗃️ = File Repo</div>
                                        <div><span style="color:purple;">[Role]</span> = User Role</div>
                                        <div><span style="color:blue;">[DAG]</span> = Data Access Group</div>
                                    </div>';
                        }
                    ?>
                </td>


                <td><?php echo empty($module->getPublicSurveyUrl($rcpid)) ? '<span>None</span>' : $module->getPublicSurveyUrl($rcpid); ?></td>
                <td>
                    <?php
                        $excludedModules = $module->getSystemwideEnabledModules();  //grab all systemwide enabled modules to exclude from this list
                        $modsenabled = $module->getEnabledModules($rcpid);  // Returns ['prefix' => 'version']
                        if (!empty($modsenabled)) {
                            echo '<ul>';
                            foreach ($modsenabled as $prefix => $version) {
                                if (in_array($prefix, $excludedModules)) {
                                    continue; // Skip this module if in the list above
                                }
                                echo '<li>' . htmlspecialchars($prefix) . ' (' . htmlspecialchars($version) . ')</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<span>None</span>';
                        }
                    ?>

                </td>
            </tr>
        </table>
    </details>
</div>
<?php
    } //end go logic
?>
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

    .arms, .events, .forms { margin: 0.2rem 0 0.2rem 1.1rem; padding: 0; }
    .arms > li { margin-bottom: 0.25rem; }
    .forms { list-style-type: disc; }
    .missing { opacity: 0.75; }

    .tier-gold { color: #d4af37; }
    .tier-silver { color: #bdbdbd; }
    .tier-bronze { color: #cd7f32; }
    .tier-none { color: #61f0f5; }
    .fade-enter-active, .fade-leave-active { transition: opacity .5s; }
    .fade-enter, .fade-leave-to { opacity: 0; }
</style>

<div id="app"></div>
<script>
    window.isSuperUser = <?=$module->isSuperUser()?1:0; ?>;
    window.module = <?=$module->getJavascriptModuleObjectName()?>;
    // TODO:create function to get the production URL based on super user or not
    //begin code to add to function
    if(window.isSuperUser) {
        let superuserlink = <?php echo json_encode($superuserlink); ?>;
        if(superuserlink && superuserlink.length > 0 && superuserlink !== 'No Move to Production request found.') { //if link is found take you to the approval page
            window.productionURL = superuserlink;
        }else { //if link is not found, it will let you create a new request
            window.productionURL = <?php  echo json_encode(APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $module->getProjectId() . '&to_prod_plugin=1')?>;
        }
    } else {    //normal user, just go to the request page to create a new request
        window.productionURL = <?php  echo json_encode(APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $module->getProjectId() . '&to_prod_plugin=1')?>;
    }
    //end code to add to function
    window.notifications = <?php echo json_encode($module->getNotifications()) ?>;
    window.emprojsettings = <?php echo json_encode($module->getProjectSettings()) ?>;


    function ChangeDateFormat(fldtype, fldnamelist, clickedEl) {
        const payload = { fldtype, fldnamelist };

        module.ajax('datechange', payload)
            .then(function (response) {
                const formattedResponse = String(response).replace(/\\n/g, '\n');
                alert(formattedResponse);

                const outerTr = findOuterRowWithReload(clickedEl);
                if (!outerTr) return;

                const reloadBtn =
                    outerTr.querySelector('button.btn.btn-sm.btn-outline-primary.text-center') ||
                    outerTr.querySelector('button.btn-outline-primary') ||
                    outerTr.querySelector('button');

                if (!reloadBtn) return;

                setTimeout(function () {
                    reloadBtn.click();
                }, 0);
            })
            .catch(function (err) {
                alert('error: ' + err);
            });
    }

    /**
     * Walks up through ancestor <tr>s until it finds one that contains the Reload button cell.
     * This handles nested tables inside the first <td>.
     * @param {HTMLElement} el
     * @returns {HTMLTableRowElement|null}
     */
    function findOuterRowWithReload(el) {
        let tr = el && el.closest ? el.closest('tr') : null;

        while (tr) {
            if (tr.querySelector('button.btn.btn-sm.btn-outline-primary.text-center, button.btn-outline-primary')) {
                return tr;
            }
            tr = tr.parentElement ? tr.parentElement.closest('tr') : null;
        }

        return null;
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
