<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();


/* =========================================================
   CHỈ ADMIN / SUPER ADMIN QUẢN LÝ TÀI KHOẢN
========================================================= */

if (
    !hasRole("admin") &&
    !hasRole("super_admin")
) {
    http_response_code(403);
    die("Bạn không có quyền quản lý tài khoản.");
}


/* =========================================================
   BIẾN THÔNG BÁO
========================================================= */

$message = "";
$messageType = "";


/* =========================================================
   HÀM ROLE
========================================================= */

function roleLabel($roleId)
{
    switch ((int)$roleId) {
        case 1:
            return "Member";

        case 3:
            return "Admin";

        case 4:
            return "Super Admin";

        default:
            return "Không xác định";
    }
}


function roleClass($roleId)
{
    switch ((int)$roleId) {
        case 1:
            return "role-member";

        case 3:
            return "role-admin";

        case 4:
            return "role-super";

        default:
            return "";
    }
}


/* =========================================================
   XỬ LÝ FORM
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       LƯU THAY ĐỔI
    ====================================================== */

    if (isset($_POST["save_member"])) {

        $id = (int)($_POST["id"] ?? 0);

        $fullName = trim($_POST["full_name"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $birthday = trim($_POST["birthday"] ?? "");
        $gender = trim($_POST["gender"] ?? "");
        $unit = trim($_POST["unit"] ?? "");

        $roleId = (int)($_POST["role_id"] ?? 1);

        $status = $_POST["status"] ?? "active";


        /* -------------------------------------------------
           KIỂM TRA DỮ LIỆU
        -------------------------------------------------- */

        if ($id <= 0) {

            $message = "Tài khoản không hợp lệ.";
            $messageType = "error";

        } elseif ($fullName === "") {

            $message = "Vui lòng nhập họ tên.";
            $messageType = "error";

        } elseif ($phone === "") {

            $message = "Vui lòng nhập số điện thoại.";
            $messageType = "error";

        } elseif (
            !in_array(
                $roleId,
                [1, 3, 4],
                true
            )
        ) {

            $message = "Loại tài khoản không hợp lệ.";
            $messageType = "error";

        } elseif (
            !in_array(
                $status,
                ["active", "locked"],
                true
            )
        ) {

            $message = "Trạng thái tài khoản không hợp lệ.";
            $messageType = "error";

        } elseif (
            $roleId === 4 &&
            !hasRole("super_admin")
        ) {

            /*
             * Admin thường không được tự tạo Super Admin.
             */

            $message = "Chỉ Super Admin mới được cấp quyền Super Admin.";
            $messageType = "error";

        } elseif (
            isset($_SESSION["user_id"]) &&
            (int)$_SESSION["user_id"] === $id &&
            $status === "locked"
        ) {

            /*
             * Không tự khóa chính mình.
             */

            $message = "Bạn không thể khóa tài khoản đang đăng nhập.";
            $messageType = "error";

        } else {

            try {


                /* =========================================
                   KIỂM TRA SĐT TRÙNG
                ========================================== */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM users
                    WHERE phone = ?
                    AND id <> ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $phone,
                    $id
                ]);


                if ($stmt->fetch()) {

                    $message = "Số điện thoại này đã được sử dụng.";
                    $messageType = "error";

                } else {


                    /* =====================================
                       KIỂM TRA EMAIL TRÙNG
                    ====================================== */

                    $emailExists = false;


                    if ($email !== "") {

                        $stmt = $pdo->prepare("
                            SELECT id
                            FROM users
                            WHERE email = ?
                            AND id <> ?
                            LIMIT 1
                        ");

                        $stmt->execute([
                            $email,
                            $id
                        ]);

                        $emailExists = $stmt->fetch();
                    }


                    if ($emailExists) {

                        $message = "Email này đã được sử dụng.";
                        $messageType = "error";

                    } else {


                        /* =================================
                           UPDATE
                        ================================== */

                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET
                                full_name = ?,
                                phone = ?,
                                email = ?,
                                birthday = ?,
                                gender = ?,
                                unit = ?,
                                role_id = ?,
                                status = ?
                            WHERE id = ?
                        ");


                        $stmt->execute([
                            $fullName,
                            $phone,
                            $email !== "" ? $email : null,
                            $birthday !== "" ? $birthday : null,
                            $gender !== "" ? $gender : null,
                            $unit !== "" ? $unit : null,
                            $roleId,
                            $status,
                            $id
                        ]);


                        /* =================================
                           NẾU SỬA TÀI KHOẢN ĐANG LOGIN
                        ================================== */

                        if (
                            isset($_SESSION["user_id"]) &&
                            (int)$_SESSION["user_id"] === $id
                        ) {

                            $_SESSION["full_name"] = $fullName;

                            $_SESSION["role"] = strtolower(
                                str_replace(
                                    " ",
                                    "_",
                                    roleLabel($roleId)
                                )
                            );
                        }


                        header(
                            "Location: members.php?success=1"
                        );

                        exit;
                    }
                }

            } catch (PDOException $e) {

                $message = "Không thể cập nhật tài khoản.";
                $messageType = "error";
            }
        }
    }


    /* =====================================================
       XÓA TÀI KHOẢN
    ====================================================== */

    if (isset($_POST["delete_member"])) {

        $deleteId =
            (int)($_POST["delete_id"] ?? 0);


        if ($deleteId <= 0) {

            $message = "Tài khoản không hợp lệ.";
            $messageType = "error";

        } elseif (
            isset($_SESSION["user_id"]) &&
            (int)$_SESSION["user_id"] === $deleteId
        ) {

            $message = "Bạn không thể xóa tài khoản đang đăng nhập.";
            $messageType = "error";

        } else {

            try {

                /* =========================================
                   KIỂM TRA ROLE TÀI KHOẢN MUỐN XÓA
                ========================================== */

                $stmt = $pdo->prepare("
                    SELECT role_id
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $deleteId
                ]);

                $deleteUser =
                    $stmt->fetch(PDO::FETCH_ASSOC);


                if (!$deleteUser) {

                    $message = "Không tìm thấy tài khoản.";
                    $messageType = "error";

                } elseif (
                    (int)$deleteUser["role_id"] === 4 &&
                    !hasRole("super_admin")
                ) {

                    $message =
                        "Admin không thể xóa tài khoản Super Admin.";

                    $messageType = "error";

                } else {

                    $stmt = $pdo->prepare("
                        DELETE FROM users
                        WHERE id = ?
                    ");

                    $stmt->execute([
                        $deleteId
                    ]);


                    header(
                        "Location: members.php?deleted=1"
                    );

                    exit;
                }

            } catch (PDOException $e) {

                /*
                 * Có thể tài khoản đã có registrations.
                 */

                $message =
                    "Không thể xóa tài khoản này vì đang có dữ liệu liên quan.";

                $messageType = "error";
            }
        }
    }
}


