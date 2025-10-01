<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: AdminController
 * Descripción: Gestiona el panel administrativo para usuarios, roles y auditoría de autenticación.
 * Método de uso: Acceder mediante rutas /admin/*.
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class AdminController extends MY_Controller
{
    private const DEFAULT_GUARD = ['admin'];

    /**
     * Nombre: __construct
     * Descripción: Inicializa dependencias, habilita assets del panel y aplica restricciones de acceso.
     * @return void
     * Método de uso: Se ejecuta automáticamente al instanciar el controlador.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function __construct()
    {
        parent::__construct();
        $this->requiereAutenticacion();
        $this->aseguraRoles(self::DEFAULT_GUARD);
        $this->load->model('UserModel');
        $this->load->model('RoleModel');

        $this->addCss(base_url('assets/css/admin.css'));
        $this->addJs(base_url('assets/js/admin.js'));
    }

    /**
     * Nombre: index
     * Descripción: Presenta el tablero principal con métricas de usuarios, roles e intentos de acceso.
     * @return void
     * Método de uso: GET /admin.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function index(): void
    {
        $this->data['title'] = $this->lang->line('auth_admin_dashboard');
        $this->data['metrics'] = [
            'users' => $this->UserModel->countUsers(),
            'roles' => $this->UserModel->countRoles(),
            'attempts' => $this->UserModel->countLoginAttempts(),
        ];
        $this->data['attempts'] = $this->UserModel->getRecentAttempts(10);

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/dashboard', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: users
     * Descripción: Lista los usuarios registrados con filtros básicos.
     * @return void
     * Método de uso: GET /admin/users.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function users(): void
    {
        $search = trim((string)$this->input->get('search', true));
        $users = $this->UserModel->findUsers(50, 0, $search !== '' ? $search : null);
        foreach ($users as $user) {
            $user->roles = $this->UserModel->getUserRoleSlugs($user->id);
        }

        $this->data['title'] = $this->lang->line('auth_admin_users');
        $this->data['search'] = $search;
        $this->data['users'] = $users;

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/users/index', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: createUser
     * Descripción: Permite crear un usuario desde el panel administrativo.
     * @return void
     * Método de uso: GET muestra formulario, POST persiste el nuevo usuario.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function createUser(): void
    {
        $roles = $this->RoleModel->all();
        if ($this->input->method(true) === 'POST') {
            $this->estableceReglasAdminUsuario();
            if ($this->form_validation->run()) {
                $this->guardaUsuarioDesdeAdmin();
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_new_user_button');
        $this->data['roles'] = $roles;
        $this->data['selectedRoles'] = [];

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/users/form', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: editUser
     * Descripción: Actualiza datos de un usuario existente.
     * @param string $userId UUID del usuario.
     * @return void
     * Método de uso: GET carga formulario, POST aplica cambios.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function editUser(string $userId): void
    {
        $user = $this->UserModel->findById($userId);
        if ($user === null) {
            show_404();
            return;
        }

        $roles = $this->RoleModel->all();
        $selectedRoles = $this->UserModel->getUserRoles($userId);

        if ($this->input->method(true) === 'POST') {
            $this->estableceReglasAdminUsuario(true, $userId);
            if ($this->form_validation->run()) {
                $this->actualizaUsuarioDesdeAdmin($user);
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_edit');
        $this->data['user'] = $user;
        $this->data['roles'] = $roles;
        $this->data['selectedRoles'] = $selectedRoles;

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/users/form', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: deleteUser
     * Descripción: Elimina un usuario del sistema.
     * @param string $userId UUID del usuario a eliminar.
     * @return void
     * Método de uso: POST /admin/users/delete/{id}.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function deleteUser(string $userId): void
    {
        if ($this->input->method(true) !== 'POST') {
            show_error('Método no permitido', 405);
            return;
        }

        if ($userId === $this->session->userdata('user_id')) {
            $this->session->set_flashdata('error', 'No puedes eliminar tu propia cuenta.');
            redirect('admin/users');
            return;
        }

        $this->UserModel->deleteUser($userId);
        $this->session->set_flashdata('success', $this->lang->line('auth_user_deleted'));
        redirect('admin/users');
    }

    /**
     * Nombre: roles
     * Descripción: Lista los roles actuales.
     * @return void
     * Método de uso: GET /admin/roles.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function roles(): void
    {
        $this->data['title'] = $this->lang->line('auth_admin_roles');
        $this->data['roles'] = $this->RoleModel->all();

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/roles/index', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: createRole
     * Descripción: Crea un nuevo rol con descripción opcional.
     * @return void
     * Método de uso: GET/POST /admin/roles/create.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function createRole(): void
    {
        if ($this->input->method(true) === 'POST') {
            $this->estableceReglasRol();
            if ($this->form_validation->run()) {
                $payload = [
                    'name' => trim((string)$this->input->post('name', true)),
                    'description' => trim((string)$this->input->post('description', true)) ?: null,
                ];
                $this->RoleModel->create($payload);
                $this->session->set_flashdata('success', $this->lang->line('general_success'));
                redirect('admin/roles');
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_new_user_button');
        $this->data['role'] = null;

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/roles/form', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: editRole
     * Descripción: Modifica un rol existente.
     * @param string $roleId UUID del rol.
     * @return void
     * Método de uso: GET/POST /admin/roles/edit/{id}.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function editRole(string $roleId): void
    {
        $role = $this->RoleModel->find($roleId);
        if ($role === null) {
            show_404();
            return;
        }

        if ($this->input->method(true) === 'POST') {
            $this->estableceReglasRol(true, $roleId);
            if ($this->form_validation->run()) {
                $payload = [
                    'name' => trim((string)$this->input->post('name', true)),
                    'description' => trim((string)$this->input->post('description', true)) ?: null,
                ];
                $this->RoleModel->update($roleId, $payload);
                $this->session->set_flashdata('success', $this->lang->line('general_success'));
                redirect('admin/roles');
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_edit');
        $this->data['role'] = $role;

        $this->load->view('partials/header', $this->data);
        $this->load->view('admin/roles/form', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: deleteRole
     * Descripción: Elimina un rol existente.
     * @param string $roleId UUID del rol.
     * @return void
     * Método de uso: POST /admin/roles/delete/{id}.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function deleteRole(string $roleId): void
    {
        if ($this->input->method(true) !== 'POST') {
            show_error('Método no permitido', 405);
            return;
        }

        $this->RoleModel->delete($roleId);
        $this->session->set_flashdata('success', $this->lang->line('general_success'));
        redirect('admin/roles');
    }

    /**
     * Nombre: aseguraRoles
     * Descripción: Verifica que el usuario autenticado tenga alguno de los roles requeridos.
     * @param array<int,string> $allowedRoles Lista de roles permitidos en minúscula.
     * @return void
     * Método de uso: $this->aseguraRoles(['admin']).
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function aseguraRoles(array $allowedRoles): void
    {
        $userRoles = (array)$this->session->userdata('roles');
        $intersect = array_intersect($allowedRoles, $userRoles);
        if ($intersect === []) {
            show_error('No autorizado', 403);
        }
    }

    /**
     * Nombre: estableceReglasAdminUsuario
     * Descripción: Define reglas de validación para formularios de usuarios administrados.
     * @param bool $isUpdate Indica si se trata de una actualización.
     * @param string|null $userId UUID del usuario existente.
     * @return void
     * Método de uso: $this->estableceReglasAdminUsuario(true, $id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function estableceReglasAdminUsuario(bool $isUpdate = false, ?string $userId = null): void
    {
        $emailRules = 'trim|required|valid_email|regex_match[/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/]';
        if (!$isUpdate) {
            $emailRules .= '|is_unique[users.email]';
        } else {
            $emailRules .= sprintf('|callback__unique_email[%s]', $userId);
        }

        $this->form_validation->set_rules(
            'full_name',
            $this->lang->line('auth_fullname_label'),
            'trim|required|min_length[3]|max_length[120]|regex_match[/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s\'\-]+$/]'
        );
        $this->form_validation->set_rules('email', $this->lang->line('auth_email_label'), $emailRules);

        if (!$isUpdate || ($this->input->post('password') !== '')) {
            $this->form_validation->set_rules(
                'password',
                $this->lang->line('auth_password_label'),
                'trim|required|min_length[8]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w]).{8,}$/]'
            );
            $this->form_validation->set_rules(
                'password_confirmation',
                $this->lang->line('auth_confirm_password_label'),
                'trim|required|matches[password]'
            );
        }

        $this->form_validation->set_rules('is_active', 'Estado', 'trim|required|in_list[0,1]');
    }

    /**
     * Nombre: guardaUsuarioDesdeAdmin
     * Descripción: Persiste un nuevo usuario utilizando datos del formulario administrativo.
     * @return void
     * Método de uso: Invocado tras validación exitosa en createUser().
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function guardaUsuarioDesdeAdmin(): void
    {
        $fullName = trim((string)$this->input->post('full_name', true));
        $email = strtolower(trim((string)$this->input->post('email', true)));
        $password = (string)$this->input->post('password');
        $isActive = (int)$this->input->post('is_active');
        $roles = (array)$this->input->post('roles');

        $validRoles = $this->filtraRolesValidos($roles);

        $payload = [
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
            'is_active' => $isActive,
            'is_locked' => 0,
            'failed_attempts' => 0,
            'locked_until' => null,
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
        ];

        $this->UserModel->createUser($payload, $validRoles);
        $this->session->set_flashdata('success', $this->lang->line('auth_user_created'));
        redirect('admin/users');
    }

    /**
     * Nombre: actualizaUsuarioDesdeAdmin
     * Descripción: Actualiza información del usuario seleccionado, incluida asignación de roles.
     * @param object $user Objeto original del usuario.
     * @return void
     * Método de uso: Invocado tras validación en editUser().
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function actualizaUsuarioDesdeAdmin(object $user): void
    {
        $userId = (string)$user->id;
        $fullName = trim((string)$this->input->post('full_name', true));
        $email = strtolower(trim((string)$this->input->post('email', true)));
        $password = (string)$this->input->post('password');
        $isActive = (int)$this->input->post('is_active');
        $roles = (array)$this->input->post('roles');

        $validRoles = $this->filtraRolesValidos($roles);

        $data = [
            'full_name' => $fullName,
            'email' => $email,
            'is_active' => $isActive,
        ];

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        $this->UserModel->updateUser($userId, $data, $validRoles);
        $this->session->set_flashdata('success', $this->lang->line('auth_user_updated'));
        redirect('admin/users');
    }

    /**
     * Nombre: filtraRolesValidos
     * Descripción: Depura la lista de roles recibida quedándose sólo con UUID existentes.
     * @param array<int,string> $roles Identificadores recibidos del formulario.
     * @return array<int,string> Roles válidos presentes en base de datos.
     * Método de uso: $roles = $this->filtraRolesValidos($_POST['roles']);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function filtraRolesValidos(array $roles): array
    {
        $rolesDisponibles = $this->RoleModel->all();
        $idsDisponibles = array_map(static fn ($role) => $role->id, $rolesDisponibles);
        return array_values(array_intersect($idsDisponibles, $roles));
    }

    /**
     * Nombre: estableceReglasRol
     * Descripción: Configura validaciones para formularios de roles.
     * @param bool $isUpdate Indica operación de actualización.
     * @param string|null $roleId UUID del rol existente.
     * @return void
     * Método de uso: $this->estableceReglasRol(true, $id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function estableceReglasRol(bool $isUpdate = false, ?string $roleId = null): void
    {
        $nameRule = 'trim|required|min_length[3]|max_length[50]|regex_match[/^[a-zA-Z0-9_\-]+$/]';
        if (!$isUpdate) {
            $nameRule .= '|is_unique[roles.name]';
        } else {
            $nameRule .= sprintf('|callback__unique_role[%s]', $roleId);
        }

        $this->form_validation->set_rules('name', $this->lang->line('auth_role_name'), $nameRule);
        $this->form_validation->set_rules('description', $this->lang->line('auth_role_description'), 'trim|max_length[150]');
    }

    /**
     * Nombre: _unique_email
     * Descripción: Callback para verificar correos únicos en edición.
     * @param string $email Correo a evaluar.
     * @param string|null $userId UUID del usuario actual.
     * @return bool true si es único o corresponde al mismo usuario.
     * Método de uso: autom. por Form Validation.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function _unique_email(string $email, ?string $userId = null): bool
    {
        $existing = $this->UserModel->findByEmail(strtolower($email));
        if ($existing === null) {
            return true;
        }

        return (string)$existing->id === (string)$userId;
    }

    /**
     * Nombre: _unique_role
     * Descripción: Callback para asegurar nombres de rol únicos durante actualización.
     * @param string $name Nombre recibido del formulario.
     * @param string|null $roleId UUID actual.
     * @return bool true si el nombre es válido.
     * Método de uso: autom. por Form Validation.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function _unique_role(string $name, ?string $roleId = null): bool
    {
        $role = $this->RoleModel->findByName($name);
        if ($role === null) {
            return true;
        }

        return (string)$role->id === (string)$roleId;
    }
}
