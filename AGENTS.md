# CodeCart source quality

- Keep comments focused on purpose, constraints and reasons for non-obvious behavior. Do not add AI conversation, task narration, release-candidate labels or marketing claims to source comments.
- Preserve third-party licenses, attribution and useful compatibility explanations. Do not classify vendor minification or ordinary technical comments as obfuscation.
- Verify behavior before claiming it works. Report checks as PASS, FAIL or NOT RUN; static inspection alone does not establish production readiness.
- Check HTML attributes and CSS declarations by meaning rather than exact formatting. A source-text match does not prove rendered layout or runtime behavior.

- Keep PHP identifiers and syntax ASCII. Preserve Unicode in translations, strings and meaningful comments. Reject invisible/bidi controls, AI omitted-code placeholders and HTML entities in executable PHP.
- Never invent framework methods: inspect actual APIs, installed dependencies, controllers, models, OCMOD and Events before changing integrations.
- Reject redundant guards, catch-and-silence errors, repeated wrappers, fake tests, unused dependencies and obvious AI-generated commentary.
- Rector runs a read-only review on main pushes. Explicit one-file corrections may be applied to main only using the manual workflow with apply=true, PHPStan, source integrity and release regression checks. Never auto-rewrite production deployments; preserve PHP 8.1–8.5.
