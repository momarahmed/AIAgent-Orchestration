/**
 * EAMCP — Enterprise AI + MCP + Multi-Agent Platform TypeScript SDK.
 *
 * Phase 5 / v1.0. Covers the full asset lifecycle plus marketplace,
 * compliance, GitOps + HA/DR, and analytics endpoints.
 */
import axios, { AxiosInstance, AxiosRequestConfig } from "axios";

export type ClientOptions = {
  baseUrl?: string;
  token?: string;
  timeoutMs?: number;
};

export class EamcpApiError extends Error {
  constructor(public status: number, public payload: unknown) {
    super(`EAMCP API error ${status}`);
  }
}

class Resource {
  constructor(protected http: AxiosInstance, protected prefix: string) {}

  protected async request<T>(method: string, path: string, opts: AxiosRequestConfig = {}): Promise<T> {
    try {
      const res = await this.http.request<T>({ method, url: `${this.prefix}${path}`, ...opts });
      return res.data;
    } catch (e: any) {
      if (e?.response) throw new EamcpApiError(e.response.status, e.response.data);
      throw e;
    }
  }
}

class Agents extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  get(id: number)                        { return this.request<any>("GET", `/${id}`); }
  create(payload: Record<string, any>)   { return this.request<any>("POST", "", { data: payload }); }
  update(id: number, payload: Record<string, any>) { return this.request<any>("PUT", `/${id}`, { data: payload }); }
  remove(id: number)                     { return this.request<any>("DELETE", `/${id}`); }
  runs(id: number)                       { return this.request<any>("GET", `/${id}/runs`); }
}

class Workflows extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  get(id: number)                        { return this.request<any>("GET", `/${id}`); }
  create(payload: Record<string, any>)   { return this.request<any>("POST", "", { data: payload }); }
  update(id: number, payload: Record<string, any>) { return this.request<any>("PUT", `/${id}`, { data: payload }); }
  remove(id: number)                     { return this.request<any>("DELETE", `/${id}`); }
  run(id: number, payload: Record<string, any> = {}) { return this.request<any>("POST", `/${id}/run`, { data: payload }); }
}

class Runs extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  get(id: number)                        { return this.request<any>("GET", `/${id}`); }
  cancel(id: number)                     { return this.request<any>("POST", `/${id}/cancel`); }
  replay(id: number)                     { return this.request<any>("POST", `/${id}/replay`); }
  resume(id: number)                     { return this.request<any>("POST", `/${id}/resume`); }
}

class Templates extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  exportJson(id: number)                 { return this.request<any>("GET", `/${id}/export.json`); }
  importJson(payload: Record<string, any>) { return this.request<any>("POST", "/import", { data: payload }); }
  instantiate(id: number, payload: Record<string, any>) { return this.request<any>("POST", `/${id}/instantiate`, { data: payload }); }
}

class Approvals extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  approve(id: number, comment?: string)  { return this.request<any>("POST", `/${id}/approve`, { data: { comment } }); }
  reject(id: number, comment?: string)   { return this.request<any>("POST", `/${id}/reject`,  { data: { comment } }); }
}

class Marketplace extends Resource {
  list(params: Record<string, any> = {})  { return this.request<any>("GET", "/listings", { params }); }
  search(q: string, params: Record<string, any> = {}) { return this.request<any>("GET", "/search", { params: { q, ...params } }); }
  get(id: number)                         { return this.request<any>("GET", `/listings/${id}`); }
  publish(payload: Record<string, any>)   { return this.request<any>("POST", "/publish", { data: payload }); }
  install(id: number, payload: Record<string, any>) { return this.request<any>("POST", `/listings/${id}/install`, { data: payload }); }
  rate(id: number, rating: number, review?: string) { return this.request<any>("POST", `/listings/${id}/rate`, { data: { rating, review } }); }
}

