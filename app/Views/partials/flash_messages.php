<?php $flashSuccess = session()->getFlashdata('success'); ?>
<?php $flashError = session()->getFlashdata('error'); ?>

<?php if (is_array($flashError)): ?>
    <div class="alert-flash alert-danger">
        <ul>
            <?php foreach ($flashError as $message): ?>
                <li><?= esc($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php elseif (is_string($flashError) && $flashError !== ''): ?>
    <div class="alert-flash alert-danger"><?= esc($flashError) ?></div>
<?php endif; ?>

<?php if (is_string($flashSuccess) && $flashSuccess !== ''): ?>
    <div class="alert-flash alert-success"><?= esc($flashSuccess) ?></div>
<?php endif; ?>
