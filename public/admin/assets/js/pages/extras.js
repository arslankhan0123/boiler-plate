/* ============================================================
 * Orchid — Extras (File Manager, Documents, Timeline)
 * Shared toolkit + per-page controllers.
 * ============================================================ */
(function () {
  'use strict';

  // ============ Shared utilities ============
  var Extras = window.OrchidExtras = window.OrchidExtras || {};

  Extras.orchidToast = function (message, variant) {
    variant = variant || 'primary';
    var container = document.getElementById('extras-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'extras-toast-container';
      container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
      container.style.zIndex = '1090';
      document.body.appendChild(container);
    }
    var wrap = document.createElement('div');
    wrap.className = 'toast align-items-center text-bg-' + variant + ' border-0 show';
    wrap.setAttribute('role', 'alert');
    wrap.innerHTML =
      '<div class="d-flex">' +
      '<div class="toast-body">' + Extras.escapeHtml(message) + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
      '</div>';
    container.appendChild(wrap);
    setTimeout(function () {
      wrap.classList.remove('show');
      setTimeout(function () { wrap.remove(); }, 300);
    }, 3200);
  };

  Extras.escapeHtml = function (str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  };

  Extras.fmtDate = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    if (isNaN(d.getTime())) return '—';
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  };

  Extras.fmtTime = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    if (isNaN(d.getTime())) return '—';
    var hh = d.getHours(), mm = d.getMinutes();
    var ampm = hh >= 12 ? 'PM' : 'AM';
    hh = hh % 12; if (hh === 0) hh = 12;
    return hh + ':' + (mm < 10 ? '0' + mm : mm) + ' ' + ampm;
  };

  Extras.relativeTime = function (d) {
    if (!(d instanceof Date)) d = new Date(d);
    if (isNaN(d.getTime())) return '—';
    var diff = (Date.now() - d.getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    if (diff < 2629800) return Math.floor(diff / 604800) + 'w ago';
    return Extras.fmtDate(d);
  };

  Extras.formatBytes = function (bytes) {
    if (bytes === 0) return '0 B';
    if (!bytes) return '—';
    var k = 1024;
    var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
  };

  Extras.renderAvatar = function (name, color) {
    var initials = name.split(' ').map(function (n) { return n[0]; }).slice(0, 2).join('').toUpperCase();
    return '<span class="avatar avatar-xs bg-' + (color || 'primary') + '-subtle text-' + (color || 'primary') + ' fw-semibold">' + initials + '</span>';
  };

  Extras.fileTypeInfo = function (type) {
    var m = {
      folder:   { cls: 'fm-fileicon--folder',  icon: 'bi-folder-fill',           label: 'FOLDER' },
      pdf:      { cls: 'fm-fileicon--pdf',     icon: 'bi-file-earmark-pdf',      label: 'PDF' },
      doc:      { cls: 'fm-fileicon--doc',     icon: 'bi-file-earmark-word',     label: 'DOC' },
      docx:     { cls: 'fm-fileicon--doc',     icon: 'bi-file-earmark-word',     label: 'DOCX' },
      xls:      { cls: 'fm-fileicon--xls',     icon: 'bi-file-earmark-excel',    label: 'XLS' },
      xlsx:     { cls: 'fm-fileicon--xls',     icon: 'bi-file-earmark-excel',    label: 'XLSX' },
      csv:      { cls: 'fm-fileicon--xls',     icon: 'bi-file-earmark-spreadsheet', label: 'CSV' },
      ppt:      { cls: 'fm-fileicon--ppt',     icon: 'bi-file-earmark-slides',   label: 'PPT' },
      pptx:     { cls: 'fm-fileicon--ppt',     icon: 'bi-file-earmark-slides',   label: 'PPTX' },
      png:      { cls: 'fm-fileicon--img',     icon: 'bi-file-earmark-image',    label: 'PNG' },
      jpg:      { cls: 'fm-fileicon--img',     icon: 'bi-file-earmark-image',    label: 'JPG' },
      svg:      { cls: 'fm-fileicon--img',     icon: 'bi-file-earmark-image',    label: 'SVG' },
      mp4:      { cls: 'fm-fileicon--vid',     icon: 'bi-file-earmark-play',     label: 'MP4' },
      mov:      { cls: 'fm-fileicon--vid',     icon: 'bi-file-earmark-play',     label: 'MOV' },
      zip:      { cls: 'fm-fileicon--zip',     icon: 'bi-file-earmark-zip',      label: 'ZIP' },
      code:     { cls: 'fm-fileicon--code',    icon: 'bi-file-earmark-code',     label: 'CODE' },
      js:       { cls: 'fm-fileicon--code',    icon: 'bi-file-earmark-code',     label: 'JS' },
      json:     { cls: 'fm-fileicon--code',    icon: 'bi-file-earmark-code',     label: 'JSON' },
      fig:      { cls: 'fm-fileicon--design',  icon: 'bi-palette',               label: 'FIG' },
      sketch:   { cls: 'fm-fileicon--design',  icon: 'bi-palette',               label: 'SKT' },
      psd:      { cls: 'fm-fileicon--design',  icon: 'bi-palette',               label: 'PSD' },
      mp3:      { cls: 'fm-fileicon--audio',   icon: 'bi-file-earmark-music',    label: 'MP3' },
      txt:      { cls: 'fm-fileicon--generic', icon: 'bi-file-earmark-text',     label: 'TXT' }
    };
    return m[type] || m.txt;
  };

  Extras.renderFileIcon = function (type, opts) {
    opts = opts || {};
    var info = Extras.fileTypeInfo(type);
    var extraCls = opts.className || '';
    var useIcon = opts.useIcon;
    var content = useIcon ? '<i class="bi ' + info.icon + '"></i>' : info.label.slice(0, 3);
    return '<span class="fm-fileicon ' + info.cls + ' ' + extraCls + '">' + content + '</span>';
  };

  // ============ Shared datasets ============
  Extras.data = {
    filesById: {},
    files: [
      // Folders (parent-based tree)
      { id: 'fld-projects', name: 'Projects',      type: 'folder', parent: 'root', modified: '2026-07-20T10:00:00', owner: 'Alex Kim',   starred: true,  size: null, itemCount: 12 },
      { id: 'fld-designs',  name: 'Designs',       type: 'folder', parent: 'root', modified: '2026-07-15T09:00:00', owner: 'Alex Kim',   starred: false, size: null, itemCount: 8 },
      { id: 'fld-docs',     name: 'Documents',     type: 'folder', parent: 'root', modified: '2026-07-10T14:20:00', owner: 'Alex Kim',   starred: false, size: null, itemCount: 22 },
      { id: 'fld-aurora',   name: 'Aurora',        type: 'folder', parent: 'root', modified: '2026-07-22T08:15:00', owner: 'Alex Kim',   starred: true,  size: null, itemCount: 14 },
      { id: 'fld-team',     name: 'Team Shared',   type: 'folder', parent: 'root', modified: '2026-07-19T11:45:00', owner: 'Sarah Miller',starred: false,size: null, itemCount: 30 },
      { id: 'fld-archive',  name: 'Archive 2025',  type: 'folder', parent: 'root', modified: '2026-01-05T09:00:00', owner: 'Alex Kim',   starred: false, size: null, itemCount: 47 },

      // Files
      { id: 'f-001', name: 'Aurora_Mockups_v3.fig',       type: 'fig',  parent: 'root', modified: '2026-07-22T10:12:00', owner: 'Sarah Miller', starred: true,  size: 12800000 },
      { id: 'f-002', name: 'Q3_Roadmap.pdf',              type: 'pdf',  parent: 'root', modified: '2026-07-21T14:30:00', owner: 'Alex Kim',     starred: true,  size: 2400000 },
      { id: 'f-003', name: 'Design_System.sketch',        type: 'sketch',parent: 'root',modified: '2026-07-20T16:05:00', owner: 'Ava Lee',      starred: false, size: 8600000 },
      { id: 'f-004', name: 'Customer_List_Export.csv',    type: 'csv',  parent: 'root', modified: '2026-07-19T09:15:00', owner: 'James Doe',    starred: false, size: 480000 },
      { id: 'f-005', name: 'Brand_Guidelines.pdf',        type: 'pdf',  parent: 'root', modified: '2026-07-18T11:22:00', owner: 'Ava Lee',      starred: true,  size: 5200000 },
      { id: 'f-006', name: 'Q2_Financials.xlsx',          type: 'xlsx', parent: 'root', modified: '2026-07-16T13:40:00', owner: 'James Doe',    starred: false, size: 340000 },
      { id: 'f-007', name: 'Onboarding_Deck.pptx',        type: 'pptx', parent: 'root', modified: '2026-07-15T10:00:00', owner: 'Sarah Miller', starred: false, size: 12300000 },
      { id: 'f-008', name: 'Product_Demo.mp4',            type: 'mp4',  parent: 'root', modified: '2026-07-14T16:20:00', owner: 'Alex Kim',     starred: false, size: 86000000 },
      { id: 'f-009', name: 'MSA_Template.docx',           type: 'docx', parent: 'root', modified: '2026-07-13T09:45:00', owner: 'Legal Team',   starred: false, size: 220000 },
      { id: 'f-010', name: 'App_Wireframes.fig',          type: 'fig',  parent: 'root', modified: '2026-07-12T14:10:00', owner: 'Ava Lee',      starred: false, size: 4600000 },
      { id: 'f-011', name: 'Vendor_Contracts.zip',        type: 'zip',  parent: 'root', modified: '2026-07-11T12:30:00', owner: 'Legal Team',   starred: false, size: 24000000 },
      { id: 'f-012', name: 'Meeting_Notes_2026-07.txt',   type: 'txt',  parent: 'root', modified: '2026-07-10T15:00:00', owner: 'Alex Kim',     starred: false, size: 8400 },
      { id: 'f-013', name: 'API_Documentation.pdf',       type: 'pdf',  parent: 'root', modified: '2026-07-09T11:50:00', owner: 'Ryan Green',   starred: false, size: 3400000 },
      { id: 'f-014', name: 'Analytics_Report_Q2.pdf',     type: 'pdf',  parent: 'root', modified: '2026-07-08T17:00:00', owner: 'James Doe',    starred: false, size: 1800000 },
      { id: 'f-015', name: 'Team_Photo_2026.png',         type: 'png',  parent: 'root', modified: '2026-07-05T09:30:00', owner: 'Alex Kim',     starred: false, size: 4200000 },
      { id: 'f-016', name: 'Logo_Assets.zip',             type: 'zip',  parent: 'root', modified: '2026-07-04T13:15:00', owner: 'Ava Lee',      starred: true,  size: 18000000 },
      { id: 'f-017', name: 'Employee_Handbook_2026.pdf',  type: 'pdf',  parent: 'root', modified: '2026-07-03T10:00:00', owner: 'HR',           starred: false, size: 2800000 },
      { id: 'f-018', name: 'Server_Migration_Plan.docx',  type: 'docx', parent: 'root', modified: '2026-07-02T14:45:00', owner: 'Ryan Green',   starred: false, size: 460000 },
      { id: 'f-019', name: 'app-config.json',             type: 'json', parent: 'root', modified: '2026-07-01T09:22:00', owner: 'Ryan Green',   starred: false, size: 12400 },
      { id: 'f-020', name: 'Voiceover_Intro.mp3',         type: 'mp3',  parent: 'root', modified: '2026-06-30T16:30:00', owner: 'Sarah Miller', starred: false, size: 3400000 }
    ],
    treeFolders: [
      { id: 'fld-projects', name: 'Projects',   icon: 'bi-folder-fill',   color: 'warning' },
      { id: 'fld-designs',  name: 'Designs',    icon: 'bi-folder-fill',   color: 'info' },
      { id: 'fld-docs',     name: 'Documents',  icon: 'bi-folder-fill',   color: 'primary' },
      { id: 'fld-aurora',   name: 'Aurora',     icon: 'bi-folder-fill',   color: 'success' },
      { id: 'fld-team',     name: 'Team Shared',icon: 'bi-folder-fill',   color: 'danger' },
      { id: 'fld-archive',  name: 'Archive 2025',icon: 'bi-folder-fill',  color: 'secondary' }
    ],

    // Documents
    documents: [
      { id: 'doc-01', title: 'Employee Handbook 2026',    type: 'pdf',  category: 'Policies',   version: 'v4.2', owner: 'HR Team',      updated: '2026-07-20', size: 2800000, status: 'approved', tags: ['handbook','hr','policy'] },
      { id: 'doc-02', title: 'Data Privacy & GDPR Policy',type: 'pdf',  category: 'Legal',      version: 'v2.1', owner: 'Legal Team',   updated: '2026-07-15', size: 1200000, status: 'approved', tags: ['gdpr','privacy','compliance'] },
      { id: 'doc-03', title: 'MSA Master Template',       type: 'doc',  category: 'Templates',  version: 'v3.0', owner: 'Legal Team',   updated: '2026-07-10', size: 340000,  status: 'approved', tags: ['contract','template','legal'] },
      { id: 'doc-04', title: 'Vendor Onboarding SOP',     type: 'pdf',  category: 'SOPs',       version: 'v1.4', owner: 'Ops Team',     updated: '2026-07-08', size: 780000,  status: 'pending',  tags: ['sop','vendor','onboarding'] },
      { id: 'doc-05', title: 'SOC 2 Type II Report',      type: 'pdf',  category: 'Reports',    version: 'v1.0', owner: 'Security',     updated: '2026-06-30', size: 5600000, status: 'approved', tags: ['security','audit','compliance'] },
      { id: 'doc-06', title: 'Q2 2026 Financial Report',  type: 'xls',  category: 'Reports',    version: 'v1.2', owner: 'Finance',      updated: '2026-07-05', size: 890000,  status: 'approved', tags: ['finance','quarterly','report'] },
      { id: 'doc-07', title: 'Employee NDA Template',     type: 'doc',  category: 'Templates',  version: 'v2.5', owner: 'Legal Team',   updated: '2026-06-20', size: 180000,  status: 'approved', tags: ['nda','contract','template'] },
      { id: 'doc-08', title: 'Remote Work Policy',        type: 'pdf',  category: 'Policies',   version: 'v3.1', owner: 'HR Team',      updated: '2026-05-14', size: 620000,  status: 'expired',  tags: ['remote','hr','policy'] },
      { id: 'doc-09', title: 'Incident Response Playbook',type: 'pdf',  category: 'SOPs',       version: 'v2.0', owner: 'Security',     updated: '2026-07-01', size: 1400000, status: 'approved', tags: ['security','incident','sop'] },
      { id: 'doc-10', title: 'New Hire Welcome Guide',    type: 'pdf',  category: 'Onboarding', version: 'v1.6', owner: 'HR Team',      updated: '2026-07-18', size: 2100000, status: 'approved', tags: ['onboarding','hr','guide'] },
      { id: 'doc-11', title: 'Brand Style Guide 2026',    type: 'pdf',  category: 'Guides',     version: 'v5.0', owner: 'Design',       updated: '2026-07-12', size: 8400000, status: 'approved', tags: ['brand','design','guide'] },
      { id: 'doc-12', title: 'Product Requirements — Aurora',type: 'doc',category: 'Guides',    version: 'v2.3', owner: 'Product',      updated: '2026-07-17', size: 540000,  status: 'pending',  tags: ['product','aurora','prd'] },
      { id: 'doc-13', title: 'Expense Reimbursement Policy',type: 'pdf',category: 'Policies',   version: 'v2.0', owner: 'Finance',      updated: '2026-06-25', size: 320000,  status: 'approved', tags: ['finance','policy','expense'] },
      { id: 'doc-14', title: 'Customer MSA — Nova Corp',  type: 'pdf',  category: 'Contracts',  version: 'v1.0', owner: 'Legal Team',   updated: '2026-07-14', size: 980000,  status: 'approved', tags: ['contract','customer','msa'] },
      { id: 'doc-15', title: 'Customer MSA — Peak Digital',type: 'pdf', category: 'Contracts',  version: 'v1.1', owner: 'Legal Team',   updated: '2026-07-09', size: 1020000, status: 'pending',  tags: ['contract','customer','msa'] },
      { id: 'doc-16', title: 'API Documentation v3',      type: 'doc',  category: 'Guides',     version: 'v3.0', owner: 'Engineering',  updated: '2026-07-19', size: 720000,  status: 'approved', tags: ['api','engineering','docs'] },
      { id: 'doc-17', title: 'Q1 Board Deck',             type: 'ppt',  category: 'Reports',    version: 'v1.0', owner: 'Executive',    updated: '2026-04-10', size: 24000000,status: 'expired',  tags: ['board','deck','quarterly'] },
      { id: 'doc-18', title: 'Code of Conduct',           type: 'pdf',  category: 'Policies',   version: 'v4.0', owner: 'HR Team',      updated: '2026-06-01', size: 240000,  status: 'approved', tags: ['policy','conduct','hr'] },
      { id: 'doc-19', title: 'Vendor Contract — AWS',     type: 'pdf',  category: 'Contracts',  version: 'v2.2', owner: 'Legal Team',   updated: '2026-06-15', size: 1600000, status: 'approved', tags: ['vendor','aws','contract'] },
      { id: 'doc-20', title: 'Manager Playbook',          type: 'pdf',  category: 'Guides',     version: 'v2.4', owner: 'HR Team',      updated: '2026-07-06', size: 1980000, status: 'pending',  tags: ['management','guide','hr'] }
    ],

    docCategories: [
      { name: 'All',        color: 'primary'   },
      { name: 'Contracts',  color: 'success'   },
      { name: 'Policies',   color: 'primary'   },
      { name: 'SOPs',       color: 'info'      },
      { name: 'Templates',  color: 'warning'   },
      { name: 'Guides',     color: 'purple'    },
      { name: 'Reports',    color: 'danger'    },
      { name: 'Legal',      color: 'dark'      },
      { name: 'Onboarding', color: 'success'   }
    ],

    docTags: ['hr','legal','policy','contract','template','sop','security','compliance','finance','onboarding','design','api','engineering','product','quarterly','audit','vendor','customer'],

    // Timeline events for Project Aurora Jan-Jul 2026
    events: [
      { id: 'ev-01', date: '2026-01-08T10:00:00', type: 'milestone',    title: 'Project Aurora Kickoff',                actor: 'Alex Kim',       description: 'Official launch of Project Aurora. Cross-functional team assembled with 12 members across product, design, engineering.' },
      { id: 'ev-02', date: '2026-01-15T14:30:00', type: 'meeting',      title: 'Discovery Workshop with Stakeholders',  actor: 'Sarah Miller',   description: 'Full-day workshop to align on vision, objectives, and success metrics.' },
      { id: 'ev-03', date: '2026-01-28T09:15:00', type: 'announcement', title: 'Aurora Roadmap Published',              actor: 'Alex Kim',       description: 'The six-month roadmap has been shared with all stakeholders.' },
      { id: 'ev-04', date: '2026-02-05T11:00:00', type: 'team',         title: 'Two Engineers Joined the Team',         actor: 'HR',             description: 'Ryan Green (Senior Backend) and Ava Lee (Design Lead) onboarded.' },
      { id: 'ev-05', date: '2026-02-14T16:00:00', type: 'milestone',    title: 'Design Sprint Complete',                actor: 'Ava Lee',        description: 'Wireframes and design system foundations complete. Design system v0.1 shipped internally.' },
      { id: 'ev-06', date: '2026-02-22T10:45:00', type: 'feature',      title: 'Auth Module Merged',                    actor: 'Ryan Green',     description: 'OAuth 2.0, MFA, and SSO support merged into main.' },
      { id: 'ev-07', date: '2026-03-01T12:00:00', type: 'deployment',   title: 'Internal Dev Environment Live',         actor: 'DevOps',         description: 'Aurora deployed to internal dev environment on Kubernetes cluster.' },
      { id: 'ev-08', date: '2026-03-15T09:30:00', type: 'milestone',    title: 'MVP Feature Freeze',                    actor: 'Alex Kim',       description: 'All planned MVP features are in — moving to hardening and QA.' },
      { id: 'ev-09', date: '2026-03-22T13:20:00', type: 'bug',          title: 'Fixed Race Condition in Sync Engine',   actor: 'Ryan Green',     description: 'Long-standing race condition in offline sync resolved (Issue #482).' },
      { id: 'ev-10', date: '2026-04-05T15:00:00', type: 'deployment',   title: 'Alpha Release to Internal Users',       actor: 'DevOps',         description: '50 internal users invited to alpha. Feedback loop opened via #aurora-alpha.' },
      { id: 'ev-11', date: '2026-04-18T11:15:00', type: 'incident',     title: 'Database Outage — 42 minutes',          actor: 'DevOps',         description: 'Primary DB became unresponsive due to lock contention. Postmortem published.' },
      { id: 'ev-12', date: '2026-04-30T10:00:00', type: 'announcement', title: 'Aurora Featured in Company All-Hands',  actor: 'Executive',      description: 'Early demo shown to the entire company at monthly all-hands.' },
      { id: 'ev-13', date: '2026-05-10T14:00:00', type: 'feature',      title: 'Real-time Collaboration Shipped',       actor: 'Ryan Green',     description: 'CRDT-based real-time collaboration merged, tested with 100 concurrent editors.' },
      { id: 'ev-14', date: '2026-05-22T09:00:00', type: 'meeting',      title: 'External Design Partner Program Kickoff',actor: 'Sarah Miller',  description: 'Five external design partners onboarded for private beta feedback.' },
      { id: 'ev-15', date: '2026-06-05T16:30:00', type: 'milestone',    title: 'Private Beta Launched',                 actor: 'Alex Kim',       description: 'Aurora private beta opened to 500 external users. Analytics show 78% D7 retention.' },
      { id: 'ev-16', date: '2026-06-18T11:45:00', type: 'bug',          title: 'Fixed Critical Auth Redirect Loop',     actor: 'Ryan Green',     description: 'SSO callback loop resolved for enterprise IdPs.' },
      { id: 'ev-17', date: '2026-06-28T10:15:00', type: 'team',         title: 'Ops Engineer Joined',                   actor: 'HR',             description: 'James Doe (Platform Ops) joined to strengthen infrastructure.' },
      { id: 'ev-18', date: '2026-07-05T09:00:00', type: 'deployment',   title: 'Aurora v1.0 Beta',                      actor: 'DevOps',         description: 'Public beta deployed globally across 4 regions.' },
      { id: 'ev-19', date: '2026-07-14T15:30:00', type: 'announcement', title: 'Press Release: Aurora Public Beta',     actor: 'Marketing',      description: 'Coverage in TechCrunch, The Verge, and Ars Technica.' },
      { id: 'ev-20', date: '2026-07-22T13:00:00', type: 'milestone',    title: 'GA Countdown Begins',                   actor: 'Alex Kim',       description: 'Aurora General Availability scheduled for August 15, 2026. All-hands GA readiness review passed.' }
    ],

    eventTypes: [
      { key: 'milestone',    label: 'Milestone',    icon: 'bi-star-fill' },
      { key: 'deployment',   label: 'Deployment',   icon: 'bi-rocket-takeoff' },
      { key: 'feature',      label: 'Feature',      icon: 'bi-stars' },
      { key: 'bug',          label: 'Bug Fix',      icon: 'bi-bug' },
      { key: 'team',         label: 'Team',         icon: 'bi-people-fill' },
      { key: 'meeting',      label: 'Meeting',      icon: 'bi-camera-video-fill' },
      { key: 'announcement', label: 'Announcement', icon: 'bi-megaphone-fill' },
      { key: 'incident',     label: 'Incident',     icon: 'bi-exclamation-triangle-fill' }
    ]
  };

  // Build lookup
  Extras.data.files.forEach(function (f) { Extras.data.filesById[f.id] = f; });

  // =========================================================
  // FILE MANAGER controller
  // =========================================================
  function initFileManager() {
    var root = document.querySelector('[data-extras-page="file-manager"]');
    if (!root) return;

    var state = {
      view: 'grid',
      currentFolder: 'root',
      folderName: 'My Drive',
      selectedIds: [],
      dragId: null,
      search: '',
      filter: 'all'
    };

    var treeEl        = root.querySelector('[data-fm-tree]');
    var browserEl     = root.querySelector('[data-fm-browser]');
    var pathbarEl     = root.querySelector('[data-fm-pathbar]');
    var previewEl     = root.querySelector('[data-fm-preview]');
    var bulkbarEl     = root.querySelector('[data-fm-bulkbar]');
    var bulkCountEl   = root.querySelector('[data-fm-bulk-count]');
    var recentStripEl = root.querySelector('[data-fm-recent]');
    var uploadListEl  = root.querySelector('[data-fm-uploads]');
    var storageFillEl = root.querySelector('[data-fm-storage-fill]');
    var storageMetaEl = root.querySelector('[data-fm-storage-meta]');
    var contextMenu   = root.querySelector('[data-fm-context-menu]');
    var searchInput   = root.querySelector('[data-fm-search]');

    var CAPACITY = 15 * 1024 * 1024 * 1024; // 15 GB

    function itemsForCurrentFolder() {
      var list = Extras.data.files.filter(function (f) {
        // treat 'root' virtually; when a folder is selected show its "children" — since we don't nest deeply, we show items belonging to that folder OR (for demo) items with matching parent id, else default to root
        if (state.currentFolder === 'root' || state.currentFolder === 'my-drive') return f.parent === 'root';
        if (state.currentFolder === 'starred')  return f.starred;
        if (state.currentFolder === 'recent')   return true; // all
        if (state.currentFolder === 'shared')   return f.owner !== 'Alex Kim';
        if (state.currentFolder === 'trash')    return false;
        // custom folder — show entries whose parent equals this id (for demo we show sample slice)
        return f.parent === state.currentFolder;
      });
      if (state.search) {
        var q = state.search.toLowerCase();
        list = list.filter(function (f) { return f.name.toLowerCase().indexOf(q) !== -1; });
      }
      // Folders first
      list.sort(function (a, b) {
        if (a.type === 'folder' && b.type !== 'folder') return -1;
        if (b.type === 'folder' && a.type !== 'folder') return 1;
        return new Date(b.modified) - new Date(a.modified);
      });
      return list;
    }

    function renderStorage() {
      var used = 0;
      Extras.data.files.forEach(function (f) { used += (f.size || 0); });
      // add a base offset so bar looks natural
      used += 4.1 * 1024 * 1024 * 1024;
      var pct = Math.min(100, Math.round((used / CAPACITY) * 100));
      storageFillEl.style.width = pct + '%';
      storageMetaEl.innerHTML =
        '<span>' + Extras.formatBytes(used) + ' of ' + Extras.formatBytes(CAPACITY) + '</span>' +
        '<span>' + pct + '%</span>';
    }

    function renderTree() {
      var systemHtml =
        '<span class="fm-tree__section-label">Locations</span>' +
        renderTreeRow('my-drive', 'My Drive',       'bi-hdd', Extras.data.files.filter(function(f){ return f.parent==='root'; }).length) +
        renderTreeRow('shared',   'Shared with me', 'bi-people', 6) +
        renderTreeRow('starred',  'Starred',        'bi-star-fill', Extras.data.files.filter(function(f){ return f.starred; }).length) +
        renderTreeRow('recent',   'Recent',         'bi-clock-history', 12) +
        renderTreeRow('trash',    'Trash',          'bi-trash', 3);

      var foldersHtml = '<span class="fm-tree__section-label">Folders</span>';
      Extras.data.treeFolders.forEach(function (f) {
        foldersHtml += renderTreeRow(f.id, f.name, f.icon, f.itemCount || Extras.data.filesById[f.id]?.itemCount || '');
      });

      treeEl.innerHTML = systemHtml + foldersHtml;
      wireTreeEvents();
    }

    function renderTreeRow(id, name, icon, count) {
      var active = state.currentFolder === id ? ' is-active' : '';
      return '<li class="fm-tree__item' + active + '" data-fm-tree-id="' + id + '" data-fm-drop-target="' + id + '">' +
             '<i class="bi ' + icon + '"></i>' +
             '<span>' + Extras.escapeHtml(name) + '</span>' +
             (count ? '<span class="fm-tree__count">' + count + '</span>' : '') +
             '</li>';
    }

    function wireTreeEvents() {
      Array.prototype.forEach.call(treeEl.querySelectorAll('[data-fm-tree-id]'), function (li) {
        li.addEventListener('click', function () {
          state.currentFolder = this.getAttribute('data-fm-tree-id');
          var label = this.querySelector('span').textContent;
          state.folderName = label;
          state.selectedIds = [];
          renderTree();
          renderPathbar();
          renderBrowser();
          renderPreview();
          renderBulkbar();
        });
        wireDropTarget(li, li.getAttribute('data-fm-drop-target'));
      });
    }

    function renderPathbar() {
      pathbarEl.innerHTML =
        '<a class="fm-pathbar__crumb" data-fm-nav="my-drive"><i class="bi bi-hdd me-1"></i>My Drive</a>' +
        (state.currentFolder !== 'my-drive' && state.currentFolder !== 'root'
          ? '<i class="bi bi-chevron-right fm-pathbar__sep"></i>' +
            '<span class="fm-pathbar__crumb is-current">' + Extras.escapeHtml(state.folderName) + '</span>'
          : '');
      Array.prototype.forEach.call(pathbarEl.querySelectorAll('[data-fm-nav]'), function (a) {
        a.addEventListener('click', function () {
          state.currentFolder = this.getAttribute('data-fm-nav');
          state.folderName = 'My Drive';
          renderTree(); renderPathbar(); renderBrowser(); renderPreview();
        });
      });
    }

    function renderRecent() {
      var recent = Extras.data.files.filter(function (f) { return f.type !== 'folder'; })
        .slice().sort(function (a, b) { return new Date(b.modified) - new Date(a.modified); })
        .slice(0, 6);
      var html = '';
      recent.forEach(function (f) {
        html += '<div class="fm-recent__chip" data-fm-open="' + f.id + '">' +
                Extras.renderFileIcon(f.type) +
                '<div style="min-width:0;">' +
                '<div style="font-size:.78rem;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:120px;">' + Extras.escapeHtml(f.name) + '</div>' +
                '<small>' + Extras.relativeTime(f.modified) + '</small>' +
                '</div></div>';
      });
      recentStripEl.innerHTML = html;
      Array.prototype.forEach.call(recentStripEl.querySelectorAll('[data-fm-open]'), function (el) {
        el.addEventListener('click', function () {
          selectItem(this.getAttribute('data-fm-open'));
        });
      });
    }

    function renderBrowser() {
      var items = itemsForCurrentFolder();
      if (items.length === 0) {
        browserEl.innerHTML =
          '<div class="extras-empty">' +
          '<div class="extras-empty__icon"><i class="bi bi-inbox"></i></div>' +
          '<h6>No files here yet</h6>' +
          '<p class="mb-0">Try uploading a file, or select a different folder.</p>' +
          '</div>';
        return;
      }
      if (state.view === 'grid')    return renderGrid(items);
      if (state.view === 'list')    return renderList(items);
      if (state.view === 'details') return renderDetailsView(items);
    }

    function renderGrid(items) {
      var html = '<div class="fm-grid">';
      items.forEach(function (f) {
        var sel = state.selectedIds.indexOf(f.id) !== -1 ? ' is-selected' : '';
        html +=
          '<div class="fm-tile' + sel + '" data-fm-item="' + f.id + '" draggable="true">' +
            (f.starred ? '<i class="bi bi-star-fill fm-tile__star"></i>' : '') +
            (f.type === 'folder'
              ? '<span class="fm-fileicon fm-tile__icon fm-fileicon--folder"><i class="bi bi-folder-fill"></i></span>'
              : Extras.renderFileIcon(f.type, { className: 'fm-tile__icon' })) +
            '<div class="fm-tile__name">' + Extras.escapeHtml(f.name) + '</div>' +
            '<div class="fm-tile__meta">' +
              (f.type === 'folder' ? (f.itemCount || 0) + ' items' : Extras.formatBytes(f.size)) +
              ' · ' + Extras.relativeTime(f.modified) +
            '</div>' +
          '</div>';
      });
      html += '</div>';
      browserEl.innerHTML = html;
      wireItemEvents();
    }

    function renderList(items) {
      var html =
        '<div class="table-responsive">' +
        '<table class="fm-list">' +
        '<thead><tr>' +
          '<th>Name</th><th>Owner</th><th>Modified</th><th>Size</th><th class="text-end">Actions</th>' +
        '</tr></thead><tbody>';
      items.forEach(function (f) {
        var sel = state.selectedIds.indexOf(f.id) !== -1 ? ' is-selected' : '';
        html +=
          '<tr class="' + sel + '" data-fm-item="' + f.id + '" draggable="true">' +
            '<td><div class="fm-list__namecell">' +
              (f.type === 'folder'
                ? '<span class="fm-fileicon fm-fileicon--folder"><i class="bi bi-folder-fill"></i></span>'
                : Extras.renderFileIcon(f.type)) +
              '<span>' + Extras.escapeHtml(f.name) + '</span>' +
              (f.starred ? ' <i class="bi bi-star-fill text-warning ms-1"></i>' : '') +
            '</div></td>' +
            '<td>' + Extras.escapeHtml(f.owner) + '</td>' +
            '<td>' + Extras.fmtDate(f.modified) + '</td>' +
            '<td>' + (f.type === 'folder' ? '—' : Extras.formatBytes(f.size)) + '</td>' +
            '<td class="text-end"><button class="btn btn-sm btn-icon" type="button" data-fm-item-menu="' + f.id + '" aria-label="More"><i class="bi bi-three-dots-vertical"></i></button></td>' +
          '</tr>';
      });
      html += '</tbody></table></div>';
      browserEl.innerHTML = html;
      wireItemEvents();
    }

    function renderDetailsView(items) {
      var html = '<div class="fm-details">';
      html += '<div class="fm-details__pane">';
      items.slice(0, 12).forEach(function (f) {
        var sel = state.selectedIds.indexOf(f.id) !== -1 ? ' is-selected' : '';
        html += '<div class="fm-list__namecell p-2 rounded' + (sel ? ' bg-body-tertiary' : '') + '" data-fm-item="' + f.id + '" style="cursor:pointer;margin-bottom:.15rem;" draggable="true">' +
                (f.type === 'folder' ? '<span class="fm-fileicon fm-fileicon--folder"><i class="bi bi-folder-fill"></i></span>' : Extras.renderFileIcon(f.type)) +
                '<div><div>' + Extras.escapeHtml(f.name) + '</div><small class="text-body-secondary">' + Extras.relativeTime(f.modified) + '</small></div></div>';
      });
      html += '</div>';
      var pv = state.selectedIds[0] ? Extras.data.filesById[state.selectedIds[0]] : items[0];
      html += '<div class="fm-details__pane fm-details__preview">';
      if (pv) {
        html += '<div class="text-center"><div class="mx-auto mb-2">' +
                (pv.type === 'folder' ? '<span class="fm-fileicon fm-fileicon--folder" style="width:80px;height:80px;font-size:1.6rem;border-radius:.75rem;"><i class="bi bi-folder-fill"></i></span>' : Extras.renderFileIcon(pv.type, { className: 'fm-fileicon' }).replace('fm-fileicon"', 'fm-fileicon" style="width:80px;height:80px;font-size:1.6rem;border-radius:.75rem;"')) +
                '</div><div class="fw-semibold">' + Extras.escapeHtml(pv.name) + '</div><small class="d-block mt-1">' + Extras.formatBytes(pv.size) + ' · Modified ' + Extras.fmtDate(pv.modified) + '</small></div>';
      }
      html += '</div>';
      html += '</div>';
      browserEl.innerHTML = html;
      wireItemEvents();
    }

    function wireItemEvents() {
      Array.prototype.forEach.call(browserEl.querySelectorAll('[data-fm-item]'), function (el) {
        var id = el.getAttribute('data-fm-item');
        el.addEventListener('click', function (e) {
          e.stopPropagation();
          if (e.ctrlKey || e.metaKey) {
            toggleSelect(id);
          } else {
            state.selectedIds = [id];
          }
          renderBulkbar();
          renderPreview();
          // update selection visuals
          Array.prototype.forEach.call(browserEl.querySelectorAll('[data-fm-item]'), function (n) {
            n.classList.toggle('is-selected', state.selectedIds.indexOf(n.getAttribute('data-fm-item')) !== -1);
          });
        });
        el.addEventListener('dblclick', function () {
          var f = Extras.data.filesById[id];
          if (f && f.type === 'folder') {
            state.currentFolder = f.id;
            state.folderName = f.name;
            state.selectedIds = [];
            renderTree(); renderPathbar(); renderBrowser(); renderPreview(); renderBulkbar();
          } else {
            Extras.orchidToast('Opening “' + f.name + '” preview…', 'primary');
          }
        });
        el.addEventListener('contextmenu', function (e) {
          e.preventDefault();
          state.selectedIds = [id];
          renderPreview();
          openContextMenu(e.clientX, e.clientY, id);
        });

        // drag n drop
        el.addEventListener('dragstart', function (e) {
          state.dragId = id;
          el.classList.add('is-dragging');
          e.dataTransfer.effectAllowed = 'move';
          try { e.dataTransfer.setData('text/plain', id); } catch (_) {}
        });
        el.addEventListener('dragend', function () {
          el.classList.remove('is-dragging');
          state.dragId = null;
          Array.prototype.forEach.call(browserEl.querySelectorAll('.is-drop-target'), function (n) { n.classList.remove('is-drop-target'); });
        });

        // items that are folders act as drop targets too
        var f = Extras.data.filesById[id];
        if (f && f.type === 'folder') {
          wireDropTarget(el, id);
        }
      });
    }

    function wireDropTarget(el, targetId) {
      el.addEventListener('dragover', function (e) {
        if (!state.dragId || state.dragId === targetId) return;
        e.preventDefault();
        el.classList.add('is-drop-target');
      });
      el.addEventListener('dragleave', function () {
        el.classList.remove('is-drop-target');
      });
      el.addEventListener('drop', function (e) {
        e.preventDefault();
        el.classList.remove('is-drop-target');
        if (!state.dragId || state.dragId === targetId) return;
        var moved = Extras.data.filesById[state.dragId];
        if (moved) {
          moved.parent = targetId === 'my-drive' ? 'root' : targetId;
          Extras.orchidToast('Moved “' + moved.name + '” to ' + (Extras.data.filesById[targetId]?.name || targetId), 'success');
          renderBrowser();
          renderTree();
        }
      });
    }

    function toggleSelect(id) {
      var idx = state.selectedIds.indexOf(id);
      if (idx === -1) state.selectedIds.push(id);
      else state.selectedIds.splice(idx, 1);
    }

    function renderBulkbar() {
      if (state.selectedIds.length > 1) {
        bulkbarEl.classList.add('is-active');
        bulkCountEl.textContent = state.selectedIds.length;
      } else {
        bulkbarEl.classList.remove('is-active');
      }
    }

    function renderPreview() {
      if (state.selectedIds.length === 0) {
        previewEl.innerHTML =
          '<div class="fm-preview__empty">' +
          '<i class="bi bi-file-earmark"></i>' +
          '<p class="mb-0 small">Select a file or folder to preview it here.</p>' +
          '</div>';
        return;
      }
      var f = Extras.data.filesById[state.selectedIds[0]];
      if (!f) return;

      previewEl.innerHTML =
        '<div class="fm-preview__body">' +
          (f.type === 'folder'
            ? '<span class="fm-fileicon fm-fileicon--folder fm-preview__icon"><i class="bi bi-folder-fill"></i></span>'
            : '<span class="fm-fileicon ' + Extras.fileTypeInfo(f.type).cls + ' fm-preview__icon">' + Extras.fileTypeInfo(f.type).label.slice(0,3) + '</span>') +
          '<div class="fm-preview__name">' + Extras.escapeHtml(f.name) + '</div>' +
          '<div class="fm-preview__meta">' + (f.type === 'folder' ? (f.itemCount || 0) + ' items' : Extras.formatBytes(f.size)) + ' · Modified ' + Extras.relativeTime(f.modified) + '</div>' +
          '<div class="fm-preview__actions">' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-fm-action="preview"><i class="bi bi-eye"></i>Preview</button>' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-fm-action="download"><i class="bi bi-download"></i>Download</button>' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-fm-action="share"><i class="bi bi-share"></i>Share</button>' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-fm-action="rename"><i class="bi bi-pencil"></i>Rename</button>' +
            '<button class="btn btn-sm btn-outline-secondary" type="button" data-fm-action="move"><i class="bi bi-folder-symlink"></i>Move</button>' +
            '<button class="btn btn-sm btn-outline-danger" type="button" data-fm-action="delete"><i class="bi bi-trash"></i>Delete</button>' +
          '</div>' +
          '<div class="fm-preview__details">' +
            '<dl>' +
              '<dt>Type</dt><dd>' + Extras.escapeHtml(f.type.toUpperCase()) + '</dd>' +
              '<dt>Owner</dt><dd>' + Extras.escapeHtml(f.owner) + '</dd>' +
              '<dt>Modified</dt><dd>' + Extras.fmtDate(f.modified) + ' at ' + Extras.fmtTime(f.modified) + '</dd>' +
              '<dt>Size</dt><dd>' + (f.type === 'folder' ? '—' : Extras.formatBytes(f.size)) + '</dd>' +
              '<dt>Path</dt><dd>My Drive / ' + Extras.escapeHtml(state.folderName) + '</dd>' +
              (f.starred ? '<dt>Starred</dt><dd><i class="bi bi-star-fill text-warning"></i> Yes</dd>' : '') +
            '</dl>' +
          '</div>' +
        '</div>';

      Array.prototype.forEach.call(previewEl.querySelectorAll('[data-fm-action]'), function (btn) {
        btn.addEventListener('click', function () {
          runAction(this.getAttribute('data-fm-action'), state.selectedIds[0]);
        });
      });
    }

    function selectItem(id) {
      state.selectedIds = [id];
      renderPreview();
      renderBulkbar();
      Array.prototype.forEach.call(browserEl.querySelectorAll('[data-fm-item]'), function (n) {
        n.classList.toggle('is-selected', n.getAttribute('data-fm-item') === id);
      });
    }

    function runAction(action, id) {
      var f = Extras.data.filesById[id];
      if (!f) return;
      if (action === 'delete') {
        var idx = Extras.data.files.indexOf(f);
        if (idx !== -1) Extras.data.files.splice(idx, 1);
        delete Extras.data.filesById[id];
        state.selectedIds = [];
        renderBrowser(); renderPreview(); renderBulkbar(); renderStorage(); renderRecent();
        Extras.orchidToast('Deleted “' + f.name + '”', 'danger');
      } else if (action === 'rename') {
        var newName = window.prompt('Rename to:', f.name);
        if (newName && newName.trim()) {
          f.name = newName.trim();
          renderBrowser(); renderPreview();
          Extras.orchidToast('Renamed', 'success');
        }
      } else if (action === 'preview') {
        Extras.orchidToast('Preview: ' + f.name, 'primary');
      } else if (action === 'download') {
        Extras.orchidToast('Downloading ' + f.name + '…', 'success');
      } else if (action === 'share') {
        Extras.orchidToast('Share link copied to clipboard', 'success');
      } else if (action === 'move') {
        Extras.orchidToast('Drag file to a folder to move it', 'primary');
      } else if (action === 'star') {
        f.starred = !f.starred;
        renderBrowser(); renderPreview();
        Extras.orchidToast(f.starred ? 'Starred' : 'Unstarred', 'primary');
      }
    }

    // Context menu
    function openContextMenu(x, y, id) {
      contextMenu.setAttribute('data-fm-target', id);
      contextMenu.style.left = Math.min(x, window.innerWidth - 240) + 'px';
      contextMenu.style.top  = Math.min(y, window.innerHeight - 320) + 'px';
      contextMenu.classList.add('is-open');
    }
    function closeContextMenu() { contextMenu.classList.remove('is-open'); }
    document.addEventListener('click', closeContextMenu);
    document.addEventListener('scroll', closeContextMenu, true);

    Array.prototype.forEach.call(contextMenu.querySelectorAll('[data-fm-cm-action]'), function (btn) {
      btn.addEventListener('click', function () {
        runAction(this.getAttribute('data-fm-cm-action'), contextMenu.getAttribute('data-fm-target'));
      });
    });

    // View toggle
    Array.prototype.forEach.call(root.querySelectorAll('[data-fm-view]'), function (btn) {
      btn.addEventListener('change', function () {
        if (this.checked) {
          state.view = this.value;
          renderBrowser();
        }
      });
    });

    // Search
    searchInput.addEventListener('input', function () {
      state.search = this.value.trim();
      renderBrowser();
    });

    // Bulk actions
    root.querySelector('[data-fm-bulk-share]').addEventListener('click', function () {
      Extras.orchidToast(state.selectedIds.length + ' items shared', 'success');
    });
    root.querySelector('[data-fm-bulk-download]').addEventListener('click', function () {
      Extras.orchidToast('Packaging ' + state.selectedIds.length + ' items as ZIP…', 'primary');
    });
    root.querySelector('[data-fm-bulk-delete]').addEventListener('click', function () {
      state.selectedIds.slice().forEach(function (id) { runAction('delete', id); });
    });
    root.querySelector('[data-fm-bulk-clear]').addEventListener('click', function () {
      state.selectedIds = []; renderBrowser(); renderPreview(); renderBulkbar();
    });

    // New / Upload
    Array.prototype.forEach.call(root.querySelectorAll('[data-fm-new]'), function (btn) {
      btn.addEventListener('click', function () {
        var kind = this.getAttribute('data-fm-new');
        if (kind === 'folder') {
          var name = window.prompt('New folder name:', 'New Folder');
          if (name && name.trim()) {
            var id = 'fld-' + Date.now();
            var newFolder = { id: id, name: name.trim(), type: 'folder', parent: state.currentFolder === 'my-drive' ? 'root' : state.currentFolder, modified: new Date().toISOString(), owner: 'Alex Kim', starred: false, size: null, itemCount: 0 };
            Extras.data.files.unshift(newFolder);
            Extras.data.filesById[id] = newFolder;
            renderTree(); renderBrowser();
            Extras.orchidToast('Folder “' + newFolder.name + '” created', 'success');
          }
        } else if (kind === 'upload') {
          simulateUpload();
        } else {
          Extras.orchidToast(this.textContent.trim() + ' — coming soon', 'primary');
        }
      });
    });

    function simulateUpload() {
      var names = ['UI_Concepts.fig', 'Meeting_Recording.mp4', 'Sprint_Notes.pdf'];
      names.forEach(function (n, i) {
        setTimeout(function () {
          var card = document.createElement('div');
          card.className = 'fm-upload-card';
          card.innerHTML =
            '<i class="bi bi-cloud-upload text-primary"></i>' +
            '<div class="fm-upload-card__meta">' +
              '<div class="fm-upload-card__name">' + Extras.escapeHtml(n) + '</div>' +
              '<div class="fm-upload-card__bar"><div class="fm-upload-card__fill"></div></div>' +
            '</div>';
          uploadListEl.appendChild(card);
          var fill = card.querySelector('.fm-upload-card__fill');
          var pct = 0;
          var timer = setInterval(function () {
            pct += Math.random() * 22;
            if (pct >= 100) {
              pct = 100;
              clearInterval(timer);
              setTimeout(function () {
                card.remove();
                var ext = n.split('.').pop();
                var id = 'up-' + Date.now();
                var f = { id: id, name: n, type: ext, parent: 'root', modified: new Date().toISOString(), owner: 'Alex Kim', starred: false, size: Math.floor(Math.random() * 4000000) + 200000 };
                Extras.data.files.unshift(f);
                Extras.data.filesById[id] = f;
                renderBrowser(); renderStorage(); renderRecent();
                Extras.orchidToast('“' + n + '” uploaded', 'success');
              }, 400);
            }
            fill.style.width = pct + '%';
          }, 220);
        }, i * 500);
      });
    }

    // initial render
    renderTree();
    renderPathbar();
    renderRecent();
    renderStorage();
    renderBrowser();
    renderPreview();
  }

  // =========================================================
  // DOCUMENTS controller
  // =========================================================
  function initDocuments() {
    var root = document.querySelector('[data-extras-page="documents"]');
    if (!root) return;

    var state = {
      category: 'All',
      tag: null,
      status: 'all',
      search: '',
      view: 'grid',
      selectedIds: []
    };

    var catListEl   = root.querySelector('[data-docs-categories]');
    var tagCloudEl  = root.querySelector('[data-docs-tags]');
    var statusListEl= root.querySelector('[data-docs-statuses]');
    var gridEl      = root.querySelector('[data-docs-grid]');
    var countEl     = root.querySelector('[data-docs-count]');
    var searchInput = root.querySelector('[data-docs-search]');
    var bulkbarEl   = root.querySelector('[data-docs-bulkbar]');
    var bulkCountEl = root.querySelector('[data-docs-bulk-count]');
    var versionsList= document.querySelector('[data-docs-versions]');
    var sortSelect  = root.querySelector('[data-docs-sort]');

    function renderCategories() {
      var counts = {};
      Extras.data.documents.forEach(function (d) {
        counts[d.category] = (counts[d.category] || 0) + 1;
      });
      var html = '';
      Extras.data.docCategories.forEach(function (c) {
        var active = c.name === state.category ? ' is-active' : '';
        var n = c.name === 'All' ? Extras.data.documents.length : (counts[c.name] || 0);
        html += '<li class="docs-category-list__item text-' + c.color + active + '" data-docs-cat="' + c.name + '">' +
                '<span class="docs-cat-dot"></span>' +
                '<span>' + c.name + '</span>' +
                '<span class="docs-cat-count">' + n + '</span>' +
                '</li>';
      });
      catListEl.innerHTML = html;
      Array.prototype.forEach.call(catListEl.querySelectorAll('[data-docs-cat]'), function (li) {
        li.addEventListener('click', function () {
          state.category = this.getAttribute('data-docs-cat');
          renderCategories(); renderGrid();
        });
      });
    }

    function renderTags() {
      tagCloudEl.innerHTML = Extras.data.docTags.map(function (t) {
        var active = state.tag === t ? ' is-active' : '';
        return '<span class="docs-tag' + active + '" data-docs-tag="' + t + '">#' + t + '</span>';
      }).join('');
      Array.prototype.forEach.call(tagCloudEl.querySelectorAll('[data-docs-tag]'), function (el) {
        el.addEventListener('click', function () {
          var t = this.getAttribute('data-docs-tag');
          state.tag = state.tag === t ? null : t;
          renderTags(); renderGrid();
        });
      });
    }

    function renderStatuses() {
      var statuses = [
        { key: 'all',      label: 'All statuses',   icon: 'bi-circle', color: 'text-body-secondary' },
        { key: 'approved', label: 'Approved',       icon: 'bi-check-circle-fill', color: 'text-success' },
        { key: 'pending',  label: 'Pending review', icon: 'bi-hourglass-split',   color: 'text-warning' },
        { key: 'expired',  label: 'Expired',        icon: 'bi-x-circle-fill',     color: 'text-danger' }
      ];
      statusListEl.innerHTML = statuses.map(function (s) {
        var active = state.status === s.key ? ' is-active' : '';
        return '<li class="' + active + '" data-docs-status="' + s.key + '"><i class="bi ' + s.icon + ' ' + s.color + '"></i>' + s.label + '</li>';
      }).join('');
      Array.prototype.forEach.call(statusListEl.querySelectorAll('[data-docs-status]'), function (li) {
        li.addEventListener('click', function () {
          state.status = this.getAttribute('data-docs-status');
          renderStatuses(); renderGrid();
        });
      });
    }

    function filteredDocs() {
      var list = Extras.data.documents.slice();
      if (state.category && state.category !== 'All') list = list.filter(function (d) { return d.category === state.category; });
      if (state.tag) list = list.filter(function (d) { return d.tags.indexOf(state.tag) !== -1; });
      if (state.status !== 'all') list = list.filter(function (d) { return d.status === state.status; });
      if (state.search) {
        var q = state.search.toLowerCase();
        list = list.filter(function (d) {
          return d.title.toLowerCase().indexOf(q) !== -1 ||
                 d.category.toLowerCase().indexOf(q) !== -1 ||
                 d.tags.join(' ').indexOf(q) !== -1;
        });
      }
      var sortBy = sortSelect ? sortSelect.value : 'modified';
      if (sortBy === 'name') list.sort(function (a, b) { return a.title.localeCompare(b.title); });
      else if (sortBy === 'size') list.sort(function (a, b) { return b.size - a.size; });
      else if (sortBy === 'category') list.sort(function (a, b) { return a.category.localeCompare(b.category); });
      else list.sort(function (a, b) { return new Date(b.updated) - new Date(a.updated); });
      return list;
    }

    function renderGrid() {
      var docs = filteredDocs();
      countEl.textContent = docs.length + ' document' + (docs.length === 1 ? '' : 's');
      if (docs.length === 0) {
        gridEl.innerHTML =
          '<div class="extras-empty">' +
          '<div class="extras-empty__icon"><i class="bi bi-search"></i></div>' +
          '<h6>No matching documents</h6>' +
          '<p>Try clearing your filters or searching a different term.</p>' +
          '</div>';
        return;
      }

      if (state.view === 'list') return renderList(docs);

      gridEl.className = 'docs-grid';
      gridEl.innerHTML = docs.map(function (d) {
        var sel = state.selectedIds.indexOf(d.id) !== -1 ? ' is-selected' : '';
        return '' +
          '<div class="docs-card' + sel + '" data-docs-item="' + d.id + '">' +
            '<input type="checkbox" class="form-check-input docs-card__checkbox" ' + (sel ? 'checked' : '') + ' data-docs-check="' + d.id + '" aria-label="Select">' +
            '<span class="docs-card__status docs-card__status--' + d.status + '">' + (d.status === 'approved' ? 'Approved' : d.status === 'pending' ? 'Pending Review' : 'Expired') + '</span>' +
            '<div class="docs-card__head">' +
              '<div class="docs-card__icon docs-card__icon--' + d.type + '">' + d.type.toUpperCase() + '</div>' +
              '<div style="min-width:0;flex:1;padding-right:70px;">' +
                '<h6 class="docs-card__title">' + Extras.escapeHtml(d.title) + '</h6>' +
                '<div class="docs-card__catrow">' +
                  '<span class="docs-card__cat-chip">' + d.category + '</span>' +
                  '<span class="docs-card__version">' + d.version + '</span>' +
                '</div>' +
              '</div>' +
            '</div>' +
            '<p class="mb-2 small text-body-secondary">' + d.tags.slice(0, 3).map(function (t) { return '#' + t; }).join(' ') + '</p>' +
            '<div class="docs-card__foot">' +
              '<div class="docs-card__owner">' + Extras.renderAvatar(d.owner, 'primary') + '<span>' + Extras.escapeHtml(d.owner) + '</span></div>' +
              '<div>' + Extras.formatBytes(d.size) + ' · ' + Extras.relativeTime(d.updated) + '</div>' +
            '</div>' +
            '<div class="mt-2 pt-2 border-top d-flex gap-1 justify-content-end docs-card__actions">' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="view" data-docs-id="' + d.id + '" title="View"><i class="bi bi-eye"></i></button>' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="download" data-docs-id="' + d.id + '" title="Download"><i class="bi bi-download"></i></button>' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="share" data-docs-id="' + d.id + '" title="Share"><i class="bi bi-share"></i></button>' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="history" data-docs-id="' + d.id + '" title="Version history"><i class="bi bi-clock-history"></i></button>' +
              '<button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="edit" data-docs-id="' + d.id + '" title="Edit metadata"><i class="bi bi-pencil"></i></button>' +
            '</div>' +
          '</div>';
      }).join('');

      wireCards();
    }

    function renderList(docs) {
      gridEl.className = '';
      gridEl.innerHTML =
        '<div class="docs-list-row" style="background:transparent;border:0;font-weight:600;font-size:.72rem;text-transform:uppercase;color:var(--orchid-text-muted);padding:.5rem 1rem;">' +
        '<span></span><span></span><span>Title</span><span class="docs-list-row__hide">Category</span><span class="docs-list-row__hide">Version</span><span class="docs-list-row__hide">Owner</span><span class="docs-list-row__hide">Status</span><span class="docs-list-row__hide">Updated</span><span class="text-end">Actions</span>' +
        '</div>' +
        docs.map(function (d) {
          var sel = state.selectedIds.indexOf(d.id) !== -1 ? ' is-selected' : '';
          return '<div class="docs-list-row' + sel + '" data-docs-item="' + d.id + '">' +
            '<input type="checkbox" class="form-check-input" ' + (sel ? 'checked' : '') + ' data-docs-check="' + d.id + '" aria-label="Select">' +
            '<div class="docs-card__icon docs-card__icon--' + d.type + '">' + d.type.toUpperCase() + '</div>' +
            '<div class="docs-list-row__title">' + Extras.escapeHtml(d.title) + '</div>' +
            '<div class="docs-list-row__hide"><span class="docs-card__cat-chip">' + d.category + '</span></div>' +
            '<div class="docs-list-row__hide"><span class="docs-card__version">' + d.version + '</span></div>' +
            '<div class="docs-list-row__hide">' + Extras.escapeHtml(d.owner) + '</div>' +
            '<div class="docs-list-row__hide"><span class="docs-card__status docs-card__status--' + d.status + '" style="position:static;">' + (d.status === 'approved' ? 'Approved' : d.status === 'pending' ? 'Pending' : 'Expired') + '</span></div>' +
            '<div class="docs-list-row__hide text-body-secondary">' + Extras.relativeTime(d.updated) + '</div>' +
            '<div class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" data-docs-action="history" data-docs-id="' + d.id + '" title="History"><i class="bi bi-clock-history"></i></button></div>' +
          '</div>';
        }).join('');
      wireCards();
    }

    function wireCards() {
      Array.prototype.forEach.call(gridEl.querySelectorAll('[data-docs-check]'), function (cb) {
        cb.addEventListener('click', function (e) {
          e.stopPropagation();
          var id = this.getAttribute('data-docs-check');
          var idx = state.selectedIds.indexOf(id);
          if (this.checked && idx === -1) state.selectedIds.push(id);
          else if (!this.checked && idx !== -1) state.selectedIds.splice(idx, 1);
          renderBulkbar();
          var card = this.closest('[data-docs-item]');
          if (card) card.classList.toggle('is-selected', this.checked);
        });
      });
      Array.prototype.forEach.call(gridEl.querySelectorAll('[data-docs-action]'), function (btn) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          runDocAction(this.getAttribute('data-docs-action'), this.getAttribute('data-docs-id'));
        });
      });
    }

    function renderBulkbar() {
      if (state.selectedIds.length > 0) {
        bulkbarEl.classList.add('is-active');
        bulkCountEl.textContent = state.selectedIds.length;
      } else {
        bulkbarEl.classList.remove('is-active');
      }
    }

    function runDocAction(action, id) {
      var d = Extras.data.documents.find(function (x) { return x.id === id; });
      if (!d) return;
      if (action === 'view')     Extras.orchidToast('Opening “' + d.title + '”', 'primary');
      else if (action === 'download') Extras.orchidToast('Downloading “' + d.title + '” v' + d.version, 'success');
      else if (action === 'share')    Extras.orchidToast('Share link copied for “' + d.title + '”', 'success');
      else if (action === 'edit')     Extras.orchidToast('Edit metadata for “' + d.title + '” — coming soon', 'primary');
      else if (action === 'history')  openVersionHistory(d);
    }

    function openVersionHistory(d) {
      var modalEl = document.getElementById('docsVersionsModal');
      var titleEl = document.getElementById('docsVersionsTitle');
      titleEl.textContent = 'Version history — ' + d.title;
      // synth versions
      var v = parseFloat(d.version.replace(/^v/, ''));
      var versions = [];
      for (var i = 0; i < 5; i++) {
        var ver = (v - i * 0.1).toFixed(1);
        if (parseFloat(ver) <= 0) break;
        versions.push({
          version: 'v' + ver,
          author: i === 0 ? d.owner : ['Alex Kim','Sarah Miller','Ava Lee'][i % 3],
          date: new Date(Date.parse(d.updated) - i * 14 * 24 * 3600 * 1000).toISOString(),
          changelog: i === 0 ? 'Latest release — approved by ' + d.owner + '.' : ['Content review and typo fixes','Section 3 rewritten','Legal team edits','Formatting update'][i % 4],
          current: i === 0
        });
      }
      versionsList.innerHTML = versions.map(function (vv) {
        return '<div class="docs-version-row' + (vv.current ? ' is-current' : '') + '">' +
          '<div class="docs-version-row__badge">' + vv.version + '</div>' +
          '<div class="docs-version-row__body">' +
            '<p>' + Extras.escapeHtml(vv.changelog) + '</p>' +
            '<small>By ' + Extras.escapeHtml(vv.author) + ' · ' + Extras.fmtDate(vv.date) + '</small>' +
          '</div>' +
          '<div>' +
            '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-docs-preview-version="' + vv.version + '">Preview</button>' +
            (vv.current ? '<span class="badge bg-success-subtle text-success">Current</span>' : '<button class="btn btn-sm btn-primary" type="button" data-docs-restore="' + vv.version + '">Restore</button>') +
          '</div>' +
        '</div>';
      }).join('');
      Array.prototype.forEach.call(versionsList.querySelectorAll('[data-docs-restore]'), function (btn) {
        btn.addEventListener('click', function () {
          var target = this.getAttribute('data-docs-restore');
          Extras.orchidToast('Restored ' + target, 'success');
        });
      });
      Array.prototype.forEach.call(versionsList.querySelectorAll('[data-docs-preview-version]'), function (btn) {
        btn.addEventListener('click', function () {
          Extras.orchidToast('Previewing ' + this.getAttribute('data-docs-preview-version'), 'primary');
        });
      });
      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    }

    // Bulk
    root.querySelector('[data-docs-bulk-archive]').addEventListener('click', function () {
      Extras.orchidToast(state.selectedIds.length + ' documents archived', 'success');
      state.selectedIds = []; renderGrid(); renderBulkbar();
    });
    root.querySelector('[data-docs-bulk-tag]').addEventListener('click', function () {
      Extras.orchidToast('Tag added to ' + state.selectedIds.length + ' docs', 'success');
    });
    root.querySelector('[data-docs-bulk-share]').addEventListener('click', function () {
      Extras.orchidToast('Shared ' + state.selectedIds.length + ' documents', 'success');
    });
    root.querySelector('[data-docs-bulk-download]').addEventListener('click', function () {
      Extras.orchidToast('Downloading ' + state.selectedIds.length + ' documents as ZIP', 'primary');
    });
    root.querySelector('[data-docs-bulk-clear]').addEventListener('click', function () {
      state.selectedIds = []; renderGrid(); renderBulkbar();
    });

    // View toggle
    Array.prototype.forEach.call(root.querySelectorAll('[data-docs-view]'), function (btn) {
      btn.addEventListener('change', function () {
        if (this.checked) {
          state.view = this.value;
          renderGrid();
        }
      });
    });

    // Sort
    if (sortSelect) sortSelect.addEventListener('change', renderGrid);

    // Search
    searchInput.addEventListener('input', function () {
      state.search = this.value.trim();
      renderGrid();
    });

    // Upload
    root.querySelector('[data-docs-upload]').addEventListener('click', function () {
      Extras.orchidToast('Upload dialog — feature demo only', 'primary');
    });

    renderCategories();
    renderTags();
    renderStatuses();
    renderGrid();
  }

  // =========================================================
  // TIMELINE controller
  // =========================================================
  function initTimeline() {
    var root = document.querySelector('[data-extras-page="timeline"]');
    if (!root) return;

    var state = {
      view: 'vertical',
      filter: 'all'
    };

    var canvas = root.querySelector('[data-tl-canvas]');
    var chipsEl = root.querySelector('[data-tl-chips]');
    var formEl = document.getElementById('tlAddForm');

    function iconOf(type) {
      var t = Extras.data.eventTypes.find(function (e) { return e.key === type; });
      return t ? t.icon : 'bi-circle';
    }
    function labelOf(type) {
      var t = Extras.data.eventTypes.find(function (e) { return e.key === type; });
      return t ? t.label : type;
    }

    function renderChips() {
      var options = [
        { key: 'all', label: 'All events' },
        { key: 'milestone', label: 'Milestones' },
        { key: 'deployment', label: 'Deployments' },
        { key: 'announcement', label: 'Announcements' },
        { key: 'team', label: 'Team' },
        { key: 'feature', label: 'Features' },
        { key: 'bug', label: 'Bug fixes' },
        { key: 'meeting', label: 'Meetings' },
        { key: 'incident', label: 'Incidents' }
      ];
      chipsEl.innerHTML = options.map(function (o) {
        var active = state.filter === o.key ? ' is-active' : '';
        return '<span class="extras-chip' + active + '" data-tl-chip="' + o.key + '">' + o.label + '</span>';
      }).join('');
      Array.prototype.forEach.call(chipsEl.querySelectorAll('[data-tl-chip]'), function (c) {
        c.addEventListener('click', function () {
          state.filter = this.getAttribute('data-tl-chip');
          renderChips(); renderCanvas();
        });
      });
    }

    function filteredEvents() {
      var list = Extras.data.events.slice().sort(function (a, b) { return new Date(a.date) - new Date(b.date); });
      if (state.filter !== 'all') list = list.filter(function (e) { return e.type === state.filter; });
      return list;
    }

    function renderCanvas() {
      var events = filteredEvents();
      if (events.length === 0) {
        canvas.innerHTML = '<div class="extras-empty"><div class="extras-empty__icon"><i class="bi bi-clock"></i></div><h6>No events match this filter</h6></div>';
        return;
      }
      if (state.view === 'vertical')     return renderVertical(events);
      if (state.view === 'horizontal')   return renderHorizontal(events);
      if (state.view === 'grouped')      return renderGrouped(events);
      if (state.view === 'compact')      return renderCompact(events);
      if (state.view === 'alternating')  return renderAlternating(events);
    }

    function eventCardHtml(e) {
      return '<div class="tl-event-card tl-type--' + e.type + '">' +
        '<div class="tl-event-card__meta">' +
          '<span class="tl-event-card__type-chip"><i class="bi ' + iconOf(e.type) + '"></i> ' + labelOf(e.type) + '</span>' +
          '<span>' + Extras.fmtDate(e.date) + ' · ' + Extras.fmtTime(e.date) + '</span>' +
          '<span>· ' + Extras.escapeHtml(e.actor) + '</span>' +
        '</div>' +
        '<h6 class="tl-event-card__title">' + Extras.escapeHtml(e.title) + '</h6>' +
        '<p class="tl-event-card__desc">' + Extras.escapeHtml(e.description) + '</p>' +
      '</div>';
    }

    function renderVertical(events) {
      var html = '<div class="tl-view--vertical">';
      events.forEach(function (e, i) {
        var side = i % 2 === 0 ? 'left' : 'right';
        html += '<div class="tl-vertical-row tl-vertical-row--' + side + ' tl-type--' + e.type + '">' +
                  '<div class="tl-vertical-row__side">' + (side === 'left' ? eventCardHtml(e) : '') + '</div>' +
                  '<div class="tl-vertical-row__center"><span class="tl-icon"><i class="bi ' + iconOf(e.type) + '"></i></span></div>' +
                  '<div class="tl-vertical-row__side">' + (side === 'right' ? eventCardHtml(e) : '') + '</div>' +
                '</div>';
      });
      html += '</div>';
      canvas.innerHTML = html;
    }

    function renderHorizontal(events) {
      // group by day
      var byDay = {};
      events.forEach(function (e) {
        var d = new Date(e.date);
        var key = d.toISOString().slice(0, 10);
        if (!byDay[key]) byDay[key] = { date: d, events: [] };
        byDay[key].events.push(e);
      });
      var keys = Object.keys(byDay).sort();
      var html = '<div class="tl-view--horizontal"><div class="tl-horizontal">';
      keys.forEach(function (k) {
        var day = byDay[k];
        html += '<div class="tl-h-col">' +
          '<div class="tl-h-col__head">' + Extras.fmtDate(day.date) + '<small>' + day.events.length + ' event' + (day.events.length===1?'':'s') + '</small></div>' +
          '<div class="tl-h-col__marker"></div>' +
          '<div class="tl-h-col__events">' +
            day.events.map(eventCardHtml).join('') +
          '</div>' +
        '</div>';
      });
      html += '</div></div>';
      canvas.innerHTML = html;
    }

    function renderGrouped(events) {
      var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      var byMonth = {};
      events.forEach(function (e) {
        var d = new Date(e.date);
        var key = d.getFullYear() + '-' + (d.getMonth() + 1);
        if (!byMonth[key]) byMonth[key] = { label: monthNames[d.getMonth()] + ' ' + d.getFullYear(), events: [] };
        byMonth[key].events.push(e);
      });
      var keys = Object.keys(byMonth).sort();
      var html = '';
      keys.forEach(function (k) {
        var g = byMonth[k];
        html += '<div class="tl-group">' +
          '<div class="tl-group__head"><h5 class="tl-group__month">' + g.label + '</h5><span class="tl-group__count">' + g.events.length + ' events</span></div>' +
          '<div class="tl-group__body">';
        g.events.forEach(function (e) {
          html += '<div class="tl-group-row tl-type--' + e.type + '">' +
            '<span class="tl-icon tl-icon--sm"><i class="bi ' + iconOf(e.type) + '"></i></span>' +
            '<div class="tl-group-row__body">' +
              '<h6 class="tl-group-row__title">' + Extras.escapeHtml(e.title) + '</h6>' +
              '<div class="tl-group-row__meta"><span class="tl-event-card__type-chip">' + labelOf(e.type) + '</span> · ' + Extras.fmtDate(e.date) + ' · ' + Extras.escapeHtml(e.actor) + '</div>' +
              '<p class="mb-0 mt-1 small text-body-secondary">' + Extras.escapeHtml(e.description) + '</p>' +
            '</div>' +
          '</div>';
        });
        html += '</div></div>';
      });
      canvas.innerHTML = html;
    }

    function renderCompact(events) {
      var html = '<ul class="tl-compact">';
      events.forEach(function (e) {
        html += '<li class="tl-compact__item tl-type--' + e.type + '">' +
          '<span class="tl-compact__time">' + Extras.fmtDate(e.date) + '</span>' +
          '<span class="tl-compact__type-dot"></span>' +
          '<span class="tl-compact__msg"><strong>' + Extras.escapeHtml(e.title) + '</strong> — ' + Extras.escapeHtml(e.description.substring(0, 90)) + (e.description.length > 90 ? '…' : '') + '</span>' +
          '<span class="tl-compact__actor">' + Extras.escapeHtml(e.actor) + '</span>' +
        '</li>';
      });
      html += '</ul>';
      canvas.innerHTML = html;
    }

    function renderAlternating(events) {
      var html = '<div class="tl-alt">';
      events.forEach(function (e, i) {
        var reverse = i % 2 === 1 ? ' tl-alt-card--reverse' : '';
        html += '<div class="tl-alt-card tl-type--' + e.type + '' + reverse + '">' +
          '<div class="tl-alt-card__thumb"><i class="bi ' + iconOf(e.type) + '"></i></div>' +
          '<div class="tl-alt-card__body">' +
            '<div class="tl-alt-card__meta"><span class="tl-event-card__type-chip"><i class="bi ' + iconOf(e.type) + '"></i> ' + labelOf(e.type) + '</span><span>' + Extras.fmtDate(e.date) + '</span></div>' +
            '<h5 class="tl-alt-card__title">' + Extras.escapeHtml(e.title) + '</h5>' +
            '<p class="tl-alt-card__desc">' + Extras.escapeHtml(e.description) + '</p>' +
            '<div class="tl-alt-card__foot"><i class="bi bi-person-circle"></i><span>' + Extras.escapeHtml(e.actor) + '</span></div>' +
          '</div>' +
        '</div>';
      });
      html += '</div>';
      canvas.innerHTML = html;
    }

    // View picker
    Array.prototype.forEach.call(root.querySelectorAll('[data-tl-view]'), function (btn) {
      btn.addEventListener('change', function () {
        if (this.checked) {
          state.view = this.value;
          renderCanvas();
        }
      });
    });

    // Add event
    if (formEl) {
      formEl.addEventListener('submit', function (e) {
        e.preventDefault();
        var fd = new FormData(formEl);
        var date = fd.get('date') || new Date().toISOString().slice(0, 10);
        var time = fd.get('time') || '10:00';
        var iso = date + 'T' + time + ':00';
        var newEvent = {
          id: 'ev-' + Date.now(),
          date: iso,
          type: fd.get('type') || 'milestone',
          title: fd.get('title') || 'Untitled event',
          actor: fd.get('actor') || 'Alex Kim',
          description: fd.get('description') || ''
        };
        Extras.data.events.push(newEvent);
        Extras.orchidToast('Event “' + newEvent.title + '” added', 'success');
        var modalEl = document.getElementById('tlAddModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        formEl.reset();
        renderCanvas();
      });
    }

    renderChips();
    renderCanvas();
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initFileManager();
      initDocuments();
      initTimeline();
    });
  } else {
    initFileManager();
    initDocuments();
    initTimeline();
  }
})();
