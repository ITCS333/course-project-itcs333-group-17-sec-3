<?php
session_start();

function requireRole($role) {

    if (!isset($_SESSION["logged_in"]) || !$_SESSION["logged_in"]) {
        echo json_encode([
            "success" => false,
            "message" => "Not logged in"
        ]);
        exit;
    }

    if ($_SESSION["role"] !== $role) {
        echo json_encode([
            "success" => false,
            "message" => "Access denied: insufficient permissions"
        ]);
        exit;
    }
}
