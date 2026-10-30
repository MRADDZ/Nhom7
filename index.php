<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD AI - Tóm tắt bài viết</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 850px;
            margin: 30px auto;
            padding: 0 15px;
            background: #f5f7fa;
        }

        h1, h2 {
            color: #174a8b;
        }

        .form-box, .article {
            background: white;
            padding: 18px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        input, textarea {
            box-sizing: border-box;
            width: 100%;
            padding: 10px;
            margin: 8px 0 15px;
        }

        textarea {
            min-height: 120px;
        }

        button {
            padding: 9px 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
        }

        .primary {
            background: #1769aa;
            color: white;
        }

        .delete {
            background: #c62828;
            color: white;
        }

        .edit {
            background: #e6a23c;
            color: white;
        }

        .summary {
            background: #edf7ed;
            padding: 10px;
            white-space: pre-wrap;
        }

        #message {
            white-space: pre-wrap;
            margin: 10px 0;
        }

        .error {
            color: #c62828;
        }

        .success {
            color: #17823b;
        }

        .content {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }
    </style>
</head>

<body>

    <h1>CRUD API kết hợp AI</h1>
    <p>Quản lý bài viết và tự động tạo tóm tắt bằng AI.</p>

    <div class="form-box">
        <h2 id="formTitle">Thêm bài viết</h2>

        <form id="articleForm">
            <label for="title">Tiêu đề</label>
            <input
                type="text"
                id="title"
                required
                placeholder="Nhập tiêu đề bài viết"
            >

            <label for="content">Nội dung</label>
            <textarea
                id="content"
                required
                placeholder="Nhập nội dung cần tóm tắt"
            ></textarea>

            <button type="submit" class="primary" id="saveButton">
                Thêm bài
            </button>

            <button type="button" id="cancelButton" hidden>
                Hủy sửa
            </button>
        </form>

        <div id="message" role="status"></div>
    </div>

    <h2>Danh sách bài viết</h2>
    <div id="articleList">Đang tải dữ liệu...</div>

    <script>
        const API = "api.php";

        const form = document.getElementById("articleForm");
        const titleInput = document.getElementById("title");
        const contentInput = document.getElementById("content");
        const message = document.getElementById("message");
        const articleList = document.getElementById("articleList");
        const saveButton = document.getElementById("saveButton");
        const cancelButton = document.getElementById("cancelButton");
        const formTitle = document.getElementById("formTitle");

        let editingId = null;

        // Chuyen ky tu dac biet thanh HTML an toan
        function escapeHTML(value) {
            const chars = {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            };

            return String(value ?? "").replace(
                /[&<>"']/g,
                char => chars[char]
            );
        }

        // Gui request va doc JSON tu API
        async function requestAPI(url, options = {}) {
            const response = await fetch(url, options);
            const raw = await response.text();

            let result;

            try {
                result = JSON.parse(raw);
            } catch {
                throw new Error(
                    "API khộng trả về JSON hợp lệ. HTTP " +
                    response.status + ": " + raw.substring(0, 200)
                );
            }

            if (!response.ok || result.success === false) {
                throw new Error(
                    result.message ||
                    result.detail ||
                    "Yêu cầu API thất bại."
                );
            }

            return result;
        }

        function showMessage(text, type = "success") {
            message.textContent = text;
            message.className = type;
        }

        // READ: Tai danh sach bai viet
        async function loadArticles() {
            try {
                const result = await requestAPI(API);
                const articles = Array.isArray(result.data)
                    ? result.data
                    : [];

                if (articles.length === 0) {
                    articleList.textContent = "Chưa có bài viết nào.";
                    return;
                }

                articleList.innerHTML = articles.map(article => `
                    <div class="article">
                        <h3>${escapeHTML(article.title)}</h3>

                        <p><strong>Nội dung:</strong></p>
                        <div class="content">${escapeHTML(article.content)}</div>

                        <p><strong>Tóm tắt AI:</strong></p>
                        <div class="summary">${
                            escapeHTML(article.summary || "Chưa có bản tóm tắt")
                        }</div>

                        <button
                            class="edit"
                            data-action="edit"
                            data-id="${Number(article.id)}"
                        >
                            Sửa
                        </button>

                        <button
                            class="delete"
                            data-action="delete"
                            data-id="${Number(article.id)}"
                        >
                            Xóa
                        </button>
                    </div>
                `).join("");

            } catch (error) {
                articleList.textContent =
                    "Không tải được danh sách: " + error.message;
            }
        }

        // CREATE va UPDATE
        form.addEventListener("submit", async function(event) {
            event.preventDefault();

            const title = titleInput.value.trim();
            const content = contentInput.value.trim();

            if (!title || !content) {
                showMessage(
                    "Vui lòng nhập đầy đủ tiêu đề và nội dung.",
                    "error"
                );
                return;
            }

            const isEditing = editingId !== null;
            const url = isEditing
                ? API + "?id=" + editingId
                : API;

            saveButton.disabled = true;
            saveButton.textContent = "Đang xử lý...";
            showMessage("Đang gửi dữ liệu đến máy chủ và AI...");

            try {
                const result = await requestAPI(url, {
                    method: isEditing ? "PUT" : "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ title, content })
                });

                resetForm();
                await loadArticles();

                if (result.ai_warning) {
                    showMessage(
                        "Bài viết đã lưu, nhưng AI chưa tạo được tóm tắt: " +
                        result.ai_warning,
                        "error"
                    );
                } else {
                    showMessage(
                        result.message || "Thao tác thành công."
                    );
                }

            } catch (error) {
                showMessage("Lỗi: " + error.message, "error");

            } finally {
                saveButton.disabled = false;
                saveButton.textContent =
                    editingId !== null ? "Lưu thay đổi" : "Thêm bài";
            }
        });

        // Chuyen sang che do sua bai viet
        async function editArticle(id) {
            try {
                const result = await requestAPI(API + "?id=" + id);
                const article = result.data;

                editingId = Number(article.id);
                titleInput.value = article.title;
                contentInput.value = article.content;

                formTitle.textContent = "Sửa bài viết";
                saveButton.textContent = "Lưu thay đổi";
                cancelButton.hidden = false;

                titleInput.focus();
                window.scrollTo({ top: 0, behavior: "smooth" });

            } catch (error) {
                showMessage("Lỗi: " + error.message, "error");
            }
        }

        // DELETE: Xoa bai viet
        async function deleteArticle(id) {
            if (!confirm("Bạn có chắc muốn xóa bài viết này?")) {
                return;
            }

            try {
                const result = await requestAPI(API + "?id=" + id, {
                    method: "DELETE"
                });

                if (editingId === Number(id)) {
                    resetForm();
                }

                await loadArticles();

                showMessage(
                    result.message || "Xóa bài viết thành công."
                );

            } catch (error) {
                showMessage("Lỗi: " + error.message, "error");
            }
        }

        // Xu ly nut Sua va Xoa
        articleList.addEventListener("click", function(event) {
            const button = event.target.closest("button[data-action]");

            if (!button) return;

            const id = Number(button.dataset.id);

            if (!Number.isInteger(id) || id < 1) return;

            if (button.dataset.action === "edit") {
                editArticle(id);
            }

            if (button.dataset.action === "delete") {
                deleteArticle(id);
            }
        });

        function resetForm() {
            form.reset();
            editingId = null;

            formTitle.textContent = "Thêm bài viết";
            saveButton.textContent = "Thêm bài";
            cancelButton.hidden = true;
        }

        cancelButton.addEventListener("click", resetForm);

        // Tai du lieu khi mo website
        loadArticles();
    </script>

</body>
</html>