<?php
if (!defined('ABSPATH')) {
    exit;
}

$register_success = isset($_GET['register_success']);
$register_error = isset($_GET['register_error']);
$errors = get_transient('glcp_register_errors');
if ($errors) {
    delete_transient('glcp_register_errors');
}
?>

<div class="glcp-schedule">
    <h2 class="text-center mb-4">Lịch Tập Gym</h2>

    <!-- Thông báo thành công -->
    <?php if ($register_success): ?>
        <div class="alert alert-success mb-4">
            Cảm ơn bạn đã đăng ký! Chúng tôi sẽ liên hệ sớm nhất.
        </div>
    <?php endif; ?>

    <!-- Thông báo lỗi -->
    <?php if ($register_error && !empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <?php foreach ($errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($schedule)): ?>
        <p class="text-center">Hiện tại chưa có lịch tập nào.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped schedule-table">
                <thead>
                    <tr>
                        <th>Tên Lớp</th>
                        <th>Loại Lớp</th>
                        <th>Thời Gian</th>
                        <th>Huấn Luyện Viên</th>
                        <th>Kinh Nghiệm HLV</th>
                        <th>Đăng Ký Học</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedule as $index => $class): ?>
                        <tr>
                            <td><?php echo esc_html($class['name'] ?? ''); ?></td>
                            <td><?php echo $class['type'] === 'free' ? 'Miễn phí' : 'Trả phí'; ?></td>
                            <td><?php echo esc_html($class['time'] ?? ''); ?></td>
                            <td><?php echo esc_html($class['trainer'] ?? ''); ?></td>
                            <td><?php echo esc_html($class['experience'] ?? ''); ?></td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm register-btn" data-bs-toggle="modal" data-bs-target="#registerModal-<?php echo $index; ?>">
                                    Đăng Ký
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Đăng Ký -->
                        <div class="modal fade" id="registerModal-<?php echo $index; ?>" tabindex="-1" aria-labelledby="registerModalLabel-<?php echo $index; ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="registerModalLabel-<?php echo $index; ?>">Đăng Ký Lớp: <?php echo esc_html($class['name']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                            <input type="hidden" name="action" value="glcp_register_class">
                                            <input type="hidden" name="class_index" value="<?php echo $index; ?>">
                                            <?php wp_nonce_field('glcp_register_nonce_action', 'glcp_register_nonce'); ?>
                                            <div class="mb-3">
                                                <label for="register_name-<?php echo $index; ?>" class="form-label">Họ Tên <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="register_name-<?php echo $index; ?>" name="register_name" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="register_phone-<?php echo $index; ?>" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
                                                <input type="tel" class="form-control" id="register_phone-<?php echo $index; ?>" name="register_phone" required pattern="[0-9]{10,15}">
                                            </div>
                                            <div class="mb-3">
                                                <label for="register_email-<?php echo $index; ?>" class="form-label">Email <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" id="register_email-<?php echo $index; ?>" name="register_email" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">Xác Nhận Đăng Ký</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    /* CSS cho bảng lịch học - Tông màu nâu sáng */
    .glcp-schedule {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background-color: #f8f4f0;
        /* Màu nền nâu sáng */
    }

    .schedule-table {
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        background-color: #fff;
    }

    .schedule-table thead {
        background-color: #a67c52;
        /* Màu nâu đậm cho header */
        color: #fff;
    }

    .schedule-table th,
    .schedule-table td {
        padding: 15px;
        text-align: center;
        vertical-align: middle;
        border: none;
        border-bottom: 1px solid #e9ecef;
    }

    .schedule-table th {
        font-size: 1.1rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .schedule-table tbody tr {
        transition: background-color 0.3s ease;
    }

    .schedule-table tbody tr:hover {
        background-color: #f1e8e0;
        /* Màu nâu nhạt khi hover */
    }

    .schedule-table td {
        font-size: 0.95rem;
        color: #5a4a42;
        /* Màu chữ nâu đậm */
    }

    .schedule-table tbody tr:nth-child(even) {
        background-color: #f9f5f1;
        /* Màu nâu rất nhạt */
    }

    /* CSS cho nút Đăng Ký - Màu đỏ */
    .register-btn {
        font-size: 0.9rem;
        padding: 8px 20px;
        border-radius: 20px;
        transition: all 0.3s ease;
        background-color: #d9534f;
        /* Màu đỏ */
        border-color: #d9534f;
    }

    .register-btn:hover {
        background-color: #c9302c;
        /* Màu đỏ đậm khi hover */
        transform: translateY(-2px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        border-color: #c9302c;
    }

    /* CSS cho Modal */
    .modal-content {
        border-radius: 10px;
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
        background-color: #f8f4f0;
        /* Màu nền nâu sáng */
    }

    .modal-header {
        background-color: #a67c52;
        /* Màu nâu đậm */
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }

    .modal-title {
        font-weight: 600;
    }

    .modal-body {
        padding: 20px;
    }

    .form-label {
        font-weight: 500;
        color: #5a4a42;
        /* Màu chữ nâu đậm */
    }

    .form-control {
        border-radius: 5px;
        border: 1px solid #d1c7bd;
        /* Viền màu nâu nhạt */
        transition: border-color 0.3s ease;
        background-color: #fff;
    }

    .form-control:focus {
        border-color: #a67c52;
        /* Màu nâu đậm khi focus */
        box-shadow: 0 0 5px rgba(166, 124, 82, 0.3);
        /* Shadow màu nâu */
    }

    /* Button trong modal */
    .modal-body .btn-primary {
        background-color: #d9534f;
        /* Màu đỏ */
        border-color: #d9534f;
    }

    .modal-body .btn-primary:hover {
        background-color: #c9302c;
        /* Màu đỏ đậm */
        border-color: #c9302c;
    }

    /* Alert */
    .alert-success {
        background-color: #dff0d8;
        border-color: #d6e9c6;
        color: #3c763d;
    }

    .alert-danger {
        background-color: #f2dede;
        border-color: #ebccd1;
        color: #a94442;
    }

    /* Responsive */
    @media (max-width: 768px) {

        .schedule-table th,
        .schedule-table td {
            padding: 10px;
            font-size: 0.85rem;
        }

        .schedule-table th {
            font-size: 0.95rem;
        }

        .register-btn {
            padding: 6px 15px;
            font-size: 0.8rem;
        }

        .glcp-schedule {
            padding: 10px;
        }
    }

    @media (max-width: 576px) {
        .table-responsive {
            border: none;
        }

        .schedule-table th,
        .schedule-table td {
            display: block;
            width: 100%;
            text-align: left;
            padding: 8px;
        }

        .schedule-table thead {
            display: none;
        }

        .schedule-table tbody tr {
            margin-bottom: 15px;
            border-bottom: 2px solid #a67c52;
            /* Màu nâu đậm */
        }

        .schedule-table td::before {
            content: attr(data-label);
            font-weight: bold;
            display: inline-block;
            width: 40%;
            color: #a67c52;
            /* Màu nâu đậm */
        }

        .schedule-table td {
            border: none;
            padding: 8px 15px;
        }

        .register-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>