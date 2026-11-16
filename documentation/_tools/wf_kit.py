#!/usr/bin/env python3
"""
wf_kit.py - low-fidelity wireframe kit.

Renders declarative page specs (see wf_pages_*.py) into standalone HTML files
that share one greyscale wireframe language and one application shell that
mirrors the real sidebar and top bar.

Design constraints:
  * greyscale only - no product colours, because this is low fidelity
  * real labels everywhere - controls are labelled, never lorem ipsum
  * one shell - the same navigation appears on every authenticated screen
  * live responsiveness - container queries make the frame itself reflow,
    so the same file demonstrates desktop, tablet and mobile behaviour
"""

from __future__ import annotations

import html
import os

# --------------------------------------------------------------------------
# CSS
# --------------------------------------------------------------------------

CSS = r"""
*, *::before, *::after { box-sizing: border-box; }

:root {
  --ink:      #1b1b1b;
  --ink-2:    #3d3d3d;
  --muted:    #6f6f6f;
  --line:     #8f8f8f;
  --line-2:   #c8c8c8;
  --line-3:   #e2e2e2;
  --fill:     #f3f3f3;
  --fill-2:   #e9e9e9;
  --paper:    #ffffff;
  --canvas:   #d7d7d7;
  --radius:   8px;
  --mono:     ui-monospace, "Cascadia Mono", Consolas, monospace;
  --sans:     -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
}

html { -webkit-text-size-adjust: 100%; }
body {
  margin: 0;
  background: var(--canvas);
  color: var(--ink);
  font: 14px/1.45 var(--sans);
  padding: 20px 20px 60px;
}

/* ---------- document chrome (not part of the wireframe) ---------- */
.doc-head {
  max-width: 1440px; margin: 0 auto 14px;
  display: flex; flex-wrap: wrap; gap: 10px 22px; align-items: baseline;
}
.doc-head h1 { font-size: 19px; margin: 0; letter-spacing: -.01em; }
.doc-head .id {
  font: 600 12px/1 var(--mono); background: var(--ink); color: #fff;
  padding: 5px 8px; border-radius: 5px; letter-spacing: .04em;
}
.doc-meta {
  max-width: 1440px; margin: 0 auto 14px;
  display: flex; flex-wrap: wrap; gap: 6px 20px;
  font-size: 12.5px; color: var(--ink-2);
}
.doc-meta b { font-weight: 600; color: var(--muted); font-size: 11px;
  text-transform: uppercase; letter-spacing: .06em; margin-right: 5px; }
.doc-meta code { font-family: var(--mono); font-size: 12px; }

.vf-chrome {
  max-width: 1440px; margin: 0 auto 8px;
  display: flex; gap: 6px; align-items: center;
}
.vf-chrome span { font-size: 11.5px; color: var(--muted); margin-right: 4px;
  text-transform: uppercase; letter-spacing: .06em; }
.vf-chrome button {
  font: 600 11.5px/1 var(--sans); padding: 6px 11px; cursor: pointer;
  background: #fff; border: 1px solid var(--line); border-radius: 6px;
  color: var(--ink-2);
}
.vf-chrome button[aria-pressed="true"] { background: var(--ink); color: #fff;
  border-color: var(--ink); }

/* ---------- viewport frame ---------- */
.vf { max-width: 1440px; margin: 0 auto 22px; }
.vf-frame {
  container-type: inline-size; container-name: wf;
  width: 1440px; max-width: 100%;
  background: var(--paper);
  border: 2px solid #2b2b2b; border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 10px 26px rgba(0,0,0,.16);
}
.vf[data-vp="tablet"] .vf-frame { width: 1024px; }
.vf[data-vp="mobile"] .vf-frame { width: 390px; }

/* ---------- application shell ---------- */
.wf-app { display: flex; min-height: 640px; background: var(--paper); }
@container wf (max-width: 1024px) {
  .wf-app { min-height: 560px; }
}

.wf-side {
  width: 264px; flex: 0 0 264px;
  border-right: 1px solid var(--line-2);
  background: var(--fill);
  padding: 14px 0 18px;
  display: flex; flex-direction: column;
}
.wf-brand {
  display: flex; gap: 9px; align-items: center;
  padding: 4px 16px 16px; border-bottom: 1px solid var(--line-2);
  margin-bottom: 12px;
}
.wf-brand .mark {
  width: 34px; height: 34px; border: 1.5px solid var(--ink);
  border-radius: 7px; display: grid; place-items: center;
  font: 700 10px/1 var(--mono); letter-spacing: .02em;
}
.wf-brand .t1 { display: block; font-weight: 700; font-size: 13.5px; }
.wf-brand .t2 { display: block; font-size: 10.5px; color: var(--muted);
  text-transform: uppercase; letter-spacing: .05em; }

.wf-nav { flex: 1; overflow: auto; }
.wf-nav .sec {
  font-size: 10px; text-transform: uppercase; letter-spacing: .1em;
  color: var(--muted); padding: 12px 16px 5px;
}
.wf-nav a {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 16px; text-decoration: none; color: var(--ink-2);
  font-size: 13px; border-left: 3px solid transparent;
}
.wf-nav a .sq {
  width: 11px; height: 11px; border: 1.2px solid var(--line);
  border-radius: 2px; flex: none;
}
.wf-nav a.on {
  background: var(--fill-2); border-left-color: var(--ink);
  font-weight: 600; color: var(--ink);
}
.wf-nav a .count {
  margin-left: auto; font: 700 10px/1 var(--mono);
  border: 1px solid var(--ink); border-radius: 9px; padding: 2px 5px;
}
.wf-side .foot {
  padding: 12px 16px 0; font-size: 10px; color: var(--muted);
  border-top: 1px solid var(--line-2); margin-top: 12px; line-height: 1.5;
}

.wf-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.wf-top {
  height: 60px; flex: none; border-bottom: 1px solid var(--line-2);
  display: flex; align-items: center; gap: 10px; padding: 0 18px;
  background: var(--paper);
}
.wf-burger {
  display: none; width: 32px; height: 30px; border: 1px solid var(--line);
  border-radius: 5px; align-items: center; justify-content: center;
  font-size: 15px; color: var(--ink-2); flex: none;
}
@container wf (max-width: 1024px) {
  .wf-side { display: none; }
  .wf-burger { display: flex; }
}
.wf-search {
  display: flex; align-items: center; gap: 6px;
  border: 1px solid var(--line-2); border-radius: 6px;
  padding: 5px 9px; min-width: 220px; color: var(--muted); font-size: 12.5px;
}
.wf-search .mag { font-size: 12px; }
.wf-top .spacer { flex: 1; }
.wf-bell {
  position: relative; width: 32px; height: 30px; border: 1px solid var(--line-2);
  border-radius: 6px; display: grid; place-items: center; font-size: 14px;
}
.wf-bell .dot {
  position: absolute; top: -6px; right: -6px; background: var(--ink); color: #fff;
  font: 700 9px/1 var(--mono); border-radius: 9px; padding: 2px 4px;
}
.wf-user { font-size: 12.5px; color: var(--ink-2); }
.wf-user small { display: block; font-size: 10.5px; color: var(--muted); }
.wf-btn {
  display: inline-flex; align-items: center; gap: 6px;
  border: 1px solid var(--ink-2); background: #fff; color: var(--ink);
  border-radius: 6px; padding: 7px 13px; font: 600 12.5px/1 var(--sans);
  cursor: pointer; white-space: nowrap;
}
.wf-btn.primary { background: var(--ink); color: #fff; border-color: var(--ink); }
.wf-btn.danger  { border-style: dashed; }
.wf-btn.sm      { padding: 5px 9px; font-size: 11.5px; }
.wf-btn.ghost   { border-color: var(--line-2); color: var(--ink-2); font-weight: 500; }

@container wf (max-width: 767px) {
  .wf-search { display: none; }
  .wf-top { gap: 8px; padding: 0 12px; }
  .wf-user { max-width: 110px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; }
  .wf-user small { display: none; }
}
.wf-body { padding: 22px 24px 34px; flex: 1; min-width: 0; }
@container wf (max-width: 767px) { .wf-body { padding: 16px 14px 26px; } }

/* ---------- blocks ---------- */
.crumbs { font-size: 11.5px; color: var(--muted); margin-bottom: 8px; }
.crumbs span { margin: 0 4px; }

.phead {
  display: flex; align-items: flex-start; gap: 14px;
  padding-bottom: 14px; margin-bottom: 18px;
  border-bottom: 1px solid var(--line-3);
}
.phead .t { flex: 1; min-width: 0; }
.phead h2 { margin: 0 0 4px; font-size: 21px; letter-spacing: -.01em; }
.phead p { margin: 0; color: var(--muted); font-size: 13px; max-width: 70ch; }
.phead .acts { display: flex; gap: 8px; flex-wrap: wrap; }
@container wf (max-width: 767px) {
  .phead { flex-direction: column; }
  .phead .acts { width: 100%; }
}

.h3 { font-size: 14.5px; font-weight: 700; margin: 22px 0 10px; }
.h3:first-child { margin-top: 0; }

.grid { display: grid; gap: 14px; }
.grid.c2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
.grid.c3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
.grid.c4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
.grid.side { grid-template-columns: minmax(0,2fr) minmax(0,1fr); }
@container wf (max-width: 1024px) {
  .grid.c4 { grid-template-columns: repeat(2, minmax(0,1fr)); }
  .grid.side { grid-template-columns: 1fr; }
}
@container wf (max-width: 767px) {
  .grid.c2, .grid.c3, .grid.c4 { grid-template-columns: 1fr; }
}

.card {
  border: 1px solid var(--line-2); border-radius: var(--radius);
  background: var(--paper); padding: 14px 16px;
}
.card.tight { padding: 0; overflow: hidden; }
.card > .card-h {
  display: flex; align-items: center; gap: 10px;
  padding: 13px 16px; border-bottom: 1px solid var(--line-3);
}
.card > .card-h h3 { margin: 0; font-size: 14px; flex: 1; }
.card > .card-b { padding: 14px 16px; }

.stat { border: 1px solid var(--line-2); border-radius: var(--radius);
  padding: 13px 15px; background: var(--paper); }
.stat .k { font-size: 11px; text-transform: uppercase; letter-spacing: .07em;
  color: var(--muted); }
.stat .v { font-size: 26px; font-weight: 700; letter-spacing: -.02em;
  margin: 5px 0 2px; font-variant-numeric: tabular-nums; }
.stat .m { font-size: 11.5px; color: var(--muted); }

.tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--line-2);
  margin-bottom: 16px; overflow-x: auto; }
.tabs a { padding: 8px 13px; font-size: 12.5px; color: var(--muted);
  text-decoration: none; border-bottom: 2px solid transparent; white-space: nowrap; }
.tabs a.on { color: var(--ink); font-weight: 700; border-bottom-color: var(--ink); }

/* filters */
.filters {
  border: 1px solid var(--line-2); border-radius: var(--radius);
  background: var(--fill); padding: 12px 14px; margin-bottom: 16px;
  display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;
}
.field { display: flex; flex-direction: column; gap: 4px; min-width: 120px; }
.field.grow { flex: 1 1 200px; }
.field > label { font-size: 11px; text-transform: uppercase;
  letter-spacing: .06em; color: var(--muted); font-weight: 600; }
.field > label .req { color: var(--ink); }
.ctl {
  border: 1px solid var(--line); border-radius: 5px; background: #fff;
  padding: 7px 9px; font-size: 12.5px; color: var(--ink);
  min-height: 33px; display: flex; align-items: center;
  justify-content: space-between; gap: 8px;
}
.ctl .ph { color: var(--muted); }
.ctl .car { color: var(--muted); font-size: 9px; }
.ctl.area { min-height: 84px; align-items: flex-start; }
.filters .spacer { flex: 1; }

/* tables */
.tw { border: 1px solid var(--line-2); border-radius: var(--radius);
  overflow-x: auto; overflow-y: hidden; background: var(--paper); }
table.wt { width: 100%; border-collapse: collapse; font-size: 12.5px; }
table.wt th {
  text-align: left; font-size: 10.5px; text-transform: uppercase;
  letter-spacing: .06em; color: var(--muted); background: var(--fill);
  padding: 9px 12px; border-bottom: 1px solid var(--line-2); white-space: nowrap;
}
table.wt td { padding: 10px 12px; border-bottom: 1px solid var(--line-3);
  vertical-align: middle; }
table.wt tr:last-child td { border-bottom: 0; }
table.wt td.num { font-variant-numeric: tabular-nums; }
table.wt td.act { text-align: right; white-space: nowrap; }
.tbl-note { padding: 8px 12px; font-size: 12px; color: var(--muted);
  border-top: 1px solid var(--line-3); background: var(--fill); }

/* stacked table at phone width - one card per row, labels from data-label */
@container wf (max-width: 767px) {
  table.wt thead { display: none; }
  table.wt, table.wt tbody, table.wt tr, table.wt td { display: block; width: 100%; }
  table.wt tr { border-bottom: 1px solid var(--line-2); padding: 8px 0; }
  table.wt tr:last-child { border-bottom: 0; }
  table.wt td { border: 0; padding: 4px 12px; display: flex; gap: 10px;
    align-items: flex-start; }
  table.wt td::before { content: attr(data-label); flex: 0 0 38%;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--muted); padding-top: 2px; }
  table.wt td.act { padding-top: 9px; }
  table.wt td.act::before { content: ""; flex: 0 0 0; }
}

.badge {
  display: inline-block; border: 1px solid var(--ink-2); border-radius: 20px;
  padding: 2px 9px; font-size: 10.5px; font-weight: 700;
  letter-spacing: .03em; text-transform: uppercase; white-space: nowrap;
}
.badge.solid { background: var(--ink); color: #fff; border-color: var(--ink); }
.badge.dash  { border-style: dashed; }

.pager { display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
  margin: 12px 0 14px; font-size: 12px; color: var(--muted); }
.pager .sp { flex: 1 1 auto; }
.pager .n { border: 1px solid var(--line-2); border-radius: 5px;
  padding: 4px 9px; background: #fff; color: var(--ink-2); }
.pager .n.on { background: var(--ink); color: #fff; border-color: var(--ink); }

/* forms */
.form-sec { margin-bottom: 20px; }
.form-sec > .fh { font-size: 13.5px; font-weight: 700; padding-bottom: 8px;
  border-bottom: 1px solid var(--line-3); margin-bottom: 13px; }
.fgrid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 13px; }
.fgrid .span2 { grid-column: 1 / -1; }
@container wf (max-width: 767px) { .fgrid { grid-template-columns: 1fr; }
  .fgrid .span2 { grid-column: auto; } }
@container wf (max-width: 767px) {
  .pager .count { flex: 1 0 100%; margin-bottom: 2px; }
  .pager .sp { display: none; }
  .pager .n { padding: 4px 8px; }
  .line .xax span:nth-child(even) { display: none; }
  .phead .acts .wf-btn.ghost { border-color: var(--line); background: #fff;
    color: var(--ink-2); }
}
.fhint { font-size: 11px; color: var(--muted); }
.ferr { font-size: 11px; color: var(--ink); font-weight: 700; }
.fhelp { font-size: 11.5px; color: var(--muted); margin-top: 3px; }
.facts { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 18px;
  padding-top: 16px; border-top: 1px solid var(--line-3); }

/* detail lists */
.dl { display: grid; grid-template-columns: 190px minmax(0,1fr);
  gap: 0; font-size: 12.5px; }
.dl dt { color: var(--muted); padding: 8px 0; border-bottom: 1px solid var(--line-3); }
.dl dd { margin: 0; padding: 8px 0; border-bottom: 1px solid var(--line-3); }
@container wf (max-width: 767px) {
  .dl { grid-template-columns: 1fr; }
  .dl dt { border-bottom: 0; padding-bottom: 0; }
}

/* timeline */
.tl { list-style: none; margin: 0; padding: 0 0 0 18px;
  border-left: 2px solid var(--line-2); }
.tl li { position: relative; padding: 0 0 16px 14px; }
.tl li:last-child { padding-bottom: 0; }
.tl li::before {
  content: ""; position: absolute; left: -24px; top: 3px;
  width: 9px; height: 9px; border-radius: 50%;
  background: #fff; border: 2px solid var(--ink-2);
}
.tl .when { font: 600 11px/1 var(--mono); color: var(--muted); }
.tl .what { font-weight: 700; font-size: 13px; margin: 4px 0 2px; }
.tl .why { font-size: 12px; color: var(--ink-2); }

/* progress / stepper */
.steps { display: flex; flex-wrap: wrap; gap: 0; margin: 0 0 6px; }
.steps .st { display: flex; align-items: center; gap: 8px; padding: 0 14px 0 0;
  font-size: 12px; color: var(--muted); }
.steps .st .dot { width: 22px; height: 22px; border-radius: 50%;
  border: 1.5px solid var(--line); display: grid; place-items: center;
  font: 700 10px/1 var(--mono); background: #fff; }
.steps .st.done { color: var(--ink); }
.steps .st.done .dot { background: var(--ink); color: #fff; border-color: var(--ink); }
.steps .st.now { color: var(--ink); font-weight: 700; }
.steps .st.now .dot { border-color: var(--ink); border-width: 3px; }
.steps .ln { flex: 0 0 26px; height: 1px; background: var(--line-2); margin-right: 14px; }
@container wf (max-width: 767px) {
  .shell-spec { grid-template-columns: 1fr !important; }
  .shell-spec .spec-side { border-right: 0 !important;
    border-bottom: 1px solid var(--line-2); }
  .shell-spec .wf-search { display: none !important; }
}

@container wf (max-width: 767px) {
  .steps { flex-direction: column; align-items: flex-start; gap: 7px; }
  .steps .ln { display: none; }
  .steps .st { padding: 0; }
}

/* notices */
.notice { display: flex; gap: 10px; border: 1px solid var(--line);
  border-left-width: 4px; border-radius: 6px; padding: 11px 13px;
  background: var(--fill); font-size: 12.5px; margin-bottom: 14px; }
.notice .ic { font-weight: 800; font-size: 13px; }
.notice.warn { border-left-style: dashed; }
.notice b { display: block; margin-bottom: 2px; }

/* empty state */
.empty { text-align: center; padding: 34px 18px; color: var(--muted); }
.empty .box { width: 54px; height: 42px; border: 1.5px dashed var(--line);
  border-radius: 6px; margin: 0 auto 12px; position: relative; }
.empty .box::before, .empty .box::after {
  content: ""; position: absolute; inset: 0; margin: auto;
  width: 34px; height: 1px; background: var(--line-2);
  transform: rotate(28deg);
}
.empty .box::after { transform: rotate(-28deg); }
.empty b { display: block; color: var(--ink); font-size: 13.5px; margin-bottom: 4px; }

/* cards list */
.cardlist { display: grid; gap: 12px; }
.cardlist.c3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
@container wf (max-width: 1024px) { .cardlist.c3 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
@container wf (max-width: 767px) { .cardlist.c3 { grid-template-columns: 1fr; } }
.cl-item { border: 1px solid var(--line-2); border-radius: var(--radius);
  padding: 14px; display: flex; flex-direction: column; gap: 7px; background: #fff; }
.cl-item .top { display: flex; gap: 8px; align-items: flex-start; }
.cl-item h4 { margin: 0; font-size: 13.5px; flex: 1; }
.cl-item .meta { font-size: 11.5px; color: var(--muted); }
.cl-item p { margin: 0; font-size: 12px; color: var(--ink-2); }
.cl-item .foot { margin-top: auto; display: flex; gap: 7px; padding-top: 6px; }

/* slots */
.slots { display: grid; grid-template-columns: repeat(auto-fill, minmax(104px,1fr));
  gap: 8px; }
.slot { border: 1px solid var(--line); border-radius: 6px; padding: 8px 6px;
  text-align: center; font: 600 12px/1.3 var(--mono); background: #fff; }
.slot small { display: block; font: 400 10px/1.4 var(--sans); color: var(--muted); }
.slot.taken { background: var(--fill-2); color: var(--muted); border-style: dashed;
  text-decoration: line-through; }
.slot.free { box-shadow: inset 0 -3px 0 var(--ink); }
.slot.sel { background: var(--ink); color: #fff; border-color: var(--ink);
  box-shadow: none; }
.slot.sel small { color: #dcdcdc; }
@container wf (max-width: 767px) {
  .avail th, .avail td { padding: 4px 2px; font-size: 9.5px; }
  .avail td:first-child { font-size: 9px; padding-left: 4px; }
}

/* availability grid */
.avail { width: 100%; border-collapse: collapse; font-size: 11.5px; }
.avail th { font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em;
  color: var(--muted); padding: 7px 6px; border-bottom: 1px solid var(--line-2);
  text-align: center; }
.avail th:first-child { text-align: left; }
.avail td { padding: 5px 6px; border-bottom: 1px solid var(--line-3); text-align: center; }
.avail td:first-child { text-align: left; font-family: var(--mono); font-size: 11px;
  color: var(--muted); }
.avail .on { background: var(--ink); border-radius: 3px; }
.avail .off { background: repeating-linear-gradient(45deg,
  #fafafa, #fafafa 3px, #ececec 3px, #ececec 6px); border-radius: 3px; }

/* charts */
.chart { border: 1px solid var(--line-2); border-radius: var(--radius);
  padding: 14px 16px; background: #fff; }
.chart h4 { margin: 0 0 3px; font-size: 13px; }
.chart .sub { font-size: 11.5px; color: var(--muted); margin-bottom: 12px; }
.bars { display: flex; align-items: flex-end; gap: 10px; height: 150px;
  padding-top: 8px; border-bottom: 1px solid var(--line); }
.bars .b { flex: 1; display: flex; flex-direction: column; justify-content: flex-end;
  align-items: center; gap: 5px; height: 100%; }
.bars .b i { display: block; width: 100%; max-width: 46px; background: var(--line-2);
  border: 1px solid var(--line); border-bottom: 0; border-radius: 3px 3px 0 0; }
.bars .b em { font: 700 10.5px/1 var(--mono); font-style: normal; }
.bars .b span { font-size: 10px; color: var(--muted); text-align: center; }
.donut { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
.donut .ring { width: 116px; height: 116px; border-radius: 50%; flex: none;
  background: conic-gradient(#1b1b1b 0 42%, #8f8f8f 42% 68%, #c8c8c8 68% 86%, #e2e2e2 86% 100%);
  position: relative; }
.donut .ring::after { content: ""; position: absolute; inset: 26px;
  background: #fff; border-radius: 50%; border: 1px solid var(--line-3); }
.donut .lg { display: grid; gap: 6px; font-size: 12px; }
.donut .lg i { display: inline-block; width: 10px; height: 10px; margin-right: 7px;
  border: 1px solid var(--line); vertical-align: -1px; }
.line .plot { position: relative; height: 140px; border-bottom: 1px solid var(--line); }
.line .grid { position: absolute; inset: 0 0 1px; display: flex;
  flex-direction: column; justify-content: space-between; }
.line .grid i { display: block; height: 1px; background: var(--line-3); }
.line .grid i:first-child { background: var(--line-2); }
.line svg { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
.line .ymax, .line .ymin { position: absolute; left: 0; font: 700 9.5px/1 var(--mono);
  color: var(--muted); background: #fff; padding: 2px 4px 2px 0; }
.line .ymax { top: 0; }
.line .ymin { bottom: 0; }
.line .xax { display: flex; justify-content: space-between; gap: 4px;
  margin-top: 6px; font-size: 10px; color: var(--muted); }
.line .xax span { white-space: nowrap; }

/* misc */
.split2 { display: grid; grid-template-columns: minmax(0,1.5fr) minmax(0,1fr);
  gap: 16px; }
@container wf (max-width: 900px) { .split2 { grid-template-columns: 1fr; } }
.muted { color: var(--muted); }
.mono { font-family: var(--mono); }
.small { font-size: 12px; }
.row { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.kv { display: flex; gap: 6px; font-size: 12px; }
.kv b { color: var(--muted); font-weight: 600; }
.imgph { border: 1.5px dashed var(--line); border-radius: 6px;
  min-height: 120px; display: grid; place-items: center; color: var(--muted);
  font-size: 11.5px; background: repeating-linear-gradient(135deg,
    #fbfbfb, #fbfbfb 6px, #f3f3f3 6px, #f3f3f3 12px); }
.avatar { width: 34px; height: 34px; border-radius: 50%;
  border: 1.5px dashed var(--line); display: grid; place-items: center;
  font-size: 10px; color: var(--muted); flex: none; }
.divider { height: 1px; background: var(--line-3); margin: 18px 0; }

/* ---------- annotation panel ---------- */
.vf-notes { max-width: 1440px; margin: 0 auto 34px;
  border: 1px solid var(--line); border-radius: 10px; background: #fafafa;
  padding: 16px 18px; }
.vf-notes h3 { margin: 0 0 4px; font-size: 13px; text-transform: uppercase;
  letter-spacing: .08em; color: var(--muted); }
.vf-notes .purpose { font-size: 13px; margin: 0 0 14px; color: var(--ink-2); }
.vf-notes ol { margin: 0 0 14px; padding-left: 20px; font-size: 12.5px;
  columns: 2; column-gap: 34px; }
.vf-notes ol li { margin-bottom: 7px; break-inside: avoid; }
.vf-notes table { width: 100%; border-collapse: collapse; font-size: 12px; }
.vf-notes th { text-align: left; font-size: 10.5px; text-transform: uppercase;
  letter-spacing: .06em; color: var(--muted); padding: 6px 8px;
  border-bottom: 1px solid var(--line); }
.vf-notes td { padding: 6px 8px; border-bottom: 1px solid var(--line-3);
  vertical-align: top; }
.vf-notes td:first-child { width: 90px; font-weight: 700; }

/* ---------- embed mode (clean screenshot) ---------- */
body.embed { padding: 0; background: #fff; }
body.embed .doc-head, body.embed .doc-meta, body.embed .vf-chrome,
body.embed .vf-notes { display: none !important; }
body.embed .vf { margin: 0; max-width: none; }
body.embed .vf-frame { width: 100% !important; max-width: none; border: 0;
  border-radius: 0; box-shadow: none; }
body.embed .wf-app { min-height: 900px; }

@media print {
  body { background: #fff; padding: 0; }
  .vf-chrome, .vf-notes { display: none; }
  .vf-frame { box-shadow: none; }
}
"""

