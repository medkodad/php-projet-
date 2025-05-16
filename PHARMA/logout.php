<?php
// Had l'file kaykhdem bach nlogoutiw l'utilisateur

// Kanbdaw b session_start() bach n9dro n3mlo logout
session_start();

// Kanms7iw ga3 l'data li f session
session_unset();

// Kan7rqiw l'session
session_destroy();

// Kanredirigiw l'utilisateur l page dyal login
header('Location: admin_login.php');
exit();
?>