<?php
require_once 'config/config.php';
session_destroy();
header('Location: ' . APP_URL . '/index.php');
exit;