# --------------------------------------------------------------------------
# Navigation definitions (verbatim from components/navigation/sidebar)
# --------------------------------------------------------------------------

NAV = {
    "student": [
        ("Main", [("Dashboard", "student.dashboard", True), ]),
        ("Scholarships", [
            ("Browse Scholarships", "student.scholarships.index", False),
            ("Recommended Scholarships", "student.scholarships.recommendations", False),
            ("My Applications", "student.applications.index", False),
        ]),
        ("Services", [
            ("Guidance Appointments", "student.guidance.appointments", False),
            ("OSAS Services", "student.services.catalog", False),
        ]),
        ("Account", [
            ("My Profile", "student.profile.show", False),
            ("Notifications", "notifications.index", False),
        ]),
    ],
    "counselor": [
        ("Main", [("Dashboard", "counselor.dashboard", True)]),
        ("Guidance", [
            ("My Appointments", "counselor.guidance.appointments", False),
            ("Availability", "counselor.guidance.availability", False),
        ]),
        ("OSAS", [
            ("Enrollment Verification", "enrollment-verifications.index", False),
        ]),
        ("Account", [
            ("Notifications", "notifications.index", False),
        ]),
    ],
    "admin": [
        ("Main", [("Dashboard", "admin.dashboard", True)]),
        ("Management", [
            ("User Management", "admin.users.index", False),
            ("Student Profiles", "admin.student-profiles.index", False),
            ("Enrollment Verification", "enrollment-verifications.index", False),
            ("Scholarships", "admin.scholarships.index", False),
            ("Scholarship Applications", "admin.scholarship-applications.index", False),
            ("Matching Weights", "admin.scholarship-matching.weights.edit", False),
        ]),
        ("Guidance & Services", [
            ("Guidance Management", "admin.guidance.appointments", False),
            ("Service Requests", "admin.student-service-requests.index", False),
            ("Service Types", "admin.service-types.index", False),
        ]),
        ("Insights", [
            ("Analytics & Reports", "admin.analytics.index", False),
        ]),
        ("System", [
            ("Audit Logs", "admin.audit-logs.index", False),
        ]),
    ],
}

