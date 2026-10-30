<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requirePermission("activity_edit");


$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("ID hoạt động không hợp lệ.");
}


/* =========================
   LẤY HOẠT ĐỘNG
========================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM activities
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    die("Không tìm thấy hoạt động.");
}


/* =========================
   GIÁ TRỊ BAN ĐẦU
========================= */

$name =
    $activity["name"]
    ?? "";

$description =
    $activity["description"]
    ?? "";

$activity_date =
    !empty($activity["activity_date"])
        ? date(
            "Y-m-d\TH:i",
            strtotime($activity["activity_date"])
        )
        : "";

$registration_deadline =
    !empty($activity["registration_deadline"])
        ? date(
            "Y-m-d\TH:i",
            strtotime($activity["registration_deadline"])
        )
        : "";

$end_date =
    !empty($activity["end_date"])
        ? date(
            "Y-m-d\TH:i",
            strtotime($activity["end_date"])
        )
        : "";

$location =
    $activity["location"]
    ?? "";

$status =
    $activity["status"]
    ?? "open";

$error = "";


/* =========================
   CẬP NHẬT
========================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name =
        trim(
            $_POST["name"]
            ?? ""
        );

    $description =
        trim(
            $_POST["description"]
            ?? ""
        );

    $activity_date =
        $_POST["activity_date"]
        ?? "";

    $registration_deadline =
        $_POST["registration_deadline"]
        ?? "";

    $end_date =
        $_POST["end_date"]
        ?? "";

    $location =
        trim(
            $_POST["location"]
            ?? ""
        );

    $status =
        $_POST["status"]
        ?? "open";


    /* =========================
       KIỂM TRA
    ========================= */

    if ($name === "") {

        $error =
            "Vui lòng nhập tên hoạt động.";

    } elseif ($registration_deadline === "") {

        $error =
            "Vui lòng chọn hạn đăng ký.";

    } elseif ($activity_date === "") {

        $error =
            "Vui lòng chọn ngày bắt đầu hoạt động.";

    } elseif ($end_date === "") {

        $error =
            "Vui lòng chọn ngày kết thúc hoạt động.";

    } elseif ($location === "") {

        $error =
            "Vui lòng nhập địa điểm.";

    } elseif (
        !in_array(
            $status,
            [
                "open",
                "closed",
                "completed"
            ],
            true
        )
    ) {

        $error =
            "Trạng thái không hợp lệ.";

    } elseif (
        strtotime($registration_deadline)
        >= strtotime($activity_date)
    ) {

        $error =
            "Hạn đăng ký phải trước ngày bắt đầu hoạt động.";

    } elseif (
        strtotime($end_date)
        <= strtotime($activity_date)
    ) {

        $error =
            "Ngày kết thúc phải sau ngày bắt đầu hoạt động.";

    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE activities

                SET
                    name = ?,
                    description = ?,
                    activity_date = ?,
                    registration_deadline = ?,
                    end_date = ?,
                    location = ?,
                    status = ?

                WHERE id = ?
            ");

            $stmt->execute([
                $name,
                $description,
                $activity_date,
                $registration_deadline,
                $end_date,
                $location,
                $status,
                $id
            ]);


            header(
                "Location: activities.php?updated=1"
            );

            exit;

        } catch (PDOException $e) {

            $error =
                "Không thể cập nhật hoạt động: "
                . $e->getMessage();
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
    Sửa hoạt động | Đoàn xã Tân An
</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: #f5f7f5;
    color: #18241c;
}

.container {
    max-width: 850px;
    margin: auto;
    padding: 35px 20px;
}

.header {
    margin-bottom: 25px;
}

.header h1 {
    font-size: 28px;
}

.header p {
    margin-top: 7px;
    color: #536157;
    font-size: 14px;
}

.card {
    background: white;
    border: 1px solid #e3e9e4;
    border-radius: 18px;
    padding: 28px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 700;
}

input,
textarea,
select {
    width: 100%;
    border: 1px solid #dce3de;
    border-radius: 10px;
    padding: 12px 13px;
    font-family: inherit;
    font-size: 14px;
    outline: none;
}

