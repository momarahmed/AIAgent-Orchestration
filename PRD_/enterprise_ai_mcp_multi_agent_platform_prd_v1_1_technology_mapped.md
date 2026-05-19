# Product Requirements Document (PRD)

# Enterprise AI + MCP + Multi-Agent + Workflow Builder Platform

**Document Version:** 1.1 — Technology-Mapped Edition  
**Date:** 2026-05-18  
**Prepared For:** Enterprise AI Agent Orchestration Platform Development  
**Primary Objective:** Build a full platform where users can create, update, delete, debug, deploy, copy, template, import, export, and visually orchestrate AI agents, MCP servers, and workflows using a selected enterprise technology stack mapped to each platform capability.

---

## 1. Executive Summary

The product is an enterprise-grade AI Agent Orchestration Platform that allows business users, administrators, GIS operators, developers, and automation teams to design and run intelligent workflows using natural language, visual workflows, AI agents, and Model Context Protocol (MCP) servers.

The platform combines:

- Natural-language chat interface.
- Visual workflow builder similar to Flowise / React Flow style builders.
- Multi-agent orchestration.
- Agent-to-agent communication.
- MCP-based agent-to-tool integration.
- Full lifecycle management for agents.
- Full lifecycle management for MCP servers.
- Full lifecycle management for workflows.
- Template creation, import, export, and reuse.
- Debugging, deployment, versioning, rollback, monitoring, and governance.
- Enterprise security, RBAC/ABAC, audit, observability, and human approval.
- Support for local and cloud LLM models.
- Support for enterprise systems such as ArcGIS Enterprise, databases, cloud platforms, files, browser automation, email, ITSM, monitoring, and DevOps tools.

The final product should act as a **factory for building and operating AI-powered enterprise automation systems**.

---

## 2. Product Vision

Enable any organization to build, operate, and govern AI agents and MCP-powered automation workflows through a unified platform.

The platform should allow a user to write prompts such as:

> Create a GIS Health Monitoring Agent that checks ArcGIS Portal, ArcGIS Server, Data Store, database, and logs, then creates a report and emails it every morning.

The system should transform the prompt into:

1. An agent specification.
2. Required MCP server/tool access.
3. A visual workflow.
4. Test cases.
5. Security policies.
6. Deployment configuration.
7. Production runtime schedule.
8. Monitoring and audit trail.

---

## 3. Problem Statement

Current automation and AI-agent tools are fragmented:

| Problem | Impact |
|---|---|
| Agents are difficult to build and govern | No standard enterprise lifecycle |
| Tools are hard-coded into agents | Poor portability and weak security |
| Workflow builders are separate from agent runtimes | No unified design-to-execution lifecycle |
| MCP servers are created manually | Slow onboarding of enterprise systems |
| Debugging agent/tool workflows is difficult | Hard to trust production automations |
| No strong approval and governance model | Risky automation and compliance gaps |
| No unified template system | Teams repeatedly rebuild the same assets |
| No clear separation between agent-to-agent and agent-to-tool communication | Architecture becomes fragile |

This platform solves those gaps by combining:

- **A2A-style communication** for agent-to-agent collaboration.
- **MCP** for agent-to-tool communication.
- **Visual workflow builder** for human-readable orchestration.
- **Design-time control plane** for creating and managing assets.
- **Runtime control plane** for executing assets.
- **Governance/security/observability plane** for production readiness.

---

## 4. Goals and Non-Goals

### 4.1 Goals

| Goal ID | Goal |
|---|---|
| G-01 | Allow users to create, update, delete, debug, deploy, and copy AI agents. |
| G-02 | Allow users to create, update, delete, debug, deploy, and copy MCP servers. |
| G-03 | Allow users to visually create, update, delete, debug, deploy, and copy workflows. |
| G-04 | Allow users to create, import, export, and reuse templates. |
| G-05 | Support multi-agent orchestration and agent-to-agent communication. |
| G-06 | Support MCP server integration through a controlled MCP Gateway and MCP Client Manager. |
| G-07 | Support enterprise-grade security, RBAC, ABAC, audit, approvals, and policy-as-code. |
| G-08 | Support local and cloud LLM routing. |
| G-09 | Support durable execution, retries, scheduling, human approval, and rollback. |
| G-10 | Provide observability for agents, tools, workflows, prompts, tokens, costs, logs, metrics, and traces. |
| G-11 | Support ArcGIS Enterprise, databases, files, cloud, browser/CUA, email, monitoring, ITSM, and DevOps integrations. |
| G-12 | Provide an extensible architecture for future agents, MCP servers, templates, and marketplaces. |

### 4.2 Non-Goals for MVP

| Non-Goal | Explanation |
|---|---|
| Fully autonomous destructive actions without approval | Delete/admin/remediation actions require approval. |
| Unlimited external plugin execution | All tools must pass validation and governance. |
| Replacing enterprise IAM | The platform integrates with enterprise IAM. |
| Replacing ITSM systems | The platform creates/updates tickets but does not replace ServiceNow/Jira. |
| Replacing full RPA suites in MVP | Browser/CUA automation is included but advanced enterprise RPA parity is later phase. |

---

## 5. Target Users and Personas

| Persona | Description | Primary Needs |
|---|---|---|
| Business User | Non-technical user who writes prompts and runs workflows | Simple chat, templates, approval, results |
| Automation Builder | Builds workflows and agents visually | Workflow builder, templates, debugging |
| MCP Developer | Builds custom MCP servers | MCP Studio, code generation, testing, deployment |
| AI Agent Designer | Creates specialized agents | Agent Studio, prompts, tools, memory, tests |
| GIS Administrator | Operates ArcGIS Enterprise/Online | GIS agents, ArcGIS MCP tools, health workflows |
| Database Administrator | Supports enterprise databases | DB MCP server, safe SQL, monitoring |
| Security Officer | Reviews risk and access | RBAC, approvals, audit, policy-as-code |
| DevOps Engineer | Deploys platform assets | CI/CD, Kubernetes, GitOps, rollback |
| Platform Admin | Manages tenants, users, agents, MCP servers, workflows | Governance, monitoring, permissions |

---

## 6. Definitions and Core Concepts

| Term | Definition |
|---|---|
| Agent | AI-powered worker with role, instructions, model config, memory, permissions, and tool access. |
| Multi-Agent System | Group of agents that collaborate on complex tasks. |
| MCP | Model Context Protocol, used to connect AI applications to external tools, resources, prompts, data, and systems. |
| MCP Server | Server exposing tools, resources, and prompts to AI hosts/clients. |
| MCP Client | Runtime client connection from host/orchestrator to an MCP server. |
| MCP Gateway | Central platform layer that governs, routes, logs, validates, and controls MCP tool access. |
| A2A | Agent-to-Agent communication layer/protocol for agent collaboration. |
| Workflow | Node/edge graph defining agents, tools, tasks, decisions, approvals, and outputs. |
| Template | Reusable packaged definition for agents, MCP servers, or workflows. |
| Design-Time Plane | Area where users create, edit, debug, package, deploy, and template assets. |
| Runtime Plane | Area where workflows and agents execute. |
| Meta-Agent | Agent that creates or manages other agents, MCP servers, workflows, tests, docs, or deployments. |
| Human-in-the-Loop | Approval step before high-risk actions. |

---

## 7. Final Product Scope

The platform must support three main asset types:

1. **Agents**
2. **MCP Servers**
3. **Workflows**

Each asset type must support the following lifecycle operations:

```text
Create
Update
Delete
Debug
Deploy
Copy
Create Template
Import Template
Export Template
Version
Rollback
Audit
Approve
Monitor
```

---

## 8. High-Level Product Capabilities

### 8.1 User Experience Capabilities

| Capability | Description |
|---|---|
| Chat UI | Users create/run/manage assets using natural language. |
| Visual Workflow Builder | Users draw workflows using nodes and edges. |
| Agent Studio | Create and manage agents. |
| MCP Server Studio | Create and manage MCP servers. |
| Template Library | Browse, create, import, export, and reuse templates. |
| Admin Console | Tenants, users, permissions, policies, models, secrets, environments. |
| Execution Monitor | View workflow runs, logs, traces, status, failures, and retries. |
| Debug Console | Step-by-step debugging, replay, test inputs, tool-call inspection. |

### 8.2 Platform Management Capabilities

| Capability | Description |
|---|---|
| Platform Intent Router | Classifies user prompt into business execution or platform management. |
| Agent Builder Agent | Generates/updates/copies/debugs/deploys agents. |
| MCP Builder Agent | Generates/updates/copies/debugs/deploys MCP servers. |
| Workflow Builder Agent | Converts prompt or visual graph into executable workflow. |
| Template Manager Agent | Manages reusable templates. |
| QA/Test Agent | Generates and runs tests. |
| Security Review Agent | Reviews risky tools, permissions, secrets, and policies. |
| DevOps Deployment Agent | Builds containers, deploys, validates, rolls back. |
| Documentation Agent | Generates README, diagrams, API docs, release notes. |

### 8.3 Runtime Capabilities

| Capability | Description |
|---|---|
| Supervisor Agent | Controls execution and coordination. |
| Planner Agent | Breaks user request into tasks. |
| Router Agent | Assigns tasks to specialist agents. |
| Agent Runtime | Executes agents and manages model calls. |
| Workflow Engine | Runs long-running workflows with state and retries. |
| Task Queue | Manages async/background work. |
| State Store | Stores workflow state, run status, outputs, and checkpoints. |
| Approval Queue | Handles human approval for risky actions. |
| Result Aggregator | Combines outputs from agents/tools. |
| Validator Agent | Checks correctness, completeness, safety. |
| Final Formatter | Produces chat answer, report, email, ticket, dashboard update. |

---

## 9. Full Final Architecture

### 9.1 Architecture Overview

```text
User
 ↓
Chat UI / Visual Builder / Agent Studio / MCP Studio / Admin Console
 ↓
API Gateway / Auth / Tenant Management
 ↓
Platform Intent Router
 ↓
Design-Time Control Plane OR Runtime Control Plane
 ↓
Meta-Agent Layer
 ↓
Agent Communication Layer
 ↓
Model Control Plane
 ↓
Memory / Knowledge Layer
 ↓
MCP Tool Control Plane
 ↓
MCP Server Layer
 ↓
Enterprise Systems / Tools
 ↓
Results / Events
 ↓
Aggregator + Validator + Human Approval
 ↓
Final Output
```

### 9.2 Main Architecture Diagram

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│                            USER EXPERIENCE LAYER                             │
│                                                                              │
│  Chat UI | Visual Workflow Builder | Agent Studio | MCP Studio | Admin Console│
│                                                                              │
│  User Actions:                                                               │
│  - Ask prompt                                                                 │
│  - Create/update/delete/debug/deploy/copy agent                               │
│  - Create/update/delete/debug/deploy/copy MCP server                          │
│  - Create/update/delete/debug/deploy/copy workflow                            │
│  - Create/import/export templates                                             │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          API / AUTH / TENANT LAYER                           │
│                                                                              │
│  API Gateway | WebSocket | SSO/OAuth | RBAC/ABAC | Tenant/Project Isolation   │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            PLATFORM INTENT ROUTER                            │
│                                                                              │
│  Classifies request into:                                                     │
│  1. Business execution                                                        │
│  2. Agent lifecycle management                                                │
│  3. MCP server lifecycle management                                           │
│  4. Workflow lifecycle management                                             │
│  5. Template lifecycle management                                             │
└──────────────────────────────┬───────────────────────────────────────────────┘
                               │
        ┌──────────────────────┴──────────────────────┐
        ▼                                             ▼
┌──────────────────────────────────────┐   ┌───────────────────────────────────┐
│         DESIGN-TIME CONTROL PLANE    │   │       RUNTIME CONTROL PLANE       │
│                                      │   │                                   │
│  Agent Studio                        │   │  Supervisor Agent                 │
│  MCP Server Studio                   │   │  Planner Agent                    │
│  Workflow Studio                     │   │  Router Agent                     │
│  Template Library                    │   │  Agent Runtime                    │
│  Import / Export Manager             │   │  Workflow Engine                  │
│  Version Manager                     │   │  Task Queue                       │
│  Debug Console                       │   │  State Store                      │
│  Test Runner                         │   │  Retry Engine                     │
│  Package Builder                     │   │  Human Approval                   │
│  Deployment Manager                  │   │  Result Aggregator                │
│  Security Scanner                    │   │  Validator Agent                  │
│  Approval Manager                    │   │  Final Formatter                  │
└──────────────────────┬───────────────┘   └──────────────────┬────────────────┘
                       │                                      │
                       ▼                                      ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            META-AGENT LAYER                                  │
