"use client";

import axios, { AxiosError, AxiosInstance } from "axios";

/**
 * Centralised API client for the Enterprise AI MCP Platform backend.
 * Browser → http://127.0.0.1:8089
 * Server   → http://backend:8000 (compose internal network) — see /lib/server-api.ts
 */
const baseURL =
  (typeof window !== "undefined"
    ? process.env.NEXT_PUBLIC_API_URL
    : process.env.INTERNAL_API_URL) || "http://127.0.0.1:8089";

export const api: AxiosInstance = axios.create({
  baseURL,
  withCredentials: true,
  headers: { Accept: "application/json" },
});

const TOKEN_KEY = "eamcp_token";

export function setAuthToken(token: string | null) {
  if (typeof window === "undefined") return;
  if (token) {
    window.localStorage.setItem(TOKEN_KEY, token);
  } else {
    window.localStorage.removeItem(TOKEN_KEY);
  }
}

export function getAuthToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

api.interceptors.request.use((config) => {
  const token = getAuthToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (r) => r,
  (error: AxiosError) => {
    if (error.response?.status === 401 && typeof window !== "undefined") {
      setAuthToken(null);
      const path = window.location.pathname;
      if (!path.startsWith("/login")) {
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

export type ApiPaginated<T> = {
  data: T[];
  current_page?: number;
  total?: number;
  per_page?: number;
};

export type Tenant = { id: number; slug: string; name: string; environment: string; projects_count?: number };
export type Project = { id: number; tenant_id: number; slug: string; name: string; description?: string };
export type Agent = { id: number; tenant_id: number; project_id: number; name: string; slug: string; status: string; risk_level: string; description?: string; current_version?: any; updated_at: string };
export type McpServer = { id: number; name: string; slug: string; transport: string; runtime: string; endpoint?: string; status: string; health: string; tools?: Tool[]; updated_at: string };
export type Tool = { id: number; mcp_server_id: number; name: string; description?: string; risk_level: string; is_enabled: boolean };
export type Workflow = { id: number; name: string; slug: string; status: string; trigger_type: string; current_version?: { id: number; version: number; graph_json: any }; updated_at: string };
export type WorkflowRun = { id: number; workflow_id: number; status: string; started_at?: string; completed_at?: string; output?: any; error?: string; tasks?: any[] };

// Auth ----------------------------------------------------------------
export async function login(email: string, password: string) {
  const { data } = await api.post("/api/auth/login", { email, password });
  setAuthToken(data.token);
  return data;
}
export async function register(name: string, email: string, password: string) {
  const { data } = await api.post("/api/auth/register", { name, email, password, password_confirmation: password });
  setAuthToken(data.token);
  return data;
}
export async function me() {
  const { data } = await api.get("/api/auth/me");
  return data;
}
export async function logout() {
  try { await api.post("/api/auth/logout"); } catch {}
  setAuthToken(null);
}

// Resources -----------------------------------------------------------
export const tenantsApi = {
  list: () => api.get<{ data: Tenant[] }>("/api/tenants").then((r) => r.data.data),
  create: (payload: Partial<Tenant>) => api.post<Tenant>("/api/tenants", payload).then((r) => r.data),
};

export const projectsApi = {
  list: (tenantId?: number) => api.get<{ data: Project[] }>("/api/projects", { params: { tenant_id: tenantId } }).then((r) => r.data.data),
  create: (payload: Partial<Project>) => api.post<Project>("/api/projects", payload).then((r) => r.data),
};

export const agentsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/agents", { params }).then((r) => r.data.data),
  get: (id: number) => api.get<Agent>(`/api/agents/${id}`).then((r) => r.data),
  create: (payload: any) => api.post<Agent>("/api/agents", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put<Agent>(`/api/agents/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/agents/${id}`),
  duplicate: (id: number) => api.post<Agent>(`/api/agents/${id}/duplicate`).then((r) => r.data),
};

export const mcpServersApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/mcp-servers", { params }).then((r) => r.data.data),
  get: (id: number) => api.get<McpServer>(`/api/mcp-servers/${id}`).then((r) => r.data),
  create: (payload: any) => api.post<McpServer>("/api/mcp-servers", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put<McpServer>(`/api/mcp-servers/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/mcp-servers/${id}`),
  health: (id: number) => api.post(`/api/mcp-servers/${id}/health`).then((r) => r.data),
};

export const workflowsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/workflows", { params }).then((r) => r.data.data),
  get: (id: number) => api.get<Workflow>(`/api/workflows/${id}`).then((r) => r.data),
  create: (payload: any) => api.post<Workflow>("/api/workflows", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put<Workflow>(`/api/workflows/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/workflows/${id}`),
  run: (id: number, input: any = {}) => api.post<WorkflowRun>(`/api/workflows/${id}/run`, { input }).then((r) => r.data),
};

export const runsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/runs", { params }).then((r) => r.data.data),
  get: (id: number) => api.get<WorkflowRun>(`/api/runs/${id}`).then((r) => r.data),
};

export const metricsApi = {
  overview: (tenantId?: number) => api.get("/api/metrics/overview", { params: { tenant_id: tenantId } }).then((r) => r.data),
  health: () => api.get("/api/health").then((r) => r.data),
};

export const auditApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/audit", { params }).then((r) => r.data.data),
};
