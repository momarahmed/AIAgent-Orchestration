# Phase 1: Core Platform Foundation

## Objective
Build the minimum enterprise platform foundation required to support AI agents, MCP servers, workflows, tenants, users, asset registries, and simple workflow execution.

This phase establishes the core product shell and technical backbone so later phases can add full lifecycle management, governance, integrations, marketplace capabilities, and scale.

## Scope
This phase includes:

- Web application foundation.
- Tenant, project, and user access foundation.
- Core asset model for agents, MCP servers, workflows, and templates.
- Basic Agent Studio.
- Basic MCP Studio.
- Basic visual Workflow Builder.
- Basic Chat UI.
- Initial Platform Intent Router.
- Initial Agent Runtime prototype.
- Initial MCP Gateway prototype.
- Basic runtime executor for simple workflows.
- Run history and tool-call logging.
- Local development and container-ready deployment foundation.

This phase does not include full production-grade lifecycle operations, advanced governance, marketplace, external A2A federation, advanced scaling, or full enterprise connector library.

## Key Requirements

### Functional Requirements

| Requirement ID | Requirement | Priority | Phase 1 Scope |
|---|---|---:|---|
| UX-001 | User can access platform via web UI | Must | Build core web shell. |
| UX-002 | User can write natural language prompt | Must | Provide Chat UI prompt entry and response panel. |
| UX-003 | User can switch between chat, workflow builder, agent studio, and MCP studio | Must | Implement main navigation and workspace layout. |
| UX-004 | User can view run history and execution status | Must | Provide basic run list and status view. |
| AG-001 | Create agent from prompt | Must | Create draft agent specification from user prompt. |
| AG-003 | Update agent configuration | Must | Support basic draft edits. |
| AG-004 | Delete/archive agent | Must | Support archive/delete with basic confirmation. |
| MCP-001 | Create MCP server from prompt | Must | Create draft MCP server record and manifest shell. |
| MCP-005 | Debug MCP server tools | Must | Provide only basic test-call placeholder or simple tool-call test for registered servers. |
| MCP-013 | Register tools into Tool Registry | Must | Implement initial tool registry model. |
| WF-001 | Create workflow visually | Must | Use node/edge visual builder. |
| WF-002 | Create workflow from prompt | Must | Generate basic workflow draft from prompt. |
| WF-010 | Execute workflow manually | Must | Execute simple workflow runs. |
| RT-001 | Execute agent workflows reliably | Must | Run simple workflows with basic state. |
| RT-006 | Aggregate results | Must | Combine outputs from simple agent/tool steps. |
| RT-008 | Generate final response/report/ticket/email | Must | Generate final chat response; external report/ticket/email can be stubbed until later phases. |
| OBS-001 | Capture logs for every agent/tool/workflow run | Must | Store run logs and tool-call records. |

### Non-Functional Requirements

| Category | Requirement | Phase 1 Interpretation |
|---|---|---|
| Availability | Production target begins at 99.5%+ in MVP | Phase 1 must be stable for dev/demo usage, not final HA. |
| Performance | UI actions should respond within 2 seconds for normal operations | Apply to navigation, form save, list pages, and simple workflow start. |
| Reliability | Workflow state must survive process restart | Store run state in PostgreSQL for basic runs. |
| Maintainability | Assets must be versionable and rollbackable | Establish schema foundations; full versioning lands in Phase 2. |
| Extensibility | New MCP servers, agents, nodes, templates can be added without core rewrite | Use modular registries and clear asset schemas. |
| Portability | Support local, Docker Compose, Kubernetes, and cloud deployments | Phase 1 must provide Docker Compose for dev and Kubernetes-ready structure. |

## User Stories / Use Cases

### User Story 1: Business User Runs a Simple Prompt
As a business user, I want to enter a prompt in the Chat UI so that the platform can classify my request and return a simple response or workflow result.

Acceptance intent:

- User enters a prompt.
- Platform Intent Router classifies the prompt as execution or asset-management intent.
- A simple response or workflow run is created.
- User sees output and run status.

