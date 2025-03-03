<?php
include_once UTILS_PATH . 'Utility.php';
function checkAuth()
{
    session_start();
    if (!isset($_SESSION['user'])) {
        // Utils::log("INFO - Redirecting user that hasn't logged in to login page.");
        header('Location: login');
        exit();
    }
}
function responseAuth()
{
    session_start();
    if (!isset($_SESSION['user'])) {
        header("Content-type: application/json");
        echo json_encode([
            "message" => "Please login first"
        ]);
        exit();
    }
}

function getCurrentUser()
{
    return $_SESSION['user'];
}
