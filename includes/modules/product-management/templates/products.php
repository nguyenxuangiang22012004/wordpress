<?php
if (!defined('ABSPATH')) {
    exit;
}

$cart = isset($_SESSION['glp_cart']) ? $_SESSION['glp_cart'] : array();
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
?>

<div class="glp-products">
    <h2 class="text-center mb-5">Sản Phẩm Gym</h2>

    <!-- Form tìm kiếm -->
    <div class="row mb-4">
        <div class="col-md-6 offset-md-3">
            <form method="get" action="" class="d-flex">
                <input type="text" name="search" class="form-control me-2" placeholder="Tìm kiếm sản phẩm..." value="<?php echo esc_attr($search_query); ?>">
                <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
            </form>
        </div>
    </div>

    <!-- Nút hiển thị giỏ hàng -->
    <div class="text-end mb-4">
        <button type="button" class="btn btn-success position-relative" data-bs-toggle="modal" data-bs-target="#cartModal">
            Xem Giỏ Hàng
            <?php if (!empty($cart)): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?php echo count($cart); ?>
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Thông báo thành công -->
    <?php if ($order_submitted) : ?>
        <div class="alert alert-success mb-4">
            Cảm ơn bạn đã đặt hàng! Chúng tôi sẽ liên hệ sớm nhất.
        </div>
    <?php endif; ?>

    <!-- Thông báo lỗi -->
    <?php if ($order_error && !empty($errors)) : ?>
        <div class="alert alert-danger mb-4">
            <?php foreach ($errors as $error) : ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($products)): ?>
        <p class="text-center">Không tìm thấy sản phẩm nào.</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($products as $index => $product): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card product-card h-100 shadow-sm">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?php echo esc_url($product['image']); ?>" class="card-img-top" alt="<?php echo esc_attr($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top text-center bg-light" style="height: 200px; line-height: 200px;">Chưa có hình ảnh</div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo esc_html($product['name']); ?></h5>
                            <p class="card-text text-muted"><?php echo esc_html($product['description']); ?></p>
                            <p class="card-text text-danger fw-bold"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                            <button type="button" class="btn btn-primary add-to-cart" data-product-id="<?php echo $index; ?>">Thêm vào giỏ hàng</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modal giỏ hàng -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">Giỏ Hàng Của Bạn</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($cart)): ?>
                        <p>Giỏ hàng của bạn đang trống.</p>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Sản Phẩm</th>
                                    <th>Giá</th>
                                    <th>Số Lượng</th>
                                    <th>Thành Tiền</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_price = 0;
                                foreach ($cart as $item):
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $total_price += $subtotal;
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($item['name']); ?></td>
                                        <td><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($subtotal, 0, ',', '.'); ?> VNĐ</td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-from-cart" data-product-id="<?php echo $item['product_id']; ?>">Xóa</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Tổng Tiền:</strong></td>
                                    <td colspan="2"><?php echo number_format($total_price, 0, ',', '.'); ?> VNĐ</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Form thông tin liên hệ -->
                        <h5 class="mt-4">Thông Tin Liên Hệ</h5>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="order-form">
                            <input type="hidden" name="action" value="glp_order_submit">
                            <?php wp_nonce_field('glp_order_form_nonce', 'glp_order_nonce'); ?>
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="customer_phone" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required pattern="[0-9]{10,15}">
                            </div>
                            <div class="mb-3">
                                <label for="customer_address" class="form-label">Địa Chỉ <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="customer_address" name="customer_address" rows="3" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Xác Nhận Đặt Hàng</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS tùy chỉnh -->
<style>
    .product-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2) !important;
    }

    .card-img-top {
        border-bottom: 1px solid #ddd;
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #333;
    }

    .card-text {
        font-size: 0.9rem;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    @media (max-width: 576px) {
        .product-card {
            margin: 0 auto;
        }
    }
</style>