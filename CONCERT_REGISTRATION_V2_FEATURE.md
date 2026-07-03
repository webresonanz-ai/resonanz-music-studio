# Concert Registration v2 - Registration Control & Capacity Management

## Overview
Added per-concert registration control (open/closed) and dynamic capacity management. Now admins can:
- Toggle registration on/off for each concert independently
- Set a specific audience capacity per concert
- Track registrations per concert via `schedule_id` foreign key

## Changes Made

### 1. Database Migration
**File:** `backend/database_migration_concert_registration_v2.sql`

Added to `schedules` table:
- `is_open_register` (TINYINT) - Boolean flag to control if registration is open
- `audience_capacity` (INT, nullable) - Max registrations for this concert (NULL = unlimited)

Added to `concert_audiences` table:
- `schedule_id` (INT, nullable, FK to schedules) - Links registration to specific concert schedule
- Index on `schedule_id` for fast per-concert count queries

**To apply the migration:**
```bash
mysql -u your_user -p your_database < backend/database_migration_concert_registration_v2.sql
```

### 2. Backend Model Updates

**`ConcertAudience.php`**
- Added `countBySchedule(int $scheduleId)` method to count registrations for a specific concert

**`ScheduleController.php`**
- Added handling for `is_open_register` and `audience_capacity` in both `store()` and `update()`
- Checkboxes/switches send boolean values, properly converted to 0/1 for DB

**`ConcertAudienceController.php`**
- Imports `Schedule` model
- New registration flow:
  1. Accept `schedule_id` in the payload
  2. If provided, load the schedule and check `is_open_register`
  3. If closed → reject with 409 "Registration for this concert is currently closed"
  4. Check `audience_capacity` for that specific concert using `countBySchedule(schedule_id)`
  5. If at capacity → reject with 409 "Maximum capacity reached"
  6. Save `schedule_id` in the `concert_audiences` record
  7. Fallback: if no `schedule_id` sent, use legacy global cap of 600

### 3. Frontend Schedule Form

**`ScheduleFormModal.vue`**
- Added three new form fields visible **only when type = "concert"**:
  1. **Banner URL** (text input)
  2. **Audience Capacity** (number input, optional - leave empty for unlimited)
  3. **Open for Registration** (toggle switch)
- Form data includes `is_open_register` (boolean) and `audience_capacity` (number or null)

### 4. Frontend TRMS Home

**`TRMSHome.vue`**
- Slideshow now checks `is_open_register` on each concert
- Shows **"Register Now"** button only when `is_open_register === true` or `is_open_register === 1`
- Shows **"Registration Closed"** badge when registration is closed

### 5. Frontend Concert Registration

**`ConcertRegistration.vue`**
- Checks `selectedConcert.is_open_register` before showing form
- If closed → displays a full "Registration is Closed" notice with back-to-home link
- Submit button disabled if `!is_open_register`
- Passes `schedule_id: selectedConcert.id` in registration payload so backend can:
  - Verify registration is open
  - Check per-concert capacity
  - Link the registration to that specific schedule

## How It Works

### Admin Workflow
1. Go to **Schedules** page
2. Add or edit a concert schedule (type = "concert")
3. Fill in:
   - Title, date, time, description
   - **Banner URL** (optional)
   - **Audience Capacity** (e.g., 500) — leave blank for unlimited
   - Toggle **"Open for Registration"** ON to activate registration

4. Save the concert

### Public User Experience
1. Visit **TRMS Home** page
2. Slideshow shows all upcoming concerts
   - If registration is open → **"Register Now"** button appears
   - If registration is closed → **"Registration Closed"** badge appears
3. Click **"Register Now"** → navigates to registration form
4. Fill the form and submit
5. Backend checks:
   - Is registration still open for this concert?
   - Is there still capacity available?
6. If both pass → registration saved, email + PDF ticket sent
7. If closed or at capacity → error message shown

### Registration Capacity Logic
- Admin sets `audience_capacity = 500`
- Backend counts registrations where `schedule_id = <concert_id>`
- When count reaches 500 → further registrations rejected with 409 error
- If `audience_capacity` is NULL → unlimited registrations allowed

## Benefits
- **Per-concert control** - Each concert can have different capacity and open/closed state
- **Prevents overbooking** - Capacity enforced per concert, not globally
- **Admin flexibility** - Open/close registration with a single toggle, no code changes
- **Better data** - `schedule_id` foreign key enables per-concert reports and analytics
- **User clarity** - Clear messaging when registration is closed
