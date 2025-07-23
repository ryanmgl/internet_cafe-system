<?php
session_start();
session_unset();      // clear session var
session_destroy();   
header("Location: admin_login.php");
exit();
?>
