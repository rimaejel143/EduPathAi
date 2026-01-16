console.log("PART3 JS LOADED");

// ---------------------------
// VARIABLES
// ---------------------------
let currentQuestion = 0;
let totalQuestions = 0;
let questions = [];
let answers = [];

// Trait scores (Part 3 only)
let traitScores = {
  LogicalReasoning: 0,
  Communication: 0,
  MemoryRecall: 0,
  TechnicalSkill: 0,
  Creativity: 0,
};

// ---------------------------
// DOM ELEMENTS
// ---------------------------
const questionText = document.getElementById("question-text");
const buttons = document.querySelectorAll(".answer-btn");
const nextButton = document.getElementById("next-btn");
const prevButton = document.getElementById("prev-btn");
const counter = document.getElementById("counter");

// ---------------------------
// LOAD QUESTIONS FROM DB
// ---------------------------
fetch("php/assessment/get_questions.php?part=3")
  .then((res) => res.json())
  .then((data) => {
    if (!data.success) {
      alert("Failed to load questions");
      return;
    }

    questions = data.questions;
    totalQuestions = questions.length;
    answers = new Array(totalQuestions).fill(null);

    // Attempt to restore any saved progress (answers and last position)
    // loadSavedProgress will update `answers` and `currentQuestion`
    // and refresh the UI accordingly.
    loadSavedProgress().then(() => {
      counter.textContent = `Question ${currentQuestion + 51} of ${
        50 + totalQuestions
      }`;
      loadQuestion();
    });
  })
  .catch((err) => {
    console.error(err);
    alert("Error loading questions");
  });

// ---------------------------
// LOAD QUESTION
// ---------------------------
function loadQuestion() {
  const q = questions[currentQuestion];

  questionText.textContent = `Question ${currentQuestion + 51}: ${
    q.question_text
  }`;
  counter.textContent = `Question ${currentQuestion + 51} of ${
    50 + totalQuestions
  }`;

  buttons[0].textContent = q.option_a;
  buttons[1].textContent = q.option_b;
  buttons[2].textContent = q.option_c;
  buttons[3].textContent = q.option_d;

  buttons.forEach((b) => b.classList.remove("active"));

  if (answers[currentQuestion] !== null) {
    const btn = document.querySelector(
      `.answer-btn[data-value="${answers[currentQuestion]}"]`
    );
    if (btn) btn.classList.add("active");
  }
}

// ---------------------------
// ANSWER CLICK
// ---------------------------
buttons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const value = parseInt(btn.dataset.value);
    answers[currentQuestion] = value;

    buttons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");
  });
});

// ---------------------------
// NEXT BUTTON
// ---------------------------
nextButton.addEventListener("click", () => {
  if (answers[currentQuestion] === null) {
    alert("Please select an answer before continuing.");
    return;
  }

  if (currentQuestion < totalQuestions - 1) {
    currentQuestion++;
    loadQuestion();
  } else {
    saveResultsToServer();
  }
});

// ---------------------------
// PREVIOUS BUTTON
// ---------------------------
prevButton.addEventListener("click", () => {
  if (currentQuestion > 0) {
    currentQuestion--;
    loadQuestion();
  }
});

// ---------------------------
// CALCULATE TRAIT SCORES
// ---------------------------
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

// ---------------------------
// SAVE ANSWERS TO SERVER
// ---------------------------
function saveResultsToServer() {
  console.log("Saving Part 3 answers...");

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
      if (!data.success) {
        alert("Error saving answers: " + data.message);
        return;
      }

      calculateScores();
      calculatePart3();
    })
    .catch((err) => {
      console.error(err);
      alert("Network error while saving Part 3.");
    });
}

// ---------------------------
// CALCULATE PART RESULT
// ---------------------------
function calculatePart3() {
  fetch("php/assessment/calculate_part_result.php?part=3", {
    credentials: "same-origin",
  })
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        window.location.href = "part3_result.html";
      } else {
        alert("Error calculating Part 3 result.");
      }
    });
}

// ====== LOAD SAVED PROGRESS (AFTER QUESTIONS LOADED) ======
// Restores previously saved answers and resumes at last answered question
async function loadSavedProgress() {
  try {
    const res = await fetch("./php/student/load_progress.php?part=3", {
      credentials: "include",
    });

    const data = await res.json();
    if (!data.success || !data.progress) return;

    const progress = data.progress;
    console.log("Loaded saved progress (Part 3):", progress);

    if (progress.answers && typeof progress.answers === "object") {
      for (const questionId in progress.answers) {
        const selectedOption = progress.answers[questionId];
        const questionIndex = questions.findIndex(
          (q) => q.question_id === parseInt(questionId)
        );
        if (questionIndex >= 0) {
          answers[questionIndex] = selectedOption;
          console.log(
            `Restored Q${questionIndex + 51}: answer = ${selectedOption}`
          );
        }
      }
    }

    // Resume from last answered question
    let lastAnsweredIndex = -1;
    answers.forEach((ans, index) => {
      if (ans !== null && ans !== undefined) lastAnsweredIndex = index;
    });

    if (lastAnsweredIndex >= 0) currentQuestion = lastAnsweredIndex;
    else currentQuestion = 0;

    counter.textContent = `Question ${currentQuestion + 51} of ${
      50 + totalQuestions
    }`;
    loadQuestion();
  } catch (err) {
    console.error("loadSavedProgress (Part 3) error:", err);
  }
}
