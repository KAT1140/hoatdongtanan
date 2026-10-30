<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();


/* =========================================================
   CHỈ ADMIN / SUPER ADMIN
========================================================= */

if (
    !hasRole("admin") &&
    !hasRole("super_admin")
) {
    http_response_code(403);
    die("Bạn không có quyền truy cập.");
}


/* =========================================================
   BIẾN
========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   ACTIVITY ID
========================================================= */

$activity_id =
    isset($_GET["activity_id"])
    ? (int) $_GET["activity_id"]
    : 0;


/* =========================================================
   XỬ LÝ POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* =====================================================
       ĐÁNH DẤU ĐÃ THAM GIA
    ====================================================== */

    if (isset($_POST["mark_attended"])) {

        $registration_id =
            (int)($_POST["registration_id"] ?? 0);


        if ($registration_id <= 0) {

            $message =
                "Đăng ký không hợp lệ.";

            $message_type =
                "error";

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE registrations
                    SET status = 'attended'
                    WHERE id = ?
                ");


                $stmt->execute([
                    $registration_id
                ]);


                $redirect =
                    "registrations.php?updated=1";


                if ($activity_id > 0) {

                    $redirect .=
                        "&activity_id="
                        . $activity_id;
                }


                header(
                    "Location: "
                    . $redirect
                );

                exit;


            } catch (PDOException $e) {

                $message =
                    "Không thể cập nhật trạng thái.";

                $message_type =
                    "error";
            }
        }
    }


    /* =====================================================
       KHÔI PHỤC VỀ ĐÃ ĐĂNG KÝ
    ====================================================== */

    if (isset($_POST["mark_registered"])) {

        $registration_id =
            (int)($_POST["registration_id"] ?? 0);


        if ($registration_id <= 0) {

            $message =
                "Đăng ký không hợp lệ.";

            $message_type =
                "error";

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE registrations
                    SET status = 'registered'
                    WHERE id = ?
                ");


                $stmt->execute([
                    $registration_id
                ]);


                $redirect =
                    "registrations.php?updated=1";


                if ($activity_id > 0) {

                    $redirect .=
                        "&activity_id="
                        . $activity_id;
                }


                header(
                    "Location: "
                    . $redirect
                );

                exit;


            } catch (PDOException $e) {

                $message =
                    "Không thể cập nhật trạng thái.";

                $message_type =
                    "error";
            }
        }
    }


    /* =====================================================
       XÓA THÀNH VIÊN KHỎI HOẠT ĐỘNG
    ====================================================== */

    if (isset($_POST["delete_registration"])) {

        $registration_id =
            (int)($_POST["registration_id"] ?? 0);


        if ($registration_id <= 0) {

            $message =
                "Đăng ký không hợp lệ.";

            $message_type =
                "error";

        } else {

            try {


                $stmt = $pdo->prepare("
                    DELETE FROM registrations
                    WHERE id = ?
                ");


                $stmt->execute([
                    $registration_id
                ]);


                $redirect =
                    "registrations.php?deleted=1";


                if ($activity_id > 0) {

                    $redirect .=
                        "&activity_id="
                        . $activity_id;
                }


                header(
                    "Location: "
                    . $redirect
                );

                exit;


            } catch (PDOException $e) {

                $message =
                    "Không thể xóa thành viên khỏi hoạt động.";

                $message_type =
                    "error";
            }
        }
    }
}


/* =========================================================
   THÔNG BÁO
========================================================= */

if (isset($_GET["updated"])) {

    $message =
        "✓ Đã cập nhật trạng thái.";

    $message_type =
        "success";
}


if (isset($_GET["deleted"])) {

    $message =
        "✓ Đã xóa thành viên khỏi hoạt động.";

    $message_type =
        "success";
}


/* =========================================================
   LẤY HOẠT ĐỘNG ĐANG XEM
========================================================= */

$current_activity = null;


if ($activity_id > 0) {


    $stmt = $pdo->prepare("
        SELECT
            id,
            name,
            activity_date,
            location
        FROM activities
        WHERE id = ?
        LIMIT 1
    ");


    $stmt->execute([
        $activity_id
    ]);


    $current_activity =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );
}


