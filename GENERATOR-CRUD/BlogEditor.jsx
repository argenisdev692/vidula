import { useState, useCallback, useRef, useEffect } from "react";

// Simulated rich text editor with full toolbar (no external deps needed)
const FONTS = ["Serif", "Sans-serif", "Monospace", "Cursive"];

const TOOLBAR_GROUPS = [
  {
    id: "history",
    items: [
      { id: "undo", icon: "↩", label: "Deshacer", cmd: "undo" },
      { id: "redo", icon: "↪", label: "Rehacer", cmd: "redo" },
    ],
  },
  {
    id: "format",
    items: [
      { id: "bold", icon: "B", label: "Negrita", cmd: "bold", style: { fontWeight: "900" } },
      { id: "italic", icon: "I", label: "Cursiva", cmd: "italic", style: { fontStyle: "italic" } },
      { id: "underline", icon: "U", label: "Subrayado", cmd: "underline", style: { textDecoration: "underline" } },
      { id: "strikeThrough", icon: "S̶", label: "Tachado", cmd: "strikeThrough" },
    ],
  },
  {
    id: "align",
    items: [
      { id: "justifyLeft", icon: "⬛▬▬\n⬛▬▬▬\n⬛▬▬", label: "Izq.", cmd: "justifyLeft", icon: "≡L" },
      { id: "justifyCenter", icon: "≡C", label: "Centro", cmd: "justifyCenter" },
      { id: "justifyRight", icon: "≡R", label: "Der.", cmd: "justifyRight" },
      { id: "justifyFull", icon: "≡≡", label: "Justif.", cmd: "justifyFull" },
    ],
  },
  {
    id: "lists",
    items: [
      { id: "insertUnorderedList", icon: "• ≡", label: "Lista", cmd: "insertUnorderedList" },
      { id: "insertOrderedList", icon: "1.≡", label: "Numerada", cmd: "insertOrderedList" },
    ],
  },
  {
    id: "blocks",
    items: [
      { id: "indent", icon: "→≡", label: "Indentar", cmd: "indent" },
      { id: "outdent", icon: "←≡", label: "Outdentar", cmd: "outdent" },
      { id: "blockquote", icon: "❝", label: "Cita", cmd: "blockquote" },
      { id: "code", icon: "</>", label: "Código", cmd: "code" },
      { id: "hr", icon: "—", label: "Línea", cmd: "insertHorizontalRule" },
    ],
  },
];

const HEADING_OPTIONS = [
  { value: "p", label: "Párrafo" },
  { value: "h1", label: "Título 1" },
  { value: "h2", label: "Título 2" },
  { value: "h3", label: "Título 3" },
  { value: "h4", label: "Título 4" },
  { value: "blockquote", label: "Cita" },
  { value: "pre", label: "Código" },
];

function ToolbarButton({ item, onCommand, active }) {
  return (
    <button
      title={item.label}
      onMouseDown={(e) => {
        e.preventDefault();
        onCommand(item.cmd);
      }}
      style={{
        background: active ? "#1a1a2e" : "transparent",
        color: active ? "#e8d5b7" : "#2d2d2d",
        border: active ? "1px solid #1a1a2e" : "1px solid transparent",
        borderRadius: "5px",
        padding: "5px 9px",
        cursor: "pointer",
        fontSize: "13px",
        fontFamily: "inherit",
        transition: "all 0.15s",
        minWidth: "32px",
        ...(item.style || {}),
      }}
      onMouseEnter={(e) => {
        if (!active) {
          e.currentTarget.style.background = "#f0e8d8";
          e.currentTarget.style.borderColor = "#c4a882";
        }
      }}
      onMouseLeave={(e) => {
        if (!active) {
          e.currentTarget.style.background = "transparent";
          e.currentTarget.style.borderColor = "transparent";
        }
      }}
    >
      {item.icon}
    </button>
  );
}

export default function BlogEditor() {
  const editorRef = useRef(null);
  const [wordCount, setWordCount] = useState(0);
  const [charCount, setCharCount] = useState(0);
  const [activeFormats, setActiveFormats] = useState({});
  const [title, setTitle] = useState("");
  const [showLinkModal, setShowLinkModal] = useState(false);
  const [linkUrl, setLinkUrl] = useState("");
  const [showImageModal, setShowImageModal] = useState(false);
  const [imageUrl, setImageUrl] = useState("");
  const [headingValue, setHeadingValue] = useState("p");
  const [fontSize, setFontSize] = useState("16px");
  const [saved, setSaved] = useState(false);

  const updateCounts = useCallback(() => {
    if (editorRef.current) {
      const text = editorRef.current.innerText || "";
      const words = text.trim() ? text.trim().split(/\s+/).length : 0;
      setWordCount(words);
      setCharCount(text.length);
    }
  }, []);

  const updateActiveFormats = useCallback(() => {
    const formats = {};
    ["bold", "italic", "underline", "strikeThrough", "insertUnorderedList", "insertOrderedList"].forEach((cmd) => {
      try {
        formats[cmd] = document.queryCommandState(cmd);
      } catch {}
    });
    setActiveFormats(formats);
  }, []);

  const execCommand = useCallback((cmd) => {
    if (cmd === "blockquote") {
      document.execCommand("formatBlock", false, "blockquote");
    } else if (cmd === "code") {
      document.execCommand("formatBlock", false, "pre");
    } else {
      document.execCommand(cmd, false, null);
    }
    editorRef.current?.focus();
    updateActiveFormats();
  }, [updateActiveFormats]);

  const applyHeading = useCallback((val) => {
    setHeadingValue(val);
    document.execCommand("formatBlock", false, val);
    editorRef.current?.focus();
  }, []);

  const applyFontSize = useCallback((size) => {
    setFontSize(size);
    editorRef.current.style.fontSize = size;
    editorRef.current?.focus();
  }, []);

  const insertLink = useCallback(() => {
    if (linkUrl) {
      document.execCommand("createLink", false, linkUrl);
      setLinkUrl("");
      setShowLinkModal(false);
    }
  }, [linkUrl]);

  const insertImage = useCallback(() => {
    if (imageUrl) {
      document.execCommand("insertImage", false, imageUrl);
      setImageUrl("");
      setShowImageModal(false);
    }
  }, [imageUrl]);

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 2000);
  };

  return (
    <div style={{
      minHeight: "100vh",
      background: "linear-gradient(135deg, #faf7f2 0%, #f0e8d8 100%)",
      fontFamily: "'Georgia', 'Times New Roman', serif",
      padding: "40px 20px",
    }}>
      {/* Header */}
      <div style={{
        maxWidth: "860px",
        margin: "0 auto 24px",
        display: "flex",
        justifyContent: "space-between",
        alignItems: "center",
      }}>
        <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
          <div style={{
            width: "36px", height: "36px",
            background: "#1a1a2e",
            borderRadius: "8px",
            display: "flex", alignItems: "center", justifyContent: "center",
            color: "#e8d5b7", fontSize: "18px",
          }}>✒</div>
          <div>
            <div style={{ fontWeight: "700", fontSize: "18px", color: "#1a1a2e", letterSpacing: "-0.5px" }}>
              Editor de Blog
            </div>
            <div style={{ fontSize: "12px", color: "#9a8a7a" }}>
              {wordCount} palabras · {charCount} caracteres
            </div>
          </div>
        </div>
        <button
          onClick={handleSave}
          style={{
            background: saved ? "#2d7a4f" : "#1a1a2e",
            color: "#e8d5b7",
            border: "none",
            borderRadius: "8px",
            padding: "10px 22px",
            cursor: "pointer",
            fontSize: "14px",
            fontFamily: "inherit",
            transition: "all 0.3s",
            letterSpacing: "0.5px",
          }}
        >
          {saved ? "✓ Guardado" : "Guardar borrador"}
        </button>
      </div>

      {/* Main editor card */}
      <div style={{
        maxWidth: "860px",
        margin: "0 auto",
        background: "#fff",
        borderRadius: "16px",
        boxShadow: "0 4px 40px rgba(0,0,0,0.10), 0 1px 4px rgba(0,0,0,0.06)",
        overflow: "hidden",
        border: "1px solid #e8ddd0",
      }}>

        {/* Title input */}
        <div style={{ padding: "32px 40px 0" }}>
          <input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            placeholder="Título de tu artículo..."
            style={{
              width: "100%",
              border: "none",
              outline: "none",
              fontSize: "32px",
              fontWeight: "700",
              color: "#1a1a2e",
              fontFamily: "'Georgia', serif",
              background: "transparent",
              letterSpacing: "-1px",
              lineHeight: "1.2",
              boxSizing: "border-box",
            }}
          />
          <div style={{ height: "1px", background: "#f0e8d8", margin: "20px 0 0" }} />
        </div>

        {/* Toolbar */}
        <div style={{
          padding: "10px 40px",
          background: "#fdfaf6",
          borderBottom: "1px solid #e8ddd0",
          display: "flex",
          flexWrap: "wrap",
          gap: "6px",
          alignItems: "center",
        }}>
          {/* Heading selector */}
          <select
            value={headingValue}
            onChange={(e) => applyHeading(e.target.value)}
            style={{
              border: "1px solid #d8ccbc",
              borderRadius: "6px",
              padding: "5px 8px",
              fontSize: "13px",
              background: "#fff",
              cursor: "pointer",
              fontFamily: "inherit",
              color: "#2d2d2d",
              outline: "none",
            }}
          >
            {HEADING_OPTIONS.map((o) => (
              <option key={o.value} value={o.value}>{o.label}</option>
            ))}
          </select>

          {/* Font size */}
          <select
            value={fontSize}
            onChange={(e) => applyFontSize(e.target.value)}
            style={{
              border: "1px solid #d8ccbc",
              borderRadius: "6px",
              padding: "5px 8px",
              fontSize: "13px",
              background: "#fff",
              cursor: "pointer",
              fontFamily: "inherit",
              color: "#2d2d2d",
              outline: "none",
            }}
          >
            {["12px","14px","16px","18px","20px","24px","28px","32px"].map(s => (
              <option key={s} value={s}>{s}</option>
            ))}
          </select>

          <div style={{ width: "1px", height: "24px", background: "#d8ccbc", margin: "0 2px" }} />

          {/* Toolbar groups */}
          {TOOLBAR_GROUPS.map((group, gi) => (
            <div key={group.id} style={{ display: "flex", gap: "2px", alignItems: "center" }}>
              {group.items.map((item) => (
                <ToolbarButton
                  key={item.id}
                  item={item}
                  onCommand={execCommand}
                  active={!!activeFormats[item.cmd]}
                />
              ))}
              {gi < TOOLBAR_GROUPS.length - 1 && (
                <div style={{ width: "1px", height: "24px", background: "#d8ccbc", margin: "0 4px" }} />
              )}
            </div>
          ))}

          <div style={{ width: "1px", height: "24px", background: "#d8ccbc", margin: "0 2px" }} />

          {/* Link button */}
          <button
            title="Insertar enlace"
            onMouseDown={(e) => { e.preventDefault(); setShowLinkModal(true); }}
            style={{
              background: "transparent", border: "1px solid transparent",
              borderRadius: "5px", padding: "5px 9px", cursor: "pointer",
              fontSize: "13px", color: "#2d2d2d", transition: "all 0.15s",
            }}
          >🔗</button>

          {/* Image button */}
          <button
            title="Insertar imagen"
            onMouseDown={(e) => { e.preventDefault(); setShowImageModal(true); }}
            style={{
              background: "transparent", border: "1px solid transparent",
              borderRadius: "5px", padding: "5px 9px", cursor: "pointer",
              fontSize: "13px", color: "#2d2d2d", transition: "all 0.15s",
            }}
          >🖼</button>
        </div>

        {/* Editor area */}
        <div
          ref={editorRef}
          contentEditable
          suppressContentEditableWarning
          onInput={() => { updateCounts(); updateActiveFormats(); }}
          onKeyUp={updateActiveFormats}
          onMouseUp={updateActiveFormats}
          data-placeholder="Empieza a escribir tu historia aquí..."
          style={{
            minHeight: "420px",
            padding: "32px 40px 40px",
            outline: "none",
            fontSize: fontSize,
            lineHeight: "1.85",
            color: "#2d2825",
            fontFamily: "'Georgia', 'Times New Roman', serif",
            position: "relative",
          }}
        />
      </div>

      {/* Link modal */}
      {showLinkModal && (
        <div style={{
          position: "fixed", inset: 0, background: "rgba(0,0,0,0.4)",
          display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000,
        }}
          onClick={() => setShowLinkModal(false)}
        >
          <div
            style={{
              background: "#fff", borderRadius: "12px", padding: "28px",
              width: "360px", boxShadow: "0 20px 60px rgba(0,0,0,0.2)",
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <h3 style={{ margin: "0 0 16px", fontFamily: "Georgia", color: "#1a1a2e" }}>Insertar enlace</h3>
            <input
              value={linkUrl}
              onChange={(e) => setLinkUrl(e.target.value)}
              placeholder="https://ejemplo.com"
              autoFocus
              style={{
                width: "100%", padding: "10px 14px", border: "1px solid #d8ccbc",
                borderRadius: "8px", fontSize: "14px", outline: "none",
                boxSizing: "border-box", marginBottom: "16px",
              }}
            />
            <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end" }}>
              <button onClick={() => setShowLinkModal(false)} style={{ padding: "8px 16px", border: "1px solid #d8ccbc", borderRadius: "7px", background: "transparent", cursor: "pointer" }}>Cancelar</button>
              <button onClick={insertLink} style={{ padding: "8px 16px", background: "#1a1a2e", color: "#e8d5b7", border: "none", borderRadius: "7px", cursor: "pointer" }}>Insertar</button>
            </div>
          </div>
        </div>
      )}

      {/* Image modal */}
      {showImageModal && (
        <div style={{
          position: "fixed", inset: 0, background: "rgba(0,0,0,0.4)",
          display: "flex", alignItems: "center", justifyContent: "center", zIndex: 1000,
        }}
          onClick={() => setShowImageModal(false)}
        >
          <div
            style={{
              background: "#fff", borderRadius: "12px", padding: "28px",
              width: "360px", boxShadow: "0 20px 60px rgba(0,0,0,0.2)",
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <h3 style={{ margin: "0 0 16px", fontFamily: "Georgia", color: "#1a1a2e" }}>Insertar imagen</h3>
            <input
              value={imageUrl}
              onChange={(e) => setImageUrl(e.target.value)}
              placeholder="https://url-de-imagen.com/foto.jpg"
              autoFocus
              style={{
                width: "100%", padding: "10px 14px", border: "1px solid #d8ccbc",
                borderRadius: "8px", fontSize: "14px", outline: "none",
                boxSizing: "border-box", marginBottom: "16px",
              }}
            />
            <div style={{ display: "flex", gap: "10px", justifyContent: "flex-end" }}>
              <button onClick={() => setShowImageModal(false)} style={{ padding: "8px 16px", border: "1px solid #d8ccbc", borderRadius: "7px", background: "transparent", cursor: "pointer" }}>Cancelar</button>
              <button onClick={insertImage} style={{ padding: "8px 16px", background: "#1a1a2e", color: "#e8d5b7", border: "none", borderRadius: "7px", cursor: "pointer" }}>Insertar</button>
            </div>
          </div>
        </div>
      )}

      {/* Global placeholder style */}
      <style>{`
        [contenteditable][data-placeholder]:empty:before {
          content: attr(data-placeholder);
          color: #c0b8aa;
          pointer-events: none;
          font-style: italic;
        }
        [contenteditable] blockquote {
          border-left: 3px solid #c4a882;
          margin: 16px 0;
          padding: 8px 20px;
          color: #6a5a4a;
          font-style: italic;
          background: #fdfaf6;
        }
        [contenteditable] pre {
          background: #1a1a2e;
          color: #e8d5b7;
          padding: 16px 20px;
          border-radius: 8px;
          font-family: monospace;
          font-size: 14px;
          overflow-x: auto;
        }
        [contenteditable] a { color: #8b5e3c; }
        [contenteditable] img { max-width: 100%; border-radius: 8px; margin: 8px 0; }
        [contenteditable] h1 { font-size: 2em; color: #1a1a2e; margin: 0.5em 0; }
        [contenteditable] h2 { font-size: 1.6em; color: #1a1a2e; margin: 0.5em 0; }
        [contenteditable] h3 { font-size: 1.3em; color: #1a1a2e; margin: 0.5em 0; }
        [contenteditable] h4 { font-size: 1.1em; color: #1a1a2e; margin: 0.5em 0; }
        [contenteditable] ul, [contenteditable] ol { padding-left: 24px; }
        [contenteditable] li { margin: 4px 0; }
        [contenteditable] hr { border: none; border-top: 2px solid #e8ddd0; margin: 20px 0; }
      `}</style>
    </div>
  );
}
