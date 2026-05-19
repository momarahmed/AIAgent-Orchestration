"""EAMCP — Enterprise AI + MCP + Multi-Agent Platform Python SDK.

Phase 5 / v1.0 — covers the full asset lifecycle (agents, MCP servers,
workflows, runs, templates, marketplace, approvals, policies, observability)
plus CI/CD friendly helpers.

Quick start::

    from eamcp import Client
    c = Client(base_url="http://127.0.0.1:8089", token="...")
    c.agents.list()
    c.marketplace.search("daily GIS health report")
"""
from .client import Client, ApiError

__all__ = ["Client", "ApiError"]
__version__ = "1.0.0"
