#!/usr/bin/env python3
"""
mk_render.py - render one high-fidelity mockup screen.

The structure comes from the same declarative page specifications used by the
wireframes (wf_pages_*.py) so the two deliverables cannot drift. Only the shell
and the stylesheet change: a dark-green institutional sidebar, a white top bar
with real icons, and the brand palette from public/css/app.css.
"""

from __future__ import annotations

import html

import wf_kit

# --------------------------------------------------------------------------
# Icon set (24x24, stroke-based, inherits currentColor)
# --------------------------------------------------------------------------

ICONS = {
    "grid": '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" '
            'height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>'
            '<rect x="14" y="14" width="7" height="7" rx="1"/>',
    "award": '<circle cx="12" cy="9" r="5"/><path d="M8.5 13.5 7 21l5-2.5L17 21l-1.5-7.5"/>',
    "star": '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/>',
    "file": '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8Z"/>'
            '<path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/>',
    "calendar": '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18"/>'
                '<path d="M8 3v4M16 3v4"/>',
    "layers": '<path d="m12 3 9 5-9 5-9-5Z"/><path d="m3 13 9 5 9-5"/>',
    "user": '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    "bell": '<path d="M18 9a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7Z"/>'
            '<path d="M10.5 20a2 2 0 0 0 3 0"/>',
    "clock": '<circle cx="12" cy="12" r="9"/><path d="M12 7v5.5l4 2"/>',
    "check": '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
    "users": '<circle cx="9" cy="8" r="3.4"/><path d="M2.5 21a6.5 6.5 0 0 1 13 0"/>'
             '<path d="M17 5.5a3.4 3.4 0 0 1 0 6.6"/><path d="M17.5 14.4A6.5 6.5 0 0 1 22 21"/>',
    "id": '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/>'
          '<path d="M5.5 16.5a3.5 3.5 0 0 1 6 0"/><path d="M14 10h4M14 14h4"/>',
    "clipboard": '<rect x="5" y="4" width="14" height="17" rx="2"/>'
                 '<path d="M9 4V3h6v1"/><path d="M9 10h6M9 14h6M9 18h3"/>',
    "sliders": '<path d="M4 7h10M18 7h2M4 12h4M12 12h8M4 17h10M18 17h2"/>'
               '<circle cx="16" cy="7" r="2"/><circle cx="10" cy="12" r="2"/>'
               '<circle cx="16" cy="17" r="2"/>',
    "inbox": '<path d="M3 13h5l1.5 3h5L16 13h5"/>'
             '<path d="M5 5h14l2 8v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-5Z"/>',
    "tag": '<path d="M20 12.5 12.5 20a2 2 0 0 1-2.8 0L4 14.3V4h10.3l5.7 5.7a2 2 0 0 1 0 2.8Z"/>'
           '<circle cx="8.5" cy="8.5" r="1.2"/>',
    "chart": '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    "shield": '<path d="M12 3 5 6v6c0 4.4 3 7.6 7 9 4-1.4 7-4.6 7-9V6Z"/>'
              '<path d="m9 12 2.2 2.2L15.5 10"/>',
    "search": '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
    "menu": '<path d="M4 7h16M4 12h16M4 17h16"/>',
    "logout": '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/>'
              '<path d="M10 8 6 12l4 4"/><path d="M6 12h9"/>',
    "chevron": '<path d="m9 6 6 6-6 6"/>',
}

NAV_ICON = {
    "Dashboard": "grid",
    "Browse Scholarships": "award",
    "Recommended Scholarships": "star",
    "My Applications": "file",
    "Guidance Appointments": "calendar",
    "OSAS Services": "layers",
    "My Profile": "user",
    "Notifications": "bell",
    "My Appointments": "calendar",
    "Availability": "clock",
    "Enrollment Verification": "check",
    "User Management": "users",
    "Student Profiles": "id",
    "Scholarships": "award",
    "Scholarship Applications": "clipboard",
    "Matching Weights": "sliders",
    "Guidance Management": "calendar",
    "Service Requests": "inbox",
    "Service Types": "tag",
    "Analytics & Reports": "chart",
    "Audit Logs": "shield",
}


