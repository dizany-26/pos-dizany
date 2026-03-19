const modalEditar = document.getElementById('modalEditarUsuario');
if (modalEditar) {
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        if (!button) return;

        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const usuario = button.getAttribute('data-usuario');
        const email = button.getAttribute('data-email');
        const rol = button.getAttribute('data-rol');

        modalEditar.querySelector('#editar-id').value = id;
        modalEditar.querySelector('#editar-nombre').value = nombre;
        modalEditar.querySelector('#editar-usuario').value = usuario;
        modalEditar.querySelector('#editar-email').value = email;
        modalEditar.querySelector('#editar-rol').value = rol;
        modalEditar.querySelector('#formEditarUsuario').action = `/usuarios/${id}`;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const buscador = document.getElementById('buscadorUsuarios');
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    const modalNuevoUsuario = document.getElementById('modalNuevoUsuario');
    const formNuevoUsuario = document.getElementById('formNuevoUsuario');
    const rolSelect = document.getElementById('nuevo-rol-id');
    const permisoCheckboxes = Array.from(document.querySelectorAll('.permiso-checkbox'));
    const btnMarcarTodos = document.getElementById('marcarTodosPermisos');
    const btnLimpiar = document.getElementById('limpiarPermisos');
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-btn');
    const empleadoRoleId = String(window.rolesUsuarios?.Empleado ?? '');
    const adminRoleId = String(window.rolesUsuarios?.Administrador ?? '');

    const setCheckedPermissions = (permissions) => {
        const selected = new Set(permissions);
        permisoCheckboxes.forEach((checkbox) => {
            checkbox.checked = selected.has(checkbox.value);
        });
    };

    const applyRoleDefaults = () => {
        if (!rolSelect) return;

        const selectedRole = String(rolSelect.value || '');

        if (selectedRole === empleadoRoleId) {
            setCheckedPermissions(['dashboard.empleado']);
            return;
        }

        if (selectedRole === adminRoleId) {
            setCheckedPermissions(['dashboard.admin']);
            return;
        }

        setCheckedPermissions([]);
    };

    const clearNewUserForm = () => {
        if (!formNuevoUsuario) return;

        formNuevoUsuario.reset();
        formNuevoUsuario.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], select').forEach((field) => {
            if (field.classList.contains('usuario-hidden-autofill')) return;
            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
                return;
            }
            field.value = '';
        });

        permisoCheckboxes.forEach((checkbox) => {
            checkbox.checked = false;
        });

        togglePasswordButtons.forEach((button) => {
            const input = button.parentElement?.querySelector('input');
            const icon = button.querySelector('i');
            if (!input || !icon) return;
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
            button.setAttribute('aria-label', 'Mostrar contraseña');
        });

        window.setTimeout(() => {
            formNuevoUsuario.querySelector('input[name="nombre"]')?.blur();
            applyRoleDefaults();
        }, 0);
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

    if (modalNuevoUsuario) {
        modalNuevoUsuario.addEventListener('show.bs.modal', () => {
            clearNewUserForm();
        });

        modalNuevoUsuario.addEventListener('hidden.bs.modal', () => {
            clearNewUserForm();
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
