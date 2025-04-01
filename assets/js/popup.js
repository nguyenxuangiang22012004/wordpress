jQuery(document).ready(function ($) {
    var delay = <? php echo esc_js(get_option('glcp_popup_delay', 5)); ?> * 1000;
    setTimeout(function () {
        $('#glcp-popup-overlay').fadeIn(300);
    }, delay);

    $('#glcp-popup-close').on('click', function () {
        $('#glcp-popup-overlay').fadeOut(300);
    });

    $('#glcp-popup-overlay').on('click', function (e) {
        if (e.target === this) {
            $(this).fadeOut(300);
        }
    });
});