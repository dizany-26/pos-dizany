const modalEditar = document.getElementById('modalEditarUsuario');
if (modalEditar) {
    modalEditar.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        if (!button) return;

        modalEditar.querySelector('#editar-id').value = button.getAttribute('data-id');
        modalEditar.querySelector('#editar-nombre').value = button.getAttribute('data-nombre');
        modalEditar.querySelector('#editar-usuario').value = button.getAttribute('data-usuario');
        modalEditar.querySelector('#editar-email').value = button.getAttribute('data-email');
        modalEditar.querySelector('#editar-rol').value = button.getAttribute('data-rol');
        modalEditar.querySelector('#formEditarUsuario').action = `/usuarios/${button.getAttribute('data-id')}`;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('buscadorUsuarios');
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    const modalNuevoUsuario = document.getElementById('modalNuevoUsuario');
    const formNuevoUsuario = document.getElementById('formNuevoUsuario');
    const rolSelect = document.getElementById('nuevo-rol-id');
    const permisoCheckboxes = Array.from(document.querySelectorAll('.permiso-checkbox'));
    const btnMarcarTodos = document.getElementById('marcarTodosPermisos');
    const btnLimpiar = document.getElementById('limpiarPermisos');
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-btn');
    const rolesUsuarios = JSON.parse(formNuevoUsuario?.dataset.rolesUsuarios || '{}');
    const empleadoRoleId = String(rolesUsuarios.Empleado ?? '');
    const adminRoleId = String(rolesUsuarios.Administrador ?? '');

    const setCheckedPermissions = (permissions = []) => {
        const selected = new Set(permissions);
        permisoCheckboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(checkbox.value);
        });
    };

    const getDefaultPermissionsByRole = (roleId) => {
        if (String(roleId) === empleadoRoleId) {
            return ['dashboard.empleado'];
        }

        if (String(roleId) === adminRoleId) {
            return ['dashboard.admin'];
        }

        return [];
    };

    const resetPasswordToggles = () => {
        togglePasswordButtons.forEach((button) => {
            const input = button.parentElement?.querySelector('input');
            const icon = button.querySelector('i');
            if (!input || !icon) return;

            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            button.setAttribute('aria-label', 'Mostrar contraseña');
        });
    };

    const clearNewUserForm = () => {
        if (!formNuevoUsuario) return;

        formNuevoUsuario.reset();
        formNuevoUsuario.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], select').forEach((field) => {
            if (field.classList.contains('usuario-hidden-autofill')) return;

            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
            } else {
                field.value = '';
            }
        });

        setCheckedPermissions(getDefaultPermissionsByRole(rolSelect?.value));
        resetPasswordToggles();

        requestAnimationFrame(() => {
            formNuevoUsuario.querySelector('input[name="nombre"]')?.blur();
        });
    };

    if (buscador) {
        buscador.addEventListener('input', function () {
            const filtro = this.value.toLowerCase();
            filas.forEach((fila) => {
                fila.style.display = fila.innerText.toLowerCase().includes(filtro) ? '' : 'none';
            });
        });
    }

    document.querySelectorAll('.cambiar-clave-btn').forEach((button) => {
        button.addEventListener('click', function () {
            document.getElementById('usuario_id_cambiar_clave').value = this.dataset.id;
            document.getElementById('nombre_usuario_label').textContent = `Usuario: ${this.dataset.nombre}`;
        });
    });

    if (rolSelect) {
        rolSelect.addEventListener('change', () => {
            setCheckedPermissions(getDefaultPermissionsByRole(rolSelect.value));
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
            setCheckedPermissions([]);
        });
    }

    if (modalNuevoUsuario) {
        modalNuevoUsuario.addEventListener('show.bs.modal', clearNewUserForm);
        modalNuevoUsuario.addEventListener('hidden.bs.modal', clearNewUserForm);
    }

    togglePasswordButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input');
            const icon = button.querySelector('i');
            if (!input || !icon) return;

            const willShowPassword = input.type === 'password';
            input.type = willShowPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !willShowPassword);
            icon.classList.toggle('fa-eye-slash', willShowPassword);
            button.setAttribute('aria-label', willShowPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
});
