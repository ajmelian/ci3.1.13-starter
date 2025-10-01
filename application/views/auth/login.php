<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
?>
<section class="container main-wrapper">
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="card-title text-center brand-title"><?= html_escape($appName ?? lang('auth_login_title')); ?></h1>
            <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_login_subtitle')); ?></p>

            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>

            <form method="post" action="<?= site_url('auth/login'); ?>" novalidate data-session-lock>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label"><?= html_escape(lang('auth_email_label')); ?></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           pattern="^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$"
                           value="<?= html_escape(set_value('email')); ?>"
                           autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><?= html_escape(lang('auth_password_label')); ?></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8"
                           autocomplete="current-password">
                    <small class="text-muted"><?= html_escape(lang('auth_password_requirements')); ?></small>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember"><?= html_escape(lang('auth_remember_me')); ?></label>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                        <?= html_escape(lang('auth_login_button')); ?>
                    </button>
                    <a class="small" href="<?= site_url('auth/forgot'); ?>"><?= html_escape(lang('auth_forgot_password')); ?></a>
                </div>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="<?= site_url('auth/register'); ?>"><?= html_escape(lang('auth_register_prompt')); ?></a>
        </div>
    </div>
</section>
