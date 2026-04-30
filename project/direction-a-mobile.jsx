// Direction A — Mobile (390px, iPhone-class)
// Same content + tone as desktop, restructured for narrow viewport.

const DirectionAMobile = () => {
  const P = window.PALETTE_A;
  return (
    <div style={{
      width: 390, fontFamily: "'Inter', system-ui, sans-serif",
      background: P.bg, color: P.ink, position: "relative"
    }}>
      {/* Top bar */}
      <header style={{
        display: "flex", justifyContent: "space-between", alignItems: "center",
        padding: "20px 20px 16px", borderBottom: `1px solid ${P.rule}`
      }}>
        <div style={{ display: "flex", alignItems: "baseline", gap: 8 }}>
          <div style={{ fontFamily: "'Fraunces', serif", fontSize: 20, fontWeight: 500, letterSpacing: "-0.01em" }}>
            270<span style={{ color: P.accent }}>°</span>West
          </div>
        </div>
        <button style={{
          background: "none", border: `1px solid ${P.ink}`, padding: "8px 12px",
          fontSize: 11, letterSpacing: "0.08em", textTransform: "uppercase",
          fontFamily: "ui-monospace, monospace", color: P.ink
        }}>
          Menu
        </button>
      </header>

      {/* Mobile sticky CTA bar (visual; sticky-feel) */}
      <div style={{
        background: P.ink, color: P.bg, padding: "10px 20px", display: "flex",
        justifyContent: "space-between", alignItems: "center",
        fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.1em", textTransform: "uppercase"
      }}>
        <span>● Free 2-min eligibility check</span>
        <span style={{ color: P.olive }}>→</span>
      </div>

      {/* HERO */}
      <section style={{ padding: "40px 20px 36px" }}>
        <div style={{
          fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em",
          textTransform: "uppercase", color: P.muted, marginBottom: 24,
          display: "flex", gap: 10, alignItems: "center"
        }}>
          <span style={{ width: 24, height: 1, background: P.muted }} />
          VAC — Claims Support
        </div>
        <h1 style={{
          fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 400,
          lineHeight: 1.0, letterSpacing: "-0.02em", margin: "0 0 20px",
          textWrap: "balance"
        }}>
          Your sacrifice.<br/>
          <em style={{ fontStyle: "italic", color: P.accent }}>Our commitment.</em>
        </h1>
        <p style={{ fontSize: 16, lineHeight: 1.55, color: P.ink, margin: "0 0 12px" }}>
          We help Canadian veterans and their families understand and access VAC benefits — with patient guidance and a teammate at your side.
        </p>
        <p style={{ fontSize: 13, lineHeight: 1.55, color: P.muted, margin: "0 0 28px" }}>
          We don't replace VAC. We help you work with them.
        </p>

        {/* Compass card mobile */}
        <div style={{
          background: P.ink, color: P.bg, padding: 24, position: "relative", overflow: "hidden",
          aspectRatio: "5 / 4", marginBottom: 28
        }}>
          <TopoLines stroke={P.olive} opacity={0.18} />
          <div style={{ position: "absolute", inset: 0, display: "flex", alignItems: "center", justifyContent: "center" }}>
            <div style={{ color: P.olive, opacity: 0.85 }}>
              <CompassRose size={180} stroke="currentColor" strokeWidth={1} bearing={270} />
            </div>
          </div>
          <div style={{ position: "absolute", top: 14, left: 14, fontFamily: "ui-monospace, monospace", fontSize: 9, letterSpacing: "0.14em", color: P.olive, textTransform: "uppercase" }}>
            Bearing 270°W
          </div>
          <div style={{ position: "absolute", bottom: 14, left: 14, right: 14, fontFamily: "'Fraunces', serif", fontSize: 14, lineHeight: 1.3, fontStyle: "italic" }}>
            "It is our turn to serve you."
          </div>
        </div>

        <a href="#quiz" style={{
          background: P.accent, color: "#fff", padding: "16px 24px",
          textDecoration: "none", fontSize: 13, fontWeight: 600, letterSpacing: "0.06em",
          textTransform: "uppercase", display: "flex", alignItems: "center", justifyContent: "space-between",
          marginBottom: 12
        }}>
          <span>Check your eligibility</span>
          <span>→</span>
        </a>
        <a href="#" style={{
          color: P.ink, textDecoration: "none", fontSize: 13, fontWeight: 500,
          letterSpacing: "0.04em", textTransform: "uppercase",
          display: "flex", alignItems: "center", justifyContent: "center",
          padding: "16px 24px", border: `1px solid ${P.ink}`
        }}>
          Free claim review
        </a>

        <div style={{
          marginTop: 32, paddingTop: 20, borderTop: `1px solid ${P.rule}`,
          display: "grid", gridTemplateColumns: "1fr 1fr 1fr", gap: 8,
          fontSize: 10, color: P.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em", textTransform: "uppercase"
        }}>
          <span>● Trusted</span>
          <span>● Confidential</span>
          <span>● No cost</span>
        </div>
      </section>

      {/* STATS */}
      <section style={{ borderTop: `1px solid ${P.rule}`, borderBottom: `1px solid ${P.rule}`, padding: "20px", display: "grid", gridTemplateColumns: "1fr 1fr", gap: "20px 24px" }}>
        {[
          ["Veterans helped", "1,200+"],
          ["Avg. response", "< 48h"],
          ["Across Canada", "10 prov."],
          ["Years guiding", "12+"]
        ].map(([label, num]) => (
          <div key={label}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 9, letterSpacing: "0.12em", textTransform: "uppercase", color: P.muted, marginBottom: 6 }}>{label}</div>
            <div style={{ fontFamily: "'Fraunces', serif", fontSize: 30, fontWeight: 400, lineHeight: 1, letterSpacing: "-0.02em" }}>{num}</div>
          </div>
        ))}
      </section>

      {/* WHAT YOU MIGHT BE FACING */}
      <section style={{ padding: "56px 20px" }}>
        <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", color: P.accent, marginBottom: 14 }}>02 — What we hear</div>
        <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 400, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 20px" }}>
          The benefits process can feel like <em style={{ fontStyle: "italic", color: P.accent }}>a lot</em> — especially on your own.
        </h2>
        <p style={{ fontSize: 15, lineHeight: 1.55, color: P.ink, margin: "0 0 28px", textWrap: "pretty" }}>
          VAC offers a wide range of programs, but knowing which apply to you and putting a strong application together takes time and clarity. That's where a trusted teammate can help.
        </p>
        <div style={{ display: "grid", gap: 24, paddingTop: 20, borderTop: `1px solid ${P.rule}` }}>
          {[
            ["Where do I start?", "It's hard to know which programs apply to your service history."],
            ["What do I include?", "Putting together documentation can be time-consuming on your own."],
            ["What happens next?", "Following up and understanding decisions can feel uncertain."]
          ].map(([h, p]) => (
            <div key={h}>
              <div style={{ fontFamily: "'Fraunces', serif", fontSize: 18, fontWeight: 500, marginBottom: 6, fontStyle: "italic" }}>"{h}"</div>
              <div style={{ fontSize: 13, lineHeight: 1.55, color: P.muted }}>{p}</div>
            </div>
          ))}
        </div>
      </section>

      {/* HOW WE HELP */}
      <section style={{ background: P.ink, color: P.bg, padding: "56px 20px", position: "relative", overflow: "hidden" }}>
        <MapGrid stroke={P.olive} opacity={0.08} size={32} />
        <div style={{ position: "relative" }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", color: P.olive, marginBottom: 14 }}>03 — How we help</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 400, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 20px" }}>
            A patient guide alongside <em style={{ fontStyle: "italic", color: P.olive }}>you and VAC</em>.
          </h2>
          <p style={{ fontSize: 15, lineHeight: 1.6, margin: "0 0 28px", textWrap: "pretty", color: "#d6d1c8" }}>
            We listen first, then help you understand which programs apply, what to gather, and how to put your strongest application forward.
          </p>
          <div style={{ display: "grid", gap: 20, paddingTop: 20, borderTop: `1px solid ${P.olive}33` }}>
            {[
              ["Listen", "We start with your story, your service, and what matters."],
              ["Clarify", "We translate the programs and paperwork into plain language."],
              ["Stand with you", "We stay through submission and follow-up."]
            ].map(([h, p]) => (
              <div key={h}>
                <div style={{ fontFamily: "'Fraunces', serif", fontSize: 18, fontWeight: 500, marginBottom: 6, color: P.olive }}>{h}</div>
                <div style={{ fontSize: 13, lineHeight: 1.55, color: "#bcc4d0" }}>{p}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* HOW IT WORKS */}
      <section style={{ padding: "56px 20px" }}>
        <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", color: P.accent, marginBottom: 14 }}>04 — How it works</div>
        <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 400, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 8px" }}>
          Three calm, guided steps.
        </h2>
        <p style={{ fontSize: 13, color: P.muted, margin: "0 0 28px" }}>
          Most veterans complete the eligibility check in under two minutes.
        </p>
        <div style={{ display: "grid", gap: 0, borderTop: `1px solid ${P.rule}` }}>
          {[
            { n: "01", h: "A two-minute check-in", p: "Quick questions so we can understand your service and what programs may apply.", time: "≈ 2 min · Online" },
            { n: "02", h: "A friendly conversation", p: "We talk through your situation and explain how VAC programs work.", time: "1 call · Your pace" },
            { n: "03", h: "Support through to filing", p: "We help you put a clear, complete application together for VAC.", time: "End-to-end" }
          ].map((step, i) => (
            <div key={step.n} style={{ padding: "24px 0", borderBottom: i < 2 ? `1px solid ${P.rule}` : "none" }}>
              <div style={{ display: "flex", alignItems: "baseline", gap: 16, marginBottom: 8 }}>
                <div style={{ fontFamily: "'Fraunces', serif", fontSize: 48, fontWeight: 300, lineHeight: 1, color: P.olive, letterSpacing: "-0.04em" }}>{step.n}</div>
                <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 9, letterSpacing: "0.12em", textTransform: "uppercase", color: P.muted }}>{step.time}</div>
              </div>
              <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 20, fontWeight: 500, margin: "0 0 8px", lineHeight: 1.2 }}>{step.h}</h3>
              <p style={{ fontSize: 14, lineHeight: 1.55, color: P.muted, margin: 0 }}>{step.p}</p>
            </div>
          ))}
        </div>
        <div style={{ marginTop: 28, padding: "14px 16px", background: P.bg2, fontSize: 12, color: P.muted, fontStyle: "italic", borderLeft: `2px solid ${P.olive}` }}>
          A note: 270 West is independent. We work alongside VAC, not in place of them — you can contact VAC directly anytime.
        </div>
      </section>

      {/* PROOF */}
      <section style={{ padding: "56px 20px", background: P.bg2 }}>
        <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", color: P.accent, marginBottom: 14 }}>05 — In their words</div>
        <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 400, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 28px" }}>
          Stories from veterans we've walked beside.
        </h2>
        <div style={{ display: "grid", gap: 20 }}>
          {[
            { quote: "They took the time to actually understand my service. I felt heard for the first time in years.", who: "Cpl. M.R., Halifax", since: "Client since 2024" },
            { quote: "Patient, plain-spoken, and never in a rush. They helped me get my paperwork in clean.", who: "MCpl. D.K., Ottawa", since: "Client since 2024" }
          ].map((t, i) => (
            <figure key={i} style={{ margin: 0, background: P.bg, padding: 20, border: `1px solid ${P.rule}` }}>
              <ImagePlaceholder
                label="Veteran portrait"
                spec="350 × 260 · 4:3"
                aspect="4 / 3"
                note=""
              />
              <blockquote style={{ fontFamily: "'Fraunces', serif", fontSize: 18, lineHeight: 1.3, fontWeight: 400, margin: "16px 0 14px", letterSpacing: "-0.01em" }}>
                "{t.quote}"
              </blockquote>
              <figcaption style={{ display: "flex", justifyContent: "space-between", fontSize: 10, fontFamily: "ui-monospace, monospace", letterSpacing: "0.08em", textTransform: "uppercase", color: P.muted }}>
                <span>— {t.who}</span><span>{t.since}</span>
              </figcaption>
            </figure>
          ))}
        </div>
      </section>

      {/* QUIZ — full width */}
      <section id="quiz" style={{ padding: "56px 20px" }}>
        <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.16em", textTransform: "uppercase", color: P.accent, marginBottom: 14 }}>06 — Start here</div>
        <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.025em", margin: "0 0 16px" }}>
          See what programs may apply.
        </h2>
        <p style={{ fontSize: 14, lineHeight: 1.55, color: P.muted, margin: "0 0 24px" }}>
          A quick check-in — no obligation, completely confidential.
        </p>
        <EligibilityQuiz theme="navy" compact={true} />
      </section>

      {/* CTA */}
      <section style={{ background: P.ink, color: P.bg, padding: "56px 20px", position: "relative", overflow: "hidden" }}>
        <TopoLines stroke={P.olive} opacity={0.12} />
        <div style={{ position: "relative" }}>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.025em", margin: "0 0 20px", textWrap: "balance" }}>
            It is our <em style={{ color: P.olive, fontStyle: "italic" }}>turn to serve you</em>.
          </h2>
          <p style={{ fontSize: 15, lineHeight: 1.55, color: "#d6d1c8", margin: "0 0 24px" }}>
            Whenever you're ready, we're here. Two-minute check-in or a phone call — no pressure either way.
          </p>
          <a href="#quiz" style={{
            background: P.accent, color: "#fff", padding: "16px 24px",
            textDecoration: "none", fontSize: 13, fontWeight: 600, letterSpacing: "0.06em",
            textTransform: "uppercase", display: "flex", alignItems: "center", justifyContent: "space-between",
            marginBottom: 10
          }}>
            <span>Check your eligibility</span><span>→</span>
          </a>
          <a href="tel:1-833-270-9378" style={{
            color: P.bg, textDecoration: "none", fontSize: 13, fontWeight: 500,
            letterSpacing: "0.04em", textTransform: "uppercase",
            border: `1px solid ${P.olive}`, padding: "16px 24px",
            display: "flex", alignItems: "center", justifyContent: "center"
          }}>
            Call 1-833-270-9378
          </a>
        </div>
      </section>

      {/* Mini footer */}
      <section style={{ background: P.ink, color: P.bg, padding: "32px 20px 24px", borderTop: `1px solid ${P.olive}22` }}>
        <div style={{ fontFamily: "'Fraunces', serif", fontSize: 18, marginBottom: 12 }}>
          270<span style={{ color: P.accent }}>°</span>West Consulting
        </div>
        <div style={{ fontSize: 12, color: "#a9b3c0", lineHeight: 1.6, marginBottom: 20 }}>
          Helping veterans and their families work with VAC.<br/>
          Made in Canada · BBB Accredited
        </div>
        <div style={{ display: "grid", gap: 10, fontSize: 13, color: "#d6d1c8", paddingTop: 16, borderTop: `1px solid ${P.olive}22` }}>
          {window.NAV_LINKS.map(n => <a key={n.label} href={n.href} style={{ color: "inherit", textDecoration: "none" }}>{n.label} →</a>)}
        </div>
        <div style={{ marginTop: 20, paddingTop: 14, borderTop: `1px solid ${P.olive}22`, fontFamily: "ui-monospace, monospace", fontSize: 9, letterSpacing: "0.08em", textTransform: "uppercase", color: P.olive, display: "flex", justifyContent: "space-between" }}>
          <span>© 2026</span>
          <span>Bearing 270° · True West</span>
        </div>
      </section>
    </div>
  );
};

window.DirectionAMobile = DirectionAMobile;
