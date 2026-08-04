# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅         |

## Reporting a Vulnerability

**Please do not open a public GitHub issue for security vulnerabilities.**

Email the maintainer directly: `security@example.com`

Include:
- A description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

You will receive a response within 72 hours. Once a fix is prepared, a CVE will be requested and a patch release will be published before public disclosure.

## Security Design Notes

- All filter values are passed to Eloquent as PDO bound parameters. SQL injection is not possible through the filter value path.
- Field names are validated against an allow-list before being interpolated into the query. Unknown fields are either silently dropped or raise an exception (strict mode).
- Input is sanitised at parse time: null bytes and control characters are stripped.
- The `IN` and `BETWEEN` operators validate that the expected number of values is present before building the query.
