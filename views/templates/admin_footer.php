</div>
<!-- /.content-wrapper -->
<footer class="main-footer" style="background-color: #ffffff; color: #495057; border-top: 1px solid #dee2e6;">
  <div class="float-right d-none d-sm-block">
    <b>Version</b> 1.109
  </div>
  <strong>&copy; <?= date('Y') ?> <?= COMPANY_NAME ?></strong> <?= BRANCH ?>
</footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1/dist/js/adminlte.min.js"></script>
<!-- Micromodal -->
<script src="https://unpkg.com/micromodal/dist/micromodal.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<!-- ===== AUTO-LOGOUT LOCK SCREEN ===== -->
<div id="lockScreen" style="
    display: none; position: fixed; inset: 0; z-index: 999999;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    align-items: center; justify-content: center; flex-direction: column;
    font-family: 'Source Sans Pro', sans-serif; color: #fff; text-align: center;
">
    <div style="position: absolute; top: 20px; left: 25px; font-size: 0.8rem; color: rgba(255,255,255,0.6); text-align: left;">
        Usuario Activo:<br>
        <span style="color: #fff; font-size: 0.95rem; font-weight: 600;">
            <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars(trim(($_SESSION['user']['first_name'] ?? '') . ' ' . ($_SESSION['user']['last_name'] ?? ''))) ?>
        </span>
    </div>

    <div style="backdrop-filter:blur(10px); background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:20px; padding:50px 40px; max-width:420px; width:90%;">
        <div style="font-size:60px; margin-bottom:15px;">🔒</div>
        <h2 style="font-weight:700; font-size:1.6rem; margin-bottom:8px;">Sesión Bloqueada</h2>
        <p style="color:rgba(255,255,255,0.65); font-size:0.95rem; margin-bottom:25px;">
            Por inactividad, tu sesión ha sido bloqueada.<br>Ingresa tu contraseña para continuar.
        </p>
        <div style="position:relative; margin-bottom:12px;">
            <input type="password" id="lockScreenPassword" placeholder="Contraseña..." autocomplete="current-password"
                style="width:100%;padding:14px 44px 14px 16px;border-radius:10px;border:1.5px solid rgba(255,255,255,0.2);background:rgba(255,255,255,0.08);color:#fff;font-size:1rem;outline:none;box-sizing:border-box;"
                onkeydown="if(event.key==='Enter') unlockSession()">
            <i class="fas fa-eye" id="lockEyeIcon" onclick="toggleLockPwVisibility()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:rgba(255,255,255,0.5);font-size:1.1rem;"></i>
        </div>
        <div id="lockScreenError" style="color:#ff6b6b;font-size:0.85rem;margin-bottom:12px;display:none;">Contraseña incorrecta. Inténtalo de nuevo.</div>
        <button onclick="unlockSession()" style="width:100%;padding:14px;background:linear-gradient(135deg,#6f42c1,#5a32a3);border:none;border-radius:10px;color:#fff;font-size:1rem;font-weight:600;cursor:pointer;transition:opacity 0.2s;"
            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
            <i class="fas fa-unlock-alt mr-2"></i> Desbloquear
        </button>
        <div style="margin-top:20px; padding-top:20px; border-top:1px solid rgba(255,255,255,0.1);">
            <a href="/soleipharmav2/auth/logout" style="color:rgba(255,255,255,0.45);font-size:0.85rem;text-decoration:none;">
                <i class="fas fa-sign-out-alt mr-1"></i>Cerrar Sesión
            </a>
        </div>
        <div id="lockCountdownDisplay" style="margin-top:12px;font-size:0.8rem;color:rgba(255,255,255,0.3);">Bloqueado por inactividad</div>
    </div>
</div>

