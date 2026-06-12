/**
 * GRAND 5 STAIRS HALL - GENERAL CLIENT CONTROLS
 * ----------------------------------------------
 * Mengelola interaksi visual umum seperti slideshow (Hero Carousel)
 * dan Modal Denah Ruangan Meja Bundar.
 */

document.addEventListener('DOMContentLoaded', () => {

    // ==========================================================================
    // 1. AUTOMATIC HERO CAROUSEL
    // ==========================================================================
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;
    let carouselInterval;

    if (slides.length > 0) {
        // Fungsi pindah slide
        const goToSlide = (n) => {
            slides[currentSlide].classList.remove('active');
            dots[currentSlide].classList.remove('active');
            currentSlide = (n + slides.length) % slides.length;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        };

        // Fungsi slide otomatis berikutnya
        const nextSlide = () => {
            goToSlide(currentSlide + 1);
        };

        // Mulai timer (tiap 5 detik geser)
        const startCarousel = () => {
            carouselInterval = setInterval(nextSlide, 5000);
        };

        // Berhenti timer (jika user mengklik manual dot)
        const stopCarousel = () => {
            clearInterval(carouselInterval);
        };

        // Pasang event listener di indikator titik (dots)
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                stopCarousel();
                goToSlide(index);
                startCarousel(); // Mulai ulang timer setelah klik
            });
        });

        // Pasang event listener di tombol prev / next jika ada
        const prevBtn = document.getElementById('carousel-prev-btn');
        const nextBtn = document.getElementById('carousel-next-btn');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                stopCarousel();
                goToSlide(currentSlide - 1);
                startCarousel();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                stopCarousel();
                goToSlide(currentSlide + 1);
                startCarousel();
            });
        }

        // Jalankan carousel
        startCarousel();
    }
});
