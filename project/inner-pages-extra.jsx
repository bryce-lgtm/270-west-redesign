// Additional inner pages — Services, Resource Article, FAQ, Legal stubs
// All share SiteHeader/SiteFooter and PALETTE_A from direction-a.jsx
// Same SEO-led H1 pattern + collaborative tone.

// ===================== SERVICES =====================

const ServicesPage = () => {
  const palette = window.PALETTE_A;
  const services = [
    {
      n: "01",
      title: "VAC eligibility assessments",
      h2: "Find out which VAC programs may apply to you",
      p: "We start with a no-cost conversation to understand your service history and what you're hoping to do. From there, we walk you through the Veterans Affairs Canada programs that could apply — and the ones that don't.",
      bullets: ["Service history review", "Plain-language program explanations", "Honest, no-pressure recommendation"]
    },
    {
      n: "02",
      title: "First-time VAC disability applications",
      h2: "Help preparing your first VAC disability claim",
      p: "If you've never filed a VAC claim before, the paperwork can feel intimidating. We help you gather the right documentation, complete forms, and submit a clear, complete application — so VAC has everything they need to make a fair decision.",
      bullets: ["Document checklist tailored to your case", "Form completion support", "Submission review before filing"]
    },
    {
      n: "03",
      title: "Re-assessments and rating increases",
      h2: "Apply for an increased VAC disability rating",
      p: "If your service-related health has changed since your last assessment, you may be eligible for a higher rating. We help you understand whether a re-assessment is right for you and prepare a strong supporting application.",
      bullets: ["Medical evidence guidance", "Comparative case insight", "Clear next-step recommendations"]
    },
    {
      n: "04",
      title: "VAC appeals and reconsiderations",
      h2: "Help with VAC appeals and reconsideration requests",
      p: "If your VAC claim was denied or you disagree with a decision, you may have options. We walk through the decision letter with you, explain your rights, and help you decide whether a reconsideration or appeal makes sense.",
      bullets: ["Decision letter walkthrough", "Reconsideration support", "Veterans Review and Appeal Board guidance"]
    }
  ];
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="Services"
        kicker="Services"
        h1="VAC claims support services for Canadian veterans"
        lead="What we help with."
        sub="From a first eligibility check to appeals and reconsiderations — we offer end-to-end support for veterans navigating Veterans Affairs Canada benefits."
      />

      <section style={{ padding: "60px 80px 0" }}>
        <ImagePlaceholder
          label="Services overview — wide editorial image"
          spec="1440 × 480 · JPG/WebP"
          aspect="3 / 1"
          note="Photo of advisor at work — paperwork, laptop, calm office. Avoid stock."
        />
      </section>

      <section style={{ padding: "80px 80px 100px" }}>
        {services.map((s, i) => (
          <div key={s.n} style={{
            display: "grid", gridTemplateColumns: "120px 1fr 360px", gap: 64,
            padding: "56px 0", borderTop: `1px solid ${palette.rule}`,
            borderBottom: i === services.length - 1 ? `1px solid ${palette.rule}` : "none"
          }}>
            <div>
              <div style={{ fontFamily: "'Fraunces', serif", fontSize: 96, fontWeight: 300, lineHeight: 0.9, color: palette.olive, letterSpacing: "-0.04em" }}>{s.n}</div>
              <div style={{ marginTop: 16, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted }}>{s.title}</div>
            </div>
            <div>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.15, letterSpacing: "-0.02em", margin: "0 0 20px", maxWidth: 640 }}>{s.h2}</h2>
              <p style={{ fontSize: 18, lineHeight: 1.6, color: palette.ink, margin: "0 0 24px", maxWidth: 640, textWrap: "pretty" }}>{s.p}</p>
              <a href="contact.html" style={{ fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.1em", textTransform: "uppercase", color: palette.accent, textDecoration: "none", borderBottom: `1px solid ${palette.accent}`, paddingBottom: 4 }}>Talk to an advisor →</a>
            </div>
            <div style={{ background: palette.bg2, padding: 24, borderLeft: `2px solid ${palette.olive}` }}>
              <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.muted, marginBottom: 14 }}>What's included</div>
              <div style={{ display: "grid", gap: 10, fontSize: 14, color: palette.ink }}>
                {s.bullets.map(b => <div key={b} style={{ display: "flex", gap: 10 }}><span style={{ color: palette.accent }}>·</span>{b}</div>)}
              </div>
            </div>
          </div>
        ))}

        <div style={{ marginTop: 56, padding: "24px 28px", background: palette.bg2, fontSize: 15, color: palette.muted, fontStyle: "italic", borderLeft: `2px solid ${palette.olive}`, lineHeight: 1.55, maxWidth: 900 }}>
          A note: 270 West Consulting is independent of Veterans Affairs Canada. We work alongside VAC, not in place of them — you can contact VAC directly any time at 1-866-522-2122.
        </div>
      </section>

      <section style={{ background: palette.ink, color: palette.bg, padding: "100px 80px", position: "relative", overflow: "hidden" }}>
        <TopoLines stroke={palette.olive} opacity={0.12} />
        <div style={{ position: "relative", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 80, alignItems: "center" }}>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 52, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.025em", margin: 0 }}>
            Not sure which service fits your situation?
          </h2>
          <div>
            <p style={{ fontSize: 17, lineHeight: 1.55, color: "#d6d1c8", maxWidth: 460, margin: "0 0 28px" }}>
              Start with a two-minute eligibility check — we'll point you in the right direction.
            </p>
            <a href="index.html#quiz" style={{ background: palette.accent, color: "#fff", padding: "18px 32px", textDecoration: "none", fontSize: 14, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", display: "inline-flex", alignItems: "center", gap: 12 }}>
              Check your eligibility →
            </a>
          </div>
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== RESOURCE ARTICLE TEMPLATE =====================

const ResourceArticlePage = () => {
  const palette = window.PALETTE_A;
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <window.SiteHeader active="Resources" />

      {/* Article header */}
      <section style={{ padding: "60px 80px 40px", borderBottom: `1px solid ${palette.rule}` }}>
        <div style={{ display: "flex", gap: 8, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.12em", textTransform: "uppercase", color: palette.muted, marginBottom: 24 }}>
          <a href="resources.html" style={{ color: palette.muted, textDecoration: "none" }}>Resources</a>
          <span>/</span>
          <span style={{ color: palette.accent }}>Eligibility & Programs</span>
        </div>
        <h1 style={{ fontFamily: "'Fraunces', serif", fontSize: 64, fontWeight: 500, lineHeight: 1.05, letterSpacing: "-0.025em", margin: "0 0 24px", maxWidth: 1100, textWrap: "balance" }}>
          A plain-language guide to VAC's main benefits programs
        </h1>
        <div style={{ display: "flex", gap: 32, fontFamily: "ui-monospace, monospace", fontSize: 12, letterSpacing: "0.08em", textTransform: "uppercase", color: palette.muted }}>
          <span>● 18 min read</span>
          <span>Updated April 2026</span>
          <span>By 270 West Consulting</span>
        </div>
      </section>

      {/* Article body — 2 col with TOC */}
      <section style={{ padding: "80px 80px", display: "grid", gridTemplateColumns: "240px 1fr 240px", gap: 64, alignItems: "start" }}>
        {/* TOC */}
        <aside style={{ position: "sticky", top: 32 }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.muted, marginBottom: 14 }}>Contents</div>
          <div style={{ display: "grid", gap: 10, fontSize: 14, lineHeight: 1.4 }}>
            {["Disability Benefits", "Income Replacement Benefit", "Career Transition Services", "Caregiver support", "How to apply", "FAQ"].map((t, i) => (
              <a key={t} href={`#h-${i}`} style={{ color: i === 0 ? palette.accent : palette.muted, textDecoration: "none", borderLeft: i === 0 ? `2px solid ${palette.accent}` : `2px solid transparent`, paddingLeft: 12 }}>{t}</a>
            ))}
          </div>
        </aside>

        {/* Article body */}
        <article style={{ maxWidth: 720 }}>
          <p style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 400, lineHeight: 1.4, color: palette.ink, margin: "0 0 32px", fontStyle: "italic", letterSpacing: "-0.01em" }}>
            Veterans Affairs Canada offers more programs than most veterans realize. This guide walks through the main ones — what they're for, who qualifies, and how to apply — in plain language.
          </p>

          <ImagePlaceholder
            label="Article hero image"
            spec="720 × 400 · JPG · 16:9"
            aspect="16 / 9"
            note="Editorial illustration or photo for the lead VAC programs guide."
          />

          <h2 id="h-0" style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "56px 0 16px", scrollMarginTop: 32 }}>
            VAC Disability Benefits
          </h2>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            VAC's Disability Benefits program provides recognition and support for service-related health conditions. There are two main forms: a Pain and Suffering Compensation (monthly or lump-sum) and an Additional Pain and Suffering Compensation for those with severe and permanent conditions.
          </p>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            To qualify, you'll generally need to show three things: that you served, that you have a current diagnosed condition, and that the condition is connected to your service. Each piece needs documentation — and putting that documentation together is where most veterans tell us they get stuck.
          </p>
          <blockquote style={{ borderLeft: `3px solid ${palette.accent}`, padding: "8px 0 8px 24px", margin: "32px 0", fontFamily: "'Fraunces', serif", fontSize: 22, fontStyle: "italic", lineHeight: 1.4, color: palette.ink }}>
            "The connection between your service and your current condition is the linchpin of a strong disability application."
          </blockquote>

          <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 24, fontWeight: 500, lineHeight: 1.25, margin: "32px 0 12px" }}>
            Who qualifies?
          </h3>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            Canadian Armed Forces members and veterans (Regular Force or Reserve), and certain RCMP members, can apply for VAC disability benefits when they have a service-related condition. Survivors and dependants may also qualify in specific situations.
          </p>

          <h2 id="h-1" style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "56px 0 16px", scrollMarginTop: 32 }}>
            Income Replacement Benefit
          </h2>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            The Income Replacement Benefit (IRB) provides ongoing income support for veterans who can't return to suitable, gainful employment because of a service-related health condition. It's calculated based on your military salary at release and reviewed periodically.
          </p>

          <ImagePlaceholder
            label="Supporting article image"
            spec="720 × 400 · JPG"
            aspect="16 / 9"
            note="Detail shot — paperwork, hands writing, calm tone."
          />

          <h2 id="h-2" style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "56px 0 16px", scrollMarginTop: 32 }}>
            Career Transition Services
          </h2>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            Career Transition Services help veterans translate military experience into civilian employment. Job-search support, career counselling, and rehabilitation programs can be combined with other VAC benefits.
          </p>

          <h2 id="h-3" style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "56px 0 16px", scrollMarginTop: 32 }}>
            Caregiver support
          </h2>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            The Caregiver Recognition Benefit recognizes the role of family members and informal caregivers in supporting a veteran with a service-related disability. It's a tax-free monthly amount paid directly to the designated caregiver.
          </p>

          <h2 id="h-4" style={{ fontFamily: "'Fraunces', serif", fontSize: 36, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "56px 0 16px", scrollMarginTop: 32 }}>
            How to apply for VAC benefits
          </h2>
          <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: "0 0 16px" }}>
            Most VAC programs are applied for through My VAC Account at vac.gc.ca/myaccount. You can also apply by paper. Whichever route you choose, the strength of your application comes down to documentation: medical records, service history, and the connection between the two.
          </p>

          <div style={{ background: palette.bg2, padding: "24px 28px", margin: "32px 0", borderLeft: `2px solid ${palette.olive}` }}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 10 }}>Helpful tip</div>
            <p style={{ fontSize: 16, lineHeight: 1.6, color: palette.ink, margin: 0, fontStyle: "italic" }}>
              Save copies of every document you submit — including covering letters and email confirmations. A well-organized record makes any future re-assessment or appeal far easier.
            </p>
          </div>
        </article>

        {/* Right rail — CTA + related */}
        <aside style={{ position: "sticky", top: 32 }}>
          <div style={{ background: palette.ink, color: palette.bg, padding: 24, marginBottom: 24 }}>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.olive, marginBottom: 10 }}>Need help?</div>
            <h3 style={{ fontFamily: "'Fraunces', serif", fontSize: 20, fontWeight: 500, margin: "0 0 12px", lineHeight: 1.25 }}>
              Talk to an advisor — free.
            </h3>
            <p style={{ fontSize: 13, color: "#d6d1c8", margin: "0 0 16px", lineHeight: 1.55 }}>
              Two-minute eligibility check. No pressure, no obligation.
            </p>
            <a href="index.html#quiz" style={{ background: palette.accent, color: "#fff", padding: "12px 18px", textDecoration: "none", fontSize: 12, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", display: "block", textAlign: "center" }}>
              Check eligibility →
            </a>
          </div>
          <div>
            <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.muted, marginBottom: 14 }}>Related guides</div>
            <div style={{ display: "grid", gap: 12 }}>
              {["VAC application checklist: documents to gather", "How VAC disability ratings actually work", "Common reasons applications come back"].map(t => (
                <a key={t} href="#" style={{ fontSize: 14, color: palette.ink, textDecoration: "none", borderTop: `1px solid ${palette.rule}`, paddingTop: 12, lineHeight: 1.4 }}>{t} →</a>
              ))}
            </div>
          </div>
        </aside>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== FAQ PAGE =====================

