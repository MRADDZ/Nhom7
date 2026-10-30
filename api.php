<?php

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/ai.php";


// Ham tra ket qua JSON
function traJSON($maHTTP, $duLieu)
{
    http_response_code($maHTTP);

    echo json_encode(
        $duLieu,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit;
}


// Ham doc JSON tu request body
function docJSON()
{
    $noiDung = file_get_contents("php://input");

    $duLieu = json_decode(
        $noiDung,
        true
    );

    if (!is_array($duLieu)) {
        traJSON(400, [
            "success" => false,
            "message" => "Dữ liệu gửi lên phải là JSON hợp lệ."
        ]);
    }

    return $duLieu;
}


// Lay phuong thuc HTTP
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";


// Lay id neu co
$id = null;

if (isset($_GET["id"])) {

    $id = filter_var(
        $_GET["id"],
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id < 1) {

        traJSON(400, [
            "success" => false,
            "message" => "ID không hợp lệ."
        ]);
    }
}


try {

    switch ($method) {

        // READ: LAY DU LIEU
        case "GET":

            // Lay mot bai viet theo ID
            if ($id !== null) {

                $sql = "SELECT * FROM articles WHERE id = ?";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);

                $baiViet = $stmt->fetch();

                if (!$baiViet) {

                    traJSON(404, [
                        "success" => false,
                        "message" => "Không tìm thấy bài viết."
                    ]);
                }

                traJSON(200, [
                    "success" => true,
                    "data" => $baiViet
                ]);
            }

            // Lay tat ca bai viet
            $sql = "SELECT * FROM articles ORDER BY id DESC";

            $stmt = $pdo->query($sql);

            $danhSach = $stmt->fetchAll();

            traJSON(200, [
                "success" => true,
                "data" => $danhSach
            ]);

            break;

        //-------------------------------------------------------------------------------------------------
        // CREATE: THEM BAI VIET
        case "POST":

            $duLieu = docJSON();

            $title = trim(
                $duLieu["title"] ?? ""
            );

            $content = trim(
                $duLieu["content"] ?? ""
            );

            // Kiem tra du lieu dau vao
            if ($title === "" || $content === "") {

                traJSON(400, [
                    "success" => false,
                    "message" =>
                        "Tiêu đề và nội dung không được để trống."
                ]);
            }

            // Tao tom tat bang AI
            $summary = null;
            $canhBaoAI = null;

            try {

                $summary = auto_summarization($content);

            } catch (Throwable $e) {

                // Van luu bai viet neu AI loi
                $canhBaoAI = $e->getMessage();
            }

            // Luu bai viet va tom tat vao MySQL
            $sql = "INSERT INTO articles
                    (title, content, summary)
                    VALUES (?, ?, ?)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $title,
                $content,
                $summary
            ]);

            $idMoi = (int) $pdo->lastInsertId();

            // Lay lai bai viet vua tao
            $stmt = $pdo->prepare(
                "SELECT * FROM articles WHERE id = ?"
            );

            $stmt->execute([$idMoi]);

            $baiVietMoi = $stmt->fetch();

            $ketQua = [
                "success" => true,
                "message" => "Thêm bài viết thành công.",
                "data" => $baiVietMoi
            ];

            if ($canhBaoAI !== null) {

                $ketQua["ai_warning"] =
                    "Bài viết đã được lưu nhưng AI chưa tạo được tóm tắt. Chi tiết: " . $canhBaoAI;
            }

            traJSON(201, $ketQua);

            break;

        //-------------------------------------------------------------------------------------------------
        // UPDATE: SUA BAI VIET
        case "PUT":

            if ($id === null) {

                traJSON(400, [
                    "success" => false,
                    "message" =>
                        "Cần cung cấp ID bài viết trên URL."
                ]);
            }

            // Kiem tra bai viet co ton tai khong
            $stmt = $pdo->prepare(
                "SELECT id FROM articles WHERE id = ?"
            );

            $stmt->execute([$id]);

            if (!$stmt->fetch()) {

                traJSON(404, [
                    "success" => false,
                    "message" => "Không tìm thấy bài viết."
                ]);
            }

            $duLieu = docJSON();

            $title = trim(
                $duLieu["title"] ?? ""
            );

            $content = trim(
                $duLieu["content"] ?? ""
            );

            if ($title === "" || $content === "") {

                traJSON(400, [
                    "success" => false,
                    "message" =>
                        "Tiêu đề và nội dung không được để trống."
                ]);
            }

            // Tao lai tom tat theo noi dung moi
            $summary = null;
            $canhBaoAI = null;

            try {

                $summary = auto_summarization($content);

            } catch (Throwable $e) {

                $canhBaoAI = $e->getMessage();
            }

            // Cap nhat bai viet
            $sql = "UPDATE articles
                    SET title = ?, content = ?, summary = ?
                    WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                $title,
                $content,
                $summary,
                $id
            ]);

            // Lay lai bai viet sau khi cap nhat
            $stmt = $pdo->prepare(
                "SELECT * FROM articles WHERE id = ?"
            );

            $stmt->execute([$id]);

            $baiVietSauSua = $stmt->fetch();

            $ketQua = [
                "success" => true,
                "message" => "Cập nhập bài viết thành công.",
                "data" => $baiVietSauSua
            ];

            if ($canhBaoAI !== null) {

                $ketQua["ai_warning"] =
                    "Bài viết đã được cập nhật nhưng AI chưa tạo được tóm tắt. Chi tiết: " . $canhBaoAI;
            }

            traJSON(200, $ketQua);

            break;

        //-------------------------------------------------------------------------------------------------
        // DELETE: XOA BAI VIET
        case "DELETE":

            if ($id === null) {

                traJSON(400, [
                    "success" => false,
                    "message" =>
                        "Cần cung cấp ID bài viết trên URL."
                ]);
            }

            $sql = "DELETE FROM articles WHERE id = ?";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {

                traJSON(404, [
                    "success" => false,
                    "message" => "Không tìm thấy bài viết."
                ]);
            }

            traJSON(200, [
                "success" => true,
                "message" => "Xóa bài viết thành công."
            ]);

            break;

        //-------------------------------------------------------------------------------------------------
        // PHUONG THUC KHONG DUOC HO TRO
        default:

            header("Allow: GET, POST, PUT, DELETE");

            traJSON(405, [
                "success" => false,
                "message" => "Phương thức HTTP không được hỗ trợ."
            ]);

    }

} catch (Throwable $e) {

    // Chi hien chi tiet loi trong qua trinh hoc localhost
    traJSON(500, [
        "success" => false,
        "message" => "Lỗi khi xử lý API.",
        "detail" => $e->getMessage()
    ]);
}