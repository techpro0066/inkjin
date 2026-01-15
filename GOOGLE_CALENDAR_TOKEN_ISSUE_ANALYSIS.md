# Google Calendar Token Refresh Issue - Analysis & Solution

## Problem Statement

Users need to reconnect Google Calendar after some time, even though they've already connected it once. Additionally, events are being added to Google Calendar but not being fetched when checking availability.

---

## Root Cause Analysis

### 1. **How Google OAuth Tokens Work**

Google OAuth provides two types of tokens:
- **Access Token**: Short-lived (expires in ~1 hour), used to make API calls
- **Refresh Token**: Long-lived (doesn't expire unless revoked), used to get new access tokens

### 2. **Current Implementation Flow**

#### **Initial Connection (callback method)**
```
1. User authorizes → Google returns authorization code
2. Exchange code for tokens → Gets access_token + refresh_token
3. Store tokens in database → user_details.google_calendar_token (JSON)
```

**Code Location**: `GoogleCalendarController::callback()` (Line 61-103)

**Issue**: The tokens are stored correctly, BUT:
- If Google doesn't provide a `refresh_token` (happens if user already granted permission before)
- The refresh token might be missing from the stored token array

#### **Token Refresh Logic (refreshToken method)**
```
1. Get stored token from database
2. Extract refresh_token from token array
3. Call Google API to get new access_token
4. Update database with new token
```

**Code Location**: `GoogleCalendarController::refreshToken()` (Line 152-182)

**Critical Issues**:

1. **Missing Refresh Token Check** (Line 166):
   ```php
   $client->refreshToken($token['refresh_token'] ?? null);
   ```
   - If `refresh_token` is `null` or missing, this silently fails
   - No error handling for missing refresh token
   - Returns `null` without clear indication why

2. **No Validation Before Refresh**:
   - Doesn't check if `refresh_token` exists before attempting refresh
   - Doesn't verify if refresh token is still valid

3. **Silent Failure**:
   - When refresh fails, methods return `null` or empty array
   - No user notification that reconnection is needed
   - Events can't be fetched, but user doesn't know why

#### **Event Fetching Logic (getEventsForDate method)**
```
1. Check if token exists
2. Check if token is expired
3. If expired → Try to refresh
4. If refresh fails → Return empty array (silent failure)
5. Fetch events from Google Calendar
```

**Code Location**: `GoogleCalendarController::getEventsForDate()` (Line 192-278)

**Issue**: 
- When token refresh fails, it returns empty array `[]`
- No indication to user that events exist but can't be fetched
- User sees no events, thinks calendar is empty

---

## Why Refresh Token Might Be Missing

### Scenario 1: User Already Granted Permission
- If user previously authorized the app (even if disconnected)
- Google might not provide a new `refresh_token`
- Only provides `access_token` (which expires in 1 hour)

### Scenario 2: Consent Screen Not Forced
- Current code has `setPrompt('consent')` (Line 27) ✅
- But if user skips consent screen, refresh token might not be provided

### Scenario 3: Token Storage Issue
- Token might be stored incorrectly
- Refresh token might be lost during JSON encoding/decoding

### Scenario 4: Refresh Token Revoked
- User might have revoked access in Google Account settings
- Refresh token becomes invalid
- Can't get new access tokens

---

## Current Behavior Flow

### **When Token Expires (After ~1 Hour)**

1. **User tries to fetch events**:
   ```
   getEventsForDate() called
   → Token expired detected
   → refreshToken() called
   → refresh_token missing or invalid
   → Returns null
   → getEventsForDate() returns []
   → User sees no events (even though events exist in Google Calendar)
   ```

2. **User tries to create event**:
   ```
   createCalendarEvent() called
   → Token expired detected
   → refreshToken() called
   → refresh_token missing or invalid
   → Returns null
   → Event creation fails silently
   → Booking created but no calendar event
   ```

3. **User tries to delete event**:
   ```
   deleteCalendarEvent() called
   → Token expired detected
   → refreshToken() called
   → refresh_token missing or invalid
   → Returns null
   → Event deletion fails silently
   ```

**Result**: User must reconnect Google Calendar manually, even though they connected it before.

---

## Why Events Aren't Being Fetched

### **The Problem**:
1. Events ARE being created successfully (when access token is valid)
2. Events exist in Google Calendar
3. But when fetching events:
   - Token expires
   - Refresh fails (no refresh token)
   - Returns empty array
   - User sees no events

### **Evidence**:
- Events are created: ✅ (Logs show successful event creation)
- Events exist in Google Calendar: ✅ (User can see them in Google Calendar)
- Events are fetched: ❌ (Returns empty array due to token refresh failure)

---

## Solution Strategy

### **1. Improve Token Refresh Logic**

**Current Code** (Line 152-182):
```php
public function refreshToken($userDetail)
{
    $token = json_decode($userDetail->google_calendar_token, true);
    $client->refreshToken($token['refresh_token'] ?? null);
    $newToken = $client->getAccessToken();
    // ...
}
```

**Problems**:
- No check if refresh_token exists
- No error handling for missing refresh_token
- Silent failure

**Improved Code**:
```php
public function refreshToken($userDetail)
{
    // 1. Check if refresh_token exists
    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
        Log::warning('Refresh token missing - user needs to reconnect', [
            'user_id' => $userDetail->user_id
        ]);
        return null; // Indicate reconnection needed
    }
    
    // 2. Try to refresh with proper error handling
    try {
        $client->refreshToken($token['refresh_token']);
        $newToken = $client->getAccessToken();
        
        // 3. Verify new token has refresh_token (preserve it)
        if ($newToken && isset($token['refresh_token'])) {
            $newToken['refresh_token'] = $token['refresh_token'];
        }
        
        // 4. Update database
        $userDetail->update(['google_calendar_token' => $newToken]);
        return $newToken;
    } catch (\Exception $e) {
        Log::error('Token refresh failed', [
            'user_id' => $userDetail->user_id,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}
```

### **2. Ensure Refresh Token is Stored**

**Current Code** (Line 100-103):
```php
$userDetail->update([
    'google_calendar_token' => $accessToken,
    'google_calendar_id' => $primaryCalendarId,
]);
```

**Issue**: If `$accessToken` doesn't contain `refresh_token`, it's not stored.

**Improved Code**:
```php
// Verify refresh_token exists before storing
if (!isset($accessToken['refresh_token'])) {
    Log::warning('No refresh token provided by Google', [
        'user_id' => $user->id,
        'access_token_keys' => array_keys($accessToken)
    ]);
    // Still store token, but log warning
}

$userDetail->update([
    'google_calendar_token' => $accessToken,
    'google_calendar_id' => $primaryCalendarId,
]);
```

### **3. Add Reconnection Detection**

**New Method**: Check if reconnection is needed
```php
public static function needsReconnection($userDetail): bool
{
    if (!$userDetail || !$userDetail->google_calendar_token) {
        return true;
    }
    
    $token = is_array($userDetail->google_calendar_token) 
        ? $userDetail->google_calendar_token 
        : json_decode($userDetail->google_calendar_token, true);
    
    // Check if refresh_token exists
    if (!isset($token['refresh_token']) || empty($token['refresh_token'])) {
        return true;
    }
    
    return false;
}
```

### **4. Improve Error Handling in Event Fetching**

**Current Code** (Line 213-221):
```php
if ($client->isAccessTokenExpired()) {
    $calendarController = new self();
    $newToken = $calendarController->refreshToken($userDetail);
    if ($newToken) {
        $client->setAccessToken($newToken);
    } else {
        Log::warning('Failed to refresh token');
        return []; // Silent failure
    }
}
```

**Improved Code**:
```php
if ($client->isAccessTokenExpired()) {
    $calendarController = new self();
    $newToken = $calendarController->refreshToken($userDetail);
    if ($newToken) {
        $client->setAccessToken($newToken);
    } else {
        // Check if refresh token is missing
        $token = is_array($userDetail->google_calendar_token) 
            ? $userDetail->google_calendar_token 
            : json_decode($userDetail->google_calendar_token, true);
        
        if (!isset($token['refresh_token'])) {
            Log::error('Refresh token missing - user needs to reconnect Google Calendar', [
                'user_id' => $userDetail->user_id,
                'date' => $date
            ]);
            // Could throw exception or return specific error
        } else {
            Log::error('Token refresh failed - refresh token may be invalid', [
                'user_id' => $userDetail->user_id,
                'date' => $date
            ]);
        }
        return []; // Still return empty, but with better logging
    }
}
```

### **5. Force Refresh Token on Reconnection**

**Current Code** (Line 27):
```php
$client->setPrompt('consent'); // Force consent screen to get refresh token
```

**This is correct**, but we should also:
- Add `setAccessType('offline')` ✅ (Already present on Line 26)
- Verify refresh_token is received before completing connection

---

## Recommended Implementation Steps

### **Step 1: Fix Token Refresh Logic**
- Add refresh_token validation
- Preserve refresh_token when updating access_token
- Better error handling and logging

### **Step 2: Verify Refresh Token Storage**
- Log when refresh_token is missing during initial connection
- Warn user if refresh_token is not provided
- Store token even if refresh_token missing (for debugging)

### **Step 3: Add Reconnection Detection**
- Check if refresh_token exists before attempting operations
- Show user-friendly message if reconnection needed
- Provide easy reconnection button/link

### **Step 4: Improve Error Messages**
- Don't silently fail
- Log detailed errors
- Show user-friendly messages when reconnection needed

### **Step 5: Test Token Refresh**
- Test with valid refresh token
- Test with missing refresh token
- Test with expired/invalid refresh token
- Verify events are fetched correctly after refresh

---

## Expected Behavior After Fix

### **Scenario 1: Valid Refresh Token**
```
1. Token expires
2. Refresh token used to get new access token
3. New token stored (with refresh_token preserved)
4. Operations continue normally
5. User never needs to reconnect
```

### **Scenario 2: Missing Refresh Token**
```
1. Token expires
2. Refresh attempted but refresh_token missing
3. Error logged with clear message
4. User notified that reconnection is needed
5. User reconnects → Gets new refresh_token
6. Operations continue normally
```

### **Scenario 3: Invalid Refresh Token**
```
1. Token expires
2. Refresh attempted but refresh_token invalid (revoked)
3. Error logged
4. User notified that reconnection is needed
5. User reconnects → Gets new refresh_token
6. Operations continue normally
```

---

## Summary

**Main Issues**:
1. ❌ Refresh token might not be stored during initial connection
2. ❌ No validation that refresh_token exists before refresh attempt
3. ❌ Refresh token not preserved when updating access_token
4. ❌ Silent failures when refresh fails
5. ❌ No user notification when reconnection needed

**Impact**:
- Users must reconnect Google Calendar frequently
- Events are created but not fetched (due to token refresh failure)
- Poor user experience

**Solution**:
- Improve token refresh logic with proper validation
- Ensure refresh_token is stored and preserved
- Add reconnection detection and user notifications
- Better error handling and logging

---

## Next Steps

1. Review and implement improved token refresh logic
2. Add refresh_token validation
3. Add reconnection detection
4. Improve error messages
5. Test thoroughly with various token scenarios

