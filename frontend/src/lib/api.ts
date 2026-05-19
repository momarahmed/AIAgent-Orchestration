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
  // Bearer-token auth — no session cookies / CSRF flow required.
  withCredentials: false,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
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
export type Workflow = { id: number; tenant_id?: number; project_id?: number; name: string; slug: string; status: string; trigger_type: string; current_version?: { id: number; version: number; graph_json: any }; updated_at: string };
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

export const chatApi = {
  execute: (payload: { prompt: string; agent_id?: number; tenant_id?: number; project_id?: number }) =>
    api.post<{ run_id: number; response: string; mock?: boolean }>("/api/chat/execute", payload).then((r) => r.data),
};

export const templatesApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/templates", { params }).then((r) => r.data.data),
  get: (id: number) => api.get(`/api/templates/${id}`).then((r) => r.data),
};

export const toolsApi = {
  list: (serverId: number) => api.get(`/api/mcp-servers/${serverId}/tools`).then((r) => r.data.data),
  create: (serverId: number, payload: any) => api.post(`/api/mcp-servers/${serverId}/tools`, payload).then((r) => r.data),
  update: (serverId: number, toolId: number, payload: any) =>
    api.put(`/api/mcp-servers/${serverId}/tools/${toolId}`, payload).then((r) => r.data),
  remove: (serverId: number, toolId: number) => api.delete(`/api/mcp-servers/${serverId}/tools/${toolId}`),
};

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
  runs: (id: number) => api.get(`/api/agents/${id}/runs`).then((r) => r.data),
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
  get: (id: number) => api.get<Workflow & { versions?: any[] }>(`/api/workflows/${id}`).then((r) => r.data),
  create: (payload: any) => api.post<Workflow>("/api/workflows", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put<Workflow>(`/api/workflows/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/workflows/${id}`),
  run: (id: number, input: any = {}, environment: string = "dev") =>
    api.post<WorkflowRun>(`/api/workflows/${id}/run`, { input, environment }).then((r) => r.data),
  copy: (id: number, name?: string) => api.post(`/api/workflows/${id}/copy`, { name }).then((r) => r.data),
  versions: (id: number) => api.get(`/api/workflows/${id}/versions`).then((r) => r.data.data),
  diff: (id: number, a: number, b: number) => api.get(`/api/workflows/${id}/versions/${a}/diff/${b}`).then((r) => r.data),
  rollback: (id: number, version: number) => api.post(`/api/workflows/${id}/versions/${version}/rollback`).then((r) => r.data),
};

export const runsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/runs", { params }).then((r) => r.data.data),
  get: (id: number) => api.get<WorkflowRun>(`/api/runs/${id}`).then((r) => r.data),
  resume: (id: number, input: any = {}) => api.post(`/api/runs/${id}/resume`, { input }).then((r) => r.data),
  cancel: (id: number, reason?: string) => api.post(`/api/runs/${id}/cancel`, { reason }).then((r) => r.data),
  replay: (id: number, fromNodeId?: string, overrides: any = {}) =>
    api.post(`/api/runs/${id}/replay`, { from_node_id: fromNodeId, overrides }).then((r) => r.data),
  debugNode: (id: number, nodeId: string, input: any = {}) =>
    api.post(`/api/runs/${id}/debug-node`, { node_id: nodeId, input }).then((r) => r.data),
};

export const approvalsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/approvals", { params }).then((r) => r.data.data),
  approve: (id: number, comment?: string) => api.post(`/api/approvals/${id}/approve`, { comment }).then((r) => r.data),
  reject: (id: number, comment?: string) => api.post(`/api/approvals/${id}/reject`, { comment }).then((r) => r.data),
};

export const deploymentsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/deployments", { params }).then((r) => r.data.data),
  create: (payload: any) => api.post("/api/deployments", payload).then((r) => r.data),
  approve: (id: number) => api.post(`/api/deployments/${id}/approve`).then((r) => r.data),
  rollback: (id: number, reason?: string) => api.post(`/api/deployments/${id}/rollback`, { reason }).then((r) => r.data),
};