ROLE_LABEL = {"student": "Student", "counselor": "Counselor", "admin": "Administrator"}
ROLE_USER = {"student": "Maria L. Santos", "counselor": "Prof. R. Dela Cruz",
             "admin": "A. Bautista"}


def esc(value) -> str:
    return html.escape(str(value), quote=True)


# --------------------------------------------------------------------------
# Block renderers
# --------------------------------------------------------------------------

def b_header(b):
    acts = "".join(
        f'<button class="wf-btn {k}">{esc(l)}</button>' for l, k in b.get("actions", [])
    )
    crumbs = b.get("crumbs")
    cr = ""
    if crumbs:
        cr = '<div class="crumbs">' + " <span>&rsaquo;</span> ".join(
            esc(c) for c in crumbs) + "</div>"
    return (f'{cr}<div class="phead"><div class="t"><h2>{esc(b["title"])}</h2>'
            f'<p>{esc(b.get("subtitle",""))}</p></div>'
            f'<div class="acts">{acts}</div></div>')


def b_notice(b):
    ic = {"info": "i", "warn": "!", "error": "!", "ok": "âœ“"}.get(b.get("kind", "info"), "i")
    title = f'<b>{esc(b["title"])}</b>' if b.get("title") else ""
    return (f'<div class="notice {esc(b.get("kind","info"))}">'
            f'<span class="ic">{ic}</span><div>{title}{esc(b.get("text",""))}</div></div>')


