<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Nombre: OtpService
 * Descripción: Servicio para generar y validar códigos TOTP compatibles con Google Authenticator.
 * Método de uso: $this->load->library('OtpService');
 * @since 2025-10-01 Aythami Melián Perdomo
 * @updated 2025-10-01 Aythami Melián Perdomo
 */
class OtpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Nombre: generateSecret
     * Descripción: Genera un secreto base32 para uso en TOTP.
     * @param int $length Longitud del secreto requerido (múltiplo de 8 recomendado).
     * @return string Secreto codificado en base32.
     * Método de uso: $secret = $this->otpservice->generateSecret();
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function generateSecret(int $length = 32): string
    {
        $randomBytes = random_bytes($length);
        return $this->base32Encode($randomBytes);
    }

    /**
     * Nombre: getProvisioningUri
     * Descripción: Construye la URI otpauth para registro en Google Authenticator.
     * @param string $secret Secreto base32 generado.
     * @param string $email Correo electrónico del usuario.
     * @param string $issuer Nombre de la aplicación emisora.
     * @return string URI otpauth compatible con Google Authenticator.
     * Método de uso: $uri = $this->otpservice->getProvisioningUri($secret, $email, 'MiApp');
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function getProvisioningUri(string $secret, string $email, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerEncoded = rawurlencode($issuer);
        return sprintf('otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30', $label, $secret, $issuerEncoded);
    }

    /**
     * Nombre: verifyCode
     * Descripción: Valida un código TOTP de seis dígitos con ventana de tolerancia configurable.
     * @param string $secret Secreto base32 almacenado para el usuario.
     * @param string $code Código proporcionado por el usuario.
     * @param int $window Ventana en intervalos de 30s que se consideran válidos.
     * @return bool true si el código es válido.
     * Método de uso: $isValid = $this->otpservice->verifyCode($secret, $code);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (!preg_match('/^[0-9]{6}$/', $code)) {
            return false;
        }

        $secretBytes = $this->base32Decode($secret);
        $timeSlice = (int)floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $calculated = $this->calculateOtp($secretBytes, $timeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nombre: base32Encode
     * Descripción: Convierte bytes en representación base32.
     * @param string $data Cadena binaria a codificar.
     * @return string Cadena en base32.
     * Método de uso: $encoded = $this->base32Encode($bytes);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function base32Encode(string $data): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $chunks = str_split($binaryString, 5);
        $base32 = '';
        foreach ($chunks as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $index = bindec($chunk);
            $base32 .= $alphabet[$index];
        }

        return $base32;
    }

    /**
     * Nombre: base32Decode
     * Descripción: Convierte un secreto base32 a bytes binarios.
     * @param string $encoded Secreto codificado en base32.
     * @return string Representación binaria del secreto.
     * Método de uso: $bytes = $this->base32Decode($secret);
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function base32Decode(string $encoded): string
    {
        $alphabet = self::BASE32_ALPHABET;
        $encoded = strtoupper($encoded);
        $binaryString = '';

        foreach (str_split($encoded) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        $chunks = str_split($binaryString, 8);
        foreach ($chunks as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }

    /**
     * Nombre: calculateOtp
     * Descripción: Calcula el código OTP para un instante específico.
     * @param string $secretBytes Secreto en formato binario.
     * @param int $timeSlice Intervalo de tiempo de 30 segundos.
     * @return string Código OTP de seis dígitos.
     * Método de uso: $otp = $this->calculateOtp($bytes, time());
     * @since 2025-10-01 Aythami Melián Perdomo
     * @updated 2025-10-01 Aythami Melián Perdomo
     */
    private function calculateOtp(string $secretBytes, int $timeSlice): string
    {
        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretBytes, true);
        $offset = ord($hash[19]) & 0xf;
        $binary = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);
        $otp = $binary % 1000000;
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }
}
