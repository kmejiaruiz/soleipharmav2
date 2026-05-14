<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Inicial — Farmacia Solei</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ───────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --purple-900: #1e0a3c;
            --purple-800: #2d1158;
            --purple-700: #3d1a7a;
            --purple-600: #5b21b6;
            --purple-500: #7c3aed;
            --purple-400: #a855f7;
            --purple-300: #c084fc;
            --purple-200: #e9d5ff;
            --indigo-600: #4f46e5;
            --indigo-500: #6366f1;
            --pink-500:   #ec4899;
            --green-500:  #22c55e;
            --green-400:  #4ade80;
            --red-400:    #f87171;
            --yellow-400: #facc15;
            --white:      #ffffff;
            --glass-bg:   rgba(255,255,255,0.07);
            --glass-border: rgba(255,255,255,0.15);
            --text-primary:   #f3f0ff;
            --text-secondary: rgba(243,240,255,0.7);
            --input-bg:   rgba(255,255,255,0.08);
            --input-border: rgba(255,255,255,0.2);
            --radius:     16px;
            --radius-sm:  8px;
            --shadow-lg:  0 25px 60px rgba(0,0,0,0.45);
            --transition: 0.3s cubic-bezier(0.4,0,0.2,1);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--purple-900) 0%, #0f0728 40%, #1a0938 70%, #0d1b4b 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 24px 16px 48px;
            position: relative;
            overflow-x: hidden;
        }

        /* Decorative orbs */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
            z-index: 0;
        }
        body::before {
            width: 500px; height: 500px;
            background: radial-gradient(circle, var(--purple-500), transparent);
            top: -100px; left: -100px;
        }
        body::after {
            width: 400px; height: 400px;
            background: radial-gradient(circle, var(--indigo-500), transparent);
            bottom: -80px; right: -80px;
        }

        /* ── Branding ──────────────────────────────────────────────── */
        .branding {
            position: relative; z-index: 1;
            text-align: center;
            margin-bottom: 36px;
            animation: fadeDown 0.6s ease both;
        }
        .branding .logo-ring {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--purple-500), var(--indigo-500));
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 14px;
            box-shadow: 0 0 0 6px rgba(124,58,237,0.2), 0 8px 32px rgba(0,0,0,0.4);
        }
        .branding .logo-ring svg { width: 36px; height: 36px; color: white; }
        .branding h1 { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.5px; }
        .branding p  { color: var(--text-secondary); font-size: 0.85rem; margin-top: 4px; }

        /* ── Wizard card ───────────────────────────────────────────── */
        .wizard-card {
            position: relative; z-index: 1;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px) saturate(1.5);
            border-radius: var(--radius);
            width: 100%;
            max-width: 680px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: fadeUp 0.7s ease both 0.1s;
        }

        /* ── Progress bar ──────────────────────────────────────────── */
        .wizard-progress {
            padding: 24px 28px 20px;
            background: rgba(255,255,255,0.04);
            border-bottom: 1px solid var(--glass-border);
        }
        .progress-steps {
            display: flex;
            align-items: center;
            gap: 0;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 18px;
            left: 50%;
            right: -50%;
            height: 2px;
            background: var(--glass-border);
            z-index: 0;
            transition: background var(--transition);
        }
        .step-item.done::after   { background: var(--purple-500); }
        .step-item.active::after { background: linear-gradient(90deg, var(--purple-500), var(--glass-border)); }

        .step-bubble {
            width: 36px; height: 36px;
            border-radius: 50%;
            border: 2px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
            color: var(--text-secondary);
            background: var(--glass-bg);
            position: relative; z-index: 1;
            transition: all var(--transition);
        }
        .step-item.active .step-bubble {
            background: linear-gradient(135deg, var(--purple-500), var(--indigo-500));
            border-color: transparent;
            color: white;
            box-shadow: 0 0 0 4px rgba(124,58,237,0.3);
        }
        .step-item.done .step-bubble {
            background: linear-gradient(135deg, var(--purple-600), var(--purple-500));
            border-color: transparent;
            color: white;
        }
        .step-label {
            font-size: 0.65rem;
            color: var(--text-secondary);
            margin-top: 6px;
            font-weight: 500;
            text-align: center;
            transition: color var(--transition);
            max-width: 80px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .step-item.active .step-label { color: var(--purple-300); }
        .step-item.done  .step-label  { color: var(--purple-400); }

        /* ── Step content ──────────────────────────────────────────── */
        .wizard-body { padding: 32px 28px; }

        .step-panel {
            display: none;
            animation: fadeIn 0.35s ease both;
        }
        .step-panel.active { display: block; }

        .step-panel h2 {
            font-size: 1.25rem; font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .step-panel .subtitle {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 24px;
            line-height: 1.5;
        }

        /* ── Welcome panel ─────────────────────────────────────────── */
        .welcome-graphic {
            text-align: center;
            padding: 16px 0 28px;
        }
        .welcome-graphic .big-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: pulse 2.5s ease infinite;
        }
        .welcome-graphic h3 {
            font-size: 1.4rem; font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        .welcome-graphic p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            max-width: 440px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .welcome-steps {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .welcome-step-chip {
            background: rgba(124,58,237,0.15);
            border: 1px solid rgba(124,58,237,0.3);
            border-radius: 100px;
            padding: 6px 14px;
            font-size: 0.78rem;
            color: var(--purple-300);
            font-weight: 500;
        }

        /* ── Form elements ─────────────────────────────────────────── */
        .form-grid {
            display: grid;
            gap: 16px;
        }
        .form-grid.grid-2 { grid-template-columns: 1fr 1fr; }
        @media (max-width: 520px) { .form-grid.grid-2 { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-group label span.req { color: var(--pink-500); }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-family: inherit;
            font-size: 0.92rem;
            padding: 11px 14px;
            width: 100%;
            transition: border-color var(--transition), box-shadow var(--transition), background var(--transition);
            outline: none;
        }
        .form-control::placeholder { color: rgba(243,240,255,0.3); }
        .form-control:focus {
            border-color: var(--purple-400);
            box-shadow: 0 0 0 3px rgba(168,85,247,0.2);
            background: rgba(255,255,255,0.11);
        }
        .form-control:disabled { opacity: 0.5; cursor: not-allowed; }

        select.form-control option { background: #1e0a3c; color: white; }

        .input-with-btn { position: relative; }
        .input-with-btn .form-control { padding-right: 48px; }
        .input-with-btn .eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-secondary); padding: 4px;
            transition: color var(--transition);
        }
        .input-with-btn .eye-btn:hover { color: var(--purple-300); }

        /* ── Connection test badge ─────────────────────────────────── */
        .conn-test-wrap { display: flex; gap: 10px; align-items: flex-end; }
        .conn-test-wrap .form-control-group { flex: 1; }

        .badge-result {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            font-size: 0.82rem;
            font-weight: 500;
            margin-top: 12px;
            animation: fadeIn 0.3s ease;
        }
        .badge-result.success { background: rgba(34,197,94,0.12); border: 1px solid rgba(74,222,128,0.35); color: var(--green-400); display: flex; }
        .badge-result.error   { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.35); color: var(--red-400); display: flex; }

        /* ── Review section ────────────────────────────────────────── */
        .review-grid {
            display: grid;
            gap: 10px;
        }
        .review-block {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
        }
        .review-block h4 {
            font-size: 0.7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--purple-300);
            margin-bottom: 8px;
        }
        .review-row {
            display: flex; justify-content: space-between; align-items: center;
            font-size: 0.84rem;
            padding: 3px 0;
        }
        .review-row span:first-child { color: var(--text-secondary); }
        .review-row span:last-child  { color: var(--text-primary); font-weight: 500; max-width: 55%; text-align: right; word-break: break-all; }

        /* ── Buttons ───────────────────────────────────────────────── */
        .wizard-footer {
            padding: 20px 28px 28px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            border-top: 1px solid var(--glass-border);
        }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px 22px;
            border-radius: var(--radius-sm);
            font-size: 0.9rem; font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            transition: all var(--transition);
            user-select: none;
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--glass-border);
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.07); color: var(--text-primary); }

        .btn-primary {
            background: linear-gradient(135deg, var(--purple-500), var(--indigo-600));
            color: white;
            box-shadow: 0 4px 20px rgba(124,58,237,0.4);
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--purple-400), var(--indigo-500));
            box-shadow: 0 6px 28px rgba(124,58,237,0.55);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn-success {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
            box-shadow: 0 4px 20px rgba(22,163,74,0.4);
        }
        .btn-success:hover { background: linear-gradient(135deg, #22c55e, #16a34a); box-shadow: 0 6px 28px rgba(22,163,74,0.5); transform: translateY(-1px); }
        .btn-success:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.82rem;
            white-space: nowrap;
        }

        /* ── Spinner ───────────────────────────────────────────────── */
        .spinner {
            width: 18px; height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            display: none;
        }
        .btn.loading .spinner  { display: inline-block; }
        .btn.loading .btn-text { display: none; }

        /* ── Error / Success states ────────────────────────────────── */
        .field-error {
            font-size: 0.75rem;
            color: var(--red-400);
            margin-top: 2px;
            display: none;
        }
        .form-group.has-error .form-control { border-color: var(--red-400); }
        .form-group.has-error .field-error  { display: block; }

        /* Final success overlay */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10,4,24,0.85);
            backdrop-filter: blur(12px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            animation: fadeIn 0.5s ease;
        }
        .success-overlay.show { display: flex; }
        .success-box {
            background: linear-gradient(135deg, rgba(30,10,60,0.95), rgba(15,7,40,0.98));
            border: 1px solid rgba(168,85,247,0.4);
            border-radius: 20px;
            padding: 48px 40px;
            text-align: center;
            max-width: 420px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.6);
            animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
        }
        .success-box .checkmark {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--green-500), #15803d);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 0 0 12px rgba(34,197,94,0.15);
            font-size: 36px;
            animation: popIn 0.6s cubic-bezier(0.34,1.56,0.64,1) 0.2s both;
        }
        .success-box h2 { font-size: 1.4rem; font-weight: 800; color: var(--text-primary); margin-bottom: 10px; }
        .success-box p  { color: var(--text-secondary); font-size: 0.9rem; line-height: 1.6; }
        .success-box .redirect-bar {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 24px;
            overflow: hidden;
        }
        .success-box .redirect-progress {
            height: 100%;
            background: linear-gradient(90deg, var(--purple-500), var(--green-500));
            animation: progress-fill 3s linear forwards;
        }

        /* ── Animations ────────────────────────────────────────────── */
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: none; } }
        @keyframes fadeUp   { from { opacity: 0; transform: translateY(24px);  } to { opacity: 1; transform: none; } }
        @keyframes fadeIn   { from { opacity: 0; } to { opacity: 1; } }
        @keyframes spin     { to   { transform: rotate(360deg); } }
        @keyframes pulse    { 0%,100% { transform: scale(1); } 50% { transform: scale(1.08); } }
        @keyframes popIn    { from { opacity: 0; transform: scale(0.7); } to { opacity: 1; transform: scale(1); } }
        @keyframes progress-fill { from { width: 0; } to { width: 100%; } }
    </style>