export const versionsApi = {
  agent: (id: number) => api.get(`/api/agents/${id}/versions`).then((r) => r.data.data),
  agentDiff: (id: number, a: number, b: number) => api.get(`/api/agents/${id}/versions/${a}/diff/${b}`).then((r) => r.data),
  agentRollback: (id: number, version: number) => api.post(`/api/agents/${id}/versions/${version}/rollback`).then((r) => r.data),
  mcp: (id: number) => api.get(`/api/mcp-servers/${id}/versions`).then((r) => r.data.data),
  mcpRollback: (id: number, version: number) => api.post(`/api/mcp-servers/${id}/versions/${version}/rollback`).then((r) => r.data),
};

export const templateLifecycleApi = {
  fromAsset: (payload: any) => api.post("/api/templates/from-asset", payload).then((r) => r.data),
  exportJson: (id: number) => api.get(`/api/templates/${id}/export.json`).then((r) => r.data),
  importJson: (manifest: any, tenantId?: number) => api.post("/api/templates/import", { manifest, tenant_id: tenantId }).then((r) => r.data),
  instantiate: (id: number, payload: any) => api.post(`/api/templates/${id}/instantiate`, payload).then((r) => r.data),
};

export const debugApi = {
  agent: (agentId: number, prompt: string, context: any = {}) =>
    api.post(`/api/agents/${agentId}/debug`, { prompt, context }).then((r) => r.data),
  tool: (toolId: number, inputs: any = {}) => api.post(`/api/tools/${toolId}/debug`, { inputs }).then((r) => r.data),
};

export const codegenApi = {
  generateMcp: (payload: { tenant_id: number; prompt: string; inputs?: any }) =>
    api.post("/api/codegen/mcp", payload).then((r) => r.data),
  list: (params: Record<string, any> = {}) => api.get("/api/codegen", { params }).then((r) => r.data.data),
};

export const testsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/tests", { params }).then((r) => r.data.data),
  create: (payload: any) => api.post("/api/tests", payload).then((r) => r.data),
  run: (id: number) => api.post(`/api/tests/${id}/run`).then((r) => r.data),
};

export const copyApi = {
  agent: (id: number, name?: string) => api.post(`/api/agents/${id}/copy`, { name }).then((r) => r.data),
  mcp: (id: number, name?: string) => api.post(`/api/mcp-servers/${id}/copy`, { name }).then((r) => r.data),
  workflow: (id: number, name?: string) => api.post(`/api/workflows/${id}/copy`, { name }).then((r) => r.data),
};

export const metricsApi = {
  overview: (tenantId?: number) => api.get("/api/metrics/overview", { params: { tenant_id: tenantId } }).then((r) => r.data),
  health: () => api.get("/api/health").then((r) => r.data),
};

export const auditApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/audit", { params }).then((r) => r.data.data),
};

// ─── Phase 3: RBAC/ABAC ─────────────────────────────────────────────
export const rbacApi = {
  roles: () => api.get("/api/rbac/roles").then((r) => r.data),
  createRole: (payload: any) => api.post("/api/rbac/roles", payload).then((r) => r.data),
  updateRole: (id: number, payload: any) => api.put(`/api/rbac/roles/${id}`, payload).then((r) => r.data),
  permissions: () => api.get("/api/rbac/permissions").then((r) => r.data),
  myPermissions: (params: Record<string, any> = {}) => api.get("/api/rbac/my-permissions", { params }).then((r) => r.data),
  assignTenantRole: (payload: any) => api.post("/api/rbac/assign-tenant-role", payload).then((r) => r.data),
  assignProjectRole: (payload: any) => api.post("/api/rbac/assign-project-role", payload).then((r) => r.data),
  abacPolicies: (params: Record<string, any> = {}) => api.get("/api/rbac/abac-policies", { params }).then((r) => r.data),
  createAbacPolicy: (payload: any) => api.post("/api/rbac/abac-policies", payload).then((r) => r.data),
  updateAbacPolicy: (id: number, payload: any) => api.put(`/api/rbac/abac-policies/${id}`, payload).then((r) => r.data),
  deleteAbacPolicy: (id: number) => api.delete(`/api/rbac/abac-policies/${id}`),
};

