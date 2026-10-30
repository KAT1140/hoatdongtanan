# Quản lý hoạt động Đoàn xã Tân An

Website hỗ trợ đoàn viên theo dõi và đăng ký hoạt động của Đoàn xã Tân An. Tài khoản quản trị có thể quản lý hoạt động, đoàn viên, lượt đăng ký và tài khoản quản trị.

## Chức năng chính

- Xem danh sách hoạt động và đăng ký tham gia bằng tài khoản đoàn viên.
- Theo dõi lịch sử đăng ký và cập nhật thông tin cá nhân.
- Quản lý hoạt động, đoàn viên và lượt đăng ký trong khu vực quản trị.
- Phân quyền theo vai trò; xuất danh sách đăng ký.

## Công nghệ sử dụng

- PHP và PDO
- MySQL hoặc MariaDB
- HTML, CSS

## Chạy dự án trên máy cá nhân

1. Cài XAMPP, khởi động **Apache** và **MySQL**.
2. Đặt thư mục dự án vào `htdocs` (ví dụ: `C:\xampp\htdocs\xaTanAn`).
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin`, tạo database tên `xatanan`, rồi import file `xatanan.sql`.
4. Kiểm tra thông tin kết nối trong `config/database.php`. Cấu hình có sẵn dùng `localhost`, database `xatanan`, tài khoản `root` và mật khẩu để trống; đổi lại nếu máy của bạn dùng thông tin khác.
5. Truy cập `http://localhost/xaTanAn/`.

Tài khoản đoàn viên có thể được tạo từ trang đăng ký trên website. Để vào khu vực quản trị, cần có tài khoản được gán vai trò quản trị trong database hoặc được tạo bởi tài khoản quản trị hiện có.

## Cấu trúc thư mục

```text
admin/             Các trang quản trị
assets/css/        Giao diện và stylesheet
config/            Kết nối database
images/            Hình ảnh, logo
includes/          Xác thực và phân quyền
index.php          Trang hoạt động
login.php          Đăng nhập
register.php       Đăng ký tài khoản đoàn viên
my-activities.php  Lịch sử hoạt động
profile.php        Thông tin cá nhân
xatanan.sql        Cấu trúc và dữ liệu mẫu database
```

## Lưu ý

File cấu hình hiện dùng thông tin database dành cho môi trường phát triển cục bộ. Hãy cập nhật thông tin kết nối phù hợp trước khi triển khai lên máy chủ.