def b_stats(b):
    items = "".join(
        f'<div class="stat"><div class="k">{esc(k)}</div>'
        f'<div class="v">{esc(v)}</div><div class="m">{esc(m)}</div></div>'
        for k, v, m in b["items"])
    n = b.get("cols", 4)
    return f'<div class="grid c{n}">{items}</div>'


def b_tabs(b):
    return ('<div class="tabs">' + "".join(
        f'<a class="{"on" if on else ""}" href="#">{esc(l)}</a>' for l, on in b["items"]
    ) + "</div>")


def b_filters(b):
    out = []
    for f in b["fields"]:
        label, kind = f[0], f[1]
        opts = f[2] if len(f) > 2 else None
        cls = "field grow" if kind == "search" else "field"
        if kind == "search":
            ctl = ('<div class="ctl"><span class="ph">Search by name, number, or '
                   'reference&hellip;</span><span class="mag">&#9906;</span></div>')
        elif kind == "select":
            first = opts[0] if opts else "All"
            ctl = f'<div class="ctl"><span>{esc(first)}</span><span class="car">&#9660;</span></div>'
        elif kind == "date":
            ctl = '<div class="ctl"><span class="ph">dd / mm / yyyy</span><span class="car">&#128197;</span></div>'
        else:
            ctl = '<div class="ctl"><span class="ph">&nbsp;</span></div>'
        out.append(f'<div class="{cls}"><label>{esc(label)}</label>{ctl}</div>')
    btns = "".join(f'<button class="wf-btn {k}">{esc(l)}</button>'
                   for l, k in b.get("buttons", [("Filter", "primary")]))
    return ('<div class="filters">' + "".join(out) +
            f'<div class="spacer"></div>{btns}</div>')