input:focus,
textarea:focus,
select:focus {
    border-color: #176b3a;
    box-shadow: 0 0 0 3px #e8f3eb;
}

textarea {
    min-height: 130px;
    resize: vertical;
}

.row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.buttons {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn {
    border: none;
    padding: 12px 18px;
    border-radius: 10px;
    text-decoration: none;
    cursor: pointer;
    font-weight: 700;
}

.btn-primary {
    background: #176b3a;
    color: white;
}

.btn-primary:hover {
    background: #125a30;
}

.btn-secondary {
    background: #eef1ef;
    color: #354139;
}

.alert {
    background: #fff0f0;
    color: #a83232;
    padding: 13px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
}

.required {
    color: #c73535;
}

.help {
    margin-top: 6px;
    color: #536157;
    font-size: 11px;
    line-height: 1.5;
}

@media(max-width: 650px) {

    .row {
        grid-template-columns: 1fr;
    }

    .card {
        padding: 20px;
    }

}

</style>

</head>

<body>

<div class="container">


    <div class="header">

        <h1>
            ✏ Chỉnh sửa hoạt động
        </h1>

        <p>
            Cập nhật thông tin hoạt động của Đoàn xã Tân An.
        </p>

    </div>


    <?php if ($error !== ""): ?>

        <div class="alert">

            ⚠ <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>


    <div class="card">

        <form method="POST">


            <!-- TÊN HOẠT ĐỘNG -->

            <div class="form-group">

                <label>

                    Tên hoạt động

                    <span class="required">
                        *
                    </span>

                </label>


                <input
                    type="text"
                    name="name"
                    value="<?= htmlspecialchars($name) ?>"
                    required
                >

            </div>


            <!-- NỘI DUNG -->

            <div class="form-group">

                <label>
                    Nội dung hoạt động
                </label>

                <textarea
                    name="description"
                ><?= htmlspecialchars($description) ?></textarea>

            </div>


            <!-- HẠN ĐĂNG KÝ + NGÀY BẮT ĐẦU -->

            <div class="row">


                <div class="form-group">

                    <label>

                        Hạn đăng ký

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="datetime-local"
                        name="registration_deadline"
                        value="<?= htmlspecialchars($registration_deadline) ?>"
                        required
                    >


                    <div class="help">

                        Sau thời gian này hoạt động sẽ tự khóa đăng ký.

                    </div>

                </div>


                <div class="form-group">

                    <label>

                        Ngày bắt đầu hoạt động

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="datetime-local"
                        name="activity_date"
                        value="<?= htmlspecialchars($activity_date) ?>"
                        required
                    >

                </div>


            </div>


            <!-- NGÀY KẾT THÚC + ĐỊA ĐIỂM -->

            <div class="row">


                <div class="form-group">

                    <label>

                        Ngày kết thúc hoạt động

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="datetime-local"
                        name="end_date"
                        value="<?= htmlspecialchars($end_date) ?>"
                        required
                    >


                    <div class="help">

                        Sau thời gian này hoạt động chuyển sang hoàn thành.

                    </div>

                </div>


                <div class="form-group">

                    <label>

                        Địa điểm

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        name="location"
                        value="<?= htmlspecialchars($location) ?>"
                        required
                    >

                </div>


            </div>


            <!-- TRẠNG THÁI -->

            <div class="form-group">

                <label>
                    Trạng thái
                </label>


                <select name="status">


                    <option
                        value="open"
                        <?= $status === "open"
                            ? "selected"
                            : "" ?>
                    >

                        🟢 Mở đăng ký

                    </option>


                    <option
                        value="closed"
                        <?= $status === "closed"
                            ? "selected"
                            : "" ?>
                    >

                        ⚪ Đóng đăng ký

                    </option>


                    <option
                        value="completed"
                        <?= $status === "completed"
                            ? "selected"
                            : "" ?>
                    >

                        🔵 Hoàn thành

                    </option>


                </select>

            </div>


            <!-- BUTTON -->

            <div class="buttons">


                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    ✓ Lưu thay đổi

                </button>


                <a
                    href="activities.php"
                    class="btn btn-secondary"
                >

                    Hủy

                </a>


            </div>


        </form>

    </div>

</div>

</body>

</html>