# CRUD API kết hợp AI: tự động tóm tắt bài viết

Ứng dụng quản lý bài viết (thêm, xem, sửa, xóa) bằng PHP và MySQL. Mỗi lần **thêm** hoặc **sửa** bài, hệ thống gọi OpenAI để tạo bản tóm tắt rồi lưu vào cơ sở dữ liệu. Nếu AI lỗi thì bài viết vẫn được lưu.

## Yêu cầu

- Windows với **XAMPP** (Apache, MySQL/MariaDB, PHP 8.x)
- PHP bật extension **cURL** và **pdo_mysql**
- Kết nối Internet
- Một **OpenAI API key**: <https://platform.openai.com/api-keys> (tài khoản API cần có credit để gọi được model)

## Cài đặt

### Bước 1. Đặt mã nguồn vào htdocs

Đường dẫn cuối cùng phải là:

```
C:\xampp\htdocs\CRUD_AI
```

### Bước 2. Bật Apache và MySQL

Mở **XAMPP Control Panel**, bấm **Start** ở **Apache** và **MySQL**.

### Bước 3. Bật cURL (nếu chưa bật)

1. Trong XAMPP Control Panel, bấm **Config** ở dòng Apache, chọn **PHP (php.ini)**.
2. Tìm dòng `;extension=curl`, xóa dấu `;` ở đầu dòng, lưu file.
3. **Stop rồi Start lại Apache.**

### Bước 4. Tạo cơ sở dữ liệu

File `DB/crud_ai.sql` chỉ tạo bảng, **không tự tạo database**, nên phải tạo database trước:

1. Mở `http://localhost/phpmyadmin`.
2. Bấm **Mới (New)** ở cột bên trái.
3. Tên cơ sở dữ liệu: `crud_ai`, kiểu so sánh: `utf8mb4_unicode_ci`, bấm **Tạo**.
4. Chọn database `crud_ai` vừa tạo, vào tab **Nhập (Import)**, chọn file `DB/crud_ai.sql`, bấm **Thực hiện**.
5. Thấy bảng `articles` là xong.

Mặc định kết nối dùng `root`, không mật khẩu, cổng 3306. Nếu máy khác thì sửa các hằng số ở đầu file `config.php`.

### Bước 5. Cấu hình API key

1. Sao chép file `config_ai _example.php` thành **`config_ai.php`** (cùng thư mục).
2. Mở `config_ai.php`, điền key của bạn:

```php
define("OPENAI_API_KEY", "sk-...key-cua-ban...");
define("OPENAI_MODEL", "gpt-6-luna");
```

`config_ai.php` đã nằm trong `.gitignore`, **không đẩy file này lên GitHub**.

## Chạy ứng dụng

Mở trình duyệt: **<http://localhost/CRUD_AI/index.php>**

- Nhập tiêu đề và nội dung, bấm **Thêm bài**. Chờ vài giây để AI tóm tắt. Bài hiện ra kèm ô "Tóm tắt AI".
- Bấm **Sửa** để đổi nội dung (đưa dữ liệu lên form), bấm **Lưu thay đổi**. Tóm tắt được tạo lại.
- Bấm **Hủy sửa** để thoát chế độ sửa.
- Bấm **Xóa** rồi xác nhận để xóa bài.

## Danh sách API

Địa chỉ: `http://localhost/CRUD_AI/api.php`. Dữ liệu gửi và nhận ở dạng JSON.

| Method | Địa chỉ | Chức năng | Mã thành công |
|---|---|---|---|
| GET | `api.php` | Lấy tất cả bài viết (mới nhất trước) | 200 |
| GET | `api.php?id=1` | Lấy một bài | 200 |
| POST | `api.php` | Thêm bài, AI tóm tắt | 201 |
| PUT | `api.php?id=1` | Sửa bài, AI tóm tắt lại | 200 |
| DELETE | `api.php?id=1` | Xóa bài | 200 |

Thân request cho POST và PUT:

```json
{ "title": "Tiêu đề bài viết", "content": "Nội dung bài viết..." }
```

Phản hồi thành công có dạng:

```json
{
  "success": true,
  "message": "Thêm bài viết thành công.",
  "data": { "id": 1, "title": "...", "content": "...", "summary": "..." }
}
```

Nếu AI lỗi, bài vẫn được lưu với `summary` là `null` và có thêm trường `ai_warning` ghi lý do.

Mã lỗi: `400` dữ liệu sai hoặc id không hợp lệ, `404` không tìm thấy bài, `405` method không hỗ trợ, `500` lỗi máy chủ hoặc cơ sở dữ liệu.

Thử nhanh bằng dòng lệnh:

```
curl http://localhost/CRUD_AI/api.php
curl -X DELETE "http://localhost/CRUD_AI/api.php?id=1"
```

## Cấu trúc thư mục

```
CRUD_AI/
├─ index.php               giao diện (gọi API bằng fetch)
├─ api.php                 CRUD API
├─ ai.php                  gọi OpenAI để tóm tắt
├─ config.php              kết nối MySQL bằng PDO
├─ config_ai.php           API key (tự tạo, không đẩy lên GitHub)
├─ config_ai _example.php  file mẫu cho config_ai.php
├─ DB/crud_ai.sql          cấu trúc bảng articles
└─ Slide/                  file thuyết trình
```

## Xử lý sự cố

| Hiện tượng | Nguyên nhân thường gặp | Cách xử lý |
|---|---|---|
| Không mở được trang | Apache chưa chạy | Start Apache trong XAMPP |
| "Không kết nối được với database" | MySQL chưa chạy hoặc chưa tạo database `crud_ai` | Start MySQL, làm lại Bước 4 |
| Bảng `articles` không có | Import khi chưa chọn database | Chọn database `crud_ai` rồi Import lại |
| `ai_warning`: "Bạn chưa có cấu hình OpenAI API key" | Chưa có file `config_ai.php` hoặc để trống key | Làm Bước 5 |
| `ai_warning`: "PHP chưa bắt extenston cURL" | Chưa bật cURL | Làm Bước 3 |
| `ai_warning`: "Incorrect API key provided" | Sai key | Sao chép lại key, dán đúng |
| `ai_warning` báo quota hoặc billing | Tài khoản hết credit | Nạp thêm credit hoặc dùng key khác |
| `ai_warning` báo model không tồn tại | Sai tên model | Sửa `OPENAI_MODEL` trong `config_ai.php` |
| Sửa key nhưng không đổi | Chưa lưu file | Nhấn Ctrl + S, thêm bài **mới** để thử |

## Lưu ý

- Nội dung bài viết được gửi lên server của OpenAI để tóm tắt. Không dùng nội dung riêng tư khi demo.
- Khi đẩy lên GitHub, kiểm tra `git status` để chắc chắn `config_ai.php` không nằm trong danh sách commit.
