package eamcp.marketplace

# Phase 5 — Marketplace install policy.
#
# Inputs:
#   input.environment   string (dev|test|staging|prod)
#   input.signed        bool    – whether the listing carries a valid signature
#   input.visibility    string  (internal|trusted|external)
#   input.tenant_id     number
#   input.rating_avg    number
#   input.category      string
#
# Defaults align with PRD Section 21.3:
#   - Unsigned external template imports are denied in staging/prod by default.
#   - External (cross-org) listings with rating_avg < 3.5 require Security Approver.

default install = {
  "allowed": true,
  "reason": "ok",
  "requires_approval": false,
}

install = decision {
  not input.signed
  startswith_env(input.environment, "staging")
  decision := deny_msg("Unsigned templates cannot be installed in staging")
}

install = decision {
  not input.signed
  startswith_env(input.environment, "prod")
  decision := deny_msg("Unsigned templates cannot be installed in production")
}

install = decision {
  input.visibility == "external"
  input.rating_avg < 3.5
  decision := warn_msg("External template with low rating requires Security Approver", true)
}

install = decision {
  input.category == "mcp"
  input.environment == "prod"
  not input.signed
  decision := deny_msg("MCP marketplace installs in prod require a signed listing")
}

# helpers
deny_msg(reason) = {
  "allowed": false,
  "reason": reason,
  "requires_approval": false,
}

warn_msg(reason, approval) = {
  "allowed": true,
  "reason": reason,
  "requires_approval": approval,
}

startswith_env(env, prefix) {
  env == prefix
}