def icon(name: str, cls: str = "") -> str:
    body = ICONS.get(name, ICONS["grid"])
    klass = f' class="{cls}"' if cls else ""
    return (f'<svg{klass} viewBox="0 0 24 24" fill="none" stroke="currentColor" '
            f'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" '
            f'aria-hidden="true">{body}</svg>')


def esc(value) -> str:
    return html.escape(str(value), quote=True)


# --------------------------------------------------------------------------
# Application shell
# --------------------------------------------------------------------------

def mk_sidebar(role, active_route):
    if role == "guest":
        return ""
    parts = ['<aside class="mk-side" aria-label="Main navigation">',
             '<a class="mk-brand" href="#">',
             '<span class="mark">ISU</span>',
             '<span><span class="t1">ISU OSAS Hub</span>'
             '<span class="t2">Student Affairs &amp; Services</span></span></a>',
             '<nav class="mk-nav">']
    for section, links in wf_kit.NAV[role]:
        parts.append(f'<p class="sec">{esc(section)}</p>')
        for label, route, _default in links:
            on = " on" if route == active_route else ""
            count = '<span class="count">3</span>' if label == "Notifications" else ""
            parts.append(f'<a class="{on.strip()}" href="#">'
                         f'{icon(NAV_ICON.get(label, "grid"))}'
                         f'<span>{esc(label)}</span>{count}</a>')
    parts.append("</nav>")
    parts.append('<div class="foot">Isabela State University<br>'
                 'Office of Student Affairs and Services<br>'
                 'Echague, Isabela 3309</div></aside>')
    return "".join(parts)


def mk_topbar(role, search=True):
    if role == "guest":
        return ""
    user = wf_kit.ROLE_USER.get(role, "User")
    label = wf_kit.ROLE_LABEL.get(role, "")
    initials = "".join(w[0] for w in user.replace(".", "").split()[:2]).upper()
    searchbox = ('<div class="mk-search">' + icon("search") +
                 '<span>Search scholarships, students, requests&hellip;</span>'
                 '<kbd>/</kbd></div>' if search else "")
    return ('<header class="mk-top">'
            f'<button class="mk-burger" aria-label="Open navigation">{icon("menu")}</button>'
            f'{searchbox}<div class="spacer"></div>'
            '<div class="mk-top-actions">'
            f'<button class="mk-bell" aria-label="Notifications">{icon("bell")}'
            '<span class="dot">3</span></button>'
            f'<div class="mk-user"><span class="av">{esc(initials)}</span>'
            f'<span class="who"><b>{esc(user)}</b><small>{esc(label)}</small></span></div>'
            f'<button class="mk-logout">{icon("logout")}<span>Sign out</span></button>'
            '</div></header>')


def mk_public_bar():
    return ('<header class="mk-pub">'
            '<div class="mk-pub-brand"><span class="mark">ISU</span>'
            '<span><b>ISU OSAS Hub</b>'
            '<small>Office of Student Affairs and Services</small></span></div>'
            '<div class="mk-pub-right">'
            '<span class="mk-pub-help">Need help? <b>osas@isu.edu.ph</b></span>'
            '<span class="mk-pub-flag">Official university system</span>'
            '</div></header>')


def screen_shell(p, blocks):
    role = p["role"]
    if role == "guest":
        return (f'<div class="mk-app mk-app--guest">{mk_public_bar()}'
                f'<div class="mk-main"><div class="mk-body">{blocks}</div></div></div>')
    return ('<div class="mk-app">' + mk_sidebar(role, p.get("nav_active", "")) +
            '<div class="mk-main">' + mk_topbar(role, p.get("search", True)) +
            f'<div class="mk-body">{blocks}</div></div></div>')


# --------------------------------------------------------------------------
# Document assembly
# --------------------------------------------------------------------------

CHROME = """<div class="mk-head">
  <span class="id">{mk_id}</span>
  <h1>{title}</h1>
</div>
<div class="mk-meta">
  <span><b>Role</b>{role}</span>
  <span><b>Screen</b><code>{wf_id}</code></span>
  <span><b>Route</b><code>{route}</code></span>
  <span><b>Module</b>{module}</span>
  <span><b>Requirements</b><code>{reqs}</code></span>
</div>
<div class="mk-bar">
  <span>Preview at</span>
  <button data-vp="desktop" aria-pressed="true">Desktop 1440</button>
  <button data-vp="tablet" aria-pressed="false">Tablet 1024</button>
  <button data-vp="mobile" aria-pressed="false">Mobile 390</button>
</div>
"""