/* =========================================================
   THÔNG BÁO
========================================================= */

if (isset($_GET["success"])) {

    $message = "✓ Đã cập nhật tài khoản.";
    $messageType = "success";
}


if (isset($_GET["deleted"])) {

    $message = "✓ Đã xóa tài khoản.";
    $messageType = "success";
}


/* =========================================================
   LẤY TÀI KHOẢN CẦN SỬA
========================================================= */

$editMember = null;


if (isset($_GET["edit"])) {

    $editId = (int)$_GET["edit"];


    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                full_name,
                phone,
                email,
                birthday,
                gender,
                unit,
                role_id,
                status
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $editId
        ]);

        $editMember =
            $stmt->fetch(PDO::FETCH_ASSOC);
    }
}


/* =========================================================
   LẤY TẤT CẢ TÀI KHOẢN
========================================================= */

$stmt = $pdo->query("
    SELECT
        u.id,
        u.full_name,
        u.phone,
        u.email,
        u.birthday,
        u.gender,
        u.unit,
        u.role_id,
        u.status,
        u.created_at,
        r.name AS role_name
    FROM users u

    LEFT JOIN roles r
        ON u.role_id = r.id

    ORDER BY u.id DESC
");


$members =
    $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Quản lý tài khoản | Đoàn xã Tân An
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            margin: 0;
            background: #f5f7f5;
            font-family: Arial, Helvetica, sans-serif;
            color: #18241c;
        }


        /* =================================================
           LAYOUT
        ================================================= */

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }


        .admin-main {
            margin-left: 250px;

            width: calc(100% - 250px);

            padding: 30px 40px;
        }


        /* =================================================
           SIDEBAR
        ================================================= */

        .sidebar {
            width: 250px;

            background: #123b25;

            color: white;

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            padding: 25px 18px;

            overflow-y: auto;
        }


        .sidebar .brand {
            display: block;

            padding: 10px;

            margin-bottom: 25px;

            color: white;
        }


        .sidebar .brand-icon {
            display: block;

            width: auto;
            height: auto;

            background: none;

            color: white;

            font-size: 28px;
        }


        .sidebar .brand h2 {
            margin: 8px 0 0;

            color: white;

            font-size: 19px;
        }


        .sidebar .brand p {
            margin: 5px 0 0;

            color: white;

            opacity: 1;

            font-size: 11px;
        }


        .sidebar .menu-title {
            margin: 20px 10px 8px;

            color: white;

            opacity: 1;

            font-size: 11px;

            text-transform: uppercase;
        }


        .sidebar .menu a {
            display: block;

            padding: 12px 13px;

            margin-bottom: 5px;

            border-radius: 10px;

            text-decoration: none;

            color: #dce9df;

            font-size: 14px;
        }


        .sidebar .menu a:hover,
        .sidebar .menu a.active {
            background: rgba(255,255,255,.12);

            color: white;
        }


        /* =================================================
           TOP
        ================================================= */

        .admin-topbar {
            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 20px;

            margin-bottom: 28px;
        }


        .admin-topbar h1 {
            margin: 0;

            font-size: 28px;
        }


        .admin-topbar p {
            margin: 6px 0 0;

            color: #536157;

            font-size: 13px;
        }


        .admin-user {
            padding: 10px 15px;

            border: 1px solid #e3e9e4;

            border-radius: 12px;

            background: white;
        }


        .admin-user strong {
            display: block;

            font-size: 13px;
        }


        .admin-user span {
            display: block;

            margin-top: 3px;

            color: #536157;

            font-size: 11px;
        }


        /* =================================================
           MESSAGE
        ================================================= */

        .message {
            margin-bottom: 20px;

            padding: 13px 16px;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 600;
        }


        .message.success {
            background: #e7f5eb;

            color: #176b3a;
        }


        .message.error {
            background: #fdecec;

            color: #a83232;
        }


        /* =================================================
           EDIT FORM
        ================================================= */

        .edit-panel {
            margin-bottom: 24px;

            padding: 23px;

            background: white;

            border: 1px solid #e2e8e3;

            border-radius: 17px;
        }


        .edit-header {
            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 20px;
        }


        .edit-header h2 {
            margin: 0;

            font-size: 18px;
        }


        .edit-header p {
            margin: 5px 0 0;

            color: #536157;

            font-size: 12px;
        }


        .close-edit {
            padding: 8px 11px;

            border-radius: 8px;

            background: #eef1ef;

            color: #435047;

            text-decoration: none;

            font-size: 12px;

            font-weight: bold;
        }


        .edit-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 16px;
        }


        .form-group {
            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .form-group label {
            font-size: 12px;

            font-weight: 700;
        }


        .form-group input,
        .form-group select {
            width: 100%;

            padding: 11px 12px;

            border: 1px solid #dce3de;

            border-radius: 9px;

            background: white;

            outline: none;

            font-size: 13px;
        }


        .form-group input:focus,
        .form-group select:focus {
            border-color: #176b3a;

            box-shadow:
                0 0 0 3px
                rgba(23,107,58,.08);
        }


        .form-actions {
            grid-column: 1 / -1;

            display: flex;

            gap: 8px;

            margin-top: 5px;
        }


        .save-btn {
            border: 0;

            padding: 11px 16px;

            border-radius: 9px;

            background: #176b3a;

            color: white;

            font-weight: 700;

            cursor: pointer;
        }


        .cancel-btn {
            padding: 11px 16px;

            border-radius: 9px;

            background: #eef1ef;

            color: #435047;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        /* =================================================
           TABLE PANEL
        ================================================= */

        .members-panel {
            padding: 22px;

            background: white;

            border: 1px solid #e4e9e5;

            border-radius: 17px;
        }


        .members-header {
            margin-bottom: 20px;
        }


        .members-header h2 {
            margin: 0;

            font-size: 18px;
        }


        .members-header p {
            margin: 6px 0 0;

            color: #536157;

            font-size: 12px;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        .members-table {
            width: 100%;

            min-width: 1100px;

            border-collapse: collapse;
        }


        .members-table th {
            padding: 13px 12px;

            background: #f7f9f7;

            border-bottom: 1px solid #e4e9e5;

            text-align: left;

            color: #68746c;

            font-size: 11px;

            text-transform: uppercase;
        }


        .members-table td {
            padding: 14px 12px;

            border-bottom: 1px solid #edf0ed;

            color: #354139;

            font-size: 13px;
        }


        .members-table tr:hover {
            background: #fafcfb;
        }


        .member-name {
            color: #183522;

            font-weight: 700;
        }


        /* =================================================
           ROLE
        ================================================= */

        .role {
            display: inline-block;

            padding: 6px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;
        }


        .role-member {
            background: #e7f5eb;
            color: #176b3a;
        }


        .role-admin {
            background: #e8f0ff;
            color: #315b9a;
        }


        .role-super {
            background: #f1e8ff;
            color: #683bb0;
        }


        /* =================================================
           STATUS
        ================================================= */

        .status-badge {
            display: inline-block;

            padding: 6px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;
        }


        .status-active {
            background: #e7f5eb;

            color: #176b3a;
        }


        .status-locked {
            background: #fdecec;

            color: #a83232;
        }


        /* =================================================
           BUTTON
        ================================================= */

        .actions {
            display: flex;

            gap: 6px;
        }


        .edit-btn {
            padding: 7px 9px;

            border-radius: 7px;

            background: #e8f0ff;

            color: #315b9a;

            text-decoration: none;

            font-size: 11px;

            font-weight: 700;
        }


        .delete-btn {
            border: 0;

            padding: 7px 9px;

            border-radius: 7px;

            background: #fdecec;

            color: #a83232;

            font-size: 11px;

            font-weight: 700;

            cursor: pointer;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media(max-width: 900px) {

            .sidebar {
                width: 210px;
            }


            .admin-main {
                margin-left: 210px;

                width: calc(100% - 210px);

                padding: 25px;
            }

        }


        @media(max-width: 650px) {

            .sidebar {
                display: none;
            }


            .admin-main {
                margin-left: 0;

                width: 100%;

                padding: 18px;
            }


            .admin-topbar {
                align-items: flex-start;

                flex-direction: column;
            }


            .edit-grid {
                grid-template-columns: 1fr;
            }


            .form-actions {
                grid-column: auto;
            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <?php require_once "includes/admin-sidebar.php"; ?>


    <main class="admin-main">


        <!-- =================================================
             TOP
        ================================================== -->

        <div class="admin-topbar">


            <div>

                <h1>
                    Tài khoản
                </h1>

                <p>
                    Quản lý tài khoản người dùng trong hệ thống.
                </p>

            </div>


            <div class="admin-user">

                <strong>
                    <?= htmlspecialchars(
                        $_SESSION["full_name"]
                        ?? "Quản trị viên"
                    ) ?>
                </strong>

                <span>
                    <?= htmlspecialchars(
                        $_SESSION["role"]
                        ?? ""
                    ) ?>
                </span>

            </div>


        </div>


        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if ($message !== ""): ?>

            <div
                class="message <?= htmlspecialchars(
                    $messageType
                ) ?>"
            >

                <?= htmlspecialchars($message) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             FORM SỬA
        ================================================== -->

        <?php if ($editMember): ?>


            <section
                class="edit-panel"
                id="edit"
            >


                <div class="edit-header">


                    <div>

                        <h2>
                            ✏️ Sửa tài khoản
                        </h2>

                        <p>
                            <?= htmlspecialchars(
                                $editMember["full_name"]
                            ) ?>
                        </p>

                    </div>


                    <a
                        href="members.php"
                        class="close-edit"
                    >
                        ✕ Đóng
                    </a>


                </div>


                <form method="POST">


                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int)$editMember["id"] ?>"
                    >


                    <div class="edit-grid">


                        <!-- HỌ TÊN -->

                        <div class="form-group">

                            <label>
                                Họ và tên
                            </label>

                            <input
                                type="text"
                                name="full_name"
                                required
                                value="<?= htmlspecialchars(
                                    $editMember["full_name"] ?? ""
                                ) ?>"
                            >

                        </div>


                        <!-- PHONE -->

                        <div class="form-group">

                            <label>
                                Số điện thoại
                            </label>

                            <input
                                type="text"
                                name="phone"
                                required
                                value="<?= htmlspecialchars(
                                    $editMember["phone"] ?? ""
                                ) ?>"
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="form-group">

                            <label>
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $editMember["email"] ?? ""
                                ) ?>"
                            >

                        </div>


                        <!-- BIRTHDAY -->

                        <div class="form-group">

                            <label>
                                Ngày sinh
                            </label>

                            <input
                                type="date"
                                name="birthday"
                                value="<?= htmlspecialchars(
                                    $editMember["birthday"] ?? ""
                                ) ?>"
                            >

                        </div>


                        <!-- GENDER -->

                        <div class="form-group">

                            <label>
                                Giới tính
                            </label>

                            <select name="gender">

                                <option value="">
                                    Chưa cập nhật
                                </option>

                                <option
                                    value="male"
                                    <?= ($editMember["gender"] ?? "")
                                        === "male"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Nam
                                </option>

                                <option
                                    value="female"
                                    <?= ($editMember["gender"] ?? "")
                                        === "female"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Nữ
                                </option>

                            </select>

                        </div>


                        <!-- UNIT -->

                        <div class="form-group">

                            <label>
                                Đơn vị
                            </label>

                            <input
                                type="text"
                                name="unit"
                                value="<?= htmlspecialchars(
                                    $editMember["unit"] ?? ""
                                ) ?>"
                            >

                        </div>


                        <!-- ROLE -->

                        <div class="form-group">

                            <label>
                                Loại tài khoản
                            </label>


                            <select name="role_id">


                                <option
                                    value="1"
                                    <?= (int)$editMember["role_id"] === 1
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Member
                                </option>


                                <option
                                    value="3"
                                    <?= (int)$editMember["role_id"] === 3
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Admin
                                </option>


                                <?php if (
                                    hasRole("super_admin")
                                    ||
                                    (int)$editMember["role_id"] === 4
                                ): ?>

                                    <option
                                        value="4"
                                        <?= (int)$editMember["role_id"] === 4
                                            ? "selected"
                                            : ""
                                        ?>
                                    >
                                        Super Admin
                                    </option>

                                <?php endif; ?>


                            </select>

                        </div>


                        <!-- STATUS -->

                        <div class="form-group">

                            <label>
                                Trạng thái
                            </label>


                            <select name="status">

                                <option
                                    value="active"
                                    <?= ($editMember["status"] ?? "")
                                        === "active"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Đang hoạt động
                                </option>


                                <option
                                    value="locked"
                                    <?= ($editMember["status"] ?? "")
                                        === "locked"
                                        ? "selected"
                                        : ""
                                    ?>
                                >
                                    Đã khóa
                                </option>

                            </select>

                        </div>


                        <!-- BUTTON -->

                        <div class="form-actions">

                            <button
                                type="submit"
                                name="save_member"
                                class="save-btn"
                            >
                                💾 Lưu thay đổi
                            </button>


                            <a
                                href="members.php"
                                class="cancel-btn"
                            >
                                Hủy
                            </a>

                        </div>


                    </div>


                </form>


            </section>


        <?php endif; ?>


        <!-- =================================================
             LIST
        ================================================== -->

        <section class="members-panel">


            <div class="members-header">

                <h2>
                    Danh sách tài khoản
                </h2>

                <p>
                    Tổng cộng:
                    <strong>
                        <?= count($members) ?>
                    </strong>
                    tài khoản
                </p>

            </div>


            <div class="table-wrapper">


                <table class="members-table">


                    <thead>

                        <tr>

                            <th>ID</th>

                            <th>Họ tên</th>

                            <th>Số điện thoại</th>

                            <th>Email</th>

                            <th>Đơn vị</th>

                            <th>Loại tài khoản</th>

                            <th>Trạng thái</th>

                            <th>Thao tác</th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php foreach ($members as $member): ?>


                        <tr>


                            <td>
                                <?= (int)$member["id"] ?>
                            </td>


                            <td class="member-name">

                                <?= htmlspecialchars(
                                    $member["full_name"] ?? ""
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $member["phone"] ?? "-"
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    !empty($member["email"])
                                    ? $member["email"]
                                    : "-"
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    !empty($member["unit"])
                                    ? $member["unit"]
                                    : "-"
                                ) ?>

                            </td>


                            <!-- ROLE -->

                            <td>

                                <span
                                    class="
                                        role
                                        <?= roleClass(
                                            $member["role_id"]
                                        ) ?>
                                    "
                                >

                                    <?= htmlspecialchars(
                                        roleLabel(
                                            $member["role_id"]
                                        )
                                    ) ?>

                                </span>

                            </td>


                            <!-- STATUS -->

                            <td>


                                <?php if (
                                    $member["status"]
                                    === "active"
                                ): ?>

                                    <span
                                        class="
                                            status-badge
                                            status-active
                                        "
                                    >
                                        Hoạt động
                                    </span>

                                <?php else: ?>

                                    <span
                                        class="
                                            status-badge
                                            status-locked
                                        "
                                    >
                                        Đã khóa
                                    </span>

                                <?php endif; ?>


                            </td>


                            <!-- ACTION -->

                            <td>


                                <div class="actions">


                                    <a
                                        href="members.php?edit=<?= (int)$member["id"] ?>#edit"
                                        class="edit-btn"
                                    >
                                        ✏️ Sửa
                                    </a>


                                    <form
                                        method="POST"
                                        onsubmit="
                                            return confirm(
                                                'Bạn có chắc muốn xóa tài khoản này?'
                                            );
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="delete_id"
                                            value="<?= (int)$member["id"] ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="delete_member"
                                            class="delete-btn"
                                        >
                                            🗑️ Xóa
                                        </button>

                                    </form>


                                </div>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                    <?php if (
                        count($members) === 0
                    ): ?>

                        <tr>

                            <td
                                colspan="8"
                                style="
                                    text-align:center;
                                    padding:40px;
                                "
                            >
                                Chưa có tài khoản.
                            </td>

                        </tr>

                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </section>


    </main>


</div>


</body>

</html>