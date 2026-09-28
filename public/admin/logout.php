<?php

require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit;
}

$submittedToken = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : '';
if (!hash_equals(adminCsrfToken(), $submittedToken)) {
    http_response_code(400);
    exit;
}

adminDestroySession();
header('Location: /admin/');
exit;