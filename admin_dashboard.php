<?php
require_once __DIR__ . "/config/db.php";

$pendingDestinations = [];
$approvedDestinations = [];
$rejectedDestinations = [];

// Draft pending dari user
$resPending = $conn->query("
    SELECT id, user_id, title, location, image, description, category,
           best_time, duration, highlight1, highlight2, highlight3, highlight4, status, reject_reason
    FROM destinations
    WHERE status = 'pending'
    ORDER BY id DESC
");
if ($resPending) {
    while ($row = $resPending->fetch_assoc()) {
        $pendingDestinations[] = $row;
    }
}

// Destinasi sudah approved (untuk daftar utama admin)
$resApproved = $conn->query("
    SELECT id, user_id, title, location, image, description, category,
           best_time, duration, highlight1, highlight2, highlight3, highlight4, status, reject_reason
    FROM destinations
    WHERE status = 'approved'
    ORDER BY id DESC
");
if ($resApproved) {
    while ($row = $resApproved->fetch_assoc()) {
        $approvedDestinations[] = $row;
    }
}

// Draft yang sudah rejected  <-- BLOK BARU
$resRejected = $conn->query("
    SELECT id, user_id, title, location, image, description, category,
           best_time, duration, highlight1, highlight2, highlight3, highlight4, status, reject_reason
    FROM destinations
    WHERE status = 'rejected'
    ORDER BY id DESC
");
if ($resRejected) {
    while ($row = $resRejected->fetch_assoc()) {
        $rejectedDestinations[] = $row;
    }
}

// Approve draft: ubah status menjadi 'approved'
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'approve_destination') {

    header('Content-Type: application/json');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE destinations SET status='approved' WHERE id=? AND status='pending'");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Destination approved.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal approve: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// Reject draft: ubah status jadi 'rejected' + simpan alasan
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'reject_destination') {

    header('Content-Type: application/json');

    $id     = (int)($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        exit;
    }
    if ($reason === '') {
        echo json_encode(['success' => false, 'message' => 'Alasan penolakan wajib diisi.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE destinations
        SET status = 'rejected', reject_reason = ?
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("si", $reason, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Destination rejected.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal reject: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}


    $pendingJson  = json_encode($pendingDestinations,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $approvedJson = json_encode($approvedDestinations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $rejectedJson = json_encode($rejectedDestinations, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // UPDATE DESTINATION (edit dari modal)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'update_destination') {

    header('Content-Type: application/json');

    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bestTime    = trim($_POST['best_time'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $h1          = trim($_POST['highlight1'] ?? '');
    $h2          = trim($_POST['highlight2'] ?? '');
    $h3          = trim($_POST['highlight3'] ?? '');
    $h4          = trim($_POST['highlight4'] ?? '');
    $currentImg  = trim($_POST['current_image'] ?? '');

    if ($id <= 0 || $title === '' || $description === '' || $category === '') {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    // proses upload gambar baru jika ada
    $newImagePath = '';
    if (!empty($_FILES['new_image']) && $_FILES['new_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/destinations/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $tmpName  = $_FILES['new_image']['tmp_name'];
        $origName = basename($_FILES['new_image']['name']);
        $ext      = pathinfo($origName, PATHINFO_EXTENSION);
        $safeName = uniqid('dest_', true) . '.' . strtolower($ext);
        $target   = $uploadDir . $safeName;

        if (move_uploaded_file($tmpName, $target)) {
            $newImagePath = 'uploads/destinations/' . $safeName;

            // opsional: hapus file lama jika perlu dan kalau path lama ada di folder uploads
            if ($currentImg && str_starts_with($currentImg, 'uploads/destinations/')) {
                $oldFile = __DIR__ . '/' . $currentImg;
                if (is_file($oldFile)) {
                    @unlink($oldFile);
                }
            }
        }
    }

    // kalau tidak ada upload baru, pakai gambar lama
    $finalImage = $newImagePath !== '' ? $newImagePath : $currentImg;

    $stmt = $conn->prepare("
        UPDATE destinations
        SET title=?, location=?, category=?, description=?,
            best_time=?, duration=?,
            highlight1=?, highlight2=?, highlight3=?, highlight4=?,
            image=?
        WHERE id=? AND status='approved'
    ");
    $stmt->bind_param(
        "sssssssssssi",
        $title, $location, $category, $description,
        $bestTime, $duration,
        $h1, $h2, $h3, $h4,
        $finalImage,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success'    => true,
            'message'    => 'Destination updated.',
            'image_path' => $finalImage
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal update: '.$stmt->error]);
    }
    $stmt->close();
    exit;
}


// DELETE DESTINATION
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'delete_destination') {

    header('Content-Type: application/json');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM destinations WHERE id=?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Destination deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal delete: '.$stmt->error]);
    }
    $stmt->close();
    exit;
}

// CREATE DESTINATION (dari admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_GET['action'])
    && $_GET['action'] === 'create_destination') {

    header('Content-Type: application/json');

    $title       = trim($_POST['title'] ?? '');
    $location    = trim($_POST['location'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bestTime    = trim($_POST['best_time'] ?? '');
    $duration    = trim($_POST['duration'] ?? '');
    $h1          = trim($_POST['highlight1'] ?? '');
    $h2          = trim($_POST['highlight2'] ?? '');
    $h3          = trim($_POST['highlight3'] ?? '');
    $h4          = trim($_POST['highlight4'] ?? '');
    $image       = trim($_POST['image'] ?? ''); // kirim path dari JS

    if ($title === '' || $description === '' || $category === '') {
        echo json_encode(['success' => false, 'message' => 'Nama, kategori, dan deskripsi wajib diisi.']);
        exit;
    }

    if ($image === '') {
        $image = 'assets/img/default_destination.jpg';
    }

    $userId = $_SESSION['user_id'] ?? null;
    $views  = 0;
    $comments = 0;
    $rating = 0.0;
    $status = 'approved'; // langsung publish oleh admin

    $stmt = $conn->prepare("
        INSERT INTO destinations
            (user_id, title, location, description, image,
             category, best_time, duration,
             highlight1, highlight2, highlight3, highlight4,
             views, comments, rating, status)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param(
    "isssssssssssiids",
    $userId, $title, $location, $description, $image,
    $category, $bestTime, $duration,
    $h1, $h2, $h3, $h4,
    $views, $comments, $rating, $status
);


    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Destination created.',
            'id'      => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal insert: '.$stmt->error]);
    }
    $stmt->close();
    exit;
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DEWASUFA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 40rem;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-green-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen p-6">
            <div class="flex items-center gap-2 mb-8">
                <div class="text-3xl">🌊</div>
                <h1 class="text-xl font-bold text-gray-800">DEWASUFA</h1>
            </div>

            <button onclick="showCreateModal()" class="w-full bg-green-500 hover:bg-green-600 text-white py-3 px-4 rounded-lg mb-6 font-medium transition-colors">
                Create New Page
            </button>

            <nav class="space-y-2">
                <button onclick="switchMenu('dashboard')" id="btnDashboard" class="w-full text-left px-4 py-3 rounded-lg transition-colors bg-gray-200 text-gray-800">
                    📊 Dashboard
                </button>
                <button onclick="switchMenu('preview')" id="btnPreview" class="w-full text-left px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-100">
                    👁️ Preview
                </button>
            </nav>

            <!-- Category Filter for Preview -->
            <div id="categoryFilter" class="hidden mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Filter Kategori</h3>
                <div class="space-y-2">
                    <button onclick="filterCategory('all')" id="filterAll" class="w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium">
                        📋 Semua Draft
                    </button>
                    <button onclick="filterCategory('waterfall')" id="filterWaterfall" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm">
                        🌊 Waterfall
                    </button>
                    <button onclick="filterCategory('sunset')" id="filterSunset" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm">
                        🌅 Sunset Beach
                    </button>
                    <button onclick="filterCategory('sunrise')" id="filterSunrise" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm">
                        🌄 Sunrise Beach
                    </button>
                    <button onclick="filterCategory('rejected')"id="filterRejected" class="w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm">
                        ❌ Rejected
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h2 id="pageTitle" class="text-3xl font-bold text-gray-800">DASHBOARD</h2>
                <button onclick="handleLogout()" class="px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium transition-all hover:shadow-lg">
                    Logout
                </button>
            </div>

            <!-- Welcome Card -->
            <div id="welcomeCard" class="bg-gradient-to-r from-green-400 to-green-500 rounded-xl p-8 mb-8 text-white shadow-lg">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Hi, Admin</h3>
                        <p class="text-green-50">Siap untuk mengelola alam dan menikmati perjalanan yang baru?</p>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
                    <div id="statsCards" class="grid grid-cols-2 gap-6 mb-8">
            <!-- Published -->
            <div class="bg-gradient-to-br from-green-400 to-green-500 rounded-xl p-6 text-white shadow-lg">
                <svg class="w-8 h-8 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-sm mb-1">Published</p>
                <p id="totalPublished" class="text-3xl font-bold">0</p>
            </div>

            <!-- Draft -->
            <div class="bg-gradient-to-br from-purple-400 to-purple-500 rounded-xl p-6 text-white shadow-lg">
                <svg class="w-8 h-8 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm mb-1">Draft</p>
                <p id="totalDrafts" class="text-3xl font-bold">0</p>
            </div>
        </div>


            <!-- Blog List -->
            <div id="blogList" class="space-y-4"></div>

            <!-- Empty State for Preview -->
            <div id="emptyState" class="hidden bg-white rounded-xl p-12 text-center shadow">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 text-lg">Tidak ada draft yang menunggu persetujuan</p>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h3 id="modalTitle" class="text-2xl font-bold text-gray-800">Add New Destination</h3>
                <button onclick="closeCreateModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-4">
                <input type="text" id="destinationName" placeholder="Destination Name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="text" id="location" placeholder="Location" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                
                <select id="category" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">Choose Category</option>
                    <option value="waterfall">🌊 Waterfall</option>
                    <option value="sunset">🌅 Sunset Beach</option>
                    <option value="sunrise">🌄 Sunrise Beach</option>
                </select>
                
                <label class="block w-full px-4 py-3 bg-green-50 text-green-600 border border-green-200 rounded-lg cursor-pointer hover:bg-green-100 transition-colors text-center">
                    <span id="photoLabel">Choose Photo</span>
                    <input type="file" id="photoInput" accept="image/*" class="hidden" onchange="handlePhotoUpload(event)">
                    <input type="hidden" id="currentImagePath">
                </label>

                <textarea id="description" placeholder="Description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                <input type="text" id="highlight1" placeholder="Highlight 1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="text" id="highlight2" placeholder="Highlight 2" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="text" id="highlight3" placeholder="Highlight 3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="text" id="highlight4" placeholder="Highlight 4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">

                <div class="grid grid-cols-2 gap-4">
                    <input type="text" id="bestTime" placeholder="Best Time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <input type="text" id="duration" placeholder="Duration" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <button onclick="handlePublish()" id="publishBtn" class="w-full bg-green-500 hover:bg-green-600 text-white py-4 rounded-lg font-semibold text-lg transition-colors">
                    Publish Destination
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 28rem;">
            <div class="p-6">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Konfirmasi Hapus</h3>
                    <p class="text-gray-600">
                        Apakah Anda yakin ingin <span id="deleteAction">menghapus</span> <span id="deleteTargetName" class="font-semibold"></span>?
                    </p>
                    <p class="text-sm text-red-600 mt-2">Tindakan ini tidak dapat dibatalkan!</p>
                </div>

                <div class="flex gap-3">
                    <button onclick="closeDeleteModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-3 rounded-lg font-semibold transition-colors">
                        Batal
                    </button>
                    <button onclick="confirmDelete()" id="confirmDeleteBtn" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-3 rounded-lg font-semibold transition-colors">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl">
            <div class="text-center">
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
    </div>

    <!-- Notification Modal -->
    <div id="notificationModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center p-6 z-40">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl text-center">
            <div id="notificationIcon" class="w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100 text-green-600">
                <span class="text-2xl">✓</span>
            </div>
            <h3 id="notificationTitle" class="text-xl font-bold text-gray-800 mb-2">
                Berhasil
            </h3>
            <p id="notificationMessage" class="text-gray-600 mb-6 text-sm">
                Aksi berhasil diproses.
            </p>
            <button onclick="closeNotification()" class="px-6 py-2.5 bg-green-500 text-white rounded-xl font-semibold hover:bg-green-600 transition-all">
                OK
            </button>
        </div>
    </div>

    <!-- Reject Reason Modal -->
        <div id="rejectReasonModal"
            class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-50">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-xl">
            <h2 class="text-xl font-bold text-gray-800 mb-3">Alasan Penolakan</h2>
            <textarea id="rejectReasonInput"
                    class="w-full border rounded-xl px-3 py-2 mb-4 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                    rows="3"
                    placeholder="Tuliskan alasan kenapa draft ini ditolak"></textarea>
            <div class="flex justify-end gap-2">
            <button type="button"
                    onclick="closeRejectModal()"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Batal
            </button>
            <button type="button"
                    onclick="submitRejectReason()"
                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                Tolak Draft
            </button>
            </div>
        </div>
        </div>

        <!-- Preview Draft Modal -->
    <div id="previewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-6 z-40">
    <div class="bg-white rounded-3xl p-6 max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Preview Draft</h2>
        <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700 text-2xl">×</button>
        </div>

        <div class="bg-white rounded-3xl shadow-md overflow-hidden">
        <div class="grid md:grid-cols-2 gap-6 p-6">
            <div>
            <img id="previewImage" src="" alt="" class="w-full h-72 object-cover rounded-2xl shadow-lg">
            <h1 id="previewName" class="text-2xl font-bold text-gray-800 mb-1 mt-4"></h1>
            <p id="previewUploader" class="text-xs text-gray-400 mb-2"></p>

            <div class="flex items-center gap-2 text-gray-600 mb-3">
                <span class="text-green-600">📍</span>
                <span id="previewLocation"></span>
            </div>

            <div class="flex items-center gap-4 mb-4">
                <div class="flex items-center gap-2 bg-green-100 px-4 py-2 rounded-full">
                <span class="text-green-600">🕐</span>
                <span id="previewDuration" class="text-sm font-medium"></span>
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-xl">
                <h3 class="font-semibold text-gray-800 mb-2 flex items-center gap-2">
                <span class="text-blue-600">🕐</span>
                Waktu Terbaik
                </h3>
                <p id="previewBestTime" class="text-gray-700"></p>
            </div>
            </div>

            <div>
            <h2 class="text-xl font-bold text-gray-800 mb-3">Description</h2>
            <p id="previewDescription" class="text-gray-600 leading-relaxed mb-5"></p>

            <h2 class="text-xl font-bold text-gray-800 mb-3">Highlight</h2>
            <div id="previewHighlights" class="grid grid-cols-2 gap-3"></div>
            </div>
        </div>
        </div>

        <div class="flex justify-end gap-3 mt-4">
    <button id="previewApproveBtn"
            class="px-5 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700">
        Approve
    </button>
    <button onclick="handlePreviewReject()"
            class="px-4 py-2 bg-red-500 text-white rounded-xl hover:bg-red-600">
        Reject
    </button>
    </div>
    </div>

        

    <script>
       let currentMenu = 'dashboard';
        let currentStatusFilter = 'all'; // all | pending | approved | rejected
        let currentCategory = 'all';
        let editingBlogId = null;
        let deleteTargetId = null;
        let photoData = null;

const pending = <?= $pendingJson  ?: '[]' ?>.map(d => ({
  id: parseInt(d.id),
  userId: parseInt(d.user_id),
  title: d.title,
  location: d.location,
  image: d.image,
  description: d.description,
  category: d.category,
  bestTime: d.best_time,
  duration: d.duration,
  highlight1: d.highlight1,
  highlight2: d.highlight2,
  highlight3: d.highlight3,
  highlight4: d.highlight4,
  status: 'draft',
  rejectReason: d.reject_reason || null
}));

const approved = <?= $approvedJson ?: '[]' ?>.map(d => ({
  id: parseInt(d.id),
  userId: parseInt(d.user_id),
  title: d.title,
  location: d.location,
  image: d.image,
  description: d.description,
  category: d.category,
  bestTime: d.best_time,
  duration: d.duration,
  highlight1: d.highlight1,
  highlight2: d.highlight2,
  highlight3: d.highlight3,
  highlight4: d.highlight4,
  status: 'published',
  rejectReason: d.reject_reason || null
}));

const rejected = <?= $rejectedJson ?: '[]' ?>.map(d => ({
  id: parseInt(d.id),
  userId: parseInt(d.user_id),
  title: d.title,
  location: d.location,
  image: d.image,
  description: d.description,
  category: d.category,
  bestTime: d.best_time,
  duration: d.duration,
  highlight1: d.highlight1,
  highlight2: d.highlight2,
  highlight3: d.highlight3,
  highlight4: d.highlight4,
  status: 'rejected',
  rejectReason: d.reject_reason || null
}));

let blogs = [...approved, ...pending, ...rejected];



        function calculateStats() {
    const totalPublished = blogs.filter(b => b.status === 'published').length;
    const totalDrafts = blogs.filter(b => b.status === 'draft').length;

    const pubEl = document.getElementById('totalPublished');
    if (pubEl) pubEl.textContent = totalPublished;

    const draftEl = document.getElementById('totalDrafts');
    if (draftEl) draftEl.textContent = totalDrafts;
}

function setStatusFilter(status) {
    currentStatusFilter = status; // 'all' | 'pending' | 'approved' | 'rejected'
    renderBlogs();
}


        function renderBlogs() {
  const blogList = document.getElementById('blogList');
  const emptyState = document.getElementById('emptyState');

  let blogsToShow = blogs;

  if (currentMenu === 'preview') {
    if (currentCategory === 'rejected') {
      // hanya yang sudah direject
      blogsToShow = blogs.filter(b => b.status === 'rejected');
    } else if (currentCategory === 'all') {
      // semua draft pending
      blogsToShow = blogs.filter(b => b.status === 'draft');
    } else {
      // draft pending per kategori
      blogsToShow = blogs.filter(
        b => b.status === 'draft' && b.category === currentCategory
      );
    }
  } else {
    // dashboard: logika lama (tampilkan semua)
    blogsToShow = blogs;
  }

  if (currentMenu === 'preview' && blogsToShow.length === 0) {
    blogList.innerHTML = '';
    emptyState.classList.remove('hidden');
    const categoryText =
      currentCategory === 'all'
        ? ''
        : ` kategori ${getCategoryName(currentCategory)}`;
    emptyState.querySelector('p').textContent =
      `Tidak ada draft yang menunggu persetujuan${categoryText}`;
    return;
  }

  emptyState.classList.add('hidden');
  blogList.innerHTML = blogsToShow
    .map(blog => `
      <div class="bg-white rounded-xl p-6 shadow flex items-center justify-between">
        <div class="flex items-center gap-4">
          <img src="${blog.image}" alt="${blog.title}" class="w-16 h-16 rounded-lg object-cover">
          <div>
            <h4 class="font-bold text-gray-800 mb-1">${blog.title}</h4>
            <p class="text-sm text-gray-600">${blog.description}</p>
            ${blog.status === 'draft' ? `
              <div class="flex gap-2 mt-2">
                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">
                  Menunggu Persetujuan
                </span>
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">
                  ${getCategoryIcon(blog.category)} ${getCategoryName(blog.category)}
                </span>
              </div>
            ` : blog.status === 'rejected' ? `
              <div class="flex gap-2 mt-2">
                <span class="inline-block px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full">
                  Rejected
                </span>
                ${blog.rejectReason ? `
                  <span class="text-xs text-red-600">
                    Alasan: ${blog.rejectReason}
                  </span>
                ` : ''}
              </div>
            ` : ''}
          </div>
        </div>
        <div class="flex gap-2">
          ${blog.status === 'draft' ? `
            <div class="flex gap-2">
              <button onclick="viewDraft(${blog.id})"
                      class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full hover:bg-blue-200">
                Review
              </button>
              <button onclick="handleApprove(${blog.id})"
                      class="w-10 h-10 flex items-center justify-center bg-green-500 hover:bg-green-600 rounded-lg transition-colors"
                      title="Approve">
                <span class="text-xl text-white font-bold">✓</span>
              </button>
              <button onclick="handleReject(${blog.id})"
                      class="w-10 h-10 flex items-center justify-center bg-red-500 hover:bg-red-600 rounded-lg transition-colors"
                      title="Reject">
                <span class="text-xl text-white font-bold">✕</span>
              </button>
            </div>
          ` : `
            <button onclick="handleEdit(${blog.id})"
                    class="p-2 hover:bg-blue-50 rounded-lg transition-colors group"
                    title="Edit">
              <svg class="w-5 h-5 text-blue-600 group-hover:text-blue-700" fill="none"
                   stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
              </svg>
            </button>
            <button onclick="handleDelete(${blog.id})"
                    class="p-2 hover:bg-red-50 rounded-lg transition-colors group"
                    title="Delete">
              <svg class="w-5 h-5 text-red-600 group-hover:text-red-700" fill="none"
                   stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/>
              </svg>
            </button>
          `}
        </div>
      </div>
    `)
    .join('');
}


        function getCategoryName(category) {
            const names = {
                'waterfall': 'Waterfall',
                'sunset': 'Sunset Beach',
                'sunrise': 'Sunrise Beach'
            };
            return names[category] || category;
        }

        function getCategoryIcon(category) {
            const icons = {
                'waterfall': '🌊',
                'sunset': '🌅',
                'sunrise': '🌄',
                'rejected': '❌'
            };
            return icons[category] || '📋';
        }

        function filterCategory(category) {
            currentCategory = category;
            
            document.getElementById('filterAll').className = category === 'all' 
                ? 'w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium'
                : 'w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm';
            document.getElementById('filterWaterfall').className = category === 'waterfall'
                ? 'w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium'
                : 'w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm';
            document.getElementById('filterSunset').className = category === 'sunset'
                ? 'w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium'
                : 'w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm';
            document.getElementById('filterSunrise').className = category === 'sunrise'
                ? 'w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium'
                : 'w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm';
            document.getElementById('filterRejected').className = category === 'rejected'
                ? 'w-full text-left px-4 py-2 rounded-lg transition-colors bg-green-100 text-green-700 text-sm font-medium'
                : 'w-full text-left px-4 py-2 rounded-lg transition-colors text-gray-600 hover:bg-gray-100 text-sm';
            
            renderBlogs();
        }

        function switchMenu(menu) {
            currentMenu = menu;
            currentCategory = 'all';
            
            document.getElementById('btnDashboard').className = menu === 'dashboard' 
                ? 'w-full text-left px-4 py-3 rounded-lg transition-colors bg-gray-200 text-gray-800'
                : 'w-full text-left px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-100';
            document.getElementById('btnPreview').className = menu === 'preview'
                ? 'w-full text-left px-4 py-3 rounded-lg transition-colors bg-gray-200 text-gray-800'
                : 'w-full text-left px-4 py-3 rounded-lg transition-colors text-gray-600 hover:bg-gray-100';

            document.getElementById('pageTitle').textContent = menu === 'dashboard' ? 'DASHBOARD' : 'PREVIEW - DRAFT BLOGS';
            document.getElementById('welcomeCard').style.display = menu === 'dashboard' ? 'block' : 'none';
            document.getElementById('statsCards').style.display = menu === 'dashboard' ? 'grid' : 'none';
            
            document.getElementById('categoryFilter').classList.toggle('hidden', menu !== 'preview');
            
            if (menu === 'preview') {
                filterCategory('all');
            } else {
                renderBlogs();
            }
        }

        function showCreateModal() {
            editingBlogId = null;
            photoData = null;
            document.getElementById('modalTitle').textContent = 'Add New Destination';
            document.getElementById('publishBtn').textContent = 'Publish Destination';
            document.getElementById('destinationName').value = '';
            document.getElementById('location').value = '';
            document.getElementById('category').value = '';
            document.getElementById('description').value = '';
            document.getElementById('highlight1').value = '';
            document.getElementById('highlight2').value = '';
            document.getElementById('highlight3').value = '';
            document.getElementById('highlight4').value = '';
            document.getElementById('bestTime').value = '';
            document.getElementById('duration').value = '';
            document.getElementById('photoLabel').textContent = 'Choose Photo';
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
            editingBlogId = null;
            photoData = null;
        }

        function handlePhotoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    photoData = reader.result;
                    document.getElementById('photoLabel').textContent = 'Photo Selected ✓';
                };
                reader.readAsDataURL(file);
            }
        }

        function handleEdit(id) {
    const blog = blogs.find(b => b.id === id);
    if (!blog) return;

    editingBlogId = id;
    photoData = null;

    document.getElementById('currentImagePath').value = blog.image || '';
    document.getElementById('modalTitle').textContent = 'Edit Destination';
    document.getElementById('publishBtn').textContent = 'Update Destination';

    document.getElementById('destinationName').value = blog.title || '';
    document.getElementById('location').value       = blog.location || '';
    document.getElementById('category').value       = blog.category || '';
    document.getElementById('description').value    = blog.description || '';

    document.getElementById('highlight1').value = blog.highlight1 || '';
    document.getElementById('highlight2').value = blog.highlight2 || '';
    document.getElementById('highlight3').value = blog.highlight3 || '';
    document.getElementById('highlight4').value = blog.highlight4 || '';

    document.getElementById('bestTime').value = blog.bestTime || '';
    document.getElementById('duration').value = blog.duration || '';

    document.getElementById('photoLabel').textContent = 'Photo Selected';
    document.getElementById('createModal').classList.add('active');
}



        function handleDelete(id) {
            const blog = blogs.find(b => b.id === id);
            if (!blog) return;

            deleteTargetId = id;
            document.getElementById('deleteTargetName').textContent = `"${blog.title}"`;
            document.getElementById('deleteAction').textContent = 'menghapus';
            document.getElementById('confirmDeleteBtn').textContent = 'Ya, Hapus';
            document.getElementById('deleteModal').classList.add('active');
        }

        function handleApprove(id) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('admin_dashboard.php?action=approve_destination', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(r => {
        if (!r.success) {
            alert(r.message || 'Gagal approve.');
            return;
        }
        const blogIndex = blogs.findIndex(b => b.id === id);
        if (blogIndex !== -1) {
            blogs[blogIndex].status = 'published';
        }
        calculateStats();
        renderBlogs();
        showNotification(
            'Destination Approved',
            `Destination berhasil di-approve dan dipublikasikan.`,
            'success'
        );
    })
    .catch(() => alert('Gagal menghubungi server.'));
}


let rejectTargetId = null;

function handleReject(id) {
  rejectTargetId = id;

  const textarea = document.getElementById('rejectReasonInput');
  if (textarea) textarea.value = '';

  const modal = document.getElementById('rejectReasonModal');
  if (modal) {
    modal.classList.remove('hidden');
  }
}

function closeRejectModal() {
    document.getElementById('rejectReasonModal').classList.add('hidden');
    rejectTargetId = null;
}

function submitRejectReason() {
  if (!rejectTargetId) return;

  const reasonInput = document.getElementById('rejectReasonInput');
  const reason = reasonInput ? reasonInput.value.trim() : '';

  if (!reason) {
    alert('Alasan penolakan wajib diisi.');
    return;
  }

  const fd = new FormData();
  fd.append('id', rejectTargetId);
  fd.append('reason', reason);

  fetch('admin_dashboard.php?action=reject_destination', {
    method: 'POST',
    body: fd
  })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        alert(res.message || 'Gagal reject.');
        return;
      }

      // PENTING: ubah status di array blogs, jangan hapus
      const blog = blogs.find(b => b.id === rejectTargetId);
      if (blog) {
        blog.status = 'rejected';
        blog.rejectReason = reason;
      }

      closeRejectModal();
      calculateStats();
      renderBlogs();
      showNotification('Draft rejected', 'Draft berhasil ditolak.', 'success');
    })
    .catch(() => {
      alert('Gagal menghubungi server.');
    });
}



        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteTargetId = null;
        }

        function confirmDelete() {
    const id = deleteTargetId;
    if (!id) {
        closeDeleteModal();
        return;
    }

    const formData = new FormData();
    formData.append('id', id);

    fetch('admin_dashboard.php?action=delete_destination', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(r => {
        if (!r.success) {
            alert(r.message || 'Gagal menghapus destination.');
            return;
        }

        const index = blogs.findIndex(b => b.id === id);
        if (index !== -1) {
            const deletedBlog = blogs[index];
            blogs.splice(index, 1);
            calculateStats();
            renderBlogs();
            showNotification(
                'Destination Deleted',
                `Destination "${deletedBlog.title}" berhasil dihapus.`,
                'danger'
            );
        }

        closeDeleteModal();
    })
    .catch(() => {
        alert('Gagal menghubungi server.');
        closeDeleteModal();
    });
}

let previewDraftId = null;

function viewDraft(id) {
    const blog = blogs.find(b => b.id === id && b.status === 'draft');
    if (!blog) return;

    previewDraftId = id;

    document.getElementById('previewImage').src        = blog.image || 'assets/img/default_destination.jpg';
    document.getElementById('previewName').textContent = blog.title || '';
    document.getElementById('previewUploader').textContent = `Uploaded by User #${blog.userId || '-'}`;
    document.getElementById('previewLocation').textContent = blog.location || '';
    document.getElementById('previewDuration').textContent = blog.duration || '';
    document.getElementById('previewBestTime').textContent = blog.bestTime || '';
    document.getElementById('previewDescription').textContent = blog.description || '';

    const hWrap = document.getElementById('previewHighlights');
    hWrap.innerHTML = '';
    [blog.highlight1, blog.highlight2, blog.highlight3, blog.highlight4]
        .filter(Boolean)
        .forEach(h => {
            hWrap.innerHTML += `
                <div class="flex items-center gap-2 bg-green-50 p-3 rounded-lg">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-sm text-gray-700">${h}</span>
                </div>
            `;
        });

    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    previewDraftId = null;
}

document.getElementById('previewApproveBtn').addEventListener('click', () => {
    if (!previewDraftId) return;

    const blog = blogs.find(b => b.id === previewDraftId);
    if (!blog) return;

    const fd = new FormData();
    fd.append('id', previewDraftId);

    fetch('admin_dashboard.php?action=approve_destination', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            alert(res.message || 'Gagal approve.');
            return;
        }
        blog.status = 'approved';
        calculateStats();
        renderBlogs();
        closePreview();
        showNotification('Draft approved', 'Draft berhasil di-approve.', 'success');
    })
    .catch(() => alert('Gagal menghubungi server.'));
});

function handlePreviewReject() {
  if (!previewDraftId) return;
  handleReject(previewDraftId);
}

        function handlePublish() {
    const title       = document.getElementById('destinationName').value;
    const location    = document.getElementById('location').value;
    const category    = document.getElementById('category').value;
    const description = document.getElementById('description').value;
    const bestTime    = document.getElementById('bestTime').value;
    const duration    = document.getElementById('duration').value;
    const h1          = document.getElementById('highlight1').value;
    const h2          = document.getElementById('highlight2').value;
    const h3          = document.getElementById('highlight3').value;
    const h4          = document.getElementById('highlight4').value;

    if (!title || !description || !category) {
        alert('Mohon isi nama destinasi, kategori, dan deskripsi!');
        return;
    }

    // MODE EDIT → update_destination
    if (editingBlogId) {
    const blog = blogs.find(b => b.id === editingBlogId);

    // JIKA DRAFT → APPROVE SAJA
    if (blog && blog.status === 'draft') {
        const fd = new FormData();
        fd.append('id', editingBlogId);

        fetch('admin_dashboard.php?action=approve_destination', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                alert(res.message || 'Gagal approve.');
                return;
            }
            blog.status = 'approved';
            calculateStats();
            renderBlogs();
            closeCreateModal();
            showNotification('Draft approved', 'Draft berhasil di-approve.', 'success');
        })
        .catch(() => alert('Gagal menghubungi server.'));

        return; // stop, jangan lanjut ke update_destination
    }

    // JIKA PUBLISHED → UPDATE DESTINATION
    const formData = new FormData();
    formData.append('id', editingBlogId);
    formData.append('title', title);
    formData.append('location', location);
    formData.append('category', category);
    formData.append('description', description);
    formData.append('best_time', bestTime);
    formData.append('duration', duration);
    formData.append('highlight1', h1);
    formData.append('highlight2', h2);
    formData.append('highlight3', h3);
    formData.append('highlight4', h4);

    // kirim path lama (jaga-jaga kalau tidak ganti foto)
    formData.append('current_image', document.getElementById('currentImagePath').value || '');

    const fileInput = document.getElementById('photoInput');
    if (fileInput.files[0]) {
        formData.append('new_image', fileInput.files[0]);
    }

    fetch('admin_dashboard.php?action=update_destination', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(r => {
        if (!r.success) {
            alert(r.message || 'Gagal update destination');
            return;
        }

        const blogIndex = blogs.findIndex(b => b.id === editingBlogId);
        if (blogIndex !== -1) {
            const b = blogs[blogIndex];
            b.title       = title;
            b.location    = location;
            b.category    = category;
            b.description = description;
            b.bestTime    = bestTime;
            b.duration    = duration;
            b.highlight1  = h1;
            b.highlight2  = h2;
            b.highlight3  = h3;
            b.highlight4  = h4;
            if (r.image_path) {
                b.image = r.image_path;
            }
        }

        calculateStats();
        renderBlogs();
        closeCreateModal();
        showNotification('Destination Updated', 'Destination berhasil diupdate.', 'success');
    })
    .catch(() => alert('Gagal menghubungi server.'));
    return;
}

    // MODE CREATE BARU → create_destination
    const formData = new FormData();
    formData.append('title', title);
    formData.append('location', location);
    formData.append('category', category);
    formData.append('description', description);
    formData.append('best_time', bestTime);
    formData.append('duration', duration);
    formData.append('highlight1', h1);
    formData.append('highlight2', h2);
    formData.append('highlight3', h3);
    formData.append('highlight4', h4);
    formData.append('image', photoData || '');

    fetch('admin_dashboard.php?action=create_destination', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(r => {
        if (!r.success) {
            alert(r.message || 'Gagal membuat destination.');
            return;
        }

        const newBlog = {
            id:        r.id ? parseInt(r.id) : (blogs.length + 1),
            title:     title,
            description,
            image:     photoData || 'assets/img/default_destination.jpg',
            views:     0,
            comments:  0,
            rating:    0,
            status:    'published',
            category,
            location,
            bestTime,
            duration,
            highlight1: h1,
            highlight2: h2,
            highlight3: h3,
            highlight4: h4
        };
        blogs.push(newBlog);

        calculateStats();
        renderBlogs();
        closeCreateModal();
        showNotification('Destination Published', 'Destination baru berhasil dibuat dan dipublikasikan.', 'success');
    })
    .catch(() => alert('Gagal menghubungi server.'));
}


        // Logout dengan modal
        function handleLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function cancelLogout() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function confirmLogout() {
            // kalau nanti pakai logout.php (session), arahkan ke sana
            // window.location.href = 'logout.php';
            window.location.href = 'index.php';
        }

        // Notification modal reusable
        function showNotification(title, message, type = 'success') {
            const modal = document.getElementById('notificationModal');
            const iconWrap = document.getElementById('notificationIcon');
            const titleEl = document.getElementById('notificationTitle');
            const msgEl = document.getElementById('notificationMessage');

            titleEl.textContent = title;
            msgEl.textContent = message;

            if (type === 'success') {
                iconWrap.className = 'w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center bg-green-100 text-green-600';
                iconWrap.innerHTML = '<span class="text-2xl">✓</span>';
            } else if (type === 'danger') {
                iconWrap.className = 'w-14 h-14 mx-auto mb-4 rounded-full flex items-center justify-center bg-red-100 text-red-600';
                iconWrap.innerHTML = '<span class="text-2xl">!</span>';
            }

            modal.classList.remove('hidden');
        }

        function closeNotification() {
            document.getElementById('notificationModal').classList.add('hidden');
        }

        // Initialize
        calculateStats();
        renderBlogs();
    </script>
</body>
</html>
