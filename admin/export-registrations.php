
    
  

header("Pragma: no-cache");
header("Expires: 0");


/* =========================================================
   MỞ OUTPUT
========================================================= */

$output = fopen("php://output", "w");


/* BOM UTF-8
   Giúp Excel hiển thị tiếng Việt đúng */
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));


/* =========================================================
   THÔNG TIN HOẠT ĐỘNG
========================================================= */

fputcsv($output, [
    "DANH SÁCH THÀNH VIÊN THAM GIA HOẠT ĐỘNG"
]);

fputcsv($output, [
    "Hoạt động",
    $activity["name"]
]);

fputcsv($output, [
    "Ngày",
    $activity["activity_date"]
]);

fputcsv($output, [
    "Thời gian",
    trim(
        ($activity["start_time"] ?? "")
        . " - "
        . ($activity["end_time"] ?? "")
    )
]);

fputcsv($output, [
    "Địa điểm",
    $activity["location"]
]);

fputcsv($output, []);


/* =========================================================
   TIÊU ĐỀ BẢNG
========================================================= */

fputcsv($output, [
    "STT",
    "Họ và tên",
    "Số điện thoại",
    "Email",
    "Ngày sinh",
    "Giới tính",
    "Đơn vị",
    "Mã đăng ký",
    "Trạng thái",
    "Thời gian đăng ký"
]);


/* =========================================================
   DỮ LIỆU
========================================================= */

$stt = 1;

foreach ($members as $member) {

    $status =
        $member["status"] === "attended"
        ? "Đã tham gia"
        : "Đã đăng ký";


    fputcsv($output, [
        $stt++,
        $member["full_name"],
        $member["phone"],
        $member["email"],
        $member["birthday"],
        $member["gender"],
        $member["unit"],
        $member["confirmation_code"],
        $status,
        $member["registered_at"]
    ]);
}


/* =========================================================
   TỔNG SỐ
========================================================= */

fputcsv($output, []);

fputcsv($output, [
    "Tổng số thành viên",
    count($members)
]);


fclose($output);

exit;
?>

