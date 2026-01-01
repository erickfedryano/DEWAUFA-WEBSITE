<?php
require_once __DIR__ . "/config/db.php";

$destinations = [
    'waterfalls' => [],
    'sunset'     => [],
    'sunrise'    => []
];

$search = trim($_GET['search'] ?? '');      // ambil keyword dari URL (form GET)
$searchSql = '';
if ($search !== '') {
    $searchEscaped = $conn->real_escape_string($search);
    $searchSql = " AND d.title LIKE '%{$searchEscaped}%'";
}

$res = $conn->query("
    SELECT 
        d.id,
        d.user_id,
        u.username,
        d.title,
        d.location,
        d.image,
        d.description,
        d.highlight1,
        d.highlight2,
        d.highlight3,
        d.highlight4,
        d.best_time,
        d.duration,
        d.rating,
        d.category,
        d.created_at
    FROM destinations d
    LEFT JOIN users u ON u.id = d.user_id
    WHERE d.status = 'approved'
    ORDER BY d.id DESC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $cat = $row['category']; // di DB: 'waterfall','sunset','sunrise'
        if ($cat === 'waterfall' || $cat === 'waterfalls') {
            $destinations['waterfalls'][] = $row;
        } elseif ($cat === 'sunset') {
            $destinations['sunset'][] = $row;
        } elseif ($cat === 'sunrise') {
            $destinations['sunrise'][] = $row;
        }
    }
}



$drafts = [];
$userId = (int)($_SESSION['user_id'] ?? 0);

$res = $conn->query("
    SELECT 
        d.id,
        d.user_id,
        u.username,
        d.title,
        d.location,
        d.image,
        d.description,
        d.category,
        d.status,
        d.reject_reason,
        d.created_at
    FROM destinations d
    LEFT JOIN users u ON u.id = d.user_id
    WHERE d.user_id = {$userId}
      AND d.status IN ('pending', 'rejected')
    ORDER BY d.created_at DESC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $drafts[] = $row;
    }
}



