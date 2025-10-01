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
            <h1 class="card-title text-center brand-title"><?= html_escape(lang('auth_two_factor_required')); ?></h1>
            <p class="text-center text-muted mb-4"><?= html_escape(lang('auth_otp_qr_help')); ?></p>
            <?php $CI->load->view('partials/alerts'); ?>
            <form method="post" action="<?= site_url('auth/otp/verify'); ?>" novalidate>
                <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                <div class="mb-3">
                    <label for="otp_code" class="form-label"><?= html_escape(lang('auth_two_factor_label')); ?></label>
                    <input type="text" id="otp_code" name="otp_code" class="form-control" required pattern="^[0-9]{6}$" autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-primary w-100" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                    <?= html_escape(lang('auth_two_factor_button')); ?>
                </button>
            </form>
        </div>
    </div>
</section>
