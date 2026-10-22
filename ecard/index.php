<?php
// Serve the ecard landing page.
header('Cache-Control: private, no-store, max-age=0');
header('Location: view.php', true, 302);
exit;
