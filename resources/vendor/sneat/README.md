# Sneat Bootstrap Admin Template

This directory is reserved for the licensed **Sneat Bootstrap Admin Template**
assets (ThemeSelection). Drop the template's compiled `css/`, `js/`, and `fonts/`
folders here so template updates are kept separate from application code.

Until the licensed template files are added, the dashboard renders a compatible
Bootstrap 5 shell (see `resources/css/sneat.css` and `resources/js/sneat.js`)
that reproduces Sneat's design language:

- Public Sans font
- `#f5f5f9` layout background
- White content cards with `.375rem` radius and subtle shadows
- Collapsible light sidebar + fixed topbar
- Primary color `#696cff`, body text `#566a7f`
- ApexCharts for charts

## How assets are wired

- `resources/css/sneat.css` — compiled entry (Bootstrap + icons + ApexCharts + Sneat tokens)
- `resources/js/sneat.js` — compiled entry (Bootstrap JS bundle + ApexCharts + Font Awesome)
- `resources/views/layouts/dashboard.blade.php` — master dashboard shell (sidebar + topbar + content slot)

To swap in the real template: copy its files here and update the two entries above.
