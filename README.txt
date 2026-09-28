SLIMM LABORATORY INC. - LABCONNECT WEB FRONTEND

This ZIP contains the styled frontend foundation for the Admin/Laboratory Staff and Doctor web console.

Design:
- White surfaces on light gray background
- Teal accent
- Inter font
- Hairline borders
- No gradients, photos, or background images
- Circular logo placeholders on login, sidebar, and settings
- Responsive layout

Roles:
Administrator: Dashboard, Patients, Doctors, Laboratory Tests, Appointments, Schedule, Results, Reports, Users, Settings.
Laboratory Staff: Dashboard, Patients, Doctors, Laboratory Tests, Appointments, Schedule, Results.
Doctor: Dashboard, My Appointments, My Schedule.

Important:
Dashboard counts, chart values, table rows, and report numbers are placeholder frontend data.
They must be replaced with PostgreSQL/API data before production use.
Role enforcement must remain on the server/API, not only in the UI.

XAMPP:
Copy LabConnect-Web to C:\xampp\htdocs\
Start Apache.
Open http://localhost/LabConnect-Web/

PostgreSQL:
Keep PostgreSQL/pgAdmin as the database system.
Configure config/database.php with your real PostgreSQL credentials.

Next development step:
Connect every page to the LabConnect PostgreSQL database and implement the REST API, CRUD, authentication, uploads, notifications, reports, and patient Android endpoints.
