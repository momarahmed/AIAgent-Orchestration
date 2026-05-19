# Phase 4 — Memory Scoping Policy (PRD §9.2 + §17)
# Enforces the agent.memory_scope field at retrieval-time.
package eamcp.memory

default allow_read = false

allow_read {
  input.scope == "none"
  # never recall
  false
}

allow_read {
  input.scope == "session"
  input.session_id == input.target.session_id
}

allow_read {
  input.scope == "project"
  input.project_id == input.target.project_id
  input.tenant_id == input.target.tenant_id
}

allow_read {
  input.scope == "tenant"
  input.tenant_id == input.target.tenant_id
}

deny[msg] {
  input.scope == "project"
  input.tenant_id != input.target.tenant_id
  msg := "Cross-tenant memory access blocked"
}
