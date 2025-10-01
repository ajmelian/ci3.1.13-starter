<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$error = $CI->session->flashdata('error');
$success = $CI->session->flashdata('success');
$warning = $CI->session->flashdata('warning');
?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?= html_escape($error); ?>
    </div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert">
        <?= html_escape($success); ?>
    </div>
<?php endif; ?>
<?php if (!empty($warning)): ?>
    <div class="alert alert-warning" role="alert">
        <?= html_escape($warning); ?>
    </div>
<?php endif; ?>
