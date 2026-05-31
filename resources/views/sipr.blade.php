<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="theme-color" content="#070b18" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>SIPR Group</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <style>
        :root {
            --bg: #070b18;
            --bg2: #0b1224;
            --panel: rgba(13, 20, 40, 0.86);
            --panel2: rgba(18, 26, 50, 0.92);
            --line: rgba(125, 149, 255, 0.18);
            --line2: rgba(120, 180, 255, 0.28);
            --text: #eef3ff;
            --muted: #9aa7c5;
            --accent: #65a8ff;
            --accent2: #294fdf;
            --success: #38d9a9;
            --warning: #ffb347;
            --danger: #ff6b7a;
            --shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            background:
                radial-gradient(circle at 15% 15%, rgba(101, 168, 255, 0.16), transparent 28%),
                radial-gradient(circle at 85% 12%, rgba(57, 93, 255, 0.16), transparent 22%),
                radial-gradient(circle at 85% 80%, rgba(56, 217, 169, 0.08), transparent 20%),
                linear-gradient(180deg, #050812 0%, #070b18 48%, #04070f 100%);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .shell {
            position: relative;
            overflow: hidden;
        }

        .shell::before,
        .shell::after {
            content: "";
            position: fixed;
            inset: auto;
            width: 28rem;
            height: 28rem;
            border-radius: 999px;
            filter: blur(80px);
            opacity: 0.28;
            pointer-events: none;
        }

        .shell::before {
            top: -8rem;
            left: -8rem;
            background: rgba(84, 131, 255, 0.35);
        }

        .shell::after {
            bottom: -10rem;
            right: -8rem;
            background: rgba(56, 217, 169, 0.2);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: blur(18px);
            background: linear-gradient(180deg, rgba(5, 8, 18, 0.9), rgba(5, 8, 18, 0.72));
            border-bottom: 1px solid rgba(125, 149, 255, 0.12);
        }

        .topbar-inner,
        .page {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
        }

        .topbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(101, 168, 255, 0.95), rgba(41, 79, 223, 0.9));
            box-shadow: 0 16px 40px rgba(41, 79, 223, 0.35);
            font-weight: 900;
            letter-spacing: 0.08em;
        }

        .brand h1 {
            margin: 0;
            font-size: 1.05rem;
            letter-spacing: 0.02em;
        }

        .brand p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .chip,
        .nav a,
        .ghost,
        .primary,
        .danger {
            border-radius: 999px;
            border: 1px solid rgba(125, 149, 255, 0.16);
            background: rgba(10, 15, 31, 0.58);
            color: var(--text);
            padding: 11px 16px;
            transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
        }

        .chip {
            color: var(--muted);
        }

        .nav a:hover,
        .ghost:hover,
        .primary:hover,
        .danger:hover {
            transform: translateY(-1px);
            border-color: rgba(120, 180, 255, 0.35);
        }

        .primary {
            background: linear-gradient(135deg, rgba(101, 168, 255, 0.9), rgba(41, 79, 223, 0.96));
            border-color: transparent;
            box-shadow: 0 18px 50px rgba(41, 79, 223, 0.3);
        }

        .danger {
            background: rgba(255, 107, 122, 0.08);
            border-color: rgba(255, 107, 122, 0.28);
            color: #ff9ca6;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 26px;
            padding: 34px 0 16px;
            align-items: stretch;
        }

        .hero-card,
        .panel,
        .stat,
        .module {
            border: 1px solid rgba(125, 149, 255, 0.16);
            background: linear-gradient(180deg, rgba(12, 18, 35, 0.9), rgba(9, 14, 29, 0.86));
            box-shadow: var(--shadow);
            border-radius: 24px;
        }

        .hero-card {
            padding: 30px;
            position: relative;
            overflow: hidden;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: auto -8% -14% auto;
            width: 22rem;
            height: 22rem;
            background: radial-gradient(circle, rgba(101, 168, 255, 0.18), transparent 60%);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(101, 168, 255, 0.2);
            color: #cfe0ff;
            background: rgba(101, 168, 255, 0.08);
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero h2 {
            margin: 18px 0 12px;
            font-size: clamp(2.2rem, 5vw, 4.4rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
        }

        .hero p {
            margin: 0;
            max-width: 60ch;
            color: var(--muted);
            font-size: 1.01rem;
            line-height: 1.7;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 24px;
        }

        .stat {
            padding: 18px;
        }

        .stat .label {
            color: var(--muted);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .stat .value {
            font-size: 1.4rem;
            margin-top: 8px;
            font-weight: 800;
        }

        .stack {
            display: grid;
            gap: 18px;
        }

        .panel {
            padding: 22px;
        }

        .panel h3,
        .module h3 {
            margin: 0;
            font-size: 1.05rem;
        }

        .panel .sub,
        .module .sub {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .form {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .field {
            display: grid;
            gap: 8px;
        }

        .field label {
            font-size: 0.76rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #b6c5ea;
        }

        .input {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid rgba(125, 149, 255, 0.18);
            background: rgba(6, 10, 22, 0.8);
            color: var(--text);
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .input:focus {
            border-color: rgba(101, 168, 255, 0.7);
            box-shadow: 0 0 0 4px rgba(101, 168, 255, 0.1);
        }

        .split {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .hint {
            color: var(--muted);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .banner {
            margin: 18px 0 0;
            padding: 16px 18px;
            border-radius: 18px;
            border: 1px solid rgba(56, 217, 169, 0.16);
            background: rgba(56, 217, 169, 0.06);
            color: #b9f7e5;
            display: none;
        }

        .banner.error {
            border-color: rgba(255, 107, 122, 0.2);
            background: rgba(255, 107, 122, 0.08);
            color: #ffb5be;
        }

        .section {
            padding: 18px 0 36px;
        }

        .modules {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .module {
            padding: 20px;
        }

        .module strong {
            display: block;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .module p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 18px 0 16px;
        }

        .tab {
            padding: 10px 15px;
            border-radius: 999px;
            border: 1px solid rgba(125, 149, 255, 0.14);
            background: rgba(7, 12, 24, 0.72);
            color: var(--muted);
        }

        .tab.active {
            color: #eff5ff;
            border-color: rgba(101, 168, 255, 0.4);
            background: rgba(101, 168, 255, 0.12);
        }

        .workspace {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .list {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .item {
            border-radius: 18px;
            border: 1px solid rgba(125, 149, 255, 0.14);
            background: rgba(7, 12, 24, 0.68);
            padding: 16px;
        }

        .item-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            background: rgba(101, 168, 255, 0.1);
            color: #cfe0ff;
            border: 1px solid rgba(101, 168, 255, 0.16);
        }

        .badge.pending {
            background: rgba(255, 179, 71, 0.08);
            color: #ffd19a;
            border-color: rgba(255, 179, 71, 0.18);
        }

        .badge.approved {
            background: rgba(56, 217, 169, 0.08);
            color: #b8fae2;
            border-color: rgba(56, 217, 169, 0.18);
        }

        .badge.rejected {
            background: rgba(255, 107, 122, 0.08);
            color: #ffb5be;
            border-color: rgba(255, 107, 122, 0.18);
        }

        .item h4 {
            margin: 0 0 6px;
            font-size: 1rem;
        }

        .item .meta {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.65;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .actions button,
        .actions a {
            border: 0;
            border-radius: 12px;
            padding: 10px 13px;
            cursor: pointer;
            background: rgba(101, 168, 255, 0.14);
            color: #eaf2ff;
        }

        .actions .muted {
            background: rgba(255, 255, 255, 0.06);
            color: #d5def3;
        }

        .actions .warn {
            background: rgba(255, 179, 71, 0.12);
            color: #ffd7a2;
        }

        .actions .danger {
            background: rgba(255, 107, 122, 0.12);
            color: #ffb5be;
            border: 0;
        }

        .hidden {
            display: none !important;
        }

        .footer {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: 10px 0 40px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        @media (max-width: 980px) {

            .hero,
            .workspace,
            .modules {
                grid-template-columns: 1fr;
            }

            .topbar-inner {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        @media (max-width: 700px) {

            .topbar-inner,
            .page,
            .footer {
                width: min(100% - 20px, 1180px);
            }

            .hero-card,
            .panel,
            .module {
                border-radius: 20px;
            }

            .hero-card {
                padding: 22px;
            }

            .split,
            .hero-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="shell">
        <header class="topbar">
            <div class="topbar-inner">
                <div class="brand">
                    <div class="logo">S</div>
                    <div>
                        <h1>SIPR Group</h1>
                        <p>Token-authenticated member system with admin approval control</p>
                    </div>
                </div>
                <nav class="nav">
                    <a href="#auth">Login</a>
                    <a href="#register">Register</a>
                    <a href="#admin">Admin</a>
                    <span class="chip" id="session-chip">No active session</span>
                </nav>
            </div>
        </header>

        <main class="page">
            <section class="hero">
                <div class="hero-card">
                    <div class="eyebrow">SIPR token system</div>
                    <h2>Clean access, controlled approvals, and a polished member experience.</h2>
                    <p>
                        This Laravel entry page mirrors the SIPR design language while using real Sanctum tokens,
                        API-driven login,
                        and an admin review queue for new member registrations.
                    </p>
                    <div class="hero-actions">
                        <a class="primary" href="#auth">Sign in</a>
                        <a class="ghost" href="#register">Request access</a>
                        <a class="ghost" href="{{ route('auth.google.redirect') }}">Continue with Google</a>
                    </div>
                    <div class="hero-grid">
                        <div class="stat">
                            <div class="label">Auth model</div>
                            <div class="value">Sanctum tokens</div>
                        </div>
                        <div class="stat">
                            <div class="label">Member intake</div>
                            <div class="value">Admin approval</div>
                        </div>
                        <div class="stat">
                            <div class="label">Review actions</div>
                            <div class="value">Approve / delete</div>
                        </div>
                        <div class="stat">
                            <div class="label">Layout style</div>
                            <div class="value">SIPR inspired</div>
                        </div>
                    </div>
                </div>

                <div class="stack">
                    <section class="panel" id="auth">
                        <h3>Member login</h3>
                        <p class="sub">Use your SIPR email and password to receive a bearer token and open the
                            dashboard.</p>
                        <form class="form" id="login-form">
                            <div class="field">
                                <label for="login-email">Email</label>
                                <input class="input" id="login-email" type="email" placeholder="name@example.com"
                                    required />
                            </div>
                            <div class="field">
                                <label for="login-password">Password</label>
                                <input class="input" id="login-password" type="password" placeholder="Your password"
                                    required />
                            </div>
                            <button class="primary" type="submit">Login with token</button>
                        </form>
                    </section>

                    <section class="panel" id="register">
                        <h3>New member request</h3>
                        <p class="sub">Registration is stored as pending until an admin approves or deletes it.</p>
                        <form class="form" id="register-form">
                            <div class="split">
                                <div class="field">
                                    <label for="reg-name">Full name</label>
                                    <input class="input" id="reg-name" type="text" placeholder="Member name" required />
                                </div>
                                <div class="field">
                                    <label for="reg-phone">Phone</label>
                                    <input class="input" id="reg-phone" type="text" placeholder="01700000000" />
                                </div>
                            </div>
                            <div class="split">
                                <div class="field">
                                    <label for="reg-email">Email</label>
                                    <input class="input" id="reg-email" type="email"
                                        placeholder="new.member@example.com" required />
                                </div>
                                <div class="field">
                                    <label for="reg-invite">Invite code</label>
                                    <input class="input" id="reg-invite" type="text" placeholder="INV-12345" required />
                                </div>
                            </div>
                            <div class="field">
                                <label for="reg-password">Password</label>
                                <input class="input" id="reg-password" type="password" placeholder="Create a password"
                                    required />
                            </div>
                            <button class="primary" type="submit">Submit request</button>
                        </form>
                    </section>
                </div>
            </section>

            <section class="section">
                <div class="modules">
                    <div class="module">
                        <strong>Professional baseline</strong>
                        <p>Sharper spacing, stronger hierarchy, and a calm navy palette keep the interface consistent
                            with the index system while feeling native to Laravel.</p>
                    </div>
                    <div class="module">
                        <strong>Token-first session</strong>
                        <p>The page stores the Sanctum token locally, checks <span style="color:#dfe7ff">/api/me</span>,
                            and restores the session on reload.</p>
                    </div>
                    <div class="module">
                        <strong>Admin review queue</strong>
                        <p>Admins see the pending queue and can approve a member into a role or delete the request
                            entirely.</p>
                    </div>
                </div>
            </section>

            <section class="section" id="admin">
                <div class="panel">
                    <div class="item-head" style="align-items: center; margin-bottom: 6px;">
                        <div>
                            <h3 style="margin: 0;">Authenticated workspace</h3>
                            <p class="sub" style="margin-top: 6px;">Current member profile and admin review controls.
                            </p>
                        </div>
                        <button class="danger hidden" id="logout-btn" type="button">Logout</button>
                    </div>
                    <div class="tabs">
                        <span class="tab active" data-role-tab="session">Session</span>
                        <span class="tab" data-role-tab="pending">Pending registrations</span>
                    </div>
                    <div class="workspace">
                        <div class="panel" style="background: rgba(7, 12, 24, 0.6); box-shadow: none;">
                            <h3>Session details</h3>
                            <p class="sub">This area reflects the active token state and the authenticated member
                                profile.</p>
                            <div class="list" id="session-card">
                                <div class="item">
                                    <h4 id="session-name">Not signed in</h4>
                                    <div class="meta" id="session-meta">Use the login form to open a token-authenticated
                                        session.</div>
                                </div>
                            </div>
                        </div>
                        <div class="panel" id="pending-panel"
                            style="background: rgba(7, 12, 24, 0.6); box-shadow: none;">
                            <h3>Pending registrations</h3>
                            <p class="sub">Available for admin users only.</p>
                            <div class="list" id="pending-list">
                                <div class="item">
                                    <h4>No registration selected</h4>
                                    <div class="meta">Sign in as an admin to load the approval queue.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            SIPR Group built on Laravel with Sanctum authentication and admin-controlled member approvals.
        </footer>

        <div class="page" style="padding-bottom: 30px;">
            <div class="banner" id="banner"></div>
        </div>
    </div>

    <script>
        const state = {
            token: localStorage.getItem('sipr_token') || '',
            member: null,
            pending: [],
        };

        const el = (id) => document.getElementById(id);
        const banner = el('banner');
        const sessionChip = el('session-chip');
        const pendingPanel = el('pending-panel');
        const logoutBtn = el('logout-btn');
        const pendingList = el('pending-list');
        const sessionName = el('session-name');
        const sessionMeta = el('session-meta');
        const roleTabs = document.querySelectorAll('[data-role-tab]');

        function showMessage(text, isError = false) {
            banner.textContent = text;
            banner.className = isError ? 'banner error' : 'banner';
            banner.style.display = 'block';
            clearTimeout(window.__siprMessageTimer);
            window.__siprMessageTimer = setTimeout(() => {
                banner.style.display = 'none';
            }, 5000);
        }

        function setSession(member, token = state.token) {
            state.member = member;
            state.token = token;
            if (token) {
                localStorage.setItem('sipr_token', token);
            } else {
                localStorage.removeItem('sipr_token');
            }

            if (member) {
                sessionChip.textContent = member.name + ' · ' + member.role;
                logoutBtn.classList.remove('hidden');
                sessionName.textContent = member.name;
                sessionMeta.textContent = [member.email, member.role, 'Token active'].filter(Boolean).join(' · ');
            } else {
                sessionChip.textContent = 'No active session';
                logoutBtn.classList.add('hidden');
                sessionName.textContent = 'Not signed in';
                sessionMeta.textContent = 'Use the login form to open a token-authenticated session.';
            }

            pendingPanel.classList.toggle('hidden', !member || member.role !== 'admin');
        }

        async function requestJson(url, options = {}) {
            const headers = Object.assign({ 'X-Requested-With': 'XMLHttpRequest' }, options.headers || {});
            const token = state.token || localStorage.getItem('sipr_token');

            if (token) {
                headers.Authorization = 'Bearer ' + token;
            }

            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
            }

            const response = await fetch(url, Object.assign({}, options, { headers }));
            let payload = null;

            try {
                payload = await response.json();
            } catch (error) {
                payload = null;
            }

            if (!response.ok) {
                const message = payload && (payload.message || payload.error) ? (payload.message || payload.error) : 'Request failed';
                throw new Error(message);
            }

            return payload;
        }

        function renderPending() {
            if (!state.member || state.member.role !== 'admin') {
                pendingList.innerHTML = `
            <div class="item">
              <h4>Admin access required</h4>
              <div class="meta">Only admin accounts can review, approve, or delete pending registrations.</div>
            </div>
          `;
                return;
            }

            if (!state.pending.length) {
                pendingList.innerHTML = `
            <div class="item">
              <h4>No pending registrations</h4>
              <div class="meta">New member requests will appear here as soon as they are submitted.</div>
            </div>
          `;
                return;
            }

            pendingList.innerHTML = state.pending.map((item) => `
          <div class="item">
            <div class="item-head">
              <div>
                <h4>${escapeHtml(item.name)}</h4>
                <div class="meta">${escapeHtml(item.email)}<br>${escapeHtml(item.phone || 'No phone provided')}<br>Invite: ${escapeHtml(item.invite_code || 'N/A')}</div>
              </div>
              <span class="badge pending">${escapeHtml(item.status || 'pending')}</span>
            </div>
            <div class="actions">
              <button class="warn" type="button" data-approve="${item.id}">Approve</button>
              <button class="danger" type="button" data-delete="${item.id}">Delete</button>
            </div>
          </div>
        `).join('');

            pendingList.querySelectorAll('[data-approve]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const id = button.getAttribute('data-approve');
                    const role = prompt('Approve as which role? admin, finance, secretary, member', 'member') || 'member';
                    if (!['admin', 'finance', 'secretary', 'member'].includes(role)) {
                        showMessage('Invalid role selected.', true);
                        return;
                    }

                    try {
                        await requestJson('/api/registrations/' + id + '/approve', {
                            method: 'POST',
                            body: JSON.stringify({ role }),
                        });
                        showMessage('Registration approved.');
                        await loadPending();
                    } catch (error) {
                        showMessage(error.message, true);
                    }
                });
            });

            pendingList.querySelectorAll('[data-delete]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const id = button.getAttribute('data-delete');
                    if (!confirm('Delete this pending registration?')) {
                        return;
                    }

                    try {
                        await requestJson('/api/registrations/' + id, { method: 'DELETE' });
                        showMessage('Registration deleted.');
                        await loadPending();
                    } catch (error) {
                        showMessage(error.message, true);
                    }
                });
            });
        }

        async function loadPending() {
            if (!state.member || state.member.role !== 'admin') {
                state.pending = [];
                renderPending();
                return;
            }

            const payload = await requestJson('/api/pending-registrations');
            state.pending = Array.isArray(payload.data) ? payload.data : [];
            renderPending();
        }

        async function loadSession() {
            if (!state.token) {
                setSession(null, '');
                renderPending();
                return;
            }

            try {
                const member = await requestJson('/api/me');
                setSession(member, state.token);
                await loadPending();
            } catch (error) {
                setSession(null, '');
                showMessage('Session expired. Please sign in again.', true);
                renderPending();
            }
        }

        document.getElementById('login-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                const payload = await requestJson('/api/login', {
                    method: 'POST',
                    body: JSON.stringify({
                        email: el('login-email').value,
                        password: el('login-password').value,
                    }),
                });

                setSession(payload.member, payload.token);
                showMessage('Signed in successfully.');
                await loadPending();
            } catch (error) {
                showMessage(error.message, true);
            }
        });

        document.getElementById('register-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await requestJson('/api/register-request', {
                    method: 'POST',
                    body: JSON.stringify({
                        name: el('reg-name').value,
                        email: el('reg-email').value,
                        phone: el('reg-phone').value,
                        invite_code: el('reg-invite').value,
                        password: el('reg-password').value,
                    }),
                });

                event.target.reset();
                showMessage('Registration request submitted. Awaiting admin approval.');
            } catch (error) {
                showMessage(error.message, true);
            }
        });

        logoutBtn.addEventListener('click', async () => {
            try {
                if (state.token) {
                    await requestJson('/api/logout', { method: 'POST' });
                }
            } catch (error) {
                // Session cleanup should continue even if the token has already expired.
            }

            state.token = '';
            state.member = null;
            localStorage.removeItem('sipr_token');
            setSession(null, '');
            state.pending = [];
            renderPending();
            showMessage('Signed out.');
        });

        roleTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                roleTabs.forEach((item) => item.classList.remove('active'));
                tab.classList.add('active');
                const target = tab.getAttribute('data-role-tab');
                if (target === 'pending') {
                    document.getElementById('pending-panel').scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    document.getElementById('session-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        setSession(null, state.token);
        renderPending();
        loadSession();
    </script>
</body>

</html>