"use client";

import {
  ReactNode,
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { localesApi } from "@/lib/api";

type Locale = { code: string; name: string; rtl: boolean; is_active: boolean };
type Dictionary = Record<string, Record<string, string>>;

type I18nContextValue = {
  locale: string;
  available: Locale[];
  isRtl: boolean;
  t: (key: string, fallback?: string) => string;
  setLocale: (code: string) => void;
  ready: boolean;
};

const I18nContext = createContext<I18nContextValue | null>(null);
const LOCALE_KEY = "eamcp_locale";

const FALLBACK_AVAILABLE: Locale[] = [
  { code: "en", name: "English", rtl: false, is_active: true },
  { code: "ar", name: "العربية", rtl: true, is_active: true },
];

const FALLBACK_DICTS: Record<string, Dictionary> = {
  en: {
    common: {
      welcome: "Welcome",
      dashboard: "Dashboard",
      agents: "Agents",
      mcp_servers: "MCP Servers",
      workflows: "Workflows",
      runs: "Runs",
      approvals: "Approvals",
      deployments: "Deployments",
      templates: "Templates",
      marketplace: "Marketplace",
      analytics: "Analytics",
      cost_governance: "Cost Governance",
      compliance: "Compliance",
      continuous_security: "Continuous Security",
      gitops: "GitOps",
      operations: "Operations",
      search: "Search",
      install: "Install",
      publish: "Publish",
      rate: "Rate",
      save: "Save",
      cancel: "Cancel",
      export: "Export",
    },
    marketplace: {
      title: "Template Marketplace",
      search_placeholder: "Find templates with natural language…",
    },
  },
  ar: {
    common: {
      welcome: "مرحبا",
      dashboard: "لوحة التحكم",
      agents: "الوكلاء",
      mcp_servers: "خوادم MCP",
      workflows: "مسارات العمل",
      runs: "التشغيلات",
      approvals: "الموافقات",
      deployments: "النشر",
      templates: "القوالب",
      marketplace: "السوق",
      analytics: "التحليلات",
      cost_governance: "حوكمة التكلفة",
      compliance: "الامتثال",
      continuous_security: "الأمان المستمر",
      gitops: "GitOps",
      operations: "العمليات",
      search: "بحث",
      install: "تثبيت",
      publish: "نشر",
      rate: "تقييم",
      save: "حفظ",
      cancel: "إلغاء",
      export: "تصدير",
    },
    marketplace: {
      title: "سوق القوالب",
      search_placeholder: "ابحث عن القوالب بلغة طبيعية…",
    },
  },
};

export function I18nProvider({ children }: { children: ReactNode }) {
  const [locale, setLocaleState] = useState<string>("en");
  const [available, setAvailable] = useState<Locale[]>(FALLBACK_AVAILABLE);
  const [dict, setDict] = useState<Dictionary>(FALLBACK_DICTS.en);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (typeof window === "undefined") return;
    const stored = window.localStorage.getItem(LOCALE_KEY);
    if (stored) setLocaleState(stored);

    localesApi
      .list()
      .then((res: any) => {
        if (Array.isArray(res?.data) && res.data.length > 0) setAvailable(res.data);
      })
      .catch(() => {});
  }, []);

  useEffect(() => {
    setReady(false);
    localesApi
      .dictionary(locale)
      .then((res: any) => {
        const translations: Dictionary | undefined = res?.translations;
        const merged = mergeDicts(FALLBACK_DICTS[locale] ?? FALLBACK_DICTS.en, translations ?? {});
        setDict(merged);
      })
      .catch(() => {
        setDict(FALLBACK_DICTS[locale] ?? FALLBACK_DICTS.en);
      })
      .finally(() => setReady(true));

    if (typeof document !== "undefined") {
      const localeMeta = available.find((l) => l.code === locale);
      document.documentElement.dir = localeMeta?.rtl ? "rtl" : "ltr";
      document.documentElement.lang = locale;
    }
  }, [locale, available]);

  const setLocale = useCallback((code: string) => {
    if (typeof window !== "undefined") window.localStorage.setItem(LOCALE_KEY, code);
    setLocaleState(code);
  }, []);

  const t = useCallback(
    (key: string, fallback?: string) => {
      const [ns, ...rest] = key.split(".");
      const k = rest.join(".");
      const value = dict?.[ns]?.[k];
      return value ?? fallback ?? key;
    },
    [dict],
  );

  const isRtl = useMemo(
    () => available.find((l) => l.code === locale)?.rtl ?? false,
    [available, locale],
  );

  const value: I18nContextValue = { locale, available, isRtl, t, setLocale, ready };
  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

function mergeDicts(base: Dictionary, override: Dictionary): Dictionary {
  const out: Dictionary = { ...base };
  for (const ns of Object.keys(override)) {
    out[ns] = { ...(base[ns] ?? {}), ...override[ns] };
  }
  return out;
}

export function useI18n(): I18nContextValue {
  const ctx = useContext(I18nContext);
  if (!ctx) {
    return {
      locale: "en",
      available: FALLBACK_AVAILABLE,
      isRtl: false,
      t: (k, fb) => fb ?? k,
      setLocale: () => {},
      ready: true,
    };
  }
  return ctx;
}
