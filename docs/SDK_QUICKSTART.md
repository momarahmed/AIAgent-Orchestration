# Public SDKs & CLI — Quickstart

The platform ships three first-class clients in v1.0:

- **Python SDK** — `sdk/python` (PyPI: `eamcp`)
- **TypeScript SDK** — `sdk/typescript` (npm: `@eamcp/sdk`)
- **CLI** — `sdk/cli` (npm: `@eamcp/cli`; binary `eamcp`)

All three share semantic versioning and a two-minor-version backward
compatibility window for the API surface used here.

## Authentication

```bash
eamcp login admin@example.com 'secret'
# Stores token at ~/.eamcp/token
```

Or set environment variables:

```env
EAMCP_BASE_URL=http://127.0.0.1:8089
EAMCP_TOKEN=eyJhbGciOi...
```

## Examples

### Python

```python
from eamcp import Client

c = Client()
c.login("admin@example.com", "secret")

agents = c.agents.list()
wf = c.workflows.create(name="Daily KPI", definition={"nodes": [], "edges": []})
c.workflows.run(wf["id"])

print(c.marketplace.search("monitoring SQL Server"))
```

### TypeScript

```ts
import { Client } from "@eamcp/sdk";

const c = new Client();
await c.login("admin@example.com", "secret");

const exp = await c.compliance.create({
  frameworks: ["SOC2"],
  period_start: "2026-01-01",
  period_end: "2026-03-31",
});
```

### CLI in GitHub Actions

```yaml
name: deploy-template
on:
  push:
    branches: [main]

jobs:
  publish:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: 20 }
      - run: npm install -g @eamcp/cli
      - run: eamcp login "${{ secrets.EAMCP_USER }}" "${{ secrets.EAMCP_PASSWORD }}"
        env: { EAMCP_BASE_URL: https://api.example.com }
      - run: |
          eamcp marketplace publish --payload "$(cat marketplace-publish.json)"
```

### CLI in GitLab CI

```yaml
deploy:
  image: node:20
  before_script:
    - npm install -g @eamcp/cli
    - eamcp login "$EAMCP_USER" "$EAMCP_PASSWORD"
  script:
    - eamcp compliance export --frameworks SOC2,GDPR --from 2026-01-01 --to 2026-03-31
```

### CLI in Azure DevOps

```yaml
- task: NodeTool@0
  inputs: { versionSpec: '20.x' }
- script: npm install -g @eamcp/cli
- script: eamcp login "$(EAMCP_USER)" "$(EAMCP_PASSWORD)"
- script: eamcp gitops sync $(EAMCP_ENV_ID)
```

## Coverage

The SDKs cover the full v1.0 surface:

- `agents`, `workflows`, `runs`, `templates`, `approvals` (full lifecycle)
- `marketplace` (browse / search / publish / install / rate)
- `policies` (read + dry-run OPA policies)
- `analytics` (usage / cost / reliability / template adoption)
- `compliance` (frameworks, exports, download)
- `gitops` (environments, sync, drift, failover-drill, regions)

Asset-specific destructive operations require the same RBAC permissions as
the UI does.
