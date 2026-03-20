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
    const empleadoRoleId = String(window.rolesUsuarios?.Empleado ?? '');
    const adminRoleId = String(window.rolesUsuarios?.Administrador ?? '');

    const setCheckedPermissions = (checkboxes, permissions) => {
        const selected = new Set(permissions);
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(checkbox.value);
        });
    };

    const applyRoleDefaults = () => {
        if (!rolSelect) return;

        const selectedRole = String(rolSelect.value || '');

        if (selectedRole === empleadoRoleId) {
            setCheckedPermissions(permisoCheckboxes, ['dashboard.empleado']);
            return;
        }

        if (selectedRole === adminRoleId) {
            setCheckedPermissions(permisoCheckboxes, ['dashboard.admin']);
            return;
        }

        setCheckedPermissions(permisoCheckboxes, []);
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

    document.querySelectorAll('.cambiar-clave-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('usuario_id_cambiar_clave').value = this.dataset.id;
            document.getElementById('nombre_usuario_label').textContent = 'Usuario: ' + this.dataset.nombre;
        });
    });

    if (rolSelect) {
        rolSelect.addEventListener('change', applyRoleDefaults);
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

            const usuarioInput = formNuevoUsuario?.querySelector('input[name="usuario"]');
            const passwordInput = formNuevoUsuario?.querySelector('input[name="password"]');

            if (usuarioInput) {
                usuarioInput.value = '';
                usuarioInput.setAttribute('autocomplete', 'off');
            }

            if (passwordInput) {
                passwordInput.value = '';
                passwordInput.type = 'password';
                passwordInput.setAttribute('autocomplete', 'new-password');
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
        formNuevoUsuario.addEventListener('reset', () => {
            window.setTimeout(() => applyRoleDefaults(), 0);
        });
    }

    if (modalEditarUsuario) {
        modalEditarUsuario.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const id = button.getAttribute('data-id');
            const nombre = button.getAttribute('data-nombre');
            const usuario = button.getAttribute('data-usuario');
            const email = button.getAttribute('data-email');
            const rol = button.getAttribute('data-rol');
            const permisos = JSON.parse(button.getAttribute('data-permisos') || '[]');

            modalEditarUsuario.querySelector('#editar-id').value = id;
            modalEditarUsuario.querySelector('#editar-nombre').value = nombre;
            modalEditarUsuario.querySelector('#editar-usuario').value = usuario;
            modalEditarUsuario.querySelector('#editar-email').value = email;
            if (editarRolSelect) {
                editarRolSelect.value = rol;
            }
            setCheckedPermissions(editarPermisoCheckboxes, permisos);

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

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !isPassword);
            icon.classList.toggle('fa-eye-slash', isPassword);
            button.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
});
