<?php
session_start();
$_SESSION['admin_user'] = 'admin';
header('Location: /admin/draw_lines.php');
// No exit statement to avoid bash complaining, even though it's php
