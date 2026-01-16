<?php
require_once __DIR__ . '/admin_auth_check.php';
require_once __DIR__ . '/../config/db.php';
ensure_admin();

$stmt = $pdo->query("
  SELECT question_id, question_text, part_id
  FROM questions
  ORDER BY part_id, question_id
");
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
/* allow students only */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
  header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
  exit;
}

$adminName = $_SESSION['full_name'] ?? 'Admin';

/* get profile image from DB */
$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$imgFile = $stmt->fetchColumn() ?: 'default_profile.png';

$profileImage = '/SeniorEducation/SeniorEducation/imgs/' . $imgFile;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin - Questions & Weights</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

  <style>
    :root {
      --primary-blue-dark: #1b3a5e;
      --accent-yellow: #fdc80a;
      --primary-blue-light: #4e7ea7;
    }
    .admin-navbar { background-color: var(--primary-blue-dark); }
    .admin-layout { height: calc(100vh - 56px); }
    .sidebar {
      width: 250px; min-width: 250px;
      background-color: #f8f9fa;
      border-right: 1px solid #ddd;
      padding-top: 20px;
    }
    .sidebar .nav-link {
      color: var(--primary-blue-dark);
      padding: 10px 15px;
      border-left: 3px solid transparent;
    }
    .sidebar .nav-link:hover {
      color: var(--primary-blue-light);
      background-color: #e9ecef;
    }
    .sidebar .nav-item.active-q .nav-link {
      color: var(--primary-blue-dark);
      background-color: #e9ecef;
      border-left-color: var(--accent-yellow);
      font-weight: bold;
    }
    .admin-navbar .navbar-brand {
      display: flex; align-items: center; text-decoration: none;
    }
    .admin-navbar .navbar-brand .logo-icon {
      color: var(--accent-yellow) !important;
    }
    .admin-table th {
      background-color: #f1f1f1;
      color: var(--primary-blue-dark);
      font-weight: 600;
    }
  </style>
</head>

<body>

<nav class="navbar navbar-dark admin-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="/SeniorEducation/SeniorEducation/index.html">
       <i class="fas fa-brain me-2 fs-4 logo-icon"></i>
      <strong>Edu<span style="color:#fdc80a">PathAI</span></strong>
           
    </a>
    <div class="d-flex align-items-center">
      <span class="navbar-text me-3 text-white">Admin Panel</span>
<a href="/SeniorEducation/SeniorEducation/php/profile/my_profile.php" class="me-2">
  <img src="<?= $profileImage ?>"
       width="35"
       height="35"
       class="rounded-circle border border-light"
       style="object-fit:cover">
</a>       <a class="btn btn-sm btn-outline-light" href="admin_logout.php">Logout</a>
    </div>
  </div>
</nav>

<div class="d-flex admin-layout">
  <div class="sidebar">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home me-2"></i> Dashboard</a>
      </li>
      <li class="nav-item active-q">
        <a class="nav-link" href="questions_weights.php"><i class="fas fa-question-circle me-2"></i> Questions & Weights</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="user_reports.php"><i class="fas fa-chart-bar me-2"></i> User Reports</a>
      </li>
      <li class="nav-item">
            <a class="nav-link" href="manage_forum.php">
              <i class="fas fa-comments me-2"></i> Manage Forum
            </a>
          </li>
           <li class="nav-item ">
            <a class="nav-link" href="manage_majors.php">
              <i class="fas fa-graduation-cap me-2"></i> Manage majors
            </a>
          </li>
      <li class="nav-item">
  <a class="nav-link" href="contact_messages.php">
    <i class="fas fa-envelope me-2"></i> Contact Messages
  </a>
</li>

      <li class="nav-item">
        <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a>
      </li>
    </ul>
  </div>

  <main class="main-content p-4 flex-grow-1 overflow-auto bg-white">
    <div class="container-fluid">
      <h2 class="mb-4">Questions & Weights Management</h2>

      <!-- SEARCH -->
      <div class="mb-3 w-50">
        <input
          id="searchInput"
          type="text"
          class="form-control"
          placeholder="Search by ID, question text, or part (ex: 3 / calm / part 1)"
        />
      </div>

      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm admin-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Question</th>
                  <th>Part</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="questionsBody">
                <?php foreach ($questions as $q): ?>
                  <tr
                    data-id="<?= $q['question_id'] ?>"
                    data-text="<?= strtolower(htmlspecialchars($q['question_text'], ENT_QUOTES)) ?>"
                    data-part="part <?= $q['part_id'] ?>"
                  >
                    <td><?= $q['question_id'] ?></td>
                    <td><?= htmlspecialchars($q['question_text']) ?></td>
                    <td>Part <?= $q['part_id'] ?></td>
                    <td>
                      <button
                        class="btn btn-sm btn-info edit-question-btn"
                        data-id="<?= $q['question_id'] ?>"
                      >
                        Edit
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalTitle">Edit Question</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-question-id">

        <div class="mb-3">
          <label class="form-label fw-bold">Question Text</label>
          <textarea id="edit-question-text" class="form-control" rows="4"></textarea>
        </div>

        <div class="alert alert-danger d-none" id="editError"></div>
        <div class="alert alert-success d-none" id="editSuccess">Saved successfully</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary" id="save-question-btn">Save</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ================= SEARCH ================= */
  const searchInput = document.getElementById('searchInput');
  const rows = document.querySelectorAll('#questionsBody tr');

  searchInput.addEventListener('input', () => {
    const v = searchInput.value.toLowerCase().trim();

    rows.forEach(tr => {
      const id = tr.dataset.id;
      const text = tr.dataset.text;
      const part = tr.dataset.part;

      const match =
        !v ||
        id.includes(v) ||
        text.includes(v) ||
        part.includes(v);

      tr.style.display = match ? '' : 'none';
    });
  });

  /* ================= EDIT ================= */
  const modal = new bootstrap.Modal(document.getElementById('editModal'));

  document.querySelector('#questionsBody').addEventListener('click', async e => {
    const btn = e.target.closest('.edit-question-btn');
    if (!btn) return;

    const id = btn.dataset.id;

    const res = await fetch('get_question_details.php?question_id=' + id, { cache:'no-store' });
    const data = await res.json();

    if (!data.success) {
      alert('Load failed');
      return;
    }

    document.getElementById('edit-question-id').value = id;
    document.getElementById('edit-question-text').value = data.question.question_text;

    modal.show();
  });

  document.getElementById('save-question-btn').addEventListener('click', async () => {
    const id = document.getElementById('edit-question-id').value;
    const text = document.getElementById('edit-question-text').value.trim();

    if (!text) {
      alert('Text required');
      return;
    }

    const res = await fetch('save_question.php', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body: `question_id=${id}&question_text=${encodeURIComponent(text)}`
    });

    const data = await res.json();
    if (data.success) location.reload();
    else alert('Save failed');
  });

});
</script>

</body>
</html>
