#!/usr/bin/env node
// EAMCP Platform CLI (Phase 5 / v1.0).
//
// Usage:
//   eamcp login <email> <password>          # stores token in ~/.eamcp/token
//   eamcp agents list
//   eamcp workflows run <id> --input '{...}'
//   eamcp marketplace search "natural language query"
//   eamcp compliance export --frameworks SOC2,ISO27001 --from 2026-01-01 --to 2026-03-31
//   eamcp gitops failover-drill us-east-1 eu-west-1
//
// Env:
//   EAMCP_BASE_URL  (default http://127.0.0.1:8089)
//   EAMCP_TOKEN     (overrides stored token)
//
import { Client } from "@eamcp/sdk";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";

const TOKEN_DIR  = path.join(os.homedir(), ".eamcp");
const TOKEN_FILE = path.join(TOKEN_DIR, "token");

function loadToken() {
  if (process.env.EAMCP_TOKEN) return process.env.EAMCP_TOKEN;
  try { return fs.readFileSync(TOKEN_FILE, "utf8").trim(); }
  catch { return undefined; }
}
function saveToken(t) {
  fs.mkdirSync(TOKEN_DIR, { recursive: true });
  fs.writeFileSync(TOKEN_FILE, t, { mode: 0o600 });
}
function parseArgs(argv) {
  const positional = [];
  const flags = {};
  for (let i = 0; i < argv.length; i++) {
    const a = argv[i];
    if (a.startsWith("--")) {
      const key = a.slice(2);
      const val = argv[i + 1] && !argv[i + 1].startsWith("--") ? argv[++i] : true;
      flags[key] = val;
    } else positional.push(a);
  }
  return { positional, flags };
}
function fail(msg, code = 1) {
  console.error(`eamcp: ${msg}`);
  process.exit(code);
}
function print(data) {
  if (data === undefined) return;
  if (typeof data === "string") console.log(data);
  else console.log(JSON.stringify(data, null, 2));
}

async function main() {
  const [, , cmd, sub, ...rest] = process.argv;
  if (!cmd || cmd === "--help" || cmd === "-h") {
    console.log("Usage: eamcp <command> [subcommand] [args] [--flag value]");
    console.log("Commands: login, health, agents, workflows, runs, templates, approvals, marketplace, analytics, compliance, gitops");
    return;
  }
  const c = new Client({ token: loadToken() });
  const { positional, flags } = parseArgs(rest);
  const parseJson = (v) => (typeof v === "string" ? JSON.parse(v) : v);

  switch (cmd) {
    case "login": {
      const [email, password] = positional;
      if (!email || !password) fail("usage: eamcp login <email> <password>");
      const token = await c.login(email, password, flags.tenant ? Number(flags.tenant) : undefined);
      saveToken(token);
      print({ ok: true, message: "Logged in; token stored." });
      return;
    }
    case "health": print(await c.health()); return;
    case "agents": {
      if (sub === "list")   return print(await c.agents.list());
      if (sub === "get")    return print(await c.agents.get(Number(positional[0])));
      if (sub === "create") return print(await c.agents.create(parseJson(flags.payload ?? "{}")));
      fail("Unknown agents subcommand");
    }
    case "workflows": {
      if (sub === "list")   return print(await c.workflows.list());
      if (sub === "get")    return print(await c.workflows.get(Number(positional[0])));
      if (sub === "create") return print(await c.workflows.create(parseJson(flags.payload ?? "{}")));
      if (sub === "run")    return print(await c.workflows.run(Number(positional[0]), parseJson(flags.input ?? "{}")));
      fail("Unknown workflows subcommand");
    }
    case "runs": {
      if (sub === "list") return print(await c.runs.list());
      if (sub === "get")  return print(await c.runs.get(Number(positional[0])));
      fail("Unknown runs subcommand");
    }
    case "templates": {
      if (sub === "list")    return print(await c.templates.list());
      if (sub === "export")  return print(await c.templates.exportJson(Number(positional[0])));
      if (sub === "import")  return print(await c.templates.importJson(parseJson(flags.payload ?? "{}")));
      fail("Unknown templates subcommand");
    }
    case "approvals": {
      if (sub === "list")    return print(await c.approvals.list());
      if (sub === "approve") return print(await c.approvals.approve(Number(positional[0]), flags.comment));
      if (sub === "reject")  return print(await c.approvals.reject(Number(positional[0]),  flags.comment));
      fail("Unknown approvals subcommand");
    }
    case "marketplace": {
      if (sub === "list")    return print(await c.marketplace.list());
      if (sub === "search")  return print(await c.marketplace.search(positional.join(" ")));
      if (sub === "install") return print(await c.marketplace.install(Number(positional[0]), parseJson(flags.payload ?? "{}")));
      if (sub === "publish") return print(await c.marketplace.publish(parseJson(flags.payload ?? "{}")));
      fail("Unknown marketplace subcommand");
    }
    case "analytics": {
      if (sub === "usage")            return print(await c.analytics.usage(Number(flags.days ?? 30)));
      if (sub === "cost")             return print(await c.analytics.cost(Number(flags.days ?? 30)));
      if (sub === "reliability")      return print(await c.analytics.reliability(Number(flags.days ?? 30)));
      if (sub === "template-adoption") return print(await c.analytics.templateAdoption(Number(flags.days ?? 90)));
      fail("Unknown analytics subcommand");
    }
    case "compliance": {
      if (sub === "frameworks") return print(await c.compliance.frameworks());
      if (sub === "exports")    return print(await c.compliance.exports());
      if (sub === "export")     return print(await c.compliance.create({
        frameworks: String(flags.frameworks ?? "SOC2").split(","),
        period_start: flags.from,
        period_end:   flags.to,
        format: flags.format ?? "zip",
      }));
      fail("Unknown compliance subcommand");
    }
    case "gitops": {
      if (sub === "environments") return print(await c.gitops.environments());
      if (sub === "sync")         return print(await c.gitops.sync(Number(positional[0])));
      if (sub === "drift")        return print(await c.gitops.drift(Number(positional[0])));
      if (sub === "regions")      return print(await c.gitops.regions());
      if (sub === "failover-drill") return print(await c.gitops.failoverDrill(positional[0], positional[1]));
      fail("Unknown gitops subcommand");
    }
    default:
      fail(`Unknown command: ${cmd}`);
  }
}

main().catch((e) => {
  if (e?.payload) console.error(JSON.stringify(e.payload, null, 2));
  console.error(String(e.message ?? e));
  process.exit(1);
});
