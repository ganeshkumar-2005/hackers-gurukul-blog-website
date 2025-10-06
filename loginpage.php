<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 1) {
        $user = $res->fetch_assoc();
        if ($password === $user['password']) { // ⚠️ plain password (no hashing)
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role']; // save role
            $_SESSION['username'] = $user['username'];

            // ✅ Redirect all users (admin & normal) to homepage.php
            header("Location: homepage.php");
            exit;
        } else { 
            $error = "Invalid password!"; 
        }
    } else { 
        $error = "No account found with this email!"; 
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Hackers Gurukul Blog - Login</title>
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
        <span class="text-xl font-bold text-slate-900 dark:text-white">Hackers Gurukul Blog</span>
      </a>
      <div class="hidden md:flex items-center gap-8">
        <a href="homepage.php" class="text-sm font-medium hover:text-primary transition-colors">Home</a>
        <a href="categories.php" class="text-sm font-medium hover:text-primary transition-colors">Categories</a>
        <a href="#" class="text-sm font-medium hover:text-primary transition-colors">About</a>
        <a href="contactpage.php" class="text-sm font-medium hover:text-primary transition-colors">Contact</a>
      </div>
      <a href="signup.php" class="hidden md:inline-flex items-center justify-center h-9 px-4 rounded-lg bg-primary text-white text-sm font-bold hover:bg-primary/90">Sign Up</a>
    </nav>
  </div>
</header>

<!-- 🔹 Main login form -->
<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="w-full max-w-md space-y-8">
    <div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900 dark:text-white">Welcome back</h2>
      <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">Sign in to continue to your dashboard.</p>
    </div>
    <div class="bg-white dark:bg-slate-900/50 p-8 shadow rounded-xl border border-slate-200/80 dark:border-slate-800/80">
      <form id="loginForm" action="" method="POST" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium">Email</label>
          <input type="email" id="email" name="email" required
            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input type="password" id="password" name="password" required
            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" class="h-4 w-4 text-primary border-slate-300 dark:border-slate-600 rounded"/> Remember me
          </label>
          <a href="forgot_password.php" class="text-sm text-primary hover:underline">Forgot Password?</a>
        </div>
        <?php if(!empty($error)): ?>
          <p class="text-red-500 text-sm"><?php echo $error; ?></p>
        <?php endif; ?>
        <button type="submit" class="w-full py-2.5 px-4 rounded-lg text-sm font-bold text-white bg-primary hover:bg-primary/90">Log In</button>
      </form>
    </div>
    <p class="mt-6 text-center text-sm">Don’t have an account? <a href="signup.php" class="text-primary font-bold hover:underline">Sign up</a></p>
  </div>
</main>

<!-- 🔹 Footer -->
<?php include 'footer.php'; ?>

</div>

<!-- JS validation -->
<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();
    if(email === "" || password === ""){
        e.preventDefault();
        alert("Please fill in all fields!");
    }
});
</script>
</body>
</html>
