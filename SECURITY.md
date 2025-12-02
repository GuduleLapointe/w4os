# Security Policy

## Supported Versions

The following versions of w4os are currently being supported with security updates:

| Version | Branch | Support Status |
| ------- | ------ | -------------- |
| 2.10.x  | 2.x / master | ✅ Full support (stable) |
| 3.0.x   | 3.x | ⚠️ Development (not production-ready) |
| < 2.10.0 | - | ❌ End of life |

**Important Notes:**
- **2.x / master** is the current stable branch and receives all security updates
- **3.x** is under active development and will become the stable branch in the future
- We strongly recommend updating to the latest 2.10.x release for production environments

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please follow these steps:

### For Security Researchers

**DO NOT** open a public issue for security vulnerabilities.

Instead, please use one of these methods:

**GitHub Security Advisories (Preferred)**
- Go to https://github.com/GuduleLapointe/w4os/security/advisories/new
- Click "Report a vulnerability"
- Provide detailed information about the vulnerability, steps to reproduce, impact assessment, and suggested fixes if available.

### What to Include

Please include as much of the following information as possible:

- Type of vulnerability (e.g., XSS, SQL injection, authentication bypass)
- Affected version(s)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if available)
- Potential impact of the vulnerability
- Suggested fix or mitigation (if you have one)

### Response Timeline

- **Initial Response:** Within 48 hours of report
- **Status Update:** Within 7 days with assessment and planned timeline
- **Fix Development:** Varies by severity (critical: 1-7 days, high: 7-30 days)
- **Public Disclosure:** After fix is released and users have time to update (typically 7-14 days)

## Security Update Process

When a security vulnerability is confirmed:

1. **Private Fix:** We develop a fix in a private branch
2. **Testing:** The fix is tested thoroughly
3. **Release:** A new version is released with security patch
4. **Advisory:** A GitHub Security Advisory is published with CVE (if applicable)
5. **Notification:** Users are notified via GitHub releases and issue tracker

## Security Best Practices for Users

### Installation & Updates

- ✅ Always use the latest stable version (2.10.x)
- ✅ Subscribe to GitHub releases for update notifications
- ✅ Test updates in a staging environment before production deployment
- ✅ Backup both WordPress and OpenSimulator databases before updates

### Configuration

- ✅ Use strong, unique passwords for OpenSimulator accounts
- ✅ Restrict database access to necessary hosts only
- ✅ Keep WordPress, PHP, and MySQL/MariaDB up to date
- ✅ Limit access to error logs (they may contain sensitive information)
- ✅ Use HTTPS for all web traffic
- ✅ Disable WP_DEBUG in production environments

### OpenSimulator Integration

- ✅ Use separate database credentials for WordPress and OpenSimulator
- ✅ Grant minimal required database permissions
- ✅ Regularly audit database access logs
- ✅ Enable fail2ban or similar brute-force protection

## OpenSimulator-Specific Security Considerations

**⚠️ IMPORTANT:** OpenSimulator has inherent security limitations that require special attention when integrating with WordPress. This plugin is designed to be as secure as WordPress itself, but OpenSimulator's architecture presents unique challenges.

### Understanding OpenSimulator's Security Model

OpenSimulator originated from the open-source release of Second Life's server code. It was designed for a **closed, single-organization environment**, not for the federated, multi-stakeholder model that Hypergrid introduces today. This fundamental architectural mismatch creates several security concerns.

### Key Security Issues in OpenSimulator

#### 1. Hypergrid: Unintended Federation
The Hypergrid—which connects different grids (virtual worlds)—was retrofitted onto connection methods originally designed for servers controlled by a single entity. This creates serious trust issues:

