# @eamcp/cli

Command-line interface for the EAMCP Platform — v1.0 (Phase 5).

```bash
npm install -g @eamcp/cli

eamcp login admin@example.com 'secret'
eamcp agents list
eamcp marketplace search "daily GIS health report"
eamcp compliance export --frameworks SOC2,ISO27001 --from 2026-01-01 --to 2026-03-31
eamcp gitops failover-drill us-east-1 eu-west-1
```

CI/CD recipes for GitHub Actions, GitLab CI, and Azure DevOps live in
`docs/SDK_QUICKSTART.md`.
