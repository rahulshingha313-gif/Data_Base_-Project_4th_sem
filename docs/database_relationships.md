# TutorHub — Database Relationship Documentation

## Entity Overview

| Table | Purpose |
|---|---|
| users | Core account for every person (student, tutor, both, or admin) |
| student_profiles | Extended profile data for the student side of an account |
| tutor_profiles | Extended profile data for the tutor side of an account |
| subjects | Master list of subjects (Math, Physics, English, ...) |
| tutor_subjects | Junction table: which subjects a tutor teaches |
| tuition_posts | A requirement posted by a student |
| tutor_posts | An availability advertisement posted by a tutor |
| applications | A tutor's application to a tuition post |
| conversations | A messaging thread tied to an accepted application |
| messages | Individual chat messages within a conversation |
| favorites | Saved tuition posts or tutor profiles |
| notifications | System-generated alerts for a user |
| reviews | Post-completion rating/review from student to tutor |
| tutor_availability | A tutor's recurring weekly free slots |
| tuition_schedule | A specific scheduled class tied to an accepted application |
| attendance | Present/Absent/Late record for one scheduled class |
| payments | Monthly fee tracking between a student and tutor |
| reports | Complaints/reports raised by any user |
| admin_actions | Audit log of every moderation action an admin takes |

## Relationship Map

```
users ──1:1── student_profiles
users ──1:1── tutor_profiles
users ──1:N── notifications
users ──1:N── reports (as reporter)
users ──1:N── admin_actions (as admin)

subjects ──1:N── tuition_posts
subjects ──1:N── tutor_posts
subjects ──M:N── tutor_profiles   (via tutor_subjects)

student_profiles ──1:N── tuition_posts
tutor_profiles   ──1:N── tutor_posts
tutor_profiles   ──1:N── tutor_availability

tuition_posts ──1:N── applications
tutor_profiles ──1:N── applications

applications ──1:1── conversations (optional, created on acceptance)
conversations ──1:N── messages

tuition_posts ──1:N── favorites
tutor_profiles ──1:N── favorites

applications ──1:N── tuition_schedule
tuition_schedule ──1:1── attendance

tuition_posts ──1:N── payments
tuition_posts ──1:N── reviews
```

## Cascade Rules

- Deleting a `user` cascades to their `student_profiles` / `tutor_profiles` / `notifications` / `applications` / etc. (ON DELETE CASCADE), since an account and everything it directly owns should be removed together.
- Deleting a `tuition_post` cascades to its `applications`, `favorites`, `payments`, and `reviews` referencing it.
- Deleting a `report`'s reported user sets `reported_user_id` to `NULL` instead of deleting the report (ON DELETE SET NULL), preserving the audit trail.
- Deleting a `conversation`'s linked `application` sets `application_id` to `NULL` rather than deleting the conversation/messages.

## Why this satisfies the DBMS project requirement

Every button/action in the UI (register, post, apply, accept, reject, message, schedule, attend, pay, review, report) maps directly to one or more of the INSERT/UPDATE/DELETE statements shown in `sql/database.sql`'s corresponding PHP file, so all relationships are exercised and visible when inspecting the database in phpMyAdmin after using the site.
