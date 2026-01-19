<?php
$config = require __DIR__ . '/../config/config.php';
$czasomatUrl = $config['czasomat']['url'];

$pageTitle = 'Czasomat - ITSS Project Management';
ob_start();
?>

<h2>Czasomat</h2>

<div class="card">
    <iframe
        src="<?php echo htmlspecialchars($czasomatUrl); ?>"
        style="width: 100%; height: 800px; border: none;"
        title="Czasomat ITSS"
    ></iframe>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
?>
