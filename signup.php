<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $mobile = trim($_POST["mobile"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    if ($username == "" || $email == "" || $mobile == "" || $password == "" || $confirm_password == "") {
        $error = "⚠️ All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $error = "⚠️ Passwords do not match!";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "⚠️ Mobile must be 10 digits!";
    } else {
        // ✅ Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $error = "⚠️ Email already exists! Try logging in.";
        } else {
            // ✅ Store password as plain text (no hashing)
            $role = "user";
            $stmt = $conn->prepare("INSERT INTO users (username, email, mobile, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $username, $email, $mobile, $password, $role);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $success = "🎉 Account created successfully! Redirecting...";
                header("refresh:2;url=loginpage.php");
            } else {
                $error = "⚠️ Error signing up. Try again!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Hackers Gurukul Blog - Signup</title>
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
    },
  },
};
</script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-800 dark:text-slate-200">
<div class="flex flex-col min-h-screen">

<!-- 🔹 Navbar -->
<header class="border-b border-slate-200/80 dark:border-slate-800/80 bg-slate-900">
  <div class="container mx-auto px-4 flex justify-between items-center py-4">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"/></svg>
      <span class="text-xl font-bold text-white">Hackers Gurukul Blog</span>
    </a>
    <a href="loginpage.php" class="px-4 py-2 bg-primary text-white rounded-lg">Log In</a>
  </div>
</header>

<!-- 🔹 Signup form -->
<main class="flex-grow flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
  <div class="w-full max-w-md space-y-8">
    <div>
      <h2 class="mt-6 text-center text-3xl font-extrabold text-slate-900 dark:text-white">Create an account</h2>
      <p class="mt-2 text-center text-sm text-slate-600 dark:text-slate-400">Join the Hackers Gurukul community 🚀</p>
    </div>
    <div class="bg-white dark:bg-slate-900/50 p-8 shadow rounded-xl border border-slate-200/80 dark:border-slate-800/80">
      <form id="signupForm" action="" method="POST" class="space-y-6">
        <div>
          <label for="username" class="block text-sm font-medium">Username</label>
          <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required
            class="w-full px-3 py-2 border rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div>
          <label for="email" class="block text-sm font-medium">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required
            class="w-full px-3 py-2 border rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div>
          <label for="mobile" class="block text-sm font-medium">Mobile Number</label>
          <input type="text" id="mobile" name="mobile" value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>" required
            class="w-full px-3 py-2 border rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div>
          <label for="password" class="block text-sm font-medium">Password</label>
          <input type="password" id="password" name="password" required
            class="w-full px-3 py-2 border rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>
        <div>
          <label for="confirm_password" class="block text-sm font-medium">Confirm Password</label>
          <input type="password" id="confirm_password" name="confirm_password" required
            class="w-full px-3 py-2 border rounded-lg bg-slate-50 dark:bg-slate-800 focus:ring-primary"/>
        </div>

        <!-- Error / Success Messages -->
        <?php if(!empty($error)): ?>
          <p class="text-red-500 text-sm"><?php echo $error; ?></p>
        <?php elseif(!empty($success)): ?>
          <p class="text-green-500 text-sm font-bold"><?php echo $success; ?></p>
        <?php endif; ?>

        <button type="submit" class="w-full py-2.5 px-4 rounded-lg text-sm font-bold text-white bg-primary hover:bg-primary/90">
          Sign Up
        </button>
      </form>
    </div>
    <p class="mt-6 text-center text-sm">Already have an account? <a href="loginpage.php" class="text-primary font-bold hover:underline">Log in</a></p>
  </div>
</main>

<?php include 'footer.php'; ?>

</div>
</body>
</html>
