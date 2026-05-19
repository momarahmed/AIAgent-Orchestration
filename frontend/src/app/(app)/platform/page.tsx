"use client";

import dynamic from "next/dynamic";

const PlatformUI = dynamic(() => import("@/components/ui/EnterpriseAIMCPPlatformPage"), { ssr: false });

export default function PlatformPage() {
  return <PlatformUI />;
}
