<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$msg = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if ($name !== "" && $email !== "" && $subject !== "" && $message !== "") {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = "⚠️ Please enter a valid email address.";
        } else {
            // ✅ Use 'contact_messages' table to match admin panel
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $name, $email, $subject, $message);
            if ($stmt->execute()) {
                $msg = "✅ Your message has been sent successfully!";
            } else {
                $msg = "❌ Error sending message. Please try again.";
            }
            $stmt->close();
        }
    } else {
        $msg = "⚠️ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Contact Us - Hackers Gurukul</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
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
    },
  },
};
</script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
<div class="relative flex min-h-screen w-full flex-col">

<!-- 🔹 Navbar -->
<header class="sticky top-0 z-10 w-full bg-background-light/80 backdrop-blur-sm dark:bg-background-dark/80">
  <div class="container mx-auto flex items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3">
      <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 42.4379C4 42.4379 14.0962 36.0744 24 41.1692C35.0664 46.8624 44 42.2078 44 42.2078L44 7.01134C44 7.01134 35.068 11.6577 24.0031 5.96913C14.0971 0.876274 4 7.27094 4 7.27094L4 42.4379Z" fill="currentColor"/>
      </svg>
      <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Hackers Gurukul Blog</h2>
    </div>
    <nav class="hidden items-center gap-8 md:flex">
      <a href="homepage.php" class="hover:text-primary">Home</a>
      <a href="categories.php" class="hover:text-primary">Categories</a>
      <a href="about.php" class="hover:text-primary">About</a>
      <a href="contactpage.php" class="text-primary font-semibold">Contact</a>
    </nav>
  </div>
</header>

<main class="flex-grow">
  <div class="container mx-auto px-4 py-12 sm:px-6 lg:px-8">
    <div class="grid grid-cols-1 gap-16 lg:grid-cols-2">
      
      <!-- Contact Form -->
      <div class="flex flex-col">
        <div class="mb-8">
          <h1 class="text-4xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-5xl">Get in Touch</h1>
          <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">We're here to help and answer any questions you might have.</p>
        </div>

        <?php if(!empty($msg)): ?>
          <p id="msgAlert" class="mb-4 text-center font-semibold 
            <?php echo (strpos($msg, '✅')!==false) ? 'text-green-500' : 'text-red-500'; ?>">
            <?php echo $msg; ?>
          </p>
          <script>
            setTimeout(()=>{ document.getElementById("msgAlert").style.display="none"; }, 4000);
          </script>
        <?php endif; ?>

        <form method="POST" class="w-full space-y-6">
          <div>
            <label class="block text-sm font-medium">Your Name</label>
            <input name="name" type="text" required class="form-input block w-full rounded-lg border-slate-300 bg-white py-3 px-4 dark:bg-slate-800 dark:text-white"/>
          </div>
          <div>
            <label class="block text-sm font-medium">Your Email</label>
            <input name="email" type="email" required class="form-input block w-full rounded-lg border-slate-300 bg-white py-3 px-4 dark:bg-slate-800 dark:text-white"/>
          </div>
          <div>
            <label class="block text-sm font-medium">Subject</label>
            <input name="subject" type="text" required class="form-input block w-full rounded-lg border-slate-300 bg-white py-3 px-4 dark:bg-slate-800 dark:text-white"/>
          </div>
          <div>
            <label class="block text-sm font-medium">Your Message</label>
            <textarea name="message" rows="4" required class="form-textarea block w-full rounded-lg border-slate-300 bg-white py-3 px-4 dark:bg-slate-800 dark:text-white"></textarea>
          </div>
          <button type="submit" class="w-full rounded-lg bg-primary py-3 px-6 font-bold text-white hover:opacity-90">
            Send Message
          </button>
        </form>
      </div>

      <!-- Contact Info -->
      <div class="flex flex-col">
        <div class="mb-8 space-y-6 rounded-xl bg-white p-8 shadow-lg dark:bg-slate-800/50">
          <h2 class="text-3xl font-bold">Contact Information</h2>
          <div class="space-y-4">
            <div class="flex items-start gap-4">
              <span class="material-symbols-outlined mt-1 text-primary"> mail </span>
              <div>
                <h3 class="font-semibold">Email</h3>
                <p>hackersgurukul@gmail.com</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <span class="material-symbols-outlined mt-1 text-primary"> call </span>
              <div>
                <h3 class="font-semibold">Phone</h3>
                <p>9876543210</p>
              </div>
            </div>
            <div class="flex items-start gap-4">
              <span class="material-symbols-outlined mt-1 text-primary"> location_on </span>
              <div>
                <h3 class="font-semibold">Address</h3>
                <p>123 Tech Lane, Blogville, 98765</p>
              </div>
            </div>
          </div>
        </div>
        <div class="h-full min-h-[300px] w-full flex-grow rounded-xl bg-cover bg-center shadow-lg"
             style='background-image: url("https://maps.googleapis.com/maps/api/staticmap?center=San+Francisco&zoom=12&size=600x300&key=YOUR_API_KEY");'>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- 🔹 Footer -->
<footer class="bg-white dark:bg-slate-900">
  <div class="container mx-auto px-4 py-8 sm:px-6 lg:px-8 flex justify-between">
    <p class="text-sm text-slate-600 dark:text-slate-400">© 2025 HG Blog. All rights reserved.</p>
  </div>
</footer>
</div>
</body>
</html>
