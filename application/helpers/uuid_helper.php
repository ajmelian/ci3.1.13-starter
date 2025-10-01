<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('uuid_v4')) {
    /**
     * Nombre: uuid_v4
     * Descripción: Genera un UUID v4 basado en random_bytes.
     * @return string UUID v4 en formato estándar.
     * Método de uso: $id = uuid_v4();
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    function uuid_v4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
