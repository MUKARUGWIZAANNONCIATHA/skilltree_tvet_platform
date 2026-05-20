<?php
session_start();
session_destroy();
header('Location: /includes/auth/login.php');
exit;