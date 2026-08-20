# ProDental Design Tokens

Version: 1.0
Status: Foundation Implemented
Owner: Product Engineering
Last Updated: 2026-08-06

---

## Token Source

Primary CSS token source:

- `resources/css/pwdl.css`

AppShell runtime token aliases:

- `resources/views/filament/appshell/styles.blade.php`

## Brand

- `--pwdl-brand-teal`
- `--pwdl-brand-teal-hover`
- `--pwdl-brand-teal-active`
- `--pwdl-brand-teal-soft`
- `--pwdl-brand-teal-ring`
- `--pwdl-brand-primary`
- `--pwdl-brand-primary-hover`
- `--pwdl-brand-primary-active`
- `--pwdl-brand-primary-soft`
- `--pwdl-brand-primary-ring`

Brand color is identity only. Teal should be used for logo, active navigation, primary actions, focus rings, and small emphasis markers.

## Neutral Palette

- `--pwdl-neutral-white`
- `--pwdl-neutral-soft-gray`
- `--pwdl-neutral-medium-gray`
- `--pwdl-neutral-dark-gray`

Neutral colors own the application surface, layout, text hierarchy, borders, empty states, and most controls.

## Surfaces

- `--pwdl-surface-background`
- `--pwdl-surface-card`
- `--pwdl-surface-muted`
- `--pwdl-surface-soft`

## Borders

- `--pwdl-border-default`
- `--pwdl-border-subtle`
- `--pwdl-border-strong`

## Text

- `--pwdl-text-primary`
- `--pwdl-text-secondary`
- `--pwdl-text-muted`
- `--pwdl-text-placeholder`
- `--pwdl-text-inverse`

## Status

- `--pwdl-status-success`
- `--pwdl-status-warning`
- `--pwdl-status-danger`
- `--pwdl-status-info`

Semantic colors are reserved for meaning only:

- Green: success
- Orange: warning
- Red: error
- Blue: information

## Usage Ratio

- 90% neutral
- 8% brand teal
- 2% semantic colors

Do not use semantic colors for decoration. Do not use teal as a large page background unless the surface is a brand identity moment.

## Header Token Usage

- Brand zone uses `--pwdl-brand-primary` only for the PD logo and small active/focus accents.
- Header surfaces use neutral white, soft gray, neutral borders, and neutral text tokens.
- User avatar uses brand-soft treatment, not a separate accent color.
- Notifications, help, workspace switcher, and search stay neutral unless they represent a real state.
- Decorative yellow or extra accent colors are not part of the AppShell header.

## Layout

- `--pwdl-layout-left`: 300px
- `--pwdl-layout-right`: 320px
- `--pwdl-layout-gap`: 24px

## Rule

Future brand changes should begin by changing token values, not rewriting component markup.

---

END OF DOCUMENT
