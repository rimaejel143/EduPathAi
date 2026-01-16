<?php
session_start();
session_unset();
session_destroy();

header("Location: /SeniorEducation/SeniorEducation/sign_in.html");
exit;
