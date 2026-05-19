package eamcp.tool_call

# Phase 2 baseline policy — mirrors the built-in fallback in OpaPolicyService.
# Rule: an agent may not invoke any tool whose risk level exceeds the agent's
# max_risk_level_without_approval unless an Approval already exists.
# Risk ladder L0 < L1 < L2 < L3 < L4.

default allow := false
default requires_approval := false
default reason := ""

risk_rank := {"L0": 0, "L1": 1, "L2": 2, "L3": 3, "L4": 4}

agent_max := risk_rank[input.agent.max_risk_level_without_approval]
tool_risk := risk_rank[input.tool.risk_level]

allowlist_ok {
  count(input.agent.allowed_tools) == 0
}
allowlist_ok {
  some i
  input.agent.allowed_tools[i] == input.tool.id
}

allow {
  allowlist_ok
  tool_risk <= agent_max
}

requires_approval {
  allowlist_ok
  tool_risk > agent_max
}

reason := sprintf("tool risk %s exceeds agent max %s", [input.tool.risk_level, input.agent.max_risk_level_without_approval]) {
  tool_risk > agent_max
}

reason := "tool not in agent allowlist" {
  not allowlist_ok
}
