"use client";

import dynamic from "next/dynamic";

const ConsoleUI = dynamic(() => import("@/components/ui/EnterpriseAIMCPPlatformConsole"), { ssr: false });

export default function ConsolePage() {
  return <ConsoleUI />;
}
