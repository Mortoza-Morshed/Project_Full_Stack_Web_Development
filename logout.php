<?php
session_start();
require_once 'includes/functions.php';

// clear session
session_unset();
session_destroy();

// set message
session_start();
set_message("You have been logged out successfully");


redirect("index.php");
?> 