<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();


/* =========================================================
   CHỈ ADMIN / SUPER ADMIN
   role_id = 3 hoặc 4
========================================================= */

if (
    !isset($_SESSION["role_id"]) ||
    !in_array(
        (int)$_SESSION["role_id"],
        [3, 4],
        true
    )
) {

    http_response_code(403);

    die("Bạn không có quyền truy cập.");
}


/* =========================================================
   TỰ ĐỘNG KHÓA KHI HẾT HẠN ĐĂNG KÝ
========================================================= */

try {

    $pdo->query("
        UPDATE activities

        SET status = 'closed'

        WHERE status = 'open'

        AND registration_deadline IS NOT NULL

        AND registration_deadline < NOW()
    ");

} catch (PDOException $e) {

    // Có thể bỏ qua để trang vẫn tiếp tục chạy.

}


/* =========================================================
   TỰ ĐỘNG HOÀN THÀNH SAU NGÀY KẾT THÚC
========================================================= */

try {

    $pdo->query("
        UPDATE activities

        SET status = 'completed'

        WHERE end_date IS NOT NULL

        AND end_date < NOW()

        AND status <> 'completed'
    ");

} catch (PDOException $e) {

    // Có thể bỏ qua để trang vẫn tiếp tục chạy.

}


/* =========================================================
   XÓA HOẠT ĐỘNG
========================================================= */

$error = "";


if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["delete_id"])
) {

    $delete_id =
        (int) $_POST["delete_id"];


    if ($delete_id > 0) {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM activities

                WHERE id = ?
            ");


            $stmt->execute([
                $delete_id
            ]);


            header(
                "Location: activities.php?deleted=1"
            );

            exit;


        } catch (PDOException $e) {

            $error =
                "Không thể xóa hoạt động vì có dữ liệu đăng ký liên quan.";
        }
    }
}


/* =========================================================
   LẤY DANH SÁCH HOẠT ĐỘNG
========================================================= */

