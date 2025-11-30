document.addEventListener("DOMContentLoaded", () => {
  // الحصول على assessment_id من URL أو localStorage
  const urlParams = new URLSearchParams(window.location.search);
  const assessmentId =
    urlParams.get("assessment_id") ||
    localStorage.getItem("last_assessment_id");

  if (!assessmentId) {
    document.getElementById("major-name").innerText =
      "No assessment found. Please take the test first.";
    return;
  }

  // جلب النتيجة من قاعدة البيانات
  fetch(`php/api/get_result.php?assessment_id=${assessmentId}`)
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("major-name").innerText =
          data.data.major_name || "No major found";
        document.getElementById("feedback-text").innerText =
          data.data.feedback || "No feedback available.";
      } else {
        document.getElementById("major-name").innerText = "No result found";
      }
    })
    .catch((err) => {
      console.error("Error fetching result:", err);
      document.getElementById("major-name").innerText = "Error loading result.";
    });
});
