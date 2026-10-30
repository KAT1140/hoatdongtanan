<?php

$currentPage =
    basename($_SERVER["PHP_SELF"]);

?>


<aside class="sidebar">


    <div
        class="brand"
        style="
            display: block !important;
            height: auto !important;
            min-height: 0 !important;
        "
    >


        <!-- =================================================
             LOGO
        ================================================== -->

        <div
            style="
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                margin-bottom: 12px !important;
                height: 50px !important;
            "
        >


            <img
                src="../images/logodoanthanhnien.jpg"
                alt="Logo Đoàn Thanh niên"

                style="
                    width: 48px !important;
                    height: 48px !important;

                    min-width: 48px !important;
                    min-height: 48px !important;

                    max-width: 48px !important;
                    max-height: 48px !important;

                    object-fit: contain !important;

                    display: block !important;

                    margin: 0 !important;
                    padding: 0 !important;

                    flex: 0 0 48px !important;
                "
            >


            <img
                src="../images/logothanhnien.jpg"
                alt="Logo Thanh niên"

                style="
                    width: 48px !important;
                    height: 48px !important;

                    min-width: 48px !important;
                    min-height: 48px !important;

                    max-width: 48px !important;
                    max-height: 48px !important;

                    object-fit: contain !important;

                    display: block !important;

                    margin: 0 !important;
                    padding: 0 !important;

                    flex: 0 0 48px !important;
                "
            >


        </div>


        <!-- =================================================
             TÊN
        ================================================== -->

        <h2>
            Đoàn xã Tân An
        </h2>


        <p>
            HỆ THỐNG QUẢN TRỊ
        </p>


    </div>


    <!-- =====================================================
         MENU
    ====================================================== -->

    <div class="menu">


        <div class="menu-title">
            Tổng quan
        </div>


        <!-- DASHBOARD -->

        <a
            href="index.php"
            class="<?=
                $currentPage === "index.php"
                ? "active"
                : ""
            ?>"
        >
            📊 Dashboard
        </a>


        <!-- HOẠT ĐỘNG -->

        <a
            href="activities.php"
            class="<?=
                in_array(
                    $currentPage,
                    [
                        "activities.php",
                        "activity-add.php",
                        "activity-edit.php",
                        "registrations.php"
                    ]
                )
                ? "active"
                : ""
            ?>"
        >
            📅 Hoạt động
        </a>


        <!-- TÀI KHOẢN -->

        <?php if (
            hasRole("admin") ||
            hasRole("super_admin")
        ): ?>


            <a
                href="members.php"
                class="<?=
                    $currentPage === "members.php"
                    ? "active"
                    : ""
                ?>"
            >
                👥 Tài khoản
            </a>


        <?php endif; ?>


        <!-- ĐĂNG XUẤT -->

        <a href="../logout.php">

            🚪 Đăng xuất

        </a>


    </div>


</aside>