│                                                                              │
│  Platform Architect Agent | Agent Builder Agent | MCP Builder Agent           │
│  Workflow Builder Agent | Template Manager Agent | QA/Test Agent              │
│  Security Review Agent | DevOps Deployment Agent | Documentation Agent        │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                         AGENT COMMUNICATION LAYER                            │
│                                                                              │
│  Internal Messaging | A2A Gateway | Event Bus | Shared Workflow State         │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            MODEL CONTROL PLANE                               │
│                                                                              │
│  Model Registry | Model Router | Local LLM | Cloud LLM | Fallback             │
│  Cost Control | Token Budget | Prompt Registry | Evaluation Scores            │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                            MEMORY / KNOWLEDGE LAYER                          │
│                                                                              │
│  Short-Term Memory | Long-Term Memory | Vector DB | RAG | Knowledge Graph     │
│  Agent Memory | Workflow State | User/Project Context                         │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                              MCP TOOL CONTROL PLANE                          │
│                                                                              │
│  MCP Gateway | MCP Client Manager | Tool Registry | Resource Registry         │
│  Prompt Registry | Tool Permission Engine | Tool Validator                    │
│  Tool Execution Log | MCP Debug Console | Tool Sandbox                        │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                                MCP SERVER LAYER                              │
│                                                                              │
│  ArcGIS MCP | Database MCP | File/PDF MCP | Browser/CUA MCP                  │
│  Cloud MCP | Email MCP | Monitoring MCP | ITSM MCP | Workflow MCP | DevOps MCP │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                          ENTERPRISE SYSTEMS / TOOLS                          │
│                                                                              │
│  ArcGIS Enterprise / ArcGIS Online / PostgreSQL / SQL Server / Oracle         │
│  Azure / AWS / GCP / Kubernetes / Files / SharePoint / Browser Apps           │
│  Email / Teams / Jira / ServiceNow / Logs / Metrics / SIEM / Git / CI/CD      │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                        GOVERNANCE / SECURITY / OBSERVABILITY                 │
│                                                                              │
│  Policy | Audit | Secrets | Sandbox | Logs | Metrics | Traces | Cost | Replay │
└──────────────────────────────────────┬───────────────────────────────────────┘
                                       ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│                               FINAL OUTPUT                                   │
│                                                                              │
│  Chat Answer | Report | Email | ITSM Ticket | Deployed Agent                 │
│  Deployed MCP Server | Deployed Workflow | Template | Dashboard Update        │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 9.3 Mermaid Diagram

```mermaid
flowchart TD

    A[User Experience Layer<br/>Chat UI / Workflow Builder / Agent Studio / MCP Studio / Admin Console]
    A --> B[API / Auth / Tenant Layer<br/>API Gateway / SSO / RBAC / ABAC / Tenant Isolation]

    B --> C[Platform Intent Router<br/>Business Execution or Platform Management]

    C --> D1[Design-Time Control Plane<br/>Build / Edit / Debug / Package / Deploy]
    C --> D2[Runtime Control Plane<br/>Execute Agents / Workflows / MCP Tools]

    D1 --> E1[Agent Studio<br/>Create Update Delete Debug Deploy Copy Agents]
    D1 --> E2[MCP Server Studio<br/>Create Update Delete Debug Deploy Copy MCP Servers]
    D1 --> E3[Workflow Studio<br/>Visual Create Update Delete Debug Deploy Copy Workflows]
    D1 --> E4[Template Library<br/>Create Import Export Templates]
    D1 --> E5[Version Manager / Test Runner / Package Builder / Deployment Manager]

    D2 --> F1[Supervisor Agent]
    F1 --> F2[Planner Agent]
    F2 --> F3[Router Agent]
    F3 --> F4[Agent Runtime]
    F4 --> F5[Workflow Engine]
    F5 --> F6[Task Queue / Retry Engine / State Store / Approval Queue]

    E1 --> G[Meta-Agent Layer]
    E2 --> G
    E3 --> G
    E4 --> G
    F4 --> G

    G --> G1[Platform Architect Agent]
    G --> G2[Agent Builder Agent]
    G --> G3[MCP Builder Agent]
    G --> G4[Workflow Builder Agent]
    G --> G5[Template Manager Agent]
    G --> G6[QA / Test Agent]
    G --> G7[Security Review Agent]
    G --> G8[DevOps Deployment Agent]
    G --> G9[Documentation Agent]

    G --> H[Agent Communication Layer<br/>Internal Messaging / A2A Gateway / Event Bus / Shared State]

    H --> I[Model Control Plane<br/>Model Registry / Router / Local LLM / Cloud LLM / Cost Control]
    I --> J[Memory and Knowledge Layer<br/>Short-Term / Long-Term / Vector DB / RAG / Knowledge Graph]

    J --> K[MCP Tool Control Plane<br/>MCP Gateway / MCP Client Manager / Tool Registry / Permission Engine / Debug Console]

    K --> L1[ArcGIS MCP Server]
    K --> L2[Database MCP Server]
    K --> L3[File/PDF MCP Server]
    K --> L4[Browser/CUA MCP Server]
    K --> L5[Cloud MCP Server]
    K --> L6[Email MCP Server]
    K --> L7[Monitoring MCP Server]
    K --> L8[ITSM MCP Server]
    K --> L9[Workflow MCP Server]
    K --> L10[Code/DevOps MCP Server]

    L1 --> M1[ArcGIS Enterprise / ArcGIS Online]
    L2 --> M2[PostgreSQL / SQL Server / Oracle]
    L3 --> M3[PDF / DOCX / Excel / SharePoint]
    L4 --> M4[Browser Apps / Legacy Apps]
    L5 --> M5[Azure / AWS / GCP / Kubernetes]
    L6 --> M6[Email / Teams / Outlook / Gmail]
    L7 --> M7[Logs / Metrics / SIEM]
    L8 --> M8[Jira / ServiceNow]
    L9 --> M9[n8n / Activepieces / Temporal]
    L10 --> M10[Git / CI-CD / Containers]

    M1 --> N[Raw Results / Execution Events]
    M2 --> N
    M3 --> N
    M4 --> N
    M5 --> N
    M6 --> N
    M7 --> N
    M8 --> N
    M9 --> N
    M10 --> N

    N --> O[Result Aggregator]
    O --> P[Validator Agent]
    P --> Q[Human Approval if Required]
    Q --> R[Final Formatter]

    R --> S1[Chat Answer]
    R --> S2[Generated Report]
    R --> S3[Email Notification]
    R --> S4[ITSM Ticket]
    R --> S5[Deployed Agent]
    R --> S6[Deployed MCP Server]
    R --> S7[Deployed Workflow]
    R --> S8[Imported / Exported Template]
    R --> S9[Dashboard Update]

    X[Governance / Security / Observability<br/>Policy / Audit / Secrets / Sandbox / Logs / Metrics / Traces] -.-> B
    X -.-> D1
    X -.-> D2
    X -.-> G
    X -.-> K
    X -.-> N
```

---

## 10. System Modules

### 10.1 User Experience Layer

| Module | Description | Key Features |
|---|---|---|
| Chat UI | Natural language interface | Ask, create, update, debug, deploy, run |
| Visual Workflow Builder | Node-based workflow designer | Drag/drop, connect nodes, templates, validation |
| Agent Studio | Agent lifecycle UI | Create/update/delete/debug/deploy/copy agents |
| MCP Studio | MCP server lifecycle UI | Generate tools, test calls, deploy servers |
| Template Library | Reusable asset catalog | Create/import/export/copy templates |
| Admin Console | Platform administration | Users, tenants, models, secrets, policies |
| Execution Monitor | Runtime observability | Runs, logs, traces, replay, status |
| Debug Console | Debug assets | Step-run, inspect tool calls, test cases |

### 10.2 API / Access / Tenant Layer

| Module | Description |
|---|---|
| API Gateway | Main entrypoint for UI, CLI, integrations, webhooks |
| WebSocket Gateway | Real-time updates for workflow execution and logs |
| Auth Service | SSO/OAuth/OIDC integration |
| RBAC/ABAC Service | Role and attribute-based authorization |
| Tenant Service | Multi-tenant isolation by organization/project/environment |
| Rate Limit Service | Prevent abuse and control usage |

### 10.3 Platform Intent Router

The router must classify user requests into one or more categories:

| Intent Type | Example |
|---|---|
| Business Execution | Run GIS health check workflow |
| Agent Lifecycle | Create a new GIS agent |
| MCP Lifecycle | Debug ArcGIS MCP server |
| Workflow Lifecycle | Copy workflow and deploy to staging |
| Template Lifecycle | Import workflow template from ZIP |
| Admin / Governance | Add policy requiring approval for delete actions |
| Monitoring / Audit | Show all failed tool calls today |

### 10.4 Design-Time Control Plane

| Module | Description |
|---|---|
| Agent Studio | Defines and manages agent specifications |
| MCP Server Studio | Defines and manages MCP server specifications/code/packages |
| Workflow Studio | Defines and manages visual workflow specifications |
| Template Library | Stores reusable templates |
| Import/Export Manager | Handles JSON/YAML/ZIP packages |
| Version Manager | Stores all asset versions and supports rollback |
| Debug Console | Debugs agent/MCP/workflow assets |
| Test Runner | Runs unit, integration, security, and simulation tests |
| Package Builder | Creates container/package artifacts |
| Deployment Manager | Deploys assets to dev/test/prod |
| Security Scanner | Scans code, dependencies, images, manifests |
| Approval Manager | Manages approval gates for deploy/delete/risky operations |

### 10.5 Runtime Control Plane

| Module | Description |
|---|---|
| Supervisor Agent | Coordinates the full execution |
| Planner Agent | Converts request into task graph |
| Router Agent | Selects agents/tools/workflows |
| Agent Runtime | Executes specialist and meta-agents |
| Workflow Engine | Durable workflow execution |
| Task Queue | Async execution and worker distribution |
| State Store | Saves workflow and task state |
| Retry Engine | Retries failed nodes based on policy |
| Scheduler | Runs recurring workflows |
| Human Approval Queue | Pauses workflows until approved/rejected |
| Result Aggregator | Combines outputs |
| Validator Agent | Checks output safety/correctness |
| Final Formatter | Produces result in correct channel |

---

## 11. Core Asset Lifecycle Requirements

### 11.1 Agent Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create Agent | User can create agent from prompt, form, or template | Agent draft is created with name, role, instructions, model, tools, memory, policies |
| Update Agent | User can update all editable fields | New version is created; previous version preserved |
| Delete Agent | User can archive/delete agent | Platform checks dependencies before deletion |
| Debug Agent | User can run test prompts and inspect tool calls | Debug trace shows prompt, model response, tool calls, outputs, errors |
| Deploy Agent | User can deploy to dev/test/prod | Tests and approvals pass before production deploy |
| Copy Agent | User can clone existing agent | New agent ID created with copied config and editable name |
| Create Template | User can save agent as template | Secrets removed; parameters generalized |
| Import Template | User can import agent template | Schema validation and security scan pass |
| Export Template | User can export agent template | Export includes manifest, config, tests, docs; excludes secrets |
| Rollback Agent | User can rollback to previous version | Runtime switches to selected approved version |

### 11.2 MCP Server Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create MCP Server | User can generate MCP server from prompt/API/OpenAPI/database schema | Server manifest, tools, resources, prompts, tests created |
| Update MCP Server | User can modify tools/resources/prompts | New version created; tests rerun |
| Delete MCP Server | User can archive/delete server | Dependency scan identifies affected agents/workflows |
| Debug MCP Server | User can test tools and inspect protocol messages | Tool input/output, schema validation, errors visible |
| Deploy MCP Server | User can deploy server locally, containerized, or remote | Health check passes; tool registry updated |
| Copy MCP Server | User can clone MCP server | New server ID and environment-specific secrets required |
| Create Template | User can save MCP server as template | Secrets removed; endpoints parameterized |
| Import Template | User can import MCP package/template | Package signature, schema, dependency, and security checks run |
| Export Template | User can export MCP package | Includes manifest, schema, tests, docs; excludes secrets |
| Rollback MCP Server | User can rollback to approved version | Tool registry points to previous version |

### 11.3 Workflow Lifecycle

