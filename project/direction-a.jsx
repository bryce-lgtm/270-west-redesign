// Direction A v2 — Editorial Navigator (refined)
// Tone: collaborative, helpful resource alongside VAC — never combative.
// Locked headline: "Your sacrifice. Our commitment."

const PALETTE_A = {
  bg: "#F4F1EC",
  bg2: "#ebe6dd",
  ink: "#0E1A2B",
  muted: "#5a6472",
  rule: "#d8d2c4",
  accent: "#A5391D",
  olive: "#7A8A6A",
  oliveSoft: "#8a9a7a"
};

const NAV_LINKS = [
  { label: "How It Works", href: "how-it-works.html" },
  { label: "About", href: "about.html" },
  { label: "Resources", href: "resources.html" },
  { label: "Contact", href: "contact.html" }
];

const SiteHeader = ({ active }) => (
  <header style={{
    display: "flex", justifyContent: "space-between", alignItems: "center",
    padding: "28px 80px", borderBottom: `1px solid ${PALETTE_A.rule}`, background: PALETTE_A.bg
  }}>
    <a href="index.html" style={{ display: "flex", alignItems: "baseline", gap: 14, textDecoration: "none", color: PALETTE_A.ink }}>
      <div style={{ fontFamily: "'Fraunces', Georgia, serif", fontSize: 26, fontWeight: 500, letterSpacing: "-0.01em", lineHeight: 1 }}>
        270<span style={{ color: PALETTE_A.accent }}>°</span>West
      </div>
      <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", color: PALETTE_A.muted, textTransform: "uppercase" }}>
        Consulting · Canada
      </div>
    </a>
    <nav style={{ display: "flex", gap: 36, alignItems: "center", fontSize: 14 }}>
      {NAV_LINKS.map(item => (
        <a key={item.label} href={item.href} style={{
          color: PALETTE_A.ink, textDecoration: "none", fontWeight: 450,
          borderBottom: active === item.label ? `1px solid ${PALETTE_A.accent}` : "1px solid transparent",
          paddingBottom: 4
        }}>{item.label}</a>
      ))}
      <a href="#quiz" style={{
        background: PALETTE_A.ink, color: PALETTE_A.bg, padding: "12px 22px",
        textDecoration: "none", fontSize: 13, fontWeight: 500, letterSpacing: "0.04em", textTransform: "uppercase"
      }}>Check Eligibility →</a>
    </nav>
  </header>
);

const SiteFooter = () => (
  <section style={{ background: PALETTE_A.ink, color: PALETTE_A.bg, padding: "100px 80px 40px", position: "relative", overflow: "hidden" }}>
    <TopoLines stroke={PALETTE_A.olive} opacity={0.12} />
    <div style={{
      position: "relative", borderTop: `1px solid ${PALETTE_A.olive}33`, paddingTop: 32,
      display: "grid", gridTemplateColumns: "1.4fr 1fr 1fr 1fr", gap: 40,
      fontSize: 13, color: "#a9b3c0"
    }}>
      <div>
        <div style={{ fontFamily: "'Fraunces', serif", fontSize: 22, color: PALETTE_A.bg, marginBottom: 8 }}>
          270<span style={{ color: PALETTE_A.accent }}>°</span>West Consulting
        </div>
        <div style={{ lineHeight: 1.6 }}>
          Helping veterans and their families work with VAC.<br/>
          Made in Canada · BBB Accredited
        </div>
      </div>
      {[
        ["Explore", NAV_LINKS.map(n => n.label)],
        ["Get in touch", ["1-833-270-9378", "info@270westconsulting.ca"]],
        ["Languages", ["English", "Français (Canada)"]]
      ].map(([h, items]) => (
        <div key={h}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: PALETTE_A.olive, marginBottom: 14 }}>{h}</div>
          <div style={{ display: "grid", gap: 8 }}>{items.map(i => <div key={i}>{i}</div>)}</div>
        </div>
      ))}
    </div>
    <div style={{ position: "relative", marginTop: 40, paddingTop: 20, borderTop: `1px solid ${PALETTE_A.olive}22`, fontSize: 11, fontFamily: "ui-monospace, monospace", letterSpacing: "0.08em", textTransform: "uppercase", color: PALETTE_A.olive, display: "flex", justifyContent: "space-between" }}>
      <span>© 2026 270 West Consulting</span>
      <span>Bearing 270° · True West</span>
    </div>
  </section>
);

