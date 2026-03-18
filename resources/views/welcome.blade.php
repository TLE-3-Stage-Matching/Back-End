<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation – {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 1.5rem;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #1b1b18;
            background: #fdfdfc;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #ededec; }
        }
        .api-docs {
            max-width: 72rem;
            margin: 0 auto;
        }
        .api-docs h1 { font-size: 1.75rem; margin: 0 0 0.5rem; font-weight: 600; color: #111827; }
        .api-docs h2 { font-size: 1.35rem; margin: 2rem 0 0.75rem; padding-bottom: 0.25rem; border-bottom: 1px solid #e3e3e0; font-weight: 600; color: #111827; }
        .api-docs h3 { font-size: 1.1rem; margin: 1.5rem 0 0.5rem; font-weight: 600; color: #111827; }
        @media (prefers-color-scheme: dark) {
            .api-docs h2 { border-bottom-color: #3e3e3a; }
            .api-docs h1, .api-docs h2, .api-docs h3 { color: #f9fafb; }
        }
        .api-docs p { margin: 0 0 0.75rem; }
        .api-docs ul, .api-docs ol { margin: 0 0 1rem; padding-left: 1.5rem; }
        .api-docs li { margin-bottom: 0.25rem; }
        .api-docs a { color: #c53030; text-decoration: none; }
        .api-docs a:hover { text-decoration: underline; }
        @media (prefers-color-scheme: dark) {
            .api-docs a { color: #f87171; }
        }
        .api-docs hr { border: none; border-top: 1px solid #e3e3e0; margin: 2rem 0; }
        @media (prefers-color-scheme: dark) {
            .api-docs hr { border-top-color: #3e3e3a; }
        }
        .api-docs code {
            font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, monospace;
            font-size: 0.9em;
            padding: 0.15em 0.4em;
            border-radius: 4px;
            background: #eee;
            color: #1b1b18;
        }
        @media (prefers-color-scheme: dark) {
            .api-docs code { background: #2d2d2d; color: #e5e5e5; }
        }
        .api-docs pre {
            margin: 0 0 1rem;
            padding: 1rem;
            overflow-x: auto;
            border-radius: 6px;
            background: #1b1b18;
            color: #e5e5e5;
        }
        .api-docs pre code { padding: 0; background: none; color: inherit; font-size: 0.85rem; }
        @media (prefers-color-scheme: dark) {
            .api-docs pre { background: #161615; }
        }
        .api-docs table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1rem;
            font-size: 0.9rem;
        }
        .api-docs th, .api-docs td {
            border: 1px solid #e3e3e0;
            padding: 0.5rem 0.75rem;
            text-align: left;
        }
        .api-docs th { background: #f5f5f4; font-weight: 600; }
        @media (prefers-color-scheme: dark) {
            .api-docs th, .api-docs td { border-color: #3e3e3a; }
            .api-docs th { background: #262626; }
        }
        .api-docs details { margin: 0.5rem 0 1rem; }
        .api-docs summary {
            cursor: pointer;
            font-weight: 500;
            padding: 0.35rem 0;
            user-select: none;
        }
        .api-docs summary:hover { opacity: 0.9; }
        .api-docs blockquote {
            margin: 0 0 1rem;
            padding-left: 1rem;
            border-left: 4px solid #e3e3e0;
            color: #666;
        }
        @media (prefers-color-scheme: dark) {
            .api-docs blockquote { border-left-color: #3e3e3a; color: #a1a09a; }
        }
        #sidebar-nav {
            position: sticky;
            top: 1.5rem;
            max-height: calc(100vh - 3rem);
            display: flex;
            flex-direction: column;
        }
        #sidebar-list {
            max-height: calc(100vh - 5rem);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d4d4d4 #f5f5f4;
        }
        #sidebar-list::-webkit-scrollbar {
            width: 8px;
        }
        #sidebar-list::-webkit-scrollbar-track {
            background: #f5f5f4;
            border-radius: 4px;
        }
        #sidebar-list::-webkit-scrollbar-thumb {
            background: #d4d4d4;
            border-radius: 4px;
        }
        #sidebar-list::-webkit-scrollbar-thumb:hover {
            background: #a3a3a3;
        }
        @media (prefers-color-scheme: dark) {
            #sidebar-list {
                scrollbar-color: #525252 #262626;
            }
            #sidebar-list::-webkit-scrollbar-track { background: #262626; }
            #sidebar-list::-webkit-scrollbar-thumb { background: #525252; }
            #sidebar-list::-webkit-scrollbar-thumb:hover { background: #737373; }
        }
        #sidebar-list a.sidebar-active {
            font-weight: 600;
            background: #f5f5f4;
            border-radius: 4px;
            padding-left: 6px !important;
        }
        @media (prefers-color-scheme: dark) {
            #sidebar-list a.sidebar-active { background: #262626; }
        }
        #sidebar-list .sidebar-section-toggle {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            width: 100%;
            padding: 3px 0;
            margin: 0;
            border: none;
            background: none;
            font: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            color: inherit;
            cursor: pointer;
            text-align: left;
        }
        #sidebar-list .sidebar-section-toggle:hover { opacity: 0.9; }
        #sidebar-list .sidebar-section-toggle .sidebar-chevron {
            font-size: 0.7rem;
            opacity: 0.8;
            transition: transform 0.15s ease;
        }
        #sidebar-list .sidebar-section-collapsed .sidebar-chevron {
            transform: rotate(-90deg);
        }
        #sidebar-list .sidebar-section-children {
            list-style: none;
            padding-left: 0;
            margin: 0 0 4px 0;
        }
        #sidebar-list .sidebar-section-collapsed .sidebar-section-children {
            display: none;
        }
        @media (max-width: 900px) {
            #api-docs {
                flex-direction: column;
            }
            #sidebar-nav {
                position: relative;
                top: auto;
                max-height: none;
                width: 100%;
                margin-bottom: 1rem;
                border-right: none;
                border-bottom: 1px solid #e3e3e0;
            }
            #sidebar-list { max-height: none; }
        }
        #docs-search-input {
            color: #111827;
            border-color: #d4d4d4;
            background: #ffffff;
        }
        #docs-search-input::placeholder {
            color: #4b5563;
        }
        #docs-search-results {
            background: #fff;
            color: #111827;
        }
        #docs-search-results .search-result-item {
            color: #111827 !important;
            background: #fff;
        }
        #docs-search-results .search-result-item span {
            color: #111827 !important;
        }
        #docs-search-results .search-no-matches {
            color: #111827 !important;
        }
        #docs-search-results * {
            color: #111827 !important;
            -webkit-text-fill-color: #111827;
        }
        #back-to-top {
            opacity: 0;
            pointer-events: none;
        }
        #back-to-top.visible {
            opacity: 1;
            pointer-events: auto;
        }
        @media (prefers-color-scheme: dark) {
            #back-to-top {
                border-color: #404040;
                background: #262626;
                color: #f9fafb;
            }
            #docs-search-input {
                border-color: #404040;
                background: #262626;
                color: #f9fafb;
            }
            #docs-search-input::placeholder {
                color: #9ca3af;
            }
            #docs-search-results {
                background: #262626;
                border-color: #404040;
                color: #f9fafb;
            }
            #docs-search-results .search-result-item {
                color: #f9fafb;
                background: #262626;
                border-color: #404040;
            }
            #docs-search-results .search-result-item span {
                color: #f9fafb !important;
            }
            #docs-search-results .search-no-matches {
                color: #f9fafb !important;
            }
            #docs-search-results .search-result-item:hover {
                background: #363636;
            }
            #docs-search-results * {
                color: #f9fafb !important;
                -webkit-text-fill-color: #f9fafb;
            }
        }
    </style>
