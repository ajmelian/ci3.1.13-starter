# Plataforma base CI 3 + Autenticación segura

**Autor:** Aythami Melián Perdomo — 2025-10-01

## Descripción
Aplicación base desarrollada en PHP 8.2 y CodeIgniter 3.1.13 que implementa un sistema completo de autenticación con control de roles, bloqueo de sesión por inactividad, recuperación de credenciales y verificación OTP compatible con Google Authenticator. El frontend responde con HTML5 y un tema local tipo Bootstrap 5 optimizado para dispositivos móviles y pantallas grandes.

## Requisitos previos
- PHP ≥ 8.2 con extensiones `openssl`, `mbstring`, `intl` y `mysqli`.
- Servidor web (Apache/Nginx) configurado para apuntar a `index.php`.
- MySQL ≥ 8.0 (o MariaDB compatible) con motor InnoDB.
- Composer para gestionar dependencias (`vlucas/phpdotenv`).
- Carpeta `system/` oficial de CodeIgniter 3.1.13 colocada en la raíz del proyecto.

## Instalación
1. Clona el repositorio en tu entorno de desarrollo.
2. Ejecuta `composer install` para cargar dependencias.
3. Copia `.env.example` a `.env` y ajusta credenciales (base de datos, correo, claves). **Nunca** subas tu `.env` a control de versiones.
4. Crea la base de datos definida en `.env` (`DB_NAME`).
5. Aplica el esquema inicial ejecutando `context/migrations/20251001000001_create_auth_tables.sql` y sincroniza si es necesario con `context/schema.sql`.
6. Inserta datos base ejecutando `context/seeders/20251001000001_seed_core_data.sql`.
7. Configura el virtual host / server block para apuntar al directorio del proyecto.
8. Verifica permisos de `application/cache` y `application/logs` (escritura para el proceso web).

## Variables de entorno clave
- `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_NAME`, `APP_KEY`.
- Parámetros de conexión: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.
- Credenciales SMTP (`MAIL_*`) para enviar correos de recuperación.
- `OTP_ISSUER` define el nombre mostrado en Google Authenticator.
- `PASSWORD_RESET_TOKEN_TTL` controla la validez (segundos) del token de reinicio.
- `SESSION_LOCK_TIMEOUT` bloquea sesiones tras inactividad (segundos).

## Migraciones y seeders
Los scripts SQL se encuentran en `context/`:
- `migrations/20251001000001_create_auth_tables.sql`: crea todas las tablas (`users`, `roles`, `user_roles`, `password_resets`, `login_attempts`).
- `seeders/20251001000001_seed_core_data.sql`: inserta roles base (admin, manager, user) y un superusuario inicial (`admin@example.com` / contraseña `Admin123!`).
- `schema.sql`: refleja el estado consolidado del esquema para revisiones rápidas.

## Funcionalidades principales
- **Autenticación segura:** contraseñas Argon2id, registro, login, logout y reestablecimiento con tokens temporales.
- **Doble factor (OTP):** activación/desactivación con generación de URI `otpauth://` para Google Authenticator y validación en login.
- **Gestión de sesiones:** bloqueo manual y automático tras inactividad configurable, con pantalla de desbloqueo.
- **Gestión de usuarios y roles:** panel administrativo (HTML5 + CSS local) con alta/edición/baja y asignación de roles.
- **Auditoría:** registro de intentos de acceso (IP, agente, resultado) visibles en el panel.
- **Internacionalización:** textos disponibles en Español e Inglés según `Accept-Language` del navegador.
- **Assets locales:** CSS/JS en `assets/` sin uso de CDN externos.

## Seguridad aplicada
- Validación doble (frontend con HTML5/regex y backend con `Form_validation`).
- CSRF habilitado globalmente, tokens incluidos en todos los formularios.
- Salida escapada en vistas con `html_escape` para mitigar XSS.
- Sesiones endurecidas (`httpOnly`, `SameSite`, regeneración en login, bloqueo por inactividad).
- Gestión de secretos vía `.env` (cargado con `vlucas/phpdotenv`).
- Auditoría de intentos con contador de fallos y bloqueo temporal configurable.

## Frontend
El layout se alimenta desde `$this->data` en `MY_Controller`, gestionando listas `css`/`js` y variables comunes (`appName`, `language`). El CSS base (`assets/css/bootstrap.min.css` y `assets/css/app.css`) ofrece una capa responsiva light inspirada en Bootstrap 5 sin depender de terceros.

## Credenciales de prueba
Seeder inicial:
- Usuario: `admin@example.com`
- Contraseña: `Admin123!`

Tras el primer login se recomienda activar OTP y cambiar la contraseña.

## Estructura relevante
```
application/
  config/        Configuración (autoload, config, database, rutas)
  controllers/   AuthController, AdminController
  core/          MY_Controller (gestor de assets y sesión)
  helpers/       uuid_helper.php
  language/      Archivos Español / Inglés
  libraries/     OtpService.php
  models/        UserModel.php, RoleModel.php
  views/         Vistas auth/, admin/ y parciales
assets/          CSS y JS locales
context/         schema.sql, migrations/, seeders/
.env.example     Plantilla de configuración segura
```

## Próximos pasos sugeridos
- Integrar la carpeta oficial `system/` y configurar rutas amigables en el servidor web.
- Añadir pruebas unitarias para modelos críticos (`UserModel`, `OtpService`).
- Programar jobs de limpieza (tokens caducados, intentos antiguos) mediante cron/CLI.
