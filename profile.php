<?php

require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   BẮT BUỘC ĐĂNG NHẬP
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}


/* =========================================================
   ADMIN / SUPER ADMIN KHÔNG Ở TRANG MEMBER
========================================================= */

if (
    isset($_SESSION["role"])
    &&
    (
        $_SESSION["role"] === "admin"
        ||
        $_SESSION["role"] === "super_admin"
    )
) {
    header("Location: admin/index.php");
    exit;
}


/* =========================================================
   BIẾN
========================================================= */

$user_id = (int) $_SESSION["user_id"];

$message = "";
$message_type = "";


/* =========================================================
   LẤY THÔNG TIN USER
========================================================= */

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
        status,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {

    session_destroy();

    header("Location: login.php");
    exit;
}


/* =========================================================
   CẬP NHẬT THÔNG TIN
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["save_profile"])
) {

    $full_name =
        trim($_POST["full_name"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $birthday =
        trim($_POST["birthday"] ?? "");

    $gender =
        trim($_POST["gender"] ?? "");

    $unit =
        trim($_POST["unit"] ?? "");


    /* =====================================================
       KIỂM TRA DỮ LIỆU
    ====================================================== */

    if ($full_name === "") {

        $message =
            "Vui lòng nhập họ và tên.";

        $message_type =
            "error";

    } elseif ($phone === "") {

        $message =
            "Vui lòng nhập số điện thoại.";

        $message_type =
            "error";

    } elseif (
        $email !== ""
        &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $message =
            "Email không hợp lệ.";

        $message_type =
            "error";

    } elseif (
        $gender !== ""
        &&
        !in_array(
            $gender,
            ["male", "female"],
            true
        )
    ) {

        $message =
            "Giới tính không hợp lệ.";

        $message_type =
            "error";

    } else {

        try {


            /* =============================================
               KIỂM TRA SĐT TRÙNG
            ============================================== */

            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE phone = ?
                AND id <> ?
                LIMIT 1
            ");

            $stmt->execute([
                $phone,
                $user_id
            ]);


            if ($stmt->fetch()) {

                $message =
                    "Số điện thoại này đã được sử dụng.";

                $message_type =
                    "error";

            } else {


                /* =========================================
                   KIỂM TRA EMAIL TRÙNG
                ========================================== */

                $email_exists = false;


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
                        $user_id
                    ]);

                    $email_exists =
                        $stmt->fetch();
                }


                if ($email_exists) {

                    $message =
                        "Email này đã được sử dụng.";

                    $message_type =
                        "error";

                } else {


                    /* =====================================
                       UPDATE
                    ====================================== */

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET
                            full_name = ?,
                            phone = ?,
                            email = ?,
                            birthday = ?,
                            gender = ?,
                            unit = ?
                        WHERE id = ?
                    ");


                    $stmt->execute([
                        $full_name,
                        $phone,
                        $email !== "" ? $email : null,
                        $birthday !== "" ? $birthday : null,
                        $gender !== "" ? $gender : null,
                        $unit !== "" ? $unit : null,
                        $user_id
                    ]);


                    /* =====================================
                       CẬP NHẬT SESSION
                    ====================================== */

                    $_SESSION["full_name"] =
                        $full_name;

                    $_SESSION["phone"] =
                        $phone;


                    header(
                        "Location: profile.php?updated=1"
                    );

                    exit;
                }
            }


        } catch (PDOException $e) {

            $message =
                "Không thể cập nhật thông tin lúc này.";

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   THÔNG BÁO THÀNH CÔNG
========================================================= */

if (isset($_GET["updated"])) {

    $message =
        "✓ Đã cập nhật thông tin cá nhân.";

    $message_type =
        "success";
}


/* =========================================================
   LẤY LẠI THÔNG TIN MỚI NHẤT
========================================================= */

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
        status,
        created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

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
        Hồ sơ của tôi | Đoàn xã Tân An
    </title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        .profile-main {
            max-width: 980px;

            margin: auto;

            padding: 32px 22px 60px;
        }


        /* =================================================
           HEADER
        ================================================= */

        .profile-header {
            display: flex;

            justify-content: space-between;

            align-items: flex-end;

            gap: 20px;

            margin-bottom: 25px;
        }


        .profile-header h1 {
            margin: 0;

            color: var(--blue-dark);

            font-size: 28px;
        }


        .profile-header p {
            margin: 7px 0 0;

            color: var(--muted);

            font-size: 13px;
        }


        .profile-back {
            padding: 10px 14px;

            border-radius: 10px;

            background: var(--blue-light);

            color: var(--blue);

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;
        }


        /* =================================================
           ALERT
        ================================================= */

        .profile-alert {
            padding: 13px 16px;

            margin-bottom: 20px;

            border-radius: 11px;

            font-size: 13px;

            font-weight: 600;
        }


        .profile-alert.success {
            background: #e7f5eb;

            color: #176b3a;
        }


        .profile-alert.error {
            background: #fdecec;

            color: #a83232;
        }


        /* =================================================
           CARD
        ================================================= */

        .profile-card {
            background: white;

            border: 1px solid var(--border);

            border-radius: 20px;

            overflow: hidden;

            box-shadow: var(--shadow);
        }


        .profile-top {
            display: flex;

            align-items: center;

            gap: 16px;

            padding: 24px;

            border-bottom: 1px solid var(--border);

            background: #f8faff;
        }


        .profile-avatar {
            width: 64px;

            height: 64px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 18px;

            background: var(--blue-light);

            font-size: 29px;
        }


        .profile-top h2 {
            margin: 0;

            color: var(--blue-dark);

            font-size: 20px;
        }


        .profile-top p {
            margin: 5px 0 0;

            color: var(--muted);

            font-size: 12px;
        }


        /* =================================================
           FORM
        ================================================= */

        .profile-form {
            padding: 25px;
        }


        .profile-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .profile-group {
            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .profile-group label {
            color: var(--text);

            font-size: 12px;

            font-weight: 700;
        }


        .profile-group input,
        .profile-group select {
            width: 100%;

            padding: 12px 13px;

            border: 1px solid var(--border);

            border-radius: 10px;

            background: white;

            color: var(--text);

            font-size: 13px;

            outline: none;

            transition: .2s;
        }


        .profile-group input:focus,
        .profile-group select:focus {
            border-color: var(--blue);

            box-shadow:
                0 0 0 3px
                rgba(0, 61, 165, .08);
        }


        /* =================================================
           BUTTON
        ================================================= */

        .profile-actions {
            grid-column: 1 / -1;

            display: flex;

            gap: 9px;

            padding-top: 5px;
        }


        .save-profile {
            border: none;

            padding: 12px 18px;

            border-radius: 10px;

            background: var(--blue);

            color: white;

            font-size: 13px;

            font-weight: 800;

            cursor: pointer;
        }


        .save-profile:hover {
            background: var(--blue-dark);
        }


        .cancel-profile {
            padding: 12px 18px;

            border-radius: 10px;

            background: var(--blue-light);

            color: var(--blue);

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;
        }


        /* =================================================
           MOBILE
        ================================================= */

        @media(max-width: 700px) {

            .profile-header {
                align-items: flex-start;

                flex-direction: column;
            }


            .profile-grid {
                grid-template-columns: 1fr;
            }


            .profile-actions {
                grid-column: auto;

                flex-direction: column;
            }


            .save-profile,
            .cancel-profile {
                width: 100%;

                text-align: center;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">


    <div class="nav-inner">


        <a
            href="index.php"
            class="brand"
        >


            <div class="brand-icon">
                🌐
            </div>


            <div>


                <div class="brand-name">
                    Đoàn xã Tân An
                </div>


                <div class="brand-sub">
                    Cổng hoạt động Đoàn
                </div>


            </div>


        </a>


        <div class="nav-user">


            <div class="welcome">


                <strong>
                    <?= htmlspecialchars(
                        $_SESSION["full_name"]
                        ?? "Đoàn viên"
                    ) ?>
                </strong>


                <span>
                    Tài khoản đoàn viên
                </span>


            </div>


            <a
                href="my-activities.php"
                class="nav-btn login-btn"
            >
                Hoạt động của tôi
            </a>


            <a
                href="logout.php"
                class="nav-btn logout-btn"
            >
                Đăng xuất
            </a>


        </div>


    </div>


</nav>


<!-- =========================================================
     CONTENT
========================================================= -->

<main class="profile-main">


    <div class="profile-header">


        <div>

            <h1>
                👤 Hồ sơ của tôi
            </h1>

            <p>
                Xem và cập nhật thông tin cá nhân của bạn.
            </p>

        </div>


        <a
            href="index.php"
            class="profile-back"
        >
            ← Trang hoạt động
        </a>


    </div>


    <?php if ($message !== ""): ?>


        <div
            class="
                profile-alert
                <?= htmlspecialchars($message_type) ?>
            "
        >
            <?= htmlspecialchars($message) ?>
        </div>


    <?php endif; ?>


    <section class="profile-card">


        <!-- =================================================
             PROFILE HEADER
        ================================================== -->

        <div class="profile-top">


            <div class="profile-avatar">
                👤
            </div>


            <div>


                <h2>
                    <?= htmlspecialchars(
                        $user["full_name"]
                        ?? "Đoàn viên"
                    ) ?>
                </h2>


                <p>
                    Thành viên Đoàn xã Tân An
                </p>


            </div>


        </div>


        <!-- =================================================
             FORM
        ================================================== -->

        <form
            method="POST"
            class="profile-form"
        >


            <div class="profile-grid">


                <!-- HỌ TÊN -->

                <div class="profile-group">


                    <label>
                        Họ và tên
                    </label>


                    <input
                        type="text"
                        name="full_name"
                        value="<?= htmlspecialchars(
                            $user["full_name"]
                            ?? ""
                        ) ?>"
                        required
                    >


                </div>


                <!-- PHONE -->

                <div class="profile-group">


                    <label>
                        Số điện thoại
                    </label>


                    <input
                        type="tel"
                        name="phone"
                        maxlength="10"
                        value="<?= htmlspecialchars(
                            $user["phone"]
                            ?? ""
                        ) ?>"
                        required
                    >


                </div>


                <!-- EMAIL -->

                <div class="profile-group">


                    <label>
                        Email
                    </label>


                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars(
                            $user["email"]
                            ?? ""
                        ) ?>"
                    >


                </div>


                <!-- BIRTHDAY -->

                <div class="profile-group">


                    <label>
                        Ngày sinh
                    </label>


                    <input
                        type="date"
                        name="birthday"
                        value="<?= htmlspecialchars(
                            $user["birthday"]
                            ?? ""
                        ) ?>"
                    >


                </div>


                <!-- GENDER -->

                <div class="profile-group">


                    <label>
                        Giới tính
                    </label>


                    <select name="gender">


                        <option value="">
                            Chưa cập nhật
                        </option>


                        <option
                            value="male"
                            <?= ($user["gender"] ?? "") === "male"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Nam
                        </option>


                        <option
                            value="female"
                            <?= ($user["gender"] ?? "") === "female"
                                ? "selected"
                                : ""
                            ?>
                        >
                            Nữ
                        </option>


                    </select>


                </div>


                <!-- UNIT -->

                <div class="profile-group">


                    <label>
                        Đơn vị
                    </label>


                    <input
                        type="text"
                        name="unit"
                        value="<?= htmlspecialchars(
                            $user["unit"]
                            ?? ""
                        ) ?>"
                    >


                </div>


                <!-- ACTIONS -->

                <div class="profile-actions">


                    <button
                        type="submit"
                        name="save_profile"
                        class="save-profile"
                    >
                        💾 Lưu thay đổi
                    </button>


                    <a
                        href="index.php"
                        class="cancel-profile"
                    >
                        Hủy
                    </a>


                </div>


            </div>


        </form>


    </section>


</main>


</body>

</html>