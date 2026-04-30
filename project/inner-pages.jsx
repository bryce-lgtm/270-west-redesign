// Inner pages — How It Works, About, Resources, Contact
// SEO-led H1s (keyword-first, scannable). Editorial line demoted to lead/kicker.
// Same collaborative tone — 270 West works alongside VAC, never against.

// ===================== Page hero (SEO-led) =====================

const PageHero = ({ kicker, h1, lead, sub, active }) => {
  const palette = window.PALETTE_A;
  return (
    <>
      <window.SiteHeader active={active} />
      <section style={{ padding: "100px 80px 80px", borderBottom: `1px solid ${palette.rule}` }}>
        <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent, marginBottom: 24, display: "flex", gap: 12, alignItems: "center" }}>
          <span style={{ width: 32, height: 1, background: palette.accent }} />
          {kicker}
        </div>
        {/* SEO H1 — keyword-led, plain, scannable */}
        <h1 style={{ fontFamily: "'Fraunces', serif", fontSize: 64, fontWeight: 500, lineHeight: 1.05, letterSpacing: "-0.02em", margin: "0 0 20px", maxWidth: 1100, textWrap: "balance" }}>
          {h1}
        </h1>
        {/* Editorial lead — keeps brand voice without competing for SEO weight */}
        {lead && (
          <p style={{ fontFamily: "'Fraunces', serif", fontSize: 28, fontWeight: 400, fontStyle: "italic", lineHeight: 1.25, color: palette.accent, margin: "0 0 24px", maxWidth: 900, letterSpacing: "-0.01em" }}>
            {lead}
          </p>
        )}
        {sub && (
          <p style={{ fontSize: 19, lineHeight: 1.55, color: palette.muted, maxWidth: 760, margin: 0 }}>
            {sub}
          </p>
        )}
      </section>
    </>
  );
};

// ===================== HOW IT WORKS =====================

