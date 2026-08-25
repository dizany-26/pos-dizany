document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('buscadorUsuarios');
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    const modalNuevoUsuario = document.getElementById('modalNuevoUsuario');
    const modalEditarUsuario = document.getElementById('modalEditarUsuario');
    const formNuevoUsuario = document.getElementById('formNuevoUsuario');
    const formEditarUsuario = document.getElementById('formEditarUsuario');
    const rolSelect = document.getElementById('nuevo-rol-id');
    const editarRolSelect = document.getElementById('editar-rol');
    const permisoCheckboxes = Array.from(document.querySelectorAll('.permiso-checkbox'));
    const editarPermisoCheckboxes = Array.from(document.querySelectorAll('.editar-permiso-checkbox'));
    const btnMarcarTodos = document.getElementById('marcarTodosPermisos');
    const btnLimpiar = document.getElementById('limpiarPermisos');
    const btnEditarMarcarTodos = document.getElementById('editarMarcarTodosPermisos');
    const btnEditarLimpiar = document.getElementById('editarLimpiarPermisos');
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-btn');
    const deleteUserForms = document.querySelectorAll('.eliminar-usuario-form');
    const changePasswordForm = document.querySelector('#modalCambiarClave form');
    const adminRoleId = String(window.rolesUsuarios?.Administrador ?? '');
    const roleTemplates = window.plantillasRolesUsuarios || {};
    const dniNuevo = document.getElementById('nuevo-usuario-dni');
    const nombreNuevo = document.getElementById('nuevo-usuario-nombre');
    const estadoDniNuevo = document.getElementById('nuevo-usuario-dni-estado');
    const consultarDniButton = document.getElementById('consultarDniUsuario');
    const crearRolButton = document.getElementById('crearRolUsuario');
    const editarRolButton = document.getElementById('editarRolUsuario');
    const systemRoleNames = new Set(['Administrador', 'Encargado', 'Cajero', 'Almacén', 'Empleado']);
    let consultaDniTimer;

    const consultarDni = async () => {
        const dni = (dniNuevo?.value || '').replace(/\D/g, '');
        if (dni.length !== 8 || !estadoDniNuevo || !consultarDniButton) {
            if (estadoDniNuevo) estadoDniNuevo.textContent = 'El DNI debe contener 8 dígitos.';
            return;
        }

        consultarDniButton.disabled = true;
        estadoDniNuevo.className = 'usuario-dni-estado mt-2 is-loading';
        estadoDniNuevo.textContent = 'Consultando RENIEC…';

        try {
            const response = await fetch(`/consulta-documento/dni/${dni}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'DNI no encontrado.');

            if (nombreNuevo) nombreNuevo.value = (data.nombre || '').toLocaleUpperCase('es-PE');
            estadoDniNuevo.className = 'usuario-dni-estado mt-2 is-success';
            estadoDniNuevo.textContent = 'Datos encontrados correctamente.';
        } catch (error) {
            estadoDniNuevo.className = 'usuario-dni-estado mt-2 is-error';
            estadoDniNuevo.textContent = error.message || 'No se pudo consultar RENIEC.';
        } finally {
            consultarDniButton.disabled = false;
        }
    };

    dniNuevo?.addEventListener('input', () => {
        dniNuevo.value = dniNuevo.value.replace(/\D/g, '').slice(0, 8);
        clearTimeout(consultaDniTimer);
        if (estadoDniNuevo) {
            estadoDniNuevo.className = 'usuario-dni-estado mt-2';
            estadoDniNuevo.textContent = dniNuevo.value.length === 8
                ? 'Listo para consultar.'
                : 'Ingresa el DNI para completar el nombre.';
        }
        if (dniNuevo.value.length === 8) consultaDniTimer = setTimeout(consultarDni, 350);
    });

    consultarDniButton?.addEventListener('click', consultarDni);

    const selectedRoleOption = () => rolSelect?.selectedOptions?.[0] || null;
    const updateRoleManagementState = () => {
        if (!editarRolButton) return;
        const option = selectedRoleOption();
        const roleName = option?.textContent?.trim() || '';
        editarRolButton.disabled = !option?.value || systemRoleNames.has(roleName);
        editarRolButton.title = !option?.value
            ? 'Selecciona un rol personalizado para editarlo'
            : systemRoleNames.has(roleName)
                ? 'Este es un rol interno protegido'
                : 'Editar rol seleccionado';
    };

    const rebuildRoleSelect = (select) => {
        if (!select || !window.jQuery?.fn?.select2) return;
        const jquerySelect = window.jQuery(select);
        const selectedValue = select.value;

        if (select.classList.contains('select2-hidden-accessible')) {
            jquerySelect.select2('destroy');
        }

        jquerySelect.select2({
            width: '100%',
            placeholder: select.querySelector('option[value=""]')?.textContent?.trim() || 'Seleccionar...',
            allowClear: Boolean(select.querySelector('option[value=""]')),
            minimumResultsForSearch: Infinity,
            dropdownCssClass: 'ui-modern-select-dropdown',
            dropdownParent: window.jQuery(document.body)
        });
        jquerySelect.val(selectedValue).trigger('change.select2');
    };

    crearRolButton?.addEventListener('click', async () => {
        if (window.jQuery?.fn?.select2 && rolSelect?.classList.contains('select2-hidden-accessible')) {
            window.jQuery(rolSelect).select2('close');
        }

        const parentModal = window.bootstrap?.Modal?.getInstance(modalNuevoUsuario);
        parentModal?._focustrap?.deactivate();

        const result = await Swal.fire({
            title: 'Crear nuevo rol',
            html: `
                <div class="usuario-role-create-modal">
                    <label for="nuevo-rol-nombre">Nombre del rol</label>
                    <input id="nuevo-rol-nombre" class="swal2-input" type="text"
                           maxlength="50" autocomplete="off" placeholder="Ej. Supervisor">
                    <small>Después de crearlo, selecciona los permisos que tendrá este rol.</small>
                </div>`,
            showCancelButton: true,
            confirmButtonText: 'Crear rol',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            reverseButtons: true,
            focusConfirm: false,
            didOpen: () => document.getElementById('nuevo-rol-nombre')?.focus(),
            preConfirm: () => {
                const name = document.getElementById('nuevo-rol-nombre')?.value.trim();
                if (!name) {
                    Swal.showValidationMessage('Escribe el nombre del nuevo rol.');
                    return false;
                }
                return name;
            }
        });

        if (!result.isConfirmed) {
            parentModal?._focustrap?.activate();
            return;
        }

        crearRolButton.disabled = true;
        try {
            const token = formNuevoUsuario?.querySelector('[name="_token"]')?.value || '';
            const response = await fetch(crearRolButton.dataset.url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ nombre: result.value })
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                const validationMessage = data.errors?.nombre?.[0];
                throw new Error(validationMessage || data.message || 'No se pudo crear el rol.');
            }

            const role = data.role;
            [rolSelect, editarRolSelect].forEach((select) => {
                if (!select || select.querySelector(`option[value="${role.id}"]`)) return;
                const option = new Option(role.name, role.id, false, select === rolSelect);
                option.dataset.description = role.description;
                option.dataset.protected = 'false';
                option.dataset.usersCount = '0';
                select.add(option);
            });
            window.rolesUsuarios[role.name] = role.id;
            roleTemplates[role.name] = [];
            rolSelect.value = String(role.id);
            rolSelect.dispatchEvent(new Event('change', { bubbles: true }));

            await Swal.fire({
                icon: 'success',
                title: 'Rol creado',
                text: `${role.name} está seleccionado. Ahora marca sus permisos.`,
                timer: 1700,
                showConfirmButton: false
            });
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'No se pudo crear el rol',
                text: error.message || 'Ocurrió un error inesperado.',
                confirmButtonText: 'Entendido'
            });
        } finally {
            crearRolButton.disabled = false;
            parentModal?._focustrap?.activate();
        }
    });

    editarRolButton?.addEventListener('click', async () => {
        const option = selectedRoleOption();
        if (!option?.value || systemRoleNames.has(option.textContent.trim())) return;

        if (window.jQuery?.fn?.select2 && rolSelect.classList.contains('select2-hidden-accessible')) {
            window.jQuery(rolSelect).select2('close');
        }
        const parentModal = window.bootstrap?.Modal?.getInstance(modalNuevoUsuario);
        parentModal?._focustrap?.deactivate();

        try {
            const result = await Swal.fire({
                title: 'Editar rol',
                html: `
                    <div class="usuario-role-create-modal">
                        <label for="editar-rol-nombre">Nombre del rol</label>
                        <input id="editar-rol-nombre" class="swal2-input" type="text"
                               maxlength="50" autocomplete="off">
                        <small>Puedes corregir el nombre o eliminar el rol si no está asignado.</small>
                    </div>`,
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: 'Guardar cambio',
                denyButtonText: 'Eliminar rol',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                denyButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                focusConfirm: false,
                didOpen: () => {
                    const input = document.getElementById('editar-rol-nombre');
                    input.value = option.textContent.trim();
                    input.focus();
                    input.select();
                },
                preConfirm: () => {
                    const name = document.getElementById('editar-rol-nombre')?.value.trim();
                    if (!name) {
                        Swal.showValidationMessage('Escribe el nombre del rol.');
                        return false;
                    }
                    return name;
                }
            });

            const token = formNuevoUsuario?.querySelector('[name="_token"]')?.value || '';
            const url = `${editarRolButton.dataset.url}/${option.value}`;

            if (result.isConfirmed) {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Accept': 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ nombre: result.value })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.errors?.nombre?.[0] || data.errors?.rol?.[0] || data.message || 'No se pudo actualizar el rol.');
                }

                const checkedPermissionsBeforeRename = permisoCheckboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => checkbox.value);
                [rolSelect, editarRolSelect].forEach((select) => {
                    const roleOption = select?.querySelector(`option[value="${data.role.id}"]`);
                    if (!roleOption) return;

                    const replacement = new Option(
                        data.role.name,
                        String(data.role.id),
                        roleOption.defaultSelected,
                        roleOption.selected
                    );
                    Object.entries(roleOption.dataset).forEach(([key, value]) => {
                        replacement.dataset[key] = value;
                    });
                    roleOption.replaceWith(replacement);
                });
                delete window.rolesUsuarios[data.role.old_name];
                window.rolesUsuarios[data.role.name] = data.role.id;
                delete roleTemplates[data.role.old_name];
                roleTemplates[data.role.name] = [];
                rolSelect.value = String(data.role.id);
                if (window.jQuery?.fn?.select2) {
                    rebuildRoleSelect(rolSelect);
                    rebuildRoleSelect(editarRolSelect);
                } else {
                    rolSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
                setCheckedPermissions(permisoCheckboxes, checkedPermissionsBeforeRename);
                updateRoleManagementState();
                await Swal.fire({ icon: 'success', title: 'Rol actualizado', timer: 1400, showConfirmButton: false });
            } else if (result.isDenied) {
                const confirmation = await Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar este rol?',
                    text: Number(option.dataset.usersCount || 0) > 0
                        ? 'Este rol está asignado y no podrá eliminarse hasta cambiar esos usuarios.'
                        : 'Esta acción no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545'
                });
                if (!confirmation.isConfirmed) return;

                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.errors?.rol?.[0] || data.message || 'No se pudo eliminar el rol.');
                }

                [rolSelect, editarRolSelect].forEach((select) => select?.querySelector(`option[value="${option.value}"]`)?.remove());
                delete window.rolesUsuarios[option.textContent.trim()];
                rolSelect.value = '';
                rolSelect.dispatchEvent(new Event('change', { bubbles: true }));
                window.jQuery?.(rolSelect).trigger('change.select2');
                await Swal.fire({ icon: 'success', title: 'Rol eliminado', timer: 1400, showConfirmButton: false });
            }
        } catch (error) {
            await Swal.fire({
                icon: 'error', title: 'No se pudo gestionar el rol',
                text: error.message || 'Ocurrió un error inesperado.', confirmButtonText: 'Entendido'
            });
        } finally {
            parentModal?._focustrap?.activate();
        }
    });

    const setCheckedPermissions = (checkboxes, permissions) => {
        const selected = new Set(permissions);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(checkbox.value);
        });
    };

    const selectedRoleName = (select) => select?.selectedOptions?.[0]?.textContent?.trim() || '';

    const updateRoleHelp = (select, helpId) => {
        const help = document.getElementById(helpId);
        if (!help) return;
        help.textContent = select?.selectedOptions?.[0]?.dataset?.description || '';
    };

    const setAdminPermissionState = (select, checkboxes, markButton, clearButton) => {
        const isAdmin = String(select?.value || '') === adminRoleId;
        checkboxes.forEach((checkbox) => {
            checkbox.disabled = isAdmin;
        });
        if (markButton) markButton.disabled = isAdmin;
        if (clearButton) clearButton.disabled = isAdmin;
    };

    const applyRoleTemplate = (select, checkboxes, helpId, markButton, clearButton, preserveUnknown = false) => {
        if (!select) return;
        const roleName = selectedRoleName(select);
        const hasTemplate = Object.prototype.hasOwnProperty.call(roleTemplates, roleName);
        if (hasTemplate || !preserveUnknown) {
            setCheckedPermissions(checkboxes, roleTemplates[roleName] || []);
        }
        updateRoleHelp(select, helpId);
        setAdminPermissionState(select, checkboxes, markButton, clearButton);
    };

    const applyRoleDefaults = () => {
        applyRoleTemplate(rolSelect, permisoCheckboxes, 'nuevo-rol-ayuda', btnMarcarTodos, btnLimpiar);
    };

    const applyEditRoleDefaults = () => {
        applyRoleTemplate(
            editarRolSelect,
            editarPermisoCheckboxes,
            'editar-rol-ayuda',
            btnEditarMarcarTodos,
            btnEditarLimpiar
        );
    };

    if (buscador) {
        buscador.addEventListener('input', function () {
            const filtro = this.value.toLowerCase();
            filas.forEach(fila => {
                const texto = fila.innerText.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    }

    deleteUserForms.forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const userName = form.dataset.usuario || 'este usuario';
            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar usuario?',
                text: `Se eliminará la cuenta de "${userName}" y sus permisos de acceso.`,
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#64748b',
                reverseButtons: true,
                focusCancel: true,
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-3 px-4',
                    cancelButton: 'rounded-3 px-4'
                }
            });

            if (result.isConfirmed) form.submit();
        });
    });

    document.querySelectorAll('.cambiar-clave-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('usuario_id_cambiar_clave').value = this.dataset.id;
            document.getElementById('nombre_usuario_label').textContent = 'Usuario: ' + this.dataset.nombre;
        });
    });

    if (rolSelect) {
        rolSelect.addEventListener('change', () => {
            applyRoleDefaults();
            updateRoleManagementState();
        });
        if (window.jQuery?.fn?.select2) {
            window.jQuery(rolSelect).on('select2:select select2:clear', updateRoleManagementState);
        }
        updateRoleManagementState();
    }

    if (editarRolSelect) {
        editarRolSelect.addEventListener('change', applyEditRoleDefaults);
        if (window.jQuery?.fn?.select2) {
            window.jQuery(editarRolSelect).on('select2:select select2:clear', applyEditRoleDefaults);
        }
    }

    if (btnMarcarTodos) {
        btnMarcarTodos.addEventListener('click', () => {
            permisoCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
        });
    }

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            permisoCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
        });
    }

    if (btnEditarMarcarTodos) {
        btnEditarMarcarTodos.addEventListener('click', () => {
            editarPermisoCheckboxes.forEach((checkbox) => {
                checkbox.checked = true;
            });
        });
    }

    if (btnEditarLimpiar) {
        btnEditarLimpiar.addEventListener('click', () => {
            editarPermisoCheckboxes.forEach((checkbox) => {
                checkbox.checked = false;
            });
        });
    }

    if (modalNuevoUsuario) {
        modalNuevoUsuario.addEventListener('show.bs.modal', () => {
            if (formNuevoUsuario) {
                formNuevoUsuario.reset();
            }

            const passwordInput = document.getElementById('nuevo-password-visible');

            if (estadoDniNuevo) {
                estadoDniNuevo.className = 'usuario-dni-estado mt-2';
                estadoDniNuevo.textContent = 'Ingresa el DNI para completar el nombre.';
            }

            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.classList.remove('clave-visible');
                passwordInput.setAttribute('autocomplete', 'one-time-code');
            }

            togglePasswordButtons.forEach((button) => {
                const icon = button.querySelector('i');
                if (icon) {
                    icon.classList.add('fa-eye');
                    icon.classList.remove('fa-eye-slash');
                }
                button.setAttribute('aria-label', 'Mostrar contraseña');
            });

            window.setTimeout(() => applyRoleDefaults(), 0);
        });
    }

    if (formNuevoUsuario) {
        formNuevoUsuario.addEventListener('submit', () => {
            const visible = document.getElementById('nuevo-password-visible');
            const payload = document.getElementById('nuevo-password-payload');
            if (visible && payload) payload.value = visible.value;
        });

        formNuevoUsuario.addEventListener('reset', () => {
            window.setTimeout(() => applyRoleDefaults(), 0);
        });
    }

    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', () => {
            const visible = document.getElementById('cambiar-clave-visible');
            const payload = document.getElementById('cambiar-clave-payload');
            if (visible && payload) payload.value = visible.value;
        });

        document.getElementById('modalCambiarClave')?.addEventListener('show.bs.modal', () => {
            const visible = document.getElementById('cambiar-clave-visible');
            const payload = document.getElementById('cambiar-clave-payload');
            if (visible) visible.value = '';
            if (payload) payload.value = '';
        });
    }

    if (modalEditarUsuario) {
        modalEditarUsuario.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const dni = button.getAttribute('data-dni');
            const email = button.getAttribute('data-email');
            const rol = button.getAttribute('data-rol');
            const permisos = JSON.parse(button.getAttribute('data-permisos') || '[]');

            modalEditarUsuario.querySelector('#editar-id').value = id;
            modalEditarUsuario.querySelector('#editar-nombre').value = nombre;
            modalEditarUsuario.querySelector('#editar-dni').value = dni;
            modalEditarUsuario.querySelector('#editar-email').value = email;
            if (editarRolSelect) {
                editarRolSelect.value = rol;
                if (window.jQuery?.fn?.select2 && editarRolSelect.classList.contains('select2-hidden-accessible')) {
                    window.jQuery(editarRolSelect).trigger('change.select2');
                }
            }
            setCheckedPermissions(editarPermisoCheckboxes, permisos);
            updateRoleHelp(editarRolSelect, 'editar-rol-ayuda');
            setAdminPermissionState(
                editarRolSelect,
                editarPermisoCheckboxes,
                btnEditarMarcarTodos,
                btnEditarLimpiar
            );

            if (formEditarUsuario) {
                formEditarUsuario.action = `/usuarios/${id}`;
            }
        });
    }

    togglePasswordButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input');
            const icon = button.querySelector('i');
            if (!input || !icon) return;

            const isHidden = !input.classList.contains('clave-visible');
            input.classList.toggle('clave-visible', isHidden);
            icon.classList.toggle('fa-eye', !isHidden);
            icon.classList.toggle('fa-eye-slash', isHidden);
            button.setAttribute('aria-label', isHidden ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
});
