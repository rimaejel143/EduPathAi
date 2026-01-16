<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* هذا الملف فقط للتحقق */
if (!isset($_SESSION['user_id'])) {
  header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
  exit;
}
