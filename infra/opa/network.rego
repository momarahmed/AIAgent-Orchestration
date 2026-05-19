package eamcp.network

# Phase 3 — Network access policies.
# Enforces per-MCP-server egress allowlists.

default allow := false
default reason := ""

# Dev environment is permissive
allow {
    input.environment == "dev"
}

# Check if host is in the allowlist
allow {
    some i
    input.allowlist[i].host == input.target_host
    port_matches(input.allowlist[i], input.target_port)
    input.allowlist[i].is_active == true
}

# Wildcard host match
allow {
    some i
    input.allowlist[i].host == "*"
    input.allowlist[i].is_active == true
}

port_matches(rule, target_port) {
    rule.port == null
}

port_matches(rule, target_port) {
    rule.port == target_port
}

reason := sprintf("Host %s:%d is not in the egress allowlist for MCP server %s in %s", [input.target_host, input.target_port, input.mcp_server_slug, input.environment]) {
    not allow
}