def _pretty(value) -> str:
    """Display form of a stored status value: under_review -> Under review."""
    text = str(value).replace("_", " ").strip()
    return text[:1].upper() + text[1:] if text else text


def b_table(b):
    cols = b["columns"]
    head = "".join(f"<th>{esc(c)}</th>" for c in cols)
    rows = []
    for r in b["rows"]:
        cells = []
        for i, c in enumerate(r):
            label = f' data-label="{esc(cols[i] if i < len(cols) else "")}"'
            if b.get("status_col") == i:
                raw = str(c).strip().lower().replace(" ", "_")
                solid = "solid" if raw in (
                    "approved", "completed", "verified", "open", "confirmed") else ""
                cells.append(f'<td{label}><span class="badge {solid}" '
                             f'data-status="{esc(raw)}">{esc(_pretty(c))}</span></td>')
            elif i == len(cols) - 1 and b.get("actions"):
                acts = " ".join(
                    f'<button class="wf-btn sm {"ghost" if a.lower() in ("view","open") else ""}">'
                    f'{esc(a)}</button>' for a in b["actions"])
                cells.append(f'<td class="act"{label}>{acts}</td>')
            else:
                num = " num" if str(c).replace(",", "").replace(".", "").replace("-", "").isdigit() else ""
                cells.append(f'<td class="{num.strip()}"{label}>{esc(c)}</td>')
        rows.append("<tr>" + "".join(cells) + "</tr>")
    note = f'<div class="tbl-note">{esc(b["note"])}</div>' if b.get("note") else ""
    return (f'<div class="tw"><table class="wt"><thead><tr>{head}</tr></thead>'
            f'<tbody>{"".join(rows)}</tbody></table>{note}</div>')


