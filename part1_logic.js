console.log("PART1 JS LOADED");

// إعداد المتغيرات
let currentQuestion = 0;
const totalQuestions = 20;
let selectedAnswers = Array(totalQuestions).fill(null);

// 🧠 Safe Init (بديل عن DOMContentLoaded)
(function init() {
  const run = () => {
    console.log("DOM READY");

    const questionText = document.getElementById("question-text");
    const buttons = document.querySelectorAll(".btn-group button");
    const nextButton = document.getElementById("next-btn");
    const prevButton = document.getElementById("prev-btn");
    const counter = document.getElementById("counter");

    console.log("Buttons found:", buttons.length);
    counter.textContent = `Question ${currentQuestion + 1} of 20`;

    loadQuestion(questionText, buttons);

    buttons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const value = parseInt(btn.dataset.value);
        selectedAnswers[currentQuestion] = value;
        highlightSelected(value, buttons);
      });
    });

    nextButton.addEventListener("click", () =>
      nextQuestion(questionText, buttons)
    );

    prevButton.addEventListener("click", () =>
      previousQuestion(questionText, buttons)
    );
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", run);
  } else {
    run();
  }
})();

// تحميل السؤال
function loadQuestion(questionText, buttons) {
  questionText.textContent = `Question ${currentQuestion + 1}: ${
    questions[currentQuestion]
  }`;
  highlightSelected(selectedAnswers[currentQuestion], buttons);
}

// تلوين الاختيار
function highlightSelected(value, buttons) {
  buttons.forEach((btn) =>
    btn.classList.toggle("active", parseInt(btn.dataset.value) === value)
  );
}

// التالي
function nextQuestion(questionText, buttons) {
  if (selectedAnswers[currentQuestion] === null) {
    alert("Please select an answer.");
    return;
  }

  if (currentQuestion < totalQuestions - 1) {
    currentQuestion++;
    loadQuestion(questionText, buttons);
    counter.textContent = `Question ${
      currentQuestion + 1
    } of ${totalQuestions}`;
  } else {
    saveAnswers();
  }
}

// السابق
function previousQuestion(questionText, buttons) {
  if (currentQuestion > 0) {
    currentQuestion--;
    loadQuestion(questionText, buttons);
    counter.textContent = `Question ${
      currentQuestion + 1
    } of ${totalQuestions}`;
  }
}

// حفظ الإجابات
function saveAnswers() {
  console.log("SAVE ANSWERS FUNCTION STARTED");

  let answersPayload = selectedAnswers.map((value, index) => ({
    question_id: index + 1,
    selected: value,
  }));

  fetch("php/assessment/save_answers.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      part: 1,
      answers: answersPayload,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("SERVER JSON:", data);

      if (!data.success) {
        alert("Error saving answers: " + data.message);
        return;
      }

      calculatePartResult();
    })
    .catch((err) => {
      console.error("NETWORK ERROR:", err);
      alert("Network error");
    });
}

// حساب النتيجة
function calculatePartResult() {
  fetch("php/assessment/calculate_part_result.php?part=1", {
    credentials: "same-origin",
  })
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        window.location.href = "part1_result.html";
      } else {
        alert("Error calculating result");
      }
    });
}

// الأسئلة
const questions = [
  "I enjoy leading group projects.",
  "I communicate easily with others.",
  "I pay attention to small details.",
  "I stay calm under pressure.",
  "I like helping others achieve their goals.",
  "I am confident in decision-making.",
  "I manage my time effectively.",
  "I like planning and organizing.",
  "I adapt well to new situations.",
  "I stay motivated even when facing challenges.",
  "I am open to new ideas.",
  "I handle criticism positively.",
  "I like analyzing problems.",
  "I find it easy to work in a team.",
  "I set goals and work hard to achieve them.",
  "I like mentoring others.",
  "I can manage conflicts effectively.",
  "I am detail-oriented in my work.",
  "I am comfortable speaking in front of groups.",
  "I enjoy motivating people.",
];
