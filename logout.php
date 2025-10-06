<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Bloggr - Logout</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#1193d4",
        "background-light": "#f6f7f8",
        "background-dark": "#101c22",
      },
      fontFamily: { display: ["Newsreader"] },
      borderRadius: { DEFAULT: "0.25rem", lg: "0.5rem", xl: "0.75rem", full: "9999px" },
    },
  },
};
</script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
<div class="flex flex-col min-h-screen">

<!-- 🔹 Navbar -->
<header class="border-b border-slate-200/80 dark:border-slate-800/80">
  <div class="container mx-auto px-4">
    <nav class="flex items-center justify-between py-4">
      <a href="homepage.php" class="flex items-center gap-2">
        <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path></svg>
        <span class="text-xl font-bold text-slate-900 dark:text-white">Bloggr</span>
      </a>
      <div class="hidden md:flex items-center gap-8">
        <a href="homepage.php" class="text-sm font-medium hover:text-primary transition-colors">Home</a>
        <a href="#" class="text-sm font-medium hover:text-primary transition-colors">Categories</a>
        <a href="#" class="text-sm font-medium hover:text-primary transition-colors">About</a>
        <a href="contactpage.php" class="text-sm font-medium hover:text-primary transition-colors">Contact</a>
      </div>
      <a href="loginpage.php" class="hidden md:inline-flex items-center justify-center h-9 px-4 rounded-lg bg-primary text-white text-sm font-bold hover:bg-primary/90">Log In</a>
    </nav>
  </div>
</header>

<!-- 🔹 Main content -->
<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="w-full max-w-md space-y-6 text-center">
    <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">You’ve been logged out</h2>
    <p class="text-slate-600 dark:text-slate-400">Your session has ended. Please log in again to continue.</p>
    <a href="loginpage.php" class="inline-block mt-6 px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90">Go to Login</a>
  </div>
</main>
<?php include 'footer.php'; ?>

</div>
</body>
</html>
