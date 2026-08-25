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

    const applyRoleTemplate = (select, checkboxes, helpId, markButton, clearButton) => {
        if (!select) return;
        const permissions = roleTemplates[selectedRoleName(select)] || [];
        setCheckedPermissions(checkboxes, permissions);
        updateRoleHelp(select, helpId);
        setAdminPermissionState(select, checkboxes, markButton, clearButton);
    };

    const applyRoleDefaults = () => {
        applyRoleTemplate(rolSelect, permisoCheckboxes, 'nuevo-rol-ayuda', btnMarcarTodos, btnLimpiar);
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
        rolSelect.addEventListener('change', applyRoleDefaults);
    }

    if (editarRolSelect) {
        editarRolSelect.addEventListener('change', () => {
            applyRoleTemplate(
                editarRolSelect,
                editarPermisoCheckboxes,
                'editar-rol-ayuda',
                btnEditarMarcarTodos,
                btnEditarLimpiar
            );
        });
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
