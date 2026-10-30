<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra đã đăng nhập
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}


/**
 * Bắt buộc đăng nhập
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }
}


/**
 * Kiểm tra role
 */
function hasRole($role)
{
    return isset($_SESSION['role'])
        && $_SESSION['role'] === $role;
}


/**
 * Bắt buộc role
 */
function requireRole($role)
{
    requireLogin();

    if (!hasRole($role)) {
        http_response_code(403);

        die("
            <h1>403 - Không có quyền truy cập</h1>
            <p>Bạn không có quyền sử dụng chức năng này.</p>
        ");
    }
}


/**
 * Kiểm tra quyền
 */
function hasPermission($permission)
{
    if (!isLoggedIn()) {
        return false;
    }

    // Super Admin có toàn quyền
    if (hasRole('super_admin')) {
        return true;
    }

    require_once __DIR__ . "/../config/database.php";

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM role_permissions rp

        INNER JOIN permissions p
            ON rp.permission_id = p.id

        INNER JOIN users u
            ON rp.role_id = u.role_id

        WHERE u.id = ?
        AND p.name = ?
    ");

    $stmt->execute([
        $_SESSION['user_id'],
        $permission
    ]);

    return (int)$stmt->fetchColumn() > 0;
}


/**
 * Bắt buộc có quyền
 */
function requirePermission($permission)
{
    requireLogin();

    if (!hasPermission($permission)) {

        http_response_code(403);

        die("
            <div style='
                font-family: Arial;
                text-align: center;
                padding: 80px;
            '>

                <h1>403</h1>

                <h2>Bạn không có quyền</h2>

                <p>
                    Tài khoản của bạn chưa được cấp quyền
                    sử dụng chức năng này.
                </p>

                <a href='index.php'>
                    ← Quay lại
                </a>

            </div>
        ");
    }
}