def b_pager(b):
    nums = "".join(
        f'<span class="n {"on" if i == b.get("current", 1) else ""}">{i}</span>'
        for i in range(1, min(b.get("total", 3), 6) + 1))
    count = b.get("count") or "1\u201315 of 15"
    return (f'<div class="pager"><span class="count">{esc(count)}</span>'
            f'<span class="sp"></span><span class="n">&lsaquo; Prev</span>{nums}'
            f'<span class="n">Next &rsaquo;</span></div>')


def _field(f):
    span = " span2" if f.get("span") == 2 else ""
    label = esc(f["label"])
    if f.get("required"):
        label += ' <span class="req">*</span>'
    kind = f.get("type", "text")
    if kind == "textarea":
        ctl = '<div class="ctl area"><span class="ph">' + esc(
            f.get("placeholder", "Enter detailsâ€¦")) + "</span></div>"
    elif kind == "select":
        ctl = (f'<div class="ctl"><span>{esc(f.get("value", (f.get("options") or ["Select"])[0]))}'
               '</span><span class="car">&#9660;</span></div>')
    elif kind == "file":
        ctl = ('<div class="ctl" style="border-style:dashed"><span class="ph">Choose file'
               ' &mdash; PDF, JPG or PNG, max 5 MB</span><span>&#8682;</span></div>')
    elif kind == "checkbox":
        ctl = ('<div class="row small"><span style="width:14px;height:14px;border:1px solid '
               'var(--line);border-radius:3px;display:inline-block"></span>'
               f'<span>{esc(f.get("placeholder",""))}</span></div>')
    else:
        ctl = (f'<div class="ctl"><span class="ph">{esc(f.get("placeholder","") or "&nbsp;")}'
               '</span></div>') if f.get("placeholder") else '<div class="ctl"></div>'
    bits = [f'<div class="field{span}" style="min-width:0"><label>{label}</label>{ctl}']
    if f.get("help"):
        bits.append(f'<div class="fhelp">{esc(f["help"])}</div>')
    if f.get("error"):
        bits.append(f'<div class="ferr">! {esc(f["error"])}</div>')
    bits.append("</div>")
    return "".join(bits)


def b_form(b):
    out = []
    for sec in b.get("sections", []):
        out.append('<div class="form-sec">')
        if sec.get("heading"):
            out.append(f'<div class="fh">{esc(sec["heading"])}</div>')
        out.append('<div class="fgrid">')
        out.extend(_field(f) for f in sec.get("fields", []))
        out.append("</div></div>")
    btns = "".join(f'<button class="wf-btn {k}">{esc(l)}</button>'
                   for l, k in b.get("buttons", [("Save changes", "primary"), ("Cancel", "outline")]))
    out.append(f'<div class="facts">{btns}</div>')
    return "".join(out)


def b_detail(b):
    out = []
    for sec in b.get("sections", []):
        out.append('<div class="form-sec">')
        if sec.get("heading"):
            out.append(f'<div class="fh">{esc(sec["heading"])}</div>')
        out.append('<dl class="dl">')
        for label, value in sec["pairs"]:
            out.append(f'<dt>{esc(label)}</dt><dd>{esc(value)}</dd>')
        out.append("</dl></div>")
    return "".join(out)


def b_timeline(b):
    items = "".join(
        f'<li><div class="when">{esc(w)}</div><div class="what">{esc(t)}</div>'
        f'<div class="why">{esc(d)}</div></li>' for w, t, d in b["items"])
    return f'<ul class="tl">{items}</ul>'


def b_steps(b):
    out = []
    for i, (label, state) in enumerate(b["items"]):
        out.append(f'<div class="st {esc(state)}"><span class="dot">'
                   f'{"&#10003;" if state == "done" else i+1}</span>{esc(label)}</div>')
        if i < len(b["items"]) - 1:
            out.append('<span class="ln"></span>')
    return '<div class="steps">' + "".join(out) + "</div>"


def b_cards(b):
    items = []
    for it in b["items"]:
        top = f'<h4>{esc(it["title"])}</h4>'
        if it.get("tag"):
            raw = str(it["tag"]).strip().lower().replace(" ", "_")
            top += (f'<span class="badge {it.get("tagkind","")}" '
                    f'data-status="{esc(raw)}">{esc(it["tag"])}</span>')
        foot = "".join(f'<button class="wf-btn sm {k}">{esc(l)}</button>'
                       for l, k in it.get("footer", []))
        items.append('<div class="cl-item">'
                     f'<div class="top">{top}</div>'
                     f'<div class="meta">{esc(it.get("meta",""))}</div>'
                     f'<p>{esc(it.get("body",""))}</p>'
                     f'<div class="foot">{foot}</div></div>')
    cls = "cardlist" + (" c3" if b.get("cols") == 3 else "")
    return f'<div class="{cls}">{"".join(items)}</div>'


def b_empty(b):
    act = ""
    if b.get("action"):
        l, k = b["action"]
        act = f'<div style="margin-top:14px"><button class="wf-btn {k}">{esc(l)}</button></div>'
    return (f'<div class="empty"><div class="box"></div><b>{esc(b["title"])}</b>'
            f'{esc(b.get("text",""))}{act}</div>')


def b_slots(b):
    items = "".join(
        f'<div class="slot {esc(state)}">{esc(t)}<small>{esc(lbl)}</small></div>'
        for t, lbl, state in b["items"])
    head = f'<div class="h3">{esc(b["date"])}</div>' if b.get("date") else ""
    return head + f'<div class="slots">{items}</div>'


def b_availability(b):
    head = "".join(f"<th>{esc(d)}</th>" for d in b["days"])
    rows = []
    for time, flags in b["rows"]:
        cells = "".join(
            f'<td class="{"on" if f == 1 else "off"}">&nbsp;</td>' for f in flags)
        rows.append(f"<tr><td>{esc(time)}</td>{cells}</tr>")
    return (f'<table class="avail"><thead><tr><th>Time</th>{head}</tr></thead>'
            f'<tbody>{"".join(rows)}</tbody></table>')


