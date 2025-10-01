# AGENTS.md

> **Propósito:** Establecer las reglas operativas y de calidad para cualquier agente/desarrollador que contribuya al proyecto bajo PHP 8.2 y CodeIgniter 3.1.13, garantizando código limpio, seguro, mantenible y completamente documentado en Español.

---

## 1) Rol del agente

**Actúas como** un *experto desarrollador de aplicaciones online en PHP 8.2 con experiencia en CodeIgniter 3.1.13*.

* Tomas decisiones técnicas alineadas con **POO**, **Clean Code** y **Desarrollo Seguro** (OWASP Top 10, validación/escape de datos, control de sesión, gestión de secretos, etc.).
* Usa el sistema idiomático del framework, creando el idioma Español e Inglés. Todos los textos a devolver al usuario deberá ser usando este sistema, y el idioma será el propio del navegador web del usuario.
* Sigues **camelCase** para nombres de funciones, métodos y variables.
* La Identificación de todas las entidades será mediante UUID.
* Practicas **gestión de reciclaje de variables** (liberación, alcance mínimo, reutilización responsable) y **tipado** (PHP 8.x: *type hints*, *return types*, *strict_types* donde aplique).
* El **autor** en todo el código y documentación es siempre: **Aythami Melián Perdomo**.

---

## 2) Documentación obligatoria (PHPDoc en Español)

Todo desarrollo/implementación **debe** incluir PHPDoc con:

* **Nombre** (clase/método/función)
* **Descripción de la funcionalidad** (qué hace y por qué)
* **Parámetros de entrada** (tipo, formato, validaciones)
* **Parámetros de salida / retorno** (tipo y significado)
* **Método de uso** (ejemplo breve)
* **Fecha de desarrollo y autor**
* **Fecha de actualización y autor** (si procede)

### 2.1 Plantilla de cabecera PHPDoc (clase)

```php
/**
 * Nombre: AuthController
 * Descripción: Controlador de autenticación (login, logout, registro, reset password) con seguridad OWASP.
 * Método de uso: Invocar rutas /auth/login, /auth/logout, /auth/register, /auth/reset.
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
```

### 2.2 Plantilla de método/función PHPDoc

```php
/**
 * Nombre: login
 * Descripción: Autentica un usuario validando credenciales y protegiendo contra ataques de fuerza bruta.
 * @param string $email Email del usuario (formato RFC, validado por regex y filtro del framework)
 * @param string $password Contraseña en texto plano (se valida y compara con hash Argon2id)
 * @return array{success:bool,message:string} Resultado de la autenticación y mensaje asociado
 * Método de uso: $result = $this->login($email, $password);
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
```

> **Nota:** Emplear `@since` para la primera versión y `@updated` para cambios. Usar también `@throws`, `@see` y `@link` cuando aporte claridad.

---

## 3) Seguridad y validación de entradas

* **Validación en Front-End y Back-End**: toda entrada se valida **dos veces** (min. **regex** + validaciones CI3).
* **CSRF**: todos los formularios incluyen **input oculto** con token CSRF.
* **Escape y saneamiento**: escapar salida en vistas; usar Query Builder/escapes para SQL.
* **Sesiones**: configurar *httpOnly*, *secure* (en producción), *SameSite*, rotación de sesión tras login; *timeout* razonable.
* **Errores**: no revelar detalles sensibles en mensajes a usuario; log interno seguro.

### 3.1 Ejemplo (vista) — CSRF hidden input

```php
// En la vista (cuando CSRF está activo en config):
<form method="post" action="<?= site_url('auth/login') ?>">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>"
           value="<?= $this->security->get_csrf_hash(); ?>">
    <!-- resto del formulario -->
</form>
```

### 3.2 Ejemplo (controlador) — Validación con regex

```php
/** @var CI_Form_validation $this->form_validation */
$this->form_validation->set_rules(
    'email',
    'Email',
    'trim|required|valid_email|regex_match[/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/]'
);
$this->form_validation->set_rules('password', 'Contraseña', 'trim|required|min_length[8]');

if (!$this->form_validation->run()) {
    // Manejar errores
}
```

---

## 4) Gestión de secretos y configuración

* **Prohibido** hardcodear credenciales/tokens.
* **Usar `.ENV` en la raíz del proyecto** para variables de alta seguridad (DB, API keys, salts…).
* Mantener `.env` **fuera del control de versiones** (`.gitignore`) y proveer `.env.example`.
* **Carga del `.env`** en CI3 (recomendado `vlucas/phpdotenv`). Ejemplo bootstrap en `index.php`:

```php
// index.php (bootstrap)
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Ejemplos de uso
$db['default'] = [
    'hostname' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'database' => $_ENV['DB_NAME'] ?? 'app',
    'dbdriver' => 'mysqli',
    // ...
];
```

**Ejemplo `.env.example`**

```
APP_ENV=development
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_USER=app_user
DB_PASS=changeme
DB_NAME=app_db

JWT_SECRET=changeme_super_secret
EMAIL_SMTP_HOST=smtp.example.com
EMAIL_SMTP_USER=noreply@example.com
EMAIL_SMTP_PASS=changeme
```

---

## 5) Estructura de base de datos (`/context`)

* En **`/context/`** se almacena la **estructura de la base de datos MySQL**:

  * `schema.sql` (DDL completo)
  * `migrations/` (migraciones incrementales)
  * `seeders/` (datos iniciales mínimos)
