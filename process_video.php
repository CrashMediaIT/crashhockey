<?php
session_start();
require 'db_config.php';

// Security Check
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'coach')) {
    header("Location: dashboard.php"); exit();
}

// === DELETE ACTION ===
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];
    
    // Get file path to unlink if it's a file
    $stmt = $pdo->prepare("SELECT file_path, video_type FROM videos WHERE id = ?");
    $stmt->execute([$id]);
    $vid = $stmt->fetch();
    
    if ($vid && $vid['video_type'] == 'file' && file_exists($vid['file_path'])) {
        unlink($vid['file_path']); // Delete file from server
    }
    
    $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$id]);
    header("Location: dashboard.php?page=video&status=deleted");
    exit();
}

// === UPLOAD ACTION ===
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $uploader = $_SESSION['user_id'];
    $target   = empty($_POST['assigned_to']) ? NULL : $_POST['assigned_to'];
    $title    = trim($_POST['title']);
    $desc     = trim($_POST['description']);
    $type     = $_POST['source_type'];
    $path     = "";

    if ($type == 'youtube') {
        // Extract YouTube ID using Regex
        $url = $_POST['youtube_url'];
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        
        if (isset($match[1])) {
            $path = $match[1]; // The ID (e.g. dQw4w9WgXcQ)
        } else {
            // Fallback: just save raw URL, but embed might fail
            $path = $url; 
        }

    } else {
        // Handle File Upload
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] == 0) {
            $allowed = ['mp4', 'mov', 'avi'];
            $filename = $_FILES['video_file']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                // Ensure 'uploads' folder exists
                if (!is_dir('uploads')) { mkdir('uploads'); }
                
                $new_name = "uploads/" . uniqid() . "." . $ext;
                
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $new_name)) {
                    $path = $new_name;
                } else {
                    die("Failed to move uploaded file.");
                }
            } else {
                die("Invalid file type. Only MP4, MOV allowed.");
            }
        } else {
            // Error uploading
            header("Location: dashboard.php?page=video&error=upload_failed");
            exit();
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO videos (uploader_id, assigned_to_user_id, title, description, video_type, file_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$uploader, $target, $title, $desc, $type, $path]);
        
        header("Location: dashboard.php?page=video&status=uploaded");
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>