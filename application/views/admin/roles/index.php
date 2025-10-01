<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$csrfName = $CI->security->get_csrf_token_name();
$csrfHash = $CI->security->get_csrf_hash();
?>
<section class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= html_escape(lang('auth_admin_roles')); ?></h1>
        <a class="btn btn-primary" href="<?= site_url('admin/roles/create'); ?>"><?= html_escape(lang('auth_new_role_button')); ?></a>
    </div>

    <?php $CI->load->view('partials/alerts'); ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><?= html_escape(lang('auth_role_name')); ?></th>
                        <th><?= html_escape(lang('auth_role_description')); ?></th>
                        <th><?= html_escape(lang('auth_actions')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($roles ?? []) as $role): ?>
                        <tr>
                            <td><?= html_escape($role->display_name ?? $role->name); ?></td>
                            <td><?= html_escape($role->description ?? ''); ?></td>
                            <td class="table-actions">
                                <a class="btn btn-secondary" href="<?= site_url('admin/roles/edit/' . $role->id); ?>"><?= html_escape(lang('auth_edit')); ?></a>
                                <form method="post" action="<?= site_url('admin/roles/delete/' . $role->id); ?>" data-confirm="<?= html_escape(lang('auth_confirm_delete')); ?>">
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
