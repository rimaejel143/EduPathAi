console.log("PART2 JS LOADED");

let currentQuestion = 0;
let totalQuestions = 0;
let questions = [];
let selectedAnswers = [];

// Safe init
(function init() {
  const run = () => {
    console.log("DOM READY");

    const questionText = document.getElementById("question-text");
    const buttons = document.querySelectorAll(".answer-btn");
    const nextButton = document.getElementById("next-btn");
    const prevButton = document.getElementById("prev-btn");
    const counter = document.getElementById("counter");

    // Load questions from DB
    fetch("php/assessment/get_questions.php?part=2", {
      credentials: "same-origin",
    })
      .then((res) => res.json())
      .then((data) => {
        if (!data.success) {
          alert("Failed to load Part 2 questions");
          return;
        }

        questions = data.questions;
        totalQuestions = questions.length;
        selectedAnswers = Array(totalQuestions).fill(null);

        // Attempt to restore any saved progress (answers and last position)
        // loadSavedProgress will update `selectedAnswers` and `currentQuestion`
        // and refresh the UI accordingly.
        loadSavedProgress(questionText, buttons, counter).then(() => {
          // Ensure UI is shown even if no progress exists
          counter.textContent = `Question ${
            currentQuestion + 1
          } of ${totalQuestions}`;
          loadQuestion(questionText, buttons);
        });
      })
      .catch((err) => {
        console.error(err);
        alert("Error loading questions");
      });

    // Answer click
    buttons.forEach((btn) => {
      btn.addEventListener("click", () => {
        const value = parseInt(btn.dataset.value);
        selectedAnswers[currentQuestion] = value;
        highlightSelected(value, buttons);
      });
    });

    nextButton.addEventListener("click", () =>
      nextQuestion(questionText, buttons, counter)
    );

    prevButton.addEventListener("click", () =>
      previousQuestion(questionText, buttons, counter)
    );
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", run);
  } else {
    run();
  }
})();

// Load question
function loadQuestion(questionText, buttons) {
  const q = questions[currentQuestion];

  questionText.textContent = `Question ${currentQuestion + 1}: ${
    q.question_text
  }`;

  buttons[0].textContent = q.option_a;
  buttons[1].textContent = q.option_b;
  buttons[2].textContent = q.option_c;
  buttons[3].textContent = q.option_d;

  highlightSelected(selectedAnswers[currentQuestion], buttons);
}

// Highlight selected
function highlightSelected(value, buttons) {
  buttons.forEach((btn) =>
    btn.classList.toggle("active", parseInt(btn.dataset.value) === value)
  );
}

// Next
function nextQuestion(questionText, buttons, counter) {
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

// Previous
function previousQuestion(questionText, buttons, counter) {
  if (currentQuestion > 0) {
    currentQuestion--;
    loadQuestion(questionText, buttons);
    counter.textContent = `Question ${
      currentQuestion + 1
    } of ${totalQuestions}`;
  }
}

// ====== LOAD SAVED PROGRESS (AFTER QUESTIONS LOADED) ======
// Called after questions are loaded from the database.
// Restores: (1) all previously selected answers, (2) the last saved question position
async function loadSavedProgress(questionText, buttons, counter) {
  try {
    const res = await fetch("./php/student/load_progress.php?part=2", {
      credentials: "include",
    });

    const data = await res.json();
    if (!data.success || !data.progress) return;

    const progress = data.progress;
    console.log("Loaded saved progress:", progress);

    if (progress.answers && typeof progress.answers === "object") {
      for (const questionId in progress.answers) {
        const selectedOption = progress.answers[questionId];
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

    // Resume from last answered question (not last visited)
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

    counter.textContent = `Question ${
      currentQuestion + 1
    } of ${totalQuestions}`;
    loadQuestion(questionText, buttons);
  } catch (err) {
    console.error("loadSavedProgress error:", err);
    // Fail silently
  }
}

// Save answers
function saveAnswers() {
  let answersPayload = selectedAnswers.map((value, index) => ({
    question_id: questions[index].question_id, // 🔴 مهم
    selected: value,
  }));

  fetch("php/assessment/save_answers.php", {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      part: 2,
      answers: answersPayload,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (!data.success) {
        alert("Error saving Part 2");
        return;
      }
      calculatePart2();
    })
    .catch(() => alert("Network error"));
}

// Calculate result
function calculatePart2() {
  fetch("php/assessment/calculate_part_result.php?part=2", {
    credentials: "same-origin",
  })
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        window.location.href = "part2_result.html";
      } else {
        alert("Error calculating Part 2 result");
      }
    });
}
