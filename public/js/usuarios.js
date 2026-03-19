    const modalEditar = document.getElementById('modalEditarUsuario');
    modalEditar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const id = button.getAttribute('data-id');
        const nombre = button.getAttribute('data-nombre');
        const usuario = button.getAttribute('data-usuario');
        const email = button.getAttribute('data-email'); // ✅ nuevo
        const rol = button.getAttribute('data-rol');
        const permissions = JSON.parse(button.getAttribute('data-permissions') || '[]');

        modalEditar.querySelector('#editar-id').value = id;
        modalEditar.querySelector('#editar-nombre').value = nombre;
        modalEditar.querySelector('#editar-usuario').value = usuario;
        modalEditar.querySelector('#editar-email').value = email; // ✅ nuevo
        modalEditar.querySelector('#editar-rol').value = rol;
        applyPermissions(modalEditar.querySelector('form'), permissions);

        modalEditar.querySelector('#formEditarUsuario').action = `/usuarios/${id}`;
    });

    document.addEventListener('DOMContentLoaded', function () {
        const buscador = document.getElementById('buscadorUsuarios');
        const filas = document.querySelectorAll('#tablaUsuarios tbody tr');

        buscador.addEventListener('input', function () {
            const filtro = this.value.toLowerCase();
            filas.forEach(fila => {
                const texto = fila.innerText.toLowerCase();
                fila.style.display = texto.includes(filtro) ? '' : 'none';
            });
        });
    });
    // cambiar clave
    document.querySelectorAll('.cambiar-clave-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('usuario_id_cambiar_clave').value = this.dataset.id;
            document.getElementById('nombre_usuario_label').textContent = "Usuario: " + this.dataset.nombre;
        });
    });

const ROLE_DEFAULTS = {
    Administrador: ['*'],
    Empleado: ['dashboard.empleado', 'operaciones.ventas', 'operaciones.gastos']
};

function getPermissionCheckboxes(scope) {
    return scope.querySelectorAll('input[name="permissions[]"]');
}

function applyPermissions(scope, permissions) {
    const all = permissions.includes('*');
    getPermissionCheckboxes(scope).forEach(cb => {
        cb.checked = all || permissions.includes(cb.value);
    });
}

function bindPermissionHelpers(scope) {
    const selectAllBtn = scope.querySelector('.permissions-select-all');
    const clearBtn = scope.querySelector('.permissions-clear-all');
    const roleSelect = scope.querySelector('.permission-role-select');

    selectAllBtn?.addEventListener('click', () => {
        getPermissionCheckboxes(scope).forEach(cb => cb.checked = true);
    });

    clearBtn?.addEventListener('click', () => {
        getPermissionCheckboxes(scope).forEach(cb => cb.checked = false);
    });

    roleSelect?.addEventListener('change', () => {
        const roleName = roleSelect.options[roleSelect.selectedIndex]?.dataset.roleName || '';
        const defaults = ROLE_DEFAULTS[roleName] || [];
        applyPermissions(scope, defaults);
    });
}

document.querySelectorAll('#modalNuevoUsuario form, #modalEditarUsuario form').forEach(bindPermissionHelpers);

document.getElementById('modalNuevoUsuario')?.addEventListener('show.bs.modal', function () {
    const form = this.querySelector('form');
    if (!form) return;
    applyPermissions(form, []);
});
