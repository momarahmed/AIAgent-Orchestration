# Phase 4 — A2A Federation Policies (PRD §13.2)
# Allowlist external A2A partners and govern direct agent message flow.
package eamcp.a2a

default allow = false

# Internal-to-internal A2A always allowed (under tenant)
allow {
  input.from_agent.scope == "internal"
  input.to_agent.scope == "internal"
  input.from_agent.tenant_id == input.to_agent.tenant_id
}

# External calls only to allowlisted partners
allow {
  input.from_agent.scope == "internal"
  input.to_agent.scope == "external"
  data.a2a_partners[input.to_agent.partner_id].status == "active"
  data.a2a_partners[input.to_agent.partner_id].tenant_id == input.from_agent.tenant_id
}

# Inbound external messages must come from a registered partner
allow {
  input.from_agent.scope == "external"
  input.to_agent.scope == "internal"
  data.a2a_partners[input.from_agent.partner_id].status == "active"
  not data.a2a_partners[input.from_agent.partner_id].quarantined
}

# Always deny if prompt-injection detected on payload
deny[msg] {
  input.injection_flags[_] == "critical"
  msg := "A2A payload blocked by prompt-injection guardrail"
}
