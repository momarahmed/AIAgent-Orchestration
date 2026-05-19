# Phase 4 — Meta-Agent Autonomy Policy (PRD §12.2)
# Caps what a meta-agent can do without human approval.
package eamcp.meta_agent

default allow_action = false

# Always allow read-only design actions (architect/documentation/migration draft).
allow_action {
  input.action.category == "design"
}

# Allow dev deployments without approval if security scan passed.
allow_action {
  input.action.category == "deploy"
  input.action.environment == "dev"
  input.action.security_scan_status == "passed"
}

# Production deploys ALWAYS need approval, regardless of meta-agent.
deny[msg] {
  input.action.category == "deploy"
  input.action.environment == "production"
  not input.action.has_human_approval
  msg := "Production deployment requires human approval"
}

# Block meta-agent from granting admin tool access automatically.
deny[msg] {
  input.action.category == "grant"
  input.action.permission == "admin"
  msg := "Meta-agent cannot grant admin access without approval"
}

# Budget guard — meta-agent cannot exceed per-run token budget.
deny[msg] {
  input.action.estimated_cost_usd > input.budget.run_cap_usd
  msg := sprintf("Meta-agent run would cost $%.2f exceeding cap of $%.2f", [input.action.estimated_cost_usd, input.budget.run_cap_usd])
}
