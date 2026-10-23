<?php
/**
 * Shared footer + scripts.  A page can set $extraScripts = ['js/events.js'] before including.
 */
$extraScripts = $extraScripts ?? [];
?>

<footer>
    <div class="container">
        <p>&copy; 2026 Student Portal. All Rights Reserved.</p>
    </div>
</footer>

<script src="<?= e(url('js/script.js')) ?>"></script>
<?php foreach ($extraScripts as $__src): ?>
<script src="<?= e(url($__src)) ?>"></script>
<?php endforeach; ?>

</body>
</html>