const HowItWorksPage = () => {
  const palette = window.PALETTE_A;
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="How It Works"
        kicker="How it works"
        h1="How VAC disability benefits work — and how 270 West helps Canadian veterans apply."
        lead="Three calm steps. At your pace."
        sub="From a quick eligibility check to a complete Veterans Affairs Canada application — we work alongside you, in plain language, with no pressure."
      />

      {/* Image — process overview */}
      <section style={{ padding: "60px 80px 0" }}>
        <ImagePlaceholder
          label="Process overview — wide editorial image"
          spec="1440 × 540 · JPG/WebP · ~150KB"
          note="Recommend a real photo of a 270 West advisor in conversation with a Canadian veteran. Warm, candid, document-on-table tone. Avoid stock photography."
          aspect="1440 / 540"
        />
      </section>

      {/* Detailed steps */}
      <section style={{ padding: "80px 80px 100px" }}>
        {[
          { n: "01", time: "≈ 2 min · Online", h: "Step 1: Check your eligibility for VAC benefits", p: "Answer five short questions about your service and what you're looking to do. We use this only to understand which Veterans Affairs Canada programs may apply — not to pre-qualify or pressure you. It's free, confidential, and you can stop anytime.", details: ["Five short questions", "No documents required", "Confidential & no obligation"] },
          { n: "02", time: "1 call · Your pace", h: "Step 2: Talk through your situation with an advisor", p: "We schedule a no-cost call with one of our claims advisors. We listen first, walk through your situation in plain language, and explain how VAC's programs work. You decide if and when to take a next step — there's never any pressure.", details: ["No-cost initial call", "We listen before we advise", "You decide next steps"] },
          { n: "03", time: "End-to-end", h: "Step 3: Build a complete VAC application — together", p: "If you'd like our help, we walk you through gathering documentation, understanding what VAC needs, and putting together a clear, complete application. We stay with you through any follow-up VAC may need.", details: ["Document checklists", "Plain-language guidance", "Stay with you through follow-up"] }
        ].map((step, i) => (
          <div key={step.n} style={{
            display: "grid", gridTemplateColumns: "120px 1fr 320px", gap: 64,
            padding: "56px 0", borderTop: `1px solid ${palette.rule}`,
            borderBottom: i === 2 ? `1px solid ${palette.rule}` : "none"
          }}>
            <div>
              <div style={{ fontFamily: "'Fraunces', serif", fontSize: 96, fontWeight: 300, lineHeight: 0.9, color: palette.olive, letterSpacing: "-0.04em" }}>{step.n}</div>
              <div style={{ marginTop: 16, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted }}>{step.time}</div>
            </div>
            <div>
              {/* H2 — keyword-rich step heading */}
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.15, letterSpacing: "-0.02em", margin: "0 0 20px" }}>{step.h}</h2>
              <p style={{ fontSize: 18, lineHeight: 1.6, color: palette.ink, margin: "0 0 24px", maxWidth: 640, textWrap: "pretty" }}>{step.p}</p>
              <ImagePlaceholder
                label={`Step ${step.n} supporting image`}
                spec="640 × 400 · JPG/WebP"
                aspect="16 / 10"
                note={i === 0 ? "Screenshot or illustration of the eligibility check-in flow." : i === 1 ? "Photo of advisor on a call — desk, paperwork, warm lighting." : "Real veteran portrait — paperwork in hand, application complete."}
              />
            </div>
            <div style={{ background: palette.bg2, padding: 24, borderLeft: `2px solid ${palette.olive}` }}>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.muted, marginBottom: 14 }}>What to expect</div>
              <div style={{ display: "grid", gap: 10, fontSize: 14, color: palette.ink }}>
                {step.details.map(d => <div key={d} style={{ display: "flex", gap: 10 }}><span style={{ color: palette.accent }}>·</span>{d}</div>)}
              </div>
            </div>
          </div>
        ))}

        <div style={{ marginTop: 56, padding: "24px 28px", background: palette.bg2, fontSize: 15, color: palette.muted, fontStyle: "italic", borderLeft: `2px solid ${palette.olive}`, lineHeight: 1.55, maxWidth: 900 }}>
          A note on our role: 270 West Consulting is an independent claims advisory service. We work alongside Veterans Affairs Canada, not in place of them — and you can contact VAC directly at any time at 1-866-522-2122 or vac.gc.ca.
        </div>
      </section>

      {/* FAQ — H2 keyword-led */}
      <section style={{ padding: "100px 80px", background: palette.bg2 }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 2fr", gap: 64 }}>
          <div>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent, marginBottom: 16 }}>Common questions</div>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: 0 }}>
              Frequently asked questions about VAC claims support
            </h2>
          </div>
          <div style={{ display: "grid", gap: 0, borderTop: `1px solid ${palette.rule}` }}>
            {[
              ["Is 270 West's eligibility check free?", "The eligibility check-in and your first conversation are completely free, with no obligation. If you'd like our help with a full application, we'll explain how that works upfront — no surprises."],
              ["Is 270 West part of Veterans Affairs Canada?", "No. We're an independent advisory service based in Canada. We work alongside Veterans Affairs Canada to help you put together your strongest application — but you can always contact VAC directly."],
              ["Can I apply for VAC benefits on my own?", "Absolutely. VAC accepts applications directly from veterans, and many people apply on their own. We're here if you'd like a teammate to help you through it."],
              ["How long does the VAC application process take?", "The eligibility check is about two minutes. From there, timelines depend on your situation and on VAC's review process. We'll set realistic expectations together on our first call."],
              ["What if my VAC claim has already been denied?", "We can still help. Sometimes a fresh look at the documentation or framing of a claim makes a meaningful difference. Bring us your story and we'll walk through it together."]
            ].map(([q, a]) => (
              <details key={q} style={{ padding: "24px 0", borderBottom: `1px solid ${palette.rule}` }}>
                <summary style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, cursor: "pointer", listStyle: "none", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                  {q}
                  <span style={{ color: palette.accent, fontFamily: "ui-monospace, monospace", fontSize: 14 }}>+</span>
                </summary>
                <p style={{ fontSize: 16, lineHeight: 1.6, color: palette.muted, margin: "16px 0 0", maxWidth: 720 }}>{a}</p>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section style={{ background: palette.ink, color: palette.bg, padding: "100px 80px", position: "relative", overflow: "hidden" }}>
        <TopoLines stroke={palette.olive} opacity={0.12} />
        <div style={{ position: "relative", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 80, alignItems: "center" }}>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 52, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.025em", margin: 0 }}>
            Start your free VAC eligibility check
          </h2>
          <div>
            <p style={{ fontSize: 17, lineHeight: 1.55, color: "#d6d1c8", maxWidth: 460, margin: "0 0 28px" }}>
              No obligation. Confidential. The first conversation is on us.
            </p>
            <a href="index.html#quiz" style={{
              background: palette.accent, color: "#fff", padding: "18px 32px",
              textDecoration: "none", fontSize: 14, fontWeight: 600, letterSpacing: "0.06em",
              textTransform: "uppercase", display: "inline-flex", alignItems: "center", gap: 12
            }}>
              Check your eligibility →
            </a>
          </div>
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== ABOUT =====================

const AboutPage = () => {
  const palette = window.PALETTE_A;
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="About"
        kicker="About 270 West"
        h1="About 270 West Consulting — VAC claims support for Canadian veterans."
        lead="True west. For those who served."
        sub="270 West Consulting is an independent claims advisory service helping Canadian veterans and their families navigate Veterans Affairs Canada with patience, plain language, and respect."
      />

      {/* Story */}
      <section style={{ padding: "100px 80px", display: "grid", gridTemplateColumns: "1fr 1.4fr", gap: 80, alignItems: "start" }}>
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent, marginBottom: 16 }}>Our story</div>
          <ImagePlaceholder
            label="Founder portrait or hero brand image"
            spec="640 × 800 · JPG/WebP · 4:5 portrait"
            aspect="4 / 5"
            note="Real photo of the founder, or a Halifax-coast brand image. Avoid stock — authenticity matters."
            theme="dark"
          />
        </div>
        <div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 40, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 28px", maxWidth: 700 }}>
            Why we started 270 West Consulting
          </h2>
          <p style={{ fontSize: 18, lineHeight: 1.6, color: palette.ink, margin: "0 0 20px", textWrap: "pretty", maxWidth: 680 }}>
            We started 270 West because we saw too many Canadian veterans trying to make sense of the VAC benefits process on their own. Not because they didn't qualify — but because the path through it can feel overwhelming, especially while balancing health, family, and life after service.
          </p>
          <p style={{ fontSize: 18, lineHeight: 1.6, color: palette.ink, margin: "0 0 20px", textWrap: "pretty", maxWidth: 680 }}>
            Our job isn't to replace Veterans Affairs Canada. It's to sit alongside you — listening first, then helping translate paperwork, programs, and timelines into something clear and manageable. We move at your pace, never the other way around.
          </p>
          <p style={{ fontSize: 18, lineHeight: 1.6, color: palette.muted, margin: 0, fontStyle: "italic", maxWidth: 680 }}>
            "Bearing 270°W" is the compass heading for true west. It's how we navigate: steady, honest, and pointed toward what veterans have earned.
          </p>
        </div>
      </section>

      {/* Values */}
      <section style={{ padding: "100px 80px", background: palette.bg2, borderTop: `1px solid ${palette.rule}` }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 2fr", gap: 64, marginBottom: 64 }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent }}>What we stand for</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: 0, maxWidth: 720 }}>
            Our values: how we work with veterans
          </h2>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 0, borderTop: `1px solid ${palette.rule}` }}>
          {[
            ["Patience", "We move at your pace. No high-pressure tactics — ever."],
            ["Plain language", "We explain programs, paperwork, and timelines without jargon."],
            ["Independence", "We're independent of VAC. Honest about what we can and can't do."],
            ["Respect", "Your service, your story, your decisions — always."]
          ].map(([h, p], i) => (
            <div key={h} style={{ padding: "32px 24px 32px 0", borderRight: i < 3 ? `1px solid ${palette.rule}` : "none", paddingLeft: i === 0 ? 0 : 24 }}>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 14 }}>0{i + 1}</div>
              <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 26, fontWeight: 500, margin: "0 0 12px" }}>{h}</h3>
              <p style={{ fontSize: 14, lineHeight: 1.6, color: palette.muted, margin: 0 }}>{p}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Team */}
      <section style={{ padding: "100px 80px" }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 2fr", gap: 64, marginBottom: 56 }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent }}>The team</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: 0, maxWidth: 720 }}>
            Meet the team behind 270 West Consulting
          </h2>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 32 }}>
          {[
            { name: "[ Founder name ]", role: "Founder & Lead Advisor", bio: "Spent over a decade walking veterans and their families through the VAC system. Halifax-based." },
            { name: "[ Advisor name ]", role: "Senior Claims Advisor", bio: "Former service member with deep experience translating military service into VAC paperwork." },
            { name: "[ Advisor name ]", role: "Client Care Lead", bio: "First point of contact for new veterans. Patient, plain-spoken, never in a rush." }
          ].map((m, i) => (
            <div key={i} style={{ background: palette.bg, border: `1px solid ${palette.rule}`, padding: 24 }}>
              <ImagePlaceholder
                label="Team member portrait"
                spec="320 × 400 · JPG · 4:5"
                aspect="4 / 5"
                note="Real photo. Consistent lighting and crop across team."
              />
              <div style={{ marginTop: 20 }}>
                <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, margin: "0 0 6px" }}>{m.name}</h3>
                <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted, marginBottom: 12 }}>{m.role}</div>
                <p style={{ fontSize: 14, lineHeight: 1.55, color: palette.muted, margin: 0 }}>{m.bio}</p>
              </div>
            </div>
          ))}
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== RESOURCES =====================

