# PowerShell Test Script for AI Recommendation Pipeline
# This script clears logs and provides commands to run two quiz attempts
# Then provides commands to inspect the logs and verify the fix works

Write-Host "===============================================" -ForegroundColor Green
Write-Host "AI Recommendation Pipeline - End-to-End Test" -ForegroundColor Green
Write-Host "===============================================" -ForegroundColor Green
Write-Host ""

$logDir = "C:\xampp\htdocs\SeniorEducation\SeniorEducation\php\assessment"
$logs = @(
    "log_predict_flow.txt",
    "log_predict_rows.txt",
    "log_predict_payload.txt",
    "log_predict_response.txt",
    "log_predict_incoming.txt",
    "log_calculate_part.txt",
    "log_save_answers.txt"
)

Write-Host "STEP 1: Clear all log files" -ForegroundColor Yellow
Write-Host "---" -ForegroundColor Yellow
foreach ($log in $logs) {
    $path = Join-Path $logDir $log
    if (Test-Path $path) {
        Remove-Item $path -Force
        Write-Host "✓ Cleared $log"
    }
}
Write-Host ""

Write-Host "STEP 2: Manual Instructions" -ForegroundColor Yellow
Write-Host "---" -ForegroundColor Yellow
Write-Host "1. Open your browser and go to: http://localhost/SeniorEducation/SeniorEducation/index.html"
Write-Host "2. Login or create an account"
Write-Host "3. START FIRST QUIZ ATTEMPT"
Write-Host "   - Answer mostly AGREE / STRONGLY AGREE on all parts"
Write-Host "   - Complete all 3 parts and submit"
Write-Host "   - Note the final major and confidence score"
Write-Host ""
Write-Host "4. LOGOUT or clear session"
Write-Host ""
Write-Host "5. START SECOND QUIZ ATTEMPT (same or different user)"
Write-Host "   - Answer mostly DISAGREE / STRONGLY DISAGREE on all parts"
Write-Host "   - Complete all 3 parts and submit"
Write-Host "   - Note if final major and confidence differ from first attempt"
Write-Host ""

Write-Host "STEP 3: After completing both attempts, run these commands to inspect logs:" -ForegroundColor Yellow
Write-Host "---" -ForegroundColor Yellow
Write-Host ""

Write-Host "# View the main flow trace (assessment ID, scores, major):" -ForegroundColor Cyan
Write-Host "Get-Content -Path '$logDir\log_predict_flow.txt' -Tail 50"
Write-Host ""

Write-Host "# View the database rows retrieved:" -ForegroundColor Cyan
Write-Host "Get-Content -Path '$logDir\log_predict_rows.txt' -Tail 50"
Write-Host ""

Write-Host "# View the AI payload sent:" -ForegroundColor Cyan
Write-Host "Get-Content -Path '$logDir\log_predict_payload.txt' -Tail 50"
Write-Host ""

Write-Host "# View the Python predictor response:" -ForegroundColor Cyan
Write-Host "Get-Content -Path '$logDir\log_predict_response.txt' -Tail 50"
Write-Host ""

Write-Host "# View all part calculations:" -ForegroundColor Cyan
Write-Host "Get-Content -Path '$logDir\log_calculate_part.txt' -Tail 50"
Write-Host ""

Write-Host "STEP 4: Verify the fix by checking:" -ForegroundColor Yellow
Write-Host "---" -ForegroundColor Yellow
Write-Host "✓ Are there TWO different assessment IDs? (proof of new attempts)"
Write-Host "✓ Do the scores differ between attempts?"
Write-Host "✓ Does the Python predictor return DIFFERENT majors?"
Write-Host "✓ Does the final result page show DIFFERENT majors?"
Write-Host ""

Write-Host "EXPECTED RESULTS IF FIX WORKS:" -ForegroundColor Green
Write-Host "---" -ForegroundColor Green
Write-Host "✓ Attempt 1 (Agree answers) → Different major (e.g., Business Admin)"
Write-Host "✓ Attempt 2 (Disagree answers) → Different major (e.g., Graphic Design)"
Write-Host "✓ log_predict_flow.txt shows: FINAL_RESULT_SAVED with different majors"
Write-Host "✓ log_predict_payload.txt shows: Different score arrays sent"
Write-Host "✓ log_predict_response.txt shows: Different major responses from Python"
Write-Host ""

Write-Host "If you see the SAME major both times, the fix didn't work. In that case:" -ForegroundColor Red
Write-Host "1. Paste the contents of log_predict_flow.txt"
Write-Host "2. Paste the contents of log_predict_response.txt"
Write-Host "3. Report any errors in the logs"
