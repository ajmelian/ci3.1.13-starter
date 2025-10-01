<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$config['protocol'] = 'smtp';
$config['smtp_host'] = $_ENV['MAIL_HOST'] ?? 'localhost';
$config['smtp_port'] = (int)($_ENV['MAIL_PORT'] ?? 25);
$config['smtp_user'] = $_ENV['MAIL_USER'] ?? '';
$config['smtp_pass'] = $_ENV['MAIL_PASS'] ?? '';
$config['smtp_crypto'] = $_ENV['MAIL_ENCRYPTION'] ?? '';
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['crlf'] = "\r\n";
$config['wordwrap'] = true;
