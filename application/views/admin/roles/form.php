<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
$role = $role ?? null;
?>
<section class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title mb-4"><?= html_escape($title ?? lang('auth_new_role_button')); ?></h1>
            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>
            <form method="post" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label class="form-label" for="name"><?= html_escape(lang('auth_role_name')); ?></label>
                    <input type="text" class="form-control" id="name" name="name" required minlength="3" maxlength="50"
                           value="<?= html_escape(set_value('name', $role->display_name ?? '')); ?>">
                    <small class="text-muted">Usa sólo letras, números, guiones o guiones bajos.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description"><?= html_escape(lang('auth_role_description')); ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="150"><?= html_escape(set_value('description', $role->description ?? '')); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_save_changes')); ?>
                </button>
                <a href="<?= site_url('admin/roles'); ?>" class="btn btn-secondary"><?= html_escape(lang('auth_cancel')); ?></a>
            </form>
        </div>
    </div>
</section>