| Operation | Requirement | Acceptance Criteria |
|---|---|---|
| Create Workflow | User can draw or generate workflow | Workflow graph created with nodes/edges/variables |
| Update Workflow | User can modify graph, nodes, variables, policies | New version is created |
| Delete Workflow | User can archive/delete workflow | Dependencies and schedules checked |
| Debug Workflow | User can step through workflow | Node-level traces and outputs visible |
| Deploy Workflow | User can promote to dev/test/prod | Validation, tests, approvals pass |
| Copy Workflow | User can clone workflow | New workflow ID and editable metadata created |
| Create Template | User can save workflow as template | Inputs parameterized; secrets removed |
| Import Template | User can import workflow template | Required agents/MCP servers mapped or created |
| Export Template | User can export workflow package | Includes graph, configs, tests, docs; excludes secrets |
| Rollback Workflow | User can rollback version | Scheduler/routing uses selected version |

---

## 12. Agent Types

### 12.1 Runtime Business Agents

| Agent | Purpose |
|---|---|
| GIS Agent | ArcGIS Enterprise/Online operations, maps, services, users, groups, logs |
| Database Agent | SQL/database health, safe queries, schema inspection |
| Browser Agent | Playwright/CUA/browser automation |
| File/Document Agent | PDF, DOCX, Excel, report generation |
| Security Agent | Permissions, risk review, compliance checks |
| Cloud Agent | Azure/AWS/GCP/Kubernetes operations |
| Monitoring Agent | Logs, metrics, alerts, system health |
| Report Agent | Executive/technical report creation |
| Email/Notification Agent | Email, Teams, Slack notifications |
| ITSM Agent | Jira/ServiceNow ticket creation/update |
| DevOps Agent | Git, CI/CD, deployment, rollback |

### 12.2 Meta-Agents

| Meta-Agent | Purpose |
|---|---|
| Platform Architect Agent | Designs complete solutions from prompt |
| Agent Builder Agent | Creates/updates/debugs/deploys/copies agents |
| MCP Builder Agent | Creates/updates/debugs/deploys/copies MCP servers |
| Workflow Builder Agent | Creates/updates/debugs/deploys/copies workflows |
| Template Manager Agent | Creates/imports/exports templates |
| QA/Test Agent | Creates and executes tests |
| Security Review Agent | Performs risk and permission review |
| DevOps Deployment Agent | Packages, deploys, validates, and rolls back assets |
| Documentation Agent | Generates docs, diagrams, changelogs, README files |
| Migration Agent | Converts workflows from n8n/Flowise/Dify/JSON/YAML where possible |
| Governance Agent | Checks compliance with enterprise policies |

---

## 13. Agent Communication Design

### 13.1 Communication Rules

```text
Agents communicate with agents.
Agents use MCP clients to call MCP servers.
MCP servers expose tools/resources/prompts.
MCP servers should not normally orchestrate other MCP servers.
The orchestrator controls the full workflow.
```

### 13.2 Communication Patterns

| Pattern | Use Case |
|---|---|
| Orchestrator-mediated messages | Default enterprise pattern |
| Shared workflow state | Agents share task outputs and checkpoints |
| Event bus | Large-scale asynchronous communication |
| A2A gateway | External/vendor/cross-framework agent communication |
| Direct agent message | Advanced use only, with policy control |

### 13.3 Agent Message Schema

```json
{
  "message_id": "msg_123",
  "conversation_id": "conv_456",
  "workflow_run_id": "run_789",
  "from_agent": "gis_agent",
  "to_agent": "report_agent",
  "message_type": "task_result",
  "priority": "normal",
  "payload": {
    "summary": "ArcGIS Portal healthy; two services stopped",
    "data_ref": "state://run_789/gis_health_result"
  },
  "created_at": "2026-05-18T12:00:00Z"
}
```

---

## 14. MCP Tool Control Plane

### 14.1 Required Components

| Component | Description |
|---|---|
| MCP Gateway | Single controlled access point for MCP tools |
| MCP Client Manager | Manages sessions to multiple MCP servers |
| Tool Registry | Stores tool metadata, schemas, owners, risk levels |
| Resource Registry | Stores MCP resources and access rules |
| Prompt Registry | Stores reusable MCP prompts/templates |
| Tool Permission Engine | Enforces who/what can call each tool |
| Tool Validator | Validates arguments, schemas, policies |
| Tool Execution Log | Stores all tool calls and outputs |
| MCP Debug Console | Allows testing tools and MCP servers |
| Tool Sandbox | Isolates risky tools and command execution |

### 14.2 MCP Server Types

| MCP Server | Example Tools |
|---|---|
| ArcGIS MCP Server | portal_health, list_services, restart_service, check_datastore, query_logs |
| Database MCP Server | check_db_health, inspect_schema, run_safe_query, analyze_slow_queries |
| File/PDF MCP Server | read_pdf, extract_tables, create_report, export_docx, export_pdf |
| Browser/CUA MCP Server | open_page, click_button, fill_form, capture_screen, read_ui_state |
| Cloud MCP Server | list_vms, check_resource_health, inspect_logs, restart_service |
| Email MCP Server | create_draft, send_email, notify_user |
| Monitoring MCP Server | query_logs, get_metrics, analyze_alerts |
| ITSM MCP Server | create_ticket, update_ticket, attach_report |
| Workflow MCP Server | trigger_n8n, trigger_activepieces, run_temporal_workflow |
| DevOps MCP Server | create_branch, run_pipeline, deploy_container, rollback_release |

### 14.3 Tool Risk Levels

| Risk Level | Examples | Required Control |
|---|---|---|
| L0 Read-only | List services, inspect schema | Normal RBAC |
| L1 Low-risk write | Create draft, create report | RBAC + audit |
| L2 Operational write | Restart service, update metadata | Approval based on policy |
| L3 Destructive | Delete item, drop table, remove user | Mandatory approval |
| L4 Admin/system | Execute script, modify IAM, deploy infra | Security approval + sandbox + audit |

---

## 15. Workflow Builder Requirements

### 15.1 Node Types

| Node Type | Description |
|---|---|
| Trigger Node | Manual, chat, webhook, schedule, event |
| Agent Node | Calls an AI agent |
| MCP Tool Node | Calls a specific MCP tool |
| Decision Node | Conditional branching |
| Transform Node | Format, map, filter, convert data |
| Approval Node | Human approval checkpoint |
| Loop Node | Iterate over items |
| Parallel Node | Run branches concurrently |
| Error Handler Node | Retry/fallback path |
| Notification Node | Email/Teams/Slack |
| Report Node | Generate PDF/DOCX/Markdown |
| ITSM Node | Create/update ticket |
| Template Node | Reusable sub-workflow |
| Script Node | Controlled script execution |
| Memory Node | Read/write memory |
| RAG Node | Retrieve documents/context |

### 15.2 Workflow Designer Features

| Feature | Requirement |
|---|---|
| Drag and drop | Users can add nodes visually |
| Connect nodes | Users can define edges and execution flow |
| Node config panel | Users configure inputs, outputs, tool selection, retry, approvals |
| Auto-layout | System can organize graph visually |
| Validation | Detect disconnected nodes, invalid tools, missing inputs |
| Dry-run | Run with mock/test data |
| Debug mode | Step-by-step node execution |
| Replay | Replay previous workflow runs |
| Versioning | Save versions and compare diffs |
| Template save | Save workflow as reusable template |
| Import/export | JSON/YAML/ZIP formats |
| Deploy | Promote to environments |
| Rollback | Return to previous workflow version |
| Execution monitor | Real-time run status and logs |

---

## 16. Template System Requirements

### 16.1 Template Types

| Template Type | Examples |
|---|---|
| Agent Template | GIS Health Agent, Database Audit Agent, Report Agent |
| MCP Server Template | ArcGIS MCP, PostgreSQL MCP, Azure MCP, Email MCP |
| Workflow Template | Daily GIS health report, incident investigation, data quality check |
| Policy Template | Approval for delete actions, read-only role, admin role |
| Prompt Template | Standard report generation prompt, investigation prompt |
| Deployment Template | Kubernetes deployment, Docker Compose, local dev |

### 16.2 Template Package Structure

```text
/template-package
  manifest.yaml
  agents/
    gis-health-agent.yaml
  mcp-servers/
    arcgis-mcp-server.yaml
  workflows/
    daily-health-workflow.yaml
  policies/
    tool-risk-policy.rego
  tests/
    workflow-tests.yaml
  docs/
    README.md
    architecture.md
  assets/
    icons/
```

### 16.3 Template Manifest Example

```yaml
id: template_gis_health_monitoring
name: GIS Health Monitoring Template
version: 1.0.0
type: workflow_bundle
category: GIS Operations
description: Creates agents, MCP tool access, and workflow for ArcGIS health monitoring.
required_connectors:
  - arcgis_mcp_server
  - database_mcp_server
  - email_mcp_server
parameters:
  - name: arcgis_portal_url
    required: true
  - name: admin_email
    required: true
  - name: schedule_cron
    required: false
security:
  requires_secret_mapping: true
  allowed_risk_level: L2
```

---

## 17. Functional Requirements

### 17.1 User Experience

| ID | Requirement | Priority |
|---|---|---|
| UX-001 | User can access platform via web UI | Must |
| UX-002 | User can write natural language prompt | Must |
| UX-003 | User can switch between chat, workflow builder, agent studio, MCP studio | Must |
| UX-004 | User can view run history and execution status | Must |
| UX-005 | User can debug failed runs from UI | Must |
| UX-006 | User can import/export templates | Must |
| UX-007 | User can view audit history per asset | Must |
| UX-008 | User can compare asset versions | Should |
| UX-009 | User can comment on workflows/assets | Could |

### 17.2 Agent Management

| ID | Requirement | Priority |
|---|---|---|
| AG-001 | Create agent from prompt | Must |
| AG-002 | Create agent from template | Must |
| AG-003 | Update agent configuration | Must |
| AG-004 | Delete/archive agent | Must |
| AG-005 | Debug agent with test prompts | Must |
| AG-006 | Deploy agent to environment | Must |
| AG-007 | Copy agent | Must |
| AG-008 | Save agent as template | Must |
| AG-009 | Import/export agent template | Must |
| AG-010 | Assign allowed MCP servers/tools | Must |
| AG-011 | Assign memory scope | Must |
| AG-012 | Assign model and fallback model | Must |
| AG-013 | View agent execution history | Must |
| AG-014 | Evaluate agent using test suite | Should |

### 17.3 MCP Server Management

| ID | Requirement | Priority |
|---|---|---|
| MCP-001 | Create MCP server from prompt | Must |
| MCP-002 | Create MCP server from template | Must |
| MCP-003 | Generate tool schemas | Must |
| MCP-004 | Add/update/delete tools/resources/prompts | Must |
| MCP-005 | Debug MCP server tools | Must |
| MCP-006 | Deploy MCP server to environment | Must |
| MCP-007 | Copy MCP server | Must |
| MCP-008 | Save MCP server as template | Must |
| MCP-009 | Import/export MCP server template | Must |
| MCP-010 | Store secrets only as vault references | Must |
| MCP-011 | Validate tool input/output schemas | Must |
| MCP-012 | Assign tool risk levels | Must |
| MCP-013 | Register tools into Tool Registry | Must |
| MCP-014 | Run MCP server health checks | Must |

### 17.4 Workflow Management

| ID | Requirement | Priority |
|---|---|---|
| WF-001 | Create workflow visually | Must |
| WF-002 | Create workflow from prompt | Must |
| WF-003 | Update workflow nodes/edges/config | Must |
| WF-004 | Delete/archive workflow | Must |
| WF-005 | Debug workflow step-by-step | Must |
| WF-006 | Deploy workflow to environment | Must |
| WF-007 | Copy workflow | Must |
| WF-008 | Save workflow as template | Must |
| WF-009 | Import/export workflow template | Must |
| WF-010 | Execute workflow manually | Must |
| WF-011 | Execute workflow on schedule | Must |
| WF-012 | Execute workflow from webhook/event | Should |
| WF-013 | Support approval nodes | Must |
| WF-014 | Support retry/error handling | Must |
| WF-015 | Support workflow version rollback | Must |

### 17.5 Runtime Execution

| ID | Requirement | Priority |
|---|---|---|
| RT-001 | Execute agent workflows reliably | Must |
| RT-002 | Persist workflow state | Must |
| RT-003 | Retry failed steps by policy | Must |
| RT-004 | Pause for human approval | Must |
| RT-005 | Resume workflow after approval | Must |
| RT-006 | Aggregate results | Must |
| RT-007 | Validate final output | Must |
| RT-008 | Generate final response/report/ticket/email | Must |
| RT-009 | Support parallel branches | Should |
| RT-010 | Support long-running workflows | Must |