const FAQPage = () => {
  const palette = window.PALETTE_A;
  const groups = [
    { title: "About 270 West", items: [
      ["Is 270 West Consulting part of Veterans Affairs Canada?", "No. We're an independent, Canadian-owned advisory service. We work alongside VAC to help veterans put together strong applications, but we're not employed by or affiliated with VAC."],
      ["Where is 270 West based?", "We're headquartered in Halifax, Nova Scotia, and serve veterans across Canada — virtually and by phone."],
      ["Are you accredited?", "Yes — we're BBB Accredited and a member of recognized Canadian veteran-services networks."]
    ]},
    { title: "Pricing & process", items: [
      ["Is the eligibility check really free?", "Yes. The two-minute check-in and your first conversation are completely free, with no obligation. If you'd like our help with a full application, we'll explain pricing upfront — no surprises."],
      ["How long does the process take?", "The eligibility check is about two minutes. From there, timelines depend on your situation and on VAC's review process. We set realistic expectations together on our first call."],
      ["What does 270 West actually do during a claim?", "We help you gather documentation, complete forms, and submit a clear, complete VAC application. We stay with you through any follow-up VAC may need."]
    ]},
    { title: "VAC programs", items: [
      ["What VAC programs can I apply for?", "There are several main programs: Disability Benefits, Income Replacement Benefit, Career Transition Services, and Caregiver Recognition. See our Resources page for plain-language guides on each."],
      ["What if my VAC claim was already denied?", "We can still help. Sometimes a fresh look at the documentation or framing of a claim makes a meaningful difference. Bring us your story and we'll walk through it together."],
      ["Can I apply for VAC benefits on my own?", "Absolutely. VAC accepts applications directly from veterans, and many people apply on their own. We're here if you'd like a teammate to help you through it."]
    ]},
    { title: "Privacy & confidentiality", items: [
      ["Is the information I share confidential?", "Yes. Everything you share with us is treated as confidential. We don't share your information with third parties without your written consent."],
      ["Do you keep my records?", "We maintain client records securely and only for as long as necessary to provide our services. See our Privacy Policy for details."]
    ]}
  ];
  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active="FAQ"
        kicker="Frequently asked questions"
        h1="Frequently asked questions about VAC claims and 270 West Consulting"
        lead="Things veterans ask us most."
        sub="Answers to the most common questions about Veterans Affairs Canada benefits, our services, pricing, and process."
      />

      <section style={{ padding: "80px 80px 100px" }}>
        {groups.map(group => (
          <div key={group.title} style={{ display: "grid", gridTemplateColumns: "320px 1fr", gap: 64, padding: "48px 0", borderTop: `1px solid ${palette.rule}` }}>
            <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 32, fontWeight: 500, lineHeight: 1.15, margin: 0, position: "sticky", top: 32, height: "fit-content" }}>{group.title}</h2>
            <div style={{ display: "grid", gap: 0 }}>
              {group.items.map(([q, a]) => (
                <details key={q} open style={{ padding: "20px 0", borderBottom: `1px solid ${palette.rule}` }}>
                  <summary style={{ fontFamily: "'Fraunces', serif", fontSize: 22, fontWeight: 500, cursor: "pointer", listStyle: "none", display: "flex", justifyContent: "space-between", alignItems: "center", lineHeight: 1.3 }}>
                    {q}
                    <span style={{ color: palette.accent, fontFamily: "ui-monospace, monospace", fontSize: 14 }}>−</span>
                  </summary>
                  <p style={{ fontSize: 16, lineHeight: 1.65, color: palette.muted, margin: "14px 0 0", maxWidth: 720 }}>{a}</p>
                </details>
              ))}
            </div>
          </div>
        ))}
      </section>

      <section style={{ background: palette.ink, color: palette.bg, padding: "100px 80px", position: "relative", overflow: "hidden" }}>
        <TopoLines stroke={palette.olive} opacity={0.12} />
        <div style={{ position: "relative", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 80, alignItems: "center" }}>
          <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 48, fontWeight: 500, lineHeight: 1.1, letterSpacing: "-0.025em", margin: 0 }}>
            Question we didn't answer?
          </h2>
          <div>
            <p style={{ fontSize: 17, lineHeight: 1.55, color: "#d6d1c8", maxWidth: 460, margin: "0 0 28px" }}>
              Send us a note — we usually reply within one business day.
            </p>
            <a href="contact.html" style={{ background: palette.accent, color: "#fff", padding: "18px 32px", textDecoration: "none", fontSize: 14, fontWeight: 600, letterSpacing: "0.06em", textTransform: "uppercase", display: "inline-flex", alignItems: "center", gap: 12 }}>
              Contact us →
            </a>
          </div>
        </div>
      </section>

      <window.SiteFooter />
    </div>
  );
};

