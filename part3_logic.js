// part3_logic.js

let currentQuestion = 0;
const totalQuestions = 10;

// Save answers for each question
let answers = new Array(totalQuestions).fill(null);

// Trait scores
let traitScores = {
  LogicalReasoning: 0,
  Communication: 0,
  MemoryRecall: 0,
  TechnicalSkill: 0,
  Creativity: 0,
};

// QUESTIONS
const questions = [
  "I can easily understand logical relationships and solve puzzles.",
  "I express ideas clearly in written and spoken form.",
  "I can remember information and details easily.",
  "I am comfortable using technology for academic work.",
  "I often come up with creative ideas for projects.",
  "I can identify errors and correct them quickly.",
  "I like explaining difficult concepts to others.",
  "I can recall facts or data accurately during tests.",
  "I use digital tools efficiently for studying.",
  "I enjoy thinking of unique solutions to problems.",
];

// DOM ELEMENTS
const questionText = document.getElementById("question-text");
const buttons = document.querySelectorAll(".answer-btn");
const nextButton = document.getElementById("next-btn");
const prevButton = document.getElementById("prev-btn");
const counter = document.getElementById("counter");

// INIT
loadQuestion();

// Load question
function loadQuestion() {
  questionText.textContent = `Question ${currentQuestion + 51}: ${
    questions[currentQuestion]
  }`;

  // Counter appears as 51–60 (Part3)
  counter.textContent = `Question ${currentQuestion + 51} of 60`;

  // Clear active states
  buttons.forEach((b) => b.classList.remove("active"));

  // Restore selected answer
  if (answers[currentQuestion] !== null) {
    let btn = document.querySelector(
      `.answer-btn[data-value="${answers[currentQuestion]}"]`
    );
    if (btn) btn.classList.add("active");
  }
}

// When answer clicked
buttons.forEach((btn) => {
  btn.addEventListener("click", () => {
    let value = parseInt(btn.dataset.value);
    answers[currentQuestion] = value;

    buttons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
});

// NEXT BUTTON
nextButton.addEventListener("click", () => {
  if (answers[currentQuestion] === null) {
    alert("Please select an answer before continuing.");
    return;
  }

  if (currentQuestion === totalQuestions - 1) {
    calculateScores();
    saveResultsToServer();
    return;
  }

  currentQuestion++;
  loadQuestion();
});

// PREVIOUS BUTTON
prevButton.addEventListener("click", () => {
  if (currentQuestion === 0) return;
  currentQuestion--;
  loadQuestion();
});

// Calculate Trait Scores
function calculateScores() {
  traitScores = {
    LogicalReasoning: 0,
    Communication: 0,
    MemoryRecall: 0,
    TechnicalSkill: 0,
    Creativity: 0,
  };

  for (let i = 0; i < totalQuestions; i++) {
    const value = answers[i];
    if (value === null) continue;

    const type = i % 5;

    if (type === 0) traitScores.LogicalReasoning += value;
    if (type === 1) traitScores.Communication += value;
    if (type === 2) traitScores.MemoryRecall += value;
    if (type === 3) traitScores.TechnicalSkill += value;
    if (type === 4) traitScores.Creativity += value;
  }
}

// SAVE TO BACKEND — CORRECT VERSION
function saveResultsToServer() {
  console.log("Saving part 3 answers...");

  // Questions for Part3 are IDs: 51 → 60
  let answersPayload = answers.map((value, index) => ({
    question_id: 51 + index,
    selected: value,
  }));

  fetch("php/assessment/save_answers.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "same-origin",
    body: JSON.stringify({
      part: 3,
      answers: answersPayload,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      console.log("SERVER:", data);

      if (!data.success) {
        alert("Error saving answers: " + data.message);
        return;
      }

      calculatePart3();
    })
    .catch((err) => {
      console.error("Network error:", err);
      alert("Network error while saving Part 3.");
    });
}

// CALCULATE PART RESULT
function calculatePart3() {
  fetch("php/assessment/calculate_part_result.php?part=3")
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        window.location.href = "part3_result.html";
      } else {
        alert("Error calculating Part 3 result.");
      }
    });
}