### 17.6 Governance and Security

| ID | Requirement | Priority |
|---|---|---|
| SEC-001 | Support SSO/OIDC/OAuth | Must |
| SEC-002 | Support RBAC and ABAC | Must |
| SEC-003 | Enforce tool-level permissions | Must |
| SEC-004 | Require approval for high-risk tools | Must |
| SEC-005 | Store secrets in vault only | Must |
| SEC-006 | Redact secrets from logs/templates | Must |
| SEC-007 | Maintain audit trail for every asset/action | Must |
| SEC-008 | Sandbox risky tools | Must |
| SEC-009 | Enforce network allowlists | Should |
| SEC-010 | Support policy-as-code | Must |
| SEC-011 | Detect prompt injection attempts | Should |
| SEC-012 | Support tenant isolation | Must |

### 17.7 Observability

| ID | Requirement | Priority |
|---|---|---|
| OBS-001 | Capture logs for every agent/tool/workflow run | Must |
| OBS-002 | Capture metrics | Must |
| OBS-003 | Capture traces | Must |
| OBS-004 | Display execution timeline | Must |
| OBS-005 | Track token and model cost | Must |
| OBS-006 | Support replay of workflow runs | Must |
| OBS-007 | Alert on failures | Should |
| OBS-008 | Export logs/traces to external observability stack | Should |

---

## 18. Non-Functional Requirements

| Category | Requirement |
|---|---|
| Availability | Production platform should target 99.5%+ availability in MVP, 99.9%+ later. |
| Scalability | Support horizontal scaling for API, agent workers, MCP gateways, workflow workers. |
| Performance | UI actions should respond within 2 seconds for normal operations. |
| Reliability | Workflow state must survive process restart. |
| Security | Secrets must never be stored in prompts, templates, exports, or logs. |
| Auditability | Every create/update/delete/deploy/tool-call must be auditable. |
| Maintainability | Assets must be versioned and rollbackable. |
| Extensibility | New MCP servers, agents, nodes, templates can be added without core rewrite. |
| Portability | Support local, Docker Compose, Kubernetes, and cloud deployments. |
| Compliance | Support policy, approval, audit, and evidence export. |
| Localization | Architecture should allow future Arabic/English UI. |

---

## 19. Data Model

### 19.1 Main Entities

| Entity | Description |
|---|---|
| Tenant | Organization boundary |
| Project | Workspace inside tenant |
| User | Platform user |
| Role | RBAC role |
| Policy | Authorization and risk rules |
| Agent | AI agent asset |
| AgentVersion | Versioned agent configuration |
| MCPServer | MCP server asset |
| MCPServerVersion | Versioned MCP server configuration |
| Tool | Tool exposed by MCP server |
| Resource | MCP resource exposed by MCP server |
| PromptTemplate | MCP/agent prompt template |
| Workflow | Workflow asset |
| WorkflowVersion | Versioned workflow graph |
| WorkflowRun | Runtime workflow execution |
| TaskRun | Runtime task/node execution |
| ToolCall | MCP tool invocation record |
| Template | Reusable package |
| Deployment | Deployment record |
| ApprovalRequest | Human approval item |
| SecretReference | Reference to vault secret |
| ModelProvider | LLM provider configuration |
| MemoryItem | Stored memory/context |
| AuditEvent | Security/audit log event |

### 19.2 Agent Entity Fields

| Field | Type | Description |
|---|---|---|
| id | UUID | Agent ID |
| tenant_id | UUID | Tenant scope |
| project_id | UUID | Project scope |
| name | string | Agent name |
| description | text | Description |
| role | string | Agent role |
| system_instructions | text | Instructions |
| model_config_id | UUID | Model config |
| allowed_mcp_servers | array | MCP server IDs |
| allowed_tools | array | Tool IDs |
| memory_scope | enum | none/session/project/tenant |
| risk_level | enum | L0-L4 |
| status | enum | draft/staging/production/archived |
| current_version_id | UUID | Active version |
| owner_id | UUID | Owner |
| created_at | timestamp | Created time |
| updated_at | timestamp | Updated time |

### 19.3 MCP Server Entity Fields

| Field | Type | Description |
|---|---|---|
| id | UUID | MCP server ID |
| tenant_id | UUID | Tenant scope |
| project_id | UUID | Project scope |
| name | string | Server name |
| description | text | Description |
| transport | enum | stdio/http/streamable-http/sse |
| runtime | enum | python/node/container/remote |
| endpoint | string | Server endpoint or command |
| auth_method | enum | none/api_key/oauth/service_account/managed_identity |
| secret_refs | array | Vault references |
| network_policy | json | Network allowlist |
| status | enum | draft/staging/production/archived |
| current_version_id | UUID | Active version |
| owner_id | UUID | Owner |
| created_at | timestamp | Created time |
| updated_at | timestamp | Updated time |

### 19.4 Workflow Entity Fields

| Field | Type | Description |
|---|---|---|
| id | UUID | Workflow ID |
| tenant_id | UUID | Tenant scope |
| project_id | UUID | Project scope |
| name | string | Workflow name |
| description | text | Description |
| trigger_type | enum | manual/chat/schedule/webhook/event |
| graph_json | json | Nodes and edges |
| variables | json | Inputs/outputs |
| retry_policy | json | Retry config |
| approval_policy | json | Approval config |
| status | enum | draft/staging/production/archived |
| current_version_id | UUID | Active version |
| owner_id | UUID | Owner |
| created_at | timestamp | Created time |
| updated_at | timestamp | Updated time |

---

## 20. API Requirements

### 20.1 Agent APIs

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/agents | Create agent |
| GET | /api/agents | List agents |
| GET | /api/agents/{id} | Get agent |
| PUT | /api/agents/{id} | Update agent |
| DELETE | /api/agents/{id} | Delete/archive agent |
| POST | /api/agents/{id}/copy | Copy agent |
| POST | /api/agents/{id}/debug | Debug agent |
| POST | /api/agents/{id}/deploy | Deploy agent |
| POST | /api/agents/{id}/rollback | Rollback agent |
| POST | /api/agents/{id}/template | Create template from agent |
| GET | /api/agents/{id}/versions | List versions |
| GET | /api/agents/{id}/runs | List agent runs |

### 20.2 MCP Server APIs

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/mcp-servers | Create MCP server |
| GET | /api/mcp-servers | List MCP servers |
| GET | /api/mcp-servers/{id} | Get MCP server |
| PUT | /api/mcp-servers/{id} | Update MCP server |
| DELETE | /api/mcp-servers/{id} | Delete/archive MCP server |
| POST | /api/mcp-servers/{id}/copy | Copy MCP server |
| POST | /api/mcp-servers/{id}/debug | Debug MCP server |
| POST | /api/mcp-servers/{id}/deploy | Deploy MCP server |
| POST | /api/mcp-servers/{id}/rollback | Rollback MCP server |
| POST | /api/mcp-servers/{id}/template | Create template from MCP server |
| GET | /api/mcp-servers/{id}/tools | List tools |
| POST | /api/mcp-servers/{id}/tools/test | Test tool call |

### 20.3 Workflow APIs

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/workflows | Create workflow |
| GET | /api/workflows | List workflows |
| GET | /api/workflows/{id} | Get workflow |
| PUT | /api/workflows/{id} | Update workflow |
| DELETE | /api/workflows/{id} | Delete/archive workflow |
| POST | /api/workflows/{id}/copy | Copy workflow |
| POST | /api/workflows/{id}/debug | Debug workflow |
| POST | /api/workflows/{id}/deploy | Deploy workflow |
| POST | /api/workflows/{id}/rollback | Rollback workflow |
| POST | /api/workflows/{id}/run | Run workflow |
| POST | /api/workflows/{id}/template | Create template from workflow |
| GET | /api/workflows/{id}/versions | List versions |
| GET | /api/workflows/{id}/runs | List workflow runs |

### 20.4 Template APIs

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

### 20.5 Runtime APIs

| Method | Endpoint | Purpose |
|---|---|---|
| POST | /api/chat | Send prompt |
| GET | /api/runs | List runs |
| GET | /api/runs/{id} | Get run status |
| POST | /api/runs/{id}/cancel | Cancel run |
| POST | /api/runs/{id}/retry | Retry failed run |
| POST | /api/runs/{id}/replay | Replay run |
| GET | /api/runs/{id}/logs | Get logs |
| GET | /api/runs/{id}/trace | Get trace |
| POST | /api/approvals/{id}/approve | Approve action |
| POST | /api/approvals/{id}/reject | Reject action |

---

## 21. Security Architecture

### 21.1 Security Principles

| Principle | Requirement |
|---|---|
| Least privilege | Agents can only access approved MCP servers/tools |
| Explicit approval | Risky actions require human approval |
| Secret isolation | Secrets stored only in external vault/key vault |
| Tenant isolation | Users cannot access other tenant assets |
| Full audit | Every action creates audit event |
| Safe templates | Templates must not include raw secrets |
| Sandboxed execution | Scripts and risky tools run in isolated environment |
| Policy-as-code | Access and risk rules are programmable and auditable |

### 21.2 Permission Matrix

| Role | Capabilities |
|---|---|
| Viewer | View assets and runs |
| Runner | Run approved workflows |
| Builder | Create/update/debug assets in dev |
| Publisher | Deploy to test/staging |
| Admin | Manage users, tenants, policies |
| Security Approver | Approve high-risk operations |
| Platform Owner | Full platform management |

### 21.3 Approval Requirements

| Action | Approval Required? |
|---|---|
| Read-only tool | No |
| Create report | No |
| Send email outside allowed domain | Yes |
| Restart service | Configurable |
| Delete resource | Yes |
| Execute command/script | Yes |
| Deploy MCP server to production | Yes |
| Grant admin tool access to agent | Yes |
| Import unsigned external template | Yes / blocked by default |

---

## 22. Observability and Audit

### 22.1 Logs

The system must capture:

- User prompt.
- Intent classification.
- Agent planning steps.
- Agent messages.
- MCP tool calls.
- Tool inputs and outputs, with sensitive data redacted.
- Workflow node status.
- Errors and retries.
- Approval decisions.
- Deployment actions.
- Import/export actions.

### 22.2 Metrics

| Metric | Description |
|---|---|
| Workflow success rate | Percent of successful workflow runs |
| Workflow duration | Time per workflow/node |
| Tool failure rate | Failure by tool/MCP server |
| Agent error rate | Failure by agent |
| Token usage | Tokens per agent/workflow/project |
| Model cost | Cost per model/provider/tenant |
| Approval latency | Time waiting for human approval |
| Deployment failure rate | Failed deployments |
| Template usage | Most used templates |

### 22.3 Traces

Each run should produce a trace similar to:

```text
Run ID: run_123
User Prompt
 └─ Intent Router
    └─ Supervisor Agent
       ├─ GIS Agent
       │  └─ ArcGIS MCP Tool: portal_health
       ├─ Database Agent
       │  └─ DB MCP Tool: check_db_health
       ├─ Report Agent
       │  └─ File MCP Tool: create_report
       └─ Email Agent
          └─ Email MCP Tool: send_email
```

---

## 23. Deployment Architecture

### 23.1 Environments

| Environment | Purpose |
|---|---|
| Local Dev | Developer testing with local LLM/MCP servers |
| Development | Shared dev environment |
| Test / QA | Integration testing |
| Staging | Pre-production validation |
| Production | Live enterprise use |

### 23.2 Deployment Units

| Service | Deployment Type |
|---|---|
| Frontend Web App | Container / static web hosting |
| API Gateway | Container / Kubernetes |
| Auth Integration | External IAM / service |
| Agent Runtime Workers | Container / Kubernetes workers |
| Workflow Engine | Temporal or equivalent |
| MCP Gateway | Container / Kubernetes |
| MCP Servers | Local, containerized, or remote |
| Event Bus | Kafka/RabbitMQ/NATS/Redis Streams |
| PostgreSQL | Managed or self-hosted |
| Redis | Managed or self-hosted |
| Vector DB | Qdrant/pgvector/Weaviate |
| Object Storage | S3/Azure Blob/GCS/MinIO |
| Observability | OpenTelemetry collector + Prometheus/Grafana/Loki |
| Secrets | Vault/Azure Key Vault/AWS Secrets Manager/GCP Secret Manager |

