import React, { useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';

const COMPONENT_REGISTRY = [
  { type: 'root', label: 'Root', description: 'Page tree root' },
  { type: 'hero', label: 'Hero', description: 'Title / subtitle; syncs to section payload' },
  { type: 'cta', label: 'CTA', description: 'Button label + URL; syncs to section payload' },
  { type: 'product_grid', label: 'Product grid', binding: 'erp.products.list' },
  { type: 'filters', label: 'Filters', binding: 'erp.categories.tree' },
  { type: 'block', label: 'Block', description: 'Generic section shell' },
  { type: 'footer', label: 'Footer', description: 'Footer region' },
];

function NodeList({ schema }) {
  const children = schema?.root?.children ?? [];
  return (
    <ul className="list-group small">
      {children.map((n) => (
        <li key={n.id} className="list-group-item d-flex justify-content-between align-items-center">
          <code>{n.type}</code>
          <span className="text-muted">{n.props?.section_key ?? ''}</span>
        </li>
      ))}
    </ul>
  );
}

function App() {
  const root = document.getElementById('storefront-studio-root');
  const base = root?.dataset?.base ?? '';
  const pageType =
    new URLSearchParams(window.location.search).get('page') ?? root?.dataset?.page ?? 'home';
  const previewUrl = root?.dataset?.preview ?? '/store';

  const [schema, setSchema] = useState(null);
  const [catalog, setCatalog] = useState(null);
  const [cart, setCart] = useState(null);
  const [uomoPresets, setUomoPresets] = useState(null);
  const [layoutDiff, setLayoutDiff] = useState(null);
  const [status, setStatus] = useState('');

  const xsrf = () => {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
  };

  const load = () => {
    setStatus('Loading…');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then((d) => {
        setSchema(d.layout_schema);
        setStatus('');
      })
      .catch(() => setStatus('Failed to load layout'));
  };

  useEffect(() => {
    load();
  }, [pageType, base]);

  useEffect(() => {
    fetch(`${base}/catalog/products?per_page=8`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then(setCatalog)
      .catch(() => {});
  }, [base]);

  useEffect(() => {
    fetch(`${base}/catalog/cart-summary`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then(setCart)
      .catch(() => {});
  }, [base]);

  useEffect(() => {
    fetch(`${base}/presets/uomo`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then(setUomoPresets)
      .catch(() => {});
  }, [base]);

  useEffect(() => {
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout/diff`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then(setLayoutDiff)
      .catch(() => {});
  }, [base, pageType, schema]);

  const save = () => {
    const ta = document.getElementById('studio-json-editor');
    if (!ta) return;
    let parsed;
    try {
      parsed = JSON.parse(ta.value);
    } catch {
      setStatus('Invalid JSON');
      return;
    }
    setStatus('Saving…');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout`, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf(),
      },
      body: JSON.stringify({ layout_schema: parsed }),
    })
      .then((r) => {
        if (!r.ok) throw new Error('save');
        return r.json();
      })
      .then(() => {
        setStatus('Saved');
        load();
      })
      .catch(() => setStatus('Save failed'));
  };

  const importJson = (file) => {
    const reader = new FileReader();
    reader.onload = () => {
      try {
        const parsed = JSON.parse(String(reader.result));
        const ta = document.getElementById('studio-json-editor');
        if (ta) ta.value = JSON.stringify(parsed, null, 2);
        setStatus('Imported (not saved yet)');
      } catch {
        setStatus('Invalid import file');
      }
    };
    reader.readAsText(file);
  };

  const postImportSave = () => {
    const ta = document.getElementById('studio-json-editor');
    if (!ta) return;
    let parsed;
    try {
      parsed = JSON.parse(ta.value);
    } catch {
      setStatus('Invalid JSON');
      return;
    }
    setStatus('Importing…');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout/import`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf(),
      },
      body: JSON.stringify({ layout_schema: parsed }),
    })
      .then((r) => {
        if (!r.ok) throw new Error('import');
        return r.json();
      })
      .then(() => {
        setStatus('Imported & saved');
        load();
      })
      .catch(() => setStatus('Import save failed'));
  };

  useEffect(() => {
    const ta = document.getElementById('studio-json-editor');
    if (ta && schema) {
      ta.value = JSON.stringify(schema, null, 2);
    }
  }, [schema]);

  const exportJson = () => {
    const ta = document.getElementById('studio-json-editor');
    if (!ta?.value) return;
    const blob = new Blob([ta.value], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `layout-${pageType}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  return (
    <div className="row">
      <div className="col-lg-5">
        <h5 className="mb-3">Page schema (v2)</h5>
        <textarea
          id="studio-json-editor"
          className="form-control font-monospace mb-2"
          rows={16}
          defaultValue="{}"
        />
        <button type="button" className="btn btn-primary me-2" onClick={save}>
          Save layout
        </button>
        <button type="button" className="btn btn-outline-secondary me-2" onClick={load}>
          Reload
        </button>
        <button type="button" className="btn btn-outline-info me-2" onClick={exportJson}>
          Export JSON
        </button>
        <label className="btn btn-outline-dark mb-0 me-2">
          Import file
          <input
            type="file"
            accept="application/json,.json"
            className="d-none"
            onChange={(e) => {
              const f = e.target.files?.[0];
              if (f) importJson(f);
              e.target.value = '';
            }}
          />
        </label>
        <button type="button" className="btn btn-outline-success" onClick={postImportSave}>
          Save imported JSON
        </button>
        {status ? <span className="ms-2 text-muted small">{status}</span> : null}
        {layoutDiff ? (
          <p className="small text-muted mt-2 mb-0">
            Publish diff:{' '}
            <strong>{layoutDiff.dirty ? 'draft ≠ published' : 'matches published (or no publish yet)'}</strong>
          </p>
        ) : null}
        <div className="mt-3">
          <h6>Nodes</h6>
          {schema ? <NodeList schema={schema} /> : <p className="text-muted small">…</p>}
        </div>
        <div className="mt-3">
          <h6>Component registry</h6>
          <ul className="list-group small">
            {COMPONENT_REGISTRY.map((c) => (
              <li key={c.type} className="list-group-item">
                <code>{c.type}</code> — {c.label}
                {c.binding ? (
                  <span className="text-muted d-block">binding: {c.binding}</span>
                ) : null}
                {c.description ? (
                  <span className="text-muted d-block">{c.description}</span>
                ) : null}
              </li>
            ))}
          </ul>
        </div>
      </div>
      <div className="col-lg-7">
        <h5 className="mb-3">Store preview</h5>
        <iframe title="store-preview" src={previewUrl} className="w-100 border rounded" style={{ minHeight: 520 }} />
        <div className="row mt-3">
          <div className="col-md-6">
            <h6>ERP catalog (sample)</h6>
            {catalog?.data ? (
              <ul className="list-group small">
                {catalog.data.map((p) => (
                  <li key={p.id} className="list-group-item">
                    {p.name} — {p.selling_price}
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-muted small">No catalog data</p>
            )}
          </div>
          <div className="col-md-6">
            <h6>Cart summary (session)</h6>
            {cart ? (
              <div className="small">
                <p className="mb-1">
                  Lines: <strong>{cart.count}</strong> · Subtotal: <strong>{cart.subtotal?.toFixed?.(2) ?? cart.subtotal}</strong>
                </p>
                <ul className="list-group list-group-flush">
                  {(cart.lines ?? []).map((l) => (
                    <li key={l.product_id} className="list-group-item px-0 py-1">
                      {l.name} × {l.qty}
                    </li>
                  ))}
                </ul>
              </div>
            ) : (
              <p className="text-muted small">—</p>
            )}
          </div>
        </div>
        <h6 className="mt-3">Uomo navbar presets (fragments)</h6>
        {uomoPresets?.navbar_fragments?.length ? (
          <ul className="list-group small">
            {uomoPresets.navbar_fragments.map((p) => (
              <li key={p.key} className="list-group-item d-flex justify-content-between">
                <code>{p.key}</code>
                <span className={p.has_html ? 'text-success' : 'text-warning'}>
                  {p.has_html ? 'ready' : 'missing'}
                </span>
              </li>
            ))}
          </ul>
        ) : (
          <p className="text-muted small">
            Run <code>php artisan storefront:extract-uomo-navbar-fragments</code> to generate HTML under{' '}
            <code>storage/app/uomo-fragments/</code>.
          </p>
        )}
      </div>
    </div>
  );
}

const el = document.getElementById('storefront-studio-root');
if (el) {
  createRoot(el).render(<App />);
}