<script>
(function() {
    const TIMEOUT_MS = 15 * 60 * 1000; // 15 minutes
    let lockTimer = null;
    let isLocked = localStorage.getItem('solei_is_locked') === 'true';

    function resetTimer() {
        if (isLocked) return;
        clearTimeout(lockTimer);
        lockTimer = setTimeout(lockSession, TIMEOUT_MS);
    }

    function lockSession() {
        isLocked = true;
        localStorage.setItem('solei_is_locked', 'true');
        const screen = document.getElementById('lockScreen');
        screen.style.display = 'flex';
        setTimeout(() => { const p = document.getElementById('lockScreenPassword'); if(p) p.focus(); }, 200);
    }

    function unlockSession() {
        const pw = document.getElementById('lockScreenPassword').value;
        const errDiv = document.getElementById('lockScreenError');
        if (!pw) { errDiv.style.display = 'block'; errDiv.textContent = 'Ingresa tu contraseña.'; return; }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const fd = new FormData();
        fd.append('password', pw);
        if (csrfMeta) fd.append('csrf_token', csrfMeta.getAttribute('content'));

        fetch('/soleipharmav2/cash/verifySessionPassword', { method: 'POST', body: fd })
        .then(r => r.json()).then(data => {
            if (data.success) {
                isLocked = false;
                localStorage.removeItem('solei_is_locked');
                document.getElementById('lockScreen').style.display = 'none';
                document.getElementById('lockScreenPassword').value = '';
                errDiv.style.display = 'none';
                resetTimer();
            } else {
                errDiv.style.display = 'block';
                errDiv.textContent = 'Contraseña incorrecta. Inténtalo de nuevo.';
                document.getElementById('lockScreenPassword').select();
            }
        }).catch(() => {
            errDiv.style.display = 'block';
            errDiv.textContent = 'Error de conexión. Intenta de nuevo.';
        });
    }

    function toggleLockPwVisibility() {
        const inp = document.getElementById('lockScreenPassword');
        const ico = document.getElementById('lockEyeIcon');
        if (inp.type === 'password') {
            inp.type = 'text'; ico.classList.replace('fa-eye','fa-eye-slash');
        } else {
            inp.type = 'password'; ico.classList.replace('fa-eye-slash','fa-eye');
        }
    }

    // Expose to global scope
    window.unlockSession = unlockSession;
    window.toggleLockPwVisibility = toggleLockPwVisibility;
    window.manualLockSession = function() {
        const cd = document.getElementById('lockCountdownDisplay');
        if (cd) cd.textContent = 'Bloqueado manualmente';
        lockSession();
    };

    // If already locked on load, show it
    if (isLocked) {
        lockSession();
    } else {
        resetTimer(); // Start the timer on page load normally
    }

    // Listen for any user activity
    ['mousemove','mousedown','keydown','touchstart','scroll','click'].forEach(evt => {
        document.addEventListener(evt, resetTimer, { passive: true });
    });
})();
</script>
<!-- ===== END AUTO-LOGOUT ===== -->

<div class="modal micromodal-slide" id="global-alert-modal" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container text-center" role="dialog" aria-modal="true" aria-labelledby="global-alert-title" style="max-width: 400px; padding: 40px 30px;">
      <div id="global-alert-icon-container"></div>
      <header class="modal__header" style="justify-content: center; margin-bottom: 10px;">
        <h2 class="modal__title" id="global-alert-title" style="font-size: 1.5rem;"></h2>
      </header>
      <main class="modal__content" id="global-alert-content" style="margin-bottom: 30px; font-size: 1.05rem;"></main>
      <footer class="modal__footer" id="global-alert-footer">
        <button class="modal__btn modal__btn-primary" id="global-alert-btn-close" data-micromodal-close aria-label="Cerrar modal" style="width: 100%; padding: 12px;">Cancelar</button>
      </footer>
    </div>
  </div>
</div>

