# Repository Instructions

This repository contains the public operator and developer documentation for
the Bedriox ecosystem, developed by
[Veno Ninja LLC](https://bedriox.com). Follow these instructions for
every change in this repository.

## Repository structure

- `README.md` is the documentation landing page and primary index.
- `docs/` contains maintained operator, developer, compatibility, testing,
  architecture, roadmap, and troubleshooting documentation.
- `tools/validate-docs.php` checks Markdown structure, trailing whitespace, and
  relative links.
- `.github/workflows/docs.yml` runs the documentation validator in CI.
- Root policy files cover contribution, governance, conduct, security,
  licensing, notices, and release history.

Do not put architectural proposals or decision records here. Those belong in
the [Bedriox RFC repository](https://github.com/Bedriox/RFCs). This
repository describes supported behavior, operational guidance, and clearly
labelled future direction.

## Writing standards

- Write concise, plain-language Markdown for the intended operator or
  developer audience.
- Give every Markdown file exactly one descriptive level-one heading and use a
  logical heading hierarchy beneath it.
- Prefer relative links for files in this repository. Use stable, primary
  sources for external technical claims.
- Mark commands, configuration, APIs, and behavior that are not backed by a
  release as illustrative or planned. Do not imply support from an accepted RFC
  alone.
- Use exact repository names, packet names, configuration keys, and version
  constraints. Avoid ambiguous terms such as "latest" when a stable identifier
  is available.
- Keep examples minimal, safe to copy, and explicit about prerequisites and
  expected results. Never include real credentials, tokens, private endpoints,
  or personal packet data.
- Avoid volatile status claims in evergreen documents. Put release-specific
  changes in `CHANGELOG.md` and supported-version claims in the compatibility
  documentation.

## Links, licensing, and attribution

Documentation prose, code examples, scripts, and repository tooling are
licensed under GPL-3.0-only. Retain applicable copyright, attribution,
`LICENSE`, and `NOTICE` content.

Record the source, exact version or commit, license, transformation, and review
date for externally derived protocol facts, tables, registries, or examples in
`THIRD_PARTY_NOTICES.md` or the owning data repository. Cite sources; do not copy
material merely because it is publicly accessible. Never add proprietary game
assets, leaked or decompiled material, or content with unclear redistribution
rights.

## Cross-repository synchronization

Documentation must agree with the repository that owns the behavior:

- transport and RakNet behavior: `RakNet`;
- Bedrock packet and version behavior: `Protocol`;
- registries and generated protocol data: `Data`;
- runtime, configuration, packaging, and operator behavior: `Bedriox`;
- architectural decisions and proposals: `RFCs`.

When a code or RFC change affects users, update this repository in the same
coordinated change set or link the tracking issue. When documentation exposes a
design conflict, update the owning RFC rather than silently redefining the
decision here. Check inbound links from sibling repositories when renaming or
moving a public page.

## Validation

From the repository root, run:

```console
php tools/validate-docs.php
```

Also exercise documented commands when the corresponding implementation is
available. A validator pass does not establish that an example is technically
correct.

## Commits

Keep commits focused and use an imperative summary. Do not include personal
email addresses or identity trailers unless a contributor deliberately chooses
to publish them. Do not rewrite or discard another contributor's work merely
to change commit metadata.

## Definition of done

A documentation change is complete when:

- the intended audience, prerequisites, behavior, and limitations are clear;
- commands and examples are verified or explicitly labelled illustrative;
- relative links and navigation are updated and valid;
- claims match accepted decisions and the owning implementation;
- licensing and required attribution are handled correctly;
- security, privacy, compatibility, and troubleshooting effects are documented
  where relevant;
- `php tools/validate-docs.php` passes; and
- the final diff contains only the intended documentation and policy changes.