class Policies extends Resource {
  list(params: Record<string, any> = {}) { return this.request<any>("GET", "", { params }); }
  get(id: number)                        { return this.request<any>("GET", `/${id}`); }
  dryRun(id: number, input: any)         { return this.request<any>("POST", `/${id}/dry-run`, { data: { input } }); }
}

class Analytics extends Resource {
  usage(days = 30)            { return this.request<any>("GET", "/usage", { params: { days } }); }
  cost(days = 30)             { return this.request<any>("GET", "/cost",  { params: { days } }); }
  reliability(days = 30)      { return this.request<any>("GET", "/reliability", { params: { days } }); }
  templateAdoption(days = 90) { return this.request<any>("GET", "/template-adoption", { params: { days } }); }
}

class Compliance extends Resource {
  frameworks()                          { return this.request<any>("GET", "/frameworks"); }
  exports()                             { return this.request<any>("GET", "/exports"); }
  create(payload: Record<string, any>)  { return this.request<any>("POST", "/exports", { data: payload }); }
  download(id: number)                  { return this.request<ArrayBuffer>("GET", `/exports/${id}/download`, { responseType: "arraybuffer" as any }); }
}

class GitOps extends Resource {
  environments()                              { return this.request<any>("GET", "/environments"); }
  register(payload: Record<string, any>)      { return this.request<any>("POST", "/environments", { data: payload }); }
  sync(id: number)                            { return this.request<any>("POST", `/environments/${id}/sync`); }
  drift(id: number)                           { return this.request<any>("GET", `/environments/${id}/drift`); }
  regions()                                   { return this.request<any>("GET", "/regions"); }
  failoverDrill(primary: string, secondary: string) {
    return this.request<any>("POST", "/failover-drill", { data: { primary_region: primary, secondary_region: secondary } });
  }
}

export class Client {
  http: AxiosInstance;
  baseUrl: string;
  token?: string;

  agents: Agents;
  workflows: Workflows;
  runs: Runs;
  templates: Templates;
  approvals: Approvals;
  policies: Policies;
  marketplace: Marketplace;
  analytics: Analytics;
  compliance: Compliance;
  gitops: GitOps;

  constructor(opts: ClientOptions = {}) {
    this.baseUrl = (opts.baseUrl ?? process.env.EAMCP_BASE_URL ?? "http://127.0.0.1:8089").replace(/\/$/, "");
    this.token = opts.token ?? process.env.EAMCP_TOKEN;
    this.http = axios.create({
      baseURL: this.baseUrl,
      timeout: opts.timeoutMs ?? 30_000,
      headers: this.headers(),
    });

    this.agents      = new Agents(this.http,      "/api/agents");
    this.workflows   = new Workflows(this.http,   "/api/workflows");
    this.runs        = new Runs(this.http,        "/api/runs");
    this.templates   = new Templates(this.http,   "/api/templates");
    this.approvals   = new Approvals(this.http,   "/api/approvals");
    this.policies    = new Policies(this.http,    "/api/opa-policies");
    this.marketplace = new Marketplace(this.http, "/api/marketplace");
    this.analytics   = new Analytics(this.http,   "/api/analytics");
    this.compliance  = new Compliance(this.http,  "/api/compliance");
    this.gitops      = new GitOps(this.http,      "/api/gitops");
  }

  private headers(): Record<string, string> {
    const h: Record<string, string> = { Accept: "application/json", "Content-Type": "application/json" };
    if (this.token) h.Authorization = `Bearer ${this.token}`;
    return h;
  }

  async login(email: string, password: string, tenantId?: number): Promise<string> {
    const res = await this.http.post("/api/auth/login", { email, password, tenant_id: tenantId });
    const token = res.data?.token;
    this.token = token;
    this.http.defaults.headers.common.Authorization = `Bearer ${token}`;
    return token;
  }

  async health(): Promise<any> {
    const res = await this.http.get("/api/health");
    return res.data;
  }
}

export default Client;
