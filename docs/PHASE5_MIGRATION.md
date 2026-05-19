# Phase 5 — Migration Guide

This guide takes an existing Phase 4 installation to **v1.0 (Phase 5)**.

## 0. Pre-flight

- All Phase 1–4 capabilities are prerequisites; they remain in place.
- Backup the database. Phase 5 adds 14 tables and never drops Phase 1–4 tables.
- Have an Argo CD or Flux deployment available (or run in simulated mode).

## 1. Apply database migration

```bash
docker compose exec backend php artisan migrate
```

Adds:

- `marketplace_listings`, `marketplace_versions`, `marketplace_installs`,
  `marketplace_ratings`, `marketplace_signing_keys`
- `analytics_snapshots`, `agent_quality_scores`
- `portfolio_budgets`, `chargeback_reports`, `cost_recommendations`
- `compliance_frameworks`, `compliance_exports`
- `sbom_snapshots`, `sbom_diffs`, `vulnerability_findings`
- `gitops_environments`, `gitops_syncs`, `region_health`
- `legacy_imports`
- `locales`, `locale_translations`

## 2. Seed demo + reference data

```bash
docker compose exec backend php artisan db:seed --class=Phase5Seeder
```

This loads:

- 25 marketplace listings spanning agents, MCP servers, and workflows
- SOC 2 / ISO 27001 / GDPR compliance framework control maps
- `en` + `ar` locale dictionaries
- Sample GitOps environments (`eamcp-dev`, `eamcp-staging`, `eamcp-prod`)
- 30 days of analytics snapshots and agent quality scores
- Sample SBOM snapshots and vulnerability findings

## 3. Configure marketplace signing

In production, set:

```env
MARKETPLACE_SIGNING_KEY=<long random secret in Vault>
```

The default key falls back to `APP_KEY` for local development. Override the
`MarketplaceSignatureService` binding in `AppServiceProvider` if you have a
real cosign / Sigstore integration to plug in.

## 4. Argo CD bootstrap (optional but recommended)

```bash
kubectl apply -f infra/argocd/project.yaml
kubectl apply -f infra/argocd/root-application.yaml
```

Subsequent changes to `infra/helm/eamcp/values-*.yaml` flow through Argo CD.

## 5. Public SDKs

```bash
pip install -e sdk/python
cd sdk/typescript && npm install && npm run build
cd ../cli && npm install
```

Then publish to your private registry or to PyPI / npm as appropriate.

## 6. Localization

The frontend reads locale dictionaries from `/api/locales/{code}`. The default
bootstrap dictionaries (`en`, `ar`) are bundled into the React provider so the
app boots even when the API is unavailable.

To add another locale, insert a `locales` row + `locale_translations` rows, or
use the `LocaleController::upsert` endpoint.

## 7. Verify

```bash
docker compose exec backend php artisan test --filter=PlatformPhase5Test
```

All Phase 5 acceptance criteria should turn green.
