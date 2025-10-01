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
            <h1 class="card-title text-center brand-title"><?= html_escape(lang('auth_reset_title')); ?></h1>
            <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_password_requirements')); ?></p>

            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>

            <form method="post" action="<?= site_url('auth/reset/' . html_escape($token ?? '')); ?>" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label for="password" class="form-label"><?= html_escape(lang('auth_password_label')); ?></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8"
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w]).{8,}$">
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label"><?= html_escape(lang('auth_confirm_password_label')); ?></label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_reset_password_button')); ?>
                </button>
            </form>
        </div>
    </div>
</section>
