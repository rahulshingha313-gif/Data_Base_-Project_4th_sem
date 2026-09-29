# TutorHub — End-to-End Testing Checklist

Use this script to manually verify the full workflow after installation. Check phpMyAdmin after each step to confirm the corresponding table was actually updated.

## 1. Student Registration & Profile
- [ ] Register a new account as **Student** at `/auth/register.php` → row appears in `users` and `student_profiles`
- [ ] Log in at `/auth/login.php`
- [ ] Go to **My Profile**, fill in class/institution/guardian info → `student_profiles` row updates

## 2. Post a Tuition Requirement
- [ ] Go to **Post a Tuition**, submit a new requirement → row appears in `tuition_posts`
- [ ] View it in **My Tuition Posts**, then **Edit** it → row updates
- [ ] Log out

## 3. Tutor Registration & Profile
- [ ] Register a new account as **Tutor**
- [ ] Complete **My Profile** (education, experience, subjects) → `tutor_profiles` and `tutor_subjects` update
- [ ] Go to **Set Availability**, add a weekly slot → row in `tutor_availability`

## 4. Browse & Apply
- [ ] Go to **Browse Tuition Posts**, use filters (subject/location/budget) to find the student's post
- [ ] Open the post and **Apply** with a message and proposed salary → row in `applications`
- [ ] Confirm a notification was created for the student → row in `notifications`

## 5. Accept & Start Conversation
- [ ] Log back in as the student
- [ ] Go to **Applications Received**, **Accept** the tutor's application → `applications.status = accepted`, `tuition_posts.status = in_progress`, new row in `conversations`
- [ ] Go to **Messages**, open the conversation, send a message → row in `messages`

## 6. Scheduling
- [ ] Go to **Schedule**, create a new class (date/time/topic) → row in `tuition_schedule`
- [ ] Log in as the tutor, confirm the class appears on their schedule too

## 7. Attendance
- [ ] As the tutor, go to the schedule and click **Attendance** for a class, mark Present/Absent/Late with notes → row in `attendance`
- [ ] As the student, view **Attendance** (read-only) and confirm the record shows

## 8. Payments
- [ ] As the tutor, go to **Payments → New Payment Record**, create a payment for a month → row in `payments` (status `pending`)
- [ ] As the student, go to **Payments**, click **Mark as Paid**, choose a method and reference → `payments.status = paid`
- [ ] As the tutor, confirm the payment shows as paid; try **Mark Overdue** on a different pending record

## 9. Completion & Review
- [ ] Mark the scheduled class **Completed** from the Schedule page → `tuition_schedule.status = completed`
- [ ] As the student, go to **Give Review**, submit a rating and text → row in `reviews`, and `tutor_profiles.avg_rating` / `total_reviews` recalculate
- [ ] Try submitting a second review for the same tuition → should be blocked (duplicate prevention)

## 10. Reports
- [ ] As either user, use **Report a Problem** on a post or profile → row in `reports`

## 11. Admin
- [ ] Log in as **Admin**
- [ ] **Dashboard**: confirm all statistic cards show non-zero, correct counts
- [ ] **Manage Users**: suspend then reactivate a test user
- [ ] **Verify Tutors**: verify the test tutor profile
- [ ] **Tuition/Tutor Posts**: close a post
- [ ] **Reviews**: view and (optionally) delete a review, confirm rating recalculates
- [ ] **Payments**: filter by status and confirm totals match what was created above
- [ ] **Reports**: change a report's status and add an admin response → reporter receives a notification
- [ ] **Manage Subjects**: add a new subject, then try deleting a subject in use (should fail gracefully) and one not in use (should succeed)
- [ ] **System Activity**: confirm the verify/suspend/etc. actions above appear in the log

## 12. Security Spot-Checks
- [ ] While logged in as Student A, try to open `/student/edit_post.php?id=<Student B's post id>` directly → should be redirected/blocked
- [ ] Try submitting a form without the CSRF token (e.g. by editing the URL) → should be rejected
- [ ] Try accessing any `/admin/*.php` page while logged in as a non-admin → redirected away

If every checkbox above passes and the corresponding table changes are visible in phpMyAdmin, the system is functioning as a fully connected, relational DBMS application.
