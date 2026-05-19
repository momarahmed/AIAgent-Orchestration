# Phase 2: Lifecycle Management and Runtime Reliability

## Objective
Complete the full lifecycle management platform for agents, MCP servers, workflows, and templates, and introduce reliable execution capabilities including debug, versioning, copy, deployment, rollback, tests, approvals, and durable workflow runtime.

This phase transforms Phase 1 from a foundation/MVP into a usable builder platform where assets can move safely from draft to dev/test/prod.

## Scope
This phase includes:

- Full agent lifecycle: create, update, delete/archive, debug, deploy, copy, template, import/export, version, rollback.
- Full MCP server lifecycle: create, update, delete/archive, debug, deploy, copy, template, import/export, version, rollback.
- Full workflow lifecycle: create, update, delete/archive, debug, deploy, copy, template, import/export, version, rollback, run.
- Template import/export as JSON/YAML/ZIP packages.
- Version Manager.
- Debug Console.
- Test Runner.
- Deployment Manager for dev/test/prod promotion.
- Approval Queue for risky lifecycle and runtime actions.
- Durable Workflow Engine integration.
- QA/Test Agent.
- Code/DevOps Agent support using OpenHands Software Agent SDK.
- Initial OPA and Vault/key vault integration for lifecycle controls.
- Provider adapters for OpenAI and Claude where needed.
- Activepieces bridge as optional integration accelerator.

## Key Requirements

### Functional Requirements

| Requirement ID | Requirement | Priority | Phase 2 Scope |
|---|---|---:|---|
| UX-005 | User can debug failed runs from UI | Must | Build Debug Console with step traces and replay metadata. |
| UX-006 | User can import/export templates | Must | Support JSON/YAML/ZIP packages. |
| UX-008 | User can compare asset versions | Should | Implement version diff for agents, MCP servers, and workflows. |
| AG-002 | Create agent from template | Must | Instantiate agent from template package. |
| AG-005 | Debug agent with test prompts | Must | Inspect prompt, model response, tool calls, outputs, and errors. |
| AG-006 | Deploy agent to environment | Must | Promote to dev/test/prod with tests and approvals. |
| AG-007 | Copy agent | Must | Clone with new ID and editable metadata. |
| AG-008 | Save agent as template | Must | Remove secrets and generalize parameters. |
| AG-009 | Import/export agent template | Must | Validate, scan, and package. |
| AG-014 | Evaluate agent using test suite | Should | QA/Test Agent generates and runs tests. |
| MCP-002 | Create MCP server from template | Must | Instantiate server package. |
| MCP-003 | Generate tool schemas | Must | Generate or import tool schemas. |
| MCP-004 | Add/update/delete tools/resources/prompts | Must | Version server and rerun tests. |
| MCP-005 | Debug MCP server tools | Must | Inspect input/output, schema validation, protocol errors. |
| MCP-006 | Deploy MCP server to environment | Must | Health check passes and registry updates. |
| MCP-007 | Copy MCP server | Must | Clone with new ID and environment-specific secrets. |
| MCP-008 | Save MCP server as template | Must | Remove credentials and parameterize endpoints. |
| MCP-009 | Import/export MCP server template | Must | Support package validation and security checks. |
| MCP-011 | Validate tool input/output schemas | Must | Enforce schemas before execution. |
| MCP-014 | Run MCP server health checks | Must | Check deploy readiness and runtime availability. |
| WF-005 | Debug workflow step-by-step | Must | Node-level trace and output inspection. |
| WF-006 | Deploy workflow to environment | Must | Validation, tests, approvals. |
| WF-007 | Copy workflow | Must | Clone graph and metadata. |
| WF-008 | Save workflow as template | Must | Parameterize inputs and remove secrets. |
| WF-009 | Import/export workflow template | Must | Required agents/MCP servers mapped or created. |
| WF-011 | Execute workflow on schedule | Must | Introduce scheduler. |
| WF-013 | Support approval nodes | Must | Human approval queue. |
| WF-014 | Support retry/error handling | Must | Per-node retry/fallback path. |
| WF-015 | Support workflow version rollback | Must | Runtime uses selected approved version. |
| RT-002 | Persist workflow state | Must | Durable state via Temporal and database. |
| RT-003 | Retry failed steps by policy | Must | Retry engine and workflow retries. |
| RT-004 | Pause for human approval | Must | Approval Queue. |
| RT-005 | Resume workflow after approval | Must | Runtime resumes after approve/reject. |
| RT-010 | Support long-running workflows | Must | Temporal workflows. |
| OBS-006 | Support replay of workflow runs | Must | Replay metadata and run history. |