const DirectionA = () => {
  const sectionPad = "120px 80px";

  return (
    <div style={{
      width: 1440, fontFamily: "'Inter', system-ui, sans-serif",
      background: PALETTE_A.bg, color: PALETTE_A.ink, position: "relative"
    }}>
      <SiteHeader active="Home" />

      {/* HERO — collaborative, gratitude-led */}
      <section style={{ padding: "100px 80px 80px", position: "relative", overflow: "hidden" }}>
        <div style={{ display: "grid", gridTemplateColumns: "1.4fr 1fr", gap: 64, alignItems: "start" }}>
          <div>
            <div style={{
              fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em",
              textTransform: "uppercase", color: PALETTE_A.muted, marginBottom: 32,
              display: "flex", gap: 12, alignItems: "center"
            }}>
              <span style={{ width: 32, height: 1, background: PALETTE_A.muted }} />
              Veterans Affairs Canada — Claims Support
            </div>
            <h1 style={{
              fontFamily: "'Fraunces', Georgia, serif", fontSize: 84, fontWeight: 400,
              lineHeight: 1.0, letterSpacing: "-0.025em", margin: "0 0 32px",
              maxWidth: 800, textWrap: "balance"
            }}>
              Your sacrifice.<br/>
              <em style={{ fontStyle: "italic", color: PALETTE_A.accent }}>Our commitment.</em>
            </h1>
            <p style={{ fontSize: 20, lineHeight: 1.55, color: PALETTE_A.ink, maxWidth: 580, margin: "0 0 16px", textWrap: "pretty" }}>
              We help Canadian veterans and their families understand and access the benefits available through Veterans Affairs Canada — with patient guidance, clear next steps, and a teammate at your side.
            </p>
            <p style={{ fontSize: 16, lineHeight: 1.55, color: PALETTE_A.muted, maxWidth: 540, margin: "0 0 40px" }}>
              We don't replace VAC. We help you work with them — confidently and with everything in order.
            </p>
            <div style={{ display: "flex", gap: 14, alignItems: "center", flexWrap: "wrap" }}>
              <a href="#quiz" style={{
                background: PALETTE_A.accent, color: "#fff", padding: "18px 32px",
                textDecoration: "none", fontSize: 14, fontWeight: 600, letterSpacing: "0.06em",
                textTransform: "uppercase", display: "inline-flex", alignItems: "center", gap: 12
              }}>
                Check your eligibility <span>→</span>
              </a>
              <a href="#" style={{
                color: PALETTE_A.ink, textDecoration: "none", fontSize: 14, fontWeight: 500,
                letterSpacing: "0.04em", textTransform: "uppercase",
                borderBottom: `1px solid ${PALETTE_A.ink}`, paddingBottom: 4
              }}>
                Free claim review
              </a>
            </div>
            <div style={{
              marginTop: 56, paddingTop: 24, borderTop: `1px solid ${PALETTE_A.rule}`,
              display: "flex", gap: 48, fontSize: 13, color: PALETTE_A.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em", textTransform: "uppercase"
            }}>
              <span>● Trusted nationwide</span>
              <span>● Confidential</span>
              <span>● No obligation</span>
            </div>
          </div>

          {/* Compass card */}
          <div style={{ position: "relative" }}>
            <div style={{
              background: PALETTE_A.ink, color: PALETTE_A.bg, padding: 40, position: "relative", overflow: "hidden",
              aspectRatio: "4 / 5"
            }}>
              <TopoLines stroke={PALETTE_A.olive} opacity={0.18} />
              <div style={{ position: "absolute", inset: 0, display: "flex", alignItems: "center", justifyContent: "center" }}>
                <div style={{ color: PALETTE_A.olive, opacity: 0.85 }}>
                  <CompassRose size={340} stroke="currentColor" strokeWidth={1} bearing={270} />
                </div>
              </div>
              <div style={{ position: "absolute", top: 24, left: 24, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", color: PALETTE_A.olive, textTransform: "uppercase" }}>
                Bearing 270°W
              </div>
              <div style={{ position: "absolute", top: 24, right: 24, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", color: PALETTE_A.olive, textTransform: "uppercase" }}>
                Halifax · 44.6°N
              </div>
              <div style={{ position: "absolute", bottom: 24, left: 24, right: 24, fontFamily: "'Fraunces', serif", fontSize: 22, lineHeight: 1.3, fontStyle: "italic" }}>
                "It is our turn<br/>to serve you."
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* STATS BAR — softer, supportive numbers */}
      <section style={{ borderTop: `1px solid ${PALETTE_A.rule}`, borderBottom: `1px solid ${PALETTE_A.rule}`, padding: "32px 80px", display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 0 }}>
        {[
          ["Veterans & families helped", "1,200+"],
          ["Avg. response time", "< 48h"],
          ["Across Canada", "10 provinces"],
          ["Years guiding claims", "12+"]
        ].map(([label, num], i) => (
          <div key={label} style={{ paddingLeft: i === 0 ? 0 : 32, borderLeft: i === 0 ? "none" : `1px solid ${PALETTE_A.rule}` }}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: PALETTE_A.muted, marginBottom: 8 }}>{label}</div>
            <div style={{ fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 400, lineHeight: 1, letterSpacing: "-0.02em" }}>{num}</div>
          </div>
        ))}
      </section>

      {/* "What you might be facing" — empathetic, not combative */}
      <section style={{ padding: sectionPad, display: "grid", gridTemplateColumns: "1fr 2fr", gap: 80, alignItems: "start" }}>
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: PALETTE_A.accent, marginBottom: 16 }}>02 — What we hear</div>
        </div>
        <div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 56, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.02em", margin: "0 0 32px", maxWidth: 720 }}>
            The benefits process can feel like <em style={{ fontStyle: "italic", color: PALETTE_A.accent }}>a lot</em> — especially on your own.
          </h2>
          <p style={{ fontSize: 19, lineHeight: 1.6, color: PALETTE_A.ink, maxWidth: 700, margin: "0 0 48px", textWrap: "pretty" }}>
            VAC offers a wide range of programs for Canadian veterans and their families — but knowing which ones apply to you, and how to put a strong, complete application together, takes time, energy, and clarity. That's where a trusted teammate can help.
          </p>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 32, paddingTop: 32, borderTop: `1px solid ${PALETTE_A.rule}` }}>
            {[
              ["Where do I start?", "It's hard to know which programs and benefits apply to your service history."],
              ["What do I include?", "Putting together the right documentation can be time-consuming on your own."],
              ["What happens next?", "Following up and understanding decisions can feel uncertain without a guide."]
            ].map(([h, p]) => (
              <div key={h}>
                <div style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, marginBottom: 10, fontStyle: "italic", color: PALETTE_A.ink }}>"{h}"</div>
                <div style={{ fontSize: 15, lineHeight: 1.55, color: PALETTE_A.muted }}>{p}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* HOW WE HELP — replaces "solution" */}
      <section style={{ background: PALETTE_A.ink, color: PALETTE_A.bg, padding: sectionPad, position: "relative", overflow: "hidden" }}>
        <MapGrid stroke={PALETTE_A.olive} opacity={0.08} size={48} />
        <div style={{ position: "relative", display: "grid", gridTemplateColumns: "1fr 2fr", gap: 80 }}>
          <div>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: PALETTE_A.olive, marginBottom: 16 }}>03 — How we help</div>
          </div>
          <div>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 56, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.02em", margin: "0 0 32px", maxWidth: 760 }}>
              A patient guide alongside <em style={{ fontStyle: "italic", color: PALETTE_A.olive }}>you and VAC</em>.
            </h2>
            <p style={{ fontSize: 19, lineHeight: 1.6, maxWidth: 700, margin: "0 0 32px", textWrap: "pretty", color: "#d6d1c8" }}>
              We sit alongside you as you work with Veterans Affairs Canada. We listen first, then help you understand which programs apply, what to gather, and how to put your strongest application forward — so the conversation with VAC goes more smoothly for everyone.
            </p>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 24, paddingTop: 24, borderTop: `1px solid ${PALETTE_A.olive}33` }}>
              {[
                ["Listen", "We start with your story, your service, and what matters to your family."],
                ["Clarify", "We translate the programs and paperwork into clear, plain language."],
                ["Stand with you", "We stay with you through submission, follow-up, and any next steps."]
              ].map(([h, p]) => (
                <div key={h}>
                  <div style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, marginBottom: 10, color: PALETTE_A.olive }}>{h}</div>
                  <div style={{ fontSize: 14, lineHeight: 1.55, color: "#bcc4d0" }}>{p}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* HOW IT WORKS — refined, gentler language */}
      <section style={{ padding: sectionPad }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "end", marginBottom: 64, gap: 40 }}>
          <div>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: PALETTE_A.accent, marginBottom: 16 }}>04 — How it works</div>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 56, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.02em", margin: 0, maxWidth: 720 }}>
              Three calm, guided steps.
            </h2>
          </div>
          <div style={{ fontSize: 14, color: PALETTE_A.muted, maxWidth: 320, textAlign: "right" }}>
            Most veterans complete an eligibility check in under two minutes. From there, we move at your pace.
          </div>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 0, borderTop: `1px solid ${PALETTE_A.ink}` }}>
          {[
            { n: "01", h: "A two-minute check-in", p: "Answer a few quick questions so we can understand your service and what programs may apply. No commitment.", time: "≈ 2 min · Online" },
            { n: "02", h: "A friendly conversation", p: "We talk through your situation, explain how VAC's programs work, and help you decide on next steps that feel right.", time: "1 call · Your pace" },
            { n: "03", h: "Support through to filing", p: "We help you put a clear, complete application together for VAC — and stay with you through any follow-up that's needed.", time: "End-to-end" }
          ].map((step, i) => (
            <div key={step.n} style={{
              padding: "40px 32px 40px 0", borderRight: i < 2 ? `1px solid ${PALETTE_A.rule}` : "none",
              paddingLeft: i === 0 ? 0 : 32
            }}>
              <div style={{ fontFamily: "'Fraunces', serif", fontSize: 84, fontWeight: 300, lineHeight: 1, color: PALETTE_A.olive, marginBottom: 24, letterSpacing: "-0.04em" }}>{step.n}</div>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: PALETTE_A.muted, marginBottom: 12 }}>{step.time}</div>
              <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 26, fontWeight: 500, margin: "0 0 14px", lineHeight: 1.2 }}>{step.h}</h3>
              <p style={{ fontSize: 15, lineHeight: 1.6, color: PALETTE_A.muted, margin: 0 }}>{step.p}</p>
            </div>
          ))}
        </div>
        <div style={{ marginTop: 48, padding: "20px 24px", background: PALETTE_A.bg2, fontSize: 14, color: PALETTE_A.muted, fontStyle: "italic", borderLeft: `2px solid ${PALETTE_A.olive}` }}>
          A note: 270 West Consulting is an independent claims advisory service. We work alongside Veterans Affairs Canada, not in place of them — and you can contact VAC directly at any time.
        </div>
      </section>

      {/* PROOF */}
      <section style={{ padding: sectionPad, background: PALETTE_A.bg2 }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 2fr", gap: 80, alignItems: "start", marginBottom: 64 }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: PALETTE_A.accent }}>05 — In their words</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 56, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.02em", margin: 0, maxWidth: 720 }}>
            Stories from veterans we've walked beside.
          </h2>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 32 }}>
          {[
            { quote: "They took the time to actually understand my service. I felt heard for the first time in years.", who: "Cpl. M.R., Halifax", since: "Client since 2024" },
            { quote: "Patient, plain-spoken, and never in a rush. They helped me get my paperwork in clean and complete.", who: "MCpl. D.K., Ottawa", since: "Client since 2024" },
            { quote: "It felt like having a teammate who knew the way. We went through it together — that meant everything.", who: "Sgt. (Ret.) J.L., Edmonton", since: "Client since 2025" }
          ].map((t, i) => (
            <figure key={i} style={{ margin: 0, background: PALETTE_A.bg, padding: 32, border: `1px solid ${PALETTE_A.rule}` }}>
              <ImagePlaceholder
                label="Veteran portrait"
                spec="380 × 285 · JPG · 4:3"
                aspect="4 / 3"
                note="Real client photo — face, environment, calm tone."
              />
              <blockquote style={{ fontFamily: "'Fraunces', serif", fontSize: 22, lineHeight: 1.3, fontWeight: 400, margin: "24px 0 20px", letterSpacing: "-0.01em" }}>
                "{t.quote}"
              </blockquote>
              <figcaption style={{ display: "flex", justifyContent: "space-between", fontSize: 12, fontFamily: "ui-monospace, monospace", letterSpacing: "0.08em", textTransform: "uppercase", color: PALETTE_A.muted }}>
                <span>— {t.who}</span><span>{t.since}</span>
              </figcaption>
            </figure>
          ))}
        </div>
      </section>

      {/* QUIZ */}
      <section id="quiz" style={{ padding: sectionPad }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1.4fr", gap: 64, alignItems: "start" }}>
          <div style={{ position: "sticky", top: 32 }}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: PALETTE_A.accent, marginBottom: 16 }}>06 — Start here</div>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 60, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.025em", margin: "0 0 24px" }}>
              See what programs may apply to you.
            </h2>
            <p style={{ fontSize: 18, lineHeight: 1.55, color: PALETTE_A.muted, maxWidth: 460, margin: "0 0 32px" }}>
              A quick check-in — no obligation, completely confidential. We'll get back to you with a clear, friendly next step.
            </p>
            <div style={{ display: "grid", gap: 12, fontSize: 14, color: PALETTE_A.ink }}>
              {["Confidential & no obligation", "Reviewed by a real advocate", "You can contact VAC directly anytime"].map(l => (
                <div key={l} style={{ display: "flex", gap: 12, alignItems: "center" }}>
                  <div style={{ width: 6, height: 6, background: PALETTE_A.accent, borderRadius: 99 }} />
                  {l}
                </div>
              ))}
            </div>
          </div>
          <EligibilityQuiz theme="navy" />
        </div>
      </section>

      {/* CTA */}
      <section style={{ background: PALETTE_A.ink, color: PALETTE_A.bg, padding: "100px 80px", position: "relative", overflow: "hidden" }}>
        <TopoLines stroke={PALETTE_A.olive} opacity={0.12} />
        <div style={{ position: "relative", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 80, alignItems: "center" }}>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 64, fontWeight: 400, lineHeight: 1.05, letterSpacing: "-0.025em", margin: 0, textWrap: "balance" }}>
            It is our <em style={{ color: PALETTE_A.olive, fontStyle: "italic" }}>turn to serve you</em>.
          </h2>
          <div>
            <p style={{ fontSize: 18, lineHeight: 1.55, color: "#d6d1c8", maxWidth: 460, margin: "0 0 28px" }}>
              Whenever you're ready, we're here. Start with a two-minute check-in or call us directly — no pressure either way.
            </p>
            <div style={{ display: "flex", gap: 14, flexWrap: "wrap" }}>
              <a href="#quiz" style={{
                background: PALETTE_A.accent, color: "#fff", padding: "18px 32px",
                textDecoration: "none", fontSize: 14, fontWeight: 600, letterSpacing: "0.06em",
                textTransform: "uppercase", display: "inline-flex", alignItems: "center", gap: 12
              }}>
                Check your eligibility →
              </a>
              <a href="tel:1-833-270-9378" style={{
                color: PALETTE_A.bg, textDecoration: "none", fontSize: 14, fontWeight: 500,
                letterSpacing: "0.04em", textTransform: "uppercase",
                border: `1px solid ${PALETTE_A.olive}`, padding: "18px 32px"
              }}>
                Call 1-833-270-9378
              </a>
            </div>
          </div>
        </div>
      </section>

      <SiteFooter />
    </div>
  );
};

window.DirectionA = DirectionA;
window.SiteHeader = SiteHeader;
window.SiteFooter = SiteFooter;
window.PALETTE_A = PALETTE_A;
window.NAV_LINKS = NAV_LINKS;
