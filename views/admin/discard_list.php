<div class="row mb-2">
            <div class="col-sm-6">
                <h1>Solicitudes Pendientes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="<?= APP_BASE ?>/admin/index">Dashboard</a></li>
                    <li class="breadcrumb-item active">Solicitudes Pendientes</li>
                </ol>
            </div>
        </div>

<div class="container mb-5">
    <div class="card shadow-sm p-3">
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-center align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Razón</th>
                        <th>Solicitante</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr data-id="<?= $r['id'] ?>" class="<?= ($r['quantity'] > $r['current_stock']) ? 'table-warning' : '' ?>">
                            <td>
                                <?= $r['id'] ?>
                                <?php if ($r['quantity'] > $r['current_stock']): ?>
                                    <br><span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Excede Stock (<?= $r['current_stock'] ?> disp.)</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['product_name']) ?></td>
                            <td><?= $r['quantity'] ?></td>
                            <td><?= htmlspecialchars($r['reason']) ?></td>
                            <td><?= htmlspecialchars($r['requester_name']) ?></td>
                            <td>
                                <?php 
                                $isUnassigned = is_null($r['assigned_to']);
                                $isAssignee = $r['is_assignee'];
                                // If not unassigned and not assignee, must be observer due to backend filter
                                $isObserver = (!$isUnassigned && !$isAssignee);
                                ?>

                                <?php if ($isObserver): ?>
                                    <span class="badge bg-secondary mb-2 d-block">Solo Lectura (Observador)</span>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="btn-group w-100">
                                            <button class="btn btn-success btn-sm w-50" disabled>Aprobar</button>
                                            <button class="btn btn-danger btn-sm w-50" disabled>Rechazar</button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="btn-group w-100">
                                            <button class="btn btn-success btn-sm w-50 action-btn" data-status="approved">Aprobar</button>
                                            <button class="btn btn-danger btn-sm w-50 action-btn" data-status="rejected">Rechazar</button>
                                        </div>
                                        <div class="btn-group w-100 mt-1">
                                            <button class="btn btn-warning btn-sm w-50 action-btn text-dark" style="font-size:0.8rem;" data-status="in_revision">A Revisión</button>
                                            <button class="btn btn-info btn-sm w-50 action-btn text-white" style="font-size:0.8rem;" data-status="in_follow_up">Seguimiento</button>
                                        </div>
                                        <?php if ($isUnassigned): ?>
                                            <button class="btn btn-primary btn-sm w-100 mt-1 assign-btn"><i class="fas fa-user-plus"></i> Asignar</button>
                                        <?php else: ?>
                                            <span class="badge bg-primary mt-1 d-block"><i class="fas fa-user-check"></i> Asignado a ti</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap y jQuery -->
<!-- Modal para Razón de Decisión -->
<div class="modal micromodal-slide" id="decision-modal" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="decision-modal-title">
      <header class="modal__header">
        <h2 class="modal__title" id="decision-modal-title">Confirmar Decisión</h2>
        <button class="modal__close" aria-label="Cerrar modal" data-micromodal-close></button>
      </header>
      <main class="modal__content" id="decision-modal-content">
        <p id="decision-modal-text"></p>
        <label for="modal-input-reason" style="font-weight:bold;">Razón de la decisión</label>
        <input type="text" id="modal-input-reason" class="micromodal-input" placeholder="Escribe la razón aquí..." autocomplete="off">
        <div id="modal-validation-msg" class="micromodal-validation" style="display:none; color:red; font-size: 0.9em; margin-top: 5px;">Debes ingresar una razón</div>

        <div id="modal-followup-container" style="display:none; margin-top: 15px;">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="modal-checkbox-followup">
                <label class="form-check-label" for="modal-checkbox-followup" style="font-weight:bold; cursor:pointer;">
                    Marcar también en Seguimiento Interno
                </label>
            </div>
            <small class="text-muted d-block ms-4" style="font-size: 0.8em; margin-top: -3px;">
                Informará al usuario que su solicitud requiere corrección y mientras tanto está bajo investigación.
            </small>
        </div>
      </main>
      <footer class="modal__footer" style="text-align: right;">
        <button class="modal__btn" data-micromodal-close aria-label="Cancelar">Cancelar</button>
        <button class="modal__btn modal__btn-primary" id="btn-confirm-decision">Confirmar</button>
      </footer>
    </div>
  </div>
</div>

