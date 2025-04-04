jQuery(document).ready(function ($) {
    // Thêm sản phẩm vào giỏ hàng
    $('.add-to-cart').on('click', function () {
        var product_id = $(this).data('product-id');
        $.ajax({
            url: glp_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'glp_add_to_cart',
                product_id: product_id,
                nonce: glp_cart_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // Tải lại trang để cập nhật badge giỏ hàng
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });

    // Xóa sản phẩm khỏi giỏ hàng
    $('.remove-from-cart').on('click', function () {
        var product_id = $(this).data('product-id');
        $.ajax({
            url: glp_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'glp_remove_from_cart',
                product_id: product_id,
                nonce: glp_cart_params.nonce
            },
            success: function (response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload(); // Tải lại trang để cập nhật giỏ hàng
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });
});