// ─── Phase 3: OPA Policies ──────────────────────────────────────────
export const opaPoliciesApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/opa-policies", { params }).then((r) => r.data),
  get: (id: number) => api.get(`/api/opa-policies/${id}`).then((r) => r.data),
  create: (payload: any) => api.post("/api/opa-policies", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put(`/api/opa-policies/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/opa-policies/${id}`),
  activate: (id: number) => api.post(`/api/opa-policies/${id}/activate`).then((r) => r.data),
  disable: (id: number) => api.post(`/api/opa-policies/${id}/disable`).then((r) => r.data),
  dryRun: (id: number, input: any) => api.post(`/api/opa-policies/${id}/dry-run`, { input }).then((r) => r.data),
  lint: (regoCode: string) => api.post("/api/opa-policies/lint", { rego_code: regoCode }).then((r) => r.data),
  library: (params: Record<string, any> = {}) => api.get("/api/opa-policies/library", { params }).then((r) => r.data),
};

// ─── Phase 3: Audit Reports ─────────────────────────────────────────
export const auditReportsApi = {
  events: (params: Record<string, any> = {}) => api.get("/api/audit-reports/events", { params }).then((r) => r.data),
  assetHistory: (subjectType: string, subjectId: number) =>
    api.get("/api/audit-reports/asset-history", { params: { subject_type: subjectType, subject_id: subjectId } }).then((r) => r.data),
  templates: () => api.get("/api/audit-reports/templates").then((r) => r.data),
  export: (payload: any) => api.post("/api/audit-reports/export", payload).then((r) => r.data),
  exports: (params: Record<string, any> = {}) => api.get("/api/audit-reports/exports", { params }).then((r) => r.data),
  download: (id: number) => api.get(`/api/audit-reports/exports/${id}/download`, { responseType: "blob" }),
};

// ─── Phase 3: Security Scans ────────────────────────────────────────
export const securityScansApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/security-scans", { params }).then((r) => r.data),
  get: (id: number) => api.get(`/api/security-scans/${id}`).then((r) => r.data),
  trigger: (payload: any) => api.post("/api/security-scans/trigger", payload).then((r) => r.data),
  scanDeployment: (id: number, targetRef: string) => api.post(`/api/security-scans/deployment/${id}`, { target_ref: targetRef }).then((r) => r.data),
  promotionGate: (id: number) => api.get(`/api/security-scans/deployment/${id}/gate`).then((r) => r.data),
  summary: (params: Record<string, any> = {}) => api.get("/api/security-scans/summary", { params }).then((r) => r.data),
};

// ─── Phase 3: Network Policies ──────────────────────────────────────
export const networkPoliciesApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/network-policies", { params }).then((r) => r.data),
  create: (payload: any) => api.post("/api/network-policies", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put(`/api/network-policies/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/network-policies/${id}`),
  check: (payload: any) => api.post("/api/network-policies/check", payload).then((r) => r.data),
};

// ─── Phase 3: Provider Budgets ──────────────────────────────────────
export const providerBudgetsApi = {
  list: (params: Record<string, any> = {}) => api.get("/api/provider-budgets", { params }).then((r) => r.data),
  create: (payload: any) => api.post("/api/provider-budgets", payload).then((r) => r.data),
  update: (id: number, payload: any) => api.put(`/api/provider-budgets/${id}`, payload).then((r) => r.data),
  remove: (id: number) => api.delete(`/api/provider-budgets/${id}`),
  check: (payload: any) => api.post("/api/provider-budgets/check", payload).then((r) => r.data),
  summary: (tenantId: number) => api.get("/api/provider-budgets/summary", { params: { tenant_id: tenantId } }).then((r) => r.data),
};
