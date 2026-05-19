"use client";

import dynamic from "next/dynamic";

const AdminUI = dynamic(() => import("@/components/ui/EnterpriseAIMCPAdminConsole"), { ssr: false });

export default function AdminPage() {
  return <AdminUI />;
}
