/*
# Insert CEMS Sample Data

Populates the database with sample admins, events, participants, and feedbacks.
Passwords are bcrypt-hashed (password: 'password').
*/

-- Sample admins
INSERT INTO admins (full_name, email, username, password, role) VALUES
('Admin User', 'admin@cvsu.edu.ph', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Event Organizer', 'organizer@cvsu.edu.ph', 'organizer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'organizer')
ON CONFLICT (username) DO NOTHING;

-- Sample events
INSERT INTO events (title, description, category, venue, event_date, start_time, end_time, capacity, status, created_by) VALUES
('University Foundation Day', 'A grand celebration of the university founding anniversary featuring cultural performances, parades, and community activities.', 'Cultural', 'University Main Grounds', '2026-01-15', '08:00:00', '17:00:00', 500, 'completed', (SELECT id FROM admins WHERE username = 'admin')),
('Tech Symposium 2026', 'Annual technology symposium featuring guest speakers, workshops, and coding competitions.', 'Academic', 'Engineering Building Hall', '2026-02-20', '09:00:00', '16:00:00', 300, 'completed', (SELECT id FROM admins WHERE username = 'admin')),
('Leadership Summit', 'A summit for student leaders and organization officers to develop leadership skills and strategic planning.', 'Leadership', 'Student Center', '2026-03-10', '08:00:00', '15:00:00', 200, 'completed', (SELECT id FROM admins WHERE username = 'admin')),
('Career Fair 2026', 'Connect with potential employers and explore career opportunities across various industries.', 'Career', 'University Gymnasium', '2026-06-20', '09:00:00', '16:00:00', 400, 'upcoming', (SELECT id FROM admins WHERE username = 'admin')),
('Environmental Awareness Week', 'Series of events promoting environmental sustainability and green initiatives.', 'Environmental', 'Science Building', '2026-07-05', '08:00:00', '17:00:00', 250, 'upcoming', (SELECT id FROM admins WHERE username = 'admin')),
('Sports Festival', 'Annual inter-college sports competition featuring basketball, volleyball, track and field, and more.', 'Sports', 'University Sports Complex', '2026-07-15', '07:00:00', '18:00:00', 600, 'upcoming', (SELECT id FROM admins WHERE username = 'admin')),
('Graduation Ceremony 2026', 'Annual commencement exercises for graduating students.', 'Ceremony', 'University Auditorium', '2026-08-10', '08:00:00', '14:00:00', 800, 'upcoming', (SELECT id FROM admins WHERE username = 'admin')),
('Research Conference', 'Showcase of student and faculty research across various disciplines.', 'Academic', 'Research Hall', '2026-08-25', '09:00:00', '17:00:00', 350, 'upcoming', (SELECT id FROM admins WHERE username = 'admin'));

-- Sample participants
INSERT INTO participants (event_id, student_number, full_name, course, year_level, email, contact_number, reference_number, attendance_status)
VALUES
((SELECT id FROM events WHERE title = 'Tech Symposium 2026' LIMIT 1), '2021-00011', 'Miguel Santos', 'BS Information Technology', '3rd Year', 'miguel.santos@cvsu.edu.ph', '09171234501', 'CEMS-AB1234', 'registered'),
((SELECT id FROM events WHERE title = 'Leadership Summit' LIMIT 1), '2021-00012', 'Isabel Cruz', 'BS Public Administration', '2nd Year', 'isabel.cruz@cvsu.edu.ph', '09171234502', 'CEMS-CD5678', 'registered'),
((SELECT id FROM events WHERE title = 'Career Fair 2026' LIMIT 1), '2021-00013', 'Joshua Lim', 'BS Business Administration', '4th Year', 'joshua.lim@cvsu.edu.ph', '09171234503', 'CEMS-EF9012', 'registered'),
((SELECT id FROM events WHERE title = 'Environmental Awareness Week' LIMIT 1), '2021-00014', 'Ana Perez', 'BS Environmental Science', '3rd Year', 'ana.perez@cvsu.edu.ph', '09171234504', 'CEMS-GH3456', 'registered'),
((SELECT id FROM events WHERE title = 'Sports Festival' LIMIT 1), '2021-00015', 'Mark dela Cruz', 'BS Physical Education', '2nd Year', 'mark.delacruz@cvsu.edu.ph', '09171234505', 'CEMS-IJ7890', 'registered');

-- Sample feedback
INSERT INTO feedbacks (participant_id, event_id, overall_rating, organization_rating, speaker_rating, venue_rating, comments)
VALUES
((SELECT id FROM participants WHERE student_number = '2021-00011' LIMIT 1), (SELECT id FROM events WHERE title = 'Tech Symposium 2026' LIMIT 1), 5, 5, 5, 4, 'Excellent speakers and well-organized workshops.'),
((SELECT id FROM participants WHERE student_number = '2021-00012' LIMIT 1), (SELECT id FROM events WHERE title = 'Leadership Summit' LIMIT 1), 4, 4, 4, 4, 'Great networking and leadership discussions.'),
((SELECT id FROM participants WHERE student_number = '2021-00013' LIMIT 1), (SELECT id FROM events WHERE title = 'Career Fair 2026' LIMIT 1), 5, 5, 5, 5, 'Very helpful employers and smooth registration process.'),
((SELECT id FROM participants WHERE student_number = '2021-00014' LIMIT 1), (SELECT id FROM events WHERE title = 'Environmental Awareness Week' LIMIT 1), 4, 4, 5, 4, 'Informative sessions with inspiring speakers.'),
((SELECT id FROM participants WHERE student_number = '2021-00015' LIMIT 1), (SELECT id FROM events WHERE title = 'Sports Festival' LIMIT 1), 5, 5, 5, 5, 'Fun competitions and excellent venue setup.');
