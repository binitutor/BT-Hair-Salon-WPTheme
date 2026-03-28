# ABOUT: BT HAIR SALON

## Overview

BT Hair Salon is a modern WordPress website designed to help clients discover services, request appointments, and communicate easily with the salon. The website combines a polished public-facing experience with an operations dashboard for administrators.

From the moment a visitor lands on the homepage, the user journey is clear:

1. Learn what the salon offers
2. View popular services and pricing
3. Select an available time slot
4. Submit an appointment request
5. Ask questions via integrated AI chat

The website is built to support both beauty branding and real booking operations. Public users see a clean, premium design and straightforward booking flow, while salon staff manage services, availability, appointment statuses, and integrations from a protected dashboard.

## What The Website Is About

BT Hair Salon's site is focused on appointment-first salon service delivery. It is not just a static brochure site. It is an interactive service platform with:

- Dynamic service listings pulled from database tables
- Live appointment slot selection
- Appointment request submission and review workflow
- Chat support for public visitors
- Admin-level controls for scheduling and integrations

The homepage messaging emphasizes quality and convenience: crafted hair experiences booked quickly. This aligns with the site's structure, where visitors can move from interest to booking in a few steps.

## Brand And User Experience

The front end uses a custom visual style with salon-oriented typography, warm tones, and a modern layout. The experience includes:

- A hero section introducing BT Hair Salon's value proposition
- A services section with cards and pricing
- A booking section that guides users through form submission
- A floating AI chat assistant for quick support

The user interface is responsive and supports desktop and mobile devices.

## Current Website Configuration (Live)

- Site URL: http://localhost/bt-hairsalon
- Site Name: BT Hair Salon
- Admin Email: binitutor1@gmail.com
- Chatbot Enabled: Yes
- Public booking page: Homepage
- Admin dashboard page: /salon-dashboard/
- Admin sign-in page: /sign-in/

## Services Offered

The current services and pricing configured in the salon database are:

- BLEACH & TONE: Pull Through - $168.00
- BLEACH & TONE: Touch Up - $117.00
- COLOR: Pull Through - $27.00
- COLOR: Touch Up - $90.00
- CUT & STYLING: Bangs & Beards - $26.00
- CUT & STYLING: Child Cut - $55.00
- CUT & STYLING: Long & Bobs - $65.00
- CUT & STYLING: Shortcuts - $55.00
- STYLE: Blowout - $55.00
- STYLE: Braids - $38.00
- STYLE: Updo - $85.00
- TEXTURE: Fusio Dose - $45.00
- TEXTURE: Perm Full - $274.00

This range covers color work, cuts, styling, and texture treatments, allowing the salon to serve both routine and advanced haircare needs.

## Hours Of Operation And Availability

BT Hair Salon uses a slot-based scheduling model. Instead of hardcoded opening and closing hours shown as static text, clients book from available time slots that admins define in the dashboard.

Current slot records include daytime and late-afternoon windows. Example configured windows include ranges such as:

- 9:00 AM to 10:00 AM
- 10:30 AM to 11:00 AM
- 12:00 PM to 1:00 PM
- 2:30 PM to 3:30 PM
- 4:00 PM to 4:30 PM

Because this is slot-driven, the salon can change practical hours by updating availability records without redesigning the public site.

## How To Book An Appointment

### Step-by-step booking instructions for clients

1. Visit the homepage at http://localhost/bt-hairsalon
2. Click Book Now or scroll to the Request Your Appointment section
3. Fill in:
	- Full Name
	- Email
	- Phone
4. Choose your Service Type from the dropdown list
5. Choose Appointment Date and Time from available slots
6. Click Submit Appointment Request

After submission, the request enters the salon workflow for review.

### What happens after booking

- New requests are stored with status = pending
- Admin staff review requests in the dashboard
- Requests are accepted or rejected based on schedule fit and business rules
- Accepted bookings lock availability as part of the management process

Current appointment summary in the database:

- Total appointments: 7
- Accepted: 1
- Pending: 1
- Rejected: 5

## How To Contact BT Hair Salon

Clients can contact or engage the salon using multiple channels supported by the website:

1. Appointment Request Form
	- Best for service bookings and structured inquiries
2. AI Chat Assistant (Public Floating Chat)
	- Best for quick questions about services, pricing, or booking process
3. Email Contact
	- Admin email currently configured: binitutor1@gmail.com

If a visitor is unsure what to book, they can start with chat and then proceed to the appointment form.

## AI Chat Support

The public chatbot is enabled and integrated with n8n. The chat widget appears as a floating assistant on public pages and allows visitors to ask salon-related questions.

Current chat webhook target configured:

- http://localhost:5678/webhook/ba1a...

This integration supports a guided, conversational pre-booking experience.

## Automation And Integration

The site includes n8n integration for:

- Appointment workflow handoff
- Chat workflow processing
- Test and validation controls from dashboard
- Secure callback support and logs for chatbot response flow

Current service webhook target configured:

- http://localhost:5678/webhook/4b06...

This architecture allows BT Hair Salon to automate notifications, confirmations, and operational tasks as workflows evolve.

## Admin Operations

The admin dashboard at /salon-dashboard/ allows authorized users to:

- Add, edit, and remove services
- Create and manage availability slots
- View all appointment requests
- Accept or reject requests
- Configure webhook endpoints
- Test service/chat integrations
- Enable or disable chatbot
- Generate callback credentials and review callback logs

Access is protected and intended for admin-level users only.

## Technical Summary

BT Hair Salon is a custom WordPress theme with:

- Public UI templates and admin dashboard templates
- Custom database tables for services, slots, appointments
- REST API routes for front-end and admin operations
- JavaScript-driven booking and dashboard interactions
- Integrated chat and automation endpoints

The result is a complete salon web platform that combines branding, booking, communication, and operational control.

## Final Description

BT Hair Salon's website is a complete digital front desk for a modern salon business. It allows clients to explore curated services with transparent pricing, request appointments through real availability windows, and interact with a chatbot for instant guidance. At the same time, it gives salon administrators practical control over services, schedules, request approvals, and automation integrations.

In short, the site is built to do three things well:

1. Present the salon professionally
2. Convert visitors into appointment requests efficiently
3. Support reliable day-to-day salon operations from a single dashboard