### Core Lifecycle Requirements

#### Agent Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create Agent | User can create agent from prompt, form, or template | Agent draft is created with name, role, instructions, model, tools, memory, policies. |
| Update Agent | User can update all editable fields | New version is created; previous version preserved. |
| Delete Agent | User can archive/delete agent | Platform checks dependencies before deletion. |
| Debug Agent | User can run test prompts and inspect tool calls | Debug trace shows prompt, model response, tool calls, outputs, errors. |
| Deploy Agent | User can deploy to dev/test/prod | Tests and approvals pass before production deploy. |
| Copy Agent | User can clone existing agent | New agent ID created with copied config and editable name. |
| Create Template | User can save agent as template | Secrets removed; parameters generalized. |
| Import Template | User can import agent template | Schema validation and security scan pass. |
| Export Template | User can export agent template | Export includes manifest, config, tests, docs; excludes secrets. |
| Rollback Agent | User can rollback to previous version | Runtime switches to selected approved version. |

#### MCP Server Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create MCP Server | User can generate MCP server from prompt/API/OpenAPI/database schema | Server manifest, tools, resources, prompts, tests created. |
| Update MCP Server | User can modify tools/resources/prompts | New version created; tests rerun. |
| Delete MCP Server | User can archive/delete server | Dependency scan identifies affected agents/workflows. |
| Debug MCP Server | User can test tools and inspect protocol messages | Tool input/output, schema validation, errors visible. |
| Deploy MCP Server | User can deploy server locally, containerized, or remote | Health check passes; tool registry updated. |
| Copy MCP Server | User can clone MCP server | New server ID and environment-specific secrets required. |
| Create Template | User can save MCP server as template | Secrets removed; endpoints parameterized. |
| Import Template | User can import MCP package/template | Package signature, schema, dependency, and security checks run. |
| Export Template | User can export MCP package | Includes manifest, schema, tests, docs; excludes secrets. |
| Rollback MCP Server | User can rollback to approved version | Tool registry points to previous version. |

#### Workflow Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create Workflow | User can draw or generate workflow | Workflow graph created with nodes/edges/variables. |
| Update Workflow | User can modify graph, nodes, variables, policies | New version is created. |
| Delete Workflow | User can archive/delete workflow | Dependencies and schedules checked. |
| Debug Workflow | User can step through workflow | Node-level traces and outputs visible. |
| Deploy Workflow | User can promote to dev/test/prod | Validation, tests, approvals pass. |
| Copy Workflow | User can clone workflow | New workflow ID and editable metadata created. |
| Create Template | User can save workflow as template | Inputs parameterized; secrets removed. |
| Import Template | User can import workflow template | Required agents/MCP servers mapped or created. |
| Export Template | User can export workflow package | Includes graph, configs, tests, docs; excludes secrets. |
| Rollback Workflow | User can rollback version | Scheduler/routing uses selected version. |

## User Stories / Use Cases

### User Story 1: Debug a Failed MCP Server
As an MCP developer, I want to debug a failed MCP server tool call so that I can understand the input, output, schema validation issue, protocol error, or runtime exception.

Flow:

1. User opens MCP Studio.
2. User selects failed MCP server.
3. User opens Debug Console.
4. Platform displays tool input, output, logs, schema validation result, and error message.
5. User edits tool configuration or server code.
6. Test Runner reruns selected tool tests.
7. User deploys fixed version to dev/test.

### User Story 2: Copy and Deploy a Workflow
As an automation builder, I want to copy an existing workflow and deploy it to staging so that I can adapt it for another department without changing the original.

Flow:

1. User selects workflow.
2. User clicks Copy.
3. Platform creates a new workflow ID with copied graph and metadata.
4. User edits nodes, variables, and approval rules.
5. User runs dry-run/debug.
6. Deployment Manager promotes the workflow to staging.
7. Version Manager records deployment version.

### User Story 3: Create Agent Template
As an AI Agent Designer, I want to save a working agent as a reusable template so other teams can instantiate it with their own secrets, endpoints, and policies.

Flow:

1. User selects production-ready agent.
2. User creates template.
3. System removes secrets and parameterizes environment-specific values.
4. System packages manifest, config, tests, and documentation.
5. Template appears in Template Library.

### User Story 4: Human Approval for High-Risk Deployment
As a security approver, I want to approve or reject production deployment of an MCP server so risky tools cannot be introduced without review.

Flow:

1. Builder requests production deployment.
2. Policy engine checks deployment risk.
3. Approval request is created.
4. Security approver reviews diff, tools, risk level, tests, and scan results.
5. Approver approves or rejects.
6. Deployment Manager continues or stops the deployment.

## Features

### 1. Debug Console

| Feature | Description |
|---|---|
| Agent debugging | Test prompts, inspect model responses, tool calls, outputs, errors. |
| MCP debugging | Test tools, inspect protocol messages, validate schemas, view errors. |
| Workflow debugging | Step through nodes, inspect node-level inputs/outputs, retry/fallback paths. |
| Replay | Replay previous workflow runs using stored metadata. |
| Trace viewer | Visualize user prompt → intent router → agents → MCP tools → result. |

### 2. Version Manager

| Feature | Description |
|---|---|
| Asset versions | Agents, MCP servers, workflows, and templates have version history. |
| Diff viewer | Compare versions for configuration, prompts, tools, graph changes. |
| Rollback | Restore approved prior version. |
| Environment status | Track draft, dev, test, staging, production, archived. |
| Audit trail | Record who changed what and when. |

### 3. Deployment Manager

| Feature | Description |
|---|---|
| Dev/test/prod promotion | Promote approved assets across environments. |
| Pre-deploy checks | Schema validation, tests, policy checks, secrets check. |
| Health checks | Validate deployed agents/MCP servers/workflows. |
| Rollback support | Return to previous approved version. |
| Deployment records | Store deployment status and logs. |

### 4. Test Runner and QA/Test Agent

| Feature | Description |
|---|---|
| Unit tests | Validate agent prompts, MCP tools, workflow nodes. |
| Integration tests | Validate agent-to-MCP and workflow execution. |
| Security tests | Check risk level, secret leakage, blocked actions. |
| Simulation tests | Run workflows with mock data. |
| Generated tests | QA/Test Agent generates tests from asset specs. |

### 5. Template Import/Export

| Template Type | Phase 2 Support |
|---|---|
| Agent Template | Create, import, export, instantiate. |
| MCP Server Template | Create, import, export, instantiate. |
| Workflow Template | Create, import, export, instantiate. |
| Prompt Template | Store and reuse. |
| Deployment Template | Basic packaging support. |

Template package structure:

```text
/template-package
  manifest.yaml
  agents/
  mcp-servers/
  workflows/
  policies/
  tests/
  docs/
  assets/
```

### 6. Runtime Reliability

| Component | Description | Technology Mapping |
|---|---|---|
| Workflow Engine | Long-running workflows, retries, signals, timers, schedules | Temporal |
| Agent Runtime | Stateful multi-agent execution | LangGraph |
| Task Queue | Async jobs and workers | Temporal queues / Redis / NATS depending implementation |
| State Store | Workflow/task status, checkpoints, outputs | PostgreSQL + Temporal state |
| Retry Engine | Retry failed nodes by policy | Temporal + workflow policies |
| Human Approval Queue | Pause/resume after approval | FastAPI + PostgreSQL + runtime hooks |

### 7. APIs Delivered in Phase 2

Agent APIs:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/agents/{id}/copy | Copy agent |
| POST | /api/agents/{id}/debug | Debug agent |
| POST | /api/agents/{id}/deploy | Deploy agent |
| POST | /api/agents/{id}/rollback | Rollback agent |
| POST | /api/agents/{id}/template | Create template from agent |
| GET | /api/agents/{id}/versions | List versions |
| GET | /api/agents/{id}/runs | List agent runs |

MCP Server APIs:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/mcp-servers/{id}/copy | Copy MCP server |
| POST | /api/mcp-servers/{id}/debug | Debug MCP server |
| POST | /api/mcp-servers/{id}/deploy | Deploy MCP server |
| POST | /api/mcp-servers/{id}/rollback | Rollback MCP server |
| POST | /api/mcp-servers/{id}/template | Create template from MCP server |
| GET | /api/mcp-servers/{id}/tools | List tools |
| POST | /api/mcp-servers/{id}/tools/test | Test tool call |

Workflow APIs:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/workflows/{id}/copy | Copy workflow |
| POST | /api/workflows/{id}/debug | Debug workflow |
| POST | /api/workflows/{id}/deploy | Deploy workflow |
| POST | /api/workflows/{id}/rollback | Rollback workflow |
| POST | /api/workflows/{id}/run | Run workflow |
| POST | /api/workflows/{id}/template | Create template from workflow |
| GET | /api/workflows/{id}/versions | List versions |
| GET | /api/workflows/{id}/runs | List workflow runs |