<!-- START REUSABLE ACTION MODAL -->
<div class="modal micromodal-slide" id="generic-action-modal" aria-hidden="true">
    <div class="modal__overlay" tabindex="-1" data-micromodal-close>
        <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="action-modal-title">
            <header class="modal__header">
                <h2 class="modal__title" id="action-modal-title">Acción Solicitada</h2>
                <button class="modal__close" aria-label="Cerrar modal" data-micromodal-close></button>
            </header>
            <main class="modal__content" id="action-modal-content">
                <p id="action-modal-desc"></p>
                <div id="action-modal-fields-container"></div>
                <div id="action-validation-msg" class="micromodal-validation" style="display:none; color:red; font-size: 0.9em; margin-top: 5px;"></div>
            </main>
            <footer class="modal__footer" style="text-align: right;">
                <button class="modal__btn" data-micromodal-close aria-label="Cancelar" id="btn-cancel-action-modal">Cancelar</button>
                <button class="modal__btn modal__btn-primary" id="btn-confirm-action-modal">Confirmar</button>
            </footer>
        </div>
    </div>
</div>

<script>
window.ActionModal = {
    currentCallback: null,
    show: function(options) {
        document.getElementById('action-modal-title').innerText = options.title || 'Acción Solicitada';
        document.getElementById('action-modal-desc').innerHTML = options.description || '';
        document.getElementById('btn-confirm-action-modal').innerText = options.confirmText || 'Confirmar';
        document.getElementById('btn-cancel-action-modal').innerText = options.cancelText || 'Cancelar';
        document.getElementById('action-validation-msg').style.display = 'none';
        
        const container = document.getElementById('action-modal-fields-container');
        container.innerHTML = '';
        
        if (options.fields && options.fields.length > 0) {
            options.fields.forEach(f => {
                let wrapper = document.createElement('div');
                wrapper.style.marginBottom = "10px";

                if (f.label) {
                    let lbl = document.createElement('label');
                    lbl.innerHTML = f.label;
                    lbl.style.display = "block";
                    lbl.style.fontWeight = "bold";
                    lbl.style.fontSize = "0.9em";
                    lbl.style.marginBottom = "4px";
                    wrapper.appendChild(lbl);
                }

                const input = document.createElement('input');
                input.type = f.type || 'text';
                input.id = f.id;
                input.className = f.className || 'micromodal-input';
                input.placeholder = f.placeholder || '';
                input.autocomplete = 'off';
                if (f.style) input.style.cssText = f.style;
                
                wrapper.appendChild(input);
                container.appendChild(wrapper);
            });
        }

        this.currentCallback = options.onConfirm;
        MicroModal.show('generic-action-modal');
    },
    hide: function() {
        MicroModal.close('generic-action-modal');
    },
    showError: function(msg) {
        let err = document.getElementById('action-validation-msg');
        err.innerHTML = msg;
        err.style.display = 'block';
    }
};

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('btn-confirm-action-modal').addEventListener('click', function() {
        if (typeof window.ActionModal.currentCallback === 'function') {
            let data = {};
            const inputs = document.getElementById('action-modal-fields-container').querySelectorAll('input, select, textarea');
            inputs.forEach(el => {
                if (el.id) data[el.id] = el.value;
            });
            window.ActionModal.currentCallback(data);
        }
    });
});
</script>
<!-- END REUSABLE ACTION MODAL -->

