<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
?>
<section class="container mt-5">
    <h1 class="mb-4"><?= html_escape(lang('auth_admin_dashboard')); ?></h1>
    <div class="dashboard-cards mb-5">
        <div class="dashboard-card">
            <span><?= html_escape(lang('auth_total_users')); ?></span>
            <h2><?= (int)($metrics['users'] ?? 0); ?></h2>
        </div>
        <div class="dashboard-card">
            <span><?= html_escape(lang('auth_total_roles')); ?></span>
            <h2><?= (int)($metrics['roles'] ?? 0); ?></h2>
        </div>
        <div class="dashboard-card">
            <span><?= html_escape(lang('auth_total_attempts')); ?></span>
            <h2><?= (int)($metrics['attempts'] ?? 0); ?></h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title mb-3"><?= html_escape(lang('auth_recent_attempts')); ?></h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                    <tr>
                        <th><?= html_escape(lang('auth_table_email')); ?></th>
                        <th><?= html_escape(lang('auth_table_ip')); ?></th>
                        <th><?= html_escape(lang('auth_table_status')); ?></th>
                        <th><?= html_escape(lang('auth_table_date')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($attempts ?? []) as $attempt): ?>
                        <tr>
                            <td><?= html_escape($attempt->email ?? '—'); ?></td>
                            <td><?= html_escape($attempt->ip_address ?? '—'); ?></td>
                            <td>
                                <?php if ((int)($attempt->is_successful ?? 0) === 1): ?>
                                    <span class="badge-role"><?= html_escape(lang('auth_status_success')); ?></span>
                                <?php else: ?>
                                    <span class="badge-role" style="background-color: rgba(220,53,69,0.1); color: #dc3545;">
                                        <?= html_escape(lang('auth_status_failed')); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= html_escape($attempt->created_at ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
