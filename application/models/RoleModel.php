<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: RoleModel
 * Descripción: Gestiona los roles de usuario disponibles en la plataforma.
 * Método de uso: $this->load->model('RoleModel');
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class RoleModel extends CI_Model
{
    private const TABLE = 'roles';

    /**
     * Nombre: create
     * Descripción: Inserta un rol con UUID generado automáticamente.
     * @param array{name:string,description:?string} $data Datos principales del rol.
     * @return bool true si se inserta correctamente.
     * Método de uso: $this->rolemodel->create(['name' => 'admin']);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function create(array $data): bool
    {
        $payload = [
            'id' => uuid_v4(),
            'name' => strtolower($data['name']),
            'display_name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert(self::TABLE, $payload);
        return $this->db->affected_rows() === 1;
    }

    /**
     * Nombre: update
     * Descripción: Modifica un rol existente por su UUID.
     * @param string $roleId UUID del rol.
     * @param array<string,mixed> $data Datos a actualizar.
     * @return bool true si se actualiza correctamente.
     * Método de uso: $this->rolemodel->update($id, ['name' => 'editor']);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function update(string $roleId, array $data): bool
    {
        if (isset($data['name'])) {
            $data['display_name'] = $data['name'];
            $data['name'] = strtolower($data['name']);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $roleId)->update(self::TABLE, $data);
        return $this->db->affected_rows() >= 0;
    }

    /**
     * Nombre: delete
     * Descripción: Elimina un rol y sus asociaciones con usuarios.
     * @param string $roleId UUID del rol.
     * @return bool true si se elimina correctamente.
     * Método de uso: $this->rolemodel->delete($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function delete(string $roleId): bool
    {
        $this->db->trans_start();
        $this->db->where('role_id', $roleId)->delete('user_roles');
        $this->db->where('id', $roleId)->delete(self::TABLE);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Nombre: all
     * Descripción: Recupera todos los roles registrados.
     * @return array<int,object> Lista de roles.
     * Método de uso: $roles = $this->rolemodel->all();
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    /**
     * Nombre: findByName
     * Descripción: Busca un rol por su nombre único.
     * @param string $name Nombre del rol (insensible a mayúsculas).
     * @return object|null Rol encontrado o null.
     * Método de uso: $role = $this->rolemodel->findByName('admin');
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function findByName(string $name): ?object
    {
        return $this->db->get_where(self::TABLE, ['name' => strtolower($name)])->row();
    }

    public function all(): array
    {
        return $this->db->order_by('name', 'ASC')->get(self::TABLE)->result();
    }

    /**
     * Nombre: find
     * Descripción: Recupera un rol por su UUID.
     * @param string $roleId UUID del rol.
     * @return object|null Rol encontrado o null.
     * Método de uso: $role = $this->rolemodel->find($id);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function find(string $roleId): ?object
    {
        return $this->db->get_where(self::TABLE, ['id' => $roleId])->row();
    }
}