Template APIs:

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/templates | Create template |
| GET | /api/templates | List templates |
| GET | /api/templates/{id} | Get template |
| PUT | /api/templates/{id} | Update template |
| DELETE | /api/templates/{id} | Delete/archive template |
| POST | /api/templates/{id}/copy | Copy template |
| POST | /api/templates/import | Import template |
| GET | /api/templates/{id}/export | Export template |
| POST | /api/templates/{id}/instantiate | Create asset from template |

Runtime APIs:

| Method | Endpoint | Purpose |
|---|---|---|
| GET | /api/runs/{id}/trace | Get trace |
| POST | /api/runs/{id}/retry | Retry failed run |
| POST | /api/runs/{id}/replay | Replay run |
| POST | /api/approvals/{id}/approve | Approve action |
| POST | /api/approvals/{id}/reject | Reject action |

## Deliverables

| Deliverable | Description | Technology |
|---|---|---|
| Agent Studio lifecycle | Full lifecycle for agents | FastAPI + PostgreSQL + LangGraph |
| MCP Studio lifecycle | Full lifecycle for MCP servers | MCP Python SDK + OpenHands SDK |
| Workflow lifecycle | Full lifecycle for workflows | React Flow + FastAPI + Temporal |
| Template Library lifecycle | Create/import/export/copy templates | Object storage + PostgreSQL |
| Debug Console | Step trace, tool inspection, replay | OpenTelemetry traces + run logs |
| Version Manager | Version, diff, rollback | PostgreSQL + object storage + optional Git |
| Deployment Manager | Dev/test/prod promotion | CI/CD-ready pipeline hooks |
| Test Runner | Unit/integration/security/simulation tests | Pytest + custom runners + OpenHands SDK |
| QA/Test Agent | Generate and run tests | LangGraph + OpenHands SDK |
| Approval Queue | Human approvals for risky actions | FastAPI + PostgreSQL |
| Temporal integration | Durable workflows, retries, schedules | Temporal |
| Secrets integration baseline | Vault/key vault references | Vault or cloud key vault |
| Policy enforcement baseline | Initial OPA checks | Open Policy Agent |
| Provider adapters | OpenAI/Claude-specific wrappers if needed | OpenAI Agents SDK, Claude Agent SDK |
| Activepieces bridge option | Connector-rich automation bridge | Activepieces API + MCP wrapper |

## Dependencies

| Dependency | Details |
|---|---|
| Phase 1 platform foundation | Required UI shell, API, database, registries, and simple runtime. |
| PostgreSQL asset registry | Must support version, deployment, template, approval, and audit records. |
| Object storage | Required for template packages, exported ZIPs, generated artifacts, test outputs. |
| Temporal | Required for durable runtime, schedules, retries, and long-running workflows. |
| LangGraph | Required for stateful agent orchestration. |
| MCP Python SDK | Required for MCP gateway/server lifecycle. |
| OpenHands Software Agent SDK | Required for code/devops agent acceleration and MCP code generation. |
| OPA | Required for initial policy enforcement. |
| Vault/key vault | Required for secret references. |
| CI/CD platform | Required for deployment pipelines. |

## Acceptance Criteria

Phase 2 is complete when:

- Users can create, update, delete/archive, debug, deploy, copy, template, import/export, version, and rollback agents.
- Users can create, update, delete/archive, debug, deploy, copy, template, import/export, version, and rollback MCP servers.
- Users can create, update, delete/archive, debug, deploy, copy, template, import/export, version, rollback, and run workflows.
- Debug Console displays agent prompts, model responses, MCP tool calls, node inputs/outputs, errors, and replay metadata.
- Version Manager stores immutable versions and supports diff and rollback.
- Deployment Manager supports promotion to dev/test/prod with pre-deploy validation.
- Test Runner runs at least unit and integration tests for agents, MCP servers, and workflows.
- QA/Test Agent can generate tests from asset specifications.
- Human Approval Queue can pause, approve/reject, and resume workflows.
- Temporal is integrated for durable workflow execution and scheduled workflows.
- Template import/export works for JSON/YAML/ZIP packages and excludes raw secrets.
- OPA policy checks and Vault/key vault secret references are available at baseline level.

## Notes

- Phase 2 is where the platform becomes a true asset lifecycle system.
- Production security hardening, sandboxing, scanning, and audit exports are expanded in Phase 3.
- Activepieces is optional in this phase as an accelerator for connector-heavy workflows.
- OpenAI Agents SDK and Claude Agent SDK should be used as provider adapters, not as replacements for LangGraph as the main runtime.
- MCP servers should remain tool providers; orchestration remains in the platform runtime.
