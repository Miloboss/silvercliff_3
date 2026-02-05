// DOM Elements တွေကို လှမ်းဖမ်းခြင်း
const menuToggle = document.getElementById('mobile-menu');
const navOverlay = document.getElementById('nav-overlay');
const loader = document.getElementById('loader');
const navLinks = document.querySelectorAll('.nav-link');

// 1. Loader Animation
// Window load ဖြစ်သွားရင် Loader ကို ဖျောက်မယ်
window.addEventListener('load', () => {
    setTimeout(() => {
        loader.style.opacity = '0'; // မှေးပြီး ပျောက်သွားအောင်
        setTimeout(() => {
            loader.style.display = 'none'; // လုံးဝ ဖယ်လိုက်မယ်
        }, 500);
    }, 1000); // 1 စက္ကန့်ကြာရင် ပျောက်မယ်
});

// 2. Menu Toggle Function
menuToggle.addEventListener('click', () => {
    // Menu Icon ကို X ပုံစံပြောင်း
    menuToggle.classList.toggle('is-active');
    // Overlay Modal ကို ဖွင့်/ပိတ် လုပ်
    navOverlay.classList.toggle('active');
    
    // Body scroll မရအောင် ပိတ် (Menu ဖွင့်ထားတုန်း)
    document.body.style.overflow = navOverlay.classList.contains('active') ? 'hidden' : 'auto';
});

// 3. Link နှိပ်လိုက်ရင် Menu ပြန်ပိတ်မယ် (UX ကောင်းအောင်)
navLinks.forEach(link => {
    link.addEventListener('click', () => {
        menuToggle.classList.remove('is-active');
        navOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
});