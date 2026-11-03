<?php

require_once "../config/database.php";
require_once "../includes/auth.php";

requireLogin();

if (
    !isset($_SESSION["role_id"]) ||
    !in_array((int)$_SESSION["role_id"], [3, 4], true)
) {
    http_response_code(403);
    die("Bạn không có quyền truy cập.");
}

$activity_id = isset($_GET["activity_id"])
    ? (int)$_GET["activity_id"]
    : 0;

if ($activity_id <= 0) {
    http_response_code(400);
    die("ID hoạt động không hợp lệ.");
}

$stmt = $pdo->prepare("
    SELECT id, name, description, activity_date, start_time, end_time,
           location, status
    FROM activities
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$activity_id]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) {
    http_response_code(404);
    die("Không tìm thấy hoạt động.");
}

$stmt = $pdo->prepare("
    SELECT
        r.status,
        r.registered_at,
        u.full_name,
        u.phone,
        u.email,
        u.unit
    FROM registrations r
    INNER JOIN users u ON r.user_id = u.id
    WHERE r.activity_id = ?
    ORDER BY r.registered_at ASC
");
$stmt->execute([$activity_id]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$escape = static function ($value): string {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
};

$formatDate = static function ($value, string $format): string {
    $timestamp = strtotime((string)($value ?? ""));
    return $timestamp === false ? "-" : date($format, $timestamp);
};

$registrationStatus = [
    "registered" => "Đã đăng ký",
    "attended" => "Đã tham gia",
    "cancelled" => "Đã hủy",
];

$activityStatus = [
    "open" => "Đang mở",
    "closed" => "Đã đóng",
    "completed" => "Hoàn thành",
];

$title = "Danh sách đăng ký - " . $activity["name"];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $escape($title) ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px;
            color: #17231b;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }

        .document {
            max-width: 1100px;
            margin: 0 auto;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .print-button {
            border: 0;
            border-radius: 6px;
            padding: 11px 16px;
            color: #fff;
            background: #176b3a;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        h1 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 22px;
        }

        .subtitle {
            margin: 0 0 22px;
            color: #4b5c51;
            text-align: center;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px 24px;
            margin-bottom: 18px;
            padding: 14px;
            border: 1px solid #d5ded7;
            border-radius: 6px;
        }

        .detail {
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .description {
            grid-column: 1 / -1;
            white-space: pre-wrap;
        }

        .summary {
            margin: 0 0 10px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #89978d;
            padding: 7px 6px;
            text-align: left;
            vertical-align: top;
            overflow-wrap: anywhere;
        }

        th {
            background: #edf3ee;
        }

        .number {
            width: 38px;
            text-align: center;
        }

        .phone,
        .status,
        .registered-at {
            white-space: nowrap;
        }

        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            body {
                padding: 0;
                font-size: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .document {
                max-width: none;
            }

            .toolbar {
                display: none;
            }

            h1 {
                font-size: 18px;
            }

            .details {
                padding: 8px;
                margin-bottom: 10px;
            }

            th,
            td {
                padding: 5px 4px;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <main class="document">
        <div class="toolbar">
            <button class="print-button" type="button" onclick="window.print()">
                In / Lưu thành PDF
            </button>
        </div>

        <h1>DANH SÁCH ĐĂNG KÝ HOẠT ĐỘNG</h1>
        <p class="subtitle">Đoàn xã Tân An</p>

        <section class="details">
            <div class="detail">
                <strong>Hoạt động:</strong>
                <?= $escape($activity["name"]) ?>
            </div>
            <div class="detail">
                <strong>Ngày hoạt động:</strong>
                <?= $escape($formatDate($activity["activity_date"], "d/m/Y")) ?>
            </div>
            <div class="detail">
                <strong>Thời gian:</strong>
                <?= $escape(trim(($activity["start_time"] ?? "") . " - " . ($activity["end_time"] ?? ""), " -")) ?>
            </div>
            <div class="detail">
                <strong>Địa điểm:</strong>
                <?= $escape($activity["location"] ?: "-") ?>
            </div>
            <div class="detail">
                <strong>Trạng thái:</strong>
                <?= $escape($activityStatus[$activity["status"]] ?? "Không xác định") ?>
            </div>
            <?php if (!empty($activity["description"])): ?>
                <div class="detail description">
                    <strong>Nội dung:</strong>
                    <?= $escape($activity["description"]) ?>
                </div>
            <?php endif; ?>
        </section>

        <p class="summary">Tổng số đăng ký: <?= count($registrations) ?></p>

        <table>
            <thead>
                <tr>
                    <th class="number">STT</th>
                    <th>Họ và tên</th>
                    <th class="phone">Số điện thoại</th>
                    <th>Email</th>
                    <th>Đơn vị</th>
                    <th class="status">Trạng thái</th>
                    <th class="registered-at">Ngày đăng ký</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registrations === []): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Chưa có lượt đăng ký.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $index => $registration): ?>
                        <tr>
                            <td class="number"><?= $index + 1 ?></td>
                            <td><?= $escape($registration["full_name"]) ?></td>
                            <td class="phone"><?= $escape($registration["phone"] ?: "-") ?></td>
                            <td><?= $escape($registration["email"] ?: "-") ?></td>
                            <td><?= $escape($registration["unit"] ?: "-") ?></td>
                            <td class="status">
                                <?= $escape($registrationStatus[$registration["status"]] ?? "Không xác định") ?>
                            </td>
                            <td class="registered-at">
                                <?= $escape($formatDate($registration["registered_at"], "d/m/Y H:i")) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
