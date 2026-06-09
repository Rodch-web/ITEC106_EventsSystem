# CVSU Campus Event Management System (CEMS)

A simple PHP event management system for Cavite State University.  
Uses **JSON files** for storage — **no database required**.

---

## How to Run

1. Open terminal/command prompt in the project folder.
2. Run this command (use your XAMPP PHP path):

```bash
C:\xampp\php\php.exe -S localhost:8000
```

3. Open your browser:
   - **Main Page:** http://localhost:8000
   - **Admin Login:** http://localhost:8000/admin/login.php

> Keep the terminal open while using the system.

**Alternative (XAMPP Apache):**
- Copy the project to `C:\xampp\htdocs\Events_System`
- Start Apache in XAMPP (MySQL is not needed)
- Open: http://localhost/Events_System/

---

## Login Credentials

### Admin Account

| Username   | Password   | Role      |
|------------|------------|-----------|
| `admin`    | `password` | Admin     |
| `organizer`| `password` | Organizer |

Use these at: **Admin Login** → http://localhost:8000/admin/login.php

### Student / Visitor

No login required. Anyone can:
- Browse events
- Register for events
- Submit feedback using their registration reference number

---

## Basic Usage

### Students
1. Open the main page → click **Events**
2. Choose an event → **View Details** → **Register Now**
3. Fill out the form and save your **reference number**
4. Submit feedback after the event using that reference number

### Admins
1. Login with the credentials above
2. **Dashboard** — view statistics
3. **Events** — create, edit, or delete events
4. **Participants** — view registrations and mark attendance
5. **Feedbacks** — view submitted feedback
6. **Reports** — generate and export reports
7. **Main Site** — go back to the public homepage

---

## Requirements

- PHP 7.4 or higher
- XAMPP (or any PHP server)
- No MySQL / database setup needed

---

## Note

All data is saved in the `data/` folder as JSON files.  
Make sure the `data/` folder can be read and written by PHP.