def b_chart(b):
    kind = b.get("kind", "bars")
    if kind == "bars":
        mx = max(b["values"]) or 1
        bars = "".join(
            f'<div class="b"><em>{esc(v)}</em><i style="height:{(v/mx)*100:.0f}%"></i>'
            f'<span>{esc(l)}</span></div>'
            for l, v in zip(b["labels"], b["values"]))
        body = f'<div class="bars">{bars}</div>'
    elif kind == "donut":
        lg = "".join(f'<div><i></i>{esc(l)} &mdash; <b>{esc(v)}</b></div>'
                     for l, v in zip(b["labels"], b["values"]))
        body = f'<div class="donut"><div class="ring"></div><div class="lg">{lg}</div></div>'
    elif b.get("labels") and b.get("values"):
        vals = b["values"]
        mx = max(vals) or 1
        n = len(vals)
        pts = " ".join(
            f"{(i * 400 / (n - 1) if n > 1 else 0):.1f},"
            f"{(118 - (v / mx) * 116):.1f}" for i, v in enumerate(vals))
        grid = "".join("<i></i>" for _ in range(4))
        labels = "".join(f"<span>{esc(l)}</span>" for l in b["labels"])
        series = ", ".join(str(v) for v in vals)
        body = (f'<div class="line">'
                f'<div class="plot" role="img" aria-label="{esc(b.get("title",""))}: '
                f'{esc(series)}">'
                f'<div class="grid" aria-hidden="true">{grid}</div>'
                f'<svg viewBox="0 0 400 120" preserveAspectRatio="none" '
                f'aria-hidden="true"><polyline points="{pts}" fill="none" '
                f'stroke="var(--ink)" stroke-width="1.6" '
                f'vector-effect="non-scaling-stroke"/></svg>'
                f'<span class="ymax">{esc(mx)}</span><span class="ymin">0</span></div>'
                f'<div class="xax">{labels}</div></div>')
    else:  # empty series: an axis with no data, not an invented trend
        body = ('<div class="line"><div class="plot"><div class="grid" aria-hidden="true">'
                '<i></i><i></i><i></i><i></i></div></div>'
                '<div class="xax"><span>No data for this period</span></div></div>')
    sub = f'<div class="sub">{esc(b["sub"])}</div>' if b.get("sub") else ""
    return (f'<div class="chart"><h4>{esc(b.get("title",""))}</h4>{sub}{body}</div>')


def b_split(b):
    left = "".join(render_block(x) for x in b["left"])
    right = "".join(render_block(x) for x in b["right"])
    cls = "split2" if b.get("ratio", "2:1") == "2:1" else "grid side"
    return f'<div class="{cls}"><div>{left}</div><div>{right}</div></div>'


def b_grid(b):
    inner = "".join(render_block(x) for x in b["items"])
    return f'<div class="grid c{b.get("cols",2)}">{inner}</div>'


def b_card(b):
    head = ""
    if b.get("title"):
        acts = "".join(f'<button class="wf-btn sm {k}">{esc(l)}</button>'
                       for l, k in b.get("actions", []))
        head = f'<div class="card-h"><h3>{esc(b["title"])}</h3>{acts}</div>'
    inner = "".join(render_block(x) for x in b.get("items", []))
    pad = ' style="padding:14px 16px"' if head else ""
    return (f'<div class="card{" tight" if head else ""}">{head}'
            f'<div class="card-b"{pad}>{inner}</div></div>')


def b_feed(b):
    out = []
    for it in b["items"]:
        unread = it.get("unread")
        dot = ('<span style="width:8px;height:8px;border-radius:50%;background:var(--ink);'
               'display:inline-block;flex:none"></span>' if unread else
               '<span style="width:8px;height:8px;display:inline-block;flex:none"></span>')
        out.append('<div style="display:flex;gap:11px;padding:12px 0;'
                   'border-bottom:1px solid var(--line-3)">' + dot +
                   '<div style="flex:1;min-width:0">'
                   f'<div style="font-weight:{"700" if unread else "600"};font-size:13px">'
                   f'{esc(it["title"])}</div>'
                   f'<div class="small muted" style="margin:2px 0 4px">{esc(it["body"])}</div>'
                   f'<div class="mono muted" style="font-size:10.5px">{esc(it["when"])}</div>'
                   '</div>'
                   '<div style="flex:none"><button class="wf-btn sm ghost">'
                   f'{esc(it.get("action","Open"))}</button></div></div>')
    return "".join(out)


def b_img(b):
    return (f'<div class="imgph" style="min-height:{b.get("h",120)}px">'
            f'{esc(b.get("label","Image placeholder"))}</div>')


def b_html(b):
    return b["html"]


RENDERERS = {
    "header": b_header, "notice": b_notice, "stats": b_stats, "tabs": b_tabs,
    "filters": b_filters, "table": b_table, "pager": b_pager, "form": b_form,
    "detail": b_detail, "timeline": b_timeline, "steps": b_steps, "cards": b_cards,
    "empty": b_empty, "slots": b_slots, "availability": b_availability,
    "chart": b_chart, "split": b_split, "grid": b_grid, "card": b_card,
    "feed": b_feed, "img": b_img, "html": b_html,
}


def render_block(b):
    fn = RENDERERS.get(b["type"])
    if fn is None:
        raise ValueError(f"unknown block type: {b['type']}")
    return fn(b)


# --------------------------------------------------------------------------
# Page assembly
# --------------------------------------------------------------------------

def sidebar(role, active_route):
    if role == "guest":
        return ""
    parts = ['<aside class="wf-side" aria-label="Main navigation">',
             '<div class="wf-brand"><span class="mark">ISU</span><span>'
             '<span class="t1">ISU OSAS Hub</span>'
             '<span class="t2">Student Affairs &amp; Services</span></span></div>',
             '<nav class="wf-nav">']
    for section, links in NAV[role]:
        parts.append(f'<p class="sec">{esc(section)}</p>')
        for label, route, _default in links:
            on = (route == active_route)
            parts.append(
                f'<a class="{"on" if on else ""}" href="#">'
                f'<span class="sq"></span>{esc(label)}'
                + ('<span class="count">3</span>' if label == "Notifications" else "")
                + "</a>")
    parts.append("</nav>")
    parts.append('<div class="foot">&copy; 2026 Isabela State University<br>'
                 'Office of Student Affairs and Services</div></aside>')
    return "".join(parts)


def topbar(role, search=True):
    if role == "guest":
        return ""
    user = ROLE_USER.get(role, "User")
    label = ROLE_LABEL.get(role, "")
    searchbox = ('<div class="wf-search"><span class="mag">&#9906;</span>'
                 '<span>Quick Search scholarships&hellip;</span></div>' if search else "")
    return ('<header class="wf-top"><div class="wf-burger">&#9776;</div>'
            f'{searchbox}<div class="spacer"></div>'
            '<div class="wf-bell">&#128276;<span class="dot">3</span></div>'
            f'<div class="wf-user">{esc(user)}<small>({esc(label)})</small></div>'
            '<button class="wf-btn sm">Logout</button></header>')


