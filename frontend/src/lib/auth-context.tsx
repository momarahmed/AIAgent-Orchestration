"use client";

import { createContext, ReactNode, useCallback, useContext, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import * as ApiClient from "./api";
import type { Project } from "./api";

type User = { id: number; name: string; email: string };
type Tenant = { id: number; slug: string; name: string; environment: string };

type AuthState = {
  user: User | null;
  tenants: Tenant[];
  projects: Project[];
  activeTenantId: number | null;
  activeProjectId: number | null;
  loading: boolean;
};

type AuthContextValue = AuthState & {
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  setActiveTenant: (id: number) => void;
  setActiveProject: (id: number) => void;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

const ACTIVE_TENANT_KEY = "eamcp_active_tenant";
const ACTIVE_PROJECT_KEY = "eamcp_active_project";

export function AuthProvider({ children }: { children: ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [projects, setProjects] = useState<Project[]>([]);
  const [activeTenantId, setActiveTenantIdState] = useState<number | null>(null);
  const [activeProjectId, setActiveProjectIdState] = useState<number | null>(null);
  const [loading, setLoading] = useState(true);

  const loadProjects = useCallback(async (tenantId: number | null) => {
    if (!tenantId) {
      setProjects([]);
      setActiveProjectIdState(null);
      return;
    }
    try {
      const list = await ApiClient.projectsApi.list(tenantId);
      setProjects(list);
      const stored =
        typeof window !== "undefined" ? Number(window.localStorage.getItem(ACTIVE_PROJECT_KEY)) : null;
      const activeId =
        stored && list.some((p) => p.id === stored) ? stored : (list[0]?.id ?? null);
      setActiveProjectIdState(activeId);
    } catch {
      setProjects([]);
      setActiveProjectIdState(null);
    }
  }, []);

  const refresh = useCallback(async () => {
    try {
      const data = await ApiClient.me();
      setUser(data.user);
      const tenantList = data.tenants || [];
      setTenants(tenantList);
      const storedTenant =
        typeof window !== "undefined" ? Number(window.localStorage.getItem(ACTIVE_TENANT_KEY)) : null;
      const activeTenant =
        storedTenant && tenantList.some((t: Tenant) => t.id === storedTenant)
          ? storedTenant
          : (tenantList[0]?.id ?? null);
      setActiveTenantIdState(activeTenant);
      await loadProjects(activeTenant);
    } catch {
      setUser(null);
      setTenants([]);
      setProjects([]);
      setActiveTenantIdState(null);
      setActiveProjectIdState(null);
    } finally {
      setLoading(false);
    }
  }, [loadProjects]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const setActiveTenant = useCallback(
    (id: number) => {
      setActiveTenantIdState(id);
      if (typeof window !== "undefined") window.localStorage.setItem(ACTIVE_TENANT_KEY, String(id));
      loadProjects(id);
    },
    [loadProjects],
  );

  const setActiveProject = useCallback((id: number) => {
    setActiveProjectIdState(id);
    if (typeof window !== "undefined") window.localStorage.setItem(ACTIVE_PROJECT_KEY, String(id));
  }, []);

  const handleLogin = useCallback(
    async (email: string, password: string) => {
      await ApiClient.login(email, password);
      await refresh();
      router.push("/dashboard");
    },
    [refresh, router],
  );

  const handleRegister = useCallback(
    async (name: string, email: string, password: string) => {
      await ApiClient.register(name, email, password);
      await refresh();
      router.push("/dashboard");
    },
    [refresh, router],
  );

  const handleLogout = useCallback(async () => {
    await ApiClient.logout();
    setUser(null);
    setTenants([]);
    setProjects([]);
    setActiveTenantIdState(null);
    setActiveProjectIdState(null);
    router.push("/login");
  }, [router]);

  return (
    <AuthContext.Provider
      value={{
        user,
        tenants,
        projects,
        activeTenantId,
        activeProjectId,
        loading,
        login: handleLogin,
        register: handleRegister,
        logout: handleLogout,
        setActiveTenant,
        setActiveProject,
        refresh,
      }}
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
