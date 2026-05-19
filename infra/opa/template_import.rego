package eamcp.template_import

# Phase 3 — Template import policies.
# Validates templates before import, checks for secrets and policy compliance.

default allow := false
default requires_scan := true
default reason := ""

# Internal templates (from same tenant) are allowed
allow {
    input.source == "internal"
    input.tenant_id == input.template_tenant_id
}

# External templates require security scan
allow {
    input.source == "external"
    input.secret_scan_passed == true
    input.code_scan_passed == true
}

# Templates with no scan results are blocked
reason := "Template must pass secret and code scans before import" {
    input.source == "external"
    not input.secret_scan_passed
}

reason := "Template contains detected secrets — remove before import" {
    input.source == "external"
    input.secret_scan_passed == false
    input.secrets_found > 0
}
