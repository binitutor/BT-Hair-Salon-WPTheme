# BT Hair Salon — WordPress Theme

A custom WordPress theme for a hair salon business. Includes a public appointment booking page, an admin dashboard for managing services, availability slots, and appointment requests, and a secure sign-in page. All data flows through a built-in REST API.

---

## Theme Details

| Field | Value |
|---|---|
| **Theme Name** | BT Hair Salon |
| **Version** | 1.0.0 |
| **Author** | Biniam Alemayehu |
| **Author URI** | https://binitutor.com |
| **Requires WordPress** | 6.4+ |
| **Tested up to** | 6.8 |
| **Requires PHP** | 7.4+ |
| **Text Domain** | `bt-hair-salon` |

---

## Features

- **Public Booking Page** — Visitors can select a service, pick an available slot, and submit an appointment request.
- **Admin Dashboard** (`/salon-dashboard/`) — Manage services, availability slots, and appointment requests with accept/reject actions.
- **Custom Sign-In Page** (`/sign-in/`) — Branded login page that redirects admins directly to the dashboard after authentication.
- **404 Page** — Styled page-not-found template consistent with the rest of the theme.
- **REST API** — All data operations are handled through a versioned REST namespace (`bt-hair/v1`).
- **n8n Webhook Integration** — Optionally trigger an n8n automation workflow when a new appointment is submitted.
- **Appointment Status Chart** — Visual breakdown of pending, accepted, and rejected appointments using Chart.js.
- **Page Auto-Creation** — Required pages (`salon-dashboard`, `sign-in`) are created automatically on theme activation with the correct templates assigned.
- **Non-Pretty-Permalink Compatible** — REST calls use query-style URLs (`index.php?rest_route=...`) for compatibility with environments that don't have Apache rewrites enabled.

---

## Frontend Stack

| Library | Version | Purpose |
|---|---|---|
| Bootstrap | 5.3.3 | Layout and UI components |
| Font Awesome | 6.5.2 | Icons |
| Playfair Display | — | Heading and brand font |
| Manrope | — | Body font |
| SweetAlert2 | 11 | Modal dialogs and alerts |
| Chart.js | 4.4.3 | Appointment status chart (dashboard only) |

---

## File Structure

```
bt-hair-salon/
├── style.css                  # Theme registration header
├── functions.php              # Core logic: DB setup, REST API, auth, asset enqueue
├── header.php                 # Shared document header (doctype → <body>)
├── footer.php                 # Shared footer (</body> → </html>)
├── index.php                  # Public homepage with booking form
├── page-salon-dashboard.php   # Admin dashboard template
├── page-sign-in.php           # Admin sign-in template
├── 404.php                    # Page not found template
├── screenshot.png             # Theme preview image
└── assets/
    ├── css/
    │   └── main.css           # All theme styles
    └── js/
        ├── public.js          # Public page: form submission, REST calls, animations
        └── dashboard.js       # Dashboard: CRUD operations, chart rendering
```

---

## Database Tables

Created automatically on theme activation via `dbDelta()`.

### `wp_bt_hair_services`
| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `service_name` | VARCHAR(190) | Name of service |
| `price` | DECIMAL(10,2) | Service price |
| `created_at` | DATETIME | Row creation timestamp |
| `updated_at` | DATETIME | Row update timestamp |

### `wp_bt_hair_slots`
| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `slot_start` | DATETIME | Slot start datetime |
| `slot_end` | DATETIME | Slot end datetime |
| `label` | VARCHAR(191) | Human-readable label |
| `is_active` | TINYINT(1) | 1 = available, 0 = booked |
| `created_at` | DATETIME | Row creation timestamp |

### `wp_bt_hair_appointments`
| Column | Type | Description |
|---|---|---|
| `id` | BIGINT UNSIGNED | Primary key |
| `full_name` | VARCHAR(190) | Client full name |
| `email` | VARCHAR(190) | Client email |
| `phone` | VARCHAR(60) | Client phone number |
| `service_id` | BIGINT UNSIGNED | FK → services |
| `slot_id` | BIGINT UNSIGNED | FK → slots |
| `appointment_start` | DATETIME | Start time of appointment |
| `appointment_end` | DATETIME | End time of appointment |
| `status` | VARCHAR(20) | `pending`, `accepted`, or `rejected` |
| `created_at` | DATETIME | Row creation timestamp |
| `updated_at` | DATETIME | Row update timestamp |

---

## REST API

**Base URL:** `{site}/index.php?rest_route=/bt-hair/v1/`

All endpoints requiring authentication use `manage_options` capability (WordPress admin role).

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| GET | `/public/options` | Public | Fetch active services and available slots for the booking form |
| POST | `/appointments` | Public | Submit a new appointment request |
| GET | `/appointments` | Admin | List all appointments |
| POST | `/appointments/{id}/status` | Admin | Update appointment status (`accepted` or `rejected`) |
| GET | `/services` | Admin | List all services |
| POST | `/services` | Admin | Create a new service |
| PUT/PATCH | `/services/{id}` | Admin | Update a service |
| DELETE | `/services/{id}` | Admin | Delete a service |
| GET | `/slots` | Admin | List all availability slots |
| POST | `/slots` | Admin | Create a new slot |
| DELETE | `/slots/{id}` | Admin | Delete a slot |
| GET | `/settings` | Admin | Get saved settings (n8n webhook URL) |
| POST | `/settings` | Admin | Save settings |

---

## Authentication & Access Control

- `/salon-dashboard/` — Admin-only. Unauthenticated users are redirected to `/sign-in/`. Authenticated non-admin users receive a 403 error.
- `/sign-in/` — Authenticated admins are automatically redirected to the dashboard.
- All admin REST endpoints require a valid WordPress nonce and `manage_options` capability.

---

## n8n Webhook Integration

An n8n webhook URL can be configured from the dashboard Settings section. When a new appointment is submitted and accepted, the theme POSTs the appointment data to the configured webhook URL, allowing automation workflows (e.g. email/SMS notifications, CRM updates) to be triggered externally.

---

## Installation

1. Copy the `bt-hair-salon` folder into `wp-content/themes/`.
2. Activate the theme from **Appearance → Themes** in the WordPress admin.
3. On activation, the theme will:
   - Create the three custom database tables.
   - Auto-create and publish the `Salon Dashboard` and `Sign In` pages with the correct templates.
   - Flush rewrite rules.
4. Visit `/salon-dashboard/` and sign in with an admin account.
5. Add services and availability slots before sharing the booking page with clients.

---

## WordPress Settings Note

This theme uses a query-string fallback for REST and page routing, so it works even without pretty permalinks enabled. However, enabling **pretty permalinks** (Settings → Permalinks → Post name) is recommended for cleaner URLs.
