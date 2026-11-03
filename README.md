# Xa Tan An

Hệ thống quản lý hoạt động cho Đoàn xã Tân An, hỗ trợ đoàn viên đăng ký tham gia hoạt động và giúp quản trị viên quản lý thông tin hoạt động, thành viên và lượt đăng ký.

## Giới thiệu

Website này cho phép:

- Đoàn viên xem danh sách hoạt động đang mở đăng ký.
- Người dùng đăng ký tham gia hoạt động trực tiếp trên website.
- Thành viên xem lịch sử hoạt động đã tham gia hoặc đã đăng ký.
- Quản trị viên quản lý hoạt động, thành viên, danh sách đăng ký và tài khoản quản trị.
- Hệ thống phân quyền theo vai trò như `admin` và `super_admin`.

## Chức năng chính

### Cho người dùng (member)

- Đăng ký tài khoản mới
- Đăng nhập / đăng xuất
- Xem danh sách hoạt động
- Đăng ký tham gia hoạt động
- Theo dõi lịch sử hoạt động cá nhân
- Cập nhật hồ sơ cá nhân

### Cho quản trị

- Quản lý hoạt động
- Quản lý thành viên
- Quản lý danh sách đăng ký
- Xác nhận trạng thái tham gia
- Xuất dữ liệu đăng ký theo nhu cầu
- Quản lý tài khoản quản trị

## Công nghệ sử dụng

- PHP 7+ / PHP 8
- PDO cho kết nối MySQL
- MySQL / MariaDB
- HTML, CSS, JavaScript cơ bản
- XAMPP để chạy môi trường local

## Yêu cầu môi trường

- XAMPP hoặc môi trường Apache + MySQL tương đương
- PHP 7.4+ hoặc 8.x
- MySQL 5.7+ hoặc MariaDB

## Hướng dẫn cài đặt

1. Cài đặt XAMPP và khởi động các dịch vụ:
   - Apache
   - MySQL
2. Sao chép thư mục dự án vào `htdocs`:
   - Ví dụ: `C:\xampp\htdocs\xaTanAn`
3. Mở phpMyAdmin tại `http://localhost/phpmyadmin`
4. Tạo database có tên `xatanan`
5. Import file `xatanan.sql` vào database vừa tạo
6. Kiểm tra cấu hình kết nối database trong file `config/database.php`
7. Truy cập website tại:
   - `http://localhost/xaTanAn/`

## Cấu hình database mặc định

File `config/database.php` hiện đang dùng cấu hình local mẫu:

```php
$host = "localhost";
$dbname = "xatanan";
$username = "root";
$password = "";
```

Nếu máy của bạn dùng tài khoản MySQL khác, hãy cập nhật lại các giá trị trên.

## Cấu trúc thư mục

```text
xaTanAn/
├── admin/                # Trang quản trị
│   ├── includes/         # Sidebar và thành phần chung admin
│   ├── activities.php
│   ├── activity-add.php
│   ├── activity-edit.php
│   ├── admins.php
│   ├── export-activity-pdf.php
│   ├── export-registrations.php
│   ├── index.php
│   ├── members.php
│   └── registrations.php
├── assets/
│   └── css/
├── config/
│   └── database.php      # Cấu hình kết nối database
├── includes/
│   └── auth.php          # Kiểm tra xác thực và phân quyền
├── images/               # Hình ảnh, logo
├── index.php             # Trang chính / danh sách hoạt động
├── login.php             # Trang đăng nhập
├── logout.php            # Trang đăng xuất
├── my-activities.php     # Lịch sử hoạt động của người dùng
├── profile.php           # Trang hồ sơ cá nhân
├── register.php          # Trang đăng ký tài khoản thành viên
├── xatanan.sql           # File database mẫu
├── README.md             # Tài liệu dự án
└── ...
```

## Lưu ý quan trọng

- Dự án này đang được cấu hình cho môi trường phát triển local.
- Trước khi triển khai lên môi trường thực tế, cần cập nhật thông tin database và kiểm tra lại quyền truy cập.
- Tài khoản thành viên có thể tự đăng ký từ trang `register.php`.
- Tài khoản quản trị phải được tạo hoặc cấp quyền trong hệ thống database theo vai trò `admin` hoặc `super_admin`.

## Nguồn tài liệu

Dự án đang sử dụng mô hình PHP thuần, tập trung vào việc quản lý dữ liệu và phân quyền trong một ứng dụng web đơn giản, phù hợp cho môi trường học tập hoặc triển khai nội bộ.

