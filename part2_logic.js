console.log("PART2 JS LOADED");

// ---------------------------
// Part 2 has 30 questions only
// Question IDs: 21 → 50
// ---------------------------
let currentQuestion = 0;
const totalQuestions = 30;

// Save selected answers
let selectedAnswers = Array(totalQuestions).fill(null);

// 30 QUESTIONS ONLY
const questions = [
  "I enjoy working with computers and solving technical problems.",
  "I like drawing, designing, or creating visual things.",
  "I am interested in how the human body works.",
  "I like managing budgets or organizing business plans.",
  "I’m curious about social justice and human rights.",
  "I like experimenting and building things.",
  "I enjoy playing with design software like Photoshop or Canva.",
  "I enjoy helping people stay healthy or learn about medicine.",
  "I enjoy analyzing financial data.",
  "I’m interested in laws, government, or debates.",
  "I like fixing mechanical or electrical devices.",
  "I’m passionate about music, art, or photography.",
  "I enjoy researching diseases or biological systems.",
  "I like planning projects and leading teams.",
  "I like discussing ethics, justice, and philosophy.",
  "I like logical games or puzzles.",
  "I enjoy creating aesthetic visuals.",
  "I like working in laboratories or doing experiments.",
  "I like marketing and entrepreneurship.",
  "I’m interested in history and culture.",
  "I enjoy coding or using technology to solve problems.",
  "I find joy in crafting or designing new concepts.",
  "I want to help discover new medical solutions.",
  "I enjoy analyzing market trends.",
  "I’m passionate about human rights.",
  "I enjoy working with machines or systems.",
  "I find satisfaction in creative expression.",
  "I am fascinated by biology and anatomy.",
  "I am good at planning investments and budgets.",
  "I enjoy participating in debates and social causes.",
];

// DOM elements
const questionText = document.getElementById("question-text");
const buttons = document.querySelectorAll(".answer-btn");
const nextButton = document.getElementById("next-btn");
const prevButton = document.getElementById("prev-btn");
const counter = document.getElementById("counter");

// Load first question
document.addEventListener("DOMContentLoaded", () => {
  loadQuestion();
});

// Load a question
function loadQuestion() {
  const questionID = 21 + currentQuestion; // FIX: Starts at 21

  questionText.textContent = `Question ${questionID}: ${questions[currentQuestion]}`;
  counter.textContent = `Question ${currentQuestion + 21} of 50`;

  buttons.forEach((btn) => {
    btn.classList.remove("active");
    if (parseInt(btn.dataset.value) === selectedAnswers[currentQuestion]) {
      btn.classList.add("active");
    }
  });
}

// Click on an answer (only highlight)
buttons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const value = parseInt(btn.dataset.value);
    selectedAnswers[currentQuestion] = value;

    buttons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
});

// NEXT QUESTION
nextButton.addEventListener("click", () => {
  if (selectedAnswers[currentQuestion] === null) {
    alert("Please choose an answer first.");
    return;
  }

  if (currentQuestion < totalQuestions - 1) {
    currentQuestion++;
    loadQuestion();
  } else {
    savePart2Results(); // Finish
  }
});

// PREVIOUS QUESTION
prevButton.addEventListener("click", () => {
  if (currentQuestion > 0) {
    currentQuestion--;
    loadQuestion();
  }
});

// SAVE TO DATABASE
function savePart2Results() {
  console.log("Saving Part 2 answers...");

  let answersPayload = selectedAnswers.map((value, index) => ({
    question_id: 21 + index, // 21 → 50
    selected: value,
  }));

  fetch("php/assessment/save_answers.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify({
      part: 2,
      answers: answersPayload,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("SERVER:", data);
      if (!data.success) {
        alert("Error saving Part 2: " + data.message);
        return;
      }
      calculatePart2();
    })
    .catch((err) => console.error(err));
}

// CALCULATE RESULT FOR PART 2
function calculatePart2() {
  fetch("php/assessment/calculate_part_result.php?part=2")
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        window.location.href = "part2_result.html";
      } else {
        alert("Error calculating Part 2 result");
      }
    });
}
