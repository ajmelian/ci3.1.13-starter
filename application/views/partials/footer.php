<?php
declare(strict_types=1);

defined('BASEPATH') or exit('No direct script access allowed');
?>
    <?php foreach (($js ?? []) as $src): ?>
        <script src="<?= html_escape($src); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