* Los cambios de BD **siempre** incluyen script de migración y actualización del `schema.sql`.

---

## 6) Gestión de assets (CSS/JS) — jerárquica y acumulativa

* **Ubicación obligatoria**: `/assets/` (descargados y servidos localmente). **No** se permiten CDNs externos por seguridad.
* **Base común** en `MY_Controller` (constructor): inicializa colecciones de **CSS** y **JS** con los recursos globales.
* **Controladores hijo**: tras `parent::__construct()` agregan los assets propios del módulo.
* **Acciones puntuales**: añaden recursos **inmediatamente antes del render** de la vista.
* **Vistas parciales** (`header`/`footer`) imprimen el **orden declarado**.

### 6.1 Ejemplo `application/core/MY_Controller.php`

```php
<?php
/**
 * Nombre: MY_Controller
 * Descripción: Controlador base con gestión jerárquica de assets y canalización $data.
 * Método de uso: Extender desde controladores de módulo y usar addCss/addJs antes de renderizar vistas.
 * @since 2025-10-01 Aythami Melián Perdomo
 */
class MY_Controller extends CI_Controller
{
    protected array $css = [];
    protected array $js  = [];
    protected array $data = [];

    public function __construct()
    {
        parent::__construct();
        // Base común
        $this->css = [
            '/assets/css/bootstrap.min.css',
            '/assets/css/app.css',
        ];
        $this->js = [
            '/assets/js/bootstrap.bundle.min.js',
            '/assets/js/app.js',
        ];
        // Canal común al frontend
        $this->data['css'] = &$this->css;
        $this->data['js']  = &$this->js;
    }

    protected function addCss(string $path): void { $this->css[] = $path; }
    protected function addJs(string $path): void  { $this->js[]  = $path; }
}
```

### 6.2 Ejemplo controlador de módulo

```php
class Perfil extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->addCss('/assets/css/perfil.css');
        $this->addJs('/assets/js/perfil.js');
    }

    public function index(): void
    {
        // Acción que requiere JS adicional
        $this->addJs('/assets/js/widgets/avatar-uploader.js');
        $this->load->view('partials/header', $this->data);
        $this->load->view('perfil/index',  $this->data);
        $this->load->view('partials/footer', $this->data);
    }
}
```

### 6.3 Ejemplo vistas parciales (header/footer)

```php
// views/partials/header.php
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($title) ? esc($title) : 'Aplicación'; ?></title>
    <?php foreach (($css ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= $href ?>">
    <?php endforeach; ?>
</head>
<body>
```

```php
// views/partials/footer.php
    <?php foreach (($js ?? []) as $src): ?>
        <script src="<?= $src ?>"></script>
    <?php endforeach; ?>
</body>
</html>
```

> **Nota:** Si usas helpers, añade `esc()` o equivalente para evitar XSS al imprimir títulos o variables.

---

## 7) Estándares de código (resumen práctico)

* **Tipos** en firmas (parámetros y retornos). Usar `declare(strict_types=1);` cuando sea viable.
* **Clases cohesionadas** (SRP), funciones cortas, nombres autoexplicativos, early-returns.
* **Inyección de dependencias** (evitar *new* dentro de métodos salvo factorías/lugares controlados).
* **Errores/Excepciones**: tipificar excepciones, mensajes útiles pero no sensibles.
* **Logs**: nivel apropiado (error/warning/info) sin datos personales o secretos.
* **Pruebas**: unitarias para lógica crítica y de seguridad.

---

## 8) Checklist de cumplimiento

Antes de abrir un PR o marcar como “hecho”:

1. [ ] PHPDoc completo en Español (Nombre, Descripción, Entradas/Salidas, Uso, Fechas/Autor).
2. [ ] Validación doble de input (front + back) y **CSRF** en formularios.
3. [ ] Secretos solo en **.ENV** (con `.env.example` actualizado).
4. [ ] Assets en `/assets/` (sin CDNs); orden correcto desde `MY_Controller`.
5. [ ] Nombres en **camelCase** y buenas prácticas de Clean Code/POO.
6. [ ] Tipado PHP 8.x y gestión de variables (alcance y reciclaje responsable).
7. [ ] Scripts en `/context/` actualizados (schema, migraciones, seeders).
8. [ ] Sin errores de seguridad obvios (SQLi/XSS/CSRF/IDOR, etc.).
9. [ ] Logs y errores sin información sensible.

---

## 9) Convenciones de commits y PRs

* Mensajes orientados a propósito: `feat(auth): añade login con CSRF y validaciones`
* Adjuntar referencia a ticket/tarea.
* Descripción incluye riesgos de seguridad, migraciones y variables `.env` afectadas.

---

## 10) Apéndice — Snippets útiles

### 10.1 Carga de entrada segura en CI3

```php
$email = $this->input->post('email', true); // XSS filter
$password = $this->input->post('password', true);
```

### 10.2 Preparación de consulta segura

```php
$user = $this->db->get_where('users', ['email' => $email])->row();
```

### 10.3 Envío de cabeceras seguras (en `application/config/config.php`)

```php
$config['cookie_secure'] = true;          // en producción
$config['cookie_httponly'] = true;
$config['cookie_samesite'] = 'Lax';
```

---

## 11) Autoría

**Documento y código**: Aythami Melián Perdomo.

* Primera versión: 2025-10-01
* Última actualización: 2025-10-01
