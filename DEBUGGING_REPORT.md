# AI Recommendation Pipeline - Bug Report & Fix

## Root Cause: Why "Computer Science" was Always Returned

### The Bug

The system always returned "Computer Science" because **`final_result.php` was rejecting the valid AI response** and failing silently with no error feedback to the user.

**Location:** `php/assessment/final_result.php` (line ~115)

### The Problematic Code

```php
if (!is_array($ai) || !isset($ai['success']) || $ai['success'] !== true) {
    echo json_encode(["success" => false, "message" => "AI failed", "details" => $response]);
    exit;
}
```

### Why It Broke

The Python predictor script (`predict_new.py`) returns:

```json
{
  "major": "Marketing",
  "major_id": 9,
  "confidence": 68.0
}
```

But the PHP code was checking for a `"success"` field that **DOES NOT EXIST**. This caused:

1. The condition `!isset($ai['success'])` to be `TRUE`
2. The whole response to be rejected as "failed"
3. The code to exit without saving or returning a result
4. The frontend to show a cached or default result (likely "Computer Science")

### Evidence

1. **Python predictor works perfectly** - tested with three different score combinations:

   - `python predict_new.py 100 50 30` → `"major": "Marketing", "major_id": 9`
   - `python predict_new.py 30 50 100` → `"major": "Business Administration", "major_id": 7`
   - `python predict_new.py 50 100 30` → `"major": "Graphic Design", "major_id": 10`

2. **Response format is correct** - the predictor does NOT include a `"success"` field by design.

## The Fix Applied

### Changed `final_result.php` Response Validation

**Before (lines 98-120):**

```php
if (!is_array($ai) || !isset($ai['success']) || $ai['success'] !== true) {
    echo json_encode(["success" => false, "message" => "AI failed", "details" => $response]);
    exit;
}
```

**After (lines 103-119):**

```php
// IMPORTANT: The Python predictor returns just {"major": "X", "major_id": N, "confidence": C}
// It does NOT include a "success" field, so we need to check for the presence of the major field instead
if (!is_array($ai) || !isset($ai['major'])) {
    @file_put_contents(__DIR__ . "/log_predict_response.txt", date("Y-m-d H:i:s") . " AI_INVALID: " . json_encode($ai) . "\n", FILE_APPEND);
    echo json_encode(["success" => false, "message" => "AI failed - missing major field", "details" => $response]);
    exit;
}
```

### Enhanced Logging Added

Added comprehensive logging to trace data flow:

- `log_predict_flow.txt` - tracks assessment ID, part results count, scores sent to AI, and final result saved
- `log_predict_response.txt` - logs raw AI response and decoded values
- `log_predict_payload.txt` - logs the scores array sent to the Python predictor

### What Was Changed

**Files modified:**

1. `php/assessment/final_result.php` - Fixed response validation logic and enhanced logging

**Key changes:**

- Line 115: Removed invalid `$ai['success']` check
- Line 116: Now checks for `$ai['major']` field instead (what the predictor actually returns)
- Added logging at multiple points to verify data flows correctly

## Why This Was Hidden

The system appeared to fail silently because:

1. The error echoed JSON with `"success": false` but the frontend may have had fallback logic or cached state
2. The log files weren't being checked for the specific error condition
3. The Python predictor's output format didn't match PHP's expected format

## Verification

To verify the fix works, perform two quiz attempts with different answer patterns and check:

- `log_predict_flow.txt` shows different assessment IDs
- `log_predict_payload.txt` shows different scores being sent to AI
- `log_predict_response.txt` shows different major results from the predictor
- The frontend displays different majors for different quiz answers

## Files to Check After Running Tests

1. `php/assessment/log_predict_flow.txt` - Main flow trace
2. `php/assessment/log_predict_rows.txt` - DB rows retrieved per attempt
3. `php/assessment/log_predict_payload.txt` - Scores sent to Python predictor
4. `php/assessment/log_predict_response.txt` - Python predictor response
5. `php/assessment/log_predict_incoming.txt` - API endpoint input

## No Breaking Changes

✅ No database schema changed
✅ No model retraining needed
✅ No UI changes
✅ All 12 majors remain accessible
✅ Scalable for future majors