### 23.3 GitOps and CI/CD

Required pipeline stages:

```text
Commit / Save Asset
 ↓
Schema Validation
 ↓
Unit Tests
 ↓
Integration Tests
 ↓
Security Scan
 ↓
Container Build
 ↓
Image Scan
 ↓
Deploy to Dev
 ↓
Smoke Test
 ↓
Approval
 ↓
Deploy to Staging/Production
 ↓
Post-Deploy Validation
 ↓
Monitoring and Rollback Readiness
```

---

## 24. Recommended Final Technology Stack and Tool Mapping — v1.1

This section updates the project with a practical technology selection and mapping for the full platform. The goal is **not** to use every tool as a core runtime. The goal is to select the technologies that best support the product objective:

> Build an enterprise platform where users can create, update, delete, debug, deploy, copy, template, import, export, and visually orchestrate AI agents, MCP servers, and workflows.

### 24.1 Technology Selection Principles

| Principle | Meaning for This Project |
|---|---|
| Build a platform, not only a chatbot | The core must include design-time studios, runtime execution, governance, debugging, deployment, and templates. |
| Separate design-time from runtime | Agent Studio, MCP Studio, Workflow Studio, and Template Library are design-time. LangGraph, Temporal, and MCP Gateway are runtime. |
| MCP is the tool interface, not the workflow engine | MCP servers expose tools/resources/prompts. They should not become the main orchestrator. |
| A2A is for agent-to-agent communication | Internal agents can use orchestrator-mediated messaging; external agents can use A2A-style integration. |
| Use durable workflow execution for production | Long-running workflows, retries, approvals, schedules, and resumability should use Temporal or equivalent. |
| Use visual workflow UI only for authoring | React Flow stores graph definitions; execution should be handled by the runtime engine. |
| Avoid tool sprawl | Dify, Flowise, SIM, Tesslate Agent Builder, CrewAI, ADK, OpenAI Agents SDK, and Claude Agent SDK should not all become core. Use them selectively. |
| Keep the core vendor-neutral | The platform should support OpenAI, Anthropic, Google, local models, and future models through adapters. |
| Security first | MCP tools, browser automation, generated code, and deployment actions must be sandboxed, logged, approved, and policy-controlled. |

---

### 24.2 Final Selected Stack — Executive Decision

| Platform Layer | Selected Technology | Status | Why This Fits the Objective |
|---|---|---|---|
| Frontend Application | **Next.js + React + TypeScript** | Core | Enterprise web app foundation for Chat UI, Studios, Admin Console, and dashboards. |
| Visual Workflow Builder | **React Flow** | Core | Best fit for custom visual node/edge workflow builder. |
| UI System | TailwindCSS + shadcn/ui | Core | Fast enterprise UI development with consistent components. |
| Backend API | **FastAPI** | Core | Python-native platform API that aligns with LangGraph, MCP Python SDK, OpenHands SDK, and AI tooling. |
| Agent Orchestration | **LangGraph** | Core | Primary runtime for stateful, multi-agent, human-in-the-loop agent workflows. |
| Durable Workflow Engine | **Temporal** | Core | Production-grade long-running workflows, retries, signals, timers, schedules, and resumability. |
| MCP Runtime | **MCP Python SDK** | Core | Primary SDK for building and operating Python MCP servers and clients. |
| TypeScript MCP Development | **FastMCP by punkpeye** | Supported | Use for TypeScript MCP server templates and Node.js-based tools. Not the only MCP framework. |
| Code/DevOps Agent | **OpenHands Software Agent SDK** | Core Accelerator | Used for code generation, repo edits, MCP server generation, test generation, and safe dev workspaces. |
| Integration Automation Hub | **Activepieces** | Integrated Accelerator | Use for connector-rich automation and pieces that can be exposed as MCP-compatible capabilities. |
| LLM App / RAG Builder | **Dify** | Optional Accelerator | Can be integrated for rapid LLM apps and RAG prototypes, but should not replace the platform core. |
| Visual LLM Prototype Tool | **Flowise** | Optional / Reference | Useful for prototyping chains/agents and inspiration, but not core production runtime. |
| AI Workforce Builder | **SIM** | Optional / Reference | Strong reference for AI workforce UX and integrations; not selected as core because this project needs custom MCP lifecycle governance. |
| Agent Studio Reference | **TesslateAI Agent-Builder** | Optional / Reference | Reference for visual agent builder patterns; not selected as core unless adopting TFrameX. |
| Provider Agent SDK | OpenAI Agents SDK | Provider Adapter | Use when building OpenAI-specific agents, handoffs, sessions, or guardrails; not the global runtime. |
| Provider Agent SDK | Claude Agent SDK | Provider Adapter | Use for Claude-code-style agents and provider-specific workflows; not the global runtime. |
| Google Agent Framework | Google ADK | Provider / Ecosystem Adapter | Use when Gemini/Google Cloud agent integration is required. |
| Simple Multi-Agent Teams | CrewAI | Optional | Good for simple crew/team patterns; LangGraph remains the core runtime. |
| Legacy Multi-Agent Framework | AutoGen | Not selected for new core | Treat as migration/reference only because current GitHub page indicates maintenance mode. |
| Database | PostgreSQL | Core | Source of truth for users, tenants, assets, versions, runs, logs metadata, policies, and configs. |
| Vector Store | Qdrant | Core | Semantic memory, RAG, workflow/agent/template search. pgvector can be an alternative for simpler deployments. |
| Cache / Session / Lightweight Queue | Redis | Core | Session cache, short-term state, locks, rate limits, ephemeral state. |
| Event Bus | NATS or Redis Streams for MVP; Kafka later | Core / Scale Option | Agent events, tool events, deployment events, run status updates. |
| Identity | Keycloak | Core | SSO, OAuth/OIDC, tenant/project role management. |
| Policy | Open Policy Agent | Core | Policy-as-code for tool access, deployment, approval, and tenant controls. |
| Secrets | HashiCorp Vault or Cloud Key Vault | Core | Store credentials, API keys, tokens, deployment secrets. |
| Observability | OpenTelemetry + Prometheus + Grafana + Loki | Core | Logs, metrics, traces, run timelines, cost and token tracking. |
| Deployment | Docker + Kubernetes | Core | Containerized platform runtime and MCP server hosting. |
| GitOps | Argo CD or Flux | Phase 2 Core | Controlled promotion, rollback, and environment drift detection. |
| CI/CD | GitHub Actions / GitLab CI / Azure DevOps | Core | Build, test, scan, package, deploy. |
| Browser / Computer Automation | Playwright + Vision/CUA | Core for automation | Browser UI automation, visual UI mapping, screenshots, and interaction agents. |
| Object Storage | MinIO/S3/Azure Blob/GCS | Core | Templates, reports, exported packages, artifacts, screenshots, logs archive. |
| Security Scanning | Trivy + Syft/Grype + secret scanners | Core | Scan containers, dependencies, SBOMs, templates, and generated MCP servers. |

---

### 24.3 Technology Evaluation Matrix for User-Listed Tools

| Technology | Best Use in This Project | Fit Score | Final Decision | Notes |
|---|---:|---:|---|---|
| **TesslateAI / Agent-Builder** | Reference for visual multi-agent builder UX and lifecycle ideas | 3/5 | Reference / Optional | Good concept for visual agent development, but likely too tied to its own stack. Use ideas, not as core. |
| **SIM** | AI workforce UX, agent management, integration inspiration | 4/5 | Optional accelerator / reference | Strong product direction, but the target platform requires custom MCP Studio, MCP Gateway, enterprise policy, and ArcGIS-specific operations. |
| **FastMCP** | TypeScript MCP server development | 4/5 | Supported MCP framework | Good for Node.js/TypeScript MCP servers. Use alongside MCP Python SDK. |
| **Flowise** | Rapid LLM workflow prototyping | 3/5 | Optional / non-core | Good visual prototyping, but not enough for full enterprise lifecycle management of agents, MCP servers, templates, deployment, and governance. Use as import/reference later. |
| **Dify** | LLM app builder, RAG pipeline, prototype applications | 4/5 | Optional accelerator | Useful for quick RAG/agentic app prototypes; should not replace the custom platform core. |
| **OpenHands** | Full AI software engineering environment | 4/5 | Optional UI / reference | Good coding-agent product, but use the SDK rather than embedding the whole app initially. |
| **OpenHands Software Agent SDK** | Code/DevOps Agent engine | 5/5 | Selected accelerator | Best fit for generating MCP server code, editing repos, producing tests, and running in sandboxed workspaces. |
| **LangGraph** | Stateful multi-agent runtime | 5/5 | Selected core | Primary orchestration engine for supervisor/planner/router/specialist agents. |
| **Activepieces** | Integration automation and connector hub | 5/5 | Selected integrated accelerator | Use for connector-rich automations and expose selected pieces as governed MCP tools. |
| **OpenAI Agents SDK** | OpenAI-specific agent execution, handoffs, sessions, guardrails | 4/5 | Provider adapter | Useful, but the platform should not depend only on OpenAI. Wrap behind Model Provider Adapter. |
| **Claude Agent SDK** | Claude-code-style agent workflows and tool usage | 4/5 | Provider adapter | Use for Claude-powered coding/building agents when required. |
| **Google ADK** | Gemini/Google agent ecosystem | 4/5 | Provider adapter | Use for Google/Gemini-specific deployments or customer environments. |
| **CrewAI** | Simple multi-agent crew templates | 3/5 | Optional | Useful for quick agent team templates, but not the main enterprise runtime. |
| **AutoGen** | Existing AutoGen workflow migration/reference | 2/5 | Not selected for new core | Treat as legacy/migration only; do not use as primary runtime. |
| **Next.js + React Flow + FastAPI + MCP Python SDK + LangGraph + Temporal** | Complete custom platform foundation | 5/5 | Selected core architecture | Best combination for this product’s custom lifecycle, runtime, governance, and MCP objectives. |
| **PostgreSQL + Qdrant + Redis + OPA + Vault + Keycloak** | Enterprise platform state, memory, policy, secrets, identity | 5/5 | Selected core infrastructure | Required for production-grade governance, memory, asset management, and security. |
| **OpenTelemetry + Prometheus + Grafana + Docker + Kubernetes** | Observability and deployment | 5/5 | Selected core operations stack | Required for production operations and scale. |

---

### 24.4 Final Product Architecture Mapped to Technologies

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ USER EXPERIENCE LAYER                                                        │
│ Next.js + React + TypeScript + Tailwind + shadcn/ui                          │
│                                                                              │
│ - Chat UI                                                                    │
│ - Visual Workflow Builder: React Flow                                        │
│ - Agent Studio                                                               │
│ - MCP Server Studio                                                          │
│ - Template Library                                                           │
│ - Admin Console                                                              │
│ - Execution Monitor                                                          │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ API / ACCESS LAYER                                                           │
│ FastAPI + Keycloak + OIDC/OAuth + RBAC/ABAC                                  │
│                                                                              │
│ - API Gateway / Backend-for-Frontend                                         │
│ - Tenant / Project / Environment isolation                                   │
│ - WebSocket/SSE streaming for run status                                     │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ PLATFORM INTENT ROUTER                                                       │
│ FastAPI service + LangGraph classification agent                             │
│                                                                              │
│ Routes requests to:                                                          │
│ - Business execution                                                         │
│ - Agent lifecycle management                                                 │
│ - MCP server lifecycle management                                            │
│ - Workflow lifecycle management                                              │
│ - Template lifecycle management                                              │
└────────────────────────────┬─────────────────────────────────────────────────┘
                             │
        ┌────────────────────┴────────────────────┐
        ▼                                         ▼
┌────────────────────────────────────┐   ┌─────────────────────────────────────┐
│ DESIGN-TIME CONTROL PLANE          │   │ RUNTIME CONTROL PLANE               │
│                                    │   │                                     │
│ Agent Studio Service               │   │ LangGraph Agent Runtime             │
│ MCP Studio Service                 │   │ Temporal Workflow Engine            │
│ Workflow Studio Service            │   │ Task Queue / State Store            │
│ Template Service                   │   │ Human Approval Queue                │
│ Version Manager                    │   │ Retry / Resume / Scheduler          │
│ Test Runner                        │   │ Result Aggregator                   │
│ Package Builder                    │   │ Validator Agent                     │
│ Deployment Manager                 │   │ Final Formatter                     │
└────────────────────┬───────────────┘   └──────────────────┬──────────────────┘
                     ▼                                      ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ META-AGENT LAYER                                                             │
