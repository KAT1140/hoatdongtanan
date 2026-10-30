<?php
require_once "config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (
    isset($_SESSION["role"])
    && (
        $_SESSION["role"] === "admin"
        || $_SESSION["role"] === "super_admin"
    )
) {
    header("Location: admin/index.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT
        r.id AS registration_id,
        r.confirmation_code,
        r.status AS registration_status,
        r.registered_at,
        a.id AS activity_id,
        a.name,
        a.description,
        a.activity_date,
        a.start_time,
        a.end_time,
        a.location,
        a.status AS activity_status
    FROM registrations r
    INNER JOIN activities a
        ON r.activity_id = a.id
    WHERE r.user_id = ?
    ORDER BY a.activity_date DESC, r.registered_at DESC
");

$stmt->execute([$user_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_registered = 0;
$total_attended = 0;
$total_cancelled = 0;

foreach ($activities as $item) {
    if ($item["registration_status"] === "registered") {
        $total_registered++;
    } elseif ($item["registration_status"] === "attended") {
        $total_attended++;
    } elseif ($item["registration_status"] === "cancelled") {
        $total_cancelled++;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoạt động của tôi | Đoàn xã Tân An</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .history-main{max-width:1180px;margin:auto;padding:32px 22px 60px}
        .history-header{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;margin-bottom:25px}
        .history-header h1{margin:0;color:var(--blue-dark);font-size:28px}
        .history-header p{margin-top:7px;color:var(--muted);font-size:13px}
        .back-btn{padding:10px 14px;border-radius:10px;background:var(--blue-light);color:var(--blue);text-decoration:none;font-size:12px;font-weight:800}
        .history-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;margin-bottom:25px}
        .history-stat{padding:18px;border:1px solid var(--border);border-radius:15px;background:white}
        .history-stat span{display:block;color:var(--muted);font-size:11px}
        .history-stat strong{display:block;margin-top:6px;color:var(--blue-dark);font-size:25px}
        .history-list{display:grid;gap:17px}
        .history-card{display:grid;grid-template-columns:1fr auto;gap:20px;padding:22px;background:white;border:1px solid var(--border);border-radius:18px}
        .history-card:hover{border-color:#b9cceb;box-shadow:var(--shadow)}
        .history-title{margin:0 0 8px;color:var(--text);font-size:18px}
        .history-description{margin-bottom:15px;color:var(--muted);font-size:13px;line-height:1.6}
        .history-info{display:flex;flex-wrap:wrap;gap:14px}
        .history-info span{color:#536174;font-size:12px}
        .history-right{min-width:170px;text-align:right}
        .history-code{margin-top:10px;color:var(--muted);font-size:11px}
        .history-code strong{display:block;margin-top:4px;color:var(--text);font-size:12px;word-break:break-all}
        .history-status{display:inline-block;padding:7px 10px;border-radius:999px;font-size:10px;font-weight:800}
        .history-status.registered{background:#eaf1ff;color:#003da5}
        .history-status.attended{background:#e7f5eb;color:#176b3a}
        .history-status.cancelled{background:#fdecec;color:#a83232}
        .history-empty{padding:70px 20px;background:white;border:1px solid var(--border);border-radius:18px;text-align:center}
        .history-empty-icon{margin-bottom:15px;font-size:45px}
        .history-empty h3{margin-bottom:7px;color:var(--blue-dark)}
        .history-empty p{margin-bottom:18px;color:var(--muted);font-size:13px}
        .history-empty a{display:inline-block;padding:11px 15px;border-radius:10px;background:var(--blue);color:white;text-decoration:none;font-size:12px;font-weight:800}
        @media(max-width:700px){.history-header{align-items:flex-start;flex-direction:column}.history-stats{grid-template-columns:1fr}.history-card{grid-template-columns:1fr}.history-right{text-align:left}}
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-inner">
        <a href="index.php" class="brand">
            <div class="brand-icon">🌐</div>
            <div>
                <div class="brand-name">Đoàn xã Tân An</div>
                <div class="brand-sub">Cổng hoạt động Đoàn</div>
            </div>
        </a>

        <div class="nav-user">
            <div class="welcome">
                <strong><?= htmlspecialchars($_SESSION["full_name"] ?? "Đoàn viên") ?></strong>
                <span>Tài khoản đoàn viên</span>
            </div>

            <a href="index.php" class="nav-btn login-btn">Hoạt động</a>
            <a href="profile.php" class="nav-btn login-btn">Hồ sơ</a>
            <a href="logout.php" class="nav-btn logout-btn">Đăng xuất</a>
        </div>
    </div>
</nav>

<main class="history-main">
    <div class="history-header">
        <div>
            <h1>📋 Hoạt động của tôi</h1>
            <p>Xem các hoạt động bạn đã đăng ký và đã tham gia.</p>
        </div>
        <a href="index.php" class="back-btn">← Xem hoạt động đang mở</a>
    </div>

    <div class="history-stats">
        <div class="history-stat">
            <span>Đang đăng ký</span>
            <strong><?= $total_registered ?></strong>
        </div>

        <div class="history-stat">
            <span>Đã tham gia</span>
            <strong><?= $total_attended ?></strong>
        </div>

        <div class="history-stat">
            <span>Đã hủy</span>
            <strong><?= $total_cancelled ?></strong>
        </div>
    </div>

    <?php if (count($activities) === 0): ?>
        <div class="history-empty">
            <div class="history-empty-icon">📭</div>
            <h3>Bạn chưa có hoạt động nào</h3>
            <p>Hãy chọn một hoạt động đang mở và đăng ký tham gia.</p>
            <a href="index.php">Xem hoạt động →</a>
        </div>
    <?php else: ?>
        <div class="history-list">
            <?php foreach ($activities as $item): ?>
                <div class="history-card">
                    <div>
                        <h3 class="history-title"><?= htmlspecialchars($item["name"]) ?></h3>

                        <div class="history-description">
                            <?= htmlspecialchars(
                                !empty($item["description"])
                                ? $item["description"]
                                : "Hoạt động Đoàn xã Tân An."
                            ) ?>
                        </div>

                        <div class="history-info">
                            <span>
                                📅
                                <?= !empty($item["activity_date"])
                                    ? date("d/m/Y", strtotime($item["activity_date"]))
                                    : "Chưa cập nhật"
                                ?>
                            </span>

                            <?php if (!empty($item["start_time"])): ?>
                                <span>
                                    🕐 <?= date("H:i", strtotime($item["start_time"])) ?>
                                    <?php if (!empty($item["end_time"])): ?>
                                        - <?= date("H:i", strtotime($item["end_time"])) ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>

                            <span>
                                📍
                                <?= htmlspecialchars(
                                    !empty($item["location"])
                                    ? $item["location"]
                                    : "Chưa cập nhật"
                                ) ?>
                            </span>
                        </div>
                    </div>

                    <div class="history-right">
                        <?php if ($item["registration_status"] === "registered"): ?>
                            <span class="history-status registered">Đã đăng ký</span>
                        <?php elseif ($item["registration_status"] === "attended"): ?>
                            <span class="history-status attended">✓ Đã tham gia</span>
                        <?php else: ?>
                            <span class="history-status cancelled">Đã hủy</span>
                        <?php endif; ?>

                        <div class="history-code">
                            Mã đăng ký
                            <strong>
                                <?= htmlspecialchars(
                                    !empty($item["confirmation_code"])
                                    ? $item["confirmation_code"]
                                    : "-"
                                ) ?>
                            </strong>
                        </div>

                        <div class="history-code">
                            Đăng ký lúc
                            <strong>
                                <?= !empty($item["registered_at"])
                                    ? date("d/m/Y H:i", strtotime($item["registered_at"]))
                                    : "-"
                                ?>
                            </strong>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
