<?php

require_once "config/database.php";


/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
   THÔNG BÁO
========================================================= */

$message = "";
$message_type = "";


/* =========================================================
   XỬ LÝ ĐĂNG KÝ HOẠT ĐỘNG
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    &&
    isset($_POST["register_activity_id"])
) {

    /* =====================================================
       CHƯA ĐĂNG NHẬP
    ====================================================== */

    if (!isset($_SESSION["user_id"])) {

        header("Location: login.php");
        exit;
    }


    $user_id =
        (int) $_SESSION["user_id"];

    $activity_id =
        (int) $_POST["register_activity_id"];


    /* =====================================================
       ID HOẠT ĐỘNG KHÔNG HỢP LỆ
    ====================================================== */

    if ($activity_id <= 0) {

        $message =
            "Hoạt động không hợp lệ.";

        $message_type =
            "error";

    } else {


        try {


            /* =================================================
               KIỂM TRA HOẠT ĐỘNG
            ================================================= */

            $stmt = $pdo->prepare("
                SELECT
                    id,
                    name,
                    status
                FROM activities
                WHERE id = ?
                LIMIT 1
            ");


            $stmt->execute([
                $activity_id
            ]);


            $activity =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$activity) {


                $message =
                    "Không tìm thấy hoạt động.";

                $message_type =
                    "error";


            } elseif (
                $activity["status"] !== "open"
            ) {


                $message =
                    "Hoạt động này đã đóng đăng ký.";

                $message_type =
                    "warning";


            } else {


                /* =================================================
                   KIỂM TRA USER ĐÃ CÓ ĐĂNG KÝ CHƯA
                ================================================= */

                $stmt = $pdo->prepare("
                    SELECT
                        id,
                        status,
                        confirmation_code
                    FROM registrations
                    WHERE user_id = ?
                    AND activity_id = ?
                    LIMIT 1
                ");


                $stmt->execute([
                    $user_id,
                    $activity_id
                ]);


                $registration =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                /* =================================================
                   ĐÃ ĐĂNG KÝ
                ================================================= */

                if (
                    $registration
                    &&
                    $registration["status"]
                    === "registered"
                ) {


                    $message =
                        "Bạn đã đăng ký hoạt động này rồi.";

                    $message_type =
                        "warning";


                /* =================================================
                   ĐÃ THAM GIA
                ================================================= */

                } elseif (
                    $registration
                    &&
                    $registration["status"]
                    === "attended"
                ) {


                    $message =
                        "Bạn đã tham gia hoạt động này.";

                    $message_type =
                        "warning";


                /* =================================================
                   ĐÃ CÓ BẢN GHI NHƯNG ĐÃ HỦY
                   → ĐĂNG KÝ LẠI
                ================================================= */

                } elseif ($registration) {


                    /*
                     * Nếu bản ghi cũ chưa có confirmation_code
                     * thì tạo mã mới.
                     */

                    $confirmation_code =
                        $registration["confirmation_code"]
                        ?? "";


                    if ($confirmation_code === "") {

                        $confirmation_code =
                            "DK"
                            . date("YmdHis")
                            . strtoupper(
                                bin2hex(
                                    random_bytes(3)
                                )
                            );
                    }


                    $stmt = $pdo->prepare("
                        UPDATE registrations
                        SET
                            status = 'registered',
                            confirmation_code = ?,
                            registered_at = CURRENT_TIMESTAMP
                        WHERE id = ?
                    ");


                    $stmt->execute([
                        $confirmation_code,
                        (int) $registration["id"]
                    ]);


                    header(
                        "Location: index.php?registered=1"
                    );

                    exit;


                /* =================================================
                   CHƯA TỪNG ĐĂNG KÝ
                   → INSERT
                ================================================= */

                } else {


                    /* =============================================
                       TẠO MÃ XÁC NHẬN DUY NHẤT
                    ============================================== */

                    $confirmation_code =
                        "DK"
                        . date("YmdHis")
                        . strtoupper(
                            bin2hex(
                                random_bytes(3)
                            )
                        );


                    $stmt = $pdo->prepare("
                        INSERT INTO registrations
                        (
                            user_id,
                            activity_id,
                            confirmation_code,
                            status
                        )
                        VALUES
                        (
                            ?,
                            ?,
                            ?,
                            'registered'
                        )
                    ");


                    $stmt->execute([
                        $user_id,
                        $activity_id,
                        $confirmation_code
                    ]);


                    header(
                        "Location: index.php?registered=1"
                    );

                    exit;
                }
            }


        } catch (PDOException $e) {


            /*
             * Trong lúc phát triển, hiện lỗi thật
             * để dễ kiểm tra.
             */

            $message =
                "Lỗi đăng ký: "
                . $e->getMessage();

            $message_type =
                "error";
        }
    }
}


/* =========================================================
   THÔNG BÁO ĐĂNG KÝ THÀNH CÔNG
========================================================= */

if (isset($_GET["registered"])) {

    $message =
        "Đăng ký hoạt động thành công! 🎉";

    $message_type =
        "success";
}


/* =========================================================
   LẤY DANH SÁCH HOẠT ĐỘNG ĐANG MỞ
========================================================= */

try {


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

        WHERE a.status = 'open'

        GROUP BY a.id

        ORDER BY a.activity_date ASC
    ");


    $activities =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (PDOException $e) {


    $activities = [];


    $message =
        "Không thể tải danh sách hoạt động.";

    $message_type =
        "error";
}


/* =========================================================
   LẤY HOẠT ĐỘNG USER ĐÃ ĐĂNG KÝ
========================================================= */

$user_registrations = [];


if (isset($_SESSION["user_id"])) {


    try {


        $stmt = $pdo->prepare("
            SELECT activity_id
            FROM registrations
            WHERE user_id = ?
            AND status = 'registered'
        ");


        $stmt->execute([
            (int) $_SESSION["user_id"]
        ]);


        $user_registrations =
            array_map(
                "intval",
                $stmt->fetchAll(
                    PDO::FETCH_COLUMN
                )
            );


    } catch (PDOException $e) {


        $user_registrations = [];
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

    <meta
        name="description"
        content="Cổng đăng ký hoạt động trực tuyến của Đoàn xã Tân An"
    >

    <title>
        Hoạt động Đoàn | Đoàn xã Tân An
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        :root {
            --hero-blue: #0b4eb5;
            --hero-blue-dark: #073d91;
            --hero-blue-light: #2865c3;
            --hero-soft: #eaf2ff;
        }


        /* =============================================
           NAVBAR
        ============================================= */

        .navbar {
            width: 100%;
            background: #ffffff;
        }

        .nav-inner {
            width: 100%;
            max-width: 1100px;
            min-height: 64px;

            margin: 0 auto;
            padding: 0 16px;

            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;

            flex-direction: row !important;
            flex-wrap: nowrap !important;
        }


        /* =============================================
           LOGO + TÊN
        ============================================= */

        .brand {
            display: flex !important;
            align-items: center !important;
            flex-direction: row !important;

            gap: 10px;

            text-decoration: none;

            flex-shrink: 0;
        }

        .brand-logo {
            width: 42px !important;
            height: 42px !important;

            min-width: 42px !important;
            min-height: 42px !important;

            max-width: 42px !important;
            max-height: 42px !important;

            object-fit: contain !important;

            display: block !important;

            margin: 0 !important;
            padding: 0 !important;

            flex: 0 0 42px !important;
        }

        .brand-name {
            color: var(--hero-blue-dark);

            font-size: 15px;
            font-weight: 800;

            line-height: 1.2;
        }

        .brand-sub {
            margin-top: 2px;

            color: #718096;

            font-size: 10px;
        }


        /* =============================================
           PHẦN USER BÊN PHẢI
        ============================================= */

        .nav-user {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-end !important;

            flex-direction: row !important;
            flex-wrap: nowrap !important;

            gap: 8px;

            margin-left: auto;
        }

        .welcome {
            display: flex;
            flex-direction: column;

            text-align: right;

            white-space: nowrap;
        }

        .welcome strong {
            font-size: 12px;
        }

        .welcome span {
            margin-top: 2px;

            color: #718096;

            font-size: 9px;
        }


        /* =============================================
           BUTTON NAVBAR
        ============================================= */

        .nav-btn {
            display: inline-flex !important;

            align-items: center;
            justify-content: center;

            white-space: nowrap;
        }


        /* =============================================
           MÀU XANH ĐỒNG BỘ HERO
        ============================================= */

        .login-btn {
            background: var(--hero-blue);
            color: white;
            border-color: var(--hero-blue);
        }

        .login-btn:hover {
            background: var(--hero-blue-dark);
            color: white;
        }

        .logout-btn {
            background: var(--hero-soft);
            color: var(--hero-blue-dark);
        }

        .logout-btn:hover {
            background: #dce9ff;
        }


        /* =============================================
           HERO
        ============================================= */

        .hero-box {
            background:
                linear-gradient(
                    120deg,
                    #073d91,
                    #0b4eb5 55%,
                    #2865c3
                );
        }


        /* =============================================
           MAIN
        ============================================= */

        .section-title h2 {
            color: var(--hero-blue-dark);
        }

        .search input:focus {
            border-color: var(--hero-blue);

            box-shadow:
                0 0 0 3px
                rgba(11, 78, 181, 0.10);
        }


        /* =============================================
           ACTIVITY
        ============================================= */

        .activity-card {
            border-color: #d9e5f7;
        }

        .activity-icon {
            background: var(--hero-soft);
        }

        .status {
            background: var(--hero-soft);
            color: var(--hero-blue-dark);
        }

        .info-icon {
            background: var(--hero-soft);
        }


        /* =============================================
           BUTTON ĐĂNG KÝ
        ============================================= */

        .register-btn {
            background: var(--hero-blue);

            color: white;

            border: none;
        }

        .register-btn:hover {
            background: var(--hero-blue-dark);
        }

        .registered-btn,
        .register-btn:disabled {
            background: var(--hero-soft);

            color: var(--hero-blue-dark);
        }

        .login-register-btn {
            background: var(--hero-blue);

            color: white;
        }

        .login-register-btn:hover {
            background: var(--hero-blue-dark);
        }


        /* =============================================
           ALERT
        ============================================= */

        .alert-success {
            background: var(--hero-soft);

            color: var(--hero-blue-dark);

            border-color: #bdd3f7;
        }


        footer {
            border-top-color: #d9e5f7;
        }


        /* =============================================
           MOBILE
        ============================================= */

        @media (max-width: 700px) {

            .nav-inner {
                padding: 8px 12px;
            }

            .welcome {
                display: none;
            }

            .brand-logo {
                width: 38px !important;
                height: 38px !important;

                min-width: 38px !important;
                min-height: 38px !important;

                max-width: 38px !important;
                max-height: 38px !important;

                flex-basis: 38px !important;
            }

            .nav-user {
                gap: 5px;
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


        <!-- LOGO -->

        <a
            href="index.php"
            class="brand"
        >

            <img
                src="images/logodoanthanhnien.jpg"
                class="brand-logo"
                alt="Logo Đoàn Thanh niên"
            >
                            <img
                    src="images/logothanhnien.jpg"
                    alt="Logo Đoàn Thanh niên"
            >


            <div>

                <div class="brand-name">
                    Đoàn xã Tân An
                </div>

                <div class="brand-sub">
                    Cổng hoạt động Đoàn
                </div>

            </div>

        </a>


        <!-- USER -->

        <div class="nav-user">


            <?php if (isset($_SESSION["user_id"])): ?>


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
                    href="profile.php"
                    class="nav-btn login-btn"
                >
                    Hồ sơ
                </a>


                <a
                    href="logout.php"
                    class="nav-btn logout-btn"
                >
                    Đăng xuất
                </a>


            <?php else: ?>


                <a
                    href="login.php"
                    class="nav-btn login-btn"
                >
                    Đăng nhập
                </a>


            <?php endif; ?>


        </div>


    </div>

</nav>



<!-- =========================================================
     HERO
========================================================= -->

<section class="hero">


    <div class="hero-box">


        <div class="hero-content">


            <span class="hero-tag">

                🔵 HOẠT ĐỘNG ĐOÀN XÃ TÂN AN

            </span>


            <h1>

                Cùng tham gia,
                <br>

                cùng tạo giá trị.

            </h1>


            <p>

                Theo dõi các hoạt động mới nhất
                của Đoàn xã Tân An và đăng ký
                tham gia trực tuyến một cách
                nhanh chóng, thuận tiện.

            </p>


        </div>


    </div>


</section>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- =====================================================
         THÔNG BÁO
    ====================================================== -->

    <?php if ($message !== ""): ?>


        <div
            class="alert alert-<?= htmlspecialchars(
                $message_type
            ) ?>"
        >

            <?= htmlspecialchars(
                $message
            ) ?>

        </div>


    <?php endif; ?>



    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="toolbar">


        <div class="section-title">


            <h2>
                Hoạt động đang mở
            </h2>


            <p>
                Những hoạt động hiện đang nhận đăng ký
            </p>


        </div>


        <div class="search">


            <span class="search-icon">
                🔎
            </span>


            <input
                type="text"
                id="searchInput"
                placeholder="Tìm hoạt động..."
                autocomplete="off"
            >


        </div>


    </div>



    <!-- =====================================================
         DANH SÁCH HOẠT ĐỘNG
    ====================================================== -->

    <div
        class="activities"
        id="activityList"
    >


        <?php if (
            count($activities) === 0
        ): ?>


            <div class="empty">


                <div class="empty-icon">
                    📋
                </div>


                <h3>
                    Hiện chưa có hoạt động nào
                </h3>


                <p>
                    Các hoạt động mới sẽ được cập nhật tại đây.
                </p>


            </div>


        <?php else: ?>


            <?php foreach (
                $activities
                as $activity
            ): ?>


                <?php


                $activity_id =
                    (int) $activity["id"];


                $is_registered =
                    in_array(
                        $activity_id,
                        $user_registrations,
                        true
                    );


                ?>


                <article
                    class="activity-card"

                    data-name="<?= htmlspecialchars(
                        strtolower(
                            $activity["name"]
                        )
                    ) ?>"
                >


                    <div class="activity-top">


                        <div class="activity-icon">
                            🌱
                        </div>


                        <span class="status">
                            ĐANG MỞ
                        </span>


                    </div>



                    <h3 class="activity-title">


                        <?= htmlspecialchars(
                            $activity["name"]
                        ) ?>


                    </h3>



                    <p class="activity-description">


                        <?= htmlspecialchars(
                            !empty(
                                $activity["description"]
                            )
                            ? $activity["description"]
                            : "Hoạt động Đoàn xã Tân An."
                        ) ?>


                    </p>



                    <div class="info-list">


                        <!-- NGÀY -->

                        <div class="info">


                            <span class="info-icon">
                                📅
                            </span>


                            <span>


                                <?= !empty(
                                    $activity["activity_date"]
                                )

                                    ? date(
                                        "d/m/Y",
                                        strtotime(
                                            $activity["activity_date"]
                                        )
                                    )

                                    : "Chưa cập nhật"
                                ?>


                            </span>


                        </div>



                        <!-- ĐỊA ĐIỂM -->

                        <div class="info">


                            <span class="info-icon">
                                📍
                            </span>


                            <span>


                                <?= htmlspecialchars(
                                    !empty(
                                        $activity["location"]
                                    )
                                    ? $activity["location"]
                                    : "Chưa cập nhật"
                                ) ?>


                            </span>


                        </div>


                    </div>



                    <!-- =========================================
                         FOOTER CARD
                    ========================================== -->

                    <div class="activity-footer">


                        <span class="registered">


                            👥


                            <?= (int)
                                $activity["registered_count"]
                            ?>


                            người đã đăng ký


                        </span>



                        <!-- CHƯA LOGIN -->

                        <?php if (
                            !isset($_SESSION["user_id"])
                        ): ?>


                            <a
                                href="login.php"
                                class="login-register-btn"
                            >

                                Đăng nhập để đăng ký

                            </a>



                        <!-- ĐÃ ĐĂNG KÝ -->

                        <?php elseif (
                            $is_registered
                        ): ?>


                            <button
                                type="button"
                                class="register-btn registered-btn"
                                disabled
                            >

                                ✓ Đã đăng ký

                            </button>



                        <!-- CHƯA ĐĂNG KÝ -->

                        <?php else: ?>


                            <form
                                method="POST"
                                action="index.php"
                            >


                                <input
                                    type="hidden"
                                    name="register_activity_id"
                                    value="<?= $activity_id ?>"
                                >


                                <button
                                    type="submit"
                                    class="register-btn"

                                    onclick="
                                        return confirm(
                                            'Bạn có chắc muốn đăng ký hoạt động này?'
                                        );
                                    "
                                >

                                    Đăng ký →

                                </button>


                            </form>


                        <?php endif; ?>


                    </div>


                </article>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer>


    © <?= date("Y") ?>

    Đoàn xã Tân An

    ·

    Hệ thống đăng ký hoạt động trực tuyến


</footer>



<!-- =========================================================
     SEARCH
========================================================= -->

<script>


document.addEventListener(
    "DOMContentLoaded",

    function () {


        const searchInput =
            document.getElementById(
                "searchInput"
            );


        const cards =
            document.querySelectorAll(
                ".activity-card"
            );


        if (!searchInput) {

            return;

        }


        searchInput.addEventListener(
            "input",

            function () {


                const keyword =
                    this
                        .value
                        .toLowerCase()
                        .trim();


                cards.forEach(
                    function (card) {


                        const name =
                            card.dataset.name
                            || "";


                        if (
                            name.includes(
                                keyword
                            )
                        ) {


                            card.style.display =
                                "";


                        } else {


                            card.style.display =
                                "none";

                        }

                    }
                );

            }
        );

    }
);


</script>


</body>


</html>