</head>
<body data-base="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>">

<!-- Branding -->
<div class="branding">
    <div class="logo-ring">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9l-6-6zm0 0v6h6M9 14h6M9 17h3" />
        </svg>
    </div>
    <h1>Farmacia Solei</h1>
    <p>Asistente de configuración inicial</p>
</div>

<!-- Wizard Card -->
<div class="wizard-card">

    <!-- Progress -->
    <div class="wizard-progress">
        <div class="progress-steps">
            <div class="step-item active" id="step-nav-1">
                <div class="step-bubble">1</div>
                <span class="step-label">Bienvenida</span>
            </div>
            <div class="step-item" id="step-nav-2">
                <div class="step-bubble">2</div>
                <span class="step-label">Base de Datos</span>
            </div>
            <div class="step-item" id="step-nav-3">
                <div class="step-bubble">3</div>
                <span class="step-label">Empresa</span>
            </div>
            <div class="step-item" id="step-nav-4">
                <div class="step-bubble">4</div>
                <span class="step-label">Superadmin</span>
            </div>
            <div class="step-item" id="step-nav-5">
                <div class="step-bubble">5</div>
                <span class="step-label">Revisar</span>
            </div>
        </div>
    </div>

    <!-- Steps -->
    <div class="wizard-body">

        <!-- ── PASO 1: Bienvenida ── -->
        <div class="step-panel active" id="panel-1">
                <div class="welcome-graphic">
                <div class="big-icon">&#128138;</div>
                <h3>Bienvenido al Sistema</h3>
                <p>
                    Este asistente te guiará para configurar <strong>Farmacia Solei</strong> por primera vez.
                    Solo necesitas seguir los pasos y el sistema quedará listo para operar.
                </p>
                <div class="welcome-steps">
                    <span class="welcome-step-chip">Configurar BD</span>
                    <span class="welcome-step-chip">Definir sucursal</span>
                    <span class="welcome-step-chip">Crear superadmin</span>
                    <span class="welcome-step-chip">Inicializar</span>
                </div>
            </div>
        </div>

        <!-- ── PASO 2: Base de Datos ── -->
        <div class="step-panel" id="panel-2">
            <h2>Base de Datos</h2>
            <p class="subtitle">Ingresa las credenciales de tu servidor MySQL. Si la base de datos no existe, el sistema la creará automáticamente.</p>

            <div class="form-grid">
                <div class="form-grid grid-2">
                    <div class="form-group" id="fg-db_host">
                        <label>Servidor (Host) <span class="req">*</span></label>
                        <input id="db_host" class="form-control" type="text" value="localhost" placeholder="localhost">
                        <span class="field-error" id="err-db_host">Campo requerido</span>
                    </div>
                    <div class="form-group" id="fg-db_port">
                        <label>Puerto <span class="req">*</span></label>
                        <input id="db_port" class="form-control" type="number" value="3306" placeholder="3306">
                        <span class="field-error" id="err-db_port">Campo requerido</span>
                    </div>
                </div>
                <div class="form-group" id="fg-db_name">
                    <label>Nombre de la Base de Datos <span class="req">*</span></label>
                    <input id="db_name" class="form-control" type="text" value="pharmacy" placeholder="pharmacy">
                    <span class="field-error" id="err-db_name">Campo requerido</span>
                </div>
                <div class="form-grid grid-2">
                    <div class="form-group" id="fg-db_user">
                        <label>Usuario MySQL <span class="req">*</span></label>
                        <input id="db_user" class="form-control" type="text" value="root" placeholder="root">
                        <span class="field-error" id="err-db_user">Campo requerido</span>
                    </div>
                    <div class="form-group" id="fg-db_pass">
                        <label>Contraseña MySQL</label>
                        <div class="input-with-btn">
                            <input id="db_pass" class="form-control" type="password" placeholder="(dejar vacío si no hay)">
                            <button type="button" class="eye-btn" onclick="togglePass('db_pass',this)" tabindex="-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="button" class="btn btn-ghost btn-sm" id="btn-test-conn" onclick="testConnection()">
                    <span class="btn-text">🔌 Probar Conexión</span>
                    <span class="spinner"></span>
                </button>
                <div class="badge-result" id="conn-result"></div>
            </div>
        </div>

        <!-- ── PASO 3: Empresa y Sucursal ── -->
        <div class="step-panel" id="panel-3">
            <h2>Empresa y Sucursal</h2>
            <p class="subtitle">Esta información aparecerá en los recibos y dentro del sistema.</p>

            <div class="form-grid">
                <div class="form-group" id="fg-company_name">
                    <label>Nombre de la Empresa <span class="req">*</span></label>
                    <input id="company_name" class="form-control" type="text" value="Farmacia Solei" placeholder="Farmacia Solei">
                    <span class="field-error" id="err-company_name">Campo requerido</span>
                </div>
                <div class="form-group" id="fg-branch">
                    <label>Sucursal <span class="req">*</span></label>
                    <select id="branch_select" class="form-control" onchange="toggleBranchCustom()">
                        <option value="">— Selecciona una sucursal —</option>
                        <option value="Sucursal Leon">Sucursal León</option>
                        <option value="Sucursal Managua">Sucursal Managua</option>
                        <option value="Sucursal Chinandega">Sucursal Chinandega</option>
                        <option value="Sucursal Masaya">Sucursal Masaya</option>
                        <option value="__custom__">Otra (especificar)…</option>
                    </select>
                    <input id="branch_custom" class="form-control" type="text" placeholder="Nombre de la sucursal" style="display:none;margin-top:8px;">
                    <input id="branch" type="hidden" value="">
                    <span class="field-error" id="err-branch">Selecciona o escribe una sucursal</span>
                </div>
                <div class="form-group" id="fg-timezone">
                    <label>Zona Horaria <span class="req">*</span></label>
                    <select id="timezone" class="form-control">
                        <option value="America/Managua" selected>America/Managua (GMT-6, Nicaragua)</option>
                        <option value="America/Costa_Rica">America/Costa_Rica (GMT-6)</option>
                        <option value="America/Mexico_City">America/Mexico_City (GMT-6)</option>
                        <option value="America/El_Salvador">America/El_Salvador (GMT-6)</option>
                        <option value="America/Guatemala">America/Guatemala (GMT-6)</option>
                        <option value="America/Tegucigalpa">America/Tegucigalpa (GMT-6)</option>
                        <option value="America/Bogota">America/Bogota (GMT-5)</option>
                        <option value="America/Lima">America/Lima (GMT-5)</option>
                        <option value="America/New_York">America/New_York (GMT-5)</option>
                        <option value="America/Chicago">America/Chicago (GMT-6)</option>
                        <option value="America/Los_Angeles">America/Los_Angeles (GMT-8)</option>
                        <option value="America/Santiago">America/Santiago (GMT-3/-4)</option>
                        <option value="America/Sao_Paulo">America/Sao_Paulo (GMT-3)</option>
                        <option value="Europe/Madrid">Europe/Madrid (GMT+1/+2)</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                <div class="form-group" id="fg-low_stock">
                    <label>Umbral de Stock Bajo <span class="req">*</span></label>
                    <input id="low_stock" class="form-control" type="number" value="9" min="1" max="999">
                    <span style="font-size:0.75rem;color:var(--text-secondary);margin-top:4px;">
                        Se marcará como "stock bajo" cuando un producto tenga esta cantidad o menos.
                    </span>
                </div>
            </div>
        </div>

        <!-- ── PASO 4: Superadmin ── -->
        <div class="step-panel" id="panel-4">
            <h2>Cuenta Superadmin</h2>
            <p class="subtitle">Esta será la cuenta principal del sistema con todos los privilegios. Guarda bien estas credenciales.</p>

            <div class="form-grid">
                <div class="form-grid grid-2">
                    <div class="form-group" id="fg-admin_fn">
                        <label>Primer Nombre <span class="req">*</span></label>
                        <input id="admin_fn" class="form-control" type="text" placeholder="María"
                               oninput="generateUsername()">
                        <span class="field-error" id="err-admin_fn">Campo requerido</span>
                    </div>
                    <div class="form-group" id="fg-admin_ln">
                        <label>Primer Apellido <span class="req">*</span></label>
                        <input id="admin_ln" class="form-control" type="text" placeholder="García"
                               oninput="generateUsername()">
                        <span class="field-error" id="err-admin_ln">Campo requerido</span>
                    </div>
                </div>
                <div class="form-group" id="fg-admin_user">
                    <label>Nombre de Usuario <span class="req">*</span></label>
                    <input id="admin_user" class="form-control" type="text" placeholder="mgarcia"
                           autocomplete="off" readonly
                           style="opacity:0.7;cursor:not-allowed;">
                    <span id="user-hint" style="font-size:0.73rem;color:var(--purple-300);margin-top:3px;display:none;">
                        Generado a partir del nombre y apellido
                    </span>
                    <span class="field-error" id="err-admin_user">Campo requerido</span>
                </div>
                <div class="form-group" id="fg-admin_pass">
                    <label>Contraseña <span class="req">*</span> <span style="font-weight:400;text-transform:none;letter-spacing:0;">(mín. 6 caracteres)</span></label>
                    <div class="input-with-btn">
                        <input id="admin_pass" class="form-control" type="password" placeholder="••••••••" autocomplete="new-password">
                        <button type="button" class="eye-btn" onclick="togglePass('admin_pass',this)" tabindex="-1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                    <span class="field-error" id="err-admin_pass">Mínimo 6 caracteres</span>
                </div>
            </div>
        </div>

        <!-- ── PASO 5: Revisión ── -->
        <div class="step-panel" id="panel-5">
            <h2>Revisar y Confirmar</h2>
            <p class="subtitle">Verifica que todo sea correcto antes de inicializar el sistema. Este proceso creará la base de datos y ejecutará todas las migraciones.</p>

            <div class="review-grid" id="review-content">
                <!-- populated by JS -->
            </div>
        </div>

    </div><!-- /.wizard-body -->

    <!-- Footer con botones de navegación -->
    <div class="wizard-footer">
        <button type="button" class="btn btn-ghost" id="btn-back" onclick="prevStep()" style="display:none;">
            ← Atrás
        </button>
        <div style="flex:1"></div>
        <button type="button" class="btn btn-primary" id="btn-next" onclick="nextStep()">
            <span class="btn-text">Empezar →</span>
        </button>
        <button type="button" class="btn btn-success" id="btn-finish" onclick="finishSetup()" style="display:none;">
            <span class="btn-text">Inicializar Sistema</span>
            <span class="spinner"></span>
        </button>
    </div>

