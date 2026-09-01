# Operational Governance Roadmap

## Delivered

- Client onboarding activates the organization, clinic, first location, services, workspace, and owner account through one final action.
- Onboarding clinic codes are system-generated, read-only, and rechecked for uniqueness at activation.
- Clinics can define a default scheduling location; assigned user location remains the first priority.
- Provider records include state licenses, expiry dates, NPI, taxonomy, credentialing dates/status, additional licenses, DEA, and scheduling buffer.
- Provider Tax ID and DEA values are encrypted at rest and masked in record views.
- Clinic, location, provider, and operatory working hours support weekday and weekend schedules plus recurring daily breaks.
- Location, provider, and operatory exceptions support full-day closures and time-specific downtime.
- Appointment slots and save-time validation enforce hours, breaks, leave, closures, operatory availability, provider buffer, and booking conflicts.

## Future Enhancements

- Replace a provider's single primary location with governed multi-location assignments and location-specific hours.
- Add credential document uploads, source verification, approval workflow, renewal reminders, and audit history.
- Add reusable holiday calendars and recurring exception rules across locations.
- Add operatory capability tags so services can require compatible rooms or equipment.
- Add provider coverage, waitlist, overbooking permissions, and emergency-slot rules.
- Add invitation and email-verification status indicators to onboarding review and client management.
- Add scheduling analytics for utilization, no-shows, lead time, and capacity.
