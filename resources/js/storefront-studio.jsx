import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const COMPONENT_REGISTRY = [
  { type: 'hero', label: 'Hero', category: 'Sections', defaults: { title: 'Build your storefront', subtitle: 'Visual commerce, no reload.' } },
  { type: 'product_grid', label: 'Product Grid', category: 'Sections', defaults: { title: 'Featured products', columns: 4, source: 'erp.products.list' } },
  { type: 'slider', label: 'Slider', category: 'Sections', defaults: { title: 'Top picks', autoplay: true } },
  { type: 'banner', label: 'Banner', category: 'Blocks', defaults: { text: 'Free shipping over $50', tone: 'info' } },
  { type: 'cta', label: 'CTA', category: 'Blocks', defaults: { label: 'Shop now', href: '/shop' } },
  { type: 'product_card', label: 'Product Card', category: 'Blocks', defaults: { show_price: true, show_badge: true } },
];

const DEVICE_PRESETS = {
  desktop: { label: 'Desktop', width: '100%' },
  tablet: { label: 'Tablet', width: 920 },
  mobile: { label: 'Mobile', width: 430 },
};

const THEME_PRESETS = {
  modern: { label: 'Modern', color: '#111827', bg: '#ffffff', accent: '#2563eb', radius: 14, spacing: 16 },
  dark: { label: 'Dark', color: '#f3f4f6', bg: '#111827', accent: '#34d399', radius: 14, spacing: 16 },
  warm: { label: 'Warm', color: '#422006', bg: '#fffbeb', accent: '#ea580c', radius: 12, spacing: 14 },
};

const MOTION_PRESETS = ['none', 'fade-up', 'stagger-grid', 'parallax-soft'];

function createNode(type) {
  const base = COMPONENT_REGISTRY.find((c) => c.type === type);
  const suffix = Math.random().toString(36).slice(2, 8);
  return {
    id: `${type}_${Date.now()}_${suffix}`,
    type,
    props: { ...(base?.defaults ?? {}) },
    meta: {
      variant: 'default',
      responsive: { mobile: {}, tablet: {}, desktop: {} },
      binding: base?.defaults?.source ?? '',
      motion: 'none',
    },
  };
}

function normalizeSchema(input) {
  if (input?.root?.children && Array.isArray(input.root.children)) {
    return input;
  }
  return {
    root: {
      id: 'root',
      type: 'root',
      children: [],
    },
  };
}

function BuilderNodeCard({ node, selected, onSelect, onMoveUp, onMoveDown, onDuplicate, onDelete }) {
  return (
    <div
      className={`border rounded p-3 mb-2 ${selected ? 'border-primary bg-light' : 'border-secondary-subtle'}`}
      role="button"
      onClick={() => onSelect(node.id)}
      style={{ cursor: 'pointer' }}
    >
      <div className="d-flex justify-content-between align-items-center">
        <div>
          <strong>{node.type}</strong>
          <div className="small text-muted">{node.props?.title ?? node.props?.text ?? node.props?.label ?? 'Component block'}</div>
        </div>
        <div className="btn-group btn-group-sm" onClick={(e) => e.stopPropagation()}>
          <button type="button" className="btn btn-outline-secondary" onClick={() => onMoveUp(node.id)}>
            ↑
          </button>
          <button type="button" className="btn btn-outline-secondary" onClick={() => onMoveDown(node.id)}>
            ↓
          </button>
          <button type="button" className="btn btn-outline-info" onClick={() => onDuplicate(node.id)}>
            Duplicate
          </button>
          <button type="button" className="btn btn-outline-danger" onClick={() => onDelete(node.id)}>
            Delete
          </button>
        </div>
      </div>
    </div>
  );
}

