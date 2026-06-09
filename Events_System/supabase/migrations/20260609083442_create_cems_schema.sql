/*
# Campus Event Management System (CEMS) Schema

Creates the complete database schema for the Cavite State University Campus Event Management System.

1. New Tables
- `admins` - System administrators with roles (admin, organizer)
- `events` - Campus events with details, dates, capacity, and status
- `participants` - Student registrations for events with attendance tracking
- `feedbacks` - Event feedback ratings and comments

2. Security
- Enable RLS on all tables
- Single-tenant policies: allow anon and authenticated access since data is shared/public
- Admin actions are protected by PHP admin panel, not RLS

3. Notes
- No user_id columns since this is a single-tenant app (no per-user isolation)
- All data is public/shared across the system
- Admin authentication is handled via PHP session, not Supabase auth
*/

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    full_name text NOT NULL,
    email text UNIQUE NOT NULL,
    username text UNIQUE NOT NULL,
    password text NOT NULL,
    role text NOT NULL DEFAULT 'organizer',
    created_at timestamptz DEFAULT now()
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    title text NOT NULL,
    description text NOT NULL,
    category text NOT NULL,
    venue text NOT NULL,
    event_date date NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    capacity integer NOT NULL DEFAULT 100,
    status text NOT NULL DEFAULT 'upcoming',
    image text,
    created_by uuid REFERENCES admins(id) ON DELETE SET NULL,
    created_at timestamptz DEFAULT now()
);

-- Participants table
CREATE TABLE IF NOT EXISTS participants (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    event_id uuid NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    student_number text NOT NULL,
    full_name text NOT NULL,
    course text NOT NULL,
    year_level text NOT NULL,
    email text NOT NULL,
    contact_number text NOT NULL,
    reference_number text NOT NULL,
    attendance_status text NOT NULL DEFAULT 'registered',
    registration_date timestamptz DEFAULT now()
);

-- Feedbacks table
CREATE TABLE IF NOT EXISTS feedbacks (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    participant_id uuid NOT NULL REFERENCES participants(id) ON DELETE CASCADE,
    event_id uuid NOT NULL REFERENCES events(id) ON DELETE CASCADE,
    overall_rating integer NOT NULL DEFAULT 5,
    organization_rating integer NOT NULL DEFAULT 5,
    speaker_rating integer NOT NULL DEFAULT 5,
    venue_rating integer NOT NULL DEFAULT 5,
    comments text,
    submitted_at timestamptz DEFAULT now()
);

-- RLS on all tables
ALTER TABLE admins ENABLE ROW LEVEL SECURITY;
ALTER TABLE events ENABLE ROW LEVEL SECURITY;
ALTER TABLE participants ENABLE ROW LEVEL SECURITY;
ALTER TABLE feedbacks ENABLE ROW LEVEL SECURITY;

-- Admin policies (single-tenant, public access)
DROP POLICY IF EXISTS "anon_select_admins" ON admins;
CREATE POLICY "anon_select_admins" ON admins FOR SELECT TO anon, authenticated USING (true);
DROP POLICY IF EXISTS "anon_insert_admins" ON admins;
CREATE POLICY "anon_insert_admins" ON admins FOR INSERT TO anon, authenticated WITH CHECK (true);
DROP POLICY IF EXISTS "anon_update_admins" ON admins;
CREATE POLICY "anon_update_admins" ON admins FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);
DROP POLICY IF EXISTS "anon_delete_admins" ON admins;
CREATE POLICY "anon_delete_admins" ON admins FOR DELETE TO anon, authenticated USING (true);

-- Events policies (single-tenant, public access)
DROP POLICY IF EXISTS "anon_select_events" ON events;
CREATE POLICY "anon_select_events" ON events FOR SELECT TO anon, authenticated USING (true);
DROP POLICY IF EXISTS "anon_insert_events" ON events;
CREATE POLICY "anon_insert_events" ON events FOR INSERT TO anon, authenticated WITH CHECK (true);
DROP POLICY IF EXISTS "anon_update_events" ON events;
CREATE POLICY "anon_update_events" ON events FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);
DROP POLICY IF EXISTS "anon_delete_events" ON events;
CREATE POLICY "anon_delete_events" ON events FOR DELETE TO anon, authenticated USING (true);

-- Participants policies (single-tenant, public access)
DROP POLICY IF EXISTS "anon_select_participants" ON participants;
CREATE POLICY "anon_select_participants" ON participants FOR SELECT TO anon, authenticated USING (true);
DROP POLICY IF EXISTS "anon_insert_participants" ON participants;
CREATE POLICY "anon_insert_participants" ON participants FOR INSERT TO anon, authenticated WITH CHECK (true);
DROP POLICY IF EXISTS "anon_update_participants" ON participants;
CREATE POLICY "anon_update_participants" ON participants FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);
DROP POLICY IF EXISTS "anon_delete_participants" ON participants;
CREATE POLICY "anon_delete_participants" ON participants FOR DELETE TO anon, authenticated USING (true);

-- Feedbacks policies (single-tenant, public access)
DROP POLICY IF EXISTS "anon_select_feedbacks" ON feedbacks;
CREATE POLICY "anon_select_feedbacks" ON feedbacks FOR SELECT TO anon, authenticated USING (true);
DROP POLICY IF EXISTS "anon_insert_feedbacks" ON feedbacks;
CREATE POLICY "anon_insert_feedbacks" ON feedbacks FOR INSERT TO anon, authenticated WITH CHECK (true);
DROP POLICY IF EXISTS "anon_update_feedbacks" ON feedbacks;
CREATE POLICY "anon_update_feedbacks" ON feedbacks FOR UPDATE TO anon, authenticated USING (true) WITH CHECK (true);
DROP POLICY IF EXISTS "anon_delete_feedbacks" ON feedbacks;
CREATE POLICY "anon_delete_feedbacks" ON feedbacks FOR DELETE TO anon, authenticated USING (true);
