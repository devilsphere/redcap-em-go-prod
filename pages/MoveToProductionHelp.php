<?php
$emprojhomelink = APP_PATH_WEBROOT . 'ProjectSetup/index.php?pid=' . $module->getProjectId();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>When to Move Your REDCap Project to Production | Johns Hopkins</title>
    <meta name="description" content="A practical guide for Johns Hopkins REDCap users on when to move a project from Development to Production, including a readiness checklist and common pitfalls." />

    <style>
        :root{
            /* Lightened theme */
            --bg: #f4f7fb;
            --panel: rgba(255,255,255,.92);
            --panel2: rgba(255,255,255,.98);
            --text: rgba(17, 24, 39, .95);
            --muted: rgba(55, 65, 81, .85);
            --muted2: rgba(75, 85, 99, .75);
            --border: rgba(17, 24, 39, .12);
            --shadow: 0 10px 30px rgba(17,24,39,.08);
            --radius: 18px;

            /* Hopkins-ish accent palette (edit freely) */
            --accent: #5bbad5;         /* light blue */
            --accent2: #f1c40f;        /* gold */
            --ok: #2bbd7e;
            --warn: #f0b429;
            --bad: #e85656;

            --max: 1100px;
            --font: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";
        }

        *{ box-sizing: border-box; }
        html, body{ height: 100%; }
        body{
            margin: 0;
            font-family: var(--font);
            color: var(--text);
            background:
                radial-gradient(900px 480px at 20% 5%, rgba(91,186,213,.18), transparent 55%),
                radial-gradient(900px 480px at 80% 0%, rgba(241,196,15,.12), transparent 55%),
                var(--bg);
            line-height: 1.45;
        }

        a{ color: #0b6fa6; text-decoration: none; }
        a:hover{ text-decoration: underline; }
        .wrap{ max-width: var(--max); margin: 0 auto; padding: 20px; }

        header{
            position: sticky; top: 0; z-index: 30;
            backdrop-filter: blur(10px);
            background: rgba(255,255,255,.80);
            border-bottom: 1px solid var(--border);
        }
        .nav{
            display: flex; align-items: center; gap: 14px;
            justify-content: space-between;
            padding: 14px 0;
        }
        .brand{
            display: flex; gap: 12px; align-items: center;
            min-width: 220px;
        }
        .logo{
            width: 38px; height: 38px; border-radius: 12px;
            background: linear-gradient(135deg, rgba(91,186,213,.95), rgba(241,196,15,.9));
            box-shadow: 0 10px 30px rgba(0,0,0,.10);
            border: 1px solid rgba(17,24,39,.14);
        }
        .brand h1{
            font-size: 14px; margin: 0;
            letter-spacing: .2px;
        }
        .brand p{ margin: 2px 0 0; font-size: 12px; color: var(--muted2); }

        .navlinks{
            display: flex; gap: 14px; flex-wrap: wrap; justify-content: flex-end;
            font-size: 13px;
        }
        .navlinks a{
            padding: 8px 10px; border-radius: 12px;
            border: 1px solid transparent;
            color: rgba(17,24,39,.75);
        }
        .navlinks a:hover{
            border-color: var(--border);
            background: rgba(17,24,39,.04);
            text-decoration: none;
        }

        .hero{
            padding: 22px 0 10px;
        }

        /* NEW: stacked hero layout (full width sections) */
        .heroStack{
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: stretch;
        }

        @media (max-width: 920px){
            .nav{ flex-direction: column; align-items: flex-start; }
            .navlinks{ justify-content: flex-start; }
        }

        .card{
            background: var(--panel2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 18px;
        }
        .card h2{ margin: 0 0 8px; font-size: 24px; line-height: 1.15; }
        .card p{ margin: 8px 0; color: var(--muted); }

        .pillRow{
            display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;
        }
        .pill{
            padding: 7px 10px; border-radius: 999px;
            border: 1px solid var(--border);
            background: rgba(17,24,39,.03);
            font-size: 12px; color: var(--muted);
        }

        .status{
            display: grid; gap: 10px;
            border: 1px solid rgba(91,186,213,.25);
            box-shadow: 0 10px 30px rgba(91,186,213,.10);
        }
        .status .box{
            border-radius: 16px;
            border: 1px solid var(--border);
            background: rgba(17,24,39,.03);
            padding: 12px;
        }
        .status .box strong{
            display: inline-flex; align-items: center; gap: 8px;
            font-size: 13px;
        }
        .dot{
            width: 10px; height: 10px; border-radius: 50%;
            display: inline-block;
        }
        .dot.ok{ background: var(--ok); }
        .dot.warn{ background: var(--warn); }
        .dot.bad{ background: var(--bad); }

        main{ padding: 10px 0 60px; }
        section{ margin-top: 18px; }
        .sectionTitle{
            display: flex; justify-content: space-between; align-items: baseline; gap: 12px;
            margin: 0 0 10px;
        }
        .sectionTitle h3{
            margin: 0;
            font-size: 18px;
            letter-spacing: .2px;
        }
        .sectionTitle span{ color: var(--muted2); font-size: 12px; }

        .grid2{
            display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
        }
        @media (max-width: 920px){ .grid2{ grid-template-columns: 1fr; } }

        ul{ margin: 10px 0 0 18px; color: var(--muted); }
        li{ margin: 6px 0; }

        .callout{
            border-left: 4px solid rgba(91,186,213,.9);
            background: rgba(91,186,213,.10);
            padding: 12px 12px 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(91,186,213,.22);
            color: var(--muted);
        }

        /* Checklist */
        .checklistTop{
            display: flex; gap: 12px; flex-wrap: wrap;
            align-items: center; justify-content: space-between;
            margin-bottom: 10px;
        }
        .progress{
            flex: 1 1 260px;
            border: 1px solid var(--border);
            border-radius: 999px;
            height: 12px;
            background: rgba(17,24,39,.03);
            overflow: hidden;
            min-width: 240px;
        }
        .bar{ height: 100%; width: 0%; background: linear-gradient(90deg, var(--accent), var(--ok)); transition: width .25s ease; }
        .progressLabel{ font-size: 12px; color: var(--muted2); }

        .checklist{
            display: grid; gap: 10px;
            margin-top: 10px;
        }
        .item{
            display: grid;
            grid-template-columns: 22px 1fr;
            gap: 10px;
            padding: 12px;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: rgba(17,24,39,.02);
        }
        .item input{ margin-top: 2px; }
        .item strong{ display: block; font-size: 14px; margin-bottom: 2px; }
        .item .hint{ font-size: 12px; color: var(--muted2); }

        .buttons{
            display: flex; gap: 10px; flex-wrap: wrap;
        }
        button{
            border: 1px solid var(--border);
            background: rgba(17,24,39,.03);
            color: var(--text);
            border-radius: 14px;
            padding: 10px 12px;
            font-size: 13px;
            cursor: pointer;
        }
        button:hover{ background: rgba(17,24,39,.06); }
        button.primary{
            border-color: rgba(91,186,213,.45);
            background: rgba(91,186,213,.14);
        }

        /* Accordion */
        details{
            border: 1px solid var(--border);
            background: rgba(17,24,39,.02);
            border-radius: 16px;
            padding: 12px;
        }
        details + details{ margin-top: 10px; }
        summary{
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }
        details p{ margin: 10px 0 0; color: var(--muted); }

        footer{
            border-top: 1px solid var(--border);
            color: var(--muted2);
            padding: 18px 0;
            font-size: 12px;
        }

        .kbd{
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 8px;
            background: rgba(17,24,39,.05);
            border: 1px solid rgba(17,24,39,.12);
            color: rgba(17,24,39,.85);
            white-space: nowrap;
        }
    </style>
</head>

<body>
<header>
    <div class="wrap">
        <div class="nav" aria-label="Site header">
            <div class="brand">
                <div class="logo" aria-hidden="true"></div>
                <div>
                    <h1>Johns Hopkins • REDCap Guidance</h1>
                    <p>When to move your project from Development to Production</p>
                </div>
            </div>
            <nav class="navlinks" aria-label="Page sections">
                <a href="#checklist">Readiness checklist</a>
                <a href="#what-changes">What changes in Production?</a>
                <a href="#faq">FAQ</a>
                <a href="#help">Get help</a>
            </nav>
        </div>
    </div>
</header>

<!-- UPDATED HERO: decision guide first, full width; then move-to-production card, full width -->
<div class="wrap hero">
    <div class="heroStack">
        <div class="card status" id="decision">
            <div class="sectionTitle">
                <h3>Quick decision guide</h3>
            </div>

            <div class="box">
                <strong><span class="dot ok" aria-hidden="true"></span> You’re ready to request Production</strong>
                <ul>
                    <li>Study workflow is finalized and piloted with test records.</li>
                    <li>PI and IRB information has been updated and completed.</li>
                    <li>Approvals are in place (e.g., IRB determination/approval when required).</li>
                    <li>User roles, exports, and identifiers are configured appropriately.</li>
                </ul>
            </div>

            <div class="box">
                <strong><span class="dot warn" aria-hidden="true"></span> Wait a bit longer if…</strong>
                <ul>
                    <li>You still expect frequent instrument edits (field names/types/options).</li>
                    <li>Branching logic, calculations, or survey logic hasn’t been thoroughly tested.<br> It is recommended that you open each page with a test record to ensure your intended workflow.</li>
                    <li>You’re unsure whether you will store PHI or need eConsent / integrations.</li>
                </ul>
            </div>

            <div class="box">
                <strong><span class="dot bad" aria-hidden="true"></span><span class="bad">Do not collect real data in Development</span></strong>
                <ul>
                    <li>Development is for building/testing—protect yourself from avoidable rework and risk.</li>
                    <li>If you must pilot with real participants, talk to your REDCap support team first.</li>
                </ul>
            </div>
        </div>

        <div class="card">
            <h2>Move to Production when you’re ready to collect real data.</h2>
            <p>
                All REDCap projects start in <strong>Development</strong> while you build and test.
                Before you begin actual enrollment, surveys, or data collection, your project should be moved
                to <strong>Production</strong> so changes are tracked, safeguarded, and auditable.
            </p>
            <div class="callout" role="note">
                <strong>Tip:</strong> Treat Development like a sandbox. Enter realistic <em>test</em> records, run through the workflow,
                and confirm every instrument behaves exactly as intended.
            </div>
            <div class="pillRow" aria-label="Key topics">
                <span class="pill">IRB / approvals</span>
                <span class="pill">PHI / identifiers</span>
                <span class="pill">Surveys & invitations</span>
                <span class="pill">User rights & roles</span>
                <span class="pill">Audit trails</span>
            </div>
        </div>
    </div>
</div>

<main class="wrap">
    <section class="grid2" aria-label="Overview cards">
        <div class="card">
            <div class="sectionTitle">
                <h3>Why Production matters</h3>
                <span>Stability + protection</span>
            </div>
            <ul>
                <li><strong>Safer changes:</strong> Production changes are reviewed and summarized to reduce accidental data loss.</li>
                <li><strong>Traceability:</strong> Production-ready projects align with audit trail expectations.</li>
                <li><strong>Team readiness:</strong> You finalize roles, exports, and access boundaries before going live.</li>
            </ul>
        </div>

        <div class="card">
            <div class="sectionTitle">
                <h3>Common “oops” moments</h3>
                <span>What to avoid</span>
            </div>
            <ul>
                <li>Editing choice codes after data exists (can break analysis and imports).</li>
                <li>Changing field types after data exists (risk of data truncation/corruption).</li>
                <li>Launching surveys before testing invitations, reminders, and stop logic.</li>
                <li>Granting broad export rights when PHI/PII is present.</li>
            </ul>
        </div>
    </section>

    <section id="checklist" class="card" aria-label="Readiness checklist">
        <div class="sectionTitle">
            <h3>Production readiness checklist</h3>
            <span>Check items off to estimate readiness</span>
        </div>

        <div class="checklistTop">
            <div style="flex: 1 1 360px; min-width: 280px;">
                <div class="progress" aria-label="Checklist completion">
                    <div class="bar" id="bar"></div>
                </div>
                <div class="progressLabel" id="progressLabel">0% complete</div>
            </div>
            <div class="buttons">
                <button class="primary" type="button" id="printBtn" title="Print or save as PDF">Print / Save PDF</button>
                <button type="button" id="resetBtn" title="Clear all checkboxes">Reset</button>
            </div>
        </div>

        <div class="checklist" id="checklistItems">
            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>I’ve completed end-to-end testing with realistic test records.</strong>
                    <div class="hint">Walk through every instrument, every event/arm (if longitudinal), and every survey path.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>Approvals/determinations are in place (e.g., IRB when required).</strong>
                    <div class="hint">Confirm your protocol/determination covers REDCap data capture, eConsent, integrations, and sharing plans.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>My instruments are “analysis-ready.”</strong>
                    <div class="hint">Field names, variable types, response choices/codes, and validations are finalized.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>Identifier/PHI handling is intentional and reviewed.</strong>
                    <div class="hint">Know which fields contain identifiers; ensure export rights match sensitivity.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>User roles and permissions are set for least privilege.</strong>
                    <div class="hint">Limit “Full Data Set” exports to users who truly need it; review reports/dashboards visibility too.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>Survey settings are finalized (public link vs. invitations, schedules, stop rules).</strong>
                    <div class="hint">Test reminders, conditional invitations, and logic that stops repeat submissions.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>I’ve tested data exports and downstream workflows.</strong>
                    <div class="hint">Validate exports to Excel/SPSS/SAS/Stata/R and any ETL/import workflows you plan to use.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>Multi-site setup is ready (if applicable).</strong>
                    <div class="hint">If using Data Access Groups (DAGs), verify each site can only view its own data.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>I’ve cleaned up test artifacts.</strong>
                    <div class="hint">Decide whether to delete test records before Production, and document what you’re keeping and why.</div>
                </div>
            </label>

            <label class="item">
                <input type="checkbox" />
                <div>
                    <strong>I know how Production changes will be handled after launch.</strong>
                    <div class="hint">Plan for versioned changes, field retirement, and analysis impact (especially after data exists).</div>
                </div>
            </label>
        </div>

        <div class="callout" style="margin-top: 12px;">
            <strong>Rule of thumb:</strong> If you still need to change field names, choice codes, or instrument structure frequently,
            keep building in Development until the design stabilizes.
        </div>
    </section>

    <section id="what-changes" class="grid2" aria-label="What changes in Production">
        <div class="card">
            <div class="sectionTitle">
                <h3>What changes in Production?</h3>
                <span>Operational differences</span>
            </div>
            <ul>
                <li><strong>Change safeguards:</strong> REDCap helps flag “risky” edits after data exists.</li>
                <li><strong>Better governance:</strong> Teams typically tighten user rights, exports, and data access boundaries.</li>
                <li><strong>Go-live mindset:</strong> Your project is treated as a live data system, not a draft.</li>
            </ul>
        </div>

        <div class="card">
            <div class="sectionTitle">
                <h3>How to request Production</h3>
                <span>Typical steps</span>
            </div>
            <ol style="margin: 10px 0 0 18px; color: var(--muted);">
                <li>Open your project and go to <span class="kbd">Project Setup</span>.</li>
                <li>Scroll to the bottom and select <span class="kbd">Move project to production</span>.</li>
                <li>Read the notice carefully and confirm test-data handling.</li>
                <li>Follow any local prompts/forms required by your JHU REDCap environment.</li>
            </ol>
            <p style="margin-top: 10px; color: var(--muted2); font-size: 12px;">
                Your local process may include an administrative review depending on project risk and configuration.
            </p>
        </div>
    </section>

    <section id="faq" class="card" aria-label="Frequently asked questions">
        <div class="sectionTitle">
            <h3>FAQ</h3>
            <span>Common questions from Hopkins study teams</span>
        </div>

        <details>
            <summary>Can I keep some test records when I move to Production?</summary>
            <p>
                Often yes, but it depends on how you’re using the test data. If test records will confuse staff or reports,
                delete them before launch. If you keep them (e.g., for demonstrations), label them clearly and restrict access.
            </p>
        </details>

        <details>
            <summary>What if I realize I need a new field after going live?</summary>
            <p>
                Adding a new field is usually low risk. The highest-risk changes are deleting fields, changing field types,
                or changing multiple-choice codes after data exists. Plan for “versioned” edits and document what changed.
            </p>
        </details>

        <details>
            <summary>My project includes PHI/PII—anything special?</summary>
            <p>
                Yes: confirm identifier tagging and export permissions match your study needs. Ensure only appropriate roles
                can export identifiers. If you’re unsure about governance expectations, contact your REDCap support team.
            </p>
        </details>

        <details>
            <summary>Is REDCap approved/appropriate at Johns Hopkins?</summary>
            <p>
                ICTR describes REDCap as a secure, HIPAA-compliant application with audit trails and granular access controls,
                and notes JH IRB approval as a feature. (Always ensure your specific protocol/determination covers your use case.)
            </p>
        </details>
    </section>

    <section id="help" class="grid2" aria-label="Help and links">
        <div class="card">
            <div class="sectionTitle">
                <h3>Get help</h3>
                <span>Talk to a human</span>
            </div>
            <p style="margin-top: 0; color: var(--muted);">
                If you want a quick review of your “go-live” plan (permissions, identifiers, survey workflow, multi-site setup),
                reach out before you collect real data.
            </p>
            <ul>
                <li><strong>Email:</strong> <a href="mailto:redcap@jhu.edu">redcap@jhu.edu</a></li>
                <li><strong>REDCap portal:</strong> <a href="https://redcap.jhu.edu/" target="_blank" rel="noopener">redcap.jhu.edu</a></li>
            </ul>
            <p style="margin-top: 10px; color: var(--muted2); font-size: 12px;">
                Replace or expand these contacts to match your local support model (e.g., ticketing link, office hours).
            </p>
        </div>

        <div class="card">
            <div class="sectionTitle">
                <h3>Helpful links</h3>
                <span>Official resources</span>
            </div>
            <ul>
                <li><a href="https://ictr.johnshopkins.edu/service/informatics/redcap/" target="_blank" rel="noopener">ICTR: Research Electronic Data Capture (REDCap)</a></li>
                <li><a href="https://projectredcap.org/resources/videos/" target="_blank" rel="noopener">REDCap video tutorials</a></li>
                <li><a href="https://ictr.johnshopkins.edu/" target="_blank" rel="noopener">Institute for Clinical & Translational Research (ICTR)</a></li>
            </ul>
            <div class="callout" style="margin-top: 12px;">
                <strong>Optional:</strong> Add your local IRB guidance, data classification links, or “secure storage” recommendations here.
            </div>
        </div>
    </section>

    <footer>
        <div class="wrap" style="padding-left:0;padding-right:0;">
            <div>
                <strong>Site note:</strong> This page is a template intended for Johns Hopkins audiences. Customize language, governance requirements,
                and contacts to match your institutional process.
            </div>
            <div style="margin-top:8px;">
                Last updated: <span id="updated"></span>
            </div>
        </div>
    </footer>
</main>

<script>
    (function(){
        const updated = document.getElementById('updated');
        updated.textContent = new Date().toLocaleDateString(undefined, { year:'numeric', month:'long', day:'numeric' });

        const checklist = document.getElementById('checklistItems');
        const boxes = Array.from(checklist.querySelectorAll('input[type="checkbox"]'));
        const bar = document.getElementById('bar');
        const label = document.getElementById('progressLabel');
        const resetBtn = document.getElementById('resetBtn');
        const printBtn = document.getElementById('printBtn');

        // Persist checkbox states in localStorage
        const KEY = 'jhu_redcap_mtp_checklist_v1';
        const saved = JSON.parse(localStorage.getItem(KEY) || '[]');
        boxes.forEach((b, i) => { if (saved[i] === true) b.checked = true; });

        function update(){
            const total = boxes.length;
            const done = boxes.filter(b => b.checked).length;
            const pct = total ? Math.round((done/total)*100) : 0;
            bar.style.width = pct + '%';
            label.textContent = pct + '% complete (' + done + ' of ' + total + ')';

            const state = boxes.map(b => !!b.checked);
            localStorage.setItem(KEY, JSON.stringify(state));
        }

        boxes.forEach(b => b.addEventListener('change', update));
        resetBtn.addEventListener('click', () => {
            boxes.forEach(b => b.checked = false);
            update();
        });
        printBtn.addEventListener('click', () => window.print());

        update();
    })();
</script>
</body>
</html>
