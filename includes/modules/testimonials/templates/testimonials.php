<div class="glcp-testimonials">
    <?php if (!empty($testimonials)): ?>
        <div class="glcp-testimonials-container">
            <div class="glcp-testimonials-slider">
                <?php foreach ($testimonials as $testimonial): if (!empty($testimonial['name']) && !empty($testimonial['content'])): ?>
                        <div class="glcp-testimonial-item">
                            <div class="glcp-testimonial-stars">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="glcp-testimonial-content">
                                <?php echo esc_html($testimonial['content']); ?>
                            </p>
                            <div class="glcp-testimonial-author">
                                <img alt="Profile picture of <?php echo esc_attr($testimonial['name']); ?>" src="<?php echo esc_url($testimonial['image'] ?: 'https://via.placeholder.com/50'); ?>" />
                                <span>
                                    <?php echo esc_html($testimonial['name']); ?>
                                </span>
                            </div>
                        </div>
                <?php endif;
                endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <p>Chưa có nhận xét nào.</p>
    <?php endif; ?>
</div>