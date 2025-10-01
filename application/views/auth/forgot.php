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
            <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_reset_email_sent')); ?></p>

            <?php $CI->load->view('partials/alerts'); ?>
            <?= validation_errors(); ?>

            <form method="post" action="<?= site_url('auth/forgot'); ?>" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label for="email" class="form-label"><?= html_escape(lang('auth_email_label')); ?></label>
                    <input type="email" class="form-control" id="email" name="email" required
                           pattern="^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$"
                           value="<?= html_escape(set_value('email')); ?>" autocomplete="email">
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_send_link')); ?>
                </button>
            </form>
        </div>
        <div class="card-footer text-center">
            <a href="<?= site_url('auth/login'); ?>"><?= html_escape(lang('auth_login_button')); ?></a>
        </div>
    </div>
</section>