const ResourcesPage = () => {
  const palette = window.PALETTE_A;
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="Resources"
        kicker="Resources"
        h1="VAC benefits resources, guides, and checklists for Canadian veterans."
        lead="Plain-language guides for navigating VAC."
        sub="Free, no-jargon explainers and checklists to help you understand Veterans Affairs Canada programs, paperwork, and timelines — whether you choose to work with us or not."
      />

      {/* Featured guides */}
      <section style={{ padding: "100px 80px" }}>
        <h2 style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent, margin: "0 0 24px", fontWeight: 500 }}>Start here</h2>
        <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr 1fr", gap: 24 }}>
          <a href="#" style={{ background: palette.ink, color: palette.bg, padding: 0, position: "relative", overflow: "hidden", textDecoration: "none", display: "block" }}>
            <ImagePlaceholder
              label="Featured guide cover image"
              spec="900 × 560 · JPG · 16:10"
              aspect="16 / 10"
              theme="dark"
              note="Editorial illustration or photo for the lead VAC guide."
            />
            <div style={{ padding: "32px 40px 40px" }}>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 12 }}>Featured guide · 18 min read</div>
              <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 500, lineHeight: 1.15, letterSpacing: "-0.02em", margin: "0 0 12px", maxWidth: 600 }}>
                A plain-language guide to VAC's main benefits programs
              </h3>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.1em", color: palette.olive, textTransform: "uppercase" }}>Read guide →</div>
            </div>
          </a>
          {[
            { tag: "Checklist · 5 min", title: "VAC application checklist: documents to gather" },
            { tag: "Explainer · 8 min", title: "How VAC disability ratings actually work" }
          ].map((g, i) => (
            <a key={i} href="#" style={{ background: palette.bg2, padding: 0, textDecoration: "none", color: palette.ink, display: "flex", flexDirection: "column" }}>
              <ImagePlaceholder
                label={`Guide ${i + 1} cover`}
                spec="440 × 280 · JPG · 16:10"
                aspect="16 / 10"
                note=""
              />
              <div style={{ padding: 24, display: "flex", flexDirection: "column", justifyContent: "space-between", flex: 1 }}>
                <div>
                  <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.accent, marginBottom: 14 }}>{g.tag}</div>
                  <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, lineHeight: 1.25, margin: 0 }}>{g.title}</h3>
                </div>
                <div style={{ marginTop: 24, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.1em", color: palette.muted, textTransform: "uppercase" }}>Read →</div>
              </div>
            </a>
          ))}
        </div>
      </section>

      {/* Library */}
      <section style={{ padding: "100px 80px", background: palette.bg2, borderTop: `1px solid ${palette.rule}` }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 2fr", gap: 64, marginBottom: 56 }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent }}>Library</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 44, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: 0, maxWidth: 720 }}>
            Browse VAC resources by topic
          </h2>
        </div>
        {[
          { topic: "VAC eligibility & programs", items: ["Disability Benefits — what they cover", "Income Replacement Benefit explained", "Career Transition Services", "Family caregiver support"] },
          { topic: "Filing your first VAC claim", items: ["What to expect when you apply", "Building your service-related health record", "Getting medical documentation right", "Common reasons applications come back"] },
          { topic: "After a VAC decision", items: ["Understanding your decision letter", "When to consider a re-assessment", "How VAC appeals work", "Mental health & wellness resources"] }
        ].map(group => (
          <div key={group.topic} style={{ borderTop: `1px solid ${palette.rule}`, padding: "32px 0" }}>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 3fr", gap: 64, alignItems: "start" }}>
              <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, margin: 0 }}>{group.topic}</h3>
              <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 0 }}>
                {group.items.map(item => (
                  <a key={item} href="#" style={{ padding: "16px 0", borderBottom: `1px solid ${palette.rule}`, textDecoration: "none", color: palette.ink, fontSize: 16, display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                    <span>{item}</span>
                    <span style={{ color: palette.accent, fontFamily: "ui-monospace, monospace", fontSize: 12 }}>→</span>
                  </a>
                ))}
              </div>
            </div>
          </div>
        ))}
      </section>

      {/* Direct VAC links */}
      <section style={{ padding: "80px 80px", display: "grid", gridTemplateColumns: "1fr 2fr", gap: 64 }}>
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.olive, marginBottom: 14 }}>Going direct</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: 0 }}>
            Contact Veterans Affairs Canada directly
          </h2>
        </div>
        <div style={{ display: "grid", gap: 16 }}>
          {[
            ["Veterans Affairs Canada — Main", "vac.gc.ca", "1-866-522-2122"],
            ["My VAC Account (online portal)", "vac.gc.ca/myaccount", "—"],
            ["VAC Assistance Service (24/7)", "Mental health support", "1-800-268-7708"]
          ].map(([name, link, phone]) => (
            <div key={name} style={{ display: "grid", gridTemplateColumns: "2fr 1.5fr 1fr", gap: 24, padding: "20px 0", borderTop: `1px solid ${palette.rule}`, alignItems: "center" }}>
              <div style={{ fontFamily: "'Fraunces', serif", fontSize: 18, fontWeight: 500 }}>{name}</div>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 13, color: palette.muted }}>{link}</div>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 13, color: palette.ink, textAlign: "right" }}>{phone}</div>
            </div>
          ))}
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== CONTACT =====================

