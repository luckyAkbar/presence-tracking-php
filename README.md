# SIMPLE PRESENCE TRACKING
A lightweight attendance tracker for organizations with roles, invitations, and real-time views.

This app provides a simple way for organizations to track attendance for meetings and events. It also includes built-in capabilities to verify that participants are actually attending, with minimal setup.

## Product Design

This section outlines product-related requirements, such as user roles, use cases, and feature requests.

### User Identification / User Roles

1. Organization Admin

Users in this group are Organization Members who have been promoted to Organization Admin. The first Organization Admin is the user who registered the Organization.

2. Organization Member

Users in this category are Registered Users who have been invited to an Organization by an Organization Admin. 

3. Guests

A Guest is anyone who has not signed up for the app.

4. Registered User

A Registered User is any Guest who has signed up. They can be invited by an Organization Admin to become an Organization Member.

### Glossary

- **Statuses**:
  - **Present**: Checked in for the event within the allowed window.
  - **Absent**: Did not check in and provided no acceptable reason.
  - **Late**: Checked in after the configured start time but within the allowed window.
  - **Excused**: Did not attend but provided an accepted reason.
- **Key terms**:
  - **Attendance list**: A configured list for a specific event/occasion that accepts check-ins and defines visibility and timing rules.
  - **Invitation**: A request sent to a Registered User to join an Organization as a Member.
  - **Organization**: A logical group that owns attendance lists, Members, and admin settings.

### User Stories

This section contains initial user stories for the early development phase. In the future, another task management system may become the source of truth; references will be noted here.

#### Template

| Story Code    | User Roles     | Description      |
| ------------- | ------------- | ------------- |
| US1 | template | template |

#### Auth

| Story Code    | User Roles     | Description      |
| ------------- | ------------- | ------------- |
| US2 | Guest | As a Guest, I want to sign up so I can become a Registered User. |
| US3 | Registered User | As a Registered User, I want to sign in to obtain a session and access my authorized resources. |
| US4 | Registered User | As a Registered User, I want to refresh my session without re-authenticating when eligible. |
| US6 | Registered User | As a Registered User, I want to sign out so my current session cannot be reused. |

#### Organization & Membership

| Story Code    | User Roles     | Description      |
| ------------- | ------------- | ------------- |
| US5 | Registered User | As a Registered User, I want to register an Organization and become its Organization Admin. |
| US7 | Organization Admin | As an Organization Admin, I want to invite Registered Users to join my Organization as Members and track invitation status (pending/accepted/rejected). |
| US8 | Organization Admin | As an Organization Admin, I want to cancel pending membership invitations I sent so invitees can no longer view or accept them. |
| US9 | Organization Admin | As an Organization Admin, I want to remove Members from my Organization to revoke their access. |
| US10 | Registered User | As a Registered User, I want to view invitations to join Organizations. |
| US11 | Registered User | As a Registered User, I want to accept or reject Organization membership invitations and notify the inviter. |
| US12 | Registered User | As a Registered User, I want to leave an Organization I’m a member of and notify the Organization Admin. |

#### Attendance Lists

| Story Code    | User Roles     | Description      |
| ------------- | ------------- | ------------- |
| US13 | Organization Admin | As an Organization Admin, I want to create an attendance list for an event with options to target specific Members, allow Guests to submit, control result visibility, set open/close times, and require proof of attendance (e.g., photo). |
| US14 | Organization Admin | As an Organization Admin, I want to update attendance list settings (e.g., title, schedule, expected attendees) in a backward-compatible way. |
| US15 | Organization Member | As an Organization Member, I want to submit my attendance on lists assigned to me. |
| US16 | Organization Member | As an Organization Member, I want to view attendance lists live or final results within my Organization. |
| US17 | Organization Member | As an Organization Member, I want to record my availability (e.g., late or excused absence) and be marked accordingly. |
| US18 | Organization Admin | As an Organization Admin, I want to update attendee statuses (e.g., from absent to present) to correct records. |
| US19 | Organization Member | As an Organization Member, I want to request changes to my attendance record from my Organization’s Admin (e.g., marked late but eventually arrived). |
| US20 | Organization Admin | As an Organization Admin, I want to view all attendance record change requests from Organization Members for a given attendance list. |
| US21 | Guest | As a Guest, I want to submit attendance on publicly accessible lists. |
| US22 | Guest | As a Guest, I want to view results of publicly accessible attendance lists. |
| US23 | Guest or Registered User | As a Guest or Registered User, I want to view an attendance list in real time to see records update live. |
| USxx | Organization Member | As an organization member, I want to get list off all oncoming, in progress, finished attendance list where I have to / able to fill. |
| USxx | Organization Admin | As an organization admin, I want to cancel an attendance list that have not been started yet so that presence list can't be filled and won't be used by the expected attendees. |

#### Reporting & Export

| Story Code    | User Roles     | Description      |
| ------------- | ------------- | ------------- |
| US24 | Registered User | As a Registered User, I want to view my attendance history to see at which events I was on time, late, excused, or absent. |
| US25 | Organization Admin | As an Organization Admin, I want to view summaries of attendance across events, including counts by status. |
| US26 | Organization Admin | As an Organization Admin, I want to export attendance lists and summaries to a CSV file. |


## Running locally

Requirements: Docker and Docker Compose plugin.

Commands:

```bash
docker compose up -d --build
curl -s http://127.0.0.1:8080/api/ping | jq .
```

Expected output:

```json
{ "service": "presence-tracking", "status": "ok", "db": "ok", "time": "..." }
```

## Project layout

- `public/`: web root. Only files here are publicly accessible. Entry point: `public/index.php`.
- `src/`: application code. Namespaced under `App\...`.
  - `Config/Config.php`: simple config holder.
  - `Support/Db.php`: PDO connection and ping.
  - `Http/Router.php`: tiny router.
- `docker/`: container configs
  - `nginx/`: reverse proxy serving `public/` and forwarding PHP to `php:9000`.
  - `php/`: PHP-FPM 8.3 with secure defaults and `pdo_mysql`.
  - `mysql/`: MySQL 8 with init script.
- `compose.yaml`: defines `proxy` (Nginx), `php` (FPM), and `mysql`.

## Security notes

- Only `public/` is exposed by the proxy. `src/` is not web-accessible.
- Nginx blocks dotfiles and common sensitive extensions.
- PHP config uses `open_basedir` to restrict filesystem access to `public/`, `src/`, and `/tmp`.

## Development Tools

### Phinx Migrator

This project use [php phinx](https://github.com/cakephp/phinx) to handle migrations. You can run `phix` through docker compose like below
<pre>docker compose exec --user $(id -u):$(id -g) php php -d open_basedir= vendor/bin/phinx migrate -e development --dry-run</pre>


### Composer

Composer are already installed within Docker image ready to use for development. One way to run composer to install dependency is like below

<pre>docker compose exec --user $(id -u):$(id -g) php php -d open_basedir= /usr/bin/composer require robmorgan/phinx</pre>


### Mysql Database Console

To connect to the application main database server, you can use `docker exec` to interactively connect to the database, for example:

`docker compose exec -it mysql mysql -u username db_name -p`