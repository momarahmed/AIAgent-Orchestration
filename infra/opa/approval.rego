package eamcp.approval

# Phase 3 — Approval requirement matrix (PRD Section 21.3).
# Determines which actions require human approval and from whom.

default requires_approval := false
default approver_roles := []
default reason := ""

risk_rank := {"L0": 0, "L1": 1, "L2": 2, "L3": 3, "L4": 4}

# L2+ tool calls always require approval
requires_approval {
    input.action == "tool_call"
    risk_rank[input.risk_level] >= 2
}

approver_roles := ["admin", "security_approver"] {
    input.action == "tool_call"
    risk_rank[input.risk_level] >= 3
}

approver_roles := ["builder", "admin"] {
    input.action == "tool_call"
    risk_rank[input.risk_level] == 2
}

# External email recipients require approval
requires_approval {
    input.action == "send_email"
    input.has_external_recipients == true
}

approver_roles := ["admin", "publisher"] {
    input.action == "send_email"
    input.has_external_recipients == true
}

# SQL write operations require DBA approval
requires_approval {
    input.action == "run_safe_query"
    input.sql_contains_write == true
}

approver_roles := ["admin", "security_approver"] {
    input.action == "run_safe_query"
    input.sql_contains_write == true
}

# Production deployments (handled by deployment.rego too, cross-referenced here)
requires_approval {
    input.action == "deploy"
    input.environment == "prod"
}

# Template imports from external sources
requires_approval {
    input.action == "template_import"
    input.source == "external"
}

reason := sprintf("Action %s with risk %s requires approval", [input.action, input.risk_level]) {
    requires_approval
}