<!-- Modal para Asignar Solicitud -->
<div class="modal micromodal-slide" id="assign-modal" aria-hidden="true">
  <div class="modal__overlay" tabindex="-1" data-micromodal-close>
    <div class="modal__container" role="dialog" aria-modal="true" aria-labelledby="assign-modal-title">
      <header class="modal__header">
        <h2 class="modal__title" id="assign-modal-title"><i class="fas fa-users"></i> Asignar Solicitud</h2>
        <button class="modal__close" aria-label="Cerrar modal" data-micromodal-close></button>
      </header>
      <main class="modal__content" id="assign-modal-content">
        <p>Selecciona al responsable principal y a los observadores para esta solicitud.</p>
        
        <div class="mb-3">
            <label for="assign-responsible" style="font-weight:bold;">Responsable Principal</label>
            <select id="assign-responsible" class="form-control micromodal-input">
                <option value="">-- Seleccionar --</option>
                <?php foreach ($superadmins as $sa): ?>
                    <option value="<?= $sa['id'] ?>"><?= htmlspecialchars($sa['first_name'] . ' ' . $sa['last_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div id="assign-validation-msg" class="micromodal-validation" style="display:none; color:red; font-size: 0.9em; margin-top: 5px;">Selecciona a un responsable</div>
        </div>

        <div class="mb-2">
            <label style="font-weight:bold;">Observadores (Opcional)</label>
            <div class="ps-2" style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 5px;">
                <?php foreach ($superadmins as $sa): ?>
                    <div class="form-check">
                        <input class="form-check-input observer-checkbox" type="checkbox" value="<?= $sa['id'] ?>" id="obs-<?= $sa['id'] ?>">
                        <label class="form-check-label" for="obs-<?= $sa['id'] ?>">
                            <?= htmlspecialchars($sa['first_name'] . ' ' . $sa['last_name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
      </main>
      <footer class="modal__footer" style="text-align: right;">
        <button class="modal__btn" data-micromodal-close aria-label="Cancelar">Cancelar</button>
        <button class="modal__btn modal__btn-primary" id="btn-confirm-assign">Guardar Asignación</button>
      </footer>
    </div>
  </div>
</div>

<!-- Bootstrap y jQuery -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    let currentDiscardId = null;
    let currentDiscardStatus = null;
    let currentDiscardRow = null;

    document.querySelectorAll('.action-btn').forEach(btn => {
        btn.onclick = () => {
            const tr = btn.closest('tr');
            currentDiscardRow = tr;
            currentDiscardId = tr.dataset.id;
            
            const productName = tr.querySelectorAll('td')[1].textContent.trim();
            const quantity = tr.querySelectorAll('td')[2].textContent.trim();
            currentDiscardStatus = btn.dataset.status;

            let actionTxt = '';
            let isWarning = false;
            switch(currentDiscardStatus) {
                case 'approved': actionTxt = 'Aprobar'; break;
                case 'rejected': actionTxt = 'Rechazar'; break;
                case 'in_revision': actionTxt = 'Mandar a corregir'; isWarning = true; break;
                case 'in_follow_up': actionTxt = 'Marcar en seguimiento'; isWarning = true; break;
            }
            
            let modalTitle = document.getElementById('decision-modal-title');
            if(isWarning) modalTitle.innerHTML = `<i class="fas fa-exclamation-triangle text-warning"></i> ${actionTxt}`;
            else modalTitle.textContent = 'Confirmar Decisión';

            document.getElementById('decision-modal-text').textContent = `¿Deseas ${actionTxt.toLowerCase()} el descarte de ${quantity} unidades de "${productName}"?`;
            
            document.getElementById('modal-input-reason').value = '';
            document.getElementById('modal-validation-msg').style.display = 'none';

            // Checkbox conditions
            const followupContainer = document.getElementById('modal-followup-container');
            const followupCheckbox = document.getElementById('modal-checkbox-followup');
            followupCheckbox.checked = false;
            if (currentDiscardStatus === 'in_revision') {
                followupContainer.style.display = 'block';
            } else {
                followupContainer.style.display = 'none';
            }
            
            MicroModal.show('decision-modal');
        };
    });

    document.getElementById('btn-confirm-decision').onclick = () => {
        const reason = document.getElementById('modal-input-reason').value.trim();
        
        if (!reason) {
            document.getElementById('modal-validation-msg').style.display = 'block';
            return;
        }

        document.getElementById('modal-validation-msg').style.display = 'none';
        MicroModal.close('decision-modal');

        const isFollowUp = (currentDiscardStatus === 'in_follow_up' || document.getElementById('modal-checkbox-followup').checked) ? 1 : 0;

        fetch('<?= APP_BASE ?>/discard/decide', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${currentDiscardId}&status=${currentDiscardStatus}&decision_reason=${encodeURIComponent(reason)}&is_follow_up=${isFollowUp}`
        })
        .then(r => r.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.message
            });
            if (data.success && currentDiscardRow) {
                currentDiscardRow.remove();
            }
        });
    };

    // Lógica para el Modal de Asignación
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.onclick = () => {
            const tr = btn.closest('tr');
            currentDiscardRow = tr;
            currentDiscardId = tr.dataset.id;
            
            // Limpiar modal
            document.getElementById('assign-responsible').value = '';
            document.querySelectorAll('.observer-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('assign-validation-msg').style.display = 'none';

            MicroModal.show('assign-modal');
        };
    });

    // Guardar Asignación
    document.getElementById('btn-confirm-assign').onclick = () => {
        const responsibleId = document.getElementById('assign-responsible').value;
        const observerCheckboxes = document.querySelectorAll('.observer-checkbox:checked');
        
        if (!responsibleId) {
            document.getElementById('assign-validation-msg').style.display = 'block';
            return;
        }
        
        document.getElementById('assign-validation-msg').style.display = 'none';
        
        // Evitar que el responsable también sea observador
        let observers = [];
        observerCheckboxes.forEach(cb => {
            if (cb.value !== responsibleId) observers.push(cb.value);
        });

        MicroModal.close('assign-modal');

        let formData = new URLSearchParams();
        formData.append('id', currentDiscardId);
        formData.append('assigned_to', responsibleId);
        observers.forEach(obs => formData.append('observers[]', obs));

        fetch('<?= APP_BASE ?>/discard/assignRequest', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.message
            }).then(() => {
                if(data.success) location.reload();
            });
        });
    };
</script>