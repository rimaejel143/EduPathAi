console.log("PART1 JS LOADED");

// ====== Global State ======
let currentQuestion = 0;
let questions = [];
let selectedAnswers = [];

// ====== DOM Elements ======
let questionText;
let buttons;
let nextButton;
let prevButton;
let counter;

// ====== LOAD SAVED PROGRESS (AFTER QUESTIONS LOADED) ======
// Called after questions are loaded from the database.
// Restores: (1) all previously selected answers, (2) the last saved question position
async function loadSavedProgress() {
  try {
    // Fetch saved progress from backend
    // Returns: last_question_number and answers map {question_id: selected_option}
    const res = await fetch("./php/student/load_progress.php?part=1", {
      credentials: "include",
    });

    const data = await res.json();
    // If no progress exists or fetch fails, silently return - test starts from Q1
    if (!data.success || !data.progress) return;

    const progress = data.progress;
    console.log("Loaded saved progress:", progress);

    // STEP 1: Restore all saved answers into selectedAnswers[]
    // progress.answers is a map: {question_id: selected_option}
    // Map it to selectedAnswers[index] by matching questions[index].question_id
    if (progress.answers && typeof progress.answers === "object") {
      for (const questionId in progress.answers) {
        const selectedOption = progress.answers[questionId];

        // Find the array index of this question by matching question_id
        const questionIndex = questions.findIndex(
          (q) => q.question_id === parseInt(questionId)
        );
        if (questionIndex >= 0) {
          selectedAnswers[questionIndex] = selectedOption;
          console.log(
            `Restored Q${questionIndex + 1}: answer = ${selectedOption}`
          );
        }
      }
    }

    // STEP 2 (FIX): resume from LAST answered question (not last visited)
    let lastAnsweredIndex = -1;

    selectedAnswers.forEach((ans, index) => {
      if (ans !== null && ans !== undefined) {
        lastAnsweredIndex = index;
      }
    });

    if (lastAnsweredIndex >= 0) {
      currentQuestion = lastAnsweredIndex;
    } else {
      currentQuestion = 0;
    }

    // STEP 3: Refresh UI with restored state
    counter.textContent = `Question ${currentQuestion + 1} of ${
      questions.length
    }`;
    renderQuestion(); // Highlights the saved answer for current question
  } catch (err) {
    console.error("loadSavedProgress error:", err);
    // Fail silently - test continues normally from Question 1
  }
}

// ====== INIT ======
document.addEventListener("DOMContentLoaded", async () => {
  console.log("DOM READY");

  // bind elements
  questionText = document.getElementById("question-text");
  buttons = document.querySelectorAll(".btn-group button");
  nextButton = document.getElementById("next-btn");
  prevButton = document.getElementById("prev-btn");
  counter = document.getElementById("counter");

  // load questions from DB
  await loadQuestionsFromDB();

  // ✅ load saved progress AFTER questions exist
  await loadSavedProgress();

  // bind button events
  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const value = parseInt(btn.dataset.value);
      selectedAnswers[currentQuestion] = value;
      highlightSelected(value);
    });
  });

  nextButton.addEventListener("click", nextQuestion);
  prevButton.addEventListener("click", previousQuestion);
});

// ====== LOAD QUESTIONS ======
async function loadQuestionsFromDB() {
  try {
    const res = await fetch(
      "/SeniorEducation/SeniorEducation/php/assessment/get_questions.php?part=1",
      { credentials: "include" }
    );
    const data = await res.json();

    if (!data.success) {
      alert("Failed to load questions");
      return;
    }

    questions = data.questions;
    selectedAnswers = Array(questions.length).fill(null);

    counter.textContent = `Question 1 of ${questions.length}`;
    renderQuestion();
  } catch (err) {
    console.error(err);
    alert("Error loading questions");
  }
}

// ====== RENDER QUESTION ======
function renderQuestion() {
  const q = questions[currentQuestion];
  if (!q) return;

  questionText.textContent = `Question ${currentQuestion + 1}: ${
    q.question_text
  }`;

  buttons[0].textContent = q.option_a;
  buttons[1].textContent = q.option_b;
  buttons[2].textContent = q.option_c;
  buttons[3].textContent = q.option_d;

  highlightSelected(selectedAnswers[currentQuestion]);
}

// ====== UI HELPERS ======
function highlightSelected(value) {
  buttons.forEach((btn) =>
    btn.classList.toggle("active", parseInt(btn.dataset.value) === value)
  );
}

// ====== NAVIGATION ======
function nextQuestion() {
  if (selectedAnswers[currentQuestion] === null) {
    alert("Please select an answer.");
    return;
  }

  if (currentQuestion < questions.length - 1) {
    currentQuestion++;
    counter.textContent = `Question ${currentQuestion + 1} of ${
      questions.length
    }`;
    renderQuestion();
  } else {
    saveAnswers();
  }
}

function previousQuestion() {
  if (currentQuestion > 0) {
    currentQuestion--;
    counter.textContent = `Question ${currentQuestion + 1} of ${
      questions.length
    }`;
    renderQuestion();
  }
}

// ====== SAVE ANSWERS ======
function saveAnswers() {
  console.log("SAVE ANSWERS FUNCTION STARTED");

  const answersPayload = questions.map((q, index) => ({
    question_id: q.question_id, // ✅ مهم
    selected: selectedAnswers[index],
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
      if (!data.success) {
        alert("Error saving answers: " + data.message);
        return;
      }
      calculatePartResult();
    })
    .catch(() => alert("Network error"));
}

// ====== CALCULATE RESULT ======
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
