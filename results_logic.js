document.addEventListener("DOMContentLoaded", () => {
  // Modernized result logic: fetch the three part totals for the current session
  // and call the AI predictor directly. This replaces the old get_result flow
  // which relied on an external cached result file and could return stale/static majors.

  const parts = [1, 2, 3];

  // Helper to fetch part result (uses session-based endpoint)
  function fetchPart(part) {
    return fetch(`php/assessment/part_result.php?part=${part}`, {
      cache: "no-store",
    })
      .then((res) => res.json())
      .then((data) => {
        if (data && data.success) {
          return Number(data.total_score || 0);
        }
        return null;
      })
      .catch((err) => {
        console.error("Error fetching part", part, err);
        return null;
      });
  }

  // Fetch all three parts in parallel
  Promise.all(parts.map((p) => fetchPart(p))).then((results) => {
    if (!results || results.length < 3 || results.some((r) => r === null)) {
      // If parts are missing, don't override the page — leave existing UI to handle it
      console.warn(
        "Incomplete part results; skipping AI call from results_logic.js"
      );
      return;
    }

    const scores = [results[0], results[1], results[2]];

    // Scores returned from the server are already normalized by calculate_part_result.php
    // Use them directly when calling the AI.
    const normScores = scores;

    // Call the AI predictor API with the stored normalized scores
    fetch("php/api/predict_major.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ scores: normScores }),
      cache: "no-store",
    })
      .then((res) => res.json())
      .then((ai) => {
        if (!ai || ai.success !== true) {
          console.error("AI predictor error", ai);
          return;
        }

        // Prefer centralized UI updater when available

        if (
          window.updateResultUI &&
          typeof window.updateResultUI === "function"
        ) {
          try {
            // pass stored scores so UI matches what was sent to the AI
            window.updateResultUI(ai, normScores);
            return;
          } catch (err) {
            console.error("updateResultUI failed", err);
          }
        }

        // Fallback: Update UI with the AI result (do not hardcode)
        const majorEl = document.getElementById("major-name");
        if (majorEl) majorEl.innerText = ai.major || "No major";

        const confEl = document.getElementById("confidence");
        if (confEl)
          confEl.innerText = "Confidence: " + (ai.confidence ?? "") + "%";

        // If a feedback container exists, populate it
        const feedbackEl = document.getElementById("feedback-text");
        if (feedbackEl) feedbackEl.innerText = ai.feedback || ai.message || "";

        // Optionally update hidden scores-list so other code (pie renderer) picks them up
        const scoresList = document.getElementById("scores-list");
        if (scoresList) {
          // display stored scores (already normalized by server)
          scoresList.innerHTML = `\n<li class="list-group-item"><strong>Part 1 – Personality:</strong> ${normScores[0]}</li>\n<li class="list-group-item"><strong>Part 2 – Interests:</strong> ${normScores[1]}</li>\n<li class="list-group-item"><strong>Part 3 – Academic Ability:</strong> ${normScores[2]}</li>`;
        }
      })
      .catch((err) => {
        console.error("Error calling AI predictor", err);
      });
  });
});
