<?php
// Compatibility route: Practice Admin now uses index.php as the canonical dashboard.
header('Location: index.php', true, 302);
exit;
