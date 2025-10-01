<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
$enabled = (bool)($twoFactorEnabled ?? false);
?>
<section class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="card-title"><?= html_escape(lang('auth_otp_title')); ?></h1>
                    <?php $CI->load->view('partials/alerts'); ?>
                    <p class="text-muted"><?= html_escape(lang('auth_otp_qr_help')); ?></p>

                    <?php if (!$enabled): ?>
                        <div class="qr-wrapper mb-3">
                            <code><?= html_escape($otpUri ?? ''); ?></code>
                        </div>
                        <p class="small text-muted mb-4">Escanea el texto anterior o pulsa <a href="<?= html_escape($otpUri ?? '#'); ?>">este enlace</a> desde tu dispositivo móvil.</p>

                        <form method="post" action="<?= site_url('auth/otp/enable'); ?>" class="mb-3" novalidate>
                            <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                            <input type="hidden" name="otp_secret" value="<?= html_escape($otpSecret ?? ''); ?>">
                            <div class="mb-3">
                                <label for="otp_code" class="form-label"><?= html_escape(lang('auth_otp_verify_label')); ?></label>
                                <input type="text" class="form-control" id="otp_code" name="otp_code" required pattern="^[0-9]{6}$" autocomplete="one-time-code">
                            </div>
                            <button type="submit" class="btn btn-primary" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                                <?= html_escape(lang('auth_otp_verify_button')); ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <?= html_escape(lang('auth_otp_enabled_success')); ?>
                        </div>
                        <form method="post" action="<?= site_url('auth/otp/disable'); ?>" novalidate>
                            <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label"><?= html_escape(lang('auth_password_label')); ?></label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            </div>
                            <button type="submit" class="btn btn-danger" data-loading-text="<?= html_escape(lang('general_continue')); ?>">
                                <?= html_escape(lang('auth_otp_disable')); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
