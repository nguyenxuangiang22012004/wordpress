<?php
if (!defined('ABSPATH')) {
    exit;
}

// Lấy thông báo lỗi/thành công từ query string
$lead_submitted = isset($_GET['lead_submitted']);
$lead_error = isset($_GET['lead_error']);
$errors = get_transient('glp_lead_form_errors');
if ($errors) {
    delete_transient('glp_lead_form_errors');
}
?>

<div class="glp-lead-form-container d-flex align-items-center justify-content-center min-vh-100 bg-light">
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>"
        class="glp-lead-form bg-white p-4 rounded shadow-sm w-100"
        style="max-width: 600px; max-height: 1000px; overflow-y: auto"
        id="glp-lead-form">

        <!-- Hidden fields -->
        <input type="hidden" name="action" value="glp_lead_submit">
        <?php wp_nonce_field('glp_lead_form_nonce', 'glp_lead_nonce'); ?>

        <!-- Thông báo thành công -->
        <?php if ($lead_submitted) : ?>
            <div class="alert alert-success mb-4">
                Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.
            </div>
        <?php endif; ?>

        <!-- Thông báo lỗi -->
        <?php if ($lead_error && !empty($errors)) : ?>
            <div class="alert alert-danger mb-4">
                <?php foreach ($errors as $error) : ?>
                    <p><?php echo esc_html($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Trường Tên -->
        <div class="form-group mb-4">
            <label for="glp_name" class="form-label">Tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="glp_name" name="name" required
                minlength="2" maxlength="50" value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>">
            <small class="form-text text-danger d-none" id="name-error">Vui lòng nhập tên hợp lệ (2-50 ký tự)</small>
        </div>

        <!-- Trường Email -->
        <div class="form-group mb-4">
            <label for="glp_email" class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" id="glp_email" name="email" required
                pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
            <small class="form-text text-danger d-none" id="email-error">Vui lòng nhập email hợp lệ</small>
        </div>

        <!-- Trường Số Điện Thoại -->
        <div class="form-group mb-4">
            <label for="glp_phone" class="form-label">Số Điện Thoại <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" id="glp_phone" name="phone" required
                pattern="[0-9]{10,15}" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>">
            <small class="form-text text-danger d-none" id="phone-error">Vui lòng nhập số điện thoại hợp lệ (10-15 số)</small>
        </div>

        <!-- Trường Tin Nhắn -->
        <div class="form-group mb-4">
            <label for="glp_message" class="form-label">Tin Nhắn <span class="text-danger">*</span></label>
            <textarea class="form-control" id="glp_message" name="message" rows="4" required
                minlength="10" maxlength="500"
                style="height: 270px; resize: none;"><?php echo isset($_POST['message']) ? esc_textarea($_POST['message']) : ''; ?></textarea>
            <small class="form-text text-danger d-none" id="message-error">Tin nhắn phải từ 10-500 ký tự</small>
        </div>

        <!-- Nút Gửi -->
        <div class="text-center">
            <button type="submit" class="btn btn-danger px-4 py-2">Gửi</button>
        </div>
    </form>
</div>

<?php
// Thêm JavaScript validate phía client
add_action('wp_footer', 'glp_lead_form_validate_script');
function glp_lead_form_validate_script()
{
    if (has_shortcode(get_post()->post_content, 'gym_lead_form')) {
?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.getElementById('glp-lead-form');
                form.addEventListener('submit', function(e) {
                    let hasError = false;

                    // Reset thông báo lỗi
                    document.querySelectorAll('.form-text.text-danger').forEach(error => {
                        error.classList.add('d-none');
                    });

                    // Validate Tên
                    const name = form.querySelector('#glp_name').value.trim();
                    if (name.length < 2 || name.length > 50) {
                        form.querySelector('#name-error').classList.remove('d-none');
                        hasError = true;
                    }

                    // Validate Email
                    const email = form.querySelector('#glp_email').value.trim();
                    const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/;
                    if (!emailPattern.test(email)) {
                        form.querySelector('#email-error').classList.remove('d-none');
                        hasError = true;
                    }

                    // Validate Số Điện Thoại
                    const phone = form.querySelector('#glp_phone').value.trim();
                    const phonePattern = /^[0-9]{10,15}$/;
                    if (!phonePattern.test(phone)) {
                        form.querySelector('#phone-error').classList.remove('d-none');
                        hasError = true;
                    }

                    // Validate Tin Nhắn
                    const message = form.querySelector('#glp_message').value.trim();
                    if (message.length < 10 || message.length > 500) {
                        form.querySelector('#message-error').classList.remove('d-none');
                        hasError = true;
                    }

                    // Ngăn submit nếu có lỗi
                    if (hasError) {
                        e.preventDefault();
                    }
                });
            });
        </script>
<?php
    }
}