/* ====== SAVE DRAFT (INSERT) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'save_draft') {

    header('Content-Type: application/json');

    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid (JSON tidak terbaca).']);
        exit;
    }

    $title       = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category    = $data['category'] ?? '';
    $location    = trim($data['location'] ?? '');
    $image       = trim($data['image'] ?? '');
    $bestTime    = trim($data['bestTime'] ?? '');
    $duration    = trim($data['duration'] ?? '');
    $highlights  = $data['highlights'] ?? [];

    $userId = $_SESSION['user_id'] ?? null;

    if ($title === '' || $description === '' || $category === '') {
        echo json_encode(['success' => false, 'message' => 'Nama, kategori, dan deskripsi wajib diisi.']);
        exit;
    }

    if (!in_array($category, ['waterfalls','sunset','sunrise','waterfall'], true)) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak valid: ' . $category]);
        exit;
    }

    // Normalisasi kategori ke nilai di DB
    $categoryDb = $category === 'waterfalls' ? 'waterfall' : $category;

    if ($image === '') {
        $image = 'assets/img/default_destination.jpg';
    }

    $views    = 0;
    $comments = 0;
    $rating   = 0.0;
    $status   = 'pending';

    // Pecah highlights ke kolom masing-masing
    $h1 = $highlights[0] ?? '';
    $h2 = $highlights[1] ?? '';
    $h3 = $highlights[2] ?? '';
    $h4 = $highlights[3] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO destinations 
        (user_id, title, location, description, image, category, best_time, duration, highlight1, highlight2, highlight3, highlight4, views, comments, rating, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

    $stmt->bind_param(
        "isssssssssssiids",
        $userId,
        $title,
        $location,
        $description,
        $image,
        $categoryDb,
        $bestTime,
        $duration,
        $h1,
        $h2,
        $h3,
        $h4,
        $views,
        $comments,
        $rating,
        $status
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Draft berhasil disimpan, menunggu persetujuan admin.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}


/* ====== UPDATE DRAFT (EDIT) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'update_draft') {

    header('Content-Type: application/json');
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);

    $id          = (int)($data['id'] ?? 0);
    $title       = trim($data['name'] ?? '');
    $description = trim($data['description'] ?? '');
    $category    = $data['category'] ?? '';
    $location    = trim($data['location'] ?? '');
    $image       = trim($data['image'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID draft tidak valid.']);
        exit;
    }

    if (!in_array($category, ['waterfalls','sunset','sunrise','waterfall'], true)) {
        echo json_encode(['success' => false, 'message' => 'Kategori tidak valid: ' . $category]);
        exit;
    }

    $categoryDb = $category === 'waterfalls' ? 'waterfall' : $category;
    if ($image === '') {
        $image = 'assets/img/default_destination.jpg';
    }

    $stmt = $conn->prepare(
        "UPDATE destinations
         SET title=?, location=?, description=?, image=?, category=?
         WHERE id=? AND status IN ('pending','rejected')"
    );
    $stmt->bind_param("sssssi", $title, $location, $description, $image, $categoryDb, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Draft berhasil diupdate.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update database: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}
/* ====== DELETE DRAFT (HAPUS) ====== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'delete_draft') {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $id = (int)($data['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID draft tidak valid.']);
        exit;
    }

    // izinkan hapus pending dan rejected
    $stmt = $conn->prepare("DELETE FROM destinations WHERE id=? AND status IN ('pending','rejected')");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Draft berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus draft: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'add_comment') {

    // form biasa, bukan JSON
    header('Content-Type: application/json');

    $destinationId = (int)($_POST['destination_id'] ?? 0);
    $text          = trim($_POST['text'] ?? '');
    $userName      = $_SESSION['username'] ?? 'Guest'; // sesuaikan nama field login

    if ($destinationId <= 0 || $text === '') {
        echo json_encode(['success' => false, 'message' => 'Komentar atau destinasi tidak valid.']);
        exit;
    }

    // 1) Simpan komentar dulu
    $stmt = $conn->prepare(
    "INSERT INTO comments (destination_id, user_id, user_name, comment_text)
     VALUES (?,?,?,?)"
);
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['username'] ?? 'Guest';
$stmt->bind_param("iiss", $destinationId, $userId, $userName, $text);


    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan komentar: ' . $stmt->error]);
        $stmt->close();
        exit;
    }

    $commentId = $stmt->insert_id;
    $stmt->close();

    // 2) Upload file gambar (bisa banyak)
    $savedImages = [];

if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $uploadDir = __DIR__ . '/uploads/comments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // batasi maksimal 4 file
    $fileCount = min(4, count($_FILES['images']['name']));

    $stmtImg = $conn->prepare(
        "INSERT INTO comment_images (comment_id, image_path) VALUES (?,?)"
    );

    for ($i = 0; $i < $fileCount; $i++) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

        $tmpName  = $_FILES['images']['tmp_name'][$i];
        $origName = basename($_FILES['images']['name'][$i]);
        $ext      = pathinfo($origName, PATHINFO_EXTENSION);

        $safeName   = uniqid('cmt_', true) . '.' . strtolower($ext);
        $targetPath = $uploadDir . $safeName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            $relativePath = 'uploads/comments/' . $safeName;
            $savedImages[] = $relativePath;

            $stmtImg->bind_param("is", $commentId, $relativePath);
            $stmtImg->execute();
        }
    }
    $stmtImg->close();
}


    echo json_encode([
        'success' => true,
        'message' => 'Komentar tersimpan.',
        'comment' => [
            'id'      => $commentId,
            'user'    => $userName,
            'time'    => 'Baru saja',
            'text'    => $text,
            'images'  => $savedImages
        ]
    ]);
    exit;
}
$commentsByDestination = [];

$sql = "
    SELECT c.id, c.destination_id, c.user_name, c.comment_text, c.created_at,
           ci.image_path
    FROM comments c
    LEFT JOIN comment_images ci ON ci.comment_id = c.id
    ORDER BY c.created_at ASC, ci.id ASC
";
$resC = $conn->query($sql);

if ($resC) {
    while ($row = $resC->fetch_assoc()) {
        $dId = (int)$row['destination_id'];
        $cId = (int)$row['id'];

        if (!isset($commentsByDestination[$dId])) {
            $commentsByDestination[$dId] = [];
        }

        // cari index comment ini di array, atau buat baru
        if (empty($commentsByDestination[$dId]) ||
            end($commentsByDestination[$dId])['id'] !== $cId) {

            $commentsByDestination[$dId][] = [
                'id'     => $cId,
                'user'   => $row['user_name'],
                'time'   => $row['created_at'],
                'text'   => $row['comment_text'],
                'images' => $row['image_path'] ? [$row['image_path']] : []
            ];
        } else {
            $lastIndex = count($commentsByDestination[$dId]) - 1;
            if ($row['image_path']) {
                $commentsByDestination[$dId][$lastIndex]['images'][] = $row['image_path'];
            }
        }
    }
}


$commentsJson = json_encode($commentsByDestination, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEWASUFA - Bali Nature Explorer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .hero-bg {
            background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/home.jpg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-blue-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10">
                        <img src="assets/img/logodewasufa.png" alt="Logo" class="w-full h-full object-cover rounded-full">
                    </div>
                    <span class="text-2xl font-bold text-gray-800">DEWASUFA</span>
                </div>
                <div class="flex items-center gap-6">
                    <button onclick="openCreateModal()" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <span>+</span>
                        Add New
                    </button>
                    <button onclick="handleLogout()" class="flex items-center gap-2 bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-all">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div id="mainView" class="min-h-screen">
        <div class="hero-bg h-80 flex flex-col items-center justify-center text-white">
            <h1 class="text-5xl font-bold mb-4">Explore The Natural Beauty of Bali</h1>
            <p class="text-xl mb-8">Discover the best nature destinations based on travelers' experiences</p>
            <div class="relative w-full max-w-2xl px-4">
                <input id="searchInput" type="text" placeholder="Cari destinasi..." class="w-full px-6 py-4 pr-14 rounded-full text-gray-800 shadow-lg focus:outline-none focus:ring-4 focus:ring-green-300" onkeypress="handleSearch(event)">
                <button onclick="performSearch()" class="absolute right-10 top-1/2 transform -translate-y-1/2 bg-green-600 text-white px-3 py-2 rounded-full hover:bg-green-700 transition-all">
                    🔍
                </button>
            </div>
        </div>

        <!-- Categories -->
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="flex gap-4 mb-8">
                <button onclick="changeCategory('all')" id="btn-all" class="category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-green-600 text-white shadow-lg scale-105">
                    <span class="text-2xl">🌏</span>
                    <span>All</span>
                </button>
                <button onclick="changeCategory('waterfalls')" id="btn-waterfalls" class="category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-white text-gray-700 hover:bg-gray-100">
                    <span class="text-2xl">🌊</span>
                    <span>Waterfall</span>
                </button>
                <button onclick="changeCategory('sunset')" id="btn-sunset" class="category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-white text-gray-700 hover:bg-gray-100">
                    <span class="text-2xl">🌅</span>
                    <span>Sunset Beach</span>
                </button>
                <button onclick="changeCategory('sunrise')" id="btn-sunrise" class="category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-white text-gray-700 hover:bg-gray-100">
                    <span class="text-2xl">🌄</span>
                    <span>Sunrise Beach</span>
                </button>
                <button onclick="changeCategory('draft')" id="btn-draft" class="category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-white text-gray-700 hover:bg-gray-100">
                    <span class="text-2xl">📝</span>
                    <span>Draft</span>
                    <span id="draftCount" class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">0</span>
                </button>
            </div>

            <!-- Destinations Grid -->
            <div id="destinationsGrid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
        </div>
    </div>

        <!-- Detail View -->
    <div id="detailView" class="hidden min-h-screen">
        <div class="max-w-6xl mx-auto p-6">
            <button onclick="closeDetail()" class="flex items-center gap-2 text-green-700 hover:text-green-900 mb-6">
                <span>←</span>
                <span>Kembali</span>
            </button>

            <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
                <div class="grid md:grid-cols-2 gap-8 p-8">
                    <div>
                        <img id="detailImage" src="" alt="" class="w-full h-80 object-cover rounded-2xl shadow-lg">
                        <h1 id="detailName" class="text-3xl font-bold text-gray-800 mb-2 mt-6"></h1>

                        <!-- Tambahan uploader -->
                        <p id="detailUploader" class="text-xs text-gray-400 mb-2"></p>

                        <div class="flex items-center gap-2 text-gray-600 mb-4">
                            <span class="text-green-600">📍</span>
                            <span id="detailLocation"></span>
                        </div>
                        
                        <div class="flex items-center gap-6 mb-6">
                            <div class="flex items-center gap-2 bg-green-100 px-4 py-2 rounded-full">
                                <span class="text-green-600">🕐</span>
                                <span id="detailDuration" class="text-sm font-medium"></span>
                            </div>
                            <div id="detailRatingDiv" class="flex items-center gap-2 bg-yellow-100 px-4 py-2 rounded-full">
                                <span class="text-yellow-600">⭐</span>
                                <span id="detailRating" class="text-sm font-bold"></span>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-xl">
                            <h3 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                                <span class="text-blue-600">🕐</span>
                                Waktu Terbaik
                            </h3>
                            <p id="detailBestTime" class="text-gray-700"></p>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Description</h2>
                        <p id="detailDescription" class="text-gray-600 leading-relaxed mb-6"></p>

                        <h2 class="text-xl font-bold text-gray-800 mb-4">Highlight</h2>
                        <div id="detailHighlights" class="grid grid-cols-2 gap-3 mb-6"></div>

                        <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                            Comment
                        </h2>
                        
                        <div id="commentsList" class="space-y-4 mb-4 max-h-60 overflow-y-auto"></div>

                        <form id="commentForm" class="space-y-3" enctype="multipart/form-data">
                            <input type="hidden" name="destination_id" id="commentDestinationId">

                            <div id="commentImagePreview" class="hidden">
                                <div class="flex flex-wrap gap-2"></div>
                            </div>

                            <div class="flex gap-2">
                                <input id="commentInput" name="text" type="text"
                                    placeholder="Tulis komentar..."
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500">

                                <label class="px-4 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 cursor-pointer flex items-center justify-center">
                                    <span class="text-xl">📷</span>
                                    <input id="commentImageInput" name="images[]" type="file"
                                        accept="image/*" multiple
                                        onchange="handleCommentImageUpload()" class="hidden">
                                </label>

                                <button type="button" onclick="addComment()"
                                        class="px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700">
                                    Kirim
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- PENUTUP detailView -->

    <!-- Create Modal -->
    <div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800">Add New Destination</h2>
                <button onclick="closeCreateModal()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
            </div>

            <div class="space-y-4">
                <input id="inputName" type="text" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Destination Name">
                <input id="inputLocation" type="text" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Location">
                
                <select id="inputCategory" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 bg-white">
                    <option value="" disabled selected>Choose Category</option>
                    <option value="waterfalls">🌊 Waterfall</option>
                    <option value="sunset">🌅 Sunset Beach</option>
                    <option value="sunrise">🌄 Sunrise Beach</option>
                </select>
                
                <div>
                    <label class="flex items-center gap-2 px-4 py-3 bg-green-100 text-green-700 rounded-xl cursor-pointer hover:bg-green-200 w-fit">
                        <span>Choose Photo</span>
                        <input id="inputImage" type="file" accept="image/*" onchange="handleImageUpload()" class="hidden">
                    </label>
                    <img id="previewImage" src="" alt="" class="hidden h-20 w-20 object-cover rounded-lg mt-2">
                </div>

                <textarea id="inputDescription" rows="4" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Description"></textarea>

                <input id="inputHighlight1" type="text" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Highlight 1">
                <input id="inputHighlight2" type="text" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Highlight 2">
                <input id="inputHighlight3" type="text" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Highlight 3">
                <input id="inputHighlight4" type="text" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Highlight 4">

                <div class="grid grid-cols-2 gap-4">
                    <input id="inputBestTime" type="text" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Best Time">
                    <input id="inputDuration" type="text" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500" placeholder="Duration">
                </div>

                <button onclick="createPost()" class="w-full bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700">
                    Publish Destination
                </button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="text-center"></div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Konfirmasi Logout</h2>
            <p class="text-gray-600 mb-6">Apakah Anda yakin ingin keluar dari akun Anda?</p>
            
            <div class="flex gap-3">
                <button onclick="cancelLogout()" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                    Batal
                </button>
                <button onclick="confirmLogout()" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all">
                    Ya, Logout
                </button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="text-center"></div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Berhasil!</h2>
            <p id="successMessage" class="text-gray-600 mb-6"></p>
            
            <button onclick="closeSuccessModal()" class="w-full px-6 py-3 bg-green-600 text-white rounded-xl font-semibold hover:bg-green-700 transition-all">
                OK
            </button>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="text-center"></div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Hapus Draft?</h2>
            <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus draft ini? Tindakan ini tidak dapat dibatalkan.</p>
            
            <div class="flex gap-3">
                <button onclick="cancelDelete()" class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                    Batal
                </button>
                <button onclick="confirmDelete()" class="flex-1 px-6 py-3 bg-red-500 text-white rounded-xl font-semibold hover:bg-red-600 transition-all">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>

    
    <script>
        let currentCategory = 'all';
        let selectedDestination = null;
        let uploadedImage = '';
        let commentImages = [];
        let searchQuery = '';
        let deleteTargetId = null;
        let editingDraftId = null;


        const destinations = {
    waterfalls: <?= json_encode($destinations['waterfalls'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>.map(d => ({
        id:           parseInt(d.id),
        userId:       d.user_id,
        userName:     d.username,
        name:         d.title,
        location:     d.location,
        image:        d.image,
        rating:       d.rating ? parseFloat(d.rating) : 0,
        description:  d.description,
        highlights:   [d.highlight1, d.highlight2, d.highlight3, d.highlight4].filter(Boolean),
        bestTime:     d.best_time || '',
        duration:     d.duration || '',
        comments:     [],
        commentsCount: d.comments ? parseInt(d.comments) : 0,
        createdAt:    d.created_at,
        status:       'approved'
    })),

    sunset: <?= json_encode($destinations['sunset'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>.map(d => ({
        id:           parseInt(d.id),
        userId:       d.user_id,
        userName:     d.username,
        name:         d.title,
        location:     d.location,
        image:        d.image,
        rating:       d.rating ? parseFloat(d.rating) : 0,
        description:  d.description,
        highlights:   [d.highlight1, d.highlight2, d.highlight3, d.highlight4].filter(Boolean),
        bestTime:     d.best_time || '',
        duration:     d.duration || '',
        comments:     [],
        commentsCount: d.comments ? parseInt(d.comments) : 0,
        createdAt:    d.created_at,
        status:       'approved'
    })),

    sunrise: <?= json_encode($destinations['sunrise'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>.map(d => ({
        id:           parseInt(d.id),
        userId:       d.user_id,
        userName:     d.username,
        name:         d.title,
        location:     d.location,
        image:        d.image,
        rating:       d.rating ? parseFloat(d.rating) : 0,
        description:  d.description,
        highlights:   [d.highlight1, d.highlight2, d.highlight3, d.highlight4].filter(Boolean),
        bestTime:     d.best_time || '',
        duration:     d.duration || '',
        comments:     [],
        commentsCount: d.comments ? parseInt(d.comments) : 0,
        createdAt:    d.created_at,
        status:       'approved'
    })),

    draft: <?= json_encode(array_map(function($d) {
        return [
            'id'           => (int)$d['id'],
            'userId'       => (int)$d['user_id'],
            'userName'     => $d['username'],
            'name'         => $d['title'],
            'location'     => $d['location'],
            'image'        => $d['image'],
            'description'  => $d['description'],
            'category'     => $d['category'],
            'createdAt'    => $d['created_at'],
            'status'       => $d['status'],          // pending / rejected
            'rejectReason' => $d['reject_reason'],   // bisa null
  ];
    }, $drafts), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>.map(d => ({
        id: d.id,
        userId: d.userId,
        userName: d.userName,
        name: d.name,
        location: d.location,
        image: d.image,
        rating: 0,
        description: d.description,
        highlights: [],
        bestTime: '',
        duration: '',
        comments: [],
        commentsCount: 0,
        createdAt: d.createdAt,
        status: d.status,            // penting
        rejectReason: d.rejectReason // penting
    }))
};



function parseDescription(full) {
    const parts = full.split('\n');
    const result = { description: '', bestTime: '', duration: '', highlights: [] };

    result.description = parts[0] || '';

    parts.forEach(line => {
        if (line.startsWith('Best time:')) {
            result.bestTime = line.replace('Best time:', '').trim();
        } else if (line.startsWith('Duration:')) {
            result.duration = line.replace('Duration:', '').trim();
        } else if (line.startsWith('Highlights:')) {
            const hs = line.replace('Highlights:', '').split(',');
            result.highlights = hs.map(h => h.trim()).filter(Boolean);
        }
    });

    return result;
}
// isi komentar dari database
const commentsByDestination = <?= $commentsJson ?> || {};

for (const category in destinations) {
    destinations[category] = destinations[category].map(d => {
        const destId = d.id; // kalau nama field ID berbeda, sesuaikan di sini
        return {
            ...d,
            comments: commentsByDestination[destId] || []
        };
    });
}

        function changeCategory(category) {
            currentCategory = category;
            searchQuery = '';
            document.getElementById('searchInput').value = '';
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.className = 'category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-white text-gray-700 hover:bg-gray-100';
            });
            document.getElementById(`btn-${category}`).className = 'category-btn flex items-center gap-2 px-6 py-3 rounded-full font-semibold transition-all bg-green-600 text-white shadow-lg scale-105';
            renderDestinations();
        }

        function renderDestinations() {
    const grid = document.getElementById('destinationsGrid');
    grid.innerHTML = '';
    
    // Update draft count
    document.getElementById('draftCount').textContent = destinations.draft.length;
    
    // Dapatkan semua destinasi berdasarkan kategori
    let allDestinations = [];
    if (currentCategory === 'all') {
        allDestinations = [
            ...destinations.waterfalls.filter(d => d.status === 'approved'),
            ...destinations.sunset.filter(d => d.status === 'approved'),
            ...destinations.sunrise.filter(d => d.status === 'approved')
        ];
    } else if (currentCategory === 'draft') {
        allDestinations = destinations.draft;
    } else {
        allDestinations = destinations[currentCategory].filter(d => d.status === 'approved');
    }
    
    let filteredDestinations = allDestinations;
    
    // Filter berdasarkan search query
    if (searchQuery) {
        filteredDestinations = allDestinations.filter(dest => {
            const searchLower = searchQuery.toLowerCase();
            return dest.name.toLowerCase().includes(searchLower) || 
                   dest.location.toLowerCase().includes(searchLower) ||
                   dest.description.toLowerCase().includes(searchLower);
        });
    }
    
    if (filteredDestinations.length === 0) {
        const emptyMessage = currentCategory === 'draft' 
            ? 'Belum ada destinasi dalam draft'
            : searchQuery 
                ? `Tidak ada destinasi yang ditemukan untuk "${searchQuery}"`
                : 'Belum ada destinasi';
                
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <p class="text-gray-500 text-lg">${emptyMessage}</p>
                ${searchQuery ? `
                    <button onclick="clearSearch()" class="mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Hapus Pencarian
                    </button>
                ` : ''}
            </div>
        `;
        return;
    }

    // Bangun kartu destinasi
    filteredDestinations.forEach(dest => {
  const isDraft = currentCategory === 'draft';

  const card = document.createElement('div');
  card.className = 'bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300';

  card.innerHTML = `
    <div class="relative h-64">
      <img src="${dest.image}" alt="${dest.name}" class="w-full h-full object-cover">
      ${isDraft ? `
        <div class="absolute top-4 left-4 flex flex-col gap-1">
          ${
            dest.status === 'rejected'
              ? `<span class="inline-block px-3 py-1 bg-red-500 text-white text-xs rounded-full">Rejected</span>`
              : `<div class="inline-flex bg-orange-500 text-white px-3 py-1 rounded-full items-center gap-1 shadow-md">
                   <span>⏳</span>
                   <span class="text-xs font-semibold">Menunggu Persetujuan</span>
                 </div>`
          }
        </div>
      ` : ''}
    </div>
    <div class="p-6">
      <h3 class="text-xl font-bold text-gray-800 mb-2">${dest.name}</h3>
      <div class="flex items-center gap-2 text-gray-600 mb-2">
        <span>📍</span>
        <span class="text-sm">${dest.location}</span>
      </div>
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">${dest.description}</p>

      ${
        isDraft && dest.status === 'rejected' && dest.rejectReason
          ? `<p class="mt-2 text-xs text-red-600"><strong>Alasan Ditolak:</strong> ${dest.rejectReason}</p>`
          : ''
      }

      <div class="flex items-center justify-between mt-4">
        ${isDraft ? `
          <div class="flex gap-2">
            <button onclick="editDraft(${dest.id})"
                    class="flex-1 px-4 py-3 bg-blue-50 rounded-lg hover:bg-blue-100 flex items-center justify-center transition-all group"
                    title="Edit">
              <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700" fill="none"
                   stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
              </svg>
            </button>
            <button onclick="confirmDeleteDraft(${dest.id})"
                    class="flex-1 px-4 py-3 bg-red-50 rounded-lg hover:bg-red-100 flex items-center justify-center transition-all group"
                    title="Delete">
              <svg class="w-5 h-5 text-red-600 group-hover:text-red-700" fill="none"
                   stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 0h10"></path>
              </svg>
            </button>
          </div>
        ` : `
          <button onclick="showDetail(${dest.id})"
                  class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold">
            Lihat Detail
          </button>
        `}
      </div>
    </div>
  `;

        // Tambah meta "Uploaded by ... • tanggal"
        const contentDiv = card.querySelector('.p-6');
        const meta = document.createElement('p');
        meta.className = 'text-xs text-gray-400 mt-1';
        const userText = dest.userName ? dest.userName : `User #${dest.userId}`;
        const dateText = dest.createdAt ? dest.createdAt : '-';
        meta.textContent = `Uploaded by ${userText} • ${dateText}`;
        contentDiv.insertBefore(meta, contentDiv.querySelector('.flex.items-center.gap-2.text-gray-600.mb-2').nextSibling);

        grid.appendChild(card);
    });
}


        function showDetail(id) {
    let foundDest = null;
    let foundCategory = null;

    for (const category in destinations) {
        const dest = destinations[category].find(d => d.id === id);
        if (dest) {
            foundDest = dest;
            foundCategory = category;
            break;
        }
    }

    if (!foundDest) return;

    selectedDestination = foundDest;
    currentCategory = foundCategory;

    document.getElementById('mainView').classList.add('hidden');
    document.getElementById('detailView').classList.remove('hidden');

    document.getElementById('detailImage').src = selectedDestination.image;
    document.getElementById('detailName').textContent = selectedDestination.name;
    document.getElementById('detailLocation').textContent = selectedDestination.location;
    document.getElementById('detailDuration').textContent = selectedDestination.duration;
    document.getElementById('detailBestTime').textContent = selectedDestination.bestTime;
    document.getElementById('detailDescription').textContent = selectedDestination.description;

    if (selectedDestination.rating > 0) {
        document.getElementById('detailRatingDiv').classList.remove('hidden');
        document.getElementById('detailRating').textContent = selectedDestination.rating;
    } else {
        document.getElementById('detailRatingDiv').classList.add('hidden');
    }

    const highlightsDiv = document.getElementById('detailHighlights');
    highlightsDiv.innerHTML = '';
    selectedDestination.highlights.forEach(h => {
        highlightsDiv.innerHTML += `
            <div class="flex items-center gap-2 bg-green-50 p-3 rounded-lg">
                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                <span class="text-sm text-gray-700">${h}</span>
            </div>
        `;
    });
    const uploaderEl = document.getElementById('detailUploader');
if (uploaderEl) {
    const userText = selectedDestination && selectedDestination.userName
        ? selectedDestination.userName
        : (selectedDestination && selectedDestination.userId
            ? `User #${selectedDestination.userId}`
            : '-');

    const dateText = selectedDestination && selectedDestination.createdAt
        ? selectedDestination.createdAt
        : '-';

    uploaderEl.textContent = `Uploaded by ${userText} • ${dateText}`;
}


    renderComments();

    const destInput = document.getElementById('commentDestinationId');
    if (destInput && selectedDestination && typeof selectedDestination.id !== 'undefined') {
        destInput.value = selectedDestination.id;
    }
}


        function renderComments() {
    const list = document.getElementById('commentsList');
    list.innerHTML = '';

    if (!selectedDestination || !selectedDestination.comments) return;

    selectedDestination.comments.forEach(c => {
        const images = (c.images || [])
            .map(src => `
                <img src="${src}"
                     class="w-16 h-16 rounded-lg object-cover cursor-pointer"
                     onclick="window.open('${src}', '_blank')">
            `)
            .join('');

        list.innerHTML += `
            <div class="border border-gray-200 rounded-xl p-3">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-sm font-semibold text-gray-800">${c.user}</span>
                    <span class="text-xs text-gray-400">${c.time}</span>
                </div>
                <p class="text-sm text-gray-700 mb-2">${c.text}</p>
                ${images ? `<div class="flex gap-2 flex-wrap">${images}</div>` : ''}
            </div>
        `;
    });
}




        function addComment() {
    if (!selectedDestination) return;

    const form = document.getElementById('commentForm');
    const textInput = document.getElementById('commentInput');
    if (!textInput.value.trim()) return;

    const formData = new FormData(form);

    fetch('user_dashboard.php?action=add_comment', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal menyimpan komentar.');
            return;
        }

        selectedDestination.comments.push(res.comment);
        renderComments();

        form.reset();
        commentImages = [];
        document.getElementById('commentImagePreview').classList.add('hidden');
        document.getElementById('commentImagePreview').querySelector('.flex').innerHTML = '';
    })
    .catch(() => {
        alert('Terjadi kesalahan jaringan saat menyimpan komentar.');
    });
}


        function closeDetail() {
            document.getElementById('mainView').classList.remove('hidden');
            document.getElementById('detailView').classList.add('hidden');
            selectedDestination = null;
            commentImages = [];
            document.getElementById('commentImagePreview').classList.add('hidden');
            document.getElementById('commentImagePreview').querySelector('.flex').innerHTML = '';
        }

        function handleCommentImageUpload() {
    const input = document.getElementById('commentImageInput');
    const previewWrapper = document.getElementById('commentImagePreview');
    const previewContainer = previewWrapper.querySelector('.flex');

    previewContainer.innerHTML = '';

    const files = input.files;
    if (!files || files.length === 0) {
        previewWrapper.classList.add('hidden');
        return;
    }

    const maxFiles = 4;
    const count = Math.min(files.length, maxFiles);

    for (let i = 0; i < count; i++) {
        const file = files[i];
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'w-16 h-16 rounded-lg object-cover';
            previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
    }

    previewWrapper.classList.remove('hidden');
}


        function renderCommentImagePreviews() {
            const previewContainer = document.getElementById('commentImagePreview').querySelector('.flex');
            previewContainer.innerHTML = '';
            
            commentImages.forEach((img, index) => {
                const imgWrapper = document.createElement('div');
                imgWrapper.className = 'relative inline-block';
                imgWrapper.innerHTML = `
                    <img src="${img}" alt="" class="h-20 w-20 object-cover rounded-lg">
                    <button onclick="removeCommentImage(${index})" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                        ×
                    </button>
                `;
                previewContainer.appendChild(imgWrapper);
            });
            
            if (commentImages.length > 0) {
                document.getElementById('commentImagePreview').classList.remove('hidden');
            } else {
                document.getElementById('commentImagePreview').classList.add('hidden');
            }
        }

        function removeCommentImage(index) {
            commentImages.splice(index, 1);
            renderCommentImagePreviews();
            document.getElementById('commentImageInput').value = '';
        }

        function performSearch() {
            const input = document.getElementById('searchInput');
            searchQuery = input.value.trim();
            console.log('Searching for:', searchQuery); // Debug
            renderDestinations();
        }

        function handleSearch(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                performSearch();
            }
        }

        function clearSearch() {
            searchQuery = '';
            document.getElementById('searchInput').value = '';
            renderDestinations();
        }

        

        function editDraft(id) {
    const draft = destinations.draft.find(d => d.id === id);
    if (!draft) return;

    editingDraftId = id;
            
            // Isi form dengan data draft
            document.getElementById('inputName').value = draft.name;
            document.getElementById('inputLocation').value = draft.location;
            document.getElementById('inputCategory').value = draft.category;
            document.getElementById('inputDescription').value = draft.description;
            document.getElementById('inputBestTime').value = draft.bestTime;
            document.getElementById('inputDuration').value = draft.duration;
            
            
            // Isi highlights
            draft.highlights.forEach((h, i) => {
                const input = document.getElementById(`inputHighlight${i + 1}`);
                if (input) input.value = h;
            });
            
            // Set image preview
            if (draft.image) {
                uploadedImage = draft.image;
                document.getElementById('previewImage').src = draft.image;
                document.getElementById('previewImage').classList.remove('hidden');
            }
            
            // Hapus draft lama (akan dibuat ulang saat save)
            destinations.draft = destinations.draft.filter(d => d.id !== id);
            
            // Buka modal
            openCreateModal();
        }

        function deleteDraft(id) {
            if (confirm('Apakah Anda yakin ingin menghapus draft ini?')) {
                destinations.draft = destinations.draft.filter(d => d.id !== id);
                renderDestinations();
                alert('Draft berhasil dihapus!');
            }
        }

        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('successModal').classList.remove('hidden');
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        function confirmDeleteDraft(id) {
            deleteTargetId = id;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function cancelDelete() {
            deleteTargetId = null;
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function confirmDelete() {
    if (!deleteTargetId) return;

    fetch('user_dashboard.php?action=delete_draft', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ id: deleteTargetId })
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal menghapus draft di server.');
            return;
        }

        // hapus dari array JS
        destinations.draft = destinations.draft.filter(d => d.id !== deleteTargetId);
        renderDestinations();
        document.getElementById('deleteModal').classList.add('hidden');
        showSuccessModal('Draft berhasil dihapus!');
        deleteTargetId = null;
    })
    .catch(() => {
        alert('Terjadi kesalahan jaringan saat menghapus draft.');
    });
}


        function openImageModal(imageSrc) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center p-4 z-50';
            modal.onclick = () => modal.remove();
            modal.innerHTML = `
                <img src="${imageSrc}" alt="Full size" class="max-w-full max-h-full object-contain rounded-lg">
            `;
            document.body.appendChild(modal);
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
            clearForm();
        }

        function handleLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function cancelLogout() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function confirmLogout() {
            document.getElementById('logoutModal').classList.add('hidden');
            window.location.href = 'index.php'; // atau logout.php
        }

        function handleImageUpload() {
            const input = document.getElementById('inputImage');
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = () => {
                    uploadedImage = reader.result;
                    const preview = document.getElementById('previewImage');
                    preview.src = uploadedImage;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        function createPost() {
    const name        = document.getElementById('inputName').value;
    const location    = document.getElementById('inputLocation').value;
    const category    = document.getElementById('inputCategory').value;
    const description = document.getElementById('inputDescription').value;

    if (!(name && location && category && description)) {
        alert('Mohon isi nama, lokasi, kategori, dan deskripsi!');
        return;
    }

    const highlights = [
        document.getElementById('inputHighlight1').value,
        document.getElementById('inputHighlight2').value,
        document.getElementById('inputHighlight3').value,
        document.getElementById('inputHighlight4').value
    ].filter(h => h);

    const basePost = {
        id: editingDraftId || Date.now(),
        name,
        location,
        category,
        image: uploadedImage || 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800',
        rating: 0,
        description,
        highlights,
        bestTime: document.getElementById('inputBestTime').value,
        duration: document.getElementById('inputDuration').value,
        comments: [],
        status: 'pending'
    };

    // update array JS
    if (editingDraftId) {
        // replace draft lama
        destinations.draft = destinations.draft.filter(d => d.id !== editingDraftId);
    }
    destinations.draft.push(basePost);

    // kirim ke server: bedakan add / update dengan flag
    const action = editingDraftId ? 'update_draft' : 'save_draft';

    fetch('user_dashboard.php?action=' + action, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(basePost)
    })
    .then(res => res.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal menyimpan ke database.');
            return;
        }
        closeCreateModal();
        changeCategory('draft');
        showSuccessModal(editingDraftId ? 'Draft berhasil diperbarui!' : 'Destinasi berhasil dibuat! Menunggu persetujuan admin.');
        renderDestinations();
        editingDraftId = null; // reset
    })
    .catch(() => {
        alert('Terjadi kesalahan jaringan saat menyimpan ke server.');
    });
}



        function clearForm() {
            document.getElementById('inputName').value = '';
            document.getElementById('inputLocation').value = '';
            document.getElementById('inputCategory').value = '';
            document.getElementById('inputDescription').value = '';
            document.getElementById('inputHighlight1').value = '';
            document.getElementById('inputHighlight2').value = '';
            document.getElementById('inputHighlight3').value = '';
            document.getElementById('inputHighlight4').value = '';
            document.getElementById('inputBestTime').value = '';
            document.getElementById('inputDuration').value = '';
            document.getElementById('previewImage').classList.add('hidden');
            uploadedImage = '';
        }

        // Initialize
        renderDestinations();

        
    </script>
</body>
</html>