<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { echo json_encode(['error'=>'DB failed']); exit; }

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error'=>'not_logged_in']);
    exit;
}

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(['error'=>'invalid_post']);
    exit;
}

$post_id = (int)$_POST['post_id'];
$user_id = $_SESSION['user_id'];

// Toggle like
$check = $conn->prepare("SELECT id FROM likes WHERE post_id=? AND user_id=?");
$check->bind_param("ii", $post_id, $user_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $conn->query("DELETE FROM likes WHERE post_id=$post_id AND user_id=$user_id");
    $liked = false;
} else {
    $ins = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
    $ins->bind_param("ii", $post_id, $user_id);
    $ins->execute();
    $liked = true;
}

// Count
$count = $conn->query("SELECT COUNT(*) AS total FROM likes WHERE post_id=$post_id")->fetch_assoc()['total'];
echo json_encode(['liked'=>$liked,'likes'=>$count]);
