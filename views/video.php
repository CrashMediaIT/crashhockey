<?php
/**
 * Video Library
 * Upload, view, and review training videos
 */

require_once __DIR__ . '/../security.php';

$is_coach = ($user_role === 'coach' || $user_role === 'coach_plus' || $user_role === 'admin');
$is_athlete = ($user_role === 'athlete');

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'my_videos';

// Build query based on role and filter
if ($is_coach) {
    if ($filter === 'review_pending') {
        $query = "
            SELECT v.*, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM video_notes WHERE video_id = v.id) as note_count
            FROM videos v
            INNER JOIN users u ON v.uploader_id = u.id
            WHERE u.assigned_coach_id = ? AND v.review_requested = 1 AND v.reviewed = 0
            ORDER BY v.created_at DESC
        ";
        $params = [$user_id];
    } else {
        $query = "
            SELECT v.*, u.first_name, u.last_name,
                   (SELECT COUNT(*) FROM video_notes WHERE video_id = v.id) as note_count
            FROM videos v
            INNER JOIN users u ON v.uploader_id = u.id
            WHERE u.assigned_coach_id = ?
            ORDER BY v.created_at DESC
        ";
        $params = [$user_id];
    }
} else {
    $query = "
        SELECT v.*, 
               (SELECT COUNT(*) FROM video_notes WHERE video_id = v.id) as note_count
        FROM videos v
        WHERE v.uploader_id = ? OR v.assigned_to_user_id = ?
        ORDER BY v.created_at DESC
    ";
    $params = [$user_id, $user_id];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$videos = $stmt->fetchAll();
?>

<style>
    :root {
        --primary: #7000a4;
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    .page-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
    }
    .btn-upload {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
    }
    .btn-upload:hover {
        background: #e64500;
    }
    .filters-bar {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    .filter-btn {
        background: transparent;
        border: 1px solid #1e293b;
        color: #94a3b8;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.2s;
    }
    .filter-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: #fff;
    }
    .videos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    .video-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s;
    }
    .video-card:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }
    .video-thumbnail {
        width: 100%;
        height: 200px;
        background: #06080b;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 48px;
    }
    .video-content {
        padding: 20px;
    }
    .video-title {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
    }
    .video-meta {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
    }
    .video-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 15px 0;
    }
    .video-badge {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid #1e293b;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
    }
    .video-badge.review {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #fff;
    }
    .video-badge.reviewed {
        background: #10b981;
        border-color: #10b981;
        color: #fff;
    }
    .btn-view {
        width: 100%;
        padding: 10px;
        background: var(--primary);
        color: #fff;
        text-align: center;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        display: block;
        transition: all 0.2s;
    }
    .btn-view:hover {
        background: #e64500;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
    }
    .empty-state i {
        font-size: 64px;
        color: #64748b;
        opacity: 0.3;
        margin-bottom: 20px;
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-video"></i> Video Library
    </h1>
    <a href="?page=video&action=upload" class="btn-upload">
        <i class="fas fa-upload"></i> Upload Video
    </a>
</div>

<?php if ($is_coach): ?>
<div class="filters-bar">
    <button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>" 
            onclick="window.location.href='?page=video&filter=all'">
        All Videos
    </button>
    <button class="filter-btn <?= $filter === 'review_pending' ? 'active' : '' ?>" 
            onclick="window.location.href='?page=video&filter=review_pending'">
        Review Pending
    </button>
</div>
<?php endif; ?>

<?php if (empty($videos)): ?>
    <div class="empty-state">
        <i class="fas fa-video-slash"></i>
        <h2 style="font-size: 24px; color: #fff; margin-bottom: 10px;">No Videos Yet</h2>
        <p style="color: #64748b;">Upload your first training video to get started</p>
    </div>
<?php else: ?>
    <div class="videos-grid">
        <?php foreach ($videos as $video): ?>
            <div class="video-card">
                <div class="video-thumbnail">
                    <i class="fas fa-play-circle"></i>
                </div>
                
                <div class="video-content">
                    <h3 class="video-title"><?= htmlspecialchars($video['title']) ?></h3>
                    
                    <?php if ($is_coach && isset($video['first_name'])): ?>
                        <div class="video-meta">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($video['first_name'] . ' ' . $video['last_name']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="video-meta">
                        <i class="fas fa-calendar"></i>
                        <?= date('M d, Y', strtotime($video['created_at'])) ?>
                    </div>
                    
                    <?php if ($video['video_type']): ?>
                        <div class="video-meta">
                            <i class="fas fa-tag"></i>
                            <?= htmlspecialchars($video['video_type']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="video-badges">
                        <?php if ($video['review_requested'] && !$video['reviewed']): ?>
                            <span class="video-badge review">REVIEW REQUESTED</span>
                        <?php elseif ($video['reviewed']): ?>
                            <span class="video-badge reviewed">REVIEWED</span>
                        <?php endif; ?>
                        
                        <?php if ($video['note_count'] > 0): ?>
                            <span class="video-badge">
                                <i class="fas fa-comment"></i> <?= $video['note_count'] ?> Notes
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <a href="?page=video&action=view&id=<?= $video['id'] ?>" class="btn-view">
                        <i class="fas fa-play"></i> View Video
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
