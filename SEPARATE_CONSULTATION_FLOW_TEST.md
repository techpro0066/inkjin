# Separate Consultation Flow - Complete Test Checklist

## Flow Overview
For artists with separate consultation and tattoo sessions, the booking flow is:
1. **Step 1: Select Consultation Date & Time**
2. **Step 2: Select Tattoo Session Date & Time** (with gap consideration if required)
3. **Step 3: Answer Questions** (if artist has questions)
4. **Step 4: Complete Payment**

---

## Test Scenarios

### Scenario 1: Separate Consultation with No Gap, No Questions

**Steps:**
1. ✅ User selects consultation date from calendar
2. ✅ User clicks "Next" button → Consultation slots load
3. ✅ User selects consultation time slot (e.g., 9:00-9:15)
4. ✅ "Next: Select Tattoo Session" button appears
5. ✅ User clicks "Next: Select Tattoo Session"
6. ✅ Calendar reappears with "Step 2: Select Tattoo Session Date"
7. ✅ Selected consultation info is displayed above calendar
8. ✅ User selects same date for tattoo session
9. ✅ User clicks "Next" → Tattoo session slots load
10. ✅ Tattoo session slots start from consultation end time (9:15) if no gap
11. ✅ User selects tattoo session slot
12. ✅ "Next: Payment" button appears (no questions)
13. ✅ User clicks "Next: Payment"
14. ✅ Payment section loads with loading spinner
15. ✅ Payment form displays correctly
16. ✅ Back button shows "Change Time" (no questions)

**Expected Results:**
- ✅ Consultation slot selected and stored in `selectedConsultationSlot`
- ✅ Tattoo session slots filtered correctly (no overlap with consultation)
- ✅ Next available slot starts at consultation end time (9:15)
- ✅ Payment section shows correctly
- ✅ Back button navigates to slots section

---

### Scenario 2: Separate Consultation with Gap Required, No Questions

**Steps:**
1. ✅ User selects consultation date
2. ✅ User selects consultation time slot
3. ✅ User clicks "Next: Select Tattoo Session"
4. ✅ Calendar shows minimum date based on gap
5. ✅ Dates before minimum date are disabled
6. ✅ User selects date on or after minimum date
7. ✅ User clicks "Next" → Tattoo session slots load
8. ✅ Gap information displayed in alert
9. ✅ User selects tattoo session slot
10. ✅ "Next: Payment" button appears
11. ✅ User clicks "Next: Payment"
12. ✅ Payment section loads correctly

**Expected Results:**
- ✅ Minimum date calculated correctly based on gap
- ✅ Calendar disables dates before minimum date
- ✅ Gap information displayed correctly
- ✅ Payment flow works correctly

---

### Scenario 3: Separate Consultation with Questions

**Steps:**
1. ✅ User selects consultation date and time
2. ✅ User selects tattoo session date and time
3. ✅ User selects tattoo session slot
4. ✅ "Next: Answer Questions" button appears (questions exist)
5. ✅ User clicks "Next: Answer Questions"
6. ✅ Questions section displays
7. ✅ User answers questions
8. ✅ User clicks "Next: Payment" or submits form
9. ✅ Payment section loads with questions data

**Expected Results:**
- ✅ Questions data stored in `window.bookingData.questions`
- ✅ Button text updates correctly based on questions
- ✅ Questions form displays correctly
- ✅ Questions answers submitted with payment

---

### Scenario 4: Navigation Back Through Flow

**Steps:**
1. ✅ From payment section, click "Back to Questions" (if questions exist)
2. ✅ From payment section, click "Change Time" (if no questions)
3. ✅ From questions section, click "Change Time"
4. ✅ From tattoo session slots, click "Change Date"
5. ✅ From consultation slots, click "Change Date"

**Expected Results:**
- ✅ Each back button navigates to correct section
- ✅ Selected dates/slots preserved when going back
- ✅ Calendar highlights preserved
- ✅ Next button visibility correct

---

## Code Fixes Applied

### Fix 1: Questions Data Storage
**Issue:** `displayTattooSessionSlots` wasn't storing questions in `window.bookingData`
**Fix:** Added code to store questions data from API response
```javascript
window.bookingData.questions = data.questions || [];
```

### Fix 2: Payment Step for Separate Consultation
**Issue:** Payment step wasn't showing payment section correctly for separate consultation flow
**Fix:** Added proper section visibility management and loading state

### Fix 3: Slot Generation with No Gap
**Issue:** Tattoo session slots weren't starting right after consultation end time
**Fix:** Modified slot generation to accept `minimumStartTime` parameter and start from consultation end time

---

## Key Functions to Verify

1. **`loadConsultationSlots(date)`** - Loads consultation slots for selected date
2. **`displayConsultationSlots(data, date)`** - Displays consultation slots
3. **`proceedToTattooSessionStep()`** - Transitions to tattoo session date selection
4. **`loadTattooSessionSlots(date)`** - Loads tattoo session slots with consultation filtering
5. **`displayTattooSessionSlots(data, date)`** - Displays tattoo session slots
6. **`proceedToQuestionsStep()`** - Transitions to questions (if questions exist)
7. **`proceedToPaymentStep()`** - Transitions to payment
8. **`showPaymentForm(paymentInfo)`** - Displays payment form

---

## Edge Cases to Test

1. ✅ Same date booking with no gap (slots start immediately after consultation)
2. ✅ Gap required (minimum date calculation)
3. ✅ No questions (skip directly to payment)
4. ✅ With questions (show questions form)
5. ✅ Back navigation preserves selections
6. ✅ Calendar date selection validation
7. ✅ Slot overlap filtering
8. ✅ Error handling for API failures

---

## Browser Console Checks

When testing, check browser console for:
- ✅ No JavaScript errors
- ✅ AJAX requests completing successfully
- ✅ `window.bookingData` object populated correctly
- ✅ `selectedConsultationSlot` and `selectedTattooSessionSlot` set correctly
- ✅ `bookingFlowStep` transitions correctly

---

## API Endpoints Used

1. **GET** `/api/consultation-slots/{tattoo_id}` - Get consultation slots
2. **GET** `/api/tattoo-session-slots/{tattoo_id}` - Get tattoo session slots
3. **POST** `/api/booking/submit/{tattoo_id}` - Submit booking (for payment info)
4. **POST** `/api/booking/separate/{tattoo_id}` - Submit separate consultation booking

---

## Status: ✅ READY FOR TESTING

All identified issues have been fixed. The flow should work correctly for:
- Separate consultation with/without gap
- With/without questions
- Proper navigation and back buttons
- Correct slot filtering and generation