│ LangGraph + OpenHands Software Agent SDK + Provider Adapters                 │
│                                                                              │
│ - Platform Architect Agent                                                   │
│ - Agent Builder Agent                                                        │
│ - MCP Builder Agent                                                          │
│ - Workflow Builder Agent                                                     │
│ - Template Manager Agent                                                     │
│ - QA/Test Agent                                                              │
│ - Security Review Agent                                                      │
│ - DevOps Deployment Agent                                                    │
│ - Documentation Agent                                                        │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ AGENT COMMUNICATION LAYER                                                    │
│ Internal messages + A2A-compatible gateway + NATS/Redis Streams/Kafka        │
│                                                                              │
│ - Internal agents communicate through orchestrator/state                     │
│ - External agents use A2A-style protocol                                     │
│ - Long-running events go through event bus                                   │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ MODEL CONTROL PLANE                                                          │
│ Provider adapters for OpenAI Agents SDK, Claude Agent SDK, Google ADK,       │
│ Ollama/vLLM/local models, and future providers                               │
│                                                                              │
│ - Model registry                                                             │
│ - Model router                                                               │
│ - Fallback model                                                             │
│ - Prompt registry                                                            │
│ - Cost and token budget                                                      │
│ - Evaluation score tracking                                                  │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ MEMORY / KNOWLEDGE LAYER                                                     │
│ PostgreSQL + Redis + Qdrant + Object Storage                                 │
│                                                                              │
│ - Short-term memory                                                          │
│ - Long-term memory                                                           │
│ - Vector search / RAG                                                        │
│ - Knowledge graph metadata                                                   │
│ - Template and artifact storage                                              │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ MCP TOOL CONTROL PLANE                                                       │
│ MCP Gateway + MCP Python SDK + FastMCP TypeScript support                    │
│                                                                              │
│ - MCP Client Manager                                                         │
│ - Tool Registry                                                              │
│ - Resource Registry                                                          │
│ - Prompt Registry                                                            │
│ - Tool Permission Engine: OPA                                                │
│ - Tool Validator                                                             │
│ - MCP Debug Console / Inspector integration                                  │
│ - Tool Sandbox                                                               │
│ - Tool Execution Log                                                         │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ MCP SERVER / INTEGRATION LAYER                                               │
│ MCP Python SDK + FastMCP + Activepieces + custom adapters                    │
│                                                                              │
│ - ArcGIS MCP Server                                                          │
│ - Database MCP Server                                                        │
│ - File/PDF MCP Server                                                        │
│ - Browser/CUA MCP Server                                                     │
│ - Cloud MCP Server                                                           │
│ - Email/Notification MCP Server                                              │
│ - Monitoring MCP Server                                                      │
│ - ITSM MCP Server                                                            │
│ - Workflow MCP Server                                                        │
│ - Code/DevOps MCP Server                                                     │
│ - Activepieces Integration Bridge                                            │
│ - Optional Dify/Flowise/SIM Integration Bridges                              │
└────────────────────────────────────┬─────────────────────────────────────────┘
                                     ▼
┌──────────────────────────────────────────────────────────────────────────────┐
│ ENTERPRISE SYSTEMS / TOOLS                                                   │
│ ArcGIS Enterprise / ArcGIS Online / Databases / Cloud / Browser / Email /    │
│ ITSM / Monitoring / Git / CI-CD / Kubernetes / Files / SharePoint            │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 24.5 How Each Listed Technology Should Be Used

#### 24.5.1 SIM

| Decision | Use as optional accelerator/reference, not the core platform. |
|---|---|
| Where it fits | AI workforce builder patterns, agent management UX, integration inspiration. |
| How to use | Study its agent/workflow UX and integration patterns. Optionally provide SIM import/export connector later. |
| Why not core | The target product needs custom MCP server lifecycle management, ArcGIS/enterprise operations, policy, template governance, deployment manager, and design-time/runtime separation. |

#### 24.5.2 TesslateAI Agent-Builder

| Decision | Use as Agent Studio reference or optional experimental accelerator. |
|---|---|
| Where it fits | Visual agent creation, testing, deployment inspiration. |
| How to use | Review its model for agent lifecycle, auth, persistence, testing. Borrow concepts; avoid hard dependency unless adopting TFrameX. |
| Why not core | The platform requires a vendor-neutral agent runtime and MCP lifecycle model. |

#### 24.5.3 Flowise

| Decision | Optional prototype/import target, not core runtime. |
|---|---|
| Where it fits | Low-code LLM/agent workflow prototyping. |
| How to use | Phase 4 compatibility: import Flowise flows into internal workflow graph or run Flowise externally as a connected service. |
| Why not core | It does not cover the full target scope: MCP server creation/debug/deploy/copy, enterprise policy, versioned templates, custom ArcGIS operation lifecycle, and durable production workflow execution. |
| Security note | Do not expose low-code tool execution publicly without strong patching, sandboxing, auth, and network isolation. |

#### 24.5.4 Dify

| Decision | Optional LLM app and RAG accelerator. |
|---|---|
| Where it fits | Rapid RAG apps, knowledge-base prototypes, model management ideas, app publishing. |
| How to use | Integrate as an external node or import selected patterns into the platform’s RAG/knowledge layer. |
| Why not core | Dify is an LLM application platform. The target product is a full enterprise AI + MCP + lifecycle factory. |

#### 24.5.5 Activepieces

| Decision | Select as Integration Automation Hub. |
|---|---|
| Where it fits | Business app connectors, event triggers, third-party SaaS automation, pieces exposed to LLM/MCP workflows. |
| How to use | Add Activepieces Bridge as a workflow/MCP node. Let platform workflows call governed Activepieces flows or expose selected pieces as MCP tools. |
| Why selected | Reduces the need to build hundreds of SaaS connectors from scratch. |

#### 24.5.6 LangGraph

| Decision | Select as core agent orchestration runtime. |
|---|---|
| Where it fits | Supervisor/planner/router agents, specialist agents, stateful task graphs, human-in-loop paths. |
| How to use | Build the Agent Runtime Service around LangGraph. Store graph state in PostgreSQL/Redis as needed. Coordinate with Temporal for durable execution. |
| Why selected | Provides a strong code-first runtime for stateful agent workflows while allowing custom governance. |

#### 24.5.7 Temporal

| Decision | Select as core durable workflow engine. |
|---|---|
| Where it fits | Long-running workflows, scheduled jobs, retries, signals, human approval waits, rollback orchestration. |
| How to use | Workflow Studio exports executable workflow definitions that run through Temporal workers. LangGraph agents run as Temporal activities or child workflows where needed. |
| Why selected | Production reliability and durability. |

#### 24.5.8 FastMCP and MCP Python SDK

| Decision | Use MCP Python SDK as primary, FastMCP as TypeScript support. |
|---|---|
| Where it fits | Building MCP servers, tool schemas, resources, prompts, MCP client manager, and debug workflows. |
| How to use | Python MCP SDK for ArcGIS/DB/File/Cloud/DevOps integrations. FastMCP for TypeScript/Node.js MCP templates and integrations. |
| Why selected | Directly maps to the MCP-first agent-to-tool architecture. |

#### 24.5.9 OpenHands and OpenHands Software Agent SDK

| Decision | Select OpenHands Software Agent SDK as Code/DevOps Agent engine. |
|---|---|
| Where it fits | MCP server code generation, test generation, repo maintenance, migration tasks, deployment manifests, documentation updates. |
| How to use | Run OpenHands SDK in sandboxed Docker/Kubernetes workspaces controlled by policy and approval. Do not allow unrestricted shell access in production. |
| Why selected | Best fit for the platform’s “create/update/debug/deploy/copy MCP server” objective. |

#### 24.5.10 OpenAI Agents SDK

| Decision | Use as OpenAI provider adapter, not the global runtime. |
|---|---|
| Where it fits | OpenAI-specific agents, handoffs, guardrails, sessions, tracing, model provider features. |
| How to use | Wrap behind Model Control Plane so the platform can route selected agents to OpenAI Agents SDK where appropriate. |
| Why not core-only | The platform should stay model/provider neutral. |

#### 24.5.11 Claude Agent SDK

| Decision | Use as Anthropic/Claude provider adapter and coding-agent option. |
|---|---|
| Where it fits | Claude-powered coding agents, tool-use workflows, developer automation. |
| How to use | Provide an Anthropic provider adapter. Use only in approved environments and with separate budgets/quotas. |
| Why not core-only | The platform should not depend on one provider. |

#### 24.5.12 Google ADK

| Decision | Use as Google/Gemini ecosystem adapter. |
|---|---|
| Where it fits | Gemini-optimized deployments, Google Cloud agent workflows, ADK sample reuse. |
| How to use | Add ADK adapter in the Model Control Plane. |
| Why not core-only | The platform should remain provider-neutral. |

#### 24.5.13 CrewAI

| Decision | Optional template runtime for simple crews. |
|---|---|
| Where it fits | Simple role-based team automations and demos. |
| How to use | Optional import/export or template converter. |
| Why not core | LangGraph + Temporal gives more control for enterprise state, durability, retries, approvals, and graph-based runtime. |

#### 24.5.14 AutoGen

| Decision | Do not select for new core development. |
|---|---|
| Where it fits | Migration/import of existing AutoGen assets or reference for multi-agent patterns. |
| How to use | Build a future AutoGen import adapter only if customers already have AutoGen workflows. |
| Why not selected | Current official GitHub result indicates AutoGen is in maintenance mode. |

---

### 24.6 Core Services to Build

| Service | Main Technology | Responsibilities |
|---|---|---|
| Web App | Next.js + React Flow | Chat UI, visual workflow builder, Agent Studio, MCP Studio, Template Library, Admin Console. |
| Platform API | FastAPI | REST/GraphQL/WebSocket APIs, tenant/project management, asset operations. |
| Identity Integration Service | Keycloak + FastAPI | SSO, token validation, user/role/project mapping. |
| Asset Registry Service | FastAPI + PostgreSQL | Store agents, MCP servers, workflows, templates, versions, metadata. |
| Version Manager | PostgreSQL + object storage + Git optional | Versioning, rollback, clone/copy, diffs. |
| Template Manager | PostgreSQL + object storage | Create/import/export JSON/YAML/ZIP templates. |
| Agent Runtime Service | LangGraph | Supervisor, planner, router, specialist agents, meta-agents. |
| Provider Adapter Service | OpenAI Agents SDK, Claude SDK, Google ADK, Ollama/vLLM adapters | Model routing and provider-specific execution. |
| Workflow Runtime Service | Temporal | Durable runs, retries, approvals, schedules, long-running workflows. |
| MCP Gateway Service | MCP Python SDK + FastMCP compatibility | Governed MCP client/session management, tool registry, tool execution, logs. |
| MCP Studio Service | MCP Python SDK + OpenHands SDK | Generate, debug, test, package, deploy MCP servers. |
| Code/DevOps Agent Service | OpenHands SDK | Repo edits, tests, container manifests, MCP server generation, docs. |
| Integration Hub Service | Activepieces Bridge | Trigger/call Activepieces flows and expose selected pieces as governed capabilities. |
| Memory Service | PostgreSQL + Redis + Qdrant | Conversation memory, agent memory, project memory, semantic search. |
| Policy Service | OPA | Tool permissions, deployment policies, approval requirements. |
| Secret Service | Vault / cloud key vault | Secret references, token injection, credential rotation. |
| Observability Service | OpenTelemetry + Prometheus + Grafana + Loki | Metrics, traces, logs, execution timeline, cost tracking. |
| Deployment Manager | Docker + Kubernetes + CI/CD + Argo CD/Flux | Build images, deploy agents/MCP servers/workflows, rollback. |
| Security Scanner | Trivy + Syft/Grype + secret scanning | Scan generated code, containers, templates, dependencies. |

---

### 24.7 Agent Types and Their Technology Mapping