const ContactPage = () => {
  const palette = window.PALETTE_A;
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="Contact"
        kicker="Contact"
        h1="Contact 270 West Consulting — VAC claims help in Canada"
        lead="Reach out. We'll listen."
        sub="Whether you have a quick question about Veterans Affairs Canada benefits or want to walk through your situation in detail — we're here. No pressure, no obligation."
      />

      <section style={{ padding: "100px 80px", display: "grid", gridTemplateColumns: "1.2fr 1fr", gap: 80, alignItems: "start" }}>
        {/* Form */}
        <div>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.16em", textTransform: "uppercase", color: palette.accent, marginBottom: 14 }}>Send us a note</div>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.02em", margin: "0 0 32px" }}>
            Get in touch about your VAC claim
          </h2>

          <div style={{ display: "grid", gap: 0 }}>
            {[
              { label: "Full name", placeholder: "Cpl. Jordan Reyes" },
              { label: "Email", placeholder: "you@example.ca" },
              { label: "Phone (optional)", placeholder: "(902) 555-0142" },
              { label: "Where are you in the process?", placeholder: "Just starting · Already filed · Appealing a decision · Not sure" }
            ].map((f, i) => (
              <div key={f.label} style={{ borderBottom: `1px solid ${palette.rule}`, padding: "20px 0", borderTop: i === 0 ? `1px solid ${palette.rule}` : "none" }}>
                <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted, marginBottom: 6 }}>{f.label}</div>
                <div style={{ fontSize: 17, color: palette.muted }}>{f.placeholder}</div>
              </div>
            ))}
            <div style={{ borderBottom: `1px solid ${palette.rule}`, padding: "20px 0" }}>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted, marginBottom: 6 }}>What's on your mind?</div>
              <div style={{ fontSize: 17, color: palette.muted, minHeight: 80 }}>Share whatever feels useful — we'll take it from there.</div>
            </div>
          </div>

          <div style={{ marginTop: 32, display: "flex", gap: 14, alignItems: "center", flexWrap: "wrap" }}>
            <button style={{
              background: palette.accent, color: "#fff", border: "none", padding: "18px 32px",
              fontSize: 14, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase",
              cursor: "pointer", fontFamily: "inherit"
            }}>
              Send →
            </button>
            <div style={{ fontSize: 12, color: palette.muted, fontFamily: "ui-monospace, monospace", letterSpacing: "0.06em" }}>
              ● CONFIDENTIAL · WE REPLY WITHIN 1 BUSINESS DAY
            </div>
          </div>
        </div>

        {/* Direct contact */}
        <div style={{ background: palette.ink, color: palette.bg, padding: 40, position: "relative", overflow: "hidden" }}>
          <TopoLines stroke={palette.olive} opacity={0.12} />
          <div style={{ position: "relative" }}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 14 }}>Or reach us directly</div>
            <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 28, fontWeight: 500, lineHeight: 1.15, margin: "0 0 32px" }}>
              Phone, email, or in person
            </h3>

            {[
              ["Phone", "1-833-270-9378", "Mon–Fri · 8:30am–6pm AT"],
              ["Email", "info@270westconsulting.ca", "Reply within 1 business day"],
              ["Mailing address", "270 West Consulting", "Halifax, Nova Scotia · Canada"]
            ].map(([label, primary, secondary]) => (
              <div key={label} style={{ paddingTop: 20, marginTop: 20, borderTop: `1px solid ${palette.olive}33` }}>
                <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 6 }}>{label}</div>
                <div style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 400, marginBottom: 4 }}>{primary}</div>
                <div style={{ fontSize: 13, color: "#a9b3c0" }}>{secondary}</div>
              </div>
            ))}

            <div style={{ marginTop: 32, paddingTop: 20, borderTop: `1px solid ${palette.olive}33`, fontSize: 12, color: "#a9b3c0", lineHeight: 1.6, fontStyle: "italic" }}>
              In crisis or need urgent mental health support? Call the VAC Assistance Service at <span style={{ color: palette.olive, fontStyle: "normal" }}>1-800-268-7708</span> — available 24/7, free and confidential.
            </div>
          </div>
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

window.HowItWorksPage = HowItWorksPage;
window.AboutPage = AboutPage;
window.ResourcesPage = ResourcesPage;
window.ContactPage = ContactPage;