/* =========================================================
   LẤY DANH SÁCH ĐĂNG KÝ
========================================================= */

if ($activity_id > 0) {


    $stmt = $pdo->prepare("
        SELECT

            r.id AS registration_id,
            r.confirmation_code,
            r.status,
            r.registered_at,

            u.id AS user_id,
            u.full_name,
            u.phone,
            u.email,
            u.unit,

            a.id AS activity_id,
            a.name AS activity_name

        FROM registrations r

        INNER JOIN users u
            ON r.user_id = u.id

        INNER JOIN activities a
            ON r.activity_id = a.id

        WHERE r.activity_id = ?

        ORDER BY r.registered_at DESC
    ");


    $stmt->execute([
        $activity_id
    ]);


} else {


    $stmt = $pdo->query("
        SELECT

            r.id AS registration_id,
            r.confirmation_code,
            r.status,
            r.registered_at,

            u.id AS user_id,
            u.full_name,
            u.phone,
            u.email,
            u.unit,

            a.id AS activity_id,
            a.name AS activity_name

        FROM registrations r

        INNER JOIN users u
            ON r.user_id = u.id

        INNER JOIN activities a
            ON r.activity_id = a.id

        ORDER BY r.registered_at DESC
    ");
}


$registrations =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );


/* =========================================================
   THỐNG KÊ
========================================================= */

$total =
    count($registrations);


$registered_count = 0;

$attended_count = 0;


foreach (
    $registrations
    as $registration
) {

    /*
     * Người đã tham gia trước đó cũng đã đăng ký,
     * nên trạng thái attended vẫn được tính vào
     * tổng "Đã đăng ký".
     */
    if (
        in_array(
            $registration["status"],
            ["registered", "attended"],
            true
        )
    ) {

        $registered_count++;
    }

    if (
        $registration["status"]
        === "attended"
    ) {

        $attended_count++;
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
        Quản lý đăng ký | Đoàn xã Tân An
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        body {
            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #f5f7f5;

            color: #17231b;
        }


        /* =================================================
           LAYOUT
        ================================================= */

        .admin-layout {
            min-height: 100vh;

            display: flex;
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

            padding: 25px 18px;

            position: fixed;

            top: 0;
            bottom: 0;
            left: 0;

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

            background: transparent;

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
            background:
                rgba(255,255,255,.12);

            color: white;
        }


        /* =================================================
           TOP
        ================================================= */

        .admin-topbar {
            display: flex;

            justify-content:
                space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
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

            background: white;

            border:
                1px solid #e3e9e4;

            border-radius: 12px;
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
           BACK
        ================================================= */

        .back-row {
            margin-bottom: 20px;
        }


        .back-btn {
            display: inline-block;

            padding: 10px 13px;

            border-radius: 9px;

            background: #eef1ef;

            color: #435047;

            text-decoration: none;

            font-size: 12px;

            font-weight: 700;
        }


        /* =================================================
           ACTIVITY INFO
        ================================================= */

        .activity-info {
            margin-bottom: 20px;

            padding: 18px;

            background: white;

            border:
                1px solid #e3e9e4;

            border-radius: 15px;
        }


        .activity-info h2 {
            margin: 0;

            font-size: 18px;
        }


        .activity-info p {
            margin: 7px 0 0;

            color: #536157;

            font-size: 12px;
        }


        /* =================================================
           STATS
        ================================================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 12px;

            margin-bottom: 20px;
        }


        .stat {
            padding: 16px;

            background: white;

            border:
                1px solid #e3e9e4;

            border-radius: 13px;
        }


        .stat span {
            display: block;

            color: #536157;

            font-size: 10px;

            text-transform: uppercase;
        }


        .stat strong {
            display: block;

            margin-top: 5px;

            font-size: 22px;
        }


        /* =================================================
           MESSAGE
        ================================================= */

        .message {
            padding: 13px 16px;

            margin-bottom: 20px;

            border-radius: 10px;

            font-size: 13px;
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
           TABLE
        ================================================= */

        .card {
            background: white;

            border:
                1px solid #e3e9e4;

            border-radius: 17px;

            overflow: hidden;
        }


        .table-wrapper {
            overflow-x: auto;
        }


        table {
            width: 100%;

            min-width: 1000px;

            border-collapse: collapse;
        }


        th {
            padding: 14px;

            background: #f7f9f7;

            color: #68756c;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            white-space: nowrap;
        }


        td {
            padding: 14px;

            border-top:
                1px solid #edf0ed;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr:hover {
            background: #fafcfb;
        }


        .member-name {
            font-weight: 700;

            color: #183522;
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

            white-space: nowrap;
        }


        .status-registered {
            background: #eef4ff;

            color: #315b9a;
        }


        .status-attended {
            background: #e7f5eb;

            color: #176b3a;
        }


        /* =================================================
           ACTIONS
        ================================================= */

        .actions {
            display: flex;

            gap: 5px;

            flex-wrap: wrap;
        }


        .actions form {
            margin: 0;
        }


        .action-btn {
            border: none;

            padding: 7px 9px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 10px;

            font-weight: 700;
        }


        .btn-attended {
            background: #e7f5eb;

            color: #176b3a;
        }


        .btn-register {
            background: #eef4ff;

            color: #315b9a;
        }


        .btn-delete {
            background: #fdecec;

            color: #a83232;
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

                width:
                    calc(100% - 210px);

                padding: 25px;
            }


            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
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


            .stats {
                grid-template-columns:
                    1fr;
            }


            .admin-topbar {
                flex-direction: column;

                align-items: flex-start;
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
                    📝 Danh sách đăng ký
                </h1>

                <p>
                    Quản lý thành viên đăng ký hoạt động.
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
             BACK
        ================================================== -->

        <div class="back-row">


            <a
                href="activities.php"
                class="back-btn"
            >
                ← Quay lại Hoạt động
            </a>
    <?php if ($current_activity): ?>

        <a
            href="xuat-danh-sach.php?activity_id=<?= (int)$activity_id ?>"
            class="export-btn"
        >
            📥 Xuất danh sách
        </a>

    <?php endif; ?>

        </div>


        <!-- =================================================
             ACTIVITY INFO
        ================================================== -->

        <?php if (
            $current_activity
        ): ?>


            <div class="activity-info">


                <h2>

                    <?= htmlspecialchars(
                        $current_activity["name"]
                    ) ?>

                </h2>


                <p>

                    📅

                    <?= !empty(
                        $current_activity[
                            "activity_date"
                        ]
                    )
                        ? date(
                            "d/m/Y",
                            strtotime(
                                $current_activity[
                                    "activity_date"
                                ]
                            )
                        )
                        : "-"
                    ?>

                    &nbsp;&nbsp; · &nbsp;&nbsp;

                    📍

                    <?= htmlspecialchars(
                        $current_activity[
                            "location"
                        ]
                        ?? "-"
                    ) ?>

                </p>


            </div>


        <?php endif; ?>


        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if (
            $message !== ""
        ): ?>


            <div
                class="
                    message
                    <?= htmlspecialchars(
                        $message_type
                    ) ?>
                "
            >

                <?= htmlspecialchars(
                    $message
                ) ?>

            </div>


        <?php endif; ?>


        <!-- =================================================
             STATS
        ================================================== -->

        <div class="stats">


            <div class="stat">

                <span>
                    Tổng thành viên
                </span>

                <strong>
                    <?= $total ?>
                </strong>

            </div>


            <div class="stat">

                <span>
                    Đã đăng ký
                </span>

                <strong>
                    <?= $registered_count ?>
                </strong>

            </div>


            <div class="stat">

                <span>
                    Đã tham gia
                </span>

                <strong>
                    <?= $attended_count ?>
                </strong>

            </div>


        </div>


        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="card">


            <div class="table-wrapper">


                <table>


                    <thead>

                        <tr>

                            <th>
                                Họ tên
                            </th>

                            <th>
                                SĐT
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Đơn vị
                            </th>

                            <th>
                                Mã đăng ký
                            </th>

                            <th>
                                Thời gian đăng ký
                            </th>

                            <th>
                                Trạng thái
                            </th>

                            <th>
                                Thao tác
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($registrations)
                        === 0
                    ): ?>


                        <tr>

                            <td
                                colspan="8"
                                style="
                                    text-align:center;
                                    padding:50px;
                                "
                            >
                                Chưa có thành viên đăng ký.
                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $registrations
                            as $registration
                        ): ?>


                            <tr>


                                <!-- NAME -->

                                <td>

                                    <span class="member-name">

                                        <?= htmlspecialchars(
                                            $registration[
                                                "full_name"
                                            ]
                                        ) ?>

                                    </span>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <?= htmlspecialchars(
                                        $registration[
                                            "phone"
                                        ]
                                        ?? "-"
                                    ) ?>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?= htmlspecialchars(
                                        !empty(
                                            $registration[
                                                "email"
                                            ]
                                        )
                                        ? $registration[
                                            "email"
                                        ]
                                        : "-"
                                    ) ?>

                                </td>


                                <!-- UNIT -->

                                <td>

                                    <?= htmlspecialchars(
                                        !empty(
                                            $registration[
                                                "unit"
                                            ]
                                        )
                                        ? $registration[
                                            "unit"
                                        ]
                                        : "-"
                                    ) ?>

                                </td>


                                <!-- CODE -->

                                <td>

                                    <?= htmlspecialchars(
                                        !empty(
                                            $registration[
                                                "confirmation_code"
                                            ]
                                        )
                                        ? $registration[
                                            "confirmation_code"
                                        ]
                                        : "-"
                                    ) ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?= !empty(
                                        $registration[
                                            "registered_at"
                                        ]
                                    )
                                        ? date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $registration[
                                                    "registered_at"
                                                ]
                                            )
                                        )
                                        : "-"
                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $registration[
                                            "status"
                                        ]
                                        === "attended"
                                    ): ?>


                                        <span
                                            class="
                                                status-badge
                                                status-attended
                                            "
                                        >
                                            ✓ Đã tham gia
                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="
                                                status-badge
                                                status-registered
                                            "
                                        >
                                            Đã đăng ký
                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- ACTION -->

                                <td>


                                    <div class="actions">


                                        <!-- =================================
                                             ĐÁNH DẤU ĐÃ THAM GIA
                                        ================================== -->

                                        <?php if (
                                            $registration[
                                                "status"
                                            ]
                                            !== "attended"
                                        ): ?>


                                            <form method="POST">


                                                <input
                                                    type="hidden"
                                                    name="registration_id"
                                                    value="<?= (int)$registration["registration_id"] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="mark_attended"
                                                    class="
                                                        action-btn
                                                        btn-attended
                                                    "
                                                >
                                                    ✓ Đã tham gia
                                                </button>


                                            </form>


                                        <?php else: ?>


                                            <!-- =================================
                                                 CHUYỂN LẠI ĐÃ ĐĂNG KÝ
                                            ================================== -->

                                            <form method="POST">


                                                <input
                                                    type="hidden"
                                                    name="registration_id"
                                                    value="<?= (int)$registration["registration_id"] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="mark_registered"
                                                    class="
                                                        action-btn
                                                        btn-register
                                                    "
                                                >
                                                    ↻ Đã đăng ký
                                                </button>


                                            </form>


                                        <?php endif; ?>


                                        <!-- =================================
                                             XÓA KHỎI HOẠT ĐỘNG
                                        ================================== -->

                                        <form
                                            method="POST"

                                            onsubmit="
                                                return confirm(
                                                    'Bạn có chắc muốn xóa thành viên này khỏi hoạt động?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="registration_id"
                                                value="<?= (int)$registration["registration_id"] ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="delete_registration"
                                                class="
                                                    action-btn
                                                    btn-delete
                                                "
                                            >
                                                🗑 Xóa khỏi hoạt động
                                            </button>


                                        </form>


                                    </div>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </main>


</div>


</body>

</html>