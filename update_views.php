<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "hackers_gurukul_blog");
if ($conn->connect_error) { echo json_encode(['total'=>0,'active'=>0]); exit; }

if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
    echo json_encode(['total'=>0,'active'=>0]);
    exit;
}

$post_id = (int)$_POST['post_id'];
$session_id = session_id();

// Update timestamp for this viewer
$conn->query("UPDATE views SET last_seen = NOW() WHERE post_id=$post_id AND session_id='$session_id'");

// Remove old viewers (inactive > 1 min)
$conn->query("DELETE FROM views WHERE last_seen < NOW() - INTERVAL 1 MINUTE");

// Count total & active
$total = $conn->query("SELECT COUNT(*) AS c FROM views WHERE post_id=$post_id")->fetch_assoc()['c'];
$active = $conn->query("SELECT COUNT(*) AS c FROM views WHERE post_id=$post_id")->fetch_assoc()['c'];

echo json_encode(['total'=>$total,'active'=>$active]);
