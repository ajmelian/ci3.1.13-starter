<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: AuthController
 * Descripción: Controlador responsable de autenticación, registro, recuperación de credenciales y gestión de OTP.
 * Método de uso: Acceder mediante rutas /auth/* definidas en el router principal.
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class AuthController extends MY_Controller
{
    private const MAX_FAILED_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    /**
     * Nombre: __construct
     * Descripción: Carga dependencias críticas para la autenticación segura y define el tiempo de bloqueo de sesión.
     * @return void
     * Método de uso: Se ejecuta automáticamente al instanciar el controlador.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('UserModel');
        $this->load->model('RoleModel');
        $this->load->library('OtpService');
        $this->load->library('email');


        $this->form_validation->set_error_delimiters('<div class="alert alert-danger">', '</div>');
    }

    /**
     * Nombre: login
     * Descripción: Muestra el formulario de acceso y procesa las credenciales del usuario.
     * @return void
     * Método de uso: Acceder vía GET para visualizar, enviar POST con email y password para autenticar.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function login(): void
    {
        if ($this->session->userdata('user_id') && !$this->session->userdata('session_locked')) {
            redirect('admin');
            return;
        }

        if ($this->input->method(true) === 'POST') {
            $this->procesaLogin();
            return;
        }

        $this->data['title'] = $this->lang->line('auth_login_title');
        $this->data['subtitle'] = $this->lang->line('auth_login_subtitle');
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/login', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: procesaLogin
     * Descripción: Ejecuta la lógica de validación, verificación y establecimiento de sesión.
     * @return void
     * Método de uso: Invocado internamente cuando se recibe una petición POST en login.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function procesaLogin(): void
    {
        $this->estableceReglasLogin();
        if (!$this->form_validation->run()) {
            $this->data['title'] = $this->lang->line('auth_login_title');
            $this->data['subtitle'] = $this->lang->line('auth_login_subtitle');
            $this->load->view('partials/header', $this->data);
            $this->load->view('auth/login', $this->data);
            $this->load->view('partials/footer', $this->data);
            return;
        }

        $email = strtolower(trim($this->input->post('email', true)));
        $password = (string)$this->input->post('password');
        $remember = (bool)$this->input->post('remember');
        $ipAddress = $this->input->ip_address();
        $userAgent = substr((string)$this->input->user_agent(), 0, 255);

        $user = $this->UserModel->findByEmail($email);
        if ($user === null) {
            $this->UserModel->logLoginAttempt(null, $ipAddress, $userAgent, false);
            $this->session->set_flashdata('error', $this->lang->line('auth_invalid_credentials'));
            redirect('auth/login');
            return;
        }

        if ((int)$user->is_active === 0) {
            $this->session->set_flashdata('error', $this->lang->line('auth_account_inactive'));
            redirect('auth/login');
            return;
        }

        if ($user->locked_until !== null && strtotime((string)$user->locked_until) > time()) {
            $this->session->set_flashdata('error', $this->lang->line('auth_account_locked'));
            redirect('auth/login');
            return;
        }

        if (!password_verify($password, (string)$user->password_hash)) {
            $this->UserModel->incrementFailedAttempts($user->id, self::MAX_FAILED_ATTEMPTS, self::LOCK_MINUTES);
            $this->UserModel->logLoginAttempt($user->id, $ipAddress, $userAgent, false);
            $this->session->set_flashdata('error', $this->lang->line('auth_invalid_credentials'));
            redirect('auth/login');
            return;
        }

        $this->UserModel->resetFailedAttempts($user->id);

        $this->session->sess_regenerate(true);
        $roles = $this->UserModel->getUserRoles($user->id);

        $sessionData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_full_name' => $user->full_name,
            'roles' => $roles,
            'session_locked' => false,
            'remember_me' => $remember,
            'last_activity' => time(),
            'two_factor_required' => (int)$user->two_factor_enabled === 1,
            'two_factor_verified' => (int)$user->two_factor_enabled === 0,
        ];

        $this->session->set_userdata($sessionData);

        $this->UserModel->updateUserAttributes($user->id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $this->UserModel->logLoginAttempt($user->id, $ipAddress, $userAgent, true);

        if ((int)$user->two_factor_enabled === 1) {
            $this->session->set_userdata('pending_two_factor_user', $user->id);
            redirect('auth/otp');
            return;
        }

        $this->session->set_flashdata('success', $this->lang->line('auth_login_success'));
        redirect('admin');
    }

    /**
     * Nombre: estableceReglasLogin
     * Descripción: Define las reglas de validación para el formulario de login.
     * @return void
     * Método de uso: Invocado internamente antes de validar las credenciales.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function estableceReglasLogin(): void
    {
        $this->form_validation->set_rules(
            'email',
            $this->lang->line('auth_email_label'),
            'trim|required|valid_email|regex_match[/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/]'
        );
        $this->form_validation->set_rules(
            'password',
            $this->lang->line('auth_password_label'),
            'trim|required|min_length[8]'
        );
    }

    /**
     * Nombre: register
     * Descripción: Gestiona el registro de nuevos usuarios finales con rol por defecto.
     * @return void
     * Método de uso: GET para mostrar formulario, POST para registrar.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function register(): void
    {
        if ($this->input->method(true) === 'POST') {
            $this->estableceReglasRegistro();
            if ($this->form_validation->run()) {
                $this->creaUsuarioDesdeFormulario();
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_register_title');
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/register', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: estableceReglasRegistro
     * Descripción: Define las validaciones para creación de cuentas.
     * @return void
     * Método de uso: Invocado internamente en register().
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function estableceReglasRegistro(): void
    {
        $this->form_validation->set_rules(
            'full_name',
            $this->lang->line('auth_fullname_label'),
            'trim|required|min_length[3]|max_length[120]|regex_match[/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s\'\-]+$/]'
        );
        $this->form_validation->set_rules(
            'email',
            $this->lang->line('auth_email_label'),
            'trim|required|valid_email|is_unique[users.email]|regex_match[/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/]'
        );
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

    /**
     * Nombre: creaUsuarioDesdeFormulario
     * Descripción: Persiste un nuevo usuario tomando los datos validados del formulario.
     * @return void
     * Método de uso: Invocado tras validación exitosa en register().
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function creaUsuarioDesdeFormulario(): void
    {
        $fullName = trim((string)$this->input->post('full_name', true));
        $email = strtolower(trim((string)$this->input->post('email', true)));
        $password = (string)$this->input->post('password');

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $payload = [
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $hash,
            'is_active' => 1,
            'is_locked' => 0,
            'failed_attempts' => 0,
            'locked_until' => null,
            'two_factor_secret' => null,
            'two_factor_enabled' => 0,
        ];

        $defaultRole = $this->RoleModel->findByName('user');
        $roles = $defaultRole ? [$defaultRole->id] : [];

        if ($this->UserModel->createUser($payload, $roles)) {
            $this->session->set_flashdata('success', $this->lang->line('auth_register_success'));
            redirect('auth/login');
        } else {
            $this->session->set_flashdata('error', $this->lang->line('auth_register_error'));
            redirect('auth/register');
        }
    }

    /**
     * Nombre: logout
     * Descripción: Cierra la sesión del usuario autenticado y limpia sus datos.
     * @return void
     * Método de uso: Acceder vía GET a /auth/logout.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function logout(): void
    {
        $this->session->sess_destroy();
        $this->session->set_flashdata('success', $this->lang->line('auth_logout_success'));
        redirect('auth/login');
    }

    /**
     * Nombre: forgotPassword
     * Descripción: Presenta formulario para solicitar restablecimiento y envía enlace seguro.
     * @return void
     * Método de uso: GET muestra formulario, POST procesa solicitud.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function forgotPassword(): void
    {
        if ($this->input->method(true) === 'POST') {
            $this->form_validation->set_rules(
                'email',
                $this->lang->line('auth_email_label'),
                'trim|required|valid_email|regex_match[/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/]'
            );

            if ($this->form_validation->run()) {
                $this->procesaSolicitudReset();
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_reset_title');
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/forgot', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: procesaSolicitudReset
     * Descripción: Genera token de restablecimiento, lo almacena y envía correo con instrucciones.
     * @return void
     * Método de uso: Invocado tras validación positiva en forgotPassword().
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function procesaSolicitudReset(): void
    {
        $email = strtolower(trim((string)$this->input->post('email', true)));
        $user = $this->UserModel->findByEmail($email);

        $ttl = (int)($_ENV['PASSWORD_RESET_TOKEN_TTL'] ?? 3600);

        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            $this->UserModel->createPasswordResetToken($user->id, $token, $ttl);
            $this->enviaCorreoRecuperacion($user->email, $user->full_name, $token);
        }

        $this->session->set_flashdata('success', $this->lang->line('auth_reset_email_sent'));
        redirect('auth/forgot');
    }

    /**
     * Nombre: resetPassword
     * Descripción: Permite establecer una nueva contraseña usando un token válido.
     * @param string $token Token de recuperación recibido en el correo.
     * @return void
     * Método de uso: GET muestra formulario, POST valida y guarda nueva contraseña.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function resetPassword(string $token): void
    {
        $reset = $this->UserModel->getResetByToken($token);
        if ($reset === null) {
            $this->session->set_flashdata('error', $this->lang->line('auth_reset_invalid_token'));
            redirect('auth/forgot');
            return;
        }

        if ($this->input->method(true) === 'POST') {
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

            if ($this->form_validation->run()) {
                $hash = password_hash((string)$this->input->post('password'), PASSWORD_ARGON2ID);
                $this->UserModel->updateUserAttributes($reset->user_id, ['password_hash' => $hash]);
                $this->UserModel->purgeResetTokens($reset->user_id);
                $this->session->set_flashdata('success', $this->lang->line('auth_reset_success'));
                redirect('auth/login');
                return;
            }
        }

        $this->data['title'] = $this->lang->line('auth_reset_title');
        $this->data['token'] = $token;
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/reset', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: enviaCorreoRecuperacion
     * Descripción: Construye y envía el correo electrónico con el enlace para restablecer la contraseña.
     * @param string $email Correo destino.
     * @param string $fullName Nombre del usuario.
     * @param string $token Token generado.
     * @return void
     * Método de uso: Invocado internamente tras registrar el token.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function enviaCorreoRecuperacion(string $email, string $fullName, string $token): void
    {
        $resetUrl = site_url('auth/reset/' . $token);
        $message = '<p>' . sprintf($this->lang->line('auth_reset_email_sent')) . '</p>';
        $message .= '<p><a href="' . html_escape($resetUrl) . '">' . html_escape($resetUrl) . '</a></p>';

        $this->email->from($_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Soporte');
        $this->email->to($email);
        $this->email->subject('Recuperación de contraseña');
        $this->email->message($message);
        @$this->email->send();
    }

    /**
     * Nombre: lockSession
     * Descripción: Bloquea la sesión actual y redirige a la pantalla de desbloqueo.
     * @return void
     * Método de uso: GET /auth/lock.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function lockSession(): void
    {
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }

        $this->session->set_userdata([
            'session_locked' => true,
            'locked_at' => time(),
        ]);

        $this->data['title'] = $this->lang->line('auth_lock_title');
        $this->data['subtitle'] = $this->lang->line('auth_lock_subtitle');
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/lock', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: unlockSession
     * Descripción: Solicita la contraseña al usuario para reactivar la sesión bloqueada.
     * @return void
     * Método de uso: POST desde la vista lock.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function unlockSession(): void
    {
        if (!$this->session->userdata('user_id')) {
            redirect('auth/login');
            return;
        }

        if ($this->input->method(true) !== 'POST') {
            redirect('auth/lock');
            return;
        }

        $this->form_validation->set_rules(
            'password',
            $this->lang->line('auth_password_label'),
            'trim|required|min_length[8]'
        );

        if (!$this->form_validation->run()) {
            $this->session->set_flashdata('error', $this->lang->line('auth_unlock_error'));
            redirect('auth/lock');
            return;
        }

        $password = (string)$this->input->post('password');
        $userId = (string)$this->session->userdata('user_id');
        $user = $this->UserModel->findById($userId);
        if ($user === null || !password_verify($password, (string)$user->password_hash)) {
            $this->session->set_flashdata('error', $this->lang->line('auth_unlock_error'));
            redirect('auth/lock');
            return;
        }

        $this->session->set_userdata([
            'session_locked' => false,
            'locked_at' => null,
            'last_activity' => time(),
        ]);

        redirect('admin');
    }
    /**
     * Nombre: verifyLoginOtp
     * Descripción: Gestiona la verificación del código OTP posterior al inicio de sesión con segundo factor obligatorio.
     * @return void
     * Método de uso: GET muestra formulario, POST valida código OTP.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function verifyLoginOtp(): void
    {
        $pendingUserId = $this->session->userdata('pending_two_factor_user');
        if (!$pendingUserId) {
            redirect('auth/login');
            return;
        }

        $user = $this->UserModel->findById((string)$pendingUserId);
        if ($user === null) {
            $this->session->sess_destroy();
            redirect('auth/login');
            return;
        }

        if ($this->input->method(true) === 'POST') {
            $code = trim((string)$this->input->post('otp_code', true));
            if ($user->two_factor_secret === null || !$this->otpservice->verifyCode($user->two_factor_secret, $code)) {
                $this->session->set_flashdata('error', $this->lang->line('auth_two_factor_error'));
                redirect('auth/otp/verify');
                return;
            }

            $this->session->set_userdata([
                'two_factor_verified' => true,
                'pending_two_factor_user' => null,
            ]);
            $this->session->set_flashdata('success', $this->lang->line('auth_two_factor_success'));
            redirect('admin');
            return;
        }

        $this->data['title'] = $this->lang->line('auth_two_factor_label');
        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/otp_verify', $this->data);
        $this->load->view('partials/footer', $this->data);
    }


    /**
     * Nombre: manageOtp
     * Descripción: Muestra la pantalla de gestión del segundo factor para el usuario autenticado.
     * @return void
     * Método de uso: GET /auth/otp.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function manageOtp(): void
    {
        $this->verificaSesionActiva();

        $user = $this->UserModel->findById($this->session->userdata('user_id'));
        if ($user === null) {
            redirect('auth/login');
            return;
        }

        $secret = $user->two_factor_secret ?? $this->otpservice->generateSecret();
        $issuer = $_ENV['OTP_ISSUER'] ?? 'CI Gestión Segura';
        $uri = $this->otpservice->getProvisioningUri($secret, $user->email, $issuer);

        $this->data['title'] = $this->lang->line('auth_otp_title');
        $this->data['otpSecret'] = $secret;
        $this->data['otpUri'] = $uri;
        $this->data['twoFactorEnabled'] = (int)$user->two_factor_enabled === 1;

        $this->load->view('partials/header', $this->data);
        $this->load->view('auth/otp', $this->data);
        $this->load->view('partials/footer', $this->data);
    }

    /**
     * Nombre: enableOtp
     * Descripción: Activa la verificación OTP después de validar el código proporcionado.
     * @return void
     * Método de uso: POST /auth/otp/enable.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function enableOtp(): void
    {
        $this->verificaSesionActiva();

        $code = trim((string)$this->input->post('otp_code', true));
        $secret = trim((string)$this->input->post('otp_secret', true));

        if ($secret === '' || !$this->otpservice->verifyCode($secret, $code)) {
            $this->session->set_flashdata('error', $this->lang->line('auth_otp_invalid'));
            redirect('auth/otp');
            return;
        }

        $this->UserModel->updateUserAttributes((string)$this->session->userdata('user_id'), [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $secret,
        ]);

        $this->session->set_flashdata('success', $this->lang->line('auth_otp_enabled_success'));
        redirect('auth/otp');
    }

    /**
     * Nombre: disableOtp
     * Descripción: Desactiva la autenticación de dos factores previo chequeo de contraseña.
     * @return void
     * Método de uso: POST /auth/otp/disable.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function disableOtp(): void
    {
        $this->verificaSesionActiva();
        $password = (string)$this->input->post('password');
        $user = $this->UserModel->findById((string)$this->session->userdata('user_id'));

        if ($user === null || !password_verify($password, (string)$user->password_hash)) {
            $this->session->set_flashdata('error', $this->lang->line('auth_unlock_error'));
            redirect('auth/otp');
            return;
        }

        $this->UserModel->updateUserAttributes($user->id, [
            'two_factor_enabled' => 0,
            'two_factor_secret' => null,
        ]);

        $this->session->set_flashdata('success', $this->lang->line('auth_otp_disabled_success'));
        redirect('auth/otp');
    }

    /**
     * Nombre: verificaSesionActiva
     * Descripción: Garantiza que el usuario esté autenticado y no tenga la sesión bloqueada.
     * @return void
     * Método de uso: Invocado en métodos protegidos.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function verificaSesionActiva(): void
    {
        if (!$this->session->userdata('user_id') || $this->session->userdata('session_locked')) {
            redirect('auth/login');
            exit;
        }

        if ($this->session->userdata('two_factor_required') && !$this->session->userdata('two_factor_verified')) {
            redirect('auth/otp/verify');
            exit;
        }
    }
}
