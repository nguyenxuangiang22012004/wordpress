<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_popup_offers_init()
{
    add_action('wp_footer', 'glcp_popup_offers_display');
}

function glcp_popup_offers_display()
{
    $message = get_option('glcp_popup_message', 'Đăng ký hôm nay để nhận 1 buổi tập thử miễn phí!');
    $delay = get_option('glcp_popup_delay', 5);
    $cta_link = get_option('glcp_popup_cta_link', '/contact'); // Mặc định là /contact
?>
    <div id="glcp-popup-overlay" class="glcp-popup-overlay" style="display: none;">
        <div id="glcp-popup" class="glcp-popup">
            <span id="glcp-popup-close" class="glcp-popup-close">×</span>
            <div class="glcp-popup-content">
                <h3>Ưu Đãi Đặc Biệt</h3>
                <p><?php echo esc_html($message); ?></p>
                <a href="<?php echo esc_url($cta_link); ?>" class="glcp-popup-button">Đăng Ký Ngay</a>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            var delay = <?php echo esc_js($delay); ?> * 1000;
            setTimeout(function() {
                $('#glcp-popup-overlay').fadeIn(300);
            }, delay);

            $('#glcp-popup-close').on('click', function() {
                $('#glcp-popup-overlay').fadeOut(300);
            });

            $('#glcp-popup-overlay').on('click', function(e) {
                if (e.target === this) {
                    $(this).fadeOut(300);
                }
            });
        });
    </script>
<?php
}

function glcp_popup_offers_admin()
{
    if (isset($_POST['glcp_save_popup'])) {
        update_option('glcp_popup_message', sanitize_text_field($_POST['popup_message']));
        update_option('glcp_popup_delay', intval($_POST['popup_delay']));
        update_option('glcp_popup_cta_link', esc_url_raw($_POST['popup_cta_link']));
        echo '<div class="updated"><p>Đã lưu popup!</p></div>';
    }
    $popup_message = get_option('glcp_popup_message', 'Đăng ký hôm nay để nhận 1 buổi tập thử miễn phí!');
    $popup_delay = get_option('glcp_popup_delay', 5);
    $popup_cta_link = get_option('glcp_popup_cta_link', '/contact');
?>
    <h2>Popup Ưu Đãi</h2>
    <form method="post">
        <p><label>Thông điệp:</label><br><input type="text" name="popup_message" value="<?php echo esc_attr($popup_message); ?>" style="width: 400px;"></p>
        <p><label>Thời gian hiển thị (giây):</label><br><input type="number" name="popup_delay" value="<?php echo esc_attr($popup_delay); ?>" min="1" style="width: 100px;"></p>
        <p><label>Liên kết CTA (URL trang liên hệ):</label><br><input type="url" name="popup_cta_link" value="<?php echo esc_attr($popup_cta_link); ?>" style="width: 400px;" placeholder="Ví dụ: /contact hoặc https://yourdomain.com/lien-he"></p>
        <p><input type="submit" name="glcp_save_popup" value="Lưu" class="button button-primary"></p>
    </form>
<?php
}

glcp_popup_offers_init();
