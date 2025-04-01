jQuery(document).ready(function ($) {
    $('.glcp-testimonials-slider').slick({
        slidesToShow: 3, // Hiển thị 3 nhận xét cùng lúc trên desktop
        slidesToScroll: 1,
        autoplay: true,
        autoplaySpeed: 3000, // Trượt mỗi 3 giây
        arrows: true, // Hiển thị nút điều hướng
        dots: true, // Hiển thị chấm (dots) bên dưới
        responsive: [
            {
                breakpoint: 768, // Dưới 768px (mobile)
                settings: {
                    slidesToShow: 1, // Chỉ hiển thị 1 nhận xét
                    slidesToScroll: 1
                }
            }
        ]
    });
});