SCRIPT = """
(function () {
  var mk = document.querySelector('.mk');
  document.querySelectorAll('.mk-bar button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('.mk-bar button').forEach(function (x) {
        x.setAttribute('aria-pressed', String(x === b));
      });
      mk.setAttribute('data-vp', b.dataset.vp);
    });
  });
  var params = new URLSearchParams(location.search);
  if (params.get('embed') === '1') { document.body.classList.add('embed'); }
  var vp = params.get('vp');
  if (vp) { mk.setAttribute('data-vp', vp); }
})();
"""


def showcase_blocks(p, blocks):
    """Three live renders of one screen at the three supported widths."""
    out = ['<div class="mk-showcase">',
           '<div class="mk-intro"><h2>One interface, three widths</h2>'
           '<p>The same screen rendered at 1440, 1024 and 390 px. Nothing is redrawn '
           'for the smaller viewports: the sidebar leaves the flow, the top bar sheds '
           'its search field, grids collapse and tables restack. Each frame below is a '
           'live render at that width, not a picture of one.</p></div>',
           '<div class="mk-devices">']
    singles = (("Desktop &middot; 1440 px", 1440, "desktop"),
               ("Tablet &middot; 1024 px", 1024, "tablet"),
               ("Mobile &middot; 390 px", 390, "mobile"))
    body = {}
    for label, width, vp in singles:
        body[vp] = (
            f'<figure class="mk-device mk-device--{vp}">'
            f'<div class="mk-device-bar"><b>{label}</b><span>{vp}</span></div>'
            f'<div class="mk-device-screen">'
            f'<div class="mk-device-canvas" style="width:{width}px">'
            f'<div class="mk-app">{mk_sidebar(p["role"], p.get("nav_active",""))}'
            f'<div class="mk-main">{mk_topbar(p["role"], p.get("search", True))}'
            f'<div class="mk-body">{blocks}</div></div></div>'
            '</div></div></figure>')
    out.append(body["desktop"])
    out.append('<div class="mk-device-pair">' + body["tablet"] + body["mobile"] + "</div>")
    out.append("</div>")
    out.append('<div class="mk-showcase-notes">'
               '<div><b>Desktop</b><span>Persistent 264 px sidebar; six summary tiles '
               'in two rows; two-column split for charts and backlogs.</span></div>'
               '<div><b>Tablet</b><span>Sidebar collapses to a menu toggle; tiles fall '
               'to two columns; split sections stack.</span></div>'
               '<div><b>Mobile</b><span>Single column; table rows restack with column '
               'labels; primary action stays full width.</span></div>'
               '</div>')
    out.append("</div>")
    return "".join(out)


def render_mockup(p, mk_id, reqs, base_id, showcase=False):
    blocks = "".join(wf_kit.render_block(b) for b in p["blocks"])
    if showcase:
        inner = showcase_blocks(p, blocks)
    else:
        inner = screen_shell(p, blocks)

    chrome = CHROME.format(
        mk_id=esc(mk_id), title=esc(p["title"]),
        role=esc(wf_kit.ROLE_LABEL.get(p["role"], "Guest")),
        wf_id=esc(base_id), route=esc(p.get("route", "—")),
        module=esc(p.get("module", "—")), reqs=esc(reqs))

    guest = " mk-frame--plain" if p["role"] == "guest" or showcase else ""
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{esc(mk_id)} &middot; {esc(p['title'])} &middot; ISU OSAS Hub mockup</title>
<link rel="stylesheet" href="../../assets/mockup.css">
</head>
<body>
{chrome}
<div class="mk" data-vp="desktop">
  <div class="mk-frame{guest}">
    {inner}
  </div>
</div>
<section class="mk-notes">
  <h3>Design intent</h3>
  <p>{esc(p.get('purpose', ''))}</p>
  <h3>What this screen establishes</h3>
  <ol>{''.join(f'<li>{esc(a)}</li>' for a in p.get('annotations', [])[:4])}</ol>
  <p class="mk-notes-foot">Structural source: <code>{esc(base_id)}</code> in the
  wireframe set. The mockup restates the same information architecture in the
  institutional palette, so a change to one is a change to both.</p>
</section>
<script>{SCRIPT}</script>
</body>
</html>
"""