$stmt = $pdo->query("

    SELECT

        a.*,

        COUNT(
            CASE

                WHEN r.status = 'registered'

                THEN r.id

            END
        ) AS registered_count

    FROM activities a

    LEFT JOIN registrations r

        ON a.id = r.activity_id

    GROUP BY a.id

    ORDER BY a.activity_date DESC

");


$activities =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

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
        Quản lý hoạt động | Đoàn xã Tân An
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        * {
            box-sizing: border-box;
        }


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

            width:
                calc(100% - 250px);

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

            left: 0;
            top: 0;
            bottom: 0;

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

            justify-content: space-between;

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

            border:
                1px solid #e3e9e4;

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
           ACTION TOP
        ================================================= */

        .top-actions {

            display: flex;

            justify-content: flex-end;

            margin-bottom: 20px;
        }


        /* =================================================
           BUTTON
        ================================================= */

        .btn {

            display: inline-block;

            border: none;

            padding: 9px 12px;

            border-radius: 8px;

            text-decoration: none;

            cursor: pointer;

            font-size: 11px;

            font-weight: 700;
        }


        .btn-primary {

            padding: 11px 16px;

            background: #176b3a;

            color: white;

            font-size: 13px;
        }


        .btn-primary:hover {

            background: #125a30;
        }


        .btn-view {

            background: #e7f5eb;

            color: #176b3a;
        }


        .btn-edit {

            background: #eef4ff;

            color: #315b9a;
        }


        .btn-delete {

            background: #fdecec;

            color: #a83232;
        }


        .btn-process {

            background: #fff4d9;

            color: #8a6400;
        }


        /* =================================================
           ALERT
        ================================================= */

        .alert {

            padding: 13px 16px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-size: 13px;
        }


        .alert-success {

            background: #e7f5eb;

            color: #176b3a;
        }


        .alert-error {

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

            border-radius: 18px;

            overflow: hidden;
        }


        .table-wrapper {

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;
        }


        th {

            padding: 14px 15px;

            background: #f7f9f7;

            color: #68756c;

            text-align: left;

            font-size: 11px;

            text-transform: uppercase;

            white-space: nowrap;
        }


        td {

            padding: 15px;

            border-top:
                1px solid #edf0ed;

            font-size: 13px;

            vertical-align: middle;
        }


        tbody tr:hover {

            background: #fafcfb;
        }


        .activity-name {

            font-weight: 700;

            color: #183522;
        }


        .date-text {

            white-space: nowrap;
        }


        .actions {

            display: flex;

            flex-wrap: wrap;

            gap: 6px;
        }


        .actions form {

            margin: 0;
        }


        /* =================================================
           STATUS
        ================================================= */

        .badge {

            display: inline-block;

            padding: 6px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

            white-space: nowrap;
        }


        .badge-open {

            background: #e7f5eb;

            color: #176b3a;
        }


        .badge-closed {

            background: #f1f2f2;

            color: #66706a;
        }


        .badge-completed {

            background: #eef4ff;

            color: #315b9a;
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

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <?php

    require_once
        "includes/admin-sidebar.php";

    ?>


    <main class="admin-main">


        <!-- =================================================
             TOP
        ================================================== -->

        <div class="admin-topbar">


            <div>


                <h1>

                    📅 Hoạt động

                </h1>


                <p>

                    Quản lý các hoạt động của Đoàn xã Tân An.

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
             THÊM HOẠT ĐỘNG
        ================================================== -->

        <div class="top-actions">


            <a
                href="activity-add.php"
                class="btn btn-primary"
            >

                ＋ Thêm hoạt động

            </a>


        </div>



        <!-- =================================================
             MESSAGE
        ================================================== -->

        <?php if (
            isset($_GET["created"])
        ): ?>


            <div class="alert alert-success">

                ✓ Đã thêm hoạt động.

            </div>


        <?php endif; ?>



        <?php if (
            isset($_GET["updated"])
        ): ?>


            <div class="alert alert-success">

                ✓ Đã cập nhật hoạt động.

            </div>


        <?php endif; ?>



        <?php if (
            isset($_GET["deleted"])
        ): ?>


            <div class="alert alert-success">

                ✓ Đã xóa hoạt động.

            </div>


        <?php endif; ?>



        <?php if ($error !== ""): ?>


            <div class="alert alert-error">

                <?= htmlspecialchars(
                    $error
                ) ?>

            </div>


        <?php endif; ?>



        <!-- =================================================
             TABLE
        ================================================== -->

        <div class="card">


            <div class="table-wrapper">


                <table>


                    <thead>


                        <tr>


                            <th>
                                Hoạt động
                            </th>


                            <th>
                                Ngày hoạt động
                            </th>


                            <th>
                                Hạn đăng ký
                            </th>


                            <th>
                                Ngày kết thúc
                            </th>


                            <th>
                                Địa điểm
                            </th>


                            <th>
                                Đăng ký
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
                        count($activities) === 0
                    ): ?>


                        <tr>


                            <td
                                colspan="8"

                                style="
                                    text-align:center;
                                    padding:50px;
                                "
                            >

                                Chưa có hoạt động nào.

                            </td>


                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $activities
                            as $activity
                        ): ?>


                            <?php


                            $activity_completed =
                                $activity["status"]
                                === "completed";


                            ?>


                            <tr>


                                <!-- TÊN -->

                                <td>


                                    <span class="activity-name">


                                        <?= htmlspecialchars(
                                            $activity["name"]
                                        ) ?>


                                    </span>


                                </td>



                                <!-- NGÀY HOẠT ĐỘNG -->

                                <td class="date-text">


                                    <?= !empty(
                                        $activity["activity_date"]
                                    )

                                        ? date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $activity["activity_date"]
                                            )
                                        )

                                        : "-"
                                    ?>


                                </td>



                                <!-- HẠN ĐĂNG KÝ -->

                                <td class="date-text">


                                    <?= !empty(
                                        $activity[
                                            "registration_deadline"
                                        ]
                                    )

                                        ? date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $activity[
                                                    "registration_deadline"
                                                ]
                                            )
                                        )

                                        : "-"
                                    ?>


                                </td>



                                <!-- NGÀY KẾT THÚC -->

                                <td class="date-text">


                                    <?= !empty(
                                        $activity["end_date"]
                                    )

                                        ? date(
                                            "d/m/Y H:i",
                                            strtotime(
                                                $activity["end_date"]
                                            )
                                        )

                                        : "-"
                                    ?>


                                </td>



                                <!-- ĐỊA ĐIỂM -->

                                <td>


                                    📍


                                    <?= htmlspecialchars(
                                        $activity["location"]
                                        ?? "-"
                                    ) ?>


                                </td>



                                <!-- SỐ ĐĂNG KÝ -->

                                <td>


                                    👥


                                    <?= (int)
                                        $activity[
                                            "registered_count"
                                        ]
                                    ?>


                                </td>



                                <!-- TRẠNG THÁI -->

                                <td>


                                    <?php if (
                                        $activity["status"]
                                        === "open"
                                    ): ?>


                                        <span
                                            class="
                                                badge
                                                badge-open
                                            "
                                        >

                                            Đang mở

                                        </span>



                                    <?php elseif (
                                        $activity["status"]
                                        === "completed"
                                    ): ?>


                                        <span
                                            class="
                                                badge
                                                badge-completed
                                            "
                                        >

                                            Hoàn thành

                                        </span>



                                    <?php else: ?>


                                        <span
                                            class="
                                                badge
                                                badge-closed
                                            "
                                        >

                                            Đã đóng

                                        </span>


                                    <?php endif; ?>


                                </td>



                                <!-- THAO TÁC -->

                                <td>


                                    <div class="actions">


                                        <!-- =================================
                                             XEM / XỬ LÝ NGƯỜI ĐĂNG KÝ
                                        ================================== -->


                                        <?php if (
                                            $activity_completed
                                        ): ?>


                                            <a
                                                href="registrations.php?activity_id=<?= (int)$activity["id"] ?>"
                                                class="btn btn-process"
                                            >

                                                👥 Xử lý tham gia

                                            </a>


                                        <?php else: ?>


                                            <a
                                                href="registrations.php?activity_id=<?= (int)$activity["id"] ?>"
                                                class="btn btn-view"
                                            >

                                                👥 Xem đăng ký

                                            </a>


                                        <?php endif; ?>



                                        <!-- =================================
                                             SỬA
                                        ================================== -->

                                        <a
                                            href="activity-edit.php?id=<?= (int)$activity["id"] ?>"
                                            class="btn btn-edit"
                                        >

                                            ✏ Sửa

                                        </a>



                                        <!-- =================================
                                             XÓA
                                        ================================== -->

                                        <form
                                            method="POST"

                                            onsubmit="
                                                return confirm(
                                                    'Bạn có chắc muốn xóa hoạt động này?'
                                                );
                                            "
                                        >


                                            <input
                                                type="hidden"
                                                name="delete_id"
                                                value="<?= (int)$activity["id"] ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-delete"
                                            >

                                                🗑 Xóa

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