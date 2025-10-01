<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
$user = $user ?? null;
$selectedRoles = $selectedRoles ?? [];
?>
<section class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title mb-4"><?= html_escape($title ?? lang('auth_new_user_button')); ?></h1>
            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>
            <form method="post" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="full_name"><?= html_escape(lang('auth_fullname_label')); ?></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required minlength="3" maxlength="120"
                               value="<?= html_escape(set_value('full_name', $user->full_name ?? '')); ?>">
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="email"><?= html_escape(lang('auth_email_label')); ?></label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?= html_escape(set_value('email', $user->email ?? '')); ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="password"><?= html_escape(lang('auth_password_label')); ?></label>
                        <input type="password" class="form-control" id="password" name="password" <?= $user ? '' : 'required'; ?> minlength="8">
                        <?php if ($user): ?>
                            <small class="text-muted">Deja el campo vacío para mantener la contraseña actual.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label class="form-label" for="password_confirmation"><?= html_escape(lang('auth_confirm_password_label')); ?></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" <?= $user ? '' : 'required'; ?> minlength="8">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="is_active"><?= html_escape(lang('auth_actions')); ?></label>
                    <select class="form-control" id="is_active" name="is_active" required>
                        <option value="1" <?= set_select('is_active', '1', isset($user->is_active) ? (int)$user->is_active === 1 : true); ?>><?= html_escape(lang('auth_status_active')); ?></option>
                        <option value="0" <?= set_select('is_active', '0', isset($user->is_active) ? (int)$user->is_active === 0 : false); ?>><?= html_escape(lang('auth_status_inactive')); ?></option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= html_escape(lang('auth_role_assign')); ?></label>
                    <div class="row">
                        <?php foreach (($roles ?? []) as $role): ?>
                            <div class="col-lg-4 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="<?= html_escape($role->id); ?>" id="role_<?= html_escape($role->id); ?>" name="roles[]"
                                        <?= in_array($role->id, set_value('roles', $selectedRoles), true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="role_<?= html_escape($role->id); ?>">
                                        <?= html_escape($role->display_name ?? $role->name); ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_save_changes')); ?>
                </button>
                <a href="<?= site_url('admin/users'); ?>" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</section>
