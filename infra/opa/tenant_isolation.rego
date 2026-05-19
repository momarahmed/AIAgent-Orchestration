package eamcp.tenant_isolation

# Phase 3 — Tenant isolation policies.
# Prevents cross-tenant access at the policy engine level.

default allow := false
default reason := ""

# Same tenant access
allow {
    input.user_tenant_id == input.resource_tenant_id
}

# Platform-wide admin override
allow {
    input.user_role == "platform_owner"
}

# System resources (no tenant) are accessible to all authenticated users
allow {
    input.resource_tenant_id == null
}

reason := sprintf("Cross-tenant access denied: user tenant %d != resource tenant %d", [input.user_tenant_id, input.resource_tenant_id]) {
    not allow
}