### User Story 2: Builder Creates a Basic Agent
As an automation builder, I want to create a draft agent from a prompt so that I can define its name, role, instructions, model, and allowed tools later.

Acceptance intent:

- User creates an agent from prompt or form.
- Agent draft is stored in PostgreSQL.
- Agent appears in Agent Studio.
- Agent has status `draft`.

### User Story 3: Builder Creates a Basic MCP Server Record
As an MCP developer, I want to register or draft an MCP server so that the platform can start cataloging its tools and metadata.

Acceptance intent:

- MCP server draft includes name, description, runtime, transport, endpoint/command, and status.
- Tool Registry can store basic tool metadata.
- MCP Studio can list the server.

### User Story 4: Builder Draws a Simple Workflow
As an automation builder, I want to draw a workflow with trigger, agent, MCP tool, and final response nodes so that I can test basic visual orchestration.

Acceptance intent:

- User adds nodes and edges in React Flow.
- Workflow graph is stored as JSON.
- Workflow can be manually run.
- Execution produces basic run logs.

## Features

### 1. User Experience Foundation

| Feature | Description | Technology Mapping |
|---|---|---|
| Platform shell | Main layout, navigation, workspace, project selector | Next.js, React, TypeScript, TailwindCSS, shadcn/ui |
| Chat UI | Prompt entry, response rendering, run status | Next.js + WebSocket/SSE-ready interface |
| Basic Workflow Builder | Visual node/edge editor | React Flow |
| Agent Studio MVP | List/create/update/archive draft agents | Next.js + FastAPI |
| MCP Studio MVP | List/register/create draft MCP servers | Next.js + FastAPI |
| Template Library placeholder | Basic template list and draft records | Next.js + PostgreSQL |
| Execution Monitor MVP | Run history, run status, simple logs | Next.js + FastAPI |

### 2. API / Access / Tenant Foundation

| Module | Phase 1 Requirement |
|---|---|
| API Gateway | Provide main REST API entrypoint using FastAPI. |
| Auth Service | Integrate Keycloak/OIDC baseline or development auth mode. |
| Tenant Service | Create tenant, project, environment data model. |
| RBAC Service | Implement baseline roles: Viewer, Runner, Builder, Admin. |
| WebSocket Gateway | Prepare architecture for real-time run updates; basic polling is acceptable in Phase 1. |

### 3. Platform Intent Router MVP

The router must classify prompts into these initial categories:

| Intent Type | Example |
|---|---|
| Business Execution | “Run GIS health check workflow.” |
| Agent Lifecycle | “Create a new GIS agent.” |
| MCP Lifecycle | “Register ArcGIS MCP server.” |
| Workflow Lifecycle | “Create workflow from this prompt.” |
| Template Lifecycle | “Show templates.” |
| Monitoring / Audit | “Show failed runs.” |

### 4. Asset Registry Foundation

Core entities to create in PostgreSQL:

| Entity | Purpose |
|---|---|
| Tenant | Organization/account boundary. |
| Project | Workspace or solution boundary. |
| User | Platform user. |
| Role | Platform role. |
| Agent | Agent specification. |
| MCPServer | MCP server specification. |
| MCPTool | Tool metadata and schema reference. |
| Workflow | Visual workflow graph. |
| WorkflowRun | Runtime workflow execution. |
| TaskRun | Runtime node/task execution. |
| ToolCall | MCP tool invocation record. |
| Template | Reusable package placeholder. |
| AuditEvent | Security/audit log event. |

### 5. Agent Runtime Prototype

| Component | Requirement | Technology |
|---|---|---|
| Supervisor Agent prototype | Coordinates a simple workflow run | LangGraph |
| Planner Agent prototype | Converts prompt into basic task graph | LangGraph |
| Router Agent prototype | Selects available draft agents/tools | LangGraph |
| Model adapter baseline | Route to configured model provider | Custom adapter abstraction |

### 6. MCP Gateway Prototype

