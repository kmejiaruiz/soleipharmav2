<?php
// views/admin/my_profile.php
$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? '';
$roleLabel = ['superadmin' => 'Súper Administrador', 'admin' => 'Administrador', 'user' => 'Usuario'][$role] ?? ucfirst($role);
$roleColor = ['superadmin' => '#6f42c1', 'admin' => '#007bff', 'user' => '#28a745'][$role] ?? '#343a40';
?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><i class="fas fa-user-circle"></i> Mi Perfil</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item active">Mi Perfil</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <!-- Profile Card -->
            <div class="col-lg-4 col-md-5 col-12 mb-4">
                <div class="card" style="border:none;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.07);overflow:hidden;">
                    <!-- Gradient Header -->
                    <div style="background:linear-gradient(135deg, #6f42c1, #343a40); padding:40px 20px; text-align:center;">
                        <div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,0.2);border:3px solid rgba(255,255,255,0.5);display:flex;align-items:center;justify-content:center;margin:0 auto 15px;font-size:36px;font-weight:700;color:#fff;">
                            <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <h4 style="color:#fff;font-weight:700;margin:0;"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></h4>
                        <span style="background:rgba(255,255,255,0.2);color:#fff;padding:4px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;display:inline-block;margin-top:8px;">
                            <?= $roleLabel ?>
                        </span>
                    </div>
                    <div class="card-body" style="padding:25px;">
                        <div class="profile-info-item border-bottom py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:0.85rem;"><i class="fas fa-user mr-2"></i>Usuario</span>
                            <strong style="font-size:0.9rem;"><?= htmlspecialchars($user['username'] ?? '') ?></strong>
                        </div>
                        <div class="profile-info-item border-bottom py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:0.85rem;"><i class="fas fa-building mr-2"></i>Sucursal</span>
                            <strong style="font-size:0.9rem;"><?= htmlspecialchars($user['branch'] ?? 'N/A') ?></strong>
                        </div>
                        <div class="profile-info-item py-2 d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size:0.85rem;"><i class="fas fa-shield-alt mr-2"></i>Rol</span>
                            <span class="badge" style="background:<?= $roleColor ?>;color:#fff;padding:5px 12px;border-radius:20px;"><?= strtoupper($role) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Password Card -->
            <div class="col-lg-5 col-md-7 col-12 mb-4">
                <div class="card" style="border:none;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.07);">
                    <div class="card-header" style="background:#fff;border-bottom:1px solid #f1f1f1;padding:20px 25px;border-radius:16px 16px 0 0;">
                        <h5 style="margin:0;font-weight:700;color:#343a40;"><i class="fas fa-key mr-2" style="color:#6f42c1;"></i>Cambiar Contraseña</h5>
                        <small class="text-muted">Mantén tu cuenta segura cambiando tu contraseña regularmente.</small>
                    </div>
                    <div class="card-body" style="padding:25px;">
                        <div id="profileChangeResult" style="display:none;margin-bottom:15px;" class="alert"></div>
                        <form id="changePasswordForm">
                            <div class="form-group">
                                <label style="font-weight:600;font-size:0.875rem;color:#495057;">Contraseña Actual</label>
                                <div style="position:relative;">
                                    <input type="password" id="currentPassword" name="current_password" class="form-control" placeholder="Tu contraseña actual" style="border-radius:8px;padding-right:44px;">
                                    <i class="fas fa-eye" onclick="togglePw('currentPassword',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#adb5bd;"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600;font-size:0.875rem;color:#495057;">Nueva Contraseña</label>
                                <div style="position:relative;">
                                    <input type="password" id="newPassword" name="new_password" class="form-control" placeholder="Mínimo 8 caracteres" style="border-radius:8px;padding-right:44px;" oninput="checkPasswordStrength(this.value)">
                                    <i class="fas fa-eye" onclick="togglePw('newPassword',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#adb5bd;"></i>
                                </div>
                                <!-- Strength indicator -->
                                <div id="strengthBar" style="height:4px;border-radius:2px;margin-top:8px;background:#e9ecef;overflow:hidden;">
                                    <div id="strengthFill" style="height:100%;width:0%;transition:all 0.3s;border-radius:2px;"></div>
                                </div>
                                <small id="strengthLabel" class="text-muted" style="font-size:0.78rem;"></small>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600;font-size:0.875rem;color:#495057;">Confirmar Nueva Contraseña</label>
                                <div style="position:relative;">
                                    <input type="password" id="confirmPassword" name="confirm_password" class="form-control" placeholder="Repite la nueva contraseña" style="border-radius:8px;padding-right:44px;">
                                    <i class="fas fa-eye" onclick="togglePw('confirmPassword',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#adb5bd;"></i>
                                </div>
                            </div>
                            <button type="button" onclick="submitPasswordChange()" class="btn btn-block mt-3" style="background:linear-gradient(135deg,#6f42c1,#5a32a3);color:#fff;border:none;border-radius:8px;padding:12px;font-weight:600;font-size:0.95rem;">
                                <i class="fas fa-save mr-2"></i>Actualizar Contraseña
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function togglePw(id, icon) {
        const inp = document.getElementById(id);
        if (inp.type === 'password') { inp.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
        else { inp.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
    }

    function checkPasswordStrength(pw) {
        const fill = document.getElementById('strengthFill');
        const label = document.getElementById('strengthLabel');
        let score = 0;
        if (pw.length >= 8) score++;
        if (/[A-Z]/.test(pw)) score++;
        if (/[0-9]/.test(pw)) score++;
        if (/[^A-Za-z0-9]/.test(pw)) score++;
        const levels = [
            { w:'0%', c:'transparent', t:'' },
            { w:'25%', c:'#dc3545', t:'Muy débil' },
            { w:'50%', c:'#fd7e14', t:'Débil' },
            { w:'75%', c:'#ffc107', t:'Aceptable' },
            { w:'100%', c:'#28a745', t:'Fuerte' }
        ];
        const l = levels[score] || levels[0];
        fill.style.width = l.w; fill.style.background = l.c; label.textContent = l.t;
    }

    function submitPasswordChange() {
        const cur = document.getElementById('currentPassword').value.trim();
        const nw = document.getElementById('newPassword').value.trim();
        const cf = document.getElementById('confirmPassword').value.trim();
        const res = document.getElementById('profileChangeResult');

        if (!cur || !nw || !cf) { showResult('error', 'Todos los campos son obligatorios.'); return; }
        if (nw.length < 8) { showResult('error', 'La nueva contraseña debe tener al menos 8 caracteres.'); return; }
        if (nw !== cf) { showResult('error', 'Las contraseñas nuevas no coinciden.'); return; }

        const fd = new FormData();
        fd.append('current_password', cur);
        fd.append('new_password', nw);
        const csrf = document.querySelector('meta[name="csrf-token"]');
        if (csrf) fd.append('csrf_token', csrf.getAttribute('content'));

        fetch('/soleipharmav2/admin/changeMyPassword', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            showResult(data.success ? 'success' : 'error', data.message);
            if (data.success) { document.getElementById('changePasswordForm').reset(); document.getElementById('strengthFill').style.width='0'; document.getElementById('strengthLabel').textContent=''; }
        }).catch(() => showResult('error', 'Error de conexión.'));
    }

    function showResult(type, msg) {
        const el = document.getElementById('profileChangeResult');
        el.style.display = 'block';
        el.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger');
        el.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle mr-2"></i>' : '<i class="fas fa-exclamation-circle mr-2"></i>') + msg;
    }
</script>
