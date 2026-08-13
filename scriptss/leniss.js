// ==========================
// LENIS - SMOOTH SCROLL
// ==========================
const lenis = new Lenis({
    duration: 1.2,
    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    smoothWheel: true,   // <-- ini yang mengaktifkan smooth untuk scroll manual (mouse wheel)
    touchMultiplier: 1.5,
});

function raf(time) {
    lenis.raf(time);
    requestAnimationFrame(raf);
}
requestAnimationFrame(raf);
