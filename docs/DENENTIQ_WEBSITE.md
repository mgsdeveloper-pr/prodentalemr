# Denentiq website — separate project

The marketing website has moved to `C:\xampp\htdocs\denentiq-website`.

It has its own source pages, assets, Node build (no dependencies), standalone PHP enquiry endpoint, configuration and deployment instructions. Deploy that project's `dist` folder independently. Its Login links point to https://app.denentiq.com via site.config.json.

This Laravel application's root redirects to /login. Marketing pages remain independently hosted. The website now connects through a private server-to-server gateway for registration and published plans. Registration drafts live in this app and resume emails use the app mailer; the separate pricing-enquiry handler retains its own website mail settings.

See [DENENTIQ_REGISTRATION.md](DENENTIQ_REGISTRATION.md) for the registration flow, plan creation and cPanel deployment steps.

See the separate project's README.md for hosting and outgoing email setup. Privacy and Terms remain drafts pending review.