// ===================== LEGAL TEMPLATE =====================

const LegalPage = ({ kind }) => {
  const palette = window.PALETTE_A;
  const meta = {
    privacy: {
      kicker: "Privacy",
      h1: "Privacy Policy — 270 West Consulting",
      lead: "How we handle your information.",
      sub: "We treat your personal information with the same care we'd want for ourselves. This policy explains what we collect, why, and how it's protected."
    },
    terms: {
      kicker: "Terms",
      h1: "Terms of Service — 270 West Consulting",
      lead: "The basics of working with us.",
      sub: "These terms describe the agreement between you and 270 West Consulting when you use our website or services."
    },
    accessibility: {
      kicker: "Accessibility",
      h1: "Accessibility Statement — 270 West Consulting",
      lead: "Built for everyone.",
      sub: "We're committed to making this site usable for veterans and family members of all abilities. Here's our current accessibility status and how to reach us if you encounter a barrier."
    }
  }[kind];

  const sectionsByKind = {
    privacy: [
      ["Information we collect", "When you fill out a form, complete the eligibility check, or contact us, we collect the information you choose to provide — typically name, email, phone number, and details relevant to your VAC claim. We also collect basic site analytics (page views, referrers) to help improve the site."],
      ["How we use your information", "We use your information to respond to your inquiry, provide the services you've requested, and follow up about your claim. We do not sell your information, and we do not share it with third parties without your written consent — except where required by law."],
      ["How we protect your information", "Client records are stored securely with reasonable safeguards in line with Canadian privacy legislation. Access is limited to advisors directly involved in your case."],
      ["Your rights", "You can request access to the personal information we hold about you, ask for corrections, or ask us to delete it. Email privacy@270westconsulting.ca to make a request."],
      ["Cookies", "Our site uses minimal cookies for analytics and essential functionality. You can control cookies through your browser settings."],
      ["Changes to this policy", "We may update this policy from time to time. The latest version is always posted here, with the effective date below."]
    ],
    terms: [
      ["About these terms", "By using this website or our services, you agree to these terms. If you don't agree, please don't use the site or engage our services."],
      ["Our services", "270 West Consulting provides claims advisory services for Canadian veterans navigating Veterans Affairs Canada benefits. We are independent of VAC. Engaging our services does not guarantee any particular outcome from VAC."],
      ["Your responsibilities", "You agree to provide accurate information when working with us, and to communicate openly so we can give the best possible guidance."],
      ["Fees", "Fees, if any, are explained in writing before any paid engagement begins. The eligibility check and first consultation are free of charge."],
      ["Limitation of liability", "We aim to provide useful, accurate guidance, but we cannot guarantee VAC decisions. To the maximum extent permitted by law, our liability is limited to the fees you have paid us."],
      ["Governing law", "These terms are governed by the laws of Nova Scotia, Canada."]
    ],
    accessibility: [
      ["Our commitment", "We're committed to providing a website that is accessible to the widest possible audience, in line with WCAG 2.1 AA where feasible."],
      ["Current status", "We continually review and improve the site's accessibility. Recent improvements include keyboard navigation, alt text on key images, and high-contrast typography."],
      ["Known limitations", "We're aware of areas that need work — including some PDF resources without full screen-reader markup. We're addressing these as we update content."],
      ["Need help or found a barrier?", "If you encounter an accessibility barrier or need information in an alternative format, please email accessibility@270westconsulting.ca or call 1-833-270-9378. We aim to respond within two business days."],
      ["VAC Assistance Service", "If you need urgent mental-health support, the VAC Assistance Service is available 24/7 at 1-800-268-7708."]
    ]
  }[kind];

  return (
    <div style={{ width: 1440, fontFamily: "'Inter', system-ui, sans-serif", background: palette.bg, color: palette.ink }}>
      <PageHero
        active={kind === "privacy" ? "Privacy" : kind === "terms" ? "Terms" : "Accessibility"}
        kicker={meta.kicker}
        h1={meta.h1}
        lead={meta.lead}
        sub={meta.sub}
      />

      <section style={{ padding: "80px 80px 100px", display: "grid", gridTemplateColumns: "240px 1fr", gap: 80 }}>
        <aside style={{ position: "sticky", top: 32, height: "fit-content" }}>
          <div style={{ fontFamily: "ui-monospace, monospace", fontSize: 10, letterSpacing: "0.14em", textTransform: "uppercase", color: palette.muted, marginBottom: 14 }}>On this page</div>
          <div style={{ display: "grid", gap: 8, fontSize: 13, lineHeight: 1.4 }}>
            {sectionsByKind.map(([title], i) => (
              <a key={title} href={`#sec-${i}`} style={{ color: palette.muted, textDecoration: "none", borderLeft: `2px solid transparent`, paddingLeft: 12 }}>{title}</a>
            ))}
          </div>
          <div style={{ marginTop: 32, fontFamily: "ui-monospace, monospace", fontSize: 11, letterSpacing: "0.1em", textTransform: "uppercase", color: palette.muted }}>
            Effective: April 2026
          </div>
        </aside>

        <article style={{ maxWidth: 760 }}>
          {sectionsByKind.map(([title, body], i) => (
            <div key={title} id={`sec-${i}`} style={{ paddingBottom: 40, marginBottom: 40, borderBottom: i < sectionsByKind.length - 1 ? `1px solid ${palette.rule}` : "none", scrollMarginTop: 32 }}>
              <h2 style={{ fontFamily: "'Fraunces', serif", fontSize: 30, fontWeight: 500, lineHeight: 1.2, letterSpacing: "-0.02em", margin: "0 0 16px" }}>
                {title}
              </h2>
              <p style={{ fontSize: 17, lineHeight: 1.65, color: palette.ink, margin: 0, textWrap: "pretty" }}>{body}</p>
            </div>
          ))}
          <div style={{ marginTop: 40, padding: "20px 24px", background: palette.bg2, fontSize: 14, color: palette.muted, fontStyle: "italic", borderLeft: `2px solid ${palette.olive}`, lineHeight: 1.6 }}>
            This page is a working draft. Final language should be reviewed by counsel before publishing.
          </div>
        </article>
      </section>

      <window.SiteFooter />
    </div>
  );
};

window.ServicesPage = ServicesPage;
window.ResourceArticlePage = ResourceArticlePage;
window.FAQPage = FAQPage;
window.LegalPage = LegalPage;