- **Malicious Host Regions:** A visitor to a foreign grid can have their entire inventory copied or deleted by the host ([Hypergrid Business report](https://www.hypergridbusiness.com/2016/10/army-reveals-opensims-top-security-risks/))
- **Asset Theft:** Any grid administrator can copy or transfer assets regardless of their permissions in the origin grid, as long as a user has used them while visiting ([OpenSimulator Wiki](http://opensimulator.org/wiki/Hypergrid_Security))
- **Unvetted Users:** Hypergrid allows users not vetted by your local policy into your grid
- **Malicious Attachments:** Even with strict permissions, visitors can wear malicious attachments or HUDs

#### 2. SSL/HTTPS Support Limitations
**SSL/HTTPS support in OpenSimulator has been inadequate until recent versions**, and many existing installations still run without encryption:

- Login credentials are transmitted in **plain text** over HTTP in most configurations ([OpenSim mailing list](https://www.mail-archive.com/opensim-dev@opensimulator.org/msg01701.html))
- ROBUST services (login, inventory, assets) historically lacked native HTTPS support ([Starflower Bracken's guide](https://starflowerbracken.wordpress.com/2020/05/03/configuring-secure-https-ports-with-tls-ssl-on-opensim/))
- Enabling HTTPS often requires reverse proxy configurations (nginx/Apache)
- Many grid operators haven't enabled SSL due to complexity or legacy compatibility

**Implication:** Assume that OpenSimulator credentials can be intercepted in transit on many grids.

#### 3. Console Access and Administration
The **documented method** for running OpenSimulator uses `screen` sessions, which leaves an **authenticated admin console open** at all times:

- No per-session authentication is required to execute admin commands ([Console-less OpenSim wiki](http://opensimulator.org/wiki/Console-less_OpenSim))
- Anyone with server access can attach to the screen session and execute privileged commands
- The RemoteAdmin interface, while offering password protection, is often misconfigured or left open to all IPs ([RemoteAdmin wiki](https://opensimulator.dev/wiki/RemoteAdmin))

**Implication:** Server-level access = full grid admin privileges, including password changes, user deletion, and terrain modification.

#### 4. User-Contributed Regions
Some large grids (e.g., OSGrid) allow users to connect their own region servers to the main grid:

- These region owners gain limited but significant database access
- They can potentially log user sessions, chat, and transactions occurring in their regions
- This is more privileged than simple Hypergrid visitors

#### 5. Inventory and Scripting Vulnerabilities
- **Content Laundering:** OAR/IAR archive systems can strip permissions and creator/owner information from content ([OpenSim Security 101](https://www.hypergridbusiness.com/2010/03/opensim-security-101/))
- **Script Exploits:** Scripts can execute console commands if granted permissions ([OsConsoleCommand wiki](http://opensimulator.org/wiki/OsConsoleCommand))
- **No User Authentication:** OpenSimulator's powerful scripting abilities are largely unchecked

### Isolation Best Practices

**Due to these limitations, many administrators recommend isolating OpenSimulator from WordPress:**

#### Recommended: Separate Infrastructure
- ✅ Run OpenSimulator and WordPress on **separate servers** (physical or virtual)
- ✅ Use Docker containers or VMs to create network isolation
- ✅ Configure **strict external database access rules**:
  - Restrict access only the WordPress and known regions IP addresses
  - Never grant access on WordPress database to the OpenSimulator server
  - Never grant access to any IP to either database
  - Protect each database with unique, secure passwords

#### Network Security
- ✅ Use a firewall to limit which services are exposed externally
- ✅ Place OpenSimulator on a separate subnet or VLAN if possible
- ✅ Monitor and log all database connections between systems

### Password Synchronization (2.x vs 3.x)

**Current Behavior (2.x):**
- Passwords are synchronized between WordPress and OpenSimulator
- Users can log in to both systems with the same credentials
- **Security Implication:** A compromised OpenSimulator installation can leak WordPress passwords

**Future Change (3.x):**
- Password synchronization will be **removed** in w4os 3.x
- One WordPress user will be able to have multiple OpenSimulator avatars
- This decoupling significantly reduces the attack surface

**Recommendation:** If you're concerned about this attack vector, consider waiting for 3.x or implementing additional authentication layers (2FA, SSO, etc.).

### The Bottom Line

While w4os itself is developed with WordPress security standards in mind, **OpenSimulator's weak security model means it should be treated as a potential attack vector**. A compromised OpenSimulator installation could be used to:

- Harvest login credentials (if SSL is not enforced)
- Execute attacks against WordPress users who reuse credentials
- Gain database access if isolation is insufficient

**Think of OpenSimulator security as "barely symbolic"** and plan your infrastructure accordingly. Defense in depth is essential.

## Known Security Issues

See our [Security Advisories](https://github.com/GuduleLapointe/w4os/security/advisories) page for a list of all disclosed vulnerabilities and their fixes.

## Security Hall of Fame

We appreciate security researchers who responsibly disclose vulnerabilities. Contributors will be credited in:

- Security advisories
- Release notes
- This file (with permission)

### Credits

*No external security researchers yet - all findings have been internal*

## Questions?

If you have questions about this security policy, please open a discussion in the [Security category](https://github.com/GuduleLapointe/w4os/discussions/categories/security) or contact us at gudule@speculoos.world.

---

*Last updated: 2025-12-02*
