<style id="quiztech-admin-unified-theme">
  :root {
    --qt-primary: #635bdf;
    --qt-primary-dark: #4f46e5;
    --qt-primary-soft: #eef2ff;
    --qt-border: #e7e9f2;
    --qt-canvas: #f7f8fc;
    --qt-text: #172033;
    --qt-muted: #64748b;
    --qt-radius: 18px;
    --qt-shadow: 0 10px 30px rgba(15, 23, 42, .055)
  }

  .admin-content>main {
    width: 100%;
    max-width: 100%;
    background: var(--qt-canvas)
  }

  .admin-content>main>.container-fluid,
  .admin-content>main>[class*="-wrapper"],
  .admin-content>main>[class*="-wrap"] {
    max-width: 1600px;
    margin-inline: auto
  }

  .admin-page-head,
  .page-header,
  .dashboard-header,
  .users-header,
  .subjects-header,
  .exams-header,
  .rooms-header,
  .profile-header,
  .settings-header,
  .leaderboard-header {
    border: 0 !important;
    border-radius: 22px !important;
    background: linear-gradient(135deg, #4338ca 0%, #635bdf 52%, #7c3aed 100%) !important;
    color: #fff !important;
    box-shadow: 0 14px 32px rgba(79, 70, 229, .16) !important;
    padding: 22px 24px !important;
    margin-bottom: 22px !important
  }

  .admin-page-head h1,
  .admin-page-head h2,
  .admin-page-head h3,
  .admin-page-head h4,
  .page-header h1,
  .page-header h2,
  .page-header h3,
  .page-header h4,
  .dashboard-header h1,
  .dashboard-header h2,
  .dashboard-header h3,
  .dashboard-header h4,
  .users-header h3,
  .subjects-header h3,
  .exams-header h3,
  .rooms-header h3 {
    color: #fff !important
  }

  .admin-page-head .text-muted,
  .page-header .text-muted,
  .dashboard-header .text-muted,
  .users-header .text-muted,
  .subjects-header .text-muted,
  .exams-header .text-muted,
  .rooms-header .text-muted {
    color: rgba(255, 255, 255, .72) !important
  }

  .card:not(.admin-page-head):not(.page-header):not(.dashboard-header):not(.users-header):not(.subjects-header):not(.exams-header):not(.rooms-header):not(.profile-header):not(.settings-header):not(.leaderboard-header),
  .creative-table-card,
  .table-wrap {
    border: 1px solid var(--qt-border) !important;
    border-radius: var(--qt-radius) !important;
    box-shadow: var(--qt-shadow) !important;
    background: #fff !important;
    overflow: hidden
  }

  .card-header {
    background: #fff !important;
    border-bottom: 1px solid #eef0f5 !important;
    padding: 16px 18px !important
  }

  .table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #c7c9d9 transparent
  }

  .table {
    --bs-table-bg: transparent;
    margin-bottom: 0
  }

  .table thead th,
  .creative-table thead th,
  .room-table thead th,
  .table-modern thead th {
    background: #f8f9fc !important;
    color: #64748b !important;
    font-size: .74rem !important;
    text-transform: uppercase !important;
    letter-spacing: .045em !important;
    font-weight: 700 !important;
    border-bottom: 1px solid #e9ebf2 !important;
    padding: .9rem 1rem !important;
    white-space: nowrap
  }

  .table tbody td,
  .creative-table tbody td,
  .room-table tbody td,
  .table-modern tbody td {
    padding: .9rem 1rem !important;
    border-bottom: 1px solid #f0f1f5 !important;
    vertical-align: middle !important
  }

  .table tbody tr:last-child td {
    border-bottom: 0 !important
  }

  .table-hover tbody tr:hover>* {
    background: #fafaff !important
  }

  .btn {
    border-radius: 11px;
    font-weight: 650
  }

  .btn-primary {
    background: linear-gradient(135deg, #635bdf, #4f46e5) !important;
    border-color: transparent !important;
    box-shadow: 0 5px 13px rgba(79, 70, 229, .14)
  }

  .btn-primary:hover {
    filter: brightness(.96)
  }

  .btn-outline-primary {
    color: #4f46e5;
    border-color: #c7d2fe
  }

  .btn-outline-primary:hover {
    background: #eef2ff;
    color: #3730a3;
    border-color: #a5b4fc
  }

  .btn-danger,
  .btn-outline-danger {
    box-shadow: none !important
  }

  .form-control,
  .form-select {
    border-radius: 11px;
    border-color: #dde1ea;
    min-height: 42px
  }

  .form-control:focus,
  .form-select:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 .2rem rgba(99, 102, 241, .12)
  }

  .input-group .form-control {
    min-height: 40px
  }

  .badge {
    font-weight: 650;
    padding: .45em .7em;
    border-radius: 999px
  }

  .modal-content {
    border: 0 !important;
    border-radius: 20px !important;
    box-shadow: 0 24px 70px rgba(15, 23, 42, .16) !important
  }

  .modal-header,
  .modal-footer {
    border-color: #eef0f5 !important
  }

  .pagination .page-link {
    border: 0;
    color: #4f46e5;
    border-radius: 9px !important;
    margin: 0 2px
  }

  .pagination .active>.page-link {
    background: #635bdf;
    color: #fff
  }

  .dropdown-menu {
    border-radius: 14px;
    border-color: #e7e9f2;
    box-shadow: 0 16px 40px rgba(15, 23, 42, .1)
  }

  .qt-mobile-table-card {
    display: none
  }

  @media(max-width:991.98px) {
    .admin-content>main {
      padding: 16px !important
    }

    .admin-page-head,
    .page-header,
    .dashboard-header,
    .users-header,
    .subjects-header,
    .exams-header,
    .rooms-header,
    .profile-header,
    .settings-header,
    .leaderboard-header {
      padding: 18px !important;
      border-radius: 17px !important
    }

    .card,
    .creative-table-card,
    .table-wrap {
      border-radius: 15px !important
    }
  }

  @media(max-width:767.98px) {
    .admin-content>main {
      padding: 12px !important
    }

    .container-fluid {
      padding-left: 0 !important;
      padding-right: 0 !important
    }

    .admin-page-head,
    .page-header,
    .dashboard-header,
    .users-header,
    .subjects-header,
    .exams-header,
    .rooms-header,
    .profile-header,
    .settings-header,
    .leaderboard-header {
      margin-bottom: 14px !important
    }

    .admin-page-head .btn,
    .page-header .btn {
      width: 100%
    }

    .table-responsive {
      border-radius: 0 !important
    }

    .table {
      min-width: 760px
    }

    .modal-dialog {
      margin: .65rem
    }

    .modal-content {
      border-radius: 16px !important
    }

    .btn-group {
      flex-wrap: wrap
    }

    .btn-group>.btn {
      flex: 1 1 auto
    }

    .pagination {
      flex-wrap: wrap;
      justify-content: center
    }

    .card-body {
      padding: 14px
    }

    .card-body.p-0 {
      padding: 0 !important
    }
  }

  @media(max-width:420px) {
    .admin-content>main {
      padding: 10px !important
    }

    .admin-navbar {
      padding-inline: 9px !important
    }

    .admin-mobile-logo {
      width: 42px !important;
      height: 33px !important
    }

    .admin-page-head,
    .page-header,
    .dashboard-header,
    .users-header,
    .subjects-header,
    .exams-header,
    .rooms-header,
    .profile-header,
    .settings-header,
    .leaderboard-header {
      padding: 15px !important
    }

    .modal-dialog {
      margin: .4rem
    }

    .form-control,
    .form-select {
      font-size: .92rem
    }
  }

  /* Role-safe final overrides: giữ màu header, không can thiệp PHP/HTML */
  .admin-content .admin-page-head,
  .admin-content .page-header,
  .admin-content .dashboard-header,
  .admin-content .users-header,
  .admin-content .subjects-header,
  .admin-content .exams-header,
  .admin-content .rooms-header,
  .admin-content .profile-header,
  .admin-content .settings-header,
  .admin-content .leaderboard-header {
    background: linear-gradient(135deg, #5546fa 0%, #554bdb 52%, #5d03b1 100%) !important;
    color: #fff !important;
    border-color: transparent !important;
  }

  .admin-content .admin-page-head .card-body,
  .admin-content .page-header .card-body,
  .admin-content .dashboard-header .card-body {
    background: transparent !important;
    color: inherit !important
  }

  .admin-content .admin-page-head h1,
  .admin-content .admin-page-head h2,
  .admin-content .admin-page-head h3,
  .admin-content .admin-page-head h4,
  .admin-content .page-header h1,
  .admin-content .page-header h2,
  .admin-content .page-header h3,
  .admin-content .page-header h4,
  .admin-content .dashboard-header h1,
  .admin-content .dashboard-header h2,
  .admin-content .dashboard-header h3,
  .admin-content .dashboard-header h4 {
    color: #fff !important
  }

  .admin-content .admin-page-head .text-muted,
  .admin-content .admin-page-head .text-secondary,
  .admin-content .page-header .text-muted,
  .admin-content .dashboard-header .text-muted {
    color: rgba(255, 255, 255, .78) !important
  }
</style>