</div><!-- /.wizard-card -->

<!-- Success overlay -->
<div class="success-overlay" id="success-overlay">
    <div class="success-box">
        <div class="checkmark">&#10003;</div>
        <h2>Sistema Listo</h2>
        <p>La configuración se completó correctamente.<br>Serás redirigido a la aplicación en un momento.</p>
        <div class="redirect-bar"><div class="redirect-progress"></div></div>
    </div>
</div>

<script>
// ── Estado del wizard ──────────────────────────────────────────────────────
let currentStep = 1;
const totalSteps = 5;

// ── Navegación ─────────────────────────────────────────────────────────────
function goToStep(n) {
    // Actualizar paneles
    document.querySelectorAll('.step-panel').forEach((p, i) => {
        p.classList.toggle('active', i + 1 === n);
    });
    // Actualizar nav
    document.querySelectorAll('.step-item').forEach((el, i) => {
        el.classList.remove('active', 'done');
        if (i + 1 < n)  el.classList.add('done');
        if (i + 1 === n) el.classList.add('active');
    });
    // Botones
    const btnBack   = document.getElementById('btn-back');
    const btnNext   = document.getElementById('btn-next');
    const btnFinish = document.getElementById('btn-finish');

    btnBack.style.display   = n > 1 ? 'inline-flex' : 'none';
    btnNext.style.display   = n < totalSteps ? 'inline-flex' : 'none';
    btnFinish.style.display = n === totalSteps ? 'inline-flex' : 'none';

    // Label del botón Siguiente
    const labels = ['Empezar →', 'Siguiente →', 'Siguiente →', 'Siguiente →'];
    const btnText = btnNext.querySelector('.btn-text');
    if (btnText && labels[n - 1]) btnText.textContent = labels[n - 1];

    if (n === totalSteps) buildReview();
    currentStep = n;
}

