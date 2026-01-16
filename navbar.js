// Navbar authentication manager
// Replaces default LOGIN with Profile dropdown if session exists

async function initializeNavbar() {
  const authArea = document.getElementById("auth-area");
  if (!authArea) return; // navbar.html not loaded

  try {
    const response = await fetch("./php/auth/check_session.php", {
      credentials: "include", // send PHP session cookie
    });

    const data = await response.json();

    if (data.logged_in) {
      // ✅ ONLY replace content if user is logged in
      renderProfileDropdown(data.full_name);
    }
    // ❌ if NOT logged in → do NOTHING
    // LOGIN already exists in navbar.html as fallback
  } catch (error) {
    console.error("Navbar session check failed:", error);
    // ❌ On error, do NOTHING
    // LOGIN stays visible (safe fallback)
  }
}

function renderProfileDropdown(userName) {
  const authArea = document.getElementById("auth-area");
  if (!authArea) return;

  authArea.innerHTML = `
    <div class="dropdown">
      <button
        class="btn btn-outline-light dropdown-toggle ms-2"
        type="button"
        id="profileDropdown"
        data-bs-toggle="dropdown"
        aria-expanded="false"
      >
        <i class="fas fa-user me-1"></i> ${escapeHtml(userName)}
      </button>

      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
        <li>
          <a class="dropdown-item" href="profile.html">
            <i class="fas fa-user-circle me-2"></i> My Profile
          </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item text-danger" href="#" id="logoutBtn">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  `;

  // Logout handler
  document.getElementById("logoutBtn").addEventListener("click", handleLogout);
}

function handleLogout(event) {
  event.preventDefault();

  fetch("./php/auth/logout.php", {
    method: "POST",
    credentials: "include",
  }).finally(() => {
    // Always go back to home
    window.location.href = "index.html";
  });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", initializeNavbar);