| Agent Type | Runtime | Tools/SDKs | Main Responsibilities |
|---|---|---|---|
| Supervisor Agent | LangGraph | Model Control Plane | Owns execution, coordinates agents, delegates tasks. |
| Planner Agent | LangGraph | LLM provider adapters | Converts prompt into plan and workflow graph. |
| Router Agent | LangGraph | Agent registry, tool registry | Selects agents, MCP tools, workflows. |
| GIS Agent | LangGraph | ArcGIS MCP Server | Portal/Server/Data Store health, services, logs, GIS operations. |
| Database Agent | LangGraph | DB MCP Server | Safe SQL, schema inspection, DB health checks. |
| Browser Agent | LangGraph | Browser/CUA MCP Server, Playwright | UI automation, visual interactions, screenshots. |
| File/Document Agent | LangGraph | File/PDF MCP Server, Qdrant | Document read, extraction, RAG, report generation. |
| Security Agent | LangGraph | OPA, Vault, Security scanners | Risk scoring, policy checks, approval decisions. |
| Report Agent | LangGraph | Document MCP Server | Create reports, summaries, exports. |
| Email Agent | LangGraph | Email MCP Server, Activepieces optional | Send notifications, drafts, approvals. |
| Agent Builder Agent | LangGraph + OpenHands SDK | Asset Registry, tests | Create/update/debug/deploy/copy agents. |
| MCP Builder Agent | LangGraph + OpenHands SDK | MCP SDKs, scanners, CI/CD | Create/update/debug/deploy/copy MCP servers. |
| Workflow Builder Agent | LangGraph | React Flow graph spec, Temporal | Generate workflow graphs and executable specs. |
| Template Manager Agent | LangGraph | Template Manager | Create/import/export templates. |
| QA/Test Agent | LangGraph + OpenHands SDK | Test Runner | Generate and execute tests. |
| DevOps Deployment Agent | LangGraph + OpenHands SDK | Kubernetes, GitOps, CI/CD | Package, deploy, rollback. |

---

### 24.8 MCP Server Types and Technology Mapping

| MCP Server | Recommended Implementation | Tools Exposed | Notes |
|---|---|---|---|
| ArcGIS MCP Server | Python MCP SDK | Portal health, Server health, Data Store status, service listing, logs, item metadata | High priority for this product. |
| Database MCP Server | Python MCP SDK | Safe query, schema inspect, health check, explain plan | Must enforce SQL safety policies. |
| File/PDF MCP Server | Python MCP SDK | Read PDF/DOCX/XLSX, extract tables, create report | Connect to Qdrant for RAG. |
| Browser/CUA MCP Server | Python/Node MCP + Playwright | open page, click, type, screenshot, observe UI | Must run in sandboxed browser workers. |
| Cloud MCP Server | Python MCP SDK | Azure/AWS/GCP resource inspection, logs, deployments | Use managed identity/service principal secrets. |
| Email MCP Server | Python MCP SDK or Activepieces bridge | draft, send, notify, approval email | Approval required for external recipients. |
| Monitoring MCP Server | Python MCP SDK | query logs, metrics, alerts, traces | Integrate with Prometheus/Loki/SIEM. |
| ITSM MCP Server | Python MCP SDK or Activepieces bridge | create/update ticket, attach report | Integrate Jira/ServiceNow. |
| Workflow MCP Server | Python MCP SDK | run workflow, get status, pause/resume | Allows agents to trigger governed workflows. |
| Code/DevOps MCP Server | Python MCP SDK + OpenHands SDK | repo edit, generate code, run tests, create PR | Must run in isolated workspace. |
| Activepieces MCP Bridge | Activepieces API + MCP wrapper | call piece, list piece, run flow | Use for SaaS connectors. |
| Dify Bridge | Dify API + MCP wrapper | run app/workflow, query knowledge base | Optional. |
| Flowise Bridge | Flowise API + MCP wrapper | run flow, import flow | Optional. |
| SIM Bridge | SIM API if available + adapter | run agent/workflow | Optional/future. |

---

### 24.9 Data and Storage Mapping

| Data Type | Store | Description |
|---|---|---|
| Tenants, users, projects | PostgreSQL | Main relational data. |
| Agent definitions | PostgreSQL + object storage | JSON/YAML specs, versions, templates. |
| MCP server definitions | PostgreSQL + object storage | Manifest, tools, resources, prompts, runtime config. |
| Workflow graphs | PostgreSQL | React Flow nodes/edges plus executable spec mapping. |
| Templates | Object storage + PostgreSQL metadata | JSON/YAML/ZIP packages. |
| Run state | Temporal + PostgreSQL | Durable workflow state and platform metadata. |
| Short-term memory | Redis + PostgreSQL | Session and conversation context. |
| Long-term memory | PostgreSQL + Qdrant | Project/user/agent memory and semantic retrieval. |
| Tool execution logs | PostgreSQL + Loki/object storage | Queryable logs and archive. |
| Traces/metrics | OpenTelemetry + Prometheus/Grafana | Runtime observability. |
| Generated code/artifacts | Object storage + Git repository | MCP servers, deployment manifests, reports. |
| Secrets | Vault / cloud key vault | Secret references only in application database. |

---

### 24.10 Updated MVP Technology Scope

The MVP should not try to integrate every listed platform. It should build the custom core first.

#### MVP Core Stack

| Area | MVP Technology |
|---|---|
| Frontend | Next.js, React, React Flow, TailwindCSS, shadcn/ui |
| Backend | FastAPI |
| Auth | Keycloak |
| Database | PostgreSQL |
| Cache | Redis |
| Vector Search | Qdrant |
| Agent Runtime | LangGraph |
| Durable Execution | Temporal |
| MCP | MCP Python SDK, MCP Gateway prototype |
| Code Agent | OpenHands Software Agent SDK, sandboxed |
| Policy | OPA basic policies |
| Secrets | Vault or cloud key vault |
| Observability | OpenTelemetry, Prometheus, Grafana, Loki |
| Deployment | Docker Compose for dev, Kubernetes-ready manifests |

#### MVP Integrations

| Integration | Technology |
|---|---|
| ArcGIS MCP Server | Python MCP SDK + ArcGIS REST/Python API |
| Database MCP Server | Python MCP SDK + SQLAlchemy/async DB drivers |
| File/PDF MCP Server | Python MCP SDK + document parsers + Qdrant |
| Email MCP Server | Python MCP SDK or Activepieces bridge |
| Code/DevOps MCP Server | OpenHands SDK sandbox |

#### Post-MVP Accelerators

| Accelerator | Phase | Purpose |
|---|---:|---|
| Activepieces | Phase 2 | Connector hub and SaaS automation bridge. |
| Dify | Phase 3 | Optional RAG/app builder bridge. |
| Flowise | Phase 3 | Optional flow import/prototype bridge. |
| SIM | Phase 4 | Optional AI workforce interoperability/reference. |
| Tesslate Agent-Builder | Phase 4 | Optional agent builder import/reference. |
| CrewAI | Phase 4 | Optional simple crew template runtime. |
| OpenAI Agents SDK | Phase 2 | Provider-specific adapter. |
| Claude Agent SDK | Phase 2 | Provider-specific adapter and coding workflows. |
| Google ADK | Phase 3 | Google/Gemini ecosystem adapter. |
| AutoGen | Phase 5 | Legacy import only if required. |

---

### 24.11 Build vs Integrate Decision

| Capability | Build Custom | Integrate Existing | Decision |
|---|---:|---:|---|
| Unified asset model for agents/MCP/workflows/templates | Yes | No | Build custom. |
| Visual workflow editor | No | Yes: React Flow | Build UI using React Flow. |
| Agent runtime | No | Yes: LangGraph | Integrate LangGraph. |
| Durable workflows | No | Yes: Temporal | Integrate Temporal. |
| MCP server framework | No | Yes: MCP SDK/FastMCP | Integrate SDKs. |
| Code generation agent | No | Yes: OpenHands SDK | Integrate SDK in sandbox. |
| SaaS connectors | No | Yes: Activepieces | Integrate after MVP core. |
| RAG app builder | No initially | Optional: Dify | Optional bridge. |
| LLM visual prototyping | No initially | Optional: Flowise | Optional bridge/import. |
| Auth/SSO | No | Yes: Keycloak | Integrate. |
| Policy engine | No | Yes: OPA | Integrate. |
| Secrets management | No | Yes: Vault/cloud vault | Integrate. |
| Observability | No | Yes: OpenTelemetry stack | Integrate. |
| Deployment runtime | No | Yes: Kubernetes/Docker | Integrate. |

---

### 24.12 Updated Technology-Mapped Roadmap

#### Phase 1 — Core Platform Foundation

| Deliverable | Technology |
|---|---|
| Next.js platform shell | Next.js, React, Tailwind, shadcn/ui |
| Visual workflow builder MVP | React Flow |
| Backend API | FastAPI |
| Auth and tenant management | Keycloak + PostgreSQL |
| Asset registry | PostgreSQL + object storage |
| Agent runtime prototype | LangGraph |
| MCP Gateway prototype | MCP Python SDK |
| Basic observability | OpenTelemetry + logs |

#### Phase 2 — Lifecycle + Runtime Reliability

| Deliverable | Technology |
|---|---|
| Agent Studio lifecycle | FastAPI + PostgreSQL + LangGraph |
| MCP Studio lifecycle | MCP Python SDK + OpenHands SDK |
| Workflow runtime | Temporal |
| Debug console | OpenTelemetry traces + run logs + replay metadata |
| Versioning/copy/rollback | PostgreSQL + object storage + Git optional |
| QA/Test Agent | LangGraph + OpenHands SDK |
| Policy enforcement | OPA |
| Secrets integration | Vault/key vault |

#### Phase 3 — Enterprise Integrations

| Deliverable | Technology |
|---|---|
| ArcGIS MCP Server | MCP Python SDK + ArcGIS REST API/Python API |
| Database MCP Server | MCP Python SDK + SQLAlchemy |
| File/PDF/RAG MCP Server | MCP Python SDK + Qdrant |
| Email/Notification MCP Server | MCP Python SDK or Activepieces |
| Activepieces bridge | Activepieces API + MCP wrapper |
| OpenAI/Claude provider adapters | OpenAI Agents SDK + Claude Agent SDK wrappers |
| Kubernetes deployment | Docker + Kubernetes + CI/CD |

#### Phase 4 — Marketplace, Adapters, Advanced Agent Ecosystem

| Deliverable | Technology |
|---|---|
| Template marketplace | PostgreSQL + object storage + UI |
| Dify bridge | Dify API + MCP wrapper |
| Flowise bridge/importer | Flowise API/flow export parser |
| SIM bridge/importer | SIM API/adapter if available |
| Google ADK adapter | Google ADK |
| CrewAI template runtime | CrewAI optional worker |
| A2A gateway | A2A-compatible service |

#### Phase 5 — Scale, Governance, and Productization

| Deliverable | Technology |
|---|---|
| GitOps deployment | Argo CD or Flux |
| Advanced observability | OpenTelemetry + Prometheus + Grafana + Loki |
| Security scanning | Trivy + Syft/Grype + secret scanning |
| HA/DR | Kubernetes multi-zone + managed DB + object storage replication |
| Cost governance | Model Control Plane + token/cost telemetry |
| Enterprise audit exports | PostgreSQL + object storage reports |

---

### 24.13 Final Recommended Stack Summary

```text
Frontend:
  Next.js + React + TypeScript + React Flow + TailwindCSS + shadcn/ui

Backend:
  FastAPI + PostgreSQL + Redis + Qdrant + Object Storage

Agent Runtime:
  LangGraph as primary runtime
  Temporal for durable workflows
  OpenHands Software Agent SDK for code/devops agents
  Provider adapters for OpenAI Agents SDK, Claude Agent SDK, Google ADK, local models

MCP Layer:
  MCP Python SDK as primary
  FastMCP TypeScript support
  MCP Gateway + Client Manager + Tool Registry + Debug Console

Integration Layer:
  Custom MCP servers first: ArcGIS, DB, File/PDF, Browser/CUA, Cloud, Email, Monitoring, ITSM
  Activepieces bridge for many SaaS connectors
  Optional Dify, Flowise, SIM bridges later

Governance:
  Keycloak + OPA + Vault + approval workflows + audit logs

Operations:
  OpenTelemetry + Prometheus + Grafana + Loki
  Docker + Kubernetes + CI/CD + Argo CD/Flux
```

---

### 24.14 Technology Source Links