function nextStep() {
    if (!validateStep(currentStep)) return;
    if (currentStep < totalSteps) goToStep(currentStep + 1);
}

function prevStep() {
    if (currentStep > 1) goToStep(currentStep - 1);
}

// ── Validación por paso ────────────────────────────────────────────────────
function validateStep(n) {
    let ok = true;
    if (n === 2) {
        ok = requireField('db_host') && requireField('db_port') && requireField('db_name') && requireField('db_user');
    }
    if (n === 3) {
        resolveBranch();
        ok = requireField('company_name')
          && requireFieldValue('branch', document.getElementById('branch').value, 'err-branch', 'fg-branch');
    }
    if (n === 4) {
        ok = requireField('admin_fn') && requireField('admin_ln') && requireField('admin_user');
        const pass = document.getElementById('admin_pass').value;
        if (pass.length < 6) {
            setError('fg-admin_pass', 'err-admin_pass', true);
            ok = false;
        } else {
            setError('fg-admin_pass', 'err-admin_pass', false);
        }
    }
    return ok;
}

function requireField(id) {
    const el = document.getElementById(id);
    const empty = !el || el.value.trim() === '';
    setError('fg-' + id, 'err-' + id, empty);
    return !empty;
}

function requireFieldValue(id, val, errId, fgId) {
    const empty = !val || val.trim() === '';
    setError(fgId, errId, empty);
    return !empty;
}

