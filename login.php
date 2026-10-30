<?php

session_start();

require_once "config/database.php";


/* =========================================================
   NẾU ĐÃ ĐĂNG NHẬP
========================================================= */

if (
    isset($_SESSION["user_id"]) &&
    isset($_SESSION["role_id"])
) {

    /* ADMIN + SUPER ADMIN */

    if (
        (int)$_SESSION["role_id"] === 3 ||
        (int)$_SESSION["role_id"] === 4
    ) {

        header("Location: admin/index.php");
        exit;
    }


    /* MEMBER */

    header("Location: index.php");
    exit;
}


/* =========================================================
   BIẾN
========================================================= */

$error = "";

$phone = "";


/* =========================================================
   XỬ LÝ LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $phone = trim($_POST["phone"] ?? "");

    $password = $_POST["password"] ?? "";


    /* KIỂM TRA RỖNG */

    if ($phone === "" || $password === "") {

        $error =
            "Vui lòng nhập đầy đủ số điện thoại và mật khẩu.";

    } else {


        /* =================================================
           LẤY TÀI KHOẢN
        ================================================= */

        $stmt = $pdo->prepare("

            SELECT
                u.id,
                u.full_name,
                u.phone,
                u.password_hash,
                u.status,
                u.role_id,
                r.name AS role_name

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.id

            WHERE u.phone = ?

            LIMIT 1
        ");


        $stmt->execute([
            $phone
        ]);


        $user =
            $stmt->fetch(PDO::FETCH_ASSOC);


        /* =================================================
           KHÔNG TÌM THẤY TÀI KHOẢN
        ================================================= */

        if (!$user) {

            $error =
                "Số điện thoại hoặc mật khẩu không chính xác.";

        }


        /* =================================================
           TÀI KHOẢN BỊ KHÓA
        ================================================= */

        elseif ($user["status"] === "locked") {

            $error =
                "Tài khoản của bạn đã bị khóa.";

        }


        /* =================================================
           KIỂM TRA MẬT KHẨU
        ================================================= */

        elseif (
            !password_verify(
                $password,
                $user["password_hash"]
            )
        ) {

            $error =
                "Số điện thoại hoặc mật khẩu không chính xác.";

        }


        /* =================================================
           ĐĂNG NHẬP THÀNH CÔNG
        ================================================= */

        else {


            session_regenerate_id(true);


            /* LƯU SESSION */

            $_SESSION["user_id"] =
                $user["id"];


            $_SESSION["full_name"] =
                $user["full_name"];


            $_SESSION["phone"] =
                $user["phone"];


            $_SESSION["role_id"] =
                (int)$user["role_id"];


            $_SESSION["role"] =
                $user["role_name"];


            /* =============================================
               ADMIN
               role_id = 3

               SUPER ADMIN
               role_id = 4
            ============================================= */

            if (
                (int)$user["role_id"] === 3 ||
                (int)$user["role_id"] === 4
            ) {

                header(
                    "Location: admin/index.php"
                );

                exit;
            }


            /* =============================================
               MEMBER
            ============================================= */

            header(
                "Location: index.php"
            );

            exit;
        }
    }
}

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
        Đăng nhập | Đoàn xã Tân An
    </title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            min-height: 100vh;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 25px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #edf8f1,
                    #f7fbf8
                );

            color: #17231b;
        }


        .auth-container {

            width: 100%;

            max-width: 430px;
        }


        .auth-card {

            width: 100%;

            padding: 38px 34px;

            border:
                1px solid #d8e8de;

            border-radius: 24px;

            background: white;

            box-shadow:
                0 18px 50px
                rgba(0, 134, 62, 0.12);
        }


        .auth-header {

            text-align: center;

            margin-bottom: 28px;
        }


        .auth-logo {

            width: 90px;
            height: 90px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: transparent;

            overflow: hidden;
        }


        .auth-logo img {

            width: 100% !important;
            height: 100% !important;

            max-width: 90px !important;
            max-height: 90px !important;

            object-fit: contain;

            display: block;
        }


        .auth-label {

            display: block;

            margin-bottom: 10px;

            color: #00863e;

            font-size: 11px;

            font-weight: 800;

            letter-spacing: 1px;
        }


        .auth-header h1 {

            margin-bottom: 9px;

            color: #006b32;

            font-size: 28px;
        }


        .auth-header p {

            color: #68756d;

            font-size: 13px;

            line-height: 1.6;
        }


        .alert {

            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 10px;

            background: #fdecec;

            color: #a83232;

            font-size: 13px;

            font-weight: 600;
        }


        .auth-form {

            display: grid;

            gap: 17px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .form-group label {

            color: #25352b;

            font-size: 12px;

            font-weight: 700;
        }


        .form-group input {

            width: 100%;

            padding: 13px 14px;

            border:
                1px solid #d8e8de;

            border-radius: 11px;

            background: white;

            color: #17231b;

            font-size: 14px;

            outline: none;

            transition: .2s;
        }


        .form-group input:focus {

            border-color: #00863e;

            box-shadow:
                0 0 0 3px
                rgba(0, 134, 62, .08);
        }


        .auth-submit {

            width: 100%;

            margin-top: 4px;

            padding: 13px 16px;

            border: none;

            border-radius: 11px;

            background: #00863e;

            color: white;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;

            transition: .2s;
        }


        .auth-submit:hover {

            background: #006b32;

            transform: translateY(-1px);
        }


        .auth-footer {

            margin-top: 23px;

            padding-top: 20px;

            border-top:
                1px solid #d8e8de;

            text-align: center;

            color: #68756d;

            font-size: 12px;
        }


        .auth-footer a {

            color: #00863e;

            text-decoration: none;

            font-weight: 800;
        }


        .auth-footer a:hover {

            text-decoration: underline;
        }


        @media (max-width: 500px) {

            body {

                padding: 15px;
            }


            .auth-card {

                padding: 28px 22px;
            }


            .auth-header h1 {

                font-size: 24px;
            }

        }

    </style>

</head>


<body>


<div class="auth-container">


    <div class="auth-card">


        <div class="auth-header">


            <div class="auth-logo">

                <img
                    src="images/logodoanthanhnien.jpg"
                    alt="Logo Đoàn Thanh niên"
                >

                <img
                    src="images/logothanhnien.jpg"
                    alt="Logo Thanh niên"
                >
            </div>


            <span class="auth-label">

                ĐOÀN XÃ TÂN AN

            </span>


            <h1>

                Chào mừng trở lại

            </h1>


            <p>

                Đăng nhập để tiếp tục sử dụng hệ thống.

            </p>


        </div>


        <?php if ($error !== ""): ?>


            <div class="alert">

                • <?= htmlspecialchars($error) ?>

            </div>


        <?php endif; ?>


        <form
            method="POST"
            class="auth-form"
        >


            <div class="form-group">


                <label>

                    Số điện thoại

                </label>


                <input
                    type="tel"
                    name="phone"
                    placeholder="09xxxxxxxx"
                    maxlength="10"
                    value="<?= htmlspecialchars($phone) ?>"
                    required
                >


            </div>


            <div class="form-group">


                <label>

                    Mật khẩu

                </label>


                <input
                    type="password"
                    name="password"
                    placeholder="Nhập mật khẩu"
                    required
                >


            </div>


            <button
                type="submit"
                class="auth-submit"
            >

                Đăng nhập →

            </button>


        </form>


        <div class="auth-footer">

            Chưa có tài khoản?

            <a href="register.php">

                Đăng ký ngay

            </a>

        </div>


    </div>


</div>


</body>

</html>