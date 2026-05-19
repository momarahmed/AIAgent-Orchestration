# Operations Runbook — Phase 3

> Covers incident response, secret rotation, MCP server rollback, OPA policy hotfix, and audit export procedures.

## 1. Incident Response

### Platform Health Check
```bash
curl http://127.0.0.1:8089/api/health
```

### Service Status
```bash
docker compose ps
docker compose logs --tail=50 backend
docker compose logs --tail=50 frontend
```

### Kubernetes Health
```bash
kubectl get pods -n eamcp-prod
kubectl describe pod <pod-name> -n eamcp-prod
kubectl logs <pod-name> -n eamcp-prod --tail=100
```

### Emergency: Kill All Running Workflows
```sql
UPDATE workflow_runs SET status = 'cancelled' WHERE status IN ('running', 'queued', 'awaiting_approval');
```

## 2. Secret Rotation

### Rotate a Secret in Vault
```bash
# Write new value
vault kv put secret/eamcp/prod/db-password value=NewPassword123

# Refresh all cached secrets (API call)
curl -X POST -H "Authorization: Bearer <ADMIN_TOKEN>" \
  http://127.0.0.1:8089/api/secrets/refresh
```

### Emergency: Vault Outage
The platform uses a 5-minute cache with 1-minute grace window. During vault outage:
1. Cached secrets continue working for up to 5 minutes
2. After cache expires, dev environment falls back to .env values
3. Staging/prod will deny new secret resolutions
4. **Action**: Restart vault and verify with health check

### Vault Health Check
```bash
vault status
curl http://127.0.0.1:8200/v1/sys/health
```

## 3. MCP Server Rollback

### Roll Back to Previous Version
```bash
curl -X POST -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/mcp-servers/{id}/versions/{version_id}/rollback
```

### Kubernetes Pod Rollback
```bash
kubectl rollout undo deployment/mcp-arcgis -n eamcp-prod
kubectl rollout status deployment/mcp-arcgis -n eamcp-prod
```

### Emergency: Disable MCP Server
```sql
UPDATE mcp_servers SET status = 'disabled' WHERE slug = 'arcgis-mcp';
```

## 4. OPA Policy Hotfix

### Disable a Problematic Policy
```bash
curl -X POST -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/opa-policies/{id}/disable
```

### Push Hotfix Policy to OPA
```bash
# Direct OPA API (bypasses the platform for emergencies)
curl -X PUT -H "Content-Type: text/plain" \
  --data-binary @infra/opa/hotfix.rego \
  http://127.0.0.1:8181/v1/policies/hotfix
```

### Verify Policy Effect
```bash
curl -X POST http://127.0.0.1:8181/v1/data/eamcp/tool_call \
  -d '{"input": {"agent": {"risk_level": "L1", "max_risk_level_without_approval": "L2"}, "tool": {"risk_level": "L3"}}}'
```

## 5. Audit Export

### Generate 30-Day Compliance Report
```bash
curl -X POST -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/audit-reports/export \
  -d '{"tenant_id": 1, "format": "csv", "period_start": "2026-04-19", "period_end": "2026-05-19"}'
```

### Download Export
```bash
curl -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/audit-reports/exports/{id}/download -o audit-report.csv
```

### Verify Audit Hash Chain Integrity
```sql
-- Check for broken hash chain
SELECT a.id, a.hash, a.prev_hash, b.hash as expected_prev
FROM audit_events a
LEFT JOIN audit_events b ON b.id = a.id - 1
WHERE a.prev_hash IS NOT NULL AND a.prev_hash != b.hash;
```

## 6. Security Scanner

### Trigger Manual Scan
```bash
curl -X POST -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/security-scans/trigger \
  -d '{"scan_type": "trivy", "target_type": "container", "target_ref": "ghcr.io/eamcp/backend:latest"}'
```

### Check Promotion Gate
```bash
curl -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/security-scans/deployment/{id}/gate
```

### Override Blocked Promotion (Emergency Only)
This requires Platform Owner role and creates an audit record:
```sql
UPDATE security_scans SET blocks_promotion = false WHERE deployment_id = ? AND blocks_promotion = true;
-- ALWAYS create an audit record explaining the override
```

## 7. Network Policy Enforcement

### Verify Allowlist
```bash
curl -X POST -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/network-policies/check \
  -d '{"mcp_server_id": 1, "host": "portal.arcgis.local", "port": 443, "environment": "prod"}'
```

### Generate K8s NetworkPolicy YAML
```bash
curl -H "Authorization: Bearer <TOKEN>" \
  http://127.0.0.1:8089/api/network-policies/mcp-servers/1/k8s-policy?environment=prod
```

## 8. Key Contacts

| Role | Responsibility |
|---|---|
| Platform Owner | Full system access, emergency overrides |
| Security Approver | OPA policy review, L3+ approvals |
| DBA | Database MCP Server safety policy |
| GIS Admin | ArcGIS MCP Server operations |
