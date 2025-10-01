<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$language = $language ?? 'spanish';
$langAttr = $language === 'english' ? 'en' : 'es';
$title = isset($title) ? html_escape($title) : html_escape($appName ?? 'Aplicación');
?>
<!doctype html>
<html lang="<?= $langAttr; ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title; ?></title>
    <?php foreach (($css ?? []) as $href): ?>
        <link rel="stylesheet" href="<?= html_escape($href); ?>">
    <?php endforeach; ?>
</head>
<body>
