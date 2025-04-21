jQuery(document).ready(function ($) {
    console.log('cart.js loaded');

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
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });

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
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function () {
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    });

    $(document).on('click', '.increase-quantity', function () {
        console.log('Increase quantity clicked');
        var $row = $(this).closest('tr');
        var product_id = $(this).data('product-id');
        var $quantityInput = $row.find('.quantity-input');
        var quantity = parseInt($quantityInput.val()) + 1;

        updateQuantity(product_id, quantity, $row);
    });

    $(document).on('click', '.decrease-quantity', function () {
        console.log('Decrease quantity clicked');
        var $row = $(this).closest('tr');
        var product_id = $(this).data('product-id');
        var $quantityInput = $row.find('.quantity-input');
        var quantity = parseInt($quantityInput.val()) - 1;

        if (quantity < 1) quantity = 1;
        updateQuantity(product_id, quantity, $row);
    });

    $(document).on('change', '.quantity-input', function () {
        console.log('Quantity input changed');
        var $row = $(this).closest('tr');
        var product_id = $(this).data('product-id');
        var quantity = parseInt($(this).val());

        if (quantity < 1 || isNaN(quantity)) quantity = 1;
        updateQuantity(product_id, quantity, $row);
    });

    function updateQuantity(product_id, quantity, $row) {
        console.log('Updating quantity for product ID:', product_id, 'to:', quantity);
        $.ajax({
            url: glp_cart_params.ajax_url,
            type: 'POST',
            data: {
                action: 'glp_update_cart_quantity',
                product_id: product_id,
                quantity: quantity,
                nonce: glp_cart_params.nonce
            },
            success: function (response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    $row.find('.quantity-input').val(quantity);
                    $row.find('.subtotal').text(response.data.subtotal);
                    $('#total-price').text(response.data.total_price);
                } else {
                    alert(response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error:', status, error);
                alert('Có lỗi xảy ra. Vui lòng thử lại.');
            }
        });
    }
});