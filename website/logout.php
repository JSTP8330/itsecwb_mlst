<!-- --> <?php
define('ITSEC_SESSION_PUBLIC', true);
require_once __DIR__ . '/session.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to homepage
header("Location: index.php");
exit;
?>
