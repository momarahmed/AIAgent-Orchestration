# EAMCP Python SDK

The official Python SDK for the **Enterprise AI + MCP + Multi-Agent Platform** — v1.0 (Phase 5).

## Install

```bash
pip install eamcp
```

For local development from this repo:

```bash
pip install -e sdk/python
```

## Quick start

```python
from eamcp import Client

c = Client(base_url="http://127.0.0.1:8089")
c.login("admin@example.com", "secret")

# Asset lifecycle
agents = c.agents.list()
wf = c.workflows.create(name="Daily KPI", definition={"nodes": [], "edges": []})

# Runs
run = c.workflows.run(wf["id"], input={"prompt": "Generate today's report"})
print(c.runs.get(run["id"]))

# Marketplace
results = c.marketplace.search("monitoring SQL Server health daily")
for r in results["results"]:
    print(r["title"], r["score"])

# Compliance
exp = c.compliance.create(frameworks=["SOC2"], period_start="2026-01-01", period_end="2026-03-31")

# GitOps + DR
print(c.gitops.environments())
print(c.gitops.failover_drill("us-east-1", "eu-west-1"))
```

## Versioning

This SDK follows semantic versioning. The platform maintains backward
compatibility for two minor versions on the API surface used here.
