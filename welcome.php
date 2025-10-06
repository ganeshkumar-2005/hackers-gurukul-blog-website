<?php
// No DB connection needed for this landing page unless you want dynamic content later
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/swiper/swiper-bundle.min.css" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        "primary": "#1193d4",
        "background-light": "#f6f7f8",
        "background-dark": "#101c22",
      },
      fontFamily: {
        "display": ["Space Grotesk"]
      },
      borderRadius: {
        "DEFAULT": "0.125rem",
        "lg": "0.25rem",
        "xl": "0.5rem",
        "full": "0.75rem"
      },
      keyframes: {
        'glow': {
          '0%, 100%': { opacity: 0.1 },
          '50%': { opacity: 0.3 },
        },
        'marquee': {
          '0%': { transform: 'translateX(0)' },
          '100%': { transform: 'translateX(-100%)' },
        }
      },
      animation: {
        'glow': 'glow 8s ease-in-out infinite',
        'marquee': 'marquee 20s linear infinite',
      }
    },
  },
}
</script>
<style>
.circuit-board {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cg fill='%231193d4' fill-opacity='0.1'%3E%3Crect x='0' y='0' width='100' height='1'/%3E%3Crect x='0' y='0' width='1' height='100'/%3E%3C/g%3E%3Cg fill='%231193d4' fill-opacity='0.05'%3E%3Crect x='0' y='50' width='100' height='1'/%3E%3Crect x='50' y='0' width='1' height='100'/%3E%3C/g%3E%3Cg fill='none' stroke='%231193d4' stroke-width='1' stroke-opacity='0.1'%3E%3Cpath d='M0 50 L50 0 L100 50 L50 100 Z'/%3E%3Cpath d='M50 0 L0 50 L50 100 L100 50 Z'/%3E%3C/g%3E%3C/svg%3E");
  animation: glow 8s ease-in-out infinite;
}
.swiper-button-next, .swiper-button-prev {
  color: #1193d4;
  --swiper-navigation-size: 32px;
  background-color: rgba(16, 28, 34, 0.5);
  border-radius: 50%;
  width: 48px;
  height: 48px;
  box-shadow: 0 0 15px rgba(17, 147, 212, 0.5);
}
.swiper-pagination-bullet {
  background-color: #1193d4;
  opacity: 0.5;
}
.swiper-pagination-bullet-active {
  opacity: 1;
  box-shadow: 0 0 10px #1193d4;
}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-white">
<div class="relative flex min-h-screen w-full flex-col overflow-x-hidden">

<!-- 🔹 Header -->
<header class="absolute top-0 left-0 right-0 z-20 flex items-center justify-between whitespace-nowrap bg-transparent px-10 py-4">
  <div class="flex items-center gap-3">
    <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
      <path d="M39.5563 34.1455V13.8546C39.5563 15.708 36.8773 17.3437 32.7927 18.3189C30.2914 18.916 27.263 19.2655 24 19.2655C20.737 19.2655 17.7086 18.916 15.2073 18.3189C11.1227 17.3437 8.44365 15.708 8.44365 13.8546V34.1455C8.44365 35.9988 11.1227 37.6346 15.2073 38.6098C17.7086 39.2069 20.737 39.5564 24 39.5564C27.263 39.5564 30.2914 39.2069 32.7927 38.6098C36.8773 37.6346 39.5563 35.9988 39.5563 34.1455Z" fill="currentColor"></path>
    </svg>
    <h2 class="text-xl font-bold text-white">Hackers Gurukul Blog</h2>
  </div>
  <div class="flex flex-1 items-center justify-end gap-2">
    <button onclick="window.location.href='signup.php'" 
            class="flex h-10 min-w-[84px] items-center justify-center overflow-hidden rounded-lg bg-primary px-4 text-sm font-bold text-white transition-all hover:bg-primary/80 hover:shadow-[0_0_15px_#1193d4]">
      Sign Up
    </button>
    <button onclick="window.location.href='loginpage.php'" 
            class="flex h-10 min-w-[84px] items-center justify-center overflow-hidden rounded-lg bg-primary/20 dark:bg-primary/30 px-4 text-sm font-bold text-primary transition-all hover:bg-primary/30 dark:hover:bg-primary/40 hover:shadow-[0_0_15px_#1193d4]">
      Log In
    </button>
  </div>
</header>

