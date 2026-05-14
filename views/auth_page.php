<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es" x-data="{
    currentView: '<?= (isset($_GET['show']) && $_GET['show'] === 'register') ? 'register' : 'login' ?>',
    forgotData: {
        step: 'form',
        username: '',
        loading: false,
        errorMsg: '',
        resetUrl: '',
        copied: false
    }
}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('COMPANY_NAME') ? COMPANY_NAME : 'Farmacia Solei' ?> — Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- NProgress -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.css">
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.min.js"></script>
    <script>
        NProgress.configure({ showSpinner: false, trickleSpeed: 80, minimum: 0.12 });
        NProgress.start();
        window.addEventListener('load', function() { NProgress.done(); });
    </script>
    <style>
        /* NProgress color override to match app purple */
        #nprogress .bar { background: #6f42c1 !important; height: 3px; }
        #nprogress .peg  { box-shadow: 0 0 10px #6f42c1, 0 0 5px #6f42c1 !important; }
    </style>
    <style>
        [x-cloak] { display: none !important; }

        * { font-family: 'Source Sans Pro', sans-serif; }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(160deg, #2d3436 0%, #343a40 50%, #3d4349 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-y: auto;
        }

        /* Subtle diagonal texture overlay */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(135deg, rgba(111,66,193,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(111,66,193,0.12) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
            margin: 2rem 1rem;
            position: relative;
            z-index: 1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 0.75rem;
            padding: 2.25rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 1px 0 rgba(255,255,255,0.06) inset;
        }

        .auth-input {
            width: 100%;
            padding: 0.65rem 0.875rem;
            border-radius: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.14);
            background: rgba(0, 0, 0, 0.2);
            color: #e9ecef;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            outline: none;
            box-sizing: border-box;
        }
        .auth-input::placeholder { color: rgba(255, 255, 255, 0.28); }
        .auth-input:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.2);
            background: rgba(0, 0, 0, 0.28);
        }

        .btn-primary {
            width: 100%;
            padding: 0.7rem;
            border-radius: 0.4rem;
            border: none;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #fff;
            background: #6f42c1;
            box-shadow: 0 2px 8px rgba(111, 66, 193, 0.35);
            letter-spacing: 0.01em;
        }
        .btn-primary:hover {
            background: #5a32a3;
            box-shadow: 0 4px 14px rgba(111, 66, 193, 0.45);
            transform: translateY(-1px);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-register {
            background: #495057;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }
        .btn-register:hover {
            background: #3d4349;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
        }

        .logo-glow {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(111, 66, 193, 0.6);
            box-shadow: 0 0 20px rgba(111, 66, 193, 0.25);
        }

        .fade-enter { animation: fadeIn 0.25s ease forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .link-switch {
            color: #a78bda;
            cursor: pointer;
            font-weight: 600;
            transition: color 0.2s;
            text-decoration: none;
            border: none;
            background: none;
            font-size: inherit;
        }
        .link-switch:hover { color: #c4b5fd; text-decoration: underline; }

        .copyright {
            text-align: center;
            color: rgba(255,255,255,0.25);
            font-size: 0.75rem;
            margin-top: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <!-- Logo & Company Name -->
        <div style="text-align:center; margin-bottom:1.5rem;">
            <img src="http://soleipharma.ct.ws/images/logo.jpg" alt="Logo" class="logo-glow" style="margin:0 auto 0.75rem;">
            <h1 style="color:#e9ecef; font-size:1.6rem; font-weight:700; margin:0; letter-spacing:-0.01em;">
                <?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'Farmacia Solei' ?>
            </h1>
            <?php if (defined('BRANCH') && BRANCH): ?>
                <p style="color:#a78bda; font-size:0.82rem; margin-top:0.2rem; font-weight:600; letter-spacing:0.04em; text-transform:uppercase;">
                    Sucursal <?= htmlspecialchars(BRANCH) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="auth-card">

            <!-- ======================== LOGIN VIEW ======================== -->
            <div x-show="currentView === 'login'" x-cloak class="fade-enter">
                <h2 style="color:#fff; font-size:1.5rem; font-weight:700; text-align:center; margin-bottom:1.25rem;">
                    Iniciar Sesión
                </h2>

                <?php if(isset($error) && !isset($modalAlert)): ?>
                    <div style="background:rgba(239,68,68,0.2); border:1px solid rgba(239,68,68,0.5); color:#fca5a5; padding:0.6rem 1rem; border-radius:0.5rem; margin-bottom:1rem; text-align:center; font-size:0.9rem;">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($modalAlert) && $modalAlert && isset($error)): ?>
                <div id="lockoutAlertModal" style="position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,0.6);">
                    <div style="background:#fff;border-radius:0.75rem;padding:1.5rem;width:100%;max-width:22rem;margin:1rem;text-align:center;">
                        <div style="margin:0 auto 1rem;width:3rem;height:3rem;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                            <svg style="width:1.5rem;height:1.5rem;color:#dc2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3 style="font-size:1.1rem;font-weight:600;color:#111;margin-bottom:0.5rem;">Aviso</h3>
                        <p style="font-size:0.9rem;color:#6b7280;margin-bottom:1.25rem;"><?= $error ?></p>
                        <button type="button" onclick="document.getElementById('lockoutAlertModal').remove()"
                            style="width:100%;padding:0.6rem;border:none;border-radius:0.5rem;background:#dc2626;color:#fff;font-weight:600;cursor:pointer;">
                            Entendido
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <form action="<?= APP_BASE ?>/auth/login" method="POST" style="display:flex;flex-direction:column;gap:1rem;">
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Usuario</label>
                        <input type="text" name="username" id="username" required class="auth-input" placeholder="Tu nombre de usuario" autocomplete="username">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Contraseña</label>
                        <input type="password" name="password" id="password" required class="auth-input" placeholder="Tu contraseña" autocomplete="current-password">
                    </div>
                    <div style="text-align:right;">
                        <button type="button" @click="currentView = 'forgot'" class="link-switch" style="font-size:0.85rem;">
                            ¿Olvidaste tu contraseña?
                        </button>
                    </div>
                    <button type="submit" class="btn-primary">Entrar</button>
                </form>

                <p style="color:rgba(255,255,255,0.6); text-align:center; margin-top:1.25rem; font-size:0.9rem;">
                    ¿No tienes cuenta?
                    <button type="button" @click="currentView = 'register'" class="link-switch">Regístrate aquí</button>
                </p>
            </div>

            <!-- ======================== REGISTER VIEW ======================== -->
            <div x-show="currentView === 'register'" x-cloak class="fade-enter">
                <h2 style="color:#fff; font-size:1.5rem; font-weight:700; text-align:center; margin-bottom:1.25rem;">
                    Registrarse
                </h2>

                <div id="registerAlert" style="display:none; padding:0.6rem 1rem; border-radius:0.5rem; margin-bottom:1rem; text-align:center; font-size:0.9rem;"></div>

                <form id="registerForm" method="POST" style="display:flex;flex-direction:column;gap:0.85rem;">
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Primer Nombre</label>
                        <input type="text" name="first_name1" id="reg_first_name1" required class="auth-input" placeholder="Primer nombre">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Segundo Nombre</label>
                        <input type="text" name="first_name2" id="reg_first_name2" required class="auth-input" placeholder="Segundo nombre">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Primer Apellido</label>
                        <input type="text" name="last_name1" id="reg_last_name1" required class="auth-input" placeholder="Primer apellido">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Segundo Apellido</label>
                        <input type="text" name="last_name2" id="reg_last_name2" required class="auth-input" placeholder="Segundo apellido">
                    </div>
                    <div>
                        <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Contraseña</label>
                        <input type="password" name="password" id="reg_password" required class="auth-input" placeholder="Elige una contraseña segura">
                    </div>
                    <button type="submit" id="registerBtn" class="btn-primary btn-register" style="margin-top:0.25rem;">
                        Registrarse
                    </button>
                </form>

                <p style="color:rgba(255,255,255,0.6); text-align:center; margin-top:1.25rem; font-size:0.9rem;">
                    ¿Ya tienes cuenta?
                    <button type="button" @click="currentView = 'login'" class="link-switch">Inicia sesión</button>
                </p>
            </div>

            <!-- ======================== FORGOT PASSWORD VIEW ======================== -->
            <div x-show="currentView === 'forgot'" x-cloak class="fade-enter"
                 x-data="forgotData" x-init="$watch('$store', () => {})">
                <h2 style="color:#fff; font-size:1.5rem; font-weight:700; text-align:center; margin-bottom:0.35rem;">
                    Recuperar Contraseña
                </h2>
                <p style="color:rgba(200,190,220,0.7); font-size:0.85rem; text-align:center; margin-bottom:1.25rem;">
                    Ingresa tu usuario para generar el enlace de recuperación.
                </p>

                <!-- Step 1: Form -->
                <div x-show="step === 'form'">
                    <div x-show="errorMsg" style="background:rgba(239,68,68,0.2); border:1px solid rgba(239,68,68,0.5); color:#fca5a5; padding:0.5rem 0.75rem; border-radius:0.5rem; margin-bottom:0.75rem; font-size:0.85rem; text-align:center;" x-text="errorMsg"></div>

                    <form @submit.prevent="
                        loading = true;
                        errorMsg = '';
                        resetUrl = '';
                        const fd = new FormData();
                        fd.append('username', username);
                        fetch('<?= APP_BASE ?>/auth/requestReset', { method:'POST', body: fd })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) { resetUrl = data.reset_url; step = 'link'; }
                                else { errorMsg = data.message; }
                            })
                            .catch(() => { errorMsg = 'Error de conexión.'; })
                            .finally(() => { loading = false; });
                    " style="display:flex;flex-direction:column;gap:1rem;">
                        <div>
                            <label style="display:block;color:rgba(255,255,255,0.85);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Usuario</label>
                            <input type="text" x-model="username" required class="auth-input" placeholder="Ingresa tu nombre de usuario">
                        </div>
                        <button type="submit" :disabled="loading" class="btn-primary" :style="loading ? 'opacity:0.6;cursor:not-allowed' : ''">
                            <span x-show="!loading">Generar Enlace</span>
                            <span x-show="loading">Procesando…</span>
                        </button>
                    </form>
                </div>

                <!-- Step 2: Link generated -->
                <div x-show="step === 'link'" style="display:flex;flex-direction:column;gap:1rem;">
                    <div style="background:rgba(111,193,111,0.1);border:1px solid rgba(100,170,100,0.3);border-radius:0.4rem;padding:0.75rem;text-align:center;">
                        <p style="color:#a3d9a3;font-size:0.85rem;font-weight:600;margin-bottom:0.25rem;">Enlace generado correctamente</p>
                        <p style="color:rgba(200,190,220,0.7);font-size:0.75rem;">
                            Copia el enlace y pégalo en tu navegador.<br>
                            <span style="color:#a78bda;font-weight:700;">Expira en 30 minutos.</span>
                        </p>
                    </div>
                    <div>
                        <label style="display:block;color:rgba(233,236,239,0.8);font-weight:600;font-size:0.85rem;margin-bottom:0.35rem;">Enlace de recuperación</label>
                        <div style="display:flex;gap:0.5rem;">
                            <input type="text" :value="resetUrl" readonly class="auth-input" style="flex:1;font-size:0.75rem;cursor:text;" @click="$event.target.select()">
                            <button type="button" @click="navigator.clipboard.writeText(resetUrl).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                                style="padding:0.5rem 0.85rem;border-radius:0.4rem;border:none;font-weight:600;font-size:0.85rem;cursor:pointer;transition:all 0.2s;"
                                :style="copied ? 'background:#3d8b3d;color:#fff' : 'background:#6f42c1;color:#fff'">
                                <span x-show="!copied">Copiar</span>
                                <span x-show="copied">✓</span>
                            </button>
                        </div>
                    </div>
                    <p style="color:rgba(200,190,220,0.6);font-size:0.75rem;text-align:center;">
                        Pega el enlace en la barra de tu navegador y presiona <kbd style="background:rgba(255,255,255,0.1);padding:0.1rem 0.35rem;border-radius:0.25rem;color:#e9ecef;font-size:0.7rem;">Enter</kbd>
                    </p>
                    <button type="button" @click="step='form'; username=''; resetUrl=''; errorMsg='';"
                        style="width:100%;padding:0.6rem;border-radius:0.4rem;border:none;background:rgba(111,66,193,0.2);color:#c4b5fd;cursor:pointer;font-size:0.85rem;transition:background 0.2s;"
                        onmouseover="this.style.background='rgba(111,66,193,0.35)'" onmouseout="this.style.background='rgba(111,66,193,0.2)'">
                        Volver
                    </button>
                </div>

                <p style="color:rgba(255,255,255,0.6); text-align:center; margin-top:1.25rem; font-size:0.9rem;">
                    <button type="button" @click="currentView = 'login'; step='form'; username=''; resetUrl=''; errorMsg='';" class="link-switch">
                        ← Volver al inicio de sesión
                    </button>
                </p>
            </div>

        </div>

        <p class="copyright">© <?= date('Y') ?> <?= defined('COMPANY_NAME') ? htmlspecialchars(COMPANY_NAME) : 'Farmacia Solei' ?>. Todos los derechos reservados.</p>
    </div>

    <!-- Register AJAX script -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(function () {
            $("#registerForm").on("submit", function (e) {
                e.preventDefault();
                $("#registerBtn").prop("disabled", true).text("Espere por favor...");
                $("#registerAlert").hide();

                $.ajax({
                    url: "<?= APP_BASE ?>/auth/registerAjax",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json"
                })
                .done(function (res) {
                    var alert = $("#registerAlert");
                    if (res.success) {
                        alert.css({
                            display: 'block',
                            background: 'rgba(34,197,94,0.2)',
                            border: '1px solid rgba(34,197,94,0.5)',
                            color: '#86efac'
                        }).html("Registro exitoso. Tu usuario es: <strong>" +
                            $('<div>').text(res.username).html() + "</strong>. No comparta su usuario con nadie.");
                        $("#registerForm")[0].reset();
                    } else {
                        alert.css({
                            display: 'block',
                            background: 'rgba(239,68,68,0.2)',
                            border: '1px solid rgba(239,68,68,0.5)',
                            color: '#fca5a5'
                        }).text(res.message);
                    }
                })
                .fail(function () {
                    $("#registerAlert").css({
                        display: 'block',
                        background: 'rgba(239,68,68,0.2)',
                        border: '1px solid rgba(239,68,68,0.5)',
                        color: '#fca5a5'
                    }).text("Error de comunicación con el servidor.");
                })
                .always(function () {
                    $("#registerBtn").prop("disabled", false).text("Registrarse");
                });
            });
        });
    </script>
</body>
</html>
