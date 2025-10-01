-- Nombre: 20251001000001_seed_core_data
-- Descripción: Inserta roles base y un usuario administrador inicial.
-- Autor: Aythami Melián Perdomo
-- Fecha: 2025-10-01

START TRANSACTION;

INSERT INTO roles (id, name, display_name, description, created_at, updated_at) VALUES
('d785974a-c258-402d-979c-2a0effd1deb8', 'admin', 'Administrador', 'Acceso total al sistema', NOW(), NOW()),
('72467d3b-f2b4-4c52-b7a5-fcd385ee5a4e', 'manager', 'Gestor', 'Puede administrar usuarios y reportes', NOW(), NOW()),
('0a84a38d-f175-49d8-9a7a-a7a038764aa9', 'user', 'Usuario', 'Acceso estándar a funcionalidades', NOW(), NOW())
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description), updated_at = NOW();

INSERT INTO users (id, full_name, email, password_hash, is_active, is_locked, failed_attempts, locked_until, two_factor_secret, two_factor_enabled, last_login_at, created_at, updated_at)
VALUES ('6eedd655-1ef2-45b4-85dc-df4b3fba206a', 'Administrador General', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$a0pqZ1piYTgxZ250QVFmcw$fWMeRnjoaU02o4JDCnv+zazXGJNLTSSkIOdQ9VX5wSc', 1, 0, 0, NULL, NULL, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), updated_at = NOW();

INSERT INTO user_roles (id, user_id, role_id, created_at) VALUES
('7f29c6d7-ba8f-4992-9c37-9ee09053c6df', '6eedd655-1ef2-45b4-85dc-df4b3fba206a', 'd785974a-c258-402d-979c-2a0effd1deb8', NOW()),
('c33380aa-7206-443b-96d5-f28fa2c3208d', '6eedd655-1ef2-45b4-85dc-df4b3fba206a', '0a84a38d-f175-49d8-9a7a-a7a038764aa9', NOW())
ON DUPLICATE KEY UPDATE created_at = VALUES(created_at);

COMMIT;
