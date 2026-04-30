// Shared atoms + motifs used across all three directions

// ----- Compass rose (SVG, abstract navigator motif) -----
const CompassRose = ({ size = 200, stroke = "currentColor", strokeWidth = 1, bearing = 270, showBearing = true }) => (
  <svg viewBox="-100 -100 200 200" width={size} height={size} fill="none" stroke={stroke} strokeWidth={strokeWidth}>
    <circle cx="0" cy="0" r="98" />
    <circle cx="0" cy="0" r="78" opacity="0.4" />
    <circle cx="0" cy="0" r="58" opacity="0.25" />
    {Array.from({ length: 72 }).map((_, i) => {
      const a = (i * 5 * Math.PI) / 180;
      const r1 = i % 6 === 0 ? 86 : i % 2 === 0 ? 92 : 95;
      const r2 = 98;
      return <line key={i} x1={Math.cos(a) * r1} y1={Math.sin(a) * r1} x2={Math.cos(a) * r2} y2={Math.sin(a) * r2} opacity={i % 6 === 0 ? 1 : 0.4} />;
    })}
    {/* Cardinal arms */}
    <path d="M 0,-78 L 6,0 L 0,-78 L -6,0 Z" fill={stroke} opacity="0.9" stroke="none" />
    <path d="M 0,78 L 6,0 L 0,78 L -6,0 Z" fill={stroke} opacity="0.3" stroke="none" />
    <path d="M -78,0 L 0,6 L -78,0 L 0,-6 Z" fill={stroke} opacity="0.6" stroke="none" />
    <path d="M 78,0 L 0,6 L 78,0 L 0,-6 Z" fill={stroke} opacity="0.6" stroke="none" />
    <circle cx="0" cy="0" r="3" fill={stroke} stroke="none" />
    {showBearing && (
      <g transform={`rotate(${bearing - 90})`}>
        <line x1="0" y1="0" x2="92" y2="0" stroke={stroke} strokeWidth={strokeWidth * 1.5} />
        <polygon points="92,0 84,-4 84,4" fill={stroke} stroke="none" />
      </g>
    )}
    {/* N E S W labels */}
    <text x="0" y="-62" textAnchor="middle" fontSize="9" fill={stroke} stroke="none" fontFamily="ui-monospace, monospace" letterSpacing="1">N</text>
    <text x="62" y="3" textAnchor="middle" fontSize="9" fill={stroke} stroke="none" fontFamily="ui-monospace, monospace">E</text>
    <text x="0" y="68" textAnchor="middle" fontSize="9" fill={stroke} stroke="none" fontFamily="ui-monospace, monospace">S</text>
    <text x="-62" y="3" textAnchor="middle" fontSize="9" fill={stroke} stroke="none" fontFamily="ui-monospace, monospace">W</text>
  </svg>
);

// ----- Topo contour lines (subtle background motif) -----
const TopoLines = ({ stroke = "currentColor", opacity = 0.08 }) => (
  <svg viewBox="0 0 800 600" preserveAspectRatio="xMidYMid slice" style={{ position: "absolute", inset: 0, width: "100%", height: "100%", pointerEvents: "none" }}>
    <g fill="none" stroke={stroke} strokeWidth="1" opacity={opacity}>
      <path d="M -50 200 Q 200 140, 400 220 T 850 180" />
      <path d="M -50 240 Q 200 180, 400 260 T 850 220" />
      <path d="M -50 280 Q 200 220, 400 300 T 850 260" />
      <path d="M -50 320 Q 200 260, 400 340 T 850 300" />
      <path d="M -50 360 Q 200 300, 400 380 T 850 340" />
      <path d="M -50 400 Q 200 340, 400 420 T 850 380" />
      <path d="M -50 440 Q 200 380, 400 460 T 850 420" />
      <path d="M -50 480 Q 200 420, 400 500 T 850 460" />
      <path d="M -50 520 Q 200 460, 400 540 T 850 500" />
    </g>
  </svg>
);

