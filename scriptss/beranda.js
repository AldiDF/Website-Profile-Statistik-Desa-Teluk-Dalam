// ==========================
// PENGATURAN MENU NAVIGASI MOBILE
// ==========================
const navToggleBeranda = document.getElementById("navToggleBeranda");
const navMenuBeranda = document.getElementById("navMenuBeranda");

if (navToggleBeranda && navMenuBeranda) {
    navToggleBeranda.addEventListener("click", function () {
        navMenuBeranda.classList.toggle("open");
    });

    navMenuBeranda.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            navMenuBeranda.classList.remove("open");
        });
    });
}

// ==========================
// HERO SLIDESHOW (5 gambar, auto geser + titik navigasi)
// ==========================
// ==========================
// HERO SLIDESHOW (5 gambar, auto geser + titik navigasi)
// ==========================
document.addEventListener("DOMContentLoaded", function () {
    const slides = document.querySelectorAll("#heroSlider .hero-slide");
    const dotsWrap = document.getElementById("heroDots");
    
    // Pastikan elemen ada di halaman, jika tidak batalkan eksekusi
    if (!slides.length || !dotsWrap) return;

    const INTERVAL_MS = 5000;
    let current = 0;
    let timer = null;

    slides.forEach((_, i) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.setAttribute("aria-label", "Tampilkan gambar ke-" + (i + 1));
        if (i === 0) dot.classList.add("active");
        dot.addEventListener("click", () => {
            goTo(i);
            restart(); // reset timer supaya tidak langsung geser lagi setelah diklik manual
        });
        dotsWrap.appendChild(dot);
    });
    
    const dots = dotsWrap.querySelectorAll("button");

    function goTo(index) {
        slides[current].classList.remove("active");
        dots[current].classList.remove("active");
        current = index;
        slides[current].classList.add("active");
        dots[current].classList.add("active");
    }

    function next() {
        goTo((current + 1) % slides.length);
    }

    function start() {
        timer = setInterval(next, INTERVAL_MS);
    }

    function stop() {
        clearInterval(timer);
    }

    function restart() {
        stop();
        start();
    }

    start();

    const heroEl = document.getElementById("beranda");
    if (heroEl) {
        heroEl.addEventListener("mouseenter", stop);
        heroEl.addEventListener("mouseleave", start);
    }
});

// ==========================
// SMOOTH SCROLL SAAT KLIK MENU
// ==========================
const navLinksScroll = document.querySelectorAll(
    '#navMenuBeranda a[href^="#"]',
);

navLinksScroll.forEach((link) => {
    link.addEventListener("click", function (e) {
        const targetId = this.getAttribute("href");
        const targetEl = document.querySelector(targetId);
        if (targetEl) {
            e.preventDefault();
            targetEl.scrollIntoView({
                behavior: "smooth",
                block: "start",
            });
            history.pushState(null, "", targetId); // update URL tanpa reload
        }
    });
});

// ==========================
// HIGHLIGHT MENU SESUAI SECTION YANG SEDANG TERLIHAT
// ==========================
const sectionsUntukObserver = document.querySelectorAll("section[id]");

if (sectionsUntukObserver.length > 0) {
    const observerNav = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.getAttribute("id");
                    navLinksScroll.forEach((link) => {
                        link.classList.toggle(
                            "active",
                            link.getAttribute("href") === "#" + id,
                        );
                    });
                }
            });
        },
        {
            rootMargin: "-40% 0px -55% 0px", // section dianggap "aktif" saat berada di tengah layar
            threshold: 0,
        },
    );

    sectionsUntukObserver.forEach((section) => observerNav.observe(section));
}
