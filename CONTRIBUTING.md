# Contributing

Thank you for improving Bedriox's documentation.

## Workflow

1. Open or select an issue describing the documentation gap.
2. Create a focused branch and edit the smallest relevant set of files.
3. Identify the repository that owns the documented behavior and verify its
   tests and current compatibility manifest before changing a claim.
4. List existing behavior that the edit must not redefine or imply.
5. Mark unreleased commands, configuration, and behavior as illustrative.
6. Cite pinned primary or reference sources when external technical facts are
   used; architecture may be compared with other servers, but their implementation is not copied.
7. Run `php tools/validate-docs.php` from the repository root.
8. Open a pull request describing the audience, behavior documented, and how
   the change was verified.

Use relative links for files in this repository. Link to stable, primary
sources for external technical claims. Never add proprietary Minecraft files,
decompiled code, credentials, authentication tokens, packet captures containing
personal data, or content whose redistribution is unclear.

By contributing, you agree that your contribution is provided under
GPL-3.0-only, as described in [LICENSE](LICENSE).

Material changes to architecture or policy belong first in
[RFCs](https://github.com/Bedriox/RFCs).
