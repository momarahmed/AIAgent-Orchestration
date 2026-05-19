package eamcp.deployment

# Phase 3 — Deployment promotion policies.
# Governs which environments require approvals and from whom.

default allow := false
default requires_approval := false
default required_approvers := []
default reason := ""

# Dev deployments are always allowed
allow {
    input.environment == "dev"
}

# Test deployments allowed for builders+
allow {
    input.environment == "test"
    input.user.role_level >= 30
}

# Staging requires builder+ role and scans must pass
allow {
    input.environment == "staging"
    input.user.role_level >= 30
    input.scans_passed == true
}

# Production requires approval
requires_approval {
    input.environment == "prod"
}

# Production ArcGIS MCP requires both Security Approver and Platform Owner
required_approvers := ["security_approver", "platform_owner"] {
    input.environment == "prod"
    input.asset_type == "mcp_server"
    input.asset_slug == "arcgis-mcp"
}

# Production deployments require at least admin approval
required_approvers := ["admin"] {
    input.environment == "prod"
    not input.asset_slug == "arcgis-mcp"
}

# Staging requires publisher+ approval
required_approvers := ["publisher"] {
    input.environment == "staging"
    input.user.role_level < 50
}

reason := "Production deployments require approval" {
    input.environment == "prod"
}

reason := "Security scans must pass before staging/prod promotion" {
    input.environment == "staging"
    not input.scans_passed
}

reason := sprintf("Insufficient role level %d for %s deployment", [input.user.role_level, input.environment]) {
    input.environment == "test"
    input.user.role_level < 30
}
