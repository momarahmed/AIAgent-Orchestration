Act as a senior frontend engineer, UI systems architect, and product-minded dashboard designer.

First, inspect all relevant TSX (----) files in the project. Understand the current app structure, styling system, routing, component hierarchy, charting libraries, state management, and available UI utilities.

Then transform the existing frontend into a futuristic enterprise-grade dashboard platform.

Primary objective:
Build a premium SaaS dashboard experience with a Login page, Admin Console, Dashboard Platform Console, Platform Page, Dashboard Preview Mode, and a comprehensive Chart Gallery containing as many enterprise chart types as the project can reasonably support.

Do not rewrite the entire app blindly. Work with the existing architecture. Refactor only where it improves maintainability, consistency, or scalability.

Implementation priorities:

1. Preserve and extend the existing TSX (----) architecture.
2. Create reusable UI components instead of duplicating markup.
3. Use realistic mock data.
4. Make the design polished, futuristic, responsive, and enterprise-ready.
5. Ensure TypeScript correctness.
6. Ensure all pages feel complete, not placeholder-like.
7. Keep the UI coherent across all pages.

Required pages:

Login Page:
Create a premium enterprise login screen with branded visuals, email/password login, SSO option, remember me, forgot password, validation states, loading state, and a futuristic visual background.

Admin Console:
Create a full admin area with overview cards, user management, roles and permissions, teams, billing, audit logs, API keys, integrations, security settings, system health, and workspace settings. Include tables, filters, role badges, action menus, modals/drawers, and empty/loading states.

Dashboard Platform Console:
Create the main analytics console with KPI cards, dashboard grid/list, recent activity, alerts, system health, data source status, revenue metrics, usage metrics, conversion metrics, date filters, search, export/share actions, and saved dashboards.

Platform Page:
Create a visually strong platform overview page with hero section, feature cards, architecture visualization, integrations, security/compliance blocks, use cases, and dashboard preview area.

Dashboard Preview Mode:
Create a fullscreen dashboard preview UI with desktop/tablet/mobile preview controls, floating toolbar, edit/preview toggle, refresh, share, export, presentation mode, responsive chart layout, loading states, and empty states.

Chart Gallery:
Create a chart gallery or analytics section with the following chart types where supported:
line, multi-line, area, stacked area, bar, horizontal bar, grouped bar, stacked bar, pie, donut, gauge, radar, radial, scatter, bubble, heatmap, calendar heatmap, treemap, funnel, waterfall, histogram, timeline, Gantt-style timeline, candlestick, box plot, Sankey, map/geospatial, KPI cards, progress charts, cohort chart, retention chart, conversion funnel, SLA/uptime chart, latency chart, error-rate chart, revenue chart, forecast chart, anomaly detection mockup, real-time activity chart, distribution chart, leaderboard table, and data table with sparklines.

If a chart type is not supported by the installed chart library, create a visually convincing custom mock component using HTML/CSS/SVG rather than adding unnecessary dependencies.

Design style:
Use a futuristic enterprise design system:
- Dark premium base
- Glass panels
- Subtle gradients
- Neon cyan/violet/blue accents
- Soft borders
- Strong typography hierarchy
- Beautiful cards
- Clear navigation
- Smooth interactive states
- Professional chart colors
- Responsive layouts
- Executive-level polish

Reusable components to create or improve:
- AppShell
- Sidebar
- TopNav
- PageHeader
- MetricCard
- ChartCard
- DashboardCard
- DataTable
- FilterBar
- DateRangeSelector
- StatusBadge
- EmptyState
- LoadingSkeleton
- Modal
- Drawer
- PreviewToolbar
- ChartGrid
- AdminTable
- SettingsCard

Mock data:
Create realistic mock datasets for users, teams, permissions, audit logs, revenue, traffic, product usage, alerts, integrations, system health, API usage, dashboards, data sources, chart datasets, and security events.

Accessibility:
Use semantic HTML, accessible buttons, readable contrast, focus states, aria labels where needed, and keyboard-friendly controls.

Responsiveness:
Ensure the app works well on desktop, tablet, and mobile. The dashboard grid, admin tables, preview mode, login page, and chart gallery must adapt cleanly.

Final check:
Before finishing, verify:
- No broken imports
- No obvious TypeScript issues
- No missing components
- No incomplete placeholder screens
- Consistent visual system
- Responsive layout
- Useful mock data
- Clean component architecture

Final response should include:
- Summary of implemented changes
- Files created or modified
- How to preview the app
- Any assumptions made