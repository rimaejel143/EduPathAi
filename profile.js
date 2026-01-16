document.addEventListener("DOMContentLoaded", loadProfile);

function loadProfile() {
  fetch("php/profile/get_profile.php")
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        alert("Please login first");
        window.location.href = "sign_in.html";
        return;
      }

      document.getElementById("full_name").value = data.full_name;
      document.getElementById("email").value = data.email;
    });
}

function updateProfile() {
  const full_name = document.getElementById("full_name").value;

  fetch("php/profile/update_profile.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ full_name }),
  })
    .then((res) => res.json())
    .then((data) => {
      document.getElementById("msg").innerText = data.message;
    });
}

function changePassword() {
  const old_password = document.getElementById("old_password").value;
  const new_password = document.getElementById("new_password").value;

  fetch("php/profile/change_password.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ old_password, new_password }),
  })
    .then((res) => res.json())
    .then((data) => {
      document.getElementById("msg").innerText = data.message;
    });
}