| Component | Requirement | Technology |
|---|---|---|
| MCP Gateway MVP | Controlled access layer for MCP tools | FastAPI + MCP Python SDK |
| MCP Client Manager MVP | Connect to registered MCP server sessions where possible | MCP Python SDK |
| Tool Registry MVP | Store tool metadata and schema references | PostgreSQL |
| Tool Execution Log MVP | Store tool-call records | PostgreSQL |

### 7. Initial Technology Stack for Phase 1

| Layer | Selected Technology |
|---|---|
| Frontend | Next.js + React + TypeScript + TailwindCSS + shadcn/ui |
| Visual Builder | React Flow |
| Backend API | FastAPI |
| Database | PostgreSQL |
| Cache/session | Redis basic setup |
| Vector store | Qdrant installed but optional in Phase 1 flows |
| Agent runtime | LangGraph prototype |
| MCP layer | MCP Python SDK prototype |
| Identity | Keycloak baseline or dev mode |
| Observability | Basic OpenTelemetry instrumentation and structured logs |
| Deployment | Docker Compose for development, Kubernetes-ready folder structure |

## Deliverables

| Deliverable | Description |
|---|---|
| Next.js platform shell | Navigation, workspace, tenants/projects, core pages. |
| Chat UI MVP | User prompt input and result display. |
| React Flow workflow builder MVP | Visual creation of simple workflows. |
| FastAPI backend | Core API service and modular routers. |
| Auth and tenant baseline | Keycloak/OIDC-ready identity and tenant/project model. |
| Asset registry | PostgreSQL schema for agents, MCP servers, workflows, templates, runs, tool calls. |
| Basic Agent Studio | Create/read/update/archive basic agent drafts. |
| Basic MCP Studio | Create/register/read MCP server drafts and initial tools. |
| Basic Workflow Studio | Create/read/update/delete workflow graph drafts. |
| Runtime executor MVP | Execute simple manual workflow. |
| MCP Gateway prototype | Initial controlled tool-access layer. |
| Run history and logs | Store and display basic workflow/tool-call logs. |
| Docker Compose dev environment | Local development stack for UI, API, PostgreSQL, Redis, Qdrant, and supporting services. |
| Initial documentation | README, setup guide, architecture overview. |

## Dependencies

| Dependency | Details |
|---|---|
| Product architecture | Uses PRD v1.1 Technology-Mapped Edition as source baseline. |
| Frontend framework | Next.js, React, TypeScript, TailwindCSS, shadcn/ui. |
| Workflow UI | React Flow. |
| Backend | FastAPI and Python runtime. |
| Database | PostgreSQL. |
| Identity | Keycloak or OIDC-compatible identity provider. |
| Agent runtime | LangGraph prototype. |
| MCP runtime | MCP Python SDK. |
| Local infrastructure | Docker Compose. |
| Development team | Frontend, backend/API, database, AI/agent engineer, DevOps. |

## Acceptance Criteria

Phase 1 is complete when:

- Users can log in or use approved development authentication.
- Users can select or create a tenant/project workspace.
- Users can access Chat UI, Agent Studio, MCP Studio, Workflow Builder, Template Library placeholder, and Execution Monitor.
- Users can create, view, update, and archive draft agents.
- Users can create/register, view, update, and archive draft MCP servers.
- Users can create, view, update, and archive visual workflow drafts.
- Users can run a simple workflow manually.
- A workflow run creates persistent records for run status, node/task status, and tool-call logs.
- The MCP Gateway prototype can register or reference MCP tools from at least one sample MCP server.
- Basic structured logs are captured for user prompt, intent classification, workflow run, and tool call.
- The local Docker Compose environment starts successfully.
- The codebase is organized so Phase 2 lifecycle features can be added without major refactor.

## Notes

- Phase 1 is the foundation phase, not the final production platform.
- Full create/update/delete/debug/deploy/copy lifecycle is completed in Phase 2.
- Enterprise-grade OPA policy, Vault integration, sandboxing, and security scanning are completed in Phase 3.
- React Flow is used only for authoring workflow graphs; execution logic belongs to the backend runtime.
- MCP is used for agent-to-tool access. Agent-to-agent communication will be expanded in later phases.
