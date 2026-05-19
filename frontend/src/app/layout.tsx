import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import { Providers } from "./providers";
import "./globals.css";

const inter = Inter({ subsets: ["latin"], display: "swap", variable: "--font-inter" });

export const metadata: Metadata = {
  title: {
    default: "Enterprise AI MCP Platform",
    template: "%s · Enterprise AI MCP Platform",
  },
  description:
    "Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform — Govern agents, MCP servers, workflows, deployments, and policies.",
};

export const viewport: Viewport = {
  themeColor: "#020617",
  width: "device-width",
  initialScale: 1,
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    // suppressHydrationWarning: browser extensions (password managers, ad blockers,
    // etc.) often inject attributes on <html>/<body> before React hydrates.
    <html lang="en" className={`${inter.variable} dark`} suppressHydrationWarning>
      <body className="bg-slate-950 text-slate-100 antialiased" suppressHydrationWarning>
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
