from __future__ import annotations

import os
from typing import Any, Dict, Optional

import httpx


class ApiError(Exception):
    def __init__(self, status: int, payload: Any):
        self.status = status
        self.payload = payload
        super().__init__(f"EAMCP API error {status}: {payload!r}")


class _Resource:
    def __init__(self, client: "Client", prefix: str):
        self._c = client
        self._p = prefix.rstrip("/")

    def _request(self, method: str, path: str, **kwargs: Any) -> Any:
        return self._c._request(method, f"{self._p}{path}", **kwargs)


class _Agents(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def get(self, agent_id: int) -> Any:  return self._request("GET", f"/{agent_id}")
    def create(self, **payload: Any) -> Any: return self._request("POST", "", json=payload)
    def update(self, agent_id: int, **payload: Any) -> Any: return self._request("PUT", f"/{agent_id}", json=payload)
    def delete(self, agent_id: int) -> Any: return self._request("DELETE", f"/{agent_id}")
    def runs(self, agent_id: int) -> Any:   return self._request("GET", f"/{agent_id}/runs")


class _Workflows(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def get(self, wf_id: int) -> Any:     return self._request("GET", f"/{wf_id}")
    def create(self, **payload: Any) -> Any: return self._request("POST", "", json=payload)
    def update(self, wf_id: int, **payload: Any) -> Any: return self._request("PUT", f"/{wf_id}", json=payload)
    def delete(self, wf_id: int) -> Any:  return self._request("DELETE", f"/{wf_id}")
    def run(self, wf_id: int, **payload: Any) -> Any: return self._request("POST", f"/{wf_id}/run", json=payload)


class _Runs(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def get(self, run_id: int) -> Any:    return self._request("GET", f"/{run_id}")
    def cancel(self, run_id: int) -> Any: return self._request("POST", f"/{run_id}/cancel")
    def replay(self, run_id: int) -> Any: return self._request("POST", f"/{run_id}/replay")
    def resume(self, run_id: int) -> Any: return self._request("POST", f"/{run_id}/resume")


class _Templates(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def export_json(self, tid: int) -> Any: return self._request("GET", f"/{tid}/export.json")
    def import_json(self, payload: Dict[str, Any]) -> Any: return self._request("POST", "/import", json=payload)
    def instantiate(self, tid: int, **payload: Any) -> Any: return self._request("POST", f"/{tid}/instantiate", json=payload)


class _Approvals(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def approve(self, aid: int, comment: Optional[str] = None) -> Any:
        return self._request("POST", f"/{aid}/approve", json={"comment": comment} if comment else {})
    def reject(self, aid: int, comment: Optional[str] = None) -> Any:
        return self._request("POST", f"/{aid}/reject", json={"comment": comment} if comment else {})


class _Marketplace(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "/listings", params=params)
    def search(self, q: str, **params: Any) -> Any: return self._request("GET", "/search", params={"q": q, **params})
    def get(self, lid: int) -> Any: return self._request("GET", f"/listings/{lid}")
    def publish(self, **payload: Any) -> Any: return self._request("POST", "/publish", json=payload)
    def install(self, lid: int, **payload: Any) -> Any: return self._request("POST", f"/listings/{lid}/install", json=payload)
    def rate(self, lid: int, rating: int, review: Optional[str] = None) -> Any:
        return self._request("POST", f"/listings/{lid}/rate", json={"rating": rating, "review": review})


class _Policies(_Resource):
    def list(self, **params: Any) -> Any: return self._request("GET", "", params=params)
    def get(self, pid: int) -> Any:       return self._request("GET", f"/{pid}")
    def dry_run(self, pid: int, input_payload: Dict[str, Any]) -> Any:
        return self._request("POST", f"/{pid}/dry-run", json={"input": input_payload})


class _Analytics(_Resource):
    def usage(self, days: int = 30) -> Any: return self._request("GET", "/usage", params={"days": days})
    def cost(self, days: int = 30) -> Any:  return self._request("GET", "/cost",  params={"days": days})
    def reliability(self, days: int = 30) -> Any: return self._request("GET", "/reliability", params={"days": days})
    def template_adoption(self, days: int = 90) -> Any: return self._request("GET", "/template-adoption", params={"days": days})


class _Compliance(_Resource):
    def frameworks(self) -> Any: return self._request("GET", "/frameworks")
    def exports(self) -> Any:    return self._request("GET", "/exports")
    def create(self, **payload: Any) -> Any: return self._request("POST", "/exports", json=payload)
    def download(self, eid: int) -> bytes:
        url = f"{self._c.base_url}{self._p}/exports/{eid}/download"
        with httpx.Client(headers=self._c._headers(), timeout=60) as cli:
            r = cli.get(url)
            r.raise_for_status()
            return r.content


class _GitOps(_Resource):
    def environments(self) -> Any: return self._request("GET", "/environments")
    def register(self, **payload: Any) -> Any: return self._request("POST", "/environments", json=payload)
    def sync(self, eid: int) -> Any: return self._request("POST", f"/environments/{eid}/sync")
    def drift(self, eid: int) -> Any: return self._request("GET", f"/environments/{eid}/drift")
    def regions(self) -> Any: return self._request("GET", "/regions")
    def failover_drill(self, primary: str, secondary: str) -> Any:
        return self._request("POST", "/failover-drill", json={"primary_region": primary, "secondary_region": secondary})


class Client:
    """Top-level EAMCP API client."""

    def __init__(
        self,
        base_url: Optional[str] = None,
        token: Optional[str] = None,
        timeout: float = 30.0,
    ) -> None:
        self.base_url = (base_url or os.environ.get("EAMCP_BASE_URL", "http://127.0.0.1:8089")).rstrip("/")
        self.token = token or os.environ.get("EAMCP_TOKEN")
        self.timeout = timeout
        self._http = httpx.Client(base_url=self.base_url, timeout=timeout, headers=self._headers())

        self.agents       = _Agents(self,       "/api/agents")
        self.workflows    = _Workflows(self,    "/api/workflows")
        self.runs         = _Runs(self,         "/api/runs")
        self.templates    = _Templates(self,    "/api/templates")
        self.approvals    = _Approvals(self,    "/api/approvals")
        self.policies     = _Policies(self,     "/api/opa-policies")
        self.marketplace  = _Marketplace(self,  "/api/marketplace")
        self.analytics    = _Analytics(self,    "/api/analytics")
        self.compliance   = _Compliance(self,   "/api/compliance")
        self.gitops       = _GitOps(self,       "/api/gitops")

    def _headers(self) -> Dict[str, str]:
        h = {"Accept": "application/json", "Content-Type": "application/json"}
        if self.token:
            h["Authorization"] = f"Bearer {self.token}"
        return h

    def _request(self, method: str, path: str, **kwargs: Any) -> Any:
        r = self._http.request(method, path, **kwargs)
        if r.status_code >= 400:
            try:
                payload = r.json()
            except Exception:
                payload = r.text
            raise ApiError(r.status_code, payload)
        if not r.content:
            return None
        ct = r.headers.get("Content-Type", "")
        if "json" in ct:
            return r.json()
        return r.content

    def login(self, email: str, password: str, tenant_id: Optional[int] = None) -> str:
        """Exchange email/password for a Sanctum token and store it."""
        payload = {"email": email, "password": password}
        if tenant_id is not None:
            payload["tenant_id"] = tenant_id
        resp = self._http.post("/api/auth/login", json=payload)
        resp.raise_for_status()
        token = resp.json().get("token")
        self.token = token
        self._http.headers.update(self._headers())
        return token

    def health(self) -> Any:
        return self._request("GET", "/api/health")
