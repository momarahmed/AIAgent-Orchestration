# Template Marketplace — Operator & Publisher Guide

Phase 5 introduces an **internal & external Template Marketplace** for agents,
MCP servers, workflows, prompts, policies, and deployments. This guide explains
how the marketplace works, how to publish to it, and how to govern it.

## Concepts

| Concept | Description |
|---|---|
| **Listing** | A published catalog entry. One per source `Template`. |
| **Version** | Semver-stamped manifest of a listing, signed and SBOM-hashed. |
| **Install** | Materialization of a listing into a tenant — creates draft assets. |
| **Visibility** | `internal` (tenant only), `trusted` (group of tenants), `external` (cross-org / public). |
| **Signature** | HMAC-SHA256 over the canonical manifest. In production this is swapped for cosign / Sigstore. |
| **Meta-agents** | Documentation, QA, and Security Review agents that auto-review every publish. |

## Publish lifecycle

1. Author a Template in any project.
2. Call `POST /api/marketplace/publish { template_id, visibility, ... }`.
3. `MarketplaceService::publish` runs:
   - Meta-agent review (`MetaAgentReviewService::review`).
   - Security scanner (`SecurityScannerService::scanTemplate`).
   - HMAC-SHA256 signing of the canonical manifest.
4. If `security.blocked` or scanner `blocks_promotion` → listing stays in `review` until cleared.
5. Otherwise the listing flips to `published` and gets an immutable `MarketplaceVersion`.

## Install lifecycle (governance)

`MarketplaceService::install` enforces three gates **before** the assets are
materialized:

1. **Signature gate** — unsigned listings cannot be installed in `staging`
   or `prod` (PRD §21.3, baked into both the service and `eamcp.marketplace.install`
   Rego policy).
2. **OPA policy gate** — `eamcp.marketplace.install` evaluates the install
   request and can deny / require approval.
3. **Approval gate** — high-risk categories (`mcp` in prod) bubble into the
   Phase 2 / 3 Approval queue.

If all three pass, the installer materializes the assets:

- `asset_type=agent` → creates a draft `Agent`.
- `asset_type=mcp` → creates a draft `McpServer`.
- `asset_type=workflow` → creates a draft `Workflow`.

`MarketplaceInstall.created_assets` records the resulting IDs.

## Rate / Review

- 1-5 star rating per user per listing (deduplicated).
- Optional text review with `moderation_state` (default `approved`; can be
  flagged via the Phase 3 Approval queue).
- The listing's `rating_avg` and `rating_count` are recomputed on every change.

## RAG search

Phase 5 ships a deterministic BM25 search over title + description + tags +
README so the UX is identical to a real embedding backend. To enable a
vector backend (Pinecone / pgvector / Weaviate), bind a custom implementation
of `RagSearchService::search` in `AppServiceProvider`.

## Operations

| Action | Where |
|---|---|
| Suspend a listing | Set `marketplace_listings.status = 'suspended'`. |
| Deprecate | Set `status = 'deprecated'`; updates UI banner. |
| Publish a new version | `MarketplaceService::publish` again. The service auto-bumps the patch number. |
| View install adoption | `/operations` and `/analytics` dashboards. |

## SDK / CLI quickstart

```bash
eamcp marketplace search "daily KPI report"
eamcp marketplace install 7 --payload '{"environment":"dev","parameters":{}}'
```

```python
from eamcp import Client
c = Client()
c.marketplace.publish(template_id=42, visibility="internal", tags=["finops","daily"])
```

## Risk mitigation summary (PRD §30)

- **Unsafe imports** — mandatory signing + meta-agent review + scanner + OPA.
- **Marketplace abuse** — moderation state on ratings + per-publisher reputation
  in `marketplace_listings.quality_review`.
- **Cost surprises** — installs respect Phase 3 provider budgets and Phase 5
  portfolio budgets.