def render_page(p):
    blocks = "".join(render_block(b) for b in p["blocks"])
    role = p["role"]
    shell = ""
    if role != "guest":
        shell = (sidebar(role, p.get("nav_active", "")) +
                 '<div class="wf-main">' + topbar(role, p.get("search", True)) +
                 f'<div class="wf-body">{blocks}</div></div>')
    else:
        shell = f'<div class="wf-main"><div class="wf-body">{blocks}</div></div>'

    notes = "".join(f"<li>{esc(n)}</li>" for n in p.get("annotations", []))
    resp = "".join(f'<tr><td>{esc(v)}</td><td>{esc(d)}</td></tr>'
                   for v, d in p.get("responsive", []))
    resp_tbl = (f'<table><thead><tr><th>Viewport</th><th>Layout behaviour</th></tr>'
                f'</thead><tbody>{resp}</tbody></table>' if resp else "")

    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{esc(p['id'])} &middot; {esc(p['title'])} &middot; ISU OSAS Hub wireframe</title>
<style>{CSS}</style>
</head>
<body>
<div class="doc-head">
  <span class="id">{esc(p['id'])}</span>
  <h1>{esc(p['title'])}</h1>
</div>
<div class="doc-meta">
  <span><b>Role</b>{esc(ROLE_LABEL.get(p['role'], 'Guest'))}</span>
  <span><b>Route</b><code>{esc(p.get('route','â€”'))}</code></span>
  <span><b>Module</b>{esc(p.get('module','â€”'))}</span>
  <span><b>Requirements</b><code>{esc(p.get('reqs','â€”'))}</code></span>
</div>
<div class="vf-chrome">
  <span>Viewport</span>
  <button data-vp="desktop" aria-pressed="true">Desktop 1440</button>
  <button data-vp="tablet" aria-pressed="false">Tablet 1024</button>
  <button data-vp="mobile" aria-pressed="false">Mobile 390</button>
</div>
<div class="vf" data-vp="desktop">
  <div class="vf-frame">
    <div class="wf-app">{shell}</div>
  </div>
</div>
<section class="vf-notes">
  <h3>Purpose</h3>
  <p class="purpose">{esc(p.get('purpose',''))}</p>
  <h3>Annotations</h3>
  <ol>{notes}</ol>
  {resp_tbl}
</section>
<script>
(function () {{
  var vf = document.querySelector('.vf');
  document.querySelectorAll('.vf-chrome button').forEach(function (b) {{
    b.addEventListener('click', function () {{
      document.querySelectorAll('.vf-chrome button').forEach(function (x) {{
        x.setAttribute('aria-pressed', String(x === b));
      }});
      vf.setAttribute('data-vp', b.dataset.vp);
    }});
  }});
  var params = new URLSearchParams(location.search);
  if (params.get('embed') === '1') {{
    document.body.classList.add('embed');
  }}
  var vp = params.get('vp');
  if (vp) {{ vf.setAttribute('data-vp', vp); }}
}})();
</script>
</body>
</html>
"""


# --------------------------------------------------------------------------
# Index page
# --------------------------------------------------------------------------

def build_index(pages, out_dir):
    groups = [
        ("Shared and guest", "shared", "Screens available before or across roles"),
        ("Student", "student", "Screens reachable by the student role"),
        ("Counselor", "counselor", "Screens reachable by the counselor role"),
        ("Administrator", "admin", "Screens reachable by the administrator role"),
    ]
    rows = []
    total = len(pages)
    for gi, (gname, folder, gdesc) in enumerate(groups):
        sel = [p for p in pages if p["folder"] == folder]
        rows.append(f'<h2>{esc(gname)} <span class="muted small">({len(sel)} screens)</span></h2>')
        rows.append(f'<p class="muted small">{esc(gdesc)}</p>')
        rows.append('<table><thead><tr><th>ID</th><th>Screen</th><th>Route</th>'
                    '<th>Module</th><th>Requirements</th><th>File</th></tr></thead><tbody>')
        for p in sel:
            rows.append(
                f'<tr><td class="mono">{esc(p["id"])}</td>'
                f'<td><a href="{esc(folder)}/{esc(p["id"])}.html">{esc(p["title"])}</a></td>'
                f'<td class="mono small">{esc(p.get("route","â€”"))}</td>'
                f'<td>{esc(p.get("module","â€”"))}</td>'
                f'<td class="mono small">{esc(p.get("reqs","â€”"))}</td>'
                f'<td class="mono small">{esc(folder)}/{esc(p["id"])}.html</td></tr>')
        rows.append('</tbody></table>')

    doc = f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ISU OSAS Hub &middot; Wireframe index</title>
<style>{CSS}
body {{ background: #fff; }}
.wrap {{ max-width: 1180px; margin: 0 auto; }}
h1 {{ font-size: 24px; margin: 0 0 4px; }}
h2 {{ font-size: 16px; margin: 28px 0 4px; padding-bottom: 6px;
      border-bottom: 1px solid var(--line-2); }}
table {{ width: 100%; border-collapse: collapse; margin: 12px 0 4px; font-size: 12.5px; }}
th {{ text-align: left; font-size: 10.5px; text-transform: uppercase;
      letter-spacing: .06em; color: var(--muted); padding: 8px 10px;
      background: var(--fill); border-bottom: 1px solid var(--line-2); }}
td {{ padding: 8px 10px; border-bottom: 1px solid var(--line-3); vertical-align: top; }}
tr:hover td {{ background: #fafafa; }}
a {{ color: var(--ink); }}
.lede {{ color: var(--ink-2); font-size: 13px; max-width: 86ch; }}
.key {{ display: flex; gap: 18px; flex-wrap: wrap; font-size: 12px;
        color: var(--muted); margin: 14px 0 0; }}
</style></head>
<body><div class="wrap">
<h1>ISU OSAS Hub &mdash; Wireframe index</h1>
<p class="lede">Low-fidelity wireframes for the {total} verified screens of the ISU OSAS Hub.
Every screen corresponds to a real route and Blade view in the delivered application.
Colour is deliberately removed: these are structural specifications, not visual designs.</p>
<div class="key">
  <span><b>{total}</b> screens</span>
  <span><b>6</b> shared</span><span><b>19</b> student</span>
  <span><b>6</b> counselor</span><span><b>27</b> administrator</span>
  <span>Open any screen and use the viewport switcher to test reflow.</span>
</div>
{''.join(rows)}
</div></body></html>"""
    path = os.path.join(out_dir, "index.html")
    with open(path, "w", encoding="utf-8", newline="\n") as fh:
        fh.write(doc)
    return path