function App() {
  const root = document.getElementById('storefront-studio-root');
  const base = root?.dataset?.base ?? '';
  const pageType = new URLSearchParams(window.location.search).get('page') ?? root?.dataset?.page ?? 'home';
  const previewUrl = root?.dataset?.preview ?? '/store';

  const [schema, setSchema] = useState(normalizeSchema(null));
  const [selectedNodeId, setSelectedNodeId] = useState(null);
  const [device, setDevice] = useState('desktop');
  const [theme, setTheme] = useState('modern');
  const [showJsonEditor, setShowJsonEditor] = useState(false);
  const [jsonDraft, setJsonDraft] = useState('{}');
  const [catalog, setCatalog] = useState(null);
  const [cart, setCart] = useState(null);
  const [uomoPresets, setUomoPresets] = useState(null);
  const [layoutDiff, setLayoutDiff] = useState(null);
  const [status, setStatus] = useState('');
  const [history, setHistory] = useState([]);
  const [future, setFuture] = useState([]);

  const xsrf = () => {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
  };

  const applyChange = (nextSchema, message) => {
    setHistory((prev) => [...prev.slice(-49), schema]);
    setFuture([]);
    setSchema(nextSchema);
    if (message) setStatus(message);
  };

  const load = () => {
    setStatus('Loading...');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((r) => r.json())
      .then((d) => {
        const normalized = normalizeSchema(d.layout_schema);
        setSchema(normalized);
        setSelectedNodeId(normalized.root.children?.[0]?.id ?? null);
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

  useEffect(() => {
    setJsonDraft(JSON.stringify(schema, null, 2));
  }, [schema]);

  const save = () => {
    setStatus('Saving...');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout`, {
      method: 'PUT',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf(),
      },
      body: JSON.stringify({ layout_schema: schema }),
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
        applyChange(normalizeSchema(parsed), 'Imported (not saved yet)');
      } catch {
        setStatus('Invalid import file');
      }
    };
    reader.readAsText(file);
  };

  const postImportSave = () => {
    setStatus('Importing...');
    fetch(`${base}/pages/${encodeURIComponent(pageType)}/layout/import`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': xsrf(),
      },
      body: JSON.stringify({ layout_schema: schema }),
    })
      .then((r) => {
        if (!r.ok) throw new Error('import');
        return r.json();
      })
      .then(() => {
        setStatus('Imported and saved');
        load();
      })
      .catch(() => setStatus('Import save failed'));
  };

  const exportJson = () => {
    const blob = new Blob([JSON.stringify(schema, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `layout-${pageType}.json`;
    a.click();
    URL.revokeObjectURL(a.href);
  };

  const nodes = schema?.root?.children ?? [];
  const selectedNode = nodes.find((n) => n.id === selectedNodeId) ?? null;

  const addNode = (type) => {
    const next = {
      ...schema,
      root: {
        ...schema.root,
        children: [...nodes, createNode(type)],
      },
    };
    applyChange(next, `${type} added`);
  };

  const replaceNode = (id, updater) => {
    const nextChildren = nodes.map((node) => (node.id === id ? updater(node) : node));
    applyChange(
      {
        ...schema,
        root: { ...schema.root, children: nextChildren },
      },
      'Draft updated'
    );
  };

  const removeNode = (id) => {
    const nextChildren = nodes.filter((node) => node.id !== id);
    applyChange(
      {
        ...schema,
        root: { ...schema.root, children: nextChildren },
      },
      'Node deleted'
    );
    if (selectedNodeId === id) setSelectedNodeId(nextChildren[0]?.id ?? null);
  };

  const reorderNode = (id, dir) => {
    const idx = nodes.findIndex((n) => n.id === id);
    const target = idx + dir;
    if (idx < 0 || target < 0 || target >= nodes.length) return;
    const nextChildren = [...nodes];
    const [item] = nextChildren.splice(idx, 1);
    nextChildren.splice(target, 0, item);
    applyChange(
      {
        ...schema,
        root: { ...schema.root, children: nextChildren },
      },
      'Order updated'
    );
  };

  const duplicateNode = (id) => {
    const idx = nodes.findIndex((n) => n.id === id);
    if (idx < 0) return;
    const original = nodes[idx];
    const copy = {
      ...original,
      id: `${original.type}_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
      props: { ...original.props },
      meta: { ...original.meta, responsive: { ...(original.meta?.responsive ?? {}) } },
    };
    const nextChildren = [...nodes];
    nextChildren.splice(idx + 1, 0, copy);
    applyChange({ ...schema, root: { ...schema.root, children: nextChildren } }, 'Node duplicated');
  };

  const undo = () => {
    if (!history.length) return;
    const previous = history[history.length - 1];
    setFuture((prev) => [schema, ...prev].slice(0, 50));
    setHistory((prev) => prev.slice(0, -1));
    setSchema(previous);
    setStatus('Undo');
  };

  const redo = () => {
    if (!future.length) return;
    const [next, ...rest] = future;
    setHistory((prev) => [...prev.slice(-49), schema]);
    setFuture(rest);
    setSchema(next);
    setStatus('Redo');
  };

  const groupedRegistry = useMemo(() => {
    return COMPONENT_REGISTRY.reduce((acc, c) => {
      if (!acc[c.category]) acc[c.category] = [];
      acc[c.category].push(c);
      return acc;
    }, {});
  }, []);

  const currentTheme = THEME_PRESETS[theme];

  return (
    <div className="container-fluid py-3">
      <div className="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 className="mb-1">Visual Ecommerce Builder</h4>
          <p className="text-muted small mb-0">
            Page: <strong>{pageType}</strong> - Draft mode
          </p>
        </div>
        <div className="d-flex gap-2 flex-wrap">
          {Object.entries(DEVICE_PRESETS).map(([key, value]) => (
            <button
              key={key}
              type="button"
              className={`btn btn-sm ${device === key ? 'btn-dark' : 'btn-outline-secondary'}`}
              onClick={() => setDevice(key)}
            >
              {value.label}
            </button>
          ))}
          <button type="button" className="btn btn-sm btn-outline-secondary" onClick={undo} disabled={!history.length}>
            Undo
          </button>
          <button type="button" className="btn btn-sm btn-outline-secondary" onClick={redo} disabled={!future.length}>
            Redo
          </button>
          <button type="button" className="btn btn-sm btn-primary" onClick={save}>
            Save draft
          </button>
          <button type="button" className="btn btn-sm btn-success" onClick={postImportSave}>
            Publish import
          </button>
        </div>
      </div>

      <div className="row g-3">
        <div className="col-xl-3">
          <div className="card h-100">
            <div className="card-header">
              <strong>Sections and Blocks</strong>
            </div>
            <div className="card-body">
              {Object.entries(groupedRegistry).map(([category, list]) => (
                <div key={category} className="mb-3">
                  <h6 className="small text-uppercase text-muted">{category}</h6>
                  <div className="d-grid gap-2">
                    {list.map((item) => (
                      <button
                        key={item.type}
                        type="button"
                        className="btn btn-outline-primary btn-sm text-start"
                        onClick={() => addNode(item.type)}
                      >
                        + {item.label}
                      </button>
                    ))}
                  </div>
                </div>
              ))}
              <hr />
              <h6 className="small text-uppercase text-muted">Draft Tools</h6>
              <button type="button" className="btn btn-outline-secondary btn-sm me-2 mb-2" onClick={load}>
                Reload
              </button>
              <button type="button" className="btn btn-outline-info btn-sm me-2 mb-2" onClick={exportJson}>
                Export JSON
              </button>
              <label className="btn btn-outline-dark btn-sm mb-2">
                Import JSON
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
              <div className="form-check mt-2">
                <input
                  className="form-check-input"
                  type="checkbox"
                  id="showJsonEditor"
                  checked={showJsonEditor}
                  onChange={(e) => setShowJsonEditor(e.target.checked)}
                />
                <label className="form-check-label small" htmlFor="showJsonEditor">
                  Show raw JSON editor
                </label>
              </div>
              {layoutDiff ? (
                <p className="small text-muted mt-2 mb-0">
                  Publish diff: <strong>{layoutDiff.dirty ? 'draft and published differ' : 'draft matches published'}</strong>
                </p>
              ) : null}
              {status ? <p className="small text-muted mt-2 mb-0">{status}</p> : null}
            </div>
          </div>
        </div>

        <div className="col-xl-6">
          <div className="card">
            <div className="card-header d-flex justify-content-between align-items-center">
              <strong>Visual Canvas</strong>
              <span className="badge text-bg-light">{DEVICE_PRESETS[device].label}</span>
            </div>
            <div className="card-body bg-light">
              <div
                className="mx-auto border rounded p-3 bg-white"
                style={{
                  width: DEVICE_PRESETS[device].width,
                  maxWidth: '100%',
                  minHeight: 420,
                  color: currentTheme.color,
                  backgroundColor: currentTheme.bg,
                  borderRadius: currentTheme.radius,
                }}
              >
                {nodes.length ? (
                  nodes.map((node) => (
                    <BuilderNodeCard
                      key={node.id}
                      node={node}
                      selected={selectedNodeId === node.id}
                      onSelect={setSelectedNodeId}
                      onMoveUp={(id) => reorderNode(id, -1)}
                      onMoveDown={(id) => reorderNode(id, 1)}
                      onDuplicate={duplicateNode}
                      onDelete={removeNode}
                    />
                  ))
                ) : (
                  <div className="text-center text-muted py-5">
                    No sections yet. Start by adding Hero or Product Grid from the left panel.
                  </div>
                )}
              </div>
            </div>
          </div>

          <div className="card mt-3">
            <div className="card-header">
              <strong>Live Store Preview</strong>
            </div>
            <div className="card-body">
              <iframe title="store-preview" src={previewUrl} className="w-100 border rounded" style={{ minHeight: 420 }} />
            </div>
          </div>
        </div>

        <div className="col-xl-3">
          <div className="card h-100">
            <div className="card-header">
              <strong>Inspector</strong>
            </div>
            <div className="card-body">
              {selectedNode ? (
                <>
                  <p className="small text-muted mb-2">
                    Selected: <code>{selectedNode.type}</code>
                  </p>
                  <div className="mb-2">
                    <label className="form-label small">Title or Text</label>
                    <input
                      className="form-control form-control-sm"
                      value={selectedNode.props?.title ?? selectedNode.props?.text ?? selectedNode.props?.label ?? ''}
                      onChange={(e) =>
                        replaceNode(selectedNode.id, (node) => ({
                          ...node,
                          props: { ...node.props, title: e.target.value, text: e.target.value, label: e.target.value },
                        }))
                      }
                    />
                  </div>
                  <div className="mb-2">
                    <label className="form-label small">Data Binding</label>
                    <select
                      className="form-select form-select-sm"
                      value={selectedNode.meta?.binding ?? ''}
                      onChange={(e) =>
                        replaceNode(selectedNode.id, (node) => ({
                          ...node,
                          meta: { ...node.meta, binding: e.target.value },
                        }))
                      }
                    >
                      <option value="">none</option>
                      <option value="erp.products.list">products</option>
                      <option value="erp.categories.tree">categories</option>
                      <option value="erp.collections.featured">collections</option>
                    </select>
                  </div>
                  <div className="mb-2">
                    <label className="form-label small">Motion Preset</label>
                    <select
                      className="form-select form-select-sm"
                      value={selectedNode.meta?.motion ?? 'none'}
                      onChange={(e) =>
                        replaceNode(selectedNode.id, (node) => ({
                          ...node,
                          meta: { ...node.meta, motion: e.target.value },
                        }))
                      }
                    >
                      {MOTION_PRESETS.map((m) => (
                        <option key={m} value={m}>
                          {m}
                        </option>
                      ))}
                    </select>
                  </div>
                </>
              ) : (
                <p className="small text-muted mb-3">Select a section from canvas to edit.</p>
              )}

              <hr />
              <h6 className="small text-uppercase text-muted">Theme Engine</h6>
              <select className="form-select form-select-sm mb-2" value={theme} onChange={(e) => setTheme(e.target.value)}>
                {Object.entries(THEME_PRESETS).map(([k, t]) => (
                  <option key={k} value={k}>
                    {t.label}
                  </option>
                ))}
              </select>
              <p className="small mb-1">
                Accent: <span style={{ color: currentTheme.accent }}>{currentTheme.accent}</span>
              </p>
              <p className="small mb-1">Radius: {currentTheme.radius}px</p>
              <p className="small mb-3">Spacing scale: {currentTheme.spacing}px</p>

              <h6 className="small text-uppercase text-muted">Catalog and Cart</h6>
              <p className="small mb-1">
                Catalog items: <strong>{catalog?.data?.length ?? 0}</strong>
              </p>
              <p className="small mb-1">
                Cart lines: <strong>{cart?.count ?? 0}</strong>
              </p>
              <p className="small mb-3">
                Subtotal: <strong>{cart?.subtotal?.toFixed?.(2) ?? cart?.subtotal ?? 0}</strong>
              </p>

              <h6 className="small text-uppercase text-muted">Uomo Fragments</h6>
              <p className="small mb-0">
                Ready: <strong>{uomoPresets?.navbar_fragments?.filter((p) => p.has_html).length ?? 0}</strong> /{' '}
                <strong>{uomoPresets?.navbar_fragments?.length ?? 0}</strong>
              </p>
            </div>
          </div>
        </div>
      </div>

      {showJsonEditor ? (
        <div className="card mt-3">
          <div className="card-header d-flex justify-content-between align-items-center">
            <strong>Raw JSON (advanced)</strong>
            <button
              type="button"
              className="btn btn-sm btn-outline-secondary"
              onClick={() => {
                try {
                  const parsed = JSON.parse(jsonDraft);
                  applyChange(normalizeSchema(parsed), 'JSON applied to canvas');
                } catch {
                  setStatus('Invalid JSON');
                }
              }}
            >
              Apply JSON
            </button>
          </div>
          <div className="card-body">
            <textarea
              id="studio-json-editor"
              className="form-control font-monospace"
              rows={12}
              value={jsonDraft}
              onChange={(e) => setJsonDraft(e.target.value)}
            />
          </div>
        </div>
      ) : null}
    </div>
  );
}

const el = document.getElementById('storefront-studio-root');
if (el) {
  createRoot(el).render(<App />);
}
