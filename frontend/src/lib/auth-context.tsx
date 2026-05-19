"use client";

import { createContext, ReactNode, useCallback, useContext, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import * as ApiClient from "./api";

type User = { id: number; name: string; email: string };
type Tenant = { id: number; slug: string; name: string; environment: string };

type AuthState = {
  user: User | null;
  tenants: Tenant[];
  activeTenantId: number | null;
  loading: boolean;
};

type AuthContextValue = AuthState & {
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  setActiveTenant: (id: number) => void;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

const ACTIVE_TENANT_KEY = "eamcp_active_tenant";

export function AuthProvider({ children }: { children: ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [activeTenantId, setActiveTenantIdState] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    try {
      const data = await ApiClient.me();
      setUser(data.user);
      setTenants(data.tenants || []);
      const stored = typeof window !== "undefined" ? Number(window.localStorage.getItem(ACTIVE_TENANT_KEY)) : null;
      const activeId = stored && (data.tenants || []).some((t: Tenant) => t.id === stored)
        ? stored
        : (data.tenants?.[0]?.id ?? null);
      setActiveTenantIdState(activeId);
    } catch (_) {
      setUser(null);
      setTenants([]);
      setActiveTenantIdState(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { refresh(); }, [refresh]);

  const setActiveTenant = useCallback((id: number) => {
    setActiveTenantIdState(id);
    if (typeof window !== "undefined") window.localStorage.setItem(ACTIVE_TENANT_KEY, String(id));
  }, []);

  const handleLogin = useCallback(async (email: string, password: string) => {
    await ApiClient.login(email, password);
    await refresh();
    router.push("/dashboard");
  }, [refresh, router]);

  const handleRegister = useCallback(async (name: string, email: string, password: string) => {
    await ApiClient.register(name, email, password);
    await refresh();
    router.push("/dashboard");
  }, [refresh, router]);

  const handleLogout = useCallback(async () => {
    await ApiClient.logout();
    setUser(null); setTenants([]); setActiveTenantIdState(null);
    router.push("/login");
  }, [router]);

  return (
    <AuthContext.Provider
      value={{ user, tenants, activeTenantId, loading, login: handleLogin, register: handleRegister, logout: handleLogout, setActiveTenant, refresh }}
    >
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used within AuthProvider");
  return ctx;
}
