<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
?>
<section class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= html_escape(lang('auth_admin_users')); ?></h1>
        <a class="btn btn-primary" href="<?= site_url('admin/users/create'); ?>"><?= html_escape(lang('auth_new_user_button')); ?></a>
    </div>

    <?php $CI->load->view('partials/alerts'); ?>

    <form method="get" action="<?= site_url('admin/users'); ?>" class="form-inline mb-4" novalidate>
        <input type="text" class="form-control" name="search" placeholder="<?= html_escape(lang('auth_search_placeholder')); ?>" value="<?= html_escape($search ?? ''); ?>">
        <button type="submit" class="btn btn-outline-primary"><?= html_escape(lang('general_search')); ?></button>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><?= html_escape(lang('auth_fullname_label')); ?></th>
                        <th><?= html_escape(lang('auth_email_label')); ?></th>
                        <th><?= html_escape(lang('auth_actions')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($users ?? []) as $user): ?>
                        <tr>
                            <td>
                                <strong><?= html_escape($user->full_name); ?></strong>
                                <div class="small text-muted">
                                    <?= (int)$user->is_active === 1 ? html_escape(lang('auth_status_active')) : html_escape(lang('auth_status_inactive')); ?>
                                </div>
                                <div class="small">
                                    <?php foreach (($user->roles ?? []) as $role): ?>
                                        <span class="badge-role"><?= html_escape($role); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td><?= html_escape($user->email); ?></td>
                            <td class="table-actions">
                                <a class="btn btn-secondary" href="<?= site_url('admin/users/edit/' . $user->id); ?>"><?= html_escape(lang('auth_edit')); ?></a>
                                <form method="post" action="<?= site_url('admin/users/delete/' . $user->id); ?>" data-confirm="<?= html_escape(lang('auth_confirm_delete')); ?>">
                                    <input type="hidden" name="<?= html_escape($csrfName); ?>" value="<?= html_escape($csrfHash); ?>">
                                    <button type="submit" class="btn btn-danger"><?= html_escape(lang('auth_delete')); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
