<?php
session_start();
$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

$user_id = $_SESSION['user_id'];
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: user_profile.php");
    exit;
}
$post_id = (int)$_GET['id'];

// Fetch post
$sql = "SELECT * FROM posts WHERE id=? AND author_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
if (!$post) {
    $_SESSION['msg'] = "⚠️ Post not found or you don’t have permission.";
    header("Location: user_profile.php");
    exit;
}

$error = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = trim($_POST['category']);
    $content = trim($_POST['content']);

    $image_url = $post['image_url'];
    $media_url = $post['media_url'];

    if (!empty($_FILES['media']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES['media']['name']);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_url = $target_file;
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            if (in_array($ext, ["jpg","jpeg","png","gif","webp"])) {
                $image_url = $target_file;
            }
        }
    }

    if (!empty($title) && !empty($description) && !empty($category) && !empty($content)) {
        $status = ($_POST['action'] === "draft") ? "draft" : "published";
        $sql = "UPDATE posts 
                SET title=?, description=?, category=?, content=?, image_url=?, media_url=?, status=? 
                WHERE id=? AND author_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssii", $title, $description, $category, $content, $image_url, $media_url, $status, $post_id, $user_id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = ($status === "draft") ? "📝 Draft updated!" : "✅ Post updated & published!";
            header("Location: user_profile.php");
            exit;
        } else {
            $error = "❌ Error updating post.";
        }
    } else {
        $error = "⚠️ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Post - Hackers Gurukul Blog</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Newsreader:wght@400;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
<script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#1193d4",
        "background-dark": "#0e1a20"
      },
      fontFamily: { display: ["Newsreader"] }
    }
  }
};
</script>
</head>
<body class="bg-background-dark text-slate-200 font-display min-h-screen flex flex-col">

<!-- 🔹 Navbar -->
<header class="bg-slate-900 border-b border-slate-800">
  <div class="container mx-auto px-4 flex justify-between items-center h-16">
    <a href="homepage.php" class="flex items-center gap-2">
      <svg class="h-6 w-6 text-primary" fill="none" viewBox="0 0 48 48"><path d="M6 6H42L36 24L42 42H6L12 24L6 6Z" fill="currentColor"></path></svg>
      <span class="text-lg font-bold text-white">Hackers Gurukul Blog</span>
    </a>
    <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg">Logout</a>
  </div>
</header>

<!-- 🔹 Edit Post Form + Preview -->
<main class="flex-grow container mx-auto px-4 py-8 max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-10">
  <div>
    <h1 class="text-3xl font-bold mb-6">✏️ Edit Post</h1>

    <?php if(!empty($error)): ?>
      <p class="text-red-500 mb-4"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" 
          class="space-y-5 bg-slate-900 p-6 rounded-xl shadow border border-slate-800">

      <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($post['title']); ?>" 
             required class="w-full px-4 py-2 border rounded-lg bg-slate-800 text-white"/>

      <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($post['description']); ?>" 
             required class="w-full px-4 py-2 border rounded-lg bg-slate-800 text-white"/>

      <select name="category" id="category" required 
              class="w-full px-4 py-2 border rounded-lg bg-slate-800 text-white">
        <option value="AI" <?php if($post['category']=="AI") echo "selected"; ?>>AI</option>
        <option value="Software" <?php if($post['category']=="Software") echo "selected"; ?>>Software</option>
        <option value="Hardware" <?php if($post['category']=="Hardware") echo "selected"; ?>>Hardware</option>
        <option value="Cybersecurity" <?php if($post['category']=="Cybersecurity") echo "selected"; ?>>Cybersecurity</option>
        <option value="Reviews" <?php if($post['category']=="Reviews") echo "selected"; ?>>Reviews</option>
      </select>

      <textarea name="content" id="content" rows="10"><?php echo htmlspecialchars($post['content']); ?></textarea>

      <input type="file" name="media" id="media" accept="image/*,video/*,.pdf,.doc,.docx"
             class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-primary file:text-white hover:file:bg-blue-600"/>

      <div class="flex gap-3">
        <button type="submit" name="action" value="publish" 
                class="w-full py-2 bg-primary text-white rounded-lg">💾 Save & Publish</button>
        <button type="submit" name="action" value="draft" 
                class="w-full py-2 bg-slate-600 text-white rounded-lg">📝 Save as Draft</button>
      </div>
    </form>
  </div>

  <!-- 🔹 Live Preview -->
  <div>
    <h2 class="text-2xl font-bold mb-4">🔍 Live Preview</h2>
    <div id="previewCard" class="bg-slate-900 p-5 rounded-lg shadow border border-slate-800 space-y-3">
      <h3 id="previewTitle" class="text-xl font-bold"><?php echo htmlspecialchars($post['title']); ?></h3>
      <p id="previewCategory" class="text-sm text-slate-400">Category: <?php echo htmlspecialchars($post['category']); ?></p>
      <p id="previewDescription" class="text-slate-300"><?php echo htmlspecialchars($post['description']); ?></p>
      <div id="previewMedia">
        <?php if($post['image_url']): ?>
          <img src="<?php echo $post['image_url']; ?>" class="w-full rounded-lg"/>
        <?php endif; ?>
      </div>
      <div id="previewContent" class="prose dark:prose-invert"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
    </div>
  </div>
</main>
</div>

<!-- 🔹 JS for Markdown + Preview -->
<script>
let easyMDE = new EasyMDE({ element: document.getElementById("content"), spellChecker: false });

function updatePreview() {
  document.getElementById("previewTitle").innerText = document.getElementById("title").value;
  document.getElementById("previewDescription").innerText = document.getElementById("description").value;
  document.getElementById("previewCategory").innerText = "Category: " + document.getElementById("category").value;
  document.getElementById("previewContent").innerHTML = easyMDE.markdown(easyMDE.value());
}

["title","description","category"].forEach(id => {
  document.getElementById(id).addEventListener("input", updatePreview);
});
easyMDE.codemirror.on("change", updatePreview);

document.getElementById("media").addEventListener("change", function(event){
  const file = event.target.files[0];
  const preview = document.getElementById("previewMedia");
  preview.innerHTML = "";
  if(file){
    const ext = file.name.split('.').pop().toLowerCase();
    if(["jpg","jpeg","png","gif","webp"].includes(ext)){
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<img src="${e.target.result}" class="w-full rounded-lg"/>`; };
      reader.readAsDataURL(file);
    } else if(["mp4","webm","ogg"].includes(ext)){
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = `<video controls class="w-full rounded-lg"><source src="${e.target.result}" type="video/${ext}"></video>`; };
      reader.readAsDataURL(file);
    } else {
      preview.innerHTML = `<p class="text-primary">📂 File ready: ${file.name}</p>`;
    }
  }
});
</script>
</body>
</html>
