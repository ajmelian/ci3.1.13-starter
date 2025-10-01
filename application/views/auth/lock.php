<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
$userFullName = $CI->session->userdata('user_full_name');
?>
<section class="lock-screen">
    <div class="lock-card">
        <h2 class="text-center mb-3"><?= html_escape($subtitle ?? lang('auth_lock_subtitle')); ?></h2>
        <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_unlock_info')); ?></p>
        <?php $CI->load->view('partials/alerts'); ?>
        <?= validation_errors(); ?>
        <form method="post" action="<?= site_url('auth/unlock'); ?>" data-session-lock>
            <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
            <div class="mb-3">
                <label class="form-label"><?= html_escape($userFullName ?? ''); ?></label>
                <input type="password" class="form-control" name="password" required minlength="8" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100" data-loading-text="<?= html_escape(lang('general_unlock')); ?>">
                <?= html_escape(lang('auth_unlock_button')); ?>
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="<?= site_url('auth/logout'); ?>" class="text-muted small">Salir de la cuenta</a>
        </div>
    </div>
</section>