</head>
<body>
    <div id="api-docs" class="api-docs" style="display:flex; gap:1.5rem; align-items:flex-start;">
        <aside id="sidebar-nav" style="width:220px; padding-right:0.75rem; border-right:1px solid #e5e5e0; font-size:0.85rem; display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem; flex-shrink:0;">
                <strong style="font-size:0.9rem;">Sections</strong>
                <div style="display:inline-flex; gap:0.25rem; background:#f5f5f4; border-radius:999px; padding:0.1rem;">
                    <button id="sidebar-tab-v1" type="button" style="border:none; background:transparent; color:#111827; border-radius:999px; padding:0.1rem 0.55rem; font-size:0.8rem; cursor:pointer;">v1</button>
                    <button id="sidebar-tab-v2" type="button" style="border:none; background:#111827; color:#f9fafb; border-radius:999px; padding:0.1rem 0.55rem; font-size:0.8rem; cursor:pointer;">v2</button>
                </div>
            </div>
            <nav id="sidebar-list" style="padding-right:0.25rem;">
                <ul style="list-style:none; padding-left:0; margin:0;"></ul>
            </nav>
        </aside>

        <div style="flex:1; min-width:0;">
        <div id="api-header" style="margin-bottom: 1.5rem;">
            <h1 style="margin-bottom: 0.25rem;">API Documentation</h1>
            <h2 style="margin: 0.5rem 0 0.25rem; font-size:1rem;">API key details</h2>
            <p style="margin: 0 0 0.5rem; color: #525252; font-size:0.9rem;">
                <strong>v1</strong>: JWT-only, no API key required. <strong>v2</strong>: requires an API key (<code>X-API-KEY</code>) and, for protected routes, a JWT Bearer token.
            </p>
            <div id="dev-auth" style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; margin-bottom: 0.75rem;">
                <span id="dev-status" style="font-size: 0.9rem;">Dev: not logged in</span>
                <button id="dev-login-button" type="button" style="padding: 0.25rem 0.75rem; font-size: 0.9rem; border-radius: 999px; border: 1px solid #d4d4d4; background: #f5f5f4; cursor: pointer;">
                    Log in as dev
                </button>
                <button id="dev-logout-button" type="button" style="display:none; padding: 0.25rem 0.75rem; font-size: 0.9rem; border-radius: 999px; border: 1px solid #d4d4d4; background: #f5f5f4; cursor: pointer;">
                    Log out
                </button>
                <button id="generate-key-button" type="button" style="padding: 0.25rem 0.75rem; font-size: 0.9rem; border-radius: 999px; border: none; background: #b91c1c; color: #fef2f2; cursor: pointer;">
                    Generate v2 API key
                </button>
                <span id="dev-key-display" style="display:none; font-size:0.85rem; font-family:ui-monospace, Menlo, monospace; padding:0.1rem 0.5rem; border-radius:999px; border:1px solid #e5e5e5; background:#fafafa; color:#111827;"></span>
            </div>
            <p style="margin: 0; font-size: 0.85rem; color: #737373;">
                To use <strong>v2</strong>: 1) Log in as dev, 2) Generate an API key, 3) Store it safely, 4) Send it with every <code>X-API-KEY</code> header on <code>/api/v2</code> calls.
            </p>
        </div>
        <div style="margin-top: 0.75rem; margin-bottom: 0.5rem; display:flex; flex-wrap:wrap; align-items:center; gap:0.75rem;">
            <div style="position:relative; flex:1; min-width:200px; max-width:320px;">
                <input id="docs-search-input" type="text" placeholder="Search endpoints, paths, sections…" aria-label="Search documentation" style="width:100%; padding:0.4rem 0.7rem; padding-right:2rem; font-size:0.9rem; border-radius:0.375rem; border:1px solid #d4d4d4; background:#fff; color:#111827;">
                <div id="docs-search-results" style="display:none; position:absolute; top:100%; left:0; right:0; margin-top:2px; max-height:280px; overflow-y:auto; background:#fff; border:1px solid #e5e5e5; border-radius:0.375rem; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1); z-index:30;"></div>
            </div>
            <div id="api-version-tabs" style="display:flex; gap:0.5rem;">
                <button id="tab-v1" type="button" style="padding:0.25rem 0.75rem; font-size:0.9rem; border-radius:999px; border:1px solid #e5e5e5; background:#f5f5f4; color:#111827; cursor:pointer;">
                    v1 documentation
                </button>
                <button id="tab-v2" type="button" style="padding:0.25rem 0.75rem; font-size:0.9rem; border-radius:999px; border:1px solid #e5e5e5; background:#111827; color:#f9fafb; cursor:pointer;">
                    v2 documentation
                </button>
                <button id="tab-api-keys" type="button" style="display:none; padding:0.25rem 0.75rem; font-size:0.9rem; border-radius:999px; border:1px solid #e5e5e5; background:#f5f5f4; color:#111827; cursor:pointer;">
                    API key management
                </button>
            </div>
        </div>
        <div id="api-docs-markdown"></div>
        <div id="api-keys-management-panel" style="display:none;">
            <h2 style="margin:0 0 1rem; font-size:1.35rem;">API key management</h2>
            <p id="api-keys-admin-message" style="margin:0 0 1rem; font-size:0.9rem; color:#525252;"></p>
            <div id="api-keys-table-wrap" style="overflow-x:auto;">
                <table id="api-keys-table" class="api-docs" style="width:100%; border-collapse:collapse; margin:0;">
                    <thead>
                        <tr>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Name</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Key preview</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Owner</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Status</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Last used</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Created</th>
                            <th style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; text-align:left;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="api-keys-tbody"></tbody>
                </table>
            </div>
            <p id="api-keys-empty" style="display:none; margin:1rem 0 0; font-size:0.9rem; color:#737373;">No API keys found.</p>
        </div>
        </div>
    </div>

    <button id="back-to-top" type="button" aria-label="Back to top" style="position:fixed; bottom:1.5rem; right:1.5rem; width:2.5rem; height:2.5rem; border-radius:999px; border:1px solid #d4d4d4; background:#f5f5f4; color:#1f2933; font-size:1.1rem; cursor:pointer; transition:opacity 0.2s ease, transform 0.2s ease; z-index:40; box-shadow:0 1px 3px rgba(0,0,0,0.12);">&#8593;</button>

    <div id="dev-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:40; align-items:center; justify-content:center;">
        <div id="dev-modal" style="background:#fff; color:#111827; padding:1.25rem 1.5rem; border-radius:0.75rem; width:100%; max-width:22rem; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);">
            <h2 style="margin:0 0 0.75rem; font-size:1.1rem;">Dev login</h2>
            <p style="margin:0 0 0.75rem; font-size:0.9rem; color:#4b5563;">Use a user with role <code>dev</code> to manage v2 API keys.</p>
            <form id="dev-login-form" style="display:flex; flex-direction:column; gap:0.5rem;">
                <label style="font-size:0.85rem;">
                    Email
                    <input id="dev-email" type="email" required style="margin-top:0.15rem; width:100%; padding:0.35rem 0.5rem; border-radius:0.375rem; border:1px solid #d4d4d8;">
                </label>
                <label style="font-size:0.85rem;">
                    Password
                    <input id="dev-password" type="password" required style="margin-top:0.15rem; width:100%; padding:0.35rem 0.5rem; border-radius:0.375rem; border:1px solid #d4d4d8;">
                </label>
                <div id="dev-login-error" style="display:none; color:#b91c1c; font-size:0.8rem;"></div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.5rem;">
                    <button type="button" id="dev-cancel" style="padding:0.3rem 0.75rem; font-size:0.85rem; border-radius:999px; border:1px solid #e5e5e5; background:#fafafa; cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" style="padding:0.3rem 0.9rem; font-size:0.85rem; border-radius:999px; border:none; background:#b91c1c; color:#fef2f2; cursor:pointer;">
                        Log in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="key-modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:40; align-items:center; justify-content:center;">
        <div id="key-modal" style="background:#fff; color:#111827; padding:1.25rem 1.5rem; border-radius:0.75rem; width:100%; max-width:28rem; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 10px 10px -5px rgba(0,0,0,0.04);">
            <h2 style="margin:0 0 0.75rem; font-size:1.1rem;">Generate v2 API key</h2>
            <p style="margin:0 0 0.75rem; font-size:0.9rem; color:#4b5563;">
                This key is shown only once. Store it securely and send it in the <code>X-API-KEY</code> header for all <code>/api/v2</code> requests.
            </p>
            <form id="key-form" style="display:flex; flex-direction:column; gap:0.5rem;">
                <label style="font-size:0.85rem;">
                    App name
                    <input id="key-app-name" type="text" required placeholder="My frontend app" style="margin-top:0.15rem; width:100%; padding:0.35rem 0.5rem; border-radius:0.375rem; border:1px solid #d4d4d8;">
                </label>
                <div id="key-result" style="display:none; margin-top:0.5rem;">
                    <div style="font-size:0.85rem; margin-bottom:0.25rem;">Your new API key:</div>
                    <div style="display:flex; gap:0.5rem; align-items:center;">
                        <input id="key-plain" type="text" readonly style="flex:1; padding:0.35rem 0.5rem; border-radius:0.375rem; border:1px solid #d4d4d8; font-family:ui-monospace, Menlo, monospace; font-size:0.85rem;">
                        <button type="button" id="key-copy" style="padding:0.3rem 0.75rem; font-size:0.8rem; border-radius:999px; border:1px solid #e5e5e5; background:#fafafa; cursor:pointer;">
                            Copy
                        </button>
                    </div>
                </div>
                <div id="key-error" style="display:none; color:#b91c1c; font-size:0.8rem; margin-top:0.25rem;"></div>
                <div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:0.75rem;">
                    <button type="button" id="key-cancel" style="padding:0.3rem 0.75rem; font-size:0.85rem; border-radius:999px; border:1px solid #e5e5e5; background:#fafafa; cursor:pointer;">
                        Close
                    </button>
                    <button type="submit" style="padding:0.3rem 0.9rem; font-size:0.85rem; border-radius:999px; border:none; background:#b91c1c; color:#fef2f2; cursor:pointer;">
                        Generate key
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        (function () {
            var v1Md = @json($apiDocV1Markdown);
            var v2Md = @json($apiDocV2Markdown);

            marked.setOptions({
                gfm: true,
                breaks: true,
                headerIds: false, // we will assign our own IDs
                mangle: false
            });

            var container = document.getElementById('api-docs-markdown');

            var v1Container = document.createElement('div');
            var v2Container = document.createElement('div');
            v1Container.id = 'v1-docs';
            v2Container.id = 'v2-docs';

            v1Container.innerHTML = marked.parse(v1Md);
            v2Container.innerHTML = marked.parse(v2Md);

            container.innerHTML = '';
            container.appendChild(v1Container);
            container.appendChild(v2Container);

            var tabV1 = document.getElementById('tab-v1');
            var tabV2 = document.getElementById('tab-v2');
            var tabApiKeys = document.getElementById('tab-api-keys');
            var sidebar = document.getElementById('sidebar-nav');
            var sidebarList = document.querySelector('#sidebar-list ul');
            var sidebarListNav = document.getElementById('sidebar-list');
            var sidebarTabV1 = document.getElementById('sidebar-tab-v1');
            var sidebarTabV2 = document.getElementById('sidebar-tab-v2');
            var apiDocsMarkdown = document.getElementById('api-docs-markdown');
            var apiKeysPanel = document.getElementById('api-keys-management-panel');

            var currentDocVersion = 'v2';

            function setTabInactive(el) {
                if (!el) return;
                el.style.background = '#f5f5f4';
                el.style.color = '#111827';
            }
            function setTabActive(el) {
                if (!el) return;
                el.style.background = '#111827';
                el.style.color = '#f9fafb';
            }

            function showV1() {
                currentDocVersion = 'v1';
                if (apiDocsMarkdown) apiDocsMarkdown.style.display = '';
                if (apiKeysPanel) apiKeysPanel.style.display = 'none';
                if (sidebar) sidebar.style.display = '';
                v1Container.style.display = 'block';
                v2Container.style.display = 'none';
                setTabActive(tabV1);
                setTabInactive(tabV2);
                setTabInactive(tabApiKeys);
                if (sidebarTabV1 && sidebarTabV2) {
                    sidebarTabV1.style.background = '#111827';
                    sidebarTabV1.style.color = '#f9fafb';
                    sidebarTabV2.style.background = 'transparent';
                    sidebarTabV2.style.color = '#111827';
                }
                renderSidebar('v1');
                if (typeof updateScrollSpy === 'function') updateScrollSpy();
                if (typeof updateBackToTopVisibility === 'function') updateBackToTopVisibility();
            }

            function showV2() {
                currentDocVersion = 'v2';
                if (apiDocsMarkdown) apiDocsMarkdown.style.display = '';
                if (apiKeysPanel) apiKeysPanel.style.display = 'none';
                if (sidebar) sidebar.style.display = '';
                v1Container.style.display = 'none';
                v2Container.style.display = 'block';
                setTabInactive(tabV1);
                setTabActive(tabV2);
                setTabInactive(tabApiKeys);
                if (sidebarTabV1 && sidebarTabV2) {
                    sidebarTabV2.style.background = '#111827';
                    sidebarTabV2.style.color = '#f9fafb';
                    sidebarTabV1.style.background = 'transparent';
                    sidebarTabV1.style.color = '#111827';
                }
                renderSidebar('v2');
                if (typeof updateScrollSpy === 'function') updateScrollSpy();
                if (typeof updateBackToTopVisibility === 'function') updateBackToTopVisibility();
            }

            function showApiKeysManagement() {
                if (apiDocsMarkdown) apiDocsMarkdown.style.display = 'none';
                if (apiKeysPanel) apiKeysPanel.style.display = 'block';
                if (sidebar) sidebar.style.display = 'none';
                v1Container.style.display = 'none';
                v2Container.style.display = 'none';
                setTabInactive(tabV1);
                setTabInactive(tabV2);
                setTabActive(tabApiKeys);
                if (typeof loadAdminApiKeys === 'function') loadAdminApiKeys();
                if (typeof updateBackToTopVisibility === 'function') updateBackToTopVisibility();
            }

            tabV1.addEventListener('click', showV1);
            tabV2.addEventListener('click', showV2);
            if (tabApiKeys) tabApiKeys.addEventListener('click', showApiKeysManagement);

            // Simple slug function to mirror our markdown anchors
            function slugify(text) {
                return text
                    .toLowerCase()
                    .trim()
                    .replace(/[\s\_]+/g, '-')      // spaces/underscores -> dashes
                    .replace(/[^\w\-]+/g, '')      // remove non-word chars (keep dashes)
                    .replace(/\-+/g, '-');         // collapse multiple dashes
            }

            // Assign IDs to headings separately for v1 and v2 to avoid collisions
            function assignHeadingIds(root, prefix) {
                var hs = root.querySelectorAll('h1, h2, h3, h4, h5, h6');
                hs.forEach(function (h) {
                    var base = slugify(h.textContent || '');
                    h.id = prefix + base;
                });
            }

            assignHeadingIds(v1Container, 'v1-');
            assignHeadingIds(v2Container, 'v2-');

            // Rewrite "Back to index" links: markdown uses #index but headings have prefixed ids
            function fixBackToIndexLinks(root, indexId) {
                root.querySelectorAll('a[href="#index"]').forEach(function (a) {
                    a.setAttribute('href', '#' + indexId);
                });
            }
            fixBackToIndexLinks(v1Container, 'v1-index');
            fixBackToIndexLinks(v2Container, 'v2-index-v2');

            var sidebarSkipTitles = [
                'API key details',
                'Index',
                'Index (v2)',
                'v2 – Front-end reference'
            ];

            function collectHeadings(root, prefix) {
                var result = [];
                var hs = root.querySelectorAll('h2, h3');
                hs.forEach(function (h) {
                    var text = (h.textContent || '').trim();
                    if (sidebarSkipTitles.indexOf(text) !== -1) return;
                    var level = parseInt(h.tagName.substring(1), 10);
                    var id = h.id || (prefix + slugify(text));
                    result.push({ text: text, level: level, id: id });
                });
                return result;
            }

            var v1Headings = collectHeadings(v1Container, 'v1-');
            var v2Headings = collectHeadings(v2Container, 'v2-');

            function isOnlyEndpointsHeading(text) {
                return typeof text === 'string' && text.indexOf('only endpoints') !== -1;
            }

            function groupHeadingsForSidebar(headings) {
                var groups = [];
                var i = 0;
                while (i < headings.length) {
                    var h = headings[i];
                    if (h.level === 2 && isOnlyEndpointsHeading(h.text)) {
                        var children = [];
                        i++;
                        while (i < headings.length && !(headings[i].level === 2 && isOnlyEndpointsHeading(headings[i].text))) {
                            children.push(headings[i]);
                            i++;
                        }
                        groups.push({ type: 'section', title: h.text, id: h.id, children: children });
                    } else {
                        groups.push({ type: 'item', heading: h });
                        i++;
                    }
                }
                return groups;
            }

            function renderSidebar(version) {
                if (!sidebar || !sidebarList) return;
                var headings = version === 'v2' ? v2Headings : v1Headings;
                var groups = groupHeadingsForSidebar(headings);
                sidebarList.innerHTML = '';
                groups.forEach(function (g) {
                    if (g.type === 'item') {
                        var h = g.heading;
                        var li = document.createElement('li');
                        li.style.marginBottom = '4px';
                        var a = document.createElement('a');
                        a.href = '#' + h.id.replace(/^v[12]-/, '');
                        a.textContent = h.text;
                        a.style.display = 'block';
                        a.style.padding = '3px 0';
                        if (h.level === 2) {
                            a.style.paddingLeft = '0';
                            a.style.fontSize = '0.9rem';
                            a.style.fontWeight = '600';
                        } else {
                            a.style.paddingLeft = '14px';
                            a.style.fontSize = '0.82rem';
                        }
                        li.appendChild(a);
                        sidebarList.appendChild(li);
                    } else {
                        var li = document.createElement('li');
                        li.style.marginBottom = '4px';
                        li.className = 'sidebar-collapsible-section sidebar-section-collapsed';
                        var toggle = document.createElement('button');
                        toggle.type = 'button';
                        toggle.className = 'sidebar-section-toggle';
                        toggle.setAttribute('aria-expanded', 'false');
                        var chevron = document.createElement('span');
                        chevron.className = 'sidebar-chevron';
                        chevron.textContent = '\u25BC';
                        toggle.appendChild(chevron);
                        toggle.appendChild(document.createTextNode(' ' + g.title));
                        li.appendChild(toggle);
                        var childUl = document.createElement('ul');
                        childUl.className = 'sidebar-section-children';
                        g.children.forEach(function (ch) {
                            var childLi = document.createElement('li');
                            childLi.style.marginBottom = '4px';
                            var a = document.createElement('a');
                            a.href = '#' + ch.id.replace(/^v[12]-/, '');
                            a.textContent = ch.text;
                            a.style.display = 'block';
                            a.style.padding = '3px 0';
                            if (ch.level === 2) {
                                a.style.paddingLeft = '8px';
                                a.style.fontSize = '0.9rem';
                                a.style.fontWeight = '600';
                            } else {
                                a.style.paddingLeft = '14px';
                                a.style.fontSize = '0.82rem';
                            }
                            childLi.appendChild(a);
                            childUl.appendChild(childLi);
                        });
                        li.appendChild(childUl);
                        sidebarList.appendChild(li);
                        toggle.addEventListener('click', function () {
                            var expanded = li.classList.toggle('sidebar-section-collapsed');
                            toggle.setAttribute('aria-expanded', !expanded);
                        });
                    }
                });
                sidebar.style.display = 'block';
            }

            // Wire sidebar version toggle
            if (sidebarTabV1 && sidebarTabV2) {
                sidebarTabV1.addEventListener('click', function () {
                    showV1();
                });
                sidebarTabV2.addEventListener('click', function () {
                    showV2();
                });
            }

            // Default to v2 (after headings are collected and renderSidebar exists)
            showV2();

            // Ensure there is a generic "top" anchor
            if (!document.getElementById('top')) {
                var topAnchor = document.createElement('div');
                topAnchor.id = 'top';
                document.body.insertBefore(topAnchor, document.body.firstChild);
            }

            // Docs search: index section titles (and path from tables when present)
            function buildSearchIndex(root, headings, prefix) {
                var index = [];
                headings.forEach(function (h) {
                    var text = (h.text || '').trim();
                    var slug = h.id ? h.id.replace(/^v[12]-/, '') : '';
                    var path = '';
                    var el = root.querySelector('#' + h.id);
                    if (el && el.nextElementSibling) {
                        var table = el.nextElementSibling.tagName === 'TABLE' ? el.nextElementSibling : el.nextElementSibling.querySelector('table');
                        if (table) {
                            var rows = table.querySelectorAll('tr');
                            for (var r = 0; r < rows.length; r++) {
                                var cells = rows[r].querySelectorAll('td, th');
                                for (var c = 0; c < cells.length; c++) {
                                    var cellText = (cells[c].textContent || '').trim();
                                    if (cellText === 'Path' && cells[c + 1]) {
                                        path = (cells[c + 1].textContent || '').trim();
                                        break;
                                    }
                                }
                                if (path) break;
                            }
                        }
                    }
                    index.push({ id: h.id, text: text, slug: slug, path: path });
                });
                return index;
            }
            var searchIndexV1 = buildSearchIndex(v1Container, v1Headings, 'v1-');
            var searchIndexV2 = buildSearchIndex(v2Container, v2Headings, 'v2-');

            var docsSearchInput = document.getElementById('docs-search-input');
            var docsSearchResults = document.getElementById('docs-search-results');
            var searchDebounce = null;
            var SEARCH_DEBOUNCE_MS = 200;
            var MAX_SEARCH_RESULTS = 18;

            function getCurrentSearchIndex() {
                return currentDocVersion === 'v2' ? searchIndexV2 : searchIndexV1;
            }

            function showSearchResults(query) {
                var q = (query || '').trim().toLowerCase();
                if (!q) {
                    docsSearchResults.style.display = 'none';
                    docsSearchResults.innerHTML = '';
                    return;
                }
                var index = getCurrentSearchIndex();
                var matches = [];
                for (var i = 0; i < index.length && matches.length < MAX_SEARCH_RESULTS; i++) {
                    var item = index[i];
                    if (item.text.toLowerCase().indexOf(q) !== -1 || (item.path && item.path.toLowerCase().indexOf(q) !== -1) || item.slug.indexOf(q) !== -1) {
                        matches.push(item);
                    }
                }
                docsSearchResults.innerHTML = '';
                if (matches.length === 0) {
                    var empty = document.createElement('div');
                    empty.className = 'search-no-matches';
                    empty.style.padding = '0.5rem 0.75rem';
                    empty.style.fontSize = '0.85rem';
                    empty.style.color = '#111827';
                    empty.style.setProperty('color', '#111827', 'important');
                    empty.textContent = 'No matches';
                    docsSearchResults.appendChild(empty);
                } else {
                    matches.forEach(function (item) {
                        var div = document.createElement('button');
                        div.type = 'button';
                        div.className = 'search-result-item';
                        div.style.display = 'block';
                        div.style.width = '100%';
                        div.style.padding = '0.45rem 0.8rem';
                        div.style.textAlign = 'left';
                        div.style.fontSize = '0.85rem';
                        div.style.border = 'none';
                        div.style.borderBottom = '1px solid #e5e5e5';
                        div.style.cursor = 'pointer';
                        div.style.color = '#111827';
                        div.style.setProperty('color', '#111827', 'important');
                        div.style.setProperty('-webkit-text-fill-color', '#111827', 'important');
                        div.style.background = '#fff';
                        if (item.path) {
                            div.innerHTML = '<span style="font-weight:600; color:#111827; -webkit-text-fill-color:#111827;">' + escapeHtml(item.text) + '</span><br><span style="font-size:0.8rem; color:#111827; -webkit-text-fill-color:#111827;">' + escapeHtml(item.path) + '</span>';
                        } else {
                            div.textContent = item.text;
                        }
                        div.addEventListener('click', function () {
                            var targetEl = document.getElementById(item.id);
                            if (targetEl) {
                                targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                            }
                            docsSearchResults.style.display = 'none';
                            docsSearchInput.value = '';
                        });
                        docsSearchResults.appendChild(div);
                    });
                }
                docsSearchResults.style.display = 'block';
            }

            if (docsSearchInput && docsSearchResults) {
                docsSearchInput.addEventListener('input', function () {
                    var self = this;
                    if (searchDebounce) clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(function () {
                        searchDebounce = null;
                        showSearchResults(self.value);
                    }, SEARCH_DEBOUNCE_MS);
                });
                docsSearchInput.addEventListener('focus', function () {
                    if (this.value.trim()) showSearchResults(this.value);
                });
                docsSearchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        docsSearchResults.style.display = 'none';
                        this.blur();
                    }
                });
            }
            document.addEventListener('click', function (e) {
                if (docsSearchResults && docsSearchInput && !docsSearchInput.contains(e.target) && !docsSearchResults.contains(e.target)) {
                    docsSearchResults.style.display = 'none';
                }
            });

            // Back to top button
            var backToTopBtn = document.getElementById('back-to-top');
            var backToTopScrollThrottle = null;
            function updateBackToTopVisibility() {
                if (!backToTopBtn) return;
                var show = window.scrollY > 100;
                if (show) {
                    backToTopBtn.classList.add('visible');
                } else {
                    backToTopBtn.classList.remove('visible');
                }
            }
            if (backToTopBtn) {
                backToTopBtn.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                window.addEventListener('scroll', function () {
                    if (backToTopScrollThrottle) return;
                    backToTopScrollThrottle = setTimeout(function () {
                        backToTopScrollThrottle = null;
                        updateBackToTopVisibility();
                    }, 80);
                }, { passive: true });
                updateBackToTopVisibility();
            }

            function scrollToFragment(target, href) {
                var fragment = href.slice(1); // e.g. "authentication", "index", or "v1-index"
                if (!fragment || fragment === 'top') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                var id;
                // If the fragment already includes a version prefix, use it as-is.
                if (fragment.indexOf('v1-') === 0 || fragment.indexOf('v2-') === 0) {
                    id = fragment;
                } else {
                    var prefix = v2Container.contains(target) ? 'v2-' : 'v1-';
                    id = prefix + fragment;
                }
                var el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            // Handle in-page anchor clicks manually for smooth scrolling
            container.addEventListener('click', function (e) {
                var target = e.target;
                if (target.tagName === 'A') {
                    var href = target.getAttribute('href') || '';
                    if (href.charAt(0) === '#') {
                        e.preventDefault();
                        scrollToFragment(target, href);
                    }
                }
            });

            // Sidebar links: scroll using current tab's prefixed IDs
            if (sidebarList) {
                sidebarList.addEventListener('click', function (e) {
                    var target = e.target;
                    if (target.tagName === 'A') {
                        var href = target.getAttribute('href') || '';
                        if (href.charAt(0) === '#') {
                            e.preventDefault();
                            var fragment = href.slice(1);
                            if (!fragment) return;
                            var id = currentDocVersion + '-' + fragment;
                            var el = document.getElementById(id);
                            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }
                    }
                });
            }

            // Scrollspy: highlight the nav item for the heading at the top of the viewport
            var scrollSpyThrottle = null;
            var scrollSpyInterval = 100;

            function updateScrollSpy() {
                var headings = currentDocVersion === 'v2' ? v2Headings : v1Headings;
                if (!headings.length || !sidebarList) return;

                // Choose the heading whose top edge is closest to the top of the viewport (>= 0),
                // falling back to the last heading when scrolled past the end.
                var bestId = null;
                var bestTop = Infinity;
                for (var i = 0; i < headings.length; i++) {
                    var el = document.getElementById(headings[i].id);
                    if (!el) continue;
                    var rect = el.getBoundingClientRect();
                    if (rect.top >= 0 && rect.top < bestTop) {
                        bestTop = rect.top;
                        bestId = headings[i].id;
                    }
                }
                // If none are below the top (user scrolled past all), highlight the last heading.
                if (!bestId) {
                    bestId = headings[headings.length - 1].id;
                }
                var fragment = bestId ? bestId.replace(/^v[12]-/, '') : null;
                var links = sidebarList.querySelectorAll('a');
                for (var j = 0; j < links.length; j++) {
                    var a = links[j];
                    var href = (a.getAttribute('href') || '').slice(1);
                    if (href === fragment) {
                        a.classList.add('sidebar-active');
                        var sectionChildren = a.closest('.sidebar-section-children');
                        if (sectionChildren) {
                            var sectionLi = sectionChildren.parentElement;
                            if (sectionLi && sectionLi.classList.contains('sidebar-collapsible-section')) {
                                sectionLi.classList.remove('sidebar-section-collapsed');
                                var toggleBtn = sectionLi.querySelector('.sidebar-section-toggle');
                                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
                            }
                        }
                        if (sidebarListNav && sidebarListNav.scrollHeight > sidebarListNav.clientHeight) {
                            var listRect = sidebarListNav.getBoundingClientRect();
                            var linkRect = a.getBoundingClientRect();
                            var scrollDelta = (linkRect.top + linkRect.height / 2) - (listRect.top + listRect.height / 2);
                            var newScroll = sidebarListNav.scrollTop + scrollDelta;
                            sidebarListNav.scrollTop = Math.max(0, Math.min(newScroll, sidebarListNav.scrollHeight - sidebarListNav.clientHeight));
                        }
                    } else {
                        a.classList.remove('sidebar-active');
                    }
                }
            }

            function onScrollForSpy() {
                if (scrollSpyThrottle) return;
                scrollSpyThrottle = setTimeout(function () {
                    scrollSpyThrottle = null;
                    updateScrollSpy();
                }, scrollSpyInterval);
            }

            window.addEventListener('scroll', onScrollForSpy, { passive: true });
            updateScrollSpy();

            var devTokenKey = 'dev_jwt_token';

            function getDevToken() {
                try {
                    return window.localStorage.getItem(devTokenKey) || '';
                } catch (e) {
                    return '';
                }
            }

            function setDevToken(token) {
                try {
                    if (token) {
                        window.localStorage.setItem(devTokenKey, token);
                    } else {
                        window.localStorage.removeItem(devTokenKey);
                    }
                } catch (e) {
                    // ignore
                }
            }

            var devStatus = document.getElementById('dev-status');
            var devLoginButton = document.getElementById('dev-login-button');
            var devLogoutButton = document.getElementById('dev-logout-button');
            var generateKeyButton = document.getElementById('generate-key-button');
            var devKeyDisplay = document.getElementById('dev-key-display');
            var existingKey = null;

            async function loadExistingKey() {
                var token = getDevToken();
                if (!token) {
                    existingKey = null;
                    devKeyDisplay.style.display = 'none';
                    generateKeyButton.style.display = 'inline-flex';
                    return;
                }

                try {
                    var res = await fetch('/api/v2/dev/api-keys', {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                    });

                    if (!res.ok) {
                        existingKey = null;
                        devKeyDisplay.style.display = 'none';
                        generateKeyButton.style.display = 'inline-flex';
                        return;
                    }

                    var data = await res.json();
                    if (data.data && data.data.plain_key) {
                        existingKey = data.data;
                        devKeyDisplay.textContent = 'v2 key: ' + existingKey.plain_key;
                        devKeyDisplay.style.display = 'inline-flex';
                        generateKeyButton.style.display = 'none';
                    } else {
                        existingKey = null;
                        devKeyDisplay.style.display = 'none';
                        generateKeyButton.style.display = 'inline-flex';
                    }
                } catch (e) {
                    existingKey = null;
                    devKeyDisplay.style.display = 'none';
                    generateKeyButton.style.display = 'inline-flex';
                }
            }

            var currentUserRole = null;

            async function fetchMe() {
                var token = getDevToken();
                if (!token) {
                    currentUserRole = null;
                    return null;
                }
                try {
                    var res = await fetch('/api/v1/auth/me', {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                    });
                    if (!res.ok) {
                        currentUserRole = null;
                        return null;
                    }
                    var data = await res.json();
                    var user = data.data || null;
                    currentUserRole = user && user.role ? user.role : null;
                    return user;
                } catch (e) {
                    currentUserRole = null;
                    return null;
                }
            }

            function updateAdminTabVisibility() {
                if (tabApiKeys) {
                    tabApiKeys.style.display = currentUserRole === 'admin' ? 'inline-flex' : 'none';
                }
            }

            function updateDevUi() {
                var token = getDevToken();
                if (token) {
                    devLoginButton.style.display = 'none';
                    devLogoutButton.style.display = 'inline-flex';
                    generateKeyButton.disabled = false;
                    generateKeyButton.style.opacity = '1';
                    loadExistingKey();
                    fetchMe().then(function () {
                        devStatus.textContent = currentUserRole === 'admin' ? 'Admin: logged in' : 'Dev: logged in';
                        updateAdminTabVisibility();
                    });
                    devStatus.textContent = 'Loading…';
                    updateAdminTabVisibility();
                } else {
                    currentUserRole = null;
                    devStatus.textContent = 'Dev: not logged in';
                    devLoginButton.style.display = 'inline-flex';
                    devLogoutButton.style.display = 'none';
                    generateKeyButton.disabled = true;
                    generateKeyButton.style.opacity = '0.6';
                    devKeyDisplay.style.display = 'none';
                    updateAdminTabVisibility();
                }
            }

            async function loadAdminApiKeys() {
                var tbody = document.getElementById('api-keys-tbody');
                var emptyEl = document.getElementById('api-keys-empty');
                var tableWrap = document.getElementById('api-keys-table-wrap');
                var msgEl = document.getElementById('api-keys-admin-message');
                if (!tbody) return;
                var token = getDevToken();
                if (!token) {
                    if (msgEl) msgEl.textContent = 'Log in as admin to manage API keys.';
                    tbody.innerHTML = '';
                    if (emptyEl) emptyEl.style.display = 'block';
                    if (tableWrap) tableWrap.style.display = 'none';
                    return;
                }
                if (msgEl) msgEl.textContent = '';
                try {
                    var res = await fetch('/api/v2/admin/api-keys', {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                    });
                    if (res.status === 401 || res.status === 403) {
                        if (msgEl) msgEl.textContent = 'Not authorized. Log in as admin.';
                        tbody.innerHTML = '';
                        if (emptyEl) emptyEl.style.display = 'block';
                        if (tableWrap) tableWrap.style.display = 'none';
                        return;
                    }
                    if (!res.ok) {
                        if (msgEl) msgEl.textContent = 'Failed to load API keys.';
                        tbody.innerHTML = '';
                        if (emptyEl) emptyEl.style.display = 'block';
                        if (tableWrap) tableWrap.style.display = 'none';
                        return;
                    }
                    var data = await res.json();
                    var keys = data.data || [];
                    if (tableWrap) tableWrap.style.display = '';
                    if (emptyEl) emptyEl.style.display = keys.length ? 'none' : 'block';
                    tbody.innerHTML = '';
                    keys.forEach(function (k) {
                        var tr = document.createElement('tr');
                        var owner = k.user ? (k.user.email || (k.user.first_name + ' ' + k.user.last_name).trim() || '—') : '—';
                        var lastUsed = k.last_used_at ? new Date(k.last_used_at).toLocaleString() : '—';
                        var created = k.created_at ? new Date(k.created_at).toLocaleString() : '—';
                        var status = k.is_active ? 'Active' : 'Revoked';
                        tr.innerHTML =
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' + escapeHtml(k.name || '') + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem; font-family:ui-monospace, Menlo, monospace; font-size:0.85rem;">' + escapeHtml(k.plain_key_preview || '') + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' + escapeHtml(owner) + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' + escapeHtml(status) + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' + escapeHtml(lastUsed) + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' + escapeHtml(created) + '</td>' +
                            '<td style="border:1px solid #e3e3e0; padding:0.5rem 0.75rem;">' +
                            (k.is_active
                                ? '<button type="button" class="revoke-api-key" data-id="' + escapeHtml(String(k.id)) + '" style="padding:0.2rem 0.5rem; font-size:0.8rem; border-radius:999px; border:1px solid #d4d4d4; background:#fafafa; cursor:pointer; color:#b91c1c;">Revoke</button>'
                                : '—') +
                            '</td>';
                        tbody.appendChild(tr);
                    });
                    tbody.querySelectorAll('.revoke-api-key').forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            var id = btn.getAttribute('data-id');
                            if (!id) return;
                            revokeAdminApiKey(id);
                        });
                    });
                } catch (e) {
                    if (msgEl) msgEl.textContent = 'Failed to load API keys.';
                    tbody.innerHTML = '';
                    if (emptyEl) emptyEl.style.display = 'block';
                    if (tableWrap) tableWrap.style.display = 'none';
                }
            }

            function escapeHtml(s) {
                if (s == null) return '';
                var div = document.createElement('div');
                div.textContent = s;
                return div.innerHTML;
            }

            async function revokeAdminApiKey(id) {
                var token = getDevToken();
                if (!token) return;
                try {
                    var res = await fetch('/api/v2/admin/api-keys/' + id, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                    });
                    if (res.ok) {
                        loadAdminApiKeys();
                    }
                } catch (e) {
                    // ignore
                }
            }

            updateDevUi();

            var devModalBackdrop = document.getElementById('dev-modal-backdrop');
            var devLoginForm = document.getElementById('dev-login-form');
            var devCancel = document.getElementById('dev-cancel');
            var devError = document.getElementById('dev-login-error');

            devLoginButton.addEventListener('click', function () {
                devError.style.display = 'none';
                devError.textContent = '';
                devModalBackdrop.style.display = 'flex';
            });

            devCancel.addEventListener('click', function () {
                devModalBackdrop.style.display = 'none';
            });

            devLogoutButton.addEventListener('click', function () {
                setDevToken('');
                updateDevUi();
            });

            devLoginForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                devError.style.display = 'none';
                devError.textContent = '';

                var email = document.getElementById('dev-email').value;
                var password = document.getElementById('dev-password').value;

                try {
                    var res = await fetch('/api/v1/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ email: email, password: password }),
                    });

                    if (!res.ok) {
                        devError.textContent = 'Login failed. Check credentials or role.';
                        devError.style.display = 'block';
                        return;
                    }

                    var data = await res.json();
                    if (!data.token) {
                        devError.textContent = 'Login response missing token.';
                        devError.style.display = 'block';
                        return;
                    }

                    setDevToken(data.token);
                    devModalBackdrop.style.display = 'none';
                    updateDevUi();
                } catch (err) {
                    devError.textContent = 'Unexpected error logging in.';
                    devError.style.display = 'block';
                }
            });

            var keyModalBackdrop = document.getElementById('key-modal-backdrop');
            var keyForm = document.getElementById('key-form');
            var keyCancel = document.getElementById('key-cancel');
            var keyError = document.getElementById('key-error');
            var keyResult = document.getElementById('key-result');
            var keyPlain = document.getElementById('key-plain');
            var keyCopy = document.getElementById('key-copy');
            var keyAppName = document.getElementById('key-app-name');
            var keySubmitButton = keyForm.querySelector('button[type="submit"]');

            generateKeyButton.addEventListener('click', function () {
                if (!getDevToken()) {
                    devModalBackdrop.style.display = 'flex';
                    return;
                }
                keyError.style.display = 'none';
                keyError.textContent = '';
                keyResult.style.display = 'none';
                keyPlain.value = '';
                keyAppName.disabled = false;
                keySubmitButton.disabled = false;
                keySubmitButton.textContent = 'Generate key';
                keyModalBackdrop.style.display = 'flex';
            });

            keyCancel.addEventListener('click', function () {
                keyModalBackdrop.style.display = 'none';
            });

            keyForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                keyError.style.display = 'none';
                keyError.textContent = '';

                var appName = document.getElementById('key-app-name').value;
                var token = getDevToken();
                if (!token) {
                    keyError.textContent = 'Dev login required.';
                    keyError.style.display = 'block';
                    return;
                }

                try {
                    var res = await fetch('/api/v2/dev/api-keys', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + token,
                        },
                        body: JSON.stringify({ name: appName }),
                    });

                    if (!res.ok) {
                        if (res.status === 401 || res.status === 403) {
                            keyError.textContent = 'Not authorized. Ensure you are logged in as a dev user.';
                        } else {
                            keyError.textContent = 'Failed to generate key.';
                        }
                        keyError.style.display = 'block';
                        return;
                    }

                    var data = await res.json();
                    if (!data.data || !data.data.plain_key) {
                        keyError.textContent = 'Unexpected response from server.';
                        keyError.style.display = 'block';
                        return;
                    }

                    keyPlain.value = data.data.plain_key;
                    keyResult.style.display = 'block';
                    // After a key exists for this user, prevent generating a new one.
                    keyAppName.disabled = true;
                    keySubmitButton.disabled = true;
                    keySubmitButton.textContent = 'Key already generated';
                } catch (err) {
                    keyError.textContent = 'Unexpected error generating key.';
                    keyError.style.display = 'block';
                }
            });

            keyCopy.addEventListener('click', function () {
                if (!keyPlain.value) return;
                try {
                    keyPlain.select();
                    document.execCommand('copy');
                } catch (e) {
                    // ignore
                }
            });
        })();
    </script>
</body>
</html>
