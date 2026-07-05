# Promotion Drafts — Rootz AI Discovery (v2.3.3 live)

**Drafted:** 2026-06-09 · **Status:** DRAFT — nothing published. Review before posting.
**Tone basis:** verification company, no overstatement, no fear-selling; "Talking to Data" narrative; AI is the interface, not a browser.

---

## 1. Blog post — discover.rootz.global/blog

**Title:** Your Website Can Already Talk to AI. Now It Can Prove Who's Talking.

**Slug:** /your-website-can-talk-to-ai

---

An AI agent can read your website right now. It doesn't need your permission, and it doesn't ask. It pulls the HTML, strips the menus and the cookie banners, guesses which paragraph is your mission statement, and moves on. When it gets something wrong — your founding date, your pricing, whether it's allowed to quote you — there's no one to correct it. The guess becomes the answer. The answer gets repeated.

That's the web AI sees today: every site, scraped and guessed at, with no way to tell a real fact from a confident hallucination.

The Rootz AI Discovery plugin changes one thing about that picture, and it's the thing that matters: **your site starts answering in its own words, signed by you.**

### What "speaking AI" actually means

Install the plugin and your WordPress site gains a set of clean, structured endpoints — the same content your visitors see, served the way an AI can actually use it:

- **`/.well-known/ai`** — who you are: name, sector, the concepts your industry uses, a list of every page with a SHA-256 hash, and the tools an agent can call.
- **`/llms.txt`** — a concise, signed overview built for language models.
- **Live tools** — an agent can *search* your content, *read* any page as clean markdown, and *verify* that what it read matches what you published.

Every one of those responses carries a cryptographic signature. Not a "trust us" badge — an actual ECDSA signature an AI can check against the site owner's key. The difference between scraped HTML and a signed answer is the difference between *"I think this site says…"* and *"this site says, and I can prove it."*

That's the whole idea behind Rootz: **knowledge starts at origin.** A fact is worth more when you can trace it back to who said it.

### See it for yourself — in your own AI

You don't have to take our word for any of this. Open Claude, ChatGPT, or any agent that can fetch a URL, and try:

> "Read https://discover.rootz.global/.well-known/ai and tell me who runs this site, what it does, and whether the data is signed."

> "Use https://discover.rootz.global/llms.txt to summarize what this site offers."

The agent will come back with a clean, structured answer — and it'll tell you the manifest is signed. That's your own AI confirming the site is speaking for itself.

### It's live, and it's small

The plugin is at **v2.3.3**, running on the lab site above, and it's in the WordPress.org review pipeline. It runs on WordPress 6.0+ / PHP 7.4+, signs locally, and adds nothing to your page load for human visitors. Free tier covers the basics; paid tiers add interactive tools and monitoring.

Your website learned to talk to people thirty years ago. This is the week it learns to talk to AI — and to prove it's really you talking.

**Get the plugin:** discover.rootz.global/plugin
**Try the live endpoints:** discover.rootz.global/.well-known/ai

---

## 2. LinkedIn post — Steven's voice

AI is already reading your website. The question is whether it's reading *you* — or its best guess at you.

Today every AI agent scrapes raw HTML, strips the noise, and guesses which words are your mission, your pricing, your policy. When it guesses wrong, the wrong answer is what gets repeated.

We built a small WordPress plugin that fixes the part that matters: your site answers in its own structured words, and every answer is cryptographically signed by you. An AI doesn't have to guess — it can read a clean manifest and *verify* the signature.

Don't take my word for it. Paste this into Claude or ChatGPT:

"Read https://discover.rootz.global/.well-known/ai and tell me who runs this site — and whether the data is signed."

Your own AI will tell you the site is speaking for itself.

Knowledge starts at origin. The plugin is live (v2.3.3) and free to try.

discover.rootz.global/plugin

#AI #WordPress #DataProvenance #TalkingToData

---

## 3. X / Twitter thread

**1/**
AI is already reading your website. It scrapes the HTML, strips the menus, and *guesses* who you are.

When it guesses wrong, the wrong answer gets repeated.

We built a small WordPress plugin so your site can answer in its own words — signed. 🧵

**2/**
Install it and your site gets clean endpoints AI can actually use:

• /.well-known/ai — who you are, every page hashed
• /llms.txt — a signed overview for LLMs
• tools to search, read & verify your content

Every response carries a verifiable ECDSA signature.

**3/**
Don't trust us — trust your own AI. Paste into Claude or ChatGPT:

"Read https://discover.rootz.global/.well-known/ai and tell me who runs this site, and whether the data is signed."

It'll come back with a clean answer and confirm the manifest is signed.

**4/**
The difference: scraped HTML says *"I think this site says…"*

A signed answer says *"this site says, and I can prove it."*

Knowledge starts at origin. Live now (v2.3.3), free to try:
discover.rootz.global/plugin
