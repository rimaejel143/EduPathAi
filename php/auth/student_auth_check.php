<?php
session_start();

function ensure_student() {
    if (!isset($_SESSION['student_id'])) {
        header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
        exit;
    }
}
