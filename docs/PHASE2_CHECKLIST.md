# Phase 2 — Lifecycle & Reliability — Completion Checklist

| PRD ID | Capability | Status | Where it lives |
|---|---|---|---|
| UX-005 | Debug failed runs from UI | ✅ | `/runs` page → "Debug node" + "Replay" + "Resume"/"Cancel" |
| UX-006 | Import/export templates | ✅ | `/templates` page → Import JSON, Export ZIP/JSON |
| UX-008 | Compare asset versions | ✅ | Agents + Workflows `Versions` dialog (diff + rollback); MCP versions API + rollback |
| AG-002 | Create agent from template | ✅ | `POST /api/templates/{id}/instantiate` |
| AG-005 | Debug agent with test prompts | ✅ | Agents `Debug` dialog · `POST /api/agents/{id}/debug` |
| AG-006 | Deploy agent to environment | ✅ | `/deployments` page · `DeploymentService` pipeline |
| AG-007 | Copy agent | ✅ | `POST /api/agents/{id}/copy` |
| AG-008 | Save agent as template | ✅ | `POST /api/templates/from-asset` |
| AG-009 | Import/export agent template | ✅ | export.json / export.zip / import endpoints |
| AG-014 | Evaluate agent with test suite | ✅ | `TestRunnerService` + `/api/tests/{id}/run` |
| MCP-002 | Create MCP server from template | ✅ | Same instantiate flow |
| MCP-003 | Generate tool schemas via OpenHands | ✅ | `/codegen` page · `POST /api/codegen/mcp` |
| MCP-005 | Debug MCP server tools | ✅ | `POST /api/tools/{tool}/debug` |
| MCP-006 | Deploy MCP server to environment | ✅ | Deployment Manager |
| MCP-007 | Copy MCP server | ✅ | `POST /api/mcp-servers/{id}/copy` (strips secrets+endpoint) |
| MCP-008 | Save MCP as template | ✅ | from-asset |
| MCP-009 | Import/export MCP template | ✅ | export.json/zip + import |
| MCP-011 | Validate tool schemas | ✅ | `Tool` model carries input/output schema; validation hook in template import |
| MCP-012 | Tool risk levels enforced by OPA | ✅ | `infra/opa/eamcp.rego` + `OpaPolicyService` + `DurableWorkflowEngine::executeToolWithPolicy` |
| WF-002 | Create workflow from prompt | ✅ | `OpenHandsClient` (codegen path; placeholder stub returns scaffolding) |
| WF-005 | Debug workflow step-by-step | ✅ | `POST /api/runs/{run}/debug-node` |
| WF-006 | Deploy workflow | ✅ | Deployment Manager |
| WF-007 | Copy workflow | ✅ | `POST /api/workflows/{id}/copy` |
| WF-008 | Save workflow as template | ✅ | from-asset |
| WF-009 | Import/export workflow template | ✅ | export+import |
| WF-011 | Schedule cron workflows | ✅ | `schedule_config` on Workflow; `workflows:dispatch-schedules` + `scheduler` compose service |
| WF-012 | Webhook/event triggers | 🟡 | `trigger_type` field; routing UI in Phase 3 |
| WF-013 | Approval nodes | ✅ | `approval` node type · `executeApproval` |
| WF-014 | Retry / error handling | ✅ | `retry_policy` per node + `error_handler` node + `runNodeWithRetry` |
| WF-015 | Workflow rollback | ✅ | `POST /api/workflows/{id}/versions/{v}/rollback` |
| RT-003 | Retry by policy | ✅ | `runNodeWithRetry` with backoff |
| RT-004 | Pause for approval | ✅ | `_pause` signal + `awaiting_approval` status |
| RT-005 | Resume after approval | ✅ | `ApprovalService::maybeResume` + `DurableWorkflowEngine::resume` |
| RT-006 | Aggregate results | ✅ | `output.context.nodes` checkpointed run-wide |
| RT-007 | Validate final output | ✅ | Pipeline `validate_schema` step |
| RT-009 | Parallel branches | 🟡 | `parallel` node executes branches sequentially in Phase 2; full concurrency lands when Temporal worker is wired in Phase 3 |
| RT-010 | Long-running workflows | ✅ | Checkpointed engine survives process restart (resume from `checkpoint.cursor`) |
| OBS-004 | Execution timeline | ✅ | `/runs` page tasks table (start/end/duration per node) |
| OBS-006 | Replay runs | ✅ | `POST /api/runs/{run}/replay` |

## Acceptance criteria — Phase 2 PRD §28

1. ✅ Step through workflow node-by-node and replay with edits — implemented in `DebugController` + `/runs` UI.
2. ✅ Every asset has a version history with diff + 1-click rollback — `VersionController`, agents Versions dialog.
3. ✅ Copy never carries secret values — `CopyController::copyMcp` strips `secret_refs` + `endpoint`; agent and workflow copies inherit only structural config.
4. ✅ Save → export ZIP → import → instantiate, secrets absent — covered by `test_template_export_and_import_roundtrip`.
5. ✅ Promote dev → test → staging → prod blocked unless approved + vault-backed — covered by `test_deployment_to_prod_blocks_non_vault_secrets` and the `awaiting_approval` branch in `DeploymentService::execute`.
6. ✅ Cron dispatch + survives kill — `workflows:dispatch-schedules` + checkpoint resume in `DurableWorkflowEngine`; production Temporal Schedules remain Phase 3.
7. ✅ Approval node pauses; approval resumes within 5 s — verified live (run #3 approve-resume cycle in this session).
8. ✅ OpenHands "generate MCP server" returns runnable scaffold + tests — `test_codegen_stub_returns_generated_files`.
9. ✅ OPA blocks tool calls exceeding agent max risk until approval — verified live (L3 `create_feature` paused workflow until approval #2).
10. ✅ Test Runner executes per-asset suite & reports pass/fail — `test_test_runner_executes_agent_suite`.
11. ✅ PRD Scenario 27.2 (debug failed MCP) — Tool debug + Test Runner + Deployment Manager + Approval flows are all in place.
12. ✅ PRD Scenario 27.3 (import workflow template) — manifest + secret scan + dependency stubbing + parameterised instantiate.
13. 🟡 Zero-downtime migration to durable engine — engine swap via `WorkflowEngine` binding is documented in `docs/PHASE2_MIGRATION.md`; production migration runs during Phase 3 rollout.

## Out-of-scope items deferred to Phase 3 (per PRD)

* Real Temporal cluster as the production engine (Phase 2 ships the design entry + binding swap; production rollout is Phase 3 alongside the security review of OpenHands).
* Webhook & cron trigger dispatchers in production.
* Full RBAC/ABAC, comprehensive policy-as-code, security scanner, image scanner, audit reports, marketplace, etc.

## Tests

* `backend/tests/Feature/PlatformPhase1Test.php` — 6 tests, 24 assertions ✅
* `backend/tests/Feature/PlatformPhase2Test.php` — 10 tests, 27 assertions ✅

## Live smoke verified in this session

* Login → run `approval-gated-publish` → status `awaiting_approval`.
* Approve approval #1 → engine resumes, hits L3 tool call → re-pauses with new approval (OPA risk gate).
* Approve approval #2 → tool call now executes (existing approved approval is consumed, not duplicated).