| Technology | Source |
|---|---|
| TesslateAI Agent-Builder | https://github.com/TesslateAI/Agent-Builder |
| SIM | https://github.com/simstudioai/sim |
| FastMCP | https://github.com/punkpeye/fastmcp |
| Flowise | https://github.com/FlowiseAI/Flowise |
| Dify | https://github.com/langgenius/dify |
| OpenHands | https://github.com/OpenHands/OpenHands |
| OpenHands Software Agent SDK | https://github.com/OpenHands/software-agent-sdk |
| LangGraph | https://github.com/langchain-ai/langgraph |
| Activepieces | https://github.com/activepieces/activepieces |
| OpenAI Agents SDK | https://github.com/openai/openai-agents-python |
| Claude Agent SDK | https://github.com/anthropics/claude-agent-sdk-python |
| Google ADK | https://github.com/google/adk-python |
| CrewAI | https://github.com/crewAIInc/crewAI |
| AutoGen | https://github.com/microsoft/autogen |
| Temporal | https://temporal.io/ |
| Open Policy Agent | https://www.openpolicyagent.org/ |
| OpenTelemetry | https://opentelemetry.io/ |

---

## 25. MVP Scope

### 25.1 MVP Must Include

| Area | MVP Capability |
|---|---|
| UI | Chat UI, Workflow Builder, Agent Studio, MCP Studio, Template Library basic |
| Agents | Create/update/delete/debug/copy/deploy basic agents |
| MCP | Register/create/debug/deploy/copy basic MCP servers |
| Workflows | Create/update/delete/debug/deploy/copy workflows |
| Templates | Create/import/export templates |
| Runtime | Execute workflows with agents and MCP tools |
| Security | RBAC, tool permissions, approval for high-risk actions |
| Observability | Logs, run history, tool-call history, basic traces |
| Integrations | ArcGIS MCP, Database MCP, File/PDF MCP, Email MCP, Code/DevOps MCP using OpenHands SDK; Activepieces bridge as optional Phase 2 accelerator |
| Deployment | Docker Compose for dev, Kubernetes-ready architecture, CI/CD-ready packaging for agents/MCP servers/workflows |

### 25.2 MVP Exclusions

| Area | Exclusion |
|---|---|
| Full marketplace | Later phase |
| Advanced RPA desktop automation | Later phase |
| Full multi-cloud deployment automation | Later phase |
| Advanced policy language UI | Later phase |
| Large-scale external A2A federation | Later phase |

---

## 26. Roadmap

### Phase 1: Foundation

| Deliverable | Description |
|---|---|
| Core UI shell | Navigation, auth, projects, tenants |
| Chat UI | Prompt entry and responses |
| Asset model | Agents, MCP servers, workflows, templates |
| Basic Agent Studio | CRUD agents |
| Basic MCP Studio | Register/debug MCP servers |
| Basic Workflow Builder | Create visual workflows |
| Runtime executor | Execute simple workflows |
| Logging | Run history and tool-call logs |

### Phase 2: Lifecycle Management

| Deliverable | Description |
|---|---|
| Debug console | Step-by-step debugging |
| Version manager | Versions and rollback |
| Copy operations | Copy agents/MCP/workflows/templates |
| Template import/export | JSON/YAML/ZIP packages |
| Deployment manager | Dev/test/prod promotion |
| Test runner | Unit/integration test execution |
| Approval queue | Human approval workflow |

### Phase 3: Enterprise Governance

| Deliverable | Description |
|---|---|
| RBAC/ABAC | Enterprise permissions |
| Policy-as-code | OPA integration |
| Secret vault | Vault/key vault integration |
| Audit reports | Exportable audit evidence |
| Tool sandbox | Secure tool execution |
| Security scanner | Template/code/dependency/image scanning |

### Phase 4: Advanced Multi-Agent Platform

| Deliverable | Description |
|---|---|
| A2A gateway | External agent communication |
| Event bus | Async scalable agent events |
| Model control plane | Routing/fallback/cost tracking |
| Memory/RAG | Vector search and project memory |
| Meta-agents | Agent/MCP/workflow/template builders |
| Observability | OpenTelemetry traces/metrics/logs |

### Phase 5: Marketplace and Scale

| Deliverable | Description |
|---|---|
| Template marketplace | Internal/external templates |
| Enterprise connector library | More MCP servers |
| GitOps integration | Argo CD/Flux promotion |
| Multi-region deployment | HA/DR deployment |
| Advanced analytics | Usage, cost, reliability insights |

---

## 27. Example End-to-End Scenarios

### 27.1 Scenario: Create GIS Health Agent

```text
User: Create a GIS Health Monitoring Agent that checks Portal, Server, Data Store,
DB, logs, creates a report, and emails it every morning.
```

Expected flow:

1. Intent Router classifies as Agent + Workflow creation.
2. Platform Architect Agent drafts solution.
3. Agent Builder Agent creates GIS Health Agent.
4. MCP Builder/Registry maps ArcGIS, DB, Monitoring, File, Email MCP tools.
5. Workflow Builder Agent creates daily schedule workflow.
6. QA Agent creates tests.
7. Security Agent reviews tool permissions.
8. User reviews generated assets.
9. Deployment Agent deploys to dev.
10. Test Runner validates.
11. Approval Manager promotes to production.
12. Scheduler runs daily.

### 27.2 Scenario: Debug Failed MCP Server

```text
User: Debug the ArcGIS MCP server because portal_health is failing.
```

Expected flow:

1. Intent Router classifies as MCP debug.
2. MCP Studio opens server context.
3. MCP Debug Console runs test input.
4. Tool Validator checks schema.
5. Logs show error.
6. MCP Builder Agent proposes fix.
7. Test Runner validates fix.
8. Deployment Agent redeploys to dev.
9. User approves production deployment.

### 27.3 Scenario: Import Workflow Template

```text
User: Import this workflow template and deploy it for my GIS operations project.
```

Expected flow:

1. Template Manager receives package.
2. Import Manager validates manifest.
3. Security Scanner checks for secrets, risky nodes, malicious scripts.
4. Dependency Mapper checks required agents/MCP servers.
5. Missing assets are created or mapped.
6. Workflow is imported as draft.
7. User configures environment parameters.
8. Tests run.
9. Workflow deployed after approval.

---

## 28. Acceptance Criteria

The product is acceptable for MVP when:

| Criteria | Status Needed |
|---|---|
| Users can create/update/delete/debug/deploy/copy agents | Required |
| Users can create/update/delete/debug/deploy/copy MCP servers | Required |
| Users can create/update/delete/debug/deploy/copy workflows | Required |
| Users can create/import/export templates | Required |
| Users can draw workflows visually | Required |
| Workflows can execute agents and MCP tools | Required |
| Tool permissions are enforced | Required |
| High-risk actions require approval | Required |
| Runs produce logs and traces | Required |
| Assets are versioned | Required |
| Rollback is supported | Required |
| Secrets are never exported | Required |
| At least ArcGIS, Database, File/PDF, and Email MCP servers are supported | Required |
| Basic Docker Compose deployment works | Required |
| Kubernetes deployment design is documented | Required |

---

## 29. Risks and Mitigations

| Risk | Mitigation |
|---|---|
| Agents perform risky operations | Tool risk levels, approval gates, RBAC, sandbox |
| Prompt injection through documents/tools | Input filtering, tool isolation, policy enforcement |
| Secrets leak through templates/logs | Vault references, redaction, export scanner |
| MCP server compromise | Sandbox, network policy, image scan, least privilege |
| Workflows become hard to debug | Trace viewer, replay, node-level logs |
| Model costs grow | Model routing, budget limits, token tracking |
| Template imports are unsafe | Signature validation, schema validation, scanning |
| Complex deployment | Use Docker Compose for MVP and Kubernetes for production |
| Agent behavior inconsistent | Test suites, evaluations, deterministic settings for critical tasks |

---

## 30. Build Priorities

### Priority 1: Must Build First

1. Core database schema.
2. Auth/RBAC basic.
3. Asset CRUD for agents/MCP/workflows/templates.
4. Visual workflow builder.
5. Agent runtime basic.
6. MCP gateway and client manager basic.
7. ArcGIS MCP server prototype.
8. Database MCP server prototype.
9. File/PDF MCP server prototype.
10. Email MCP server prototype.
11. Workflow run engine basic.
12. Debug logs and run history.

### Priority 2: Production Readiness

1. Versioning and rollback.
2. Deployment manager.
3. Approval queue.
4. Test runner.
5. Template import/export.
6. Secret vault integration.
7. Policy engine.
8. Observability integration.

### Priority 3: Advanced Capabilities

1. A2A gateway.
2. Event bus.
3. Meta-agent automation.
4. Model control plane.
5. RAG/vector memory.
6. Marketplace.
7. GitOps.
8. Multi-region deployment.

---

## 31. Example Asset Specifications

### 31.1 Agent Specification Example

```yaml
id: agent_gis_health_monitor
name: GIS Health Monitoring Agent
role: GIS Operations Specialist
status: draft
model:
  provider: local_or_cloud_router
  preferred_model: gpt-4.1-or-local-llm
  fallback_model: local-deepseek
memory:
  scope: project
  retention_days: 90
permissions:
  allowed_mcp_servers:
    - arcgis_mcp_server
    - database_mcp_server
    - monitoring_mcp_server
    - file_mcp_server
    - email_mcp_server
  allowed_tools:
    - portal_health
    - list_services
    - check_datastore
    - check_db_health
    - query_logs
    - create_report
    - send_email
risk_policy:
  max_risk_level_without_approval: L1
instructions: |
  You are responsible for checking GIS platform health.
  Always summarize findings clearly.
  Never restart or delete services without approval.
```

### 31.2 MCP Server Specification Example

```yaml
id: arcgis_mcp_server
name: ArcGIS Enterprise MCP Server
runtime: python
framework: fastmcp
transport: http
environment: dev
auth:
  method: oauth_or_service_account
  secret_refs:
    - vault://arcgis/portal_admin_client_secret
tools:
  - name: portal_health
    risk_level: L0
    description: Check ArcGIS Portal health.
  - name: list_services
    risk_level: L0
    description: List ArcGIS Server services.
  - name: restart_service
    risk_level: L2
    description: Restart an ArcGIS Server service.
  - name: delete_item
    risk_level: L3
    description: Delete an ArcGIS Portal item.
network_policy:
  allowed_hosts:
    - portal.company.com
    - server.company.com
```

### 31.3 Workflow Specification Example

```yaml
id: workflow_daily_gis_health
name: Daily GIS Health Report
trigger:
  type: schedule
  cron: "0 7 * * *"
variables:
  admin_email: gis.admin@company.com
nodes:
  - id: start
    type: trigger
  - id: gis_check
    type: agent
    agent_id: agent_gis_health_monitor
  - id: db_check
    type: mcp_tool
    mcp_server_id: database_mcp_server
    tool: check_db_health
  - id: report
    type: agent
    agent_id: report_agent
  - id: approval
    type: approval
    condition: "risk_level >= L2 or failed_services > 0"
  - id: email
    type: mcp_tool
    mcp_server_id: email_mcp_server
    tool: send_email
edges:
  - from: start
    to: gis_check
  - from: gis_check
    to: db_check
  - from: db_check
    to: report
  - from: report
    to: approval
  - from: approval
    to: email
retry_policy:
  max_attempts: 3
  backoff_seconds: 30
```

---

## 32. Reference Links

These are suggested reference technologies and standards for implementation:

| Topic | URL |
|---|---|
| Model Context Protocol | https://modelcontextprotocol.io/ |
| MCP Architecture | https://modelcontextprotocol.io/docs/learn/architecture |
| MCP Specification | https://modelcontextprotocol.io/specification/2025-11-25 |
| FastMCP | https://gofastmcp.com/ |
| A2A Protocol | https://a2a-protocol.org/latest/ |
| React Flow | https://reactflow.dev/ |
| Temporal | https://temporal.io/ |
| Open Policy Agent | https://openpolicyagent.org/ |
| OpenTelemetry | https://opentelemetry.io/ |
| LangGraph | https://langchain-ai.github.io/langgraph/ |
| Qdrant | https://qdrant.tech/ |
| Kubernetes | https://kubernetes.io/ |
| Argo CD | https://argo-cd.readthedocs.io/ |
| Playwright | https://playwright.dev/ |

---

## 33. Final Product Statement

This platform is not just an AI chatbot and not just a workflow builder.

It is a complete enterprise AI automation platform with:

```text
Design-Time Control Plane
+ Runtime Control Plane
+ Multi-Agent Orchestration
+ MCP Tool Integration
+ A2A Communication
+ Visual Workflow Builder
+ Full Lifecycle Management
+ Templates and Reusability
+ Security and Governance
+ Observability and Audit
+ Deployment and DevSecOps
```

The final system should enable users to build and operate AI-powered enterprise automations safely, visually, and repeatably.