function setError(fgId, errId, hasErr) {
    const fg  = document.getElementById(fgId);
    const err = document.getElementById(errId);
    if (fg)  fg.classList.toggle('has-error', hasErr);
    if (err) err.style.display = hasErr ? 'block' : 'none';
}

// ── Sucursal ───────────────────────────────────────────────────────────────
function toggleBranchCustom() {
    const sel    = document.getElementById('branch_select');
    const custom = document.getElementById('branch_custom');
    const hidden = document.getElementById('branch');
    if (sel.value === '__custom__') {
        custom.style.display = 'block';
        hidden.value = '';
        custom.focus();
    } else {
        custom.style.display = 'none';
        hidden.value = sel.value;
    }
    // clear error
    setError('fg-branch', 'err-branch', false);
}

function resolveBranch() {
    const sel    = document.getElementById('branch_select');
    const custom = document.getElementById('branch_custom');
    const hidden = document.getElementById('branch');
    if (sel.value === '__custom__') {
        hidden.value = custom.value.trim();
    } else {
        hidden.value = sel.value;
    }
}

// ── Toggle password visibility ─────────────────────────────────────────────
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`
        : `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>`;
}

// -- Generacion automatica de usuario ------------------------------------------
let userEdited = false;

function removeAccents(str) {
    return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

function generateUsername() {
    const fn = document.getElementById('admin_fn').value.trim();
    const ln = document.getElementById('admin_ln').value.trim();

    const hint = document.getElementById('user-hint');

    if (!fn && !ln) {
        document.getElementById('admin_user').value = '';
        hint.style.display = 'none';
        return;
    }

    const firstLetter = fn.length > 0 ? fn.charAt(0) : '';
    const lastName    = ln.replace(/\s+/g, '');
    const generated   = removeAccents((firstLetter + lastName).toLowerCase()).replace(/[^a-z0-9]/g, '');

    document.getElementById('admin_user').value = generated;
    hint.style.display = generated ? 'block' : 'none';
    setError('fg-admin_user', 'err-admin_user', false);
}

// ── Probar Conexión MySQL ──────────────────────────────────────────────────
async function testConnection() {
    const btn    = document.getElementById('btn-test-conn');
    const result = document.getElementById('conn-result');
    btn.classList.add('loading');
    btn.disabled = true;

    // Reset: quitar clases y limpiar el estilo inline para que el CSS tome control
    result.className = 'badge-result';
    result.removeAttribute('style');   // <-- FIX: sin esto, display:none inline bloquea el CSS
    result.innerHTML = '';

    const body = new URLSearchParams({
        db_host: document.getElementById('db_host').value,
        db_port: document.getElementById('db_port').value,
        db_user: document.getElementById('db_user').value,
        db_pass: document.getElementById('db_pass').value,
    });

    try {
        // URL relativa para evitar problemas con 127.0.0.1 vs localhost
        const base = document.body.dataset.base;
        const res  = await fetch(base + '/setup/testConnection', { method: 'POST', body });
        const data = await res.json();
        result.classList.add(data.success ? 'success' : 'error');
        result.innerHTML = (data.success ? '+ ' : 'x ') + data.message;
    } catch (e) {
        result.classList.add('error');
        result.innerHTML = 'x Error de red: ' + e.message;
    }

    btn.classList.remove('loading');
    btn.disabled = false;
}

// ── Construir revisión ─────────────────────────────────────────────────────
function buildReview() {
    resolveBranch();
    const rows = [
        { section: '🗄️ Base de Datos', items: [
            ['Servidor', document.getElementById('db_host').value + ':' + document.getElementById('db_port').value],
            ['Base de datos', document.getElementById('db_name').value],
            ['Usuario', document.getElementById('db_user').value],
            ['Contraseña', document.getElementById('db_pass').value ? '••••••••' : '(sin contraseña)'],
        ]},
        { section: '🏪 Empresa', items: [
            ['Nombre', document.getElementById('company_name').value],
            ['Sucursal', document.getElementById('branch').value],
            ['Zona horaria', document.getElementById('timezone').value],
            ['Umbral stock bajo', document.getElementById('low_stock').value + ' unidades'],
        ]},
        { section: '👤 Superadmin', items: [
            ['Nombre', document.getElementById('admin_fn').value + ' ' + document.getElementById('admin_ln').value],
            ['Usuario', document.getElementById('admin_user').value],
            ['Contraseña', '••••••••'],
        ]},
    ];

    let html = '';
    rows.forEach(block => {
        html += `<div class="review-block"><h4>${block.section}</h4>`;
        block.items.forEach(([k, v]) => {
            html += `<div class="review-row"><span>${k}</span><span>${escHtml(v)}</span></div>`;
        });
        html += `</div>`;
    });
    document.getElementById('review-content').innerHTML = html;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Finalizar Setup ────────────────────────────────────────────────────
async function finishSetup() {
    const btn = document.getElementById('btn-finish');
    btn.classList.add('loading');
    btn.disabled = true;
    document.getElementById('btn-back').disabled = true;

    resolveBranch();

    // Detectar la base de la app dinámicamente desde la URL actual
    // Ej: /soleipharmav2leon/setup/index → base = /soleipharmav2leon
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const base = pathParts.length > 0 ? '/' + pathParts[0] : '';

    const body = new URLSearchParams({
        db_host:      document.getElementById('db_host').value,
        db_port:      document.getElementById('db_port').value,
        db_name:      document.getElementById('db_name').value,
        db_user:      document.getElementById('db_user').value,
        db_pass:      document.getElementById('db_pass').value,
        company_name: document.getElementById('company_name').value,
        branch:       document.getElementById('branch').value,
        timezone:     document.getElementById('timezone').value,
        low_stock:    document.getElementById('low_stock').value,
        admin_fn:     document.getElementById('admin_fn').value,
        admin_ln:     document.getElementById('admin_ln').value,
        admin_user:   document.getElementById('admin_user').value,
        admin_pass:   document.getElementById('admin_pass').value,
    });

    try {
        const res  = await fetch(base + '/setup/save', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            document.getElementById('success-overlay').classList.add('show');
            setTimeout(() => { window.location.href = base + '/'; }, 3200);
        } else {
            btn.classList.remove('loading');
            btn.disabled = false;
            document.getElementById('btn-back').disabled = false;
            alert('Error: ' + data.message);
        }
    } catch (e) {
        btn.classList.remove('loading');
        btn.disabled = false;
        document.getElementById('btn-back').disabled = false;
        alert('Error de red. Verifica que el servidor esté activo.');
    }
}

// Init
goToStep(1);
</script>
</body>
</html>
