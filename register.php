<?php

require_once "config/database.php";

$errors = [];
$success = "";

$full_name = "";
$phone = "";
$email = "";
$birthday = "";
$gender = "";
$unit = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Lấy dữ liệu từ form
    $full_name = trim($_POST["full_name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $birthday = $_POST["birthday"] ?? "";
    $gender = $_POST["gender"] ?? "";
    $unit = trim($_POST["unit"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";


    // =========================
    // KIỂM TRA DỮ LIỆU
    // =========================

    if ($full_name === "") {
        $errors[] = "Vui lòng nhập họ và tên.";
    }

    if ($phone === "") {
        $errors[] = "Vui lòng nhập số điện thoại.";
    } elseif (!preg_match("/^(0[0-9]{9})$/", $phone)) {
        $errors[] = "Số điện thoại không hợp lệ.";
    }

    if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ.";
    }

    if ($birthday === "") {
        $errors[] = "Vui lòng chọn ngày sinh.";
    }

    if ($gender === "") {
        $errors[] = "Vui lòng chọn giới tính.";
    }

    if ($unit === "") {
        $errors[] = "Vui lòng nhập chi đoàn / đơn vị.";
    }

    if ($password === "") {
        $errors[] = "Vui lòng nhập mật khẩu.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }


    // =========================
    // KIỂM TRA SỐ ĐIỆN THOẠI
    // =========================

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE phone = ?
            LIMIT 1
        ");

        $stmt->execute([$phone]);

        if ($stmt->fetch()) {
            $errors[] = "Số điện thoại này đã được đăng ký.";
        }
    }


    // =========================
    // TẠO TÀI KHOẢN
    // =========================

    if (empty($errors)) {

        // Hash mật khẩu
        $password_hash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name,
                    phone,
                    email,
                    birthday,
                    gender,
                    unit,
                    password_hash,
                    role_id,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'active')
            ");

            $stmt->execute([
                $full_name,
                $phone,
                $email !== "" ? $email : null,
                $birthday,
                $gender,
                $unit,
                $password_hash
            ]);

            $success = "Đăng ký tài khoản thành công!";

            // Xóa dữ liệu form sau khi đăng ký
            $full_name = "";
            $phone = "";
            $email = "";
            $birthday = "";
            $gender = "";
            $unit = "";

        } catch (PDOException $e) {

            $errors[] = "Có lỗi xảy ra khi tạo tài khoản.";
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
        Đăng ký tài khoản | Đoàn xã Tân An
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="auth-page">

<div class="auth-container">

    <div class="auth-card">

        <!-- HEADER -->

        <div class="auth-header">

            <div class="auth-logo">
                🌿
            </div>

            <span class="auth-label">
                ĐOÀN XÃ TÂN AN
            </span>

            <h1>
                Tạo tài khoản
            </h1>

            <p>
                Đăng ký để tham gia các hoạt động
                của Đoàn xã Tân An.
            </p>

        </div>


        <!-- THÔNG BÁO LỖI -->

        <?php if (!empty($errors)): ?>

            <div class="alert alert-error">

                <?php foreach ($errors as $error): ?>

                    <div>
                        • <?= htmlspecialchars($error) ?>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>


        <!-- THÔNG BÁO THÀNH CÔNG -->

        <?php if ($success !== ""): ?>

            <div class="alert alert-success">

                ✓ <?= htmlspecialchars($success) ?>

                <div class="success-login">

                    <a href="login.php">
                        Đăng nhập ngay →
                    </a>

                </div>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            method="POST"
            action=""
            class="auth-form"
        >

            <div class="form-group">

                <label>
                    Họ và tên
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Nguyễn Văn A"
                    value="<?= htmlspecialchars($full_name) ?>"
                    required
                >

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Số điện thoại
                        <span>*</span>
                    </label>

                    <input
                        type="tel"
                        name="phone"
                        placeholder="09xxxxxxxx"
                        value="<?= htmlspecialchars($phone) ?>"
                        maxlength="10"
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
                        placeholder="example@gmail.com"
                        value="<?= htmlspecialchars($email) ?>"
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Ngày sinh
                        <span>*</span>
                    </label>

                    <input
                        type="date"
                        name="birthday"
                        value="<?= htmlspecialchars($birthday) ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Giới tính
                        <span>*</span>
                    </label>

                    <select
                        name="gender"
                        required
                    >

                        <option value="">
                            Chọn giới tính
                        </option>

                        <option
                            value="Nam"
                            <?= $gender === "Nam" ? "selected" : "" ?>
                        >
                            Nam
                        </option>

                        <option
                            value="Nữ"
                            <?= $gender === "Nữ" ? "selected" : "" ?>
                        >
                            Nữ
                        </option>

                        <option
                            value="Khác"
                            <?= $gender === "Khác" ? "selected" : "" ?>
                        >
                            Khác
                        </option>

                    </select>

                </div>

            </div>


            <div class="form-group">

                <label>
                    Chi đoàn / Đơn vị
                    <span>*</span>
                </label>

                <input
                    type="text"
                    name="unit"
                    placeholder="Ví dụ: Chi đoàn ấp..."
                    value="<?= htmlspecialchars($unit) ?>"
                    required
                >

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Mật khẩu
                        <span>*</span>
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="Ít nhất 6 ký tự"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Xác nhận mật khẩu
                        <span>*</span>
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Nhập lại mật khẩu"
                        required
                    >

                </div>

            </div>


            <button
                type="submit"
                class="auth-submit"
            >
                Tạo tài khoản
                →
            </button>

        </form>


        <div class="auth-footer">

            Đã có tài khoản?

            <a href="login.php">
                Đăng nhập
            </a>

        </div>

    </div>

</div>

</body>

</html>