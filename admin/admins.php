<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   KIỂM TRA ĐĂNG NHẬP
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit;
}


/* =========================================================
   CHỈ SUPER ADMIN ĐƯỢC TẠO ADMIN
   role_id = 4
========================================================= */

if (
    !isset($_SESSION["role_id"]) ||
    (int)$_SESSION["role_id"] !== 4
) {
    http_response_code(403);
    die("Bạn không có quyền tạo tài khoản quản trị.");
}


/* =========================================================
   BIẾN
========================================================= */

$error = "";
$success = "";

$full_name = "";
$phone = "";
$email = "";
$unit = "";


/* =========================================================
   XỬ LÝ TẠO TÀI KHOẢN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $unit = trim($_POST["unit"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    if (
        $full_name === "" ||
        $phone === "" ||
        $email === "" ||
        $password === "" ||
        $confirm_password === ""
    ) {

        $error = "Vui lòng nhập đầy đủ thông tin.";

    } elseif ($password !== $confirm_password) {

        $error = "Mật khẩu xác nhận không khớp.";

    } elseif (strlen($password) < 6) {

        $error = "Mật khẩu phải có ít nhất 6 ký tự.";

    } else {


        /* =================================================
           KIỂM TRA TRÙNG SĐT / EMAIL
        ================================================= */

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE phone = ?
               OR email = ?
            LIMIT 1
        ");

        $stmt->execute([
            $phone,
            $email
        ]);


        if ($stmt->fetch()) {

            $error =
                "Số điện thoại hoặc email đã tồn tại.";

        } else {


            /* =================================================
               HASH MẬT KHẨU
            ================================================= */

            $password_hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            /* =================================================
               TẠO ADMIN
               role_id = 3
            ================================================= */

            $stmt = $pdo->prepare("
                INSERT INTO users
                (
                    full_name,
                    phone,
                    email,
                    unit,
                    password_hash,
                    role_id,
                    status,
                    created_at,
                    updated_at
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    3,
                    'active',
                    NOW(),
                    NOW()
                )
            ");


            $stmt->execute([
                $full_name,
                $phone,
                $email,
                $unit,
                $password_hash
            ]);


            $success =
                "Tạo tài khoản quản trị thành công.";


            $full_name = "";
            $phone = "";
            $email = "";
            $unit = "";
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
        Thêm tài khoản quản trị | Đoàn xã Tân An
    </title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            min-height: 100vh;

            padding: 35px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7f5;

            color: #18241c;
        }


        .container {

            max-width: 760px;

            margin: auto;
        }


        .top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;
        }


        .top h1 {

            font-size: 28px;

            color: #123b25;
        }


        .back {

            text-decoration: none;

            color: #00863e;

            font-weight: 700;
        }


        .card {

            background: white;

            border: 1px solid #e1e8e3;

            border-radius: 18px;

            padding: 28px;

            box-shadow:
                0 10px 30px
                rgba(0, 0, 0, .05);
        }


        .alert {

            padding: 13px 15px;

            margin-bottom: 18px;

            border-radius: 10px;

            font-size: 14px;

            font-weight: 600;
        }


        .error {

            background: #fdecec;

            color: #a83232;
        }


        .success {

            background: #e6f5ec;

            color: #006b32;
        }


        .form {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .full {

            grid-column: 1 / -1;
        }


        label {

            font-size: 13px;

            font-weight: 700;
        }


        input {

            width: 100%;

            padding: 12px 13px;

            border:
                1px solid #d8e5dc;

            border-radius: 10px;

            outline: none;

            font-size: 14px;
        }


        input:focus {

            border-color: #00863e;

            box-shadow:
                0 0 0 3px
                rgba(0, 134, 62, .08);
        }


        button {

            width: 100%;

            padding: 13px;

            border: none;

            border-radius: 10px;

            background: #00863e;

            color: white;

            font-size: 14px;

            font-weight: 800;

            cursor: pointer;
        }


        button:hover {

            background: #006b32;
        }


        .note {

            margin-top: 20px;

            padding: 13px;

            border-radius: 10px;

            background: #f1f6f2;

            color: #5d6b61;

            font-size: 13px;

            line-height: 1.6;
        }


        @media(max-width: 650px) {

            body {

                padding: 20px;
            }


            .form {

                grid-template-columns: 1fr;
            }


            .full {

                grid-column: auto;
            }


            .top {

                flex-direction: column;

                align-items: flex-start;

                gap: 10px;
            }
        }

    </style>

</head>


<body>


<div class="container">


    <div class="top">


        <div>

            <h1>
                Thêm tài khoản quản trị
            </h1>

        </div>


        <a
            href="index.php"
            class="back"
        >
            ← Quay lại
        </a>


    </div>


    <div class="card">


        <?php if ($error !== ""): ?>

            <div class="alert error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success !== ""): ?>

            <div class="alert success">

                <?= htmlspecialchars($success) ?>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            class="form"
        >


            <div class="form-group full">

                <label>
                    Họ và tên
                </label>

                <input
                    type="text"
                    name="full_name"
                    value="<?= htmlspecialchars($full_name) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Số điện thoại
                </label>

                <input
                    type="tel"
                    name="phone"
                    maxlength="10"
                    value="<?= htmlspecialchars($phone) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    required
                >

            </div>


            <div class="form-group full">

                <label>
                    Đơn vị
                </label>

                <input
                    type="text"
                    name="unit"
                    value="<?= htmlspecialchars($unit) ?>"
                >

            </div>


            <div class="form-group">

                <label>
                    Mật khẩu
                </label>

                <input
                    type="password"
                    name="password"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Xác nhận mật khẩu
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    required
                >

            </div>


            <div class="form-group full">

                <button type="submit">

                    Tạo tài khoản quản trị

                </button>

            </div>


        </form>


    </div>


</div>


</body>

</html>