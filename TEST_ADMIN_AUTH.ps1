#!/usr/bin/env pwsh
# Quick test to verify admin page redirects without session

Write-Host "Testing Admin Pages Auth Redirects..." -ForegroundColor Cyan

$BaseURL = "http://localhost/SeniorEducation"
$AdminPages = @("admin_dashboard.php", "questions_weights.php", "user_reports.php", "settings.php")

foreach ($page in $AdminPages) {
    Write-Host "`nTesting: $page" -ForegroundColor Yellow
    
    # Use -FollowRelLink to follow redirects
    try {
        $response = Invoke-WebRequest -Uri "$BaseURL/$page" -MaximumRedirection 1 -SkipHttpErrorCheck -SessionVariable 'sess'
        
        if ($response.StatusCode -in 301, 302, 303, 307, 308) {
            Write-Host "  ✓ Correctly redirects (Status: $($response.StatusCode))" -ForegroundColor Green
        } elseif ($response.StatusCode -eq 200) {
            Write-Host "  ✗ Returned 200 OK - should redirect for non-admin session" -ForegroundColor Red
        } else {
            Write-Host "  ? Unexpected status: $($response.StatusCode)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "  ? Error: $($_.Exception.Message)" -ForegroundColor Yellow
    }
}

Write-Host "`nTest complete!" -ForegroundColor Cyan