// ----- Map grid (graph-paper coords) -----
const MapGrid = ({ stroke = "currentColor", opacity = 0.06, size = 32 }) => (
  <svg style={{ position: "absolute", inset: 0, width: "100%", height: "100%", pointerEvents: "none" }}>
    <defs>
      <pattern id={`grid-${size}`} width={size} height={size} patternUnits="userSpaceOnUse">
        <path d={`M ${size} 0 L 0 0 0 ${size}`} fill="none" stroke={stroke} strokeWidth="1" opacity={opacity} />
      </pattern>
    </defs>
    <rect width="100%" height="100%" fill={`url(#grid-${size})`} />
  </svg>
);

// ----- Veteran portrait placeholder (per doc: real veteran preferred over stock) -----
const VeteranPortrait = ({ label = "real veteran portrait", aspect = "4 / 5", bg = "#1f2a3a", fg = "#7A8A6A" }) => (
  <div style={{
    aspectRatio: aspect,
    background: bg,
    position: "relative",
    overflow: "hidden",
    color: fg,
    border: `1px solid ${fg}33`
  }}>
    {/* subtle diagonal stripe */}
    <svg width="100%" height="100%" style={{ position: "absolute", inset: 0, opacity: 0.18 }}>
      <defs>
        <pattern id={`stripe-${label.replace(/\s/g,'')}`} width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
          <line x1="0" y1="0" x2="0" y2="14" stroke={fg} strokeWidth="1" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill={`url(#stripe-${label.replace(/\s/g,'')})`} />
    </svg>
    <div style={{
      position: "absolute", left: 12, bottom: 12, fontFamily: "ui-monospace, 'SF Mono', monospace",
      fontSize: 10, letterSpacing: "0.08em", textTransform: "uppercase", color: fg
    }}>
      [ {label} ]
    </div>
    <div style={{
      position: "absolute", right: 12, top: 12, fontFamily: "ui-monospace, monospace",
      fontSize: 9, letterSpacing: "0.1em", color: fg, opacity: 0.6
    }}>
      270°W
    </div>
  </div>
);

// ----- Eligibility Quiz (shared, themeable) -----
const QUIZ_QUESTIONS = [
  {
    id: "served",
    q: "Have you served in the Canadian Armed Forces?",
    options: ["Regular Force", "Reserve Force", "RCMP", "No"]
  },
  {
    id: "rating",
    q: "Do you currently have a VAC disability rating?",
    options: ["No rating", "0–30%", "40–70%", "80%+"]
  },
  {
    id: "health",
    q: "Are you experiencing service-related health issues?",
    options: ["Yes", "Not sure", "No"]
  },
  {
    id: "filed",
    q: "Have you previously filed a claim with VAC?",
    options: ["Yes — approved", "Yes — denied", "No"]
  },
  {
    id: "goal",
    q: "What are you looking to do?",
    options: ["File a new claim", "Increase an existing rating", "Appeal a denial", "Not sure yet"]
  }
];

