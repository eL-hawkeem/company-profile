<?php
$contact_info = [
    'address' => getSiteSetting('contact_address'),
    'phone' => getSiteSetting('contact_phone'),
    'email' => getSiteSetting('contact_email'),
    'hours' => getSiteSetting('contact_hours')
];
?>
<footer class="footer-section">
    <div class="container">
        <div class="row g-5">
            <!-- Company Info -->
            <div class="col-lg-4">
                <div class="footer-brand">
                    <h4>PT Sarana Sentra Teknologi Utama</h4>
                    <p class="footer-desc">
                        PT. Sarana Sentra Teknologi Utama adalah partner terpercaya Anda dalam penyediaan dan pemeliharaan infrastruktur teknologi untuk pertumbuhan bisnis yang berkelanjutan.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-widget">
                    <h5 class="widget-title">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="index.php">Beranda</a></li>
                        <li><a href="tentang.php">Tentang Kami</a></li>
                        <li><a href="produk.php">Produk</a></li>
                        <li><a href="artikel.php">Blog</a></li>
                        <li><a href="kontak.php">Kontak</a></li>
                    </ul>
                </div>
            </div>
            <!-- Services -->
            <div class="col-lg-2 col-md-6">
                <div class="footer-widget">
                    <h5 class="widget-title">Layanan</h5>
                    <ul class="footer-links">
                        <li><a href="layanan.php?slug=pengadaan">Pengadaan</a></li>
                        <li><a href="layanan.php?slug=kontrak">Perawatan Kontrak</a></li>
                        <li><a href="layanan.php?slug=retail">Perawatan Retail</a></li>
                    </ul>
                </div>
            </div>
            <!-- Contact Info -->
            <div class="col-lg-4">
                <div class="footer-widget">
                    <h5 class="widget-title">Kontak Kami</h5>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($contact_info['address']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($contact_info['phone']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($contact_info['email']); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($contact_info['hours']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="copyright">&copy; 2025 PT. Sarana Sentra Teknologi Utama. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
</footer>
<style>
    .footer-section {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #e2e8f0;
        padding: 4rem 0 2rem;
        position: relative;
    }

    .footer-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.5), transparent);
    }

    .footer-brand h4 {
        color: #cbd5e1;
        margin-bottom: 1rem;
        font-weight: 700;
    }

    .footer-desc {
        color: #cbd5e1;
        margin-bottom: 1.5rem;
        line-height: 1.7;
    }

    .social-links {
        display: flex;
        gap: 1rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        background: rgba(59, 130, 246, 0.1);
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #04ffffff;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .social-link:hover {
        background: #3b82f6;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
    }

    .widget-title {
        color: #f1f5f9;
        margin-bottom: 1.5rem;
        font-weight: 600;
        position: relative;
        padding-bottom: 0.5rem;
    }

    .widget-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 30px;
        height: 2px;
        background: #3b82f6;
    }

    .footer-links {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 0.75rem;
    }

    .footer-links a {
        color: #cbd5e1;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-links a:hover {
        color: #3b82f6;
        padding-left: 5px;
    }

    .contact-info .contact-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1rem;
        gap: 0.75rem;
    }

    .contact-item i {
        color: #04ffffff;
        font-size: 1.1rem;
        margin-top: 0.2rem;
        flex-shrink: 0;
    }

    .contact-item span {
        color: #cbd5e1;
        line-height: 1.6;
    }

    .footer-divider {
        border: none;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(203, 213, 225, 0.3), transparent);
        margin: 2.5rem 0 1.5rem;
    }

    .copyright {
        color: #94a3b8;
        margin: 0;
    }

    .footer-bottom-links {
        display: flex;
        gap: 2rem;
        justify-content: flex-end;
    }

    .footer-bottom-links a {
        color: #94a3b8;
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }

    .footer-bottom-links a:hover {
        color: #3b82f6;
    }

    @media (max-width: 768px) {
        .footer-bottom-links {
            justify-content: flex-start;
            margin-top: 1rem;
        }
    }
</style>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
<!-- AOS Animation -->
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<!-- Custom JavaScript -->
<script>
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });

    // Initialize Testimonial Swiper
    const testimonialSwiper = new Swiper('.testimonial-swiper', {
        slidesPerView: 'auto',
        centeredSlides: true,
        spaceBetween: 30,
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.testimonial-pagination',
            clickable: true,
            bulletClass: 'swiper-pagination-bullet',
            bulletActiveClass: 'swiper-pagination-bullet-active',
        },
        breakpoints: {
            320: {
                slidesPerView: 1,
                spaceBetween: 20,
            },
            768: {
                slidesPerView: 2,
                spaceBetween: 30,
            },
            1024: {
                slidesPerView: 3,
                spaceBetween: 30,
            },
        },
        on: {
            init: function() {
                updateActiveSlide(this);
            },
            slideChange: function() {
                updateActiveSlide(this);
            }
        }
    });

    function updateActiveSlide(swiper) {
        const slides = swiper.slides;
        slides.forEach((slide, index) => {
            if (slide.classList.contains('swiper-slide-active')) {
                slide.style.zIndex = '10';
            } else if (slide.classList.contains('swiper-slide-next') || slide.classList.contains('swiper-slide-prev')) {
                slide.style.zIndex = '5';
            } else {
                slide.style.zIndex = '1';
            }
        });
    }

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();

            const href = this.getAttribute('href');

            // Tambahkan pengecekan ini
            if (href.length > 1) {
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = 'none';
        }
    });
</script>
</body>

</html>