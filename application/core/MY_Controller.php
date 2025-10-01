<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: MY_Controller
 * Descripción: Controlador base que centraliza la carga de assets, los metadatos comunes y el flujo seguro hacia las vistas.
 * Método de uso: Extender la clase desde controladores de módulo y utilizar addCss/addJs antes de renderizar vistas.
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class MY_Controller extends CI_Controller
{
    protected array $css = [];
    protected array $js = [];
    protected array $data = [];
    protected int $lockTimeout = 900;

    /**
     * Nombre: __construct
     * Descripción: Inicializa el controlador base configurando assets globales, idioma y canal de datos.
     * @return void No retorna datos.
     * Método de uso: parent::__construct() desde controladores hijos.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function __construct()
    {
        parent::__construct();

        $this->css = [
            base_url('assets/css/bootstrap.min.css'),
            base_url('assets/css/app.css'),
        ];

        $this->js = [
            base_url('assets/js/bootstrap.bundle.min.js'),
            base_url('assets/js/app.js'),
        ];

        $this->data = [
            'css' => &$this->css,
            'js'  => &$this->js,
        ];

        $this->configuraIdioma();
        $this->data['appName'] = $this->config->item('app_name') ?? 'Aplicación';
        $this->data['language'] = $this->config->item('language') ?? 'spanish';
        $this->lockTimeout = (int)($_ENV['SESSION_LOCK_TIMEOUT'] ?? 900);
        $this->verificaSesionPorInactividad();
    }

    /**
     * Nombre: verificaSesionPorInactividad
     * Descripción: Bloquea la sesión cuando se supera el tiempo de inactividad permitido.
     * @return void
     * Método de uso: Invocado automáticamente en el constructor.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function verificaSesionPorInactividad(): void
    {
        $userId = $this->session->userdata('user_id');
        $sessionLocked = $this->session->userdata('session_locked');
        $lastActivity = (int)$this->session->userdata('last_activity');

        if (!$userId || $sessionLocked) {
            return;
        }

        if ($lastActivity > 0 && (time() - $lastActivity) > $this->lockTimeout) {
            $this->session->set_userdata([
                'session_locked' => true,
                'locked_at' => time(),
            ]);
            redirect('auth/lock');
            exit;
        }

        $this->session->set_userdata('last_activity', time());
    }

    /**
     * Nombre: addCss
     * Descripción: Registra un archivo CSS adicional para la vista actual.
     * @param string $path Ruta relativa o absoluta al recurso CSS (validada como URL).
     * @return void No retorna datos.
     * Método de uso: $this->addCss(base_url('assets/css/modulo.css'));
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    protected function addCss(string $path): void
    {
        $this->css[] = $path;
    }

    /**
     * Nombre: addJs
     * Descripción: Registra un archivo JavaScript adicional para la vista actual.
     * @param string $path Ruta relativa o absoluta al recurso JS (validada como URL).
     * @return void No retorna datos.
     * Método de uso: $this->addJs(base_url('assets/js/modulo.js'));
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    protected function addJs(string $path): void
    {
        $this->js[] = $path;
    }

    /**
     * Nombre: configuraIdioma
     * Descripción: Determina el idioma preferido del usuario y lo carga en el sistema de traducciones.
     * @return void No retorna datos.
     * Método de uso: Invocado automáticamente en el constructor.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function configuraIdioma(): void
    {
        $idiomaNavegador = $this->input->server('HTTP_ACCEPT_LANGUAGE', true) ?? 'es';
        $idiomaSeleccionado = $this->determinaIdioma($idiomaNavegador);
        $this->config->set_item('language', $idiomaSeleccionado);
        $this->lang->load('auth', $idiomaSeleccionado);
        $this->lang->load('general', $idiomaSeleccionado);
    }

    /**
     * Nombre: determinaIdioma
     * Descripción: Convierte la cabecera HTTP_ACCEPT_LANGUAGE en un idioma soportado por la plataforma.
     * @param string $idiomaCabecera Cadena con los idiomas preferidos del navegador.
     * @return string Idioma soportado (es/en) para la carga de archivos de idioma.
     * Método de uso: $idioma = $this->determinaIdioma('es-ES,es;q=0.9,en;q=0.8');
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function determinaIdioma(string $idiomaCabecera): string
    {
        $idiomaCabecera = strtolower($idiomaCabecera);
        if (str_contains($idiomaCabecera, 'en')) {
            return 'english';
        }

        return 'spanish';
    }
}
