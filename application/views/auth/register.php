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
            <h1 class="card-title text-center brand-title"><?= html_escape(lang('auth_register_title')); ?></h1>
            <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_password_requirements')); ?></p>

            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>

            <form method="post" action="<?= site_url('auth/register'); ?>" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label for="full_name" class="form-label"><?= html_escape(lang('auth_fullname_label')); ?></label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required minlength="3" maxlength="120"
                           pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s\'\-]+$"
                           value="<?= html_escape(set_value('full_name')); ?>" autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label"><?= html_escape(lang('auth_email_label')); ?></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           pattern="^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$"
                           value="<?= html_escape(set_value('email')); ?>" autocomplete="email">
                </div>
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <label for="password" class="form-label"><?= html_escape(lang('auth_password_label')); ?></label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="8"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w]).{8,}$"
                               autocomplete="new-password">
                    </div>
                    <div class="col-lg-6 mb-3">
                        <label for="password_confirmation" class="form-label"><?= html_escape(lang('auth_confirm_password_label')); ?></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8"
                               autocomplete="new-password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_register_button')); ?>
                </button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="<?= site_url('auth/login'); ?>"><?= html_escape(lang('auth_has_account')); ?></a>
        </div>
    </div>
</section>
