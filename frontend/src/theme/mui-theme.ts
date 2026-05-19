"use client";

import { createTheme } from "@mui/material/styles";

/**
 * Futuristic enterprise dark theme — neon cyan + violet accents.
 * Aligns with Tailwind tokens used by the imported UI components.
 */
export const muiTheme = createTheme({
  palette: {
    mode: "dark",
    primary: { main: "#22d3ee" },
    secondary: { main: "#a855f7" },
    success: { main: "#34d399" },
    error:   { main: "#f87171" },
    warning: { main: "#fbbf24" },
    info:    { main: "#60a5fa" },
    background: {
      default: "#020617",
      paper: "#0f172a",
    },
    divider: "rgba(148, 163, 184, 0.16)",
  },
  typography: {
    fontFamily:
      'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, ui-sans-serif, system-ui, sans-serif',
    h1: { fontWeight: 700, letterSpacing: "-0.02em" },
    h2: { fontWeight: 700, letterSpacing: "-0.02em" },
    h3: { fontWeight: 600 },
    button: { textTransform: "none", fontWeight: 600 },
  },
  shape: { borderRadius: 16 },
  components: {
    MuiPaper: {
      styleOverrides: {
        root: {
          backgroundImage: "none",
          border: "1px solid rgba(148, 163, 184, 0.12)",
        },
      },
    },
    MuiButton: {
      defaultProps: { disableElevation: true },
      styleOverrides: {
        root: { borderRadius: 14, paddingInline: 18, paddingBlock: 10 },
      },
    },
  },
});
