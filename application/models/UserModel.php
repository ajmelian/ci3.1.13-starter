<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: UserModel
 * Descripción: Gestiona las operaciones CRUD de usuarios, sus roles y atributos de seguridad.
 * Método de uso: Cargar desde controladores mediante $this->load->model('UserModel');
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class UserModel extends CI_Model
{
    private const TABLE = 'users';
    private const ROLES_TABLE = 'roles';
    private const PIVOT_TABLE = 'user_roles';
    private const RESET_TABLE = 'password_resets';
    private const ATTEMPT_TABLE = 'login_attempts';

    /**
     * Nombre: createUser
     * Descripción: Inserta un nuevo usuario en la base de datos junto con sus roles asociados.
     * @param array{full_name:string,email:string,password_hash:string,is_active:int,is_locked:int,two_factor_secret:?string,two_factor_enabled:int,locked_until:?string} $data Datos principales del usuario.
     * @param string[] $roles Identificadores UUID de los roles a asociar.
     * @return bool true si la operación finaliza correctamente, false en caso contrario.
     * Método de uso: $this->UserModel->createUser($payload, $roleIds);
     * @throws Throwable Cuando la transacción falla y se revierte.
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function createUser(array $data, array $roles = []): bool
    {
        $this->db->trans_start();

        $userId = uuid_v4();
        $payload = array_merge($data, [
            'id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->insert(self::TABLE, $payload);

        foreach ($roles as $roleId) {
            $this->db->insert(self::PIVOT_TABLE, [
                'id' => uuid_v4(),
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->trans_complete();

        return $this->db->trans_status();
    }

    /**
     * Nombre: updateUser
     * Descripción: Actualiza los datos principales del usuario y sincroniza sus roles.
     * @param string $userId UUID del usuario a actualizar.
     * @param array<string,mixed> $data Datos a actualizar en la tabla de usuarios.
     * @param string[] $roles Relación de roles (UUID) a asociar.
     * @return bool true si se actualiza correctamente, false en otro caso.
     * Método de uso: $this->UserModel->updateUser($id, $payload, $roleIds);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function updateUser(string $userId, array $data, array $roles = []): bool
    {
        $this->db->trans_start();

        if (!empty($data)) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $userId)->update(self::TABLE, $data);
        }

        $this->db->where('user_id', $userId)->delete(self::PIVOT_TABLE);

        foreach ($roles as $roleId) {
            $this->db->insert(self::PIVOT_TABLE, [
                'id' => uuid_v4(),
                'user_id' => $userId,
                'role_id' => $roleId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Nombre: updateUserAttributes
     * Descripción: Actualiza atributos sincrónicos del usuario sin tocar roles.
     * @param string $userId UUID del usuario.
     * @param array<string,mixed> $data Datos a persistir.
     * @return bool true si se actualiza correctamente.
     * Método de uso: $this->UserModel->updateUserAttributes($id, ['failed_attempts' => 0]);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function updateUserAttributes(string $userId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $userId)->update(self::TABLE, $data);
        return $this->db->affected_rows() >= 0;
    }

    /**
     * Nombre: findByEmail
     * Descripción: Obtiene un usuario a partir de su correo electrónico.
     * @param string $email Correo electrónico a buscar (ya normalizado a minúsculas).
     * @return object|null Registro del usuario o null si no existe.
     * Método de uso: $user = $this->UserModel->findByEmail($email);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function findByEmail(string $email): ?object
    {
        return $this->db->get_where(self::TABLE, ['email' => $email])->row();
    }

    /**
     * Nombre: findById
     * Descripción: Recupera un usuario por su UUID.
     * @param string $userId UUID del usuario.
     * @return object|null Registro del usuario o null si no existe.
     * Método de uso: $user = $this->UserModel->findById($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function findById(string $userId): ?object
    {
        return $this->db->get_where(self::TABLE, ['id' => $userId])->row();
    }

    /**
     * Nombre: findUsers
     * Descripción: Obtiene el listado paginado de usuarios para la vista administrativa.
     * @param int $limit Número máximo de registros.
     * @param int $offset Desplazamiento para la paginación.
     * @param string|null $search Palabra clave opcional para filtrar por nombre o correo.
     * @return array<int,object> Colección de usuarios encontrados.
     * Método de uso: $users = $this->UserModel->findUsers(20, 0, 'juan');
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function findUsers(int $limit = 25, int $offset = 0, ?string $search = null): array
    {
        if ($search !== null && $search !== '') {
            $this->db->group_start()
                ->like('full_name', $search)
                ->or_like('email', $search)
                ->group_end();
        }

        $this->db->limit($limit, $offset);
        $this->db->order_by('created_at', 'DESC');

        return $this->db->get(self::TABLE)->result();
    }

    /**
     * Nombre: deleteUser
     * Descripción: Elimina un usuario y sus relaciones de rol.
     * @param string $userId UUID del usuario a eliminar.
     * @return bool true si se elimina correctamente, false en caso contrario.
     * Método de uso: $this->UserModel->deleteUser($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function deleteUser(string $userId): bool
    {
        $this->db->trans_start();
        $this->db->where('user_id', $userId)->delete(self::PIVOT_TABLE);
        $this->db->where('user_id', $userId)->delete(self::RESET_TABLE);
        $this->db->where('id', $userId)->delete(self::TABLE);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Nombre: getUserRoles
     * Descripción: Devuelve los roles asignados a un usuario.
     * @param string $userId UUID del usuario.
     * @return array<int,string> Lista de identificadores de roles asociados.
     * Método de uso: $roles = $this->UserModel->getUserRoles($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function getUserRoles(string $userId): array
    {
        $this->db->select('r.id');
        $this->db->from(self::PIVOT_TABLE . ' ur');
        $this->db->join(self::ROLES_TABLE . ' r', 'r.id = ur.role_id', 'inner');
        $this->db->where('ur.user_id', $userId);
        $roles = $this->db->get()->result();

        return array_map(static fn ($role) => $role->id, $roles);
    }

    /**
     * Nombre: createPasswordResetToken
     * Descripción: Genera un token de restablecimiento de contraseña y lo persiste con tiempo de expiración.
     * @param string $userId UUID del usuario.
     * @param string $token Token seguro generado para el proceso.
     * @param int $ttl Segundos de validez del token.
     * @return bool true si se almacena correctamente.
     * Método de uso: $this->UserModel->createPasswordResetToken($userId, $token, 3600);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function createPasswordResetToken(string $userId, string $token, int $ttl): bool
    {
        $this->db->insert(self::RESET_TABLE, [
            'id' => uuid_v4(),
            'user_id' => $userId,
            'token' => password_hash($token, PASSWORD_DEFAULT),
            'raw_token' => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + $ttl),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() === 1;
    }

    /**
     * Nombre: getResetByToken
     * Descripción: Busca un token de restablecimiento válido.
     * @param string $token Token sin hash recibido del usuario.
     * @return object|null Registro del token si existe y está vigente.
     * Método de uso: $reset = $this->UserModel->getResetByToken($token);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function getResetByToken(string $token): ?object
    {
        $this->db->where('expires_at >=', date('Y-m-d H:i:s'));
        $resets = $this->db->get(self::RESET_TABLE)->result();
        foreach ($resets as $reset) {
            if (password_verify($token, $reset->token)) {
                return $reset;
            }
        }
        return null;
    }

    /**
     * Nombre: purgeResetTokens
     * Descripción: Elimina tokens de restablecimiento utilizados o caducados de un usuario.
     * @param string $userId UUID del usuario.
     * @return void
     * Método de uso: $this->UserModel->purgeResetTokens($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function purgeResetTokens(string $userId): void
    {
        $this->db->where('user_id', $userId)->delete(self::RESET_TABLE);
    }

    /**
     * Nombre: logLoginAttempt
     * Descripción: Registra los intentos de acceso para auditoría y bloqueo.
     * @param string|null $userId UUID del usuario (null si no se identificó).
     * @param string $ipAddress Dirección IP del intento.
     * @param string $userAgent User-Agent reportado por el cliente.
     * @param bool $success Indica si fue exitoso.
     * @return void
     * Método de uso: $this->UserModel->logLoginAttempt($userId, $ip, $ua, true);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function logLoginAttempt(?string $userId, string $ipAddress, string $userAgent, bool $success): void
    {
        $this->db->insert(self::ATTEMPT_TABLE, [
            'id' => uuid_v4(),
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'is_successful' => $success ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Nombre: countFailedAttempts
     * Descripción: Devuelve el número de intentos fallidos recientes para un usuario.
     * @param string $userId UUID del usuario.
     * @param int $minutes Ventana temporal en minutos a evaluar.
     * @return int Número de intentos fallidos.
     * Método de uso: $count = $this->UserModel->countFailedAttempts($id, 15);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function countFailedAttempts(string $userId, int $minutes = 15): int
    {
        $threshold = date('Y-m-d H:i:s', time() - ($minutes * 60));
        $this->db->where('user_id', $userId);
        $this->db->where('is_successful', 0);
        $this->db->where('created_at >=', $threshold);
        return (int)$this->db->count_all_results(self::ATTEMPT_TABLE);
    }

    /**
     * Nombre: resetFailedAttempts
     * Descripción: Reinicia el contador de intentos fallidos del usuario.
     * @param string $userId UUID del usuario.
     * @return void
     * Método de uso: $this->UserModel->resetFailedAttempts($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function resetFailedAttempts(string $userId): void
    {
        $this->updateUserAttributes($userId, ['failed_attempts' => 0, 'locked_until' => null]);
    }

    /**
     * Nombre: incrementFailedAttempts
     * Descripción: Aumenta el contador de fallos y establece bloqueo si supera umbral.
     * @param string $userId UUID del usuario.
     * @param int $maxAttempts Número máximo de intentos permitidos.
     * @param int $lockMinutes Minutos de bloqueo cuando se supera el límite.
     * @return void
     * Método de uso: $this->UserModel->incrementFailedAttempts($id, 5, 15);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function incrementFailedAttempts(string $userId, int $maxAttempts, int $lockMinutes): void
    {
        $user = $this->findById($userId);
        $attempts = ($user->failed_attempts ?? 0) + 1;
        $payload = ['failed_attempts' => $attempts];
        if ($attempts >= $maxAttempts) {
            $payload['locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
        }
        $this->updateUserAttributes($userId, $payload);
    }

    /**
     * Nombre: getRoles
     * Descripción: Obtiene todos los roles disponibles en el sistema.
     * @return array<int,object> Colección de roles.
     * Método de uso: $roles = $this->UserModel->getRoles();
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function getRoles(): array
    {
        return $this->db->order_by('name', 'ASC')->get(self::ROLES_TABLE)->result();
    }
}
