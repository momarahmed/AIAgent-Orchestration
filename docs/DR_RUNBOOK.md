# Regional Failover Runbook — v1.0 (Phase 5)

This runbook is **executed verbatim during quarterly DR drills** and during a
real regional outage.

## SLO

- **RTO**: ≤ 30 minutes from primary failure to traffic served from secondary.
- **RPO**: ≤ 5 minutes of data loss.

## Topology

- **Primary region**: `us-east-1` — full stack (backend, OPA, Temporal,
  Kafka, MySQL/Postgres primary, Vault primary, object storage primary).
- **Secondary region**: `eu-west-1` — warm-standby (managed DB read replica,
  cross-region object storage replication, Vault performance secondary,
  Temporal global namespace, Kafka stretched cluster).

State is captured per-table in the `region_health` table and surfaced on
`/operations` and `/gitops`.

## Detection

1. Pager alert from synthetic check on `https://app.example.com/api/health`
   failing 3 × 60 s.
2. Argo CD reports primary cluster as **Out Of Sync — connection refused**.
3. `RegionHealth` row for the primary flips to `status=degraded` via the
   regional probe.

## Failover Procedure

1. **Confirm primary impact.** Page the on-call SRE and Platform Owner.
2. **Trigger failover drill / actual failover** via the platform UI or CLI:
   ```bash
   eamcp gitops failover-drill us-east-1 eu-west-1
   ```
   This records the event in the audit log (`dr_drill`).
3. **Promote DB replica** — for AWS RDS:
   ```bash
   aws rds promote-read-replica --db-instance-identifier eamcp-db-eu
   ```
4. **Switch global load balancer** to route to the secondary cluster's
   ingress. Route 53 weighted policy → 100% to `app-eu.example.com`.
5. **Sync Argo CD secondary** — the `eamcp-prod` Application in the secondary
   cluster reconciles to the latest commit.
6. **Verify Temporal workflows** resume from last checkpoint:
   ```bash
   eamcp runs list --status running --since 30m
   ```
   Any run that does not resume within 10 minutes is escalated; check
   `WorkflowRun.state` for the last checkpoint and replay via `eamcp runs replay`.
7. **Verify OPA bundle** loaded in the secondary cluster matches the primary
   commit hash.
8. **Confirm marketplace + audit exports** are usable on the secondary.

## Post-Failover

1. Open an incident review issue.
2. Compute actual RTO / RPO and record in the `region_health.measurements`
   JSON column for the affected regions.
3. If RPO > 5 minutes or RTO > 30 minutes, file a remediation ticket and
   block the next promote until it is fixed.

## Failback

1. Once the original primary is healthy, set up reverse replication from
   `eu-west-1` → `us-east-1`.
2. During a maintenance window, run the same procedure with roles swapped.

## Quarterly Drill Acceptance

A drill is "successful" when:

- All Temporal workflows resume from their last checkpoint.
- No marketplace install / compliance export fails for ≥ 5 minutes after
  cutover.
- RTO ≤ 30 minutes and RPO ≤ 5 minutes.
- The audit log contains a complete `dr_drill` event chain.