<script>
    MicroModal.init();

    // Helper to replace generic Swal.fire
    window.Swal = {
        fire: function(arg1, arg2, arg3) {
            return new Promise((resolve) => {
                let options = {};
                // Soporte para sintaxis corta Swal.fire('titulo', 'texto', 'icono')
                if (typeof arg1 === 'string') {
                    options.title = arg1;
                    options.text = arg2 || '';
                    options.icon = arg3 || 'info';
                } else {
                    options = arg1 || {};
                }

                let title = options.title || 'Alerta';
                let text = options.text || options.html || '';
                let type = options.icon || options.type || 'info';

                // Evitar información redundante (ej. title="Error" y text="Error")
                if (title && text && title.toLowerCase().trim() === text.toLowerCase().trim()) {
                    text = '';
                }

                // Icons mapping 
                let iconHtml = '';
                if (type === 'success') {
                    iconHtml = '<i class="fas fa-check-circle icon-success modal-icon" style="font-size: 60px;"></i>';
                } else if (type === 'error') {
                    iconHtml = '<i class="fas fa-times-circle icon-error modal-icon" style="font-size: 60px;"></i>';
                } else if (type === 'warning') {
                    iconHtml = '<i class="fas fa-exclamation-triangle icon-warning modal-icon" style="font-size: 60px;"></i>';
                } else {
                    iconHtml = '<i class="fas fa-info-circle icon-info modal-icon" style="font-size: 60px;"></i>';
                }

                document.getElementById('global-alert-icon-container').innerHTML = iconHtml;
                document.getElementById('global-alert-title').innerHTML = title;
                document.getElementById('global-alert-content').innerHTML = text;
                
                // Configure buttons
                let footer = document.getElementById('global-alert-footer');
                if (options.showCancelButton) {
                    footer.innerHTML = `
                        <button class="modal__btn" id="global-alert-btn-cancel" data-micromodal-close aria-label="Cancelar" style="margin-right: 10px;">` + (options.cancelButtonText || 'Cancelar') + `</button>
                        <button class="modal__btn modal__btn-primary" id="global-alert-btn-confirm">` + (options.confirmButtonText || 'Confirmar') + `</button>
                    `;
                } else {
                    footer.innerHTML = `
                        <button class="modal__btn modal__btn-primary" id="global-alert-btn-close" data-micromodal-close aria-label="Cerrar" style="width: 100%; padding: 12px;">` + (options.confirmButtonText || 'Cancelar') + `</button>
                    `;
                }

                let resolved = false;

                MicroModal.show('global-alert-modal', {
                    onClose: modal => {
                        if (!resolved) {
                            resolved = true;
                            // If it's a basic alert (no cancel btn), closing it acts as "confirmed" to proceed
                            // If it's a question (has cancel btn), closing it via overlay/esc acts as "canceled"
                            resolve({ isConfirmed: !options.showCancelButton, isDismissed: options.showCancelButton });
                        }
                    }
                });

                // Attach events for Promises
                const btnConfirm = document.getElementById('global-alert-btn-confirm');
                if (btnConfirm) {
                    btnConfirm.onclick = () => {
                        if (resolved) return;
                        resolved = true;
                        resolve({ isConfirmed: true, isDismissed: false });
                        MicroModal.close('global-alert-modal');
                    };
                }

                const btnClose = document.getElementById('global-alert-btn-close');
                if (btnClose) {
                    btnClose.onclick = () => {
                        if (resolved) return;
                        resolved = true;
                        resolve({ isConfirmed: true, isDismissed: false });
                        MicroModal.close('global-alert-modal');
                    };
                }
            });
        }
    };
</script>

<!-- Scripts personalizados -->
<script src="assets/js/main.js"></script>
<script>
  (function () {
    const wrapper = document.getElementById('loading-wrapper');
    if (!wrapper) return;

    // Fade out immediately once the page has fully loaded
    window.addEventListener('load', () => {
      wrapper.style.opacity = '0';
      wrapper.style.visibility = 'hidden';
    });

    // Make the app feel real-time by fading the loader back IN when clicking links
    document.addEventListener('click', (e) => {
      const link = e.target.closest('a');
      if (link && link.href) {
        const isTargetBlank = link.target === '_blank';
        const isJavascript = link.href.startsWith('javascript:');
        const isHash = link.getAttribute('href') === '#' || link.getAttribute('href').startsWith('#');
        const isDownload = link.hasAttribute('download');

        if (!isTargetBlank && !isJavascript && !isHash && !isDownload) {
          wrapper.style.opacity = '1';
          wrapper.style.visibility = 'visible';
        }
      }
    });

    // Fallback if the page is loaded from bfcache (Back/Forward Cache)
    window.addEventListener('pageshow', (event) => {
      if (event.persisted) {
        wrapper.style.opacity = '0';
        wrapper.style.visibility = 'hidden';
      }
    });
  })();
</script>


</body>

</html>