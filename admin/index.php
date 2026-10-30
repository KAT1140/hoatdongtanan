    <?php

    require_once "../config/database.php";
    require_once "../includes/auth.php";

    requireLogin();


    if (
        !isset($_SESSION["role_id"]) ||
        !in_array((int)$_SESSION["role_id"], [3, 4], true)
    ) {
        http_response_code(403);
        die("Bạn không có quyền truy cập khu vực quản trị.");
    }


    /* =========================
    THỐNG KÊ
    ========================= */

    $total_members = $pdo->query("
        SELECT COUNT(*)
        FROM users u
        INNER JOIN roles r
            ON u.role_id = r.id
        WHERE r.name = 'member'
        AND u.status = 'active'
    ")->fetchColumn();


    $total_activities = $pdo->query("
        SELECT COUNT(*)
        FROM activities
    ")->fetchColumn();


    $total_registrations = $pdo->query("
        SELECT COUNT(*)
        FROM registrations
        WHERE status = 'registered'
    ")->fetchColumn();


    $open_activities = $pdo->query("
        SELECT COUNT(*)
        FROM activities
        WHERE status = 'open'
    ")->fetchColumn();


    /* =========================
    HOẠT ĐỘNG GẦN NHẤT
    ========================= */

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

        ORDER BY a.created_at DESC

        LIMIT 5
    ");

    $activities = $stmt->fetchAll();

    ?>

    <!DOCTYPE html>

    <html lang="vi">

    <head>

        <meta charset="UTF-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1.0"
        >

        <link rel="stylesheet" href="../assets/css/style.css">

        <style>

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                background: #f5f7f5;

                color: #18241c;
            }

            .dashboard-layout {
                min-height: 100vh;

                display: flex;
            }


            /* SIDEBAR */

            .sidebar {
                width: 250px;

                background: #123b25;

                color: white;

                padding: 25px 18px;

                position: fixed;

                top: 0;
                bottom: 0;
                left: 0;
            }

            .brand {
                padding: 10px;

                margin-bottom: 30px;
            }

            .brand-icon {
                font-size: 28px;
            }

            .brand h2 {
                margin-top: 8px;

                font-size: 19px;
            }

            .brand p {
                margin-top: 5px;

                font-size: 12px;

                color: #e7f0ea;

                opacity: 1;
            }


            .menu-title {
                margin: 20px 10px 8px;

                font-size: 11px;

                text-transform: uppercase;

                color: #e7f0ea;

                opacity: 1;
            }

            .menu a {
                display: block;

                padding: 12px 13px;

                margin-bottom: 5px;

                border-radius: 10px;

                text-decoration: none;

                color: #dce9df;

                font-size: 14px;
            }

            .menu a:hover,
            .menu a.active {
                background: rgba(255,255,255,.12);

                color: white;
            }


            /* MAIN */

            .dashboard-main {
                margin-left: 250px;

                width: calc(100% - 250px);
                max-width: none;
                flex: 1;

                padding: 30px 40px;
            }


            .topbar {
                display: flex;

                justify-content: space-between;

                align-items: center;

                margin-bottom: 30px;
            }

            .topbar h1 {
                font-size: 28px;
            }

            .topbar p {
                margin-top: 5px;

                color: #536157;

                font-size: 14px;
            }

            .dashboard-user {
                background: white;

                padding: 10px 15px;

                border-radius: 12px;

                border: 1px solid #e3e9e4;
            }

            .dashboard-user strong {
                display: block;

                font-size: 14px;
            }

            .dashboard-user span {
                color: #536157;

                font-size: 12px;
            }


            /* STAT */

            .stats {
                display: grid;

                grid-template-columns:
                    repeat(4, 1fr);

                gap: 18px;
            }

            .stat {
                background: white;

                border: 1px solid #e4e9e5;

                border-radius: 16px;

                padding: 22px;
            }

            .stat-icon {
                font-size: 25px;
            }

            .stat-number {
                margin-top: 15px;

                font-size: 30px;

                font-weight: 800;
            }

            .stat-label {
                margin-top: 5px;

                color: #536157;

                font-size: 13px;
            }


            /* CONTENT */

            .content-grid {
                margin-top: 25px;

                display: grid;

                grid-template-columns:
                    2fr 1fr;

                gap: 20px;
            }

            .panel {
                background: white;

                border: 1px solid #e4e9e5;

                border-radius: 18px;

                padding: 22px;
            }

            .panel-header {
                display: flex;

                align-items: center;

                justify-content: space-between;

                margin-bottom: 20px;
            }

            .panel-header h2 {
                font-size: 18px;
            }

            .panel-header a {
                color: #176b3a;

                text-decoration: none;

                font-size: 13px;

                font-weight: 700;
            }


            /* ACTIVITY */

            .activity {
                padding: 14px 0;

                border-bottom: 1px solid #edf0ed;

                display: flex;

                justify-content: space-between;

                gap: 15px;
            }

            .activity:last-child {
                border-bottom: none;
            }

            .activity h3 {
                font-size: 15px;
            }

            .activity p {
                margin-top: 6px;

                color: #536157;

                font-size: 13px;
            }

            .badge {
                height: fit-content;

                padding: 6px 9px;

                border-radius: 999px;

                font-size: 11px;

                background: #e7f5eb;

                color: #176b3a;
            }


            /* QUICK */

            .quick {
                display: flex;

                flex-direction: column;

                gap: 10px;
            }

            .quick a {
                display: block;

                padding: 14px;

                background: #f5f8f5;

                border-radius: 11px;

                color: #25352b;

                text-decoration: none;

                font-size: 14px;

                font-weight: 600;
            }

            .quick a:hover {
                background: #e9f2eb;
            }


            /* MOBILE */

            @media(max-width: 900px) {

                .sidebar {
                    width: 210px;
                }

                .dashboard-main {
                    margin-left: 210px;

                    width: calc(100% - 210px);

                    padding: 25px;
                }

                .stats {
                    grid-template-columns:
                        repeat(2, 1fr);
                }

                .content-grid {
                    grid-template-columns: 1fr;
                }
            }


            @media(max-width: 650px) {

                .sidebar {
                    display: none;
                }

                .dashboard-main {
                    margin-left: 0;

                    width: 100%;

                    padding: 20px;
                }

                .stats {
                    grid-template-columns: 1fr;
                }

                .topbar {
                    align-items: flex-start;

                    gap: 15px;

                    flex-direction: column;
                }
            }

        </style>

    </head>


    <body>

    <div class="dashboard-layout">


        <!-- SIDEBAR -->

    <?php require_once "includes/admin-sidebar.php"; ?>


        <!-- MAIN -->

        <main class="dashboard-main">


            <div class="topbar">

                <div>

                    <h1>
                        Tổng quan
                    </h1>

                    <p>
                        Chào mừng bạn đến với hệ thống quản trị.
                    </p>

                </div>


                <div class="dashboard-user">

                    <strong>
                        <?= htmlspecialchars($_SESSION["full_name"]) ?>
                    </strong>

                    <span>
                        <?= htmlspecialchars($_SESSION["role"]) ?>
                    </span>

                </div>

            </div>


            <!-- STATS -->

            <section class="stats">


                <div class="stat">

                    <div class="stat-icon">
                        👥
                    </div>

                    <div class="stat-number">
                        <?= $total_members ?>
                    </div>

                    <div class="stat-label">
                        Đoàn viên đang hoạt động
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-icon">
                        📅
                    </div>

                    <div class="stat-number">
                        <?= $total_activities ?>
                    </div>

                    <div class="stat-label">
                        Tổng hoạt động
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-icon">
                        📝
                    </div>

                    <div class="stat-number">
                        <?= $total_registrations ?>
                    </div>

                    <div class="stat-label">
                        Lượt đăng ký
                    </div>

                </div>


                <div class="stat">

                    <div class="stat-icon">
                        🟢
                    </div>

                    <div class="stat-number">
                        <?= $open_activities ?>
                    </div>

                    <div class="stat-label">
                        Hoạt động đang mở
                    </div>

                </div>

            </section>


            <!-- CONTENT -->

            <section class="content-grid">


                <div class="panel">

                    <div class="panel-header">

                        <h2>
                            Hoạt động mới nhất
                        </h2>

                        <?php if (hasPermission("activity_view")): ?>

                            <a href="activities.php">
                                Xem tất cả →
                            </a>

                        <?php endif; ?>

                    </div>


                    <?php if (count($activities) > 0): ?>

                        <?php foreach ($activities as $activity): ?>

                            <div class="activity">

                                <div>

                                    <h3>
                                        <?= htmlspecialchars($activity["name"]) ?>
                                    </h3>

                                    <p>

                                        📅
                                        <?= date(
                                            "d/m/Y",
                                            strtotime($activity["activity_date"])
                                        ) ?>

                                        &nbsp; • &nbsp;

                                        📍
                                        <?= htmlspecialchars($activity["location"]) ?>

                                    </p>

                                </div>


                                <span class="badge">

                                    <?= (int)$activity["registered_count"] ?>

                                    người đăng ký

                                </span>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <p style="color:#536157;">
                            Chưa có hoạt động nào.
                        </p>

                    <?php endif; ?>

                </div>


                <div class="panel">

                    <div class="panel-header">

                        <h2>
                            Thao tác nhanh
                        </h2>

                    </div>


                    <div class="quick">


                        <?php if (hasPermission("activity_create")): ?>

                            <a href="activity-add.php">
                                ➕ Thêm hoạt động
                            </a>

                        <?php endif; ?>


                        <?php if (hasPermission("member_view")): ?>

                            <a href="members.php">
                                👥 Quản lý đoàn viên
                            </a>

                        <?php endif; ?>


                        <?php if (hasPermission("admin_create")): ?>

                            <a href="admins.php">
                                🛡️ Thêm tài khoản quản trị
                            </a>

                        <?php endif; ?>


                    </div>

                </div>


            </section>

        </main>

    </div>

    </body>

    </html>