const EligibilityQuiz = ({ theme = "navy", compact = false }) => {
  const [step, setStep] = React.useState(0); // 0 = intro, 1..N = questions, N+1 = capture, N+2 = results
  const [answers, setAnswers] = React.useState({});
  const [contact, setContact] = React.useState({ name: "", email: "", phone: "" });
  const N = QUIZ_QUESTIONS.length;

  const totalSteps = N + 2;
  const progress = step === 0 ? 0 : Math.min(1, (step) / totalSteps);

  const palettes = {
    navy:    { bg: "#0E1A2B", fg: "#F4F1EC", muted: "#8A95A6", accent: "#A5391D", chip: "#1a2940", chipHover: "#243453", border: "#22324a" },
    bone:    { bg: "#F4F1EC", fg: "#0E1A2B", muted: "#5a6472", accent: "#A5391D", chip: "#ffffff",  chipHover: "#fff8ec",  border: "#d8d2c4" },
    olive:   { bg: "#2b3528", fg: "#F4F1EC", muted: "#a9b3a3", accent: "#d96b3a", chip: "#37432f",  chipHover: "#445040",  border: "#4a5742" }
  };
  const c = palettes[theme] || palettes.navy;

  const reset = () => { setStep(0); setAnswers({}); setContact({ name: "", email: "", phone: "" }); };

  return (
    <div style={{
      background: c.bg, color: c.fg, padding: compact ? "32px" : "48px",
      border: `1px solid ${c.border}`, position: "relative", overflow: "hidden",
      fontFamily: "Inter, system-ui, sans-serif"
    }}>
      {/* Header */}
      <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 24, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: c.muted }}>
        <span>Eligibility Check</span>
        <span>{step === 0 ? "00" : String(Math.min(step, totalSteps)).padStart(2, "0")} / {String(totalSteps).padStart(2, "0")}</span>
      </div>

      {/* Progress bar */}
      <div style={{ height: 2, background: c.border, marginBottom: 32, position: "relative" }}>
        <div style={{ position: "absolute", inset: 0, width: `${progress * 100}%`, background: c.accent, transition: "width 0.4s ease" }} />
      </div>

      {step === 0 && (
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", color: c.muted, textTransform: "uppercase", marginBottom: 12 }}>Step 01 — Intake</div>
          <h3 style={{ fontSize: compact ? 28 : 36, fontWeight: 500, lineHeight: 1.1, margin: "0 0 16px", letterSpacing: "-0.02em" }}>
            See which VAC programs may apply to&nbsp;you.
          </h3>
          <p style={{ fontSize: 16, lineHeight: 1.55, color: c.muted, maxWidth: 520, margin: "0 0 28px" }}>
            Five quick questions. About two minutes. Fully confidential and no obligation — we'll get back to you with a clear, friendly next step.
          </p>
          <div style={{ display: "flex", gap: 12, alignItems: "center", flexWrap: "wrap" }}>
            <button onClick={() => setStep(1)} style={{
              background: c.accent, color: "#fff", border: "none", padding: "16px 28px",
              fontSize: 14, fontWeight: 600, letterSpacing: "0.04em", textTransform: "uppercase",
              cursor: "pointer", fontFamily: "inherit"
            }}>
              Start now →
            </button>
            <div style={{ display: "flex", gap: 16, fontSize: 12, color: c.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em" }}>
              <span>● 2 MIN</span><span>● CONFIDENTIAL</span><span>● NO COST</span>
            </div>
          </div>
        </div>
      )}

      {step >= 1 && step <= N && (() => {
        const Q = QUIZ_QUESTIONS[step - 1];
        return (
          <div>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", color: c.muted, textTransform: "uppercase", marginBottom: 12 }}>
              Question {String(step).padStart(2, "0")} of {String(N).padStart(2, "0")}
            </div>
            <h3 style={{ fontSize: compact ? 24 : 30, fontWeight: 500, lineHeight: 1.2, margin: "0 0 28px", letterSpacing: "-0.01em", maxWidth: 600 }}>
              {Q.q}
            </h3>
            <div style={{ display: "grid", gridTemplateColumns: Q.options.length > 3 ? "1fr 1fr" : "1fr", gap: 10, marginBottom: 28 }}>
              {Q.options.map(opt => {
                const selected = answers[Q.id] === opt;
                return (
                  <button key={opt}
                    onClick={() => { setAnswers({ ...answers, [Q.id]: opt }); setTimeout(() => setStep(step + 1), 220); }}
                    style={{
                      background: selected ? c.accent : c.chip, color: selected ? "#fff" : c.fg,
                      border: `1px solid ${selected ? c.accent : c.border}`, padding: "16px 20px",
                      fontSize: 15, textAlign: "left", cursor: "pointer", fontFamily: "inherit",
                      transition: "all 0.15s", display: "flex", justifyContent: "space-between", alignItems: "center"
                    }}
                    onMouseEnter={e => { if (!selected) e.currentTarget.style.background = c.chipHover; }}
                    onMouseLeave={e => { if (!selected) e.currentTarget.style.background = c.chip; }}
                  >
                    <span>{opt}</span>
                    <span style={{ opacity: 0.5, fontSize: 12, fontFamily: "ui-monospace, monospace" }}>→</span>
                  </button>
                );
              })}
            </div>
            <div style={{ display: "flex", justifyContent: "space-between", fontSize: 12, color: c.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em" }}>
              {step > 1 ? <button onClick={() => setStep(step - 1)} style={{ background: "none", border: "none", color: c.muted, cursor: "pointer", fontFamily: "inherit", letterSpacing: "inherit" }}>← BACK</button> : <span />}
              <span>{Math.round(progress * 100)}% COMPLETE</span>
            </div>
          </div>
        );
      })()}

      {step === N + 1 && (
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", color: c.muted, textTransform: "uppercase", marginBottom: 12 }}>Almost there</div>
          <h3 style={{ fontSize: compact ? 26 : 32, fontWeight: 500, lineHeight: 1.15, margin: "0 0 12px", letterSpacing: "-0.02em" }}>
            You may qualify for additional benefits.
          </h3>
          <p style={{ fontSize: 15, lineHeight: 1.55, color: c.muted, maxWidth: 480, margin: "0 0 24px" }}>
            Where should we send your tailored next step? We'll reach out within one business day.
          </p>
          <div style={{ display: "grid", gap: 10, marginBottom: 20 }}>
            {[
              { k: "name", label: "Full name", type: "text" },
              { k: "email", label: "Email", type: "email" },
              { k: "phone", label: "Phone", type: "tel" }
            ].map(f => (
              <div key={f.k} style={{ borderBottom: `1px solid ${c.border}`, padding: "8px 0" }}>
                <div style={{ fontSize: 10, color: c.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.1em", textTransform: "uppercase", marginBottom: 4 }}>{f.label}</div>
                <input
                  type={f.type}
                  value={contact[f.k]}
                  onChange={e => setContact({ ...contact, [f.k]: e.target.value })}
                  style={{ background: "transparent", border: "none", outline: "none", color: c.fg, fontSize: 16, fontFamily: "inherit", width: "100%", padding: "4px 0" }}
                />
              </div>
            ))}
          </div>
          <button onClick={() => setStep(step + 1)} style={{
            background: c.accent, color: "#fff", border: "none", padding: "16px 28px",
            fontSize: 14, fontWeight: 600, letterSpacing: "0.04em", textTransform: "uppercase",
            cursor: "pointer", fontFamily: "inherit"
          }}>
            Get my results →
          </button>
          <div style={{ marginTop: 14, fontSize: 11, color: c.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em" }}>
            🔒 Confidential. We never share your info.
          </div>
        </div>
      )}

      {step >= N + 2 && (
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", color: c.accent, textTransform: "uppercase", marginBottom: 12 }}>● Result ready</div>
          <h3 style={{ fontSize: compact ? 28 : 34, fontWeight: 500, lineHeight: 1.15, margin: "0 0 16px", letterSpacing: "-0.02em" }}>
            Thanks — we'll be in touch shortly.
          </h3>
          <p style={{ fontSize: 16, lineHeight: 1.55, color: c.muted, maxWidth: 520, margin: "0 0 28px" }}>
            Based on your answers, there are a few VAC programs we'd like to walk through with you. A 270 West advisor will reach out to {contact.name || "you"} within one business day for a friendly, no-obligation conversation.
          </p>
          <div style={{ display: "flex", gap: 12, flexWrap: "wrap" }}>
            <button style={{
              background: c.accent, color: "#fff", border: "none", padding: "16px 28px",
              fontSize: 14, fontWeight: 600, letterSpacing: "0.04em", textTransform: "uppercase",
              cursor: "pointer", fontFamily: "inherit"
            }}>
              Book a free conversation →
            </button>
            <button onClick={reset} style={{
              background: "transparent", color: c.fg, border: `1px solid ${c.border}`, padding: "16px 28px",
              fontSize: 14, fontWeight: 500, letterSpacing: "0.04em", textTransform: "uppercase",
              cursor: "pointer", fontFamily: "inherit"
            }}>
              Restart
            </button>
          </div>
        </div>
      )}
    </div>
  );
};

// Hero copy variants — used by Tweaks
const HERO_COPY_VARIANTS = {
  earned: {
    eyebrow: "VAC Disability Benefits — Canada",
    headline: "Get the disability benefits you've earned — without the confusion, delays, or denials.",
    sub: "270 West Consulting helps Canadian veterans navigate the VAC system with expert guidance, proven processes, and a higher likelihood of approval."
  },
  navigator: {
    eyebrow: "Veterans Affairs Canada — Your Advocate",
    headline: "The VAC system isn't built to be easy. We are.",
    sub: "We act as your advocate and guide — from eligibility to submission — so you receive every benefit your service earned."
  },
  outcome: {
    eyebrow: "Approval-Driven Claims Support",
    headline: "More approvals. Higher ratings. Less paperwork.",
    sub: "Veterans across Canada trust 270 West to navigate VAC, increase their rating, and unlock the benefits they're entitled to."
  },
  sacrifice: {
    eyebrow: "Your Sacrifice. Our Commitment.",
    headline: "You served. Now it's our turn to fight for you.",
    sub: "We guide veterans and their families through every step of the VAC claims process — with clarity, advocacy, and proven results."
  }
};

// ----- Explicit image placeholder (loud, unmissable, includes spec) -----
const ImagePlaceholder = ({ label, spec, aspect = "16 / 10", note, theme = "light" }) => {
  const isDark = theme === "dark";
  const bg = isDark ? "#1f2a3a" : "#e8e2d4";
  const fg = isDark ? "#A5391D" : "#A5391D";
  const ink = isDark ? "#F4F1EC" : "#0E1A2B";
  const muted = isDark ? "#a9b3c0" : "#5a6472";
  return (
    <div style={{
      aspectRatio: aspect, background: bg, position: "relative", overflow: "hidden",
      border: `2px dashed ${fg}`, color: ink,
      display: "flex", flexDirection: "column", justifyContent: "center", alignItems: "center",
      padding: 24, textAlign: "center"
    }}>
      <svg width="100%" height="100%" style={{ position: "absolute", inset: 0, opacity: 0.10 }}>
        <defs>
          <pattern id={`ph-${(label||'x').replace(/\s/g,'')}`} width="24" height="24" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <line x1="0" y1="0" x2="0" y2="24" stroke={fg} strokeWidth="1" />
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill={`url(#ph-${(label||'x').replace(/\s/g,'')})`} />
      </svg>
      <div style={{ position: "relative", maxWidth: "85%" }}>
        <div style={{
          display: "inline-block", padding: "4px 10px", marginBottom: 14,
          background: fg, color: "#fff",
          fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", fontWeight: 600
        }}>
          ⌖ Image placeholder
        </div>
        <div style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, lineHeight: 1.2, marginBottom: 10, letterSpacing: "-0.01em" }}>
          {label}
        </div>
        {spec && (
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.08em", color: muted, lineHeight: 1.6, textTransform: "uppercase" }}>
            {spec}
          </div>
        )}
        {note && (
          <div style={{ marginTop: 14, fontSize: 12, color: muted, lineHeight: 1.55, fontStyle: "italic", maxWidth: 360, marginLeft: "auto", marginRight: "auto" }}>
            {note}
          </div>
        )}
      </div>
    </div>
  );
};

Object.assign(window, {
  CompassRose, TopoLines, MapGrid, VeteranPortrait, ImagePlaceholder,
  EligibilityQuiz, QUIZ_QUESTIONS, HERO_COPY_VARIANTS
});