<!-- 🔹 Swiper -->
<main class="flex-1">
  <div class="swiper-container h-screen relative">
    <div class="absolute inset-0 circuit-board z-0"></div>
    <div class="swiper-wrapper">
      
      <!-- Slide 1 -->
      <div class="swiper-slide">
        <div class="absolute inset-0 bg-cover bg-center" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCl29Vy86yQFo2qK1UvsEN1iySaujCWWfHZCpLlXY2JPuEn5gF4-QOeiy_pLlhStynbv9pb2ZeoIK9JZSaamt2VDfWQfhergfbe9zQLMyHDTX5syYaJLIGqUGTTIoGDytZte8d_mshV-jM8gbvzeUA7k8I1fVR1sgo2KdCZK7ZXKKOzDYBTET8eV-MQ04rEsZbF4zl73Mw9ohaJT2ceMCTmQ-fV-goCaPtBLbVkRAjXe4E61E6KwTcoFtxTUIm7m7kNsrZ6IkXM2yz-");'>
          <div class="absolute inset-0 bg-black/70"></div>
        </div>
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">
          <h1 class="text-4xl md:text-6xl font-bold text-white tracking-tighter mb-4" style="text-shadow: 0 0 15px rgba(17, 147, 212, 0.7);">
            Ethical Hacking: Defend the Digital Frontier
          </h1>
          <p class="text-lg md:text-xl text-gray-300 max-w-3xl mb-8">
            Master the art of penetration testing and vulnerability assessment to protect systems from cyber threats.
          </p>
          <button onclick="window.location.href='signup.php'" 
                  class="flex h-12 min-w-[84px] items-center justify-center overflow-hidden rounded-lg bg-primary px-6 text-base font-bold text-white transition-all hover:bg-primary/80 hover:shadow-[0_0_20px_#1193d4]">
            Learn More
          </button>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="swiper-slide">
        <div class="absolute inset-0 bg-cover bg-center" style='background-image: url("https://th.bing.com/th/id/OIP.KUd8RojuE7HkqKu-D25hXAHaEK?w=324&h=182&c=7&r=0&o=7&cb=12&dpr=1.3&pid=1.7&rm=3");'>
          <div class="absolute inset-0 bg-black/70"></div>
        </div>
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">
          <h1 class="text-4xl md:text-6xl font-bold text-white tracking-tighter mb-4" style="text-shadow: 0 0 15px rgba(17, 147, 212, 0.7);">
            Secure Coding: Build Unbreakable Software
          </h1>
          <p class="text-lg md:text-xl text-gray-300 max-w-3xl mb-8">
            Discover the principles of writing secure code and prevent common vulnerabilities from day one.
          </p>
          <button onclick="window.location.href='signup.php'" 
                  class="flex h-12 min-w-[84px] items-center justify-center overflow-hidden rounded-lg bg-primary px-6 text-base font-bold text-white transition-all hover:bg-primary/80 hover:shadow-[0_0_20px_#1193d4]">
            Learn More
          </button>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="swiper-slide">
        <div class="absolute inset-0 bg-cover bg-center" style='background-image: url("data:image/webp;base64,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");'>
          <div class="absolute inset-0 bg-black/70"></div>
        </div>
        <div class="relative z-10 flex flex-col items-center justify-center h-full text-center px-4">
          <h1 class="text-4xl md:text-6xl font-bold text-white tracking-tighter mb-4" style="text-shadow: 0 0 15px rgba(17, 147, 212, 0.7);">
            Network Defense: Fortify Your Perimeter
          </h1>
          <p class="text-lg md:text-xl text-gray-300 max-w-3xl mb-8">
            Learn to design and maintain secure network infrastructures against sophisticated attacks.
          </p>
          <button onclick="window.location.href='signup.php'" 
                  class="flex h-12 min-w-[84px] items-center justify-center overflow-hidden rounded-lg bg-primary px-6 text-base font-bold text-white transition-all hover:bg-primary/80 hover:shadow-[0_0_20px_#1193d4]">
            Learn More
          </button>
        </div>
      </div>

    </div>
    <div class="swiper-pagination"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
  </div>
</main>

<!-- 🔹 Footer -->
<footer class="w-full border-t border-primary/20 dark:border-primary/30 py-8 px-4 sm:px-6 lg:px-8 bg-background-dark">
  <div class="mx-auto max-w-5xl text-center">
    <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-4 mb-6">
      <a class="text-sm text-white hover:text-primary" href="about.php">About Us</a>
      <a class="text-sm text-white hover:text-primary" href="contactpage.php">Contact</a>
      <a class="text-sm text-white hover:text-primary" href="privacy-policy.php">Privacy Policy</a>
      <a class="text-sm text-white hover:text-primary" href="terms.php">Terms of Service</a>
    </div>
    <p class="text-sm text-gray-400">© 2024 Hackers Gurukul Blog. All rights reserved.</p>
  </div>
</footer>

</div>

<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script>
var swiper = new Swiper('.swiper-container', {
  loop: true,
  autoplay: { delay: 5000, disableOnInteraction: false },
  effect: 'fade',
  fadeEffect: { crossFade: true },
  pagination: { el: '.swiper-pagination', clickable: true },
  navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
});
</script>
</body>
</html>
