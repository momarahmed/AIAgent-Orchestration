# Phase 2 — Migration Guide

This guide covers the zero-downtime cut-over from the Phase 1 in-process
workflow runner to the Phase 2 durable engine. The platform exposes a single
`App\Contracts\WorkflowEngine` binding, which lets us swap the implementation
without touching controllers or the Phase 1 run API contract.

## Steps

1. Deploy backend image with Phase 2 schema and the `DurableWorkflowEngine` bound.
2. Run `php artisan migrate --force` — the Phase 2 migration is additive and
   non-destructive (new tables + nullable columns).
3. Roll the frontend to v1.2 to surface the new Approvals, Deployments, Versions,
   Templates, Debug, and Codegen pages.
4. Switch the `WorkflowEngine` binding to the Temporal-backed engine
   (Phase 3) once the Temporal cluster is provisioned. The contract stays
   identical; only the binding flips.
5. Drain old runs from the in-process engine (which already wrote
   `WorkflowRun` rows) — durable engine reads checkpoints from the same table.

## Compatibility

* Phase 1 runs remain valid and queryable. Their `checkpoint` is null; the
  durable engine still recognises them as terminal.
* Existing graphs continue to work; the new node types (Approval, Decision,
  Transform, Loop, Parallel, Error Handler, Template) are additive.
* `WorkflowController::run` accepts an optional `environment` field; it
  defaults to `dev`.
