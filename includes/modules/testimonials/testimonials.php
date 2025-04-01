<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_testimonials_init()
{
    add_shortcode('gym_testimonials', 'glcp_testimonials_shortcode');
}

function glcp_testimonials_shortcode()
{
    $testimonials = maybe_unserialize(get_option('glcp_testimonials', []));
    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/testimonials/templates/testimonials.php';
    return ob_get_clean();
}

function glcp_testimonials_admin()
{
    // Xử lý lưu dữ liệu
    if (isset($_POST['glcp_save_testimonials']) && check_admin_referer('glcp_save_testimonials_action', 'glcp_testimonials_nonce')) {
        $testimonials = isset($_POST['testimonials']) ? $_POST['testimonials'] : [];
        update_option('glcp_testimonials', maybe_serialize($testimonials));
        echo '<div class="updated"><p>Đã lưu nhận xét!</p></div>';
    }

    // Xử lý xóa nhận xét
    if (isset($_POST['glcp_delete_testimonial']) && check_admin_referer('glcp_delete_testimonial_action', 'glcp_delete_testimonial_nonce')) {
        $index = intval($_POST['delete_index']);
        $testimonials = maybe_unserialize(get_option('glcp_testimonials', []));
        if (isset($testimonials[$index])) {
            unset($testimonials[$index]);
            $testimonials = array_values($testimonials);
            update_option('glcp_testimonials', maybe_serialize($testimonials));
            echo '<div class="updated"><p>Đã xóa nhận xét!</p></div>';
        }
    }

    $testimonials = maybe_unserialize(get_option('glcp_testimonials', []));
?>
    <h2>Nhận Xét Khách Hàng</h2>
    <form method="post" id="glcp-testimonials-form">
        <?php wp_nonce_field('glcp_save_testimonials_action', 'glcp_testimonials_nonce'); ?>
        <table class="wp-list-table widefat striped" id="glcp-testimonials-table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Nội Dung</th>
                    <th>Ảnh Đại Diện (URL)</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody id="glcp-testimonials-rows">
                <?php if (!empty($testimonials)): ?>
                    <?php foreach ($testimonials as $index => $testimonial): ?>
                        <tr class="glcp-testimonial-row">
                            <td><input type="text" name="testimonials[<?php echo $index; ?>][name]" value="<?php echo esc_attr($testimonial['name'] ?? ''); ?>" placeholder="Tên khách hàng" style="width: 100%;"></td>
                            <td><textarea name="testimonials[<?php echo $index; ?>][content]" placeholder="Nội dung nhận xét" style="width: 100%;"><?php echo esc_textarea($testimonial['content'] ?? ''); ?></textarea></td>
                            <td><input type="text" name="testimonials[<?php echo $index; ?>][image]" value="<?php echo esc_attr($testimonial['image'] ?? ''); ?>" placeholder="URL ảnh đại diện" style="width: 100%;"></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('glcp_delete_testimonial_action', 'glcp_delete_testimonial_nonce'); ?>
                                    <input type="hidden" name="delete_index" value="<?php echo $index; ?>">
                                    <input type="submit" name="glcp_delete_testimonial" value="Xóa" class="button button-secondary" onclick="return confirm('Bạn chắc chắn muốn xóa nhận xét này?');">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="glcp-testimonial-row">
                        <td><input type="text" name="testimonials[0][name]" value="" placeholder="Tên khách hàng" style="width: 100%;"></td>
                        <td><textarea name="testimonials[0][content]" placeholder="Nội dung nhận xét" style="width: 100%;"></textarea></td>
                        <td><input type="text" name="testimonials[0][image]" value="" placeholder="URL ảnh đại diện" style="width: 100%;"></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p>
            <button type="button" id="glcp-add-testimonial" class="button button-secondary">Thêm Nhận Xét</button>
            <input type="submit" name="glcp_save_testimonials" value="Lưu" class="button button-primary">
        </p>
    </form>

    <script>
        jQuery(document).ready(function($) {
            var rowCount = <?php echo !empty($testimonials) ? count($testimonials) : 1; ?>;

            $('#glcp-add-testimonial').on('click', function() {
                var newRow = '<tr class="glcp-testimonial-row">' +
                    '<td><input type="text" name="testimonials[' + rowCount + '][name]" value="" placeholder="Tên khách hàng" style="width: 100%;"></td>' +
                    '<td><textarea name="testimonials[' + rowCount + '][content]" placeholder="Nội dung nhận xét" style="width: 100%;"></textarea></td>' +
                    '<td><input type="text" name="testimonials[' + rowCount + '][image]" value="" placeholder="URL ảnh đại diện" style="width: 100%;"></td>' +
                    '<td></td>' +
                    '</tr>';
                $('#glcp-testimonials-rows').append(newRow);
                rowCount++;
            });
        });
    </script>
<?php
}

glcp_testimonials_init();
