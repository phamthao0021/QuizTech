<?php

require_once 'includes/config.php';

require_once 'includes/functions.php';

require_once 'includes/auth.php';

require_once 'includes/data.php';



$page_title = 'Trang chủ';

$subjects = getSubjects() ?? [];
$exams    = getExams()    ?? [];
$stats    = getStats()    ?? [];

$stats_subjects  = (int)($stats['subjects'] ?? 0);

$stats_exams     = (int)($stats['exams'] ?? 0);

$stats_questions = (int)($stats['questions'] ?? 0);

$stats_results   = (int)($stats['results'] ?? 0);



require_once 'includes/header_guest.php';

?>

<style>
  :root {

    --qt-p: #6548e8;

    --qt-p2: #9b5cf6;

    --qt-p3: #d946ef;

    --qt-navy: #17143f;

    --qt-text: #253047;

    --qt-muted: #68758c;

    --qt-line: #e9e7f3;

    --qt-soft: #f8f6ff;

    --qt-soft2: #f2efff
  }



  html {

    scroll-behavior: smooth
  }



  .qt-home {

    overflow: hidden;

    color: var(--qt-text);

    font-size: 15px
  }



  .qt-home p {

    font-size: .96rem;

    line-height: 1.72
  }



  .qt-home h1,

  .qt-home h2,

  .qt-home h3,

  .qt-home h4,

  .qt-home h5 {

    font-family: "Space Grotesk", Inter, sans-serif
  }



  .qt-section {

    padding: 68px 0
  }



  .qt-eyebrow {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    color: var(--qt-p);

    font-weight: 700;

    font-size: .76rem;

    text-transform: uppercase;

    letter-spacing: .08em
  }



  .qt-title {

    font-weight: 700;

    color: var(--qt-navy);

    font-size: clamp(1.7rem, 3vw, 2.45rem);

    line-height: 1.18;

    letter-spacing: -.035em
  }



  .qt-gradient {

    background: linear-gradient(100deg, #5146e5 0%, #8b5cf6 48%, #c044e8 100%);

    -webkit-background-clip: text;

    background-clip: text;

    color: transparent
  }



  .qt-hero {

    position: relative;

    background: linear-gradient(135deg, #faf8ff 0%, #f0edff 47%, #f7f9ff 100%);

    padding: 70px 0 48px;

    isolation: isolate
  }



  .qt-hero:before,

  .qt-hero:after {

    content: "";

    position: absolute;

    border-radius: 50%;

    filter: blur(8px);

    z-index: -1;

    animation: qtFloat 7s ease-in-out infinite
  }



  .qt-hero:before {

    width: 340px;

    height: 340px;

    background: radial-gradient(circle, rgba(139, 92, 246, .18), transparent 68%);

    top: -90px;

    left: -80px
  }



  .qt-hero:after {

    width: 420px;

    height: 420px;

    background: radial-gradient(circle, rgba(99, 102, 241, .15), transparent 68%);

    right: -120px;

    bottom: -140px;

    animation-delay: -2.5s
  }



  .qt-pill {

    display: inline-flex;

    gap: 8px;

    align-items: center;

    background: rgba(255, 255, 255, .85);

    border: 1px solid #ddd6fe;

    border-radius: 999px;

    padding: 7px 13px;

    color: #5848df;

    font-weight: 700;

    font-size: .78rem;

    box-shadow: 0 8px 24px rgba(79, 70, 229, .06)
  }



  .qt-hero h1 {

    font-weight: 700;

    color: var(--qt-navy);

    font-size: clamp(2.15rem, 4.5vw, 3.65rem);

    line-height: 1.08;

    letter-spacing: -.05em
  }



  .qt-hero-lead {

    color: var(--qt-muted);

    max-width: 620px
  }



  .qt-btn-main {

    background: linear-gradient(135deg, #5b4bea, #8b5cf6);

    color: #fff;

    border: 0;

    border-radius: 12px;

    padding: .75rem 1.18rem;

    font-size: .91rem;

    font-weight: 700;

    box-shadow: 0 10px 24px rgba(101, 72, 232, .22);

    transition: .25s
  }



  .qt-btn-main:hover {

    color: #fff;

    transform: translateY(-2px);

    box-shadow: 0 14px 28px rgba(101, 72, 232, .28)
  }



  .qt-btn-soft {

    background: #fff;

    border: 1px solid #ddd9eb;

    border-radius: 12px;

    padding: .75rem 1.18rem;

    font-size: .91rem;

    font-weight: 700;

    color: #3e4960;

    transition: .25s
  }



  .qt-btn-soft:hover {

    border-color: #b8a8f5;

    color: var(--qt-p);

    transform: translateY(-2px)
  }



  .qt-demo-card {

    position: relative;

    background: rgba(255, 255, 255, .92);

    border: 1px solid rgba(218, 214, 235, .9);

    border-radius: 22px;

    padding: 23px;

    box-shadow: 0 24px 55px rgba(53, 42, 110, .10);

    animation: qtCardFloat 5s ease-in-out infinite
  }



  .qt-demo-card:before {

    content: "";

    position: absolute;

    inset: -1px;

    border-radius: 22px;

    padding: 1px;

    background: linear-gradient(135deg, rgba(139, 92, 246, .45), transparent 42%, rgba(99, 102, 241, .22));

    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);

    -webkit-mask-composite: xor;

    pointer-events: none
  }



  .qt-demo-q {

    font-size: 1.18rem;

    font-weight: 700;

    color: #171b2d;

    line-height: 1.4
  }



  .qt-choice {

    border: 1px solid #e6e7ee;

    border-radius: 11px;

    padding: 9px 12px;

    margin-top: 8px;

    font-size: .85rem;

    transition: .2s
  }



  .qt-choice:hover {

    border-color: #c4b5fd;

    transform: translateX(3px)
  }



  .qt-choice.active {

    border-color: #b9a7fa;

    background: #f1edff;

    color: #5941d8;

    font-weight: 600
  }



  .qt-icon {

    width: 46px;

    height: 46px;

    border-radius: 14px;

    display: grid;

    place-items: center;

    background: linear-gradient(135deg, #eeeaff, #f7eefe);

    color: var(--qt-p);

    font-size: 1.18rem;

    transition: .25s
  }



  .qt-metrics {

    margin-top: 32px;

    background: rgba(255, 255, 255, .9);

    border: 1px solid #e5e2f0;

    border-radius: 18px;

    padding: 12px;

    box-shadow: 0 12px 35px rgba(38, 30, 86, .05)
  }



  .qt-metric {

    padding: 7px 14px;

    border-right: 1px solid #ebe8f2
  }



  .qt-metric:last-child {

    border-right: 0
  }



  .qt-metric strong {

    font-family: "Space Grotesk", sans-serif;

    font-size: 1.35rem;

    color: #6550e8
  }



  .qt-metric div {

    font-size: .76rem
  }



  .qt-card {

    position: relative;

    border: 1px solid var(--qt-line);

    border-radius: 18px;

    padding: 21px;

    height: 100%;

    transition: transform .3s, box-shadow .3s, border-color .3s;

    background: #fff
  }



  .qt-card:hover {

    transform: translateY(-6px);

    box-shadow: 0 18px 40px rgba(50, 39, 110, .09);

    border-color: #cfc4fa
  }



  .qt-card:hover .qt-icon {

    transform: rotate(-4deg) scale(1.07);

    background: linear-gradient(135deg, #6548e8, #9b5cf6);

    color: #fff
  }



  .qt-card h5 {

    font-size: 1rem
  }



  .qt-card p {

    font-size: .84rem !important
  }



  .qt-how {

    background: linear-gradient(180deg, #fbfaff, #f6f4ff)
  }



  .qt-step-num {

    width: 33px;

    height: 33px;

    border-radius: 10px;

    display: grid;

    place-items: center;

    background: linear-gradient(135deg, #5d4ce7, #985cf4);

    color: #fff;

    font-size: .84rem;

    font-weight: 800;

    box-shadow: 0 7px 16px rgba(101, 72, 232, .2)
  }



  .qt-chip {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 7px 10px;

    border: 1px solid #e4dffd;

    border-radius: 10px;

    background: #faf9ff;

    color: #6654d9;

    font-size: .78rem;

    font-weight: 600
  }



  .qt-cta {

    position: relative;

    overflow: hidden;

    background: linear-gradient(125deg, #211653 0%, #4d2a98 52%, #7c3aed 100%);

    border-radius: 24px;

    padding: 39px 28px;

    color: #fff;

    box-shadow: 0 20px 45px rgba(76, 41, 152, .18)
  }



  .qt-cta:after {

    content: "";

    position: absolute;

    width: 230px;

    height: 230px;

    border-radius: 50%;

    background: rgba(255, 255, 255, .07);

    right: -70px;

    top: -90px;

    animation: qtPulse 4s ease-in-out infinite
  }



  .qt-contact {

    background: linear-gradient(180deg, #fff, #faf9ff)
  }



  .qt-contact-card {

    border: 1px solid #e8e4f2;

    border-radius: 20px;

    padding: 25px;

    background: #fff;

    box-shadow: 0 15px 35px rgba(45, 35, 95, .05)
  }



  .qt-contact-item {

    display: flex;

    gap: 13px;

    align-items: flex-start;

    margin-bottom: 17px
  }



  .qt-contact-item .qt-icon {

    flex: 0 0 42px;

    width: 42px;

    height: 42px
  }



  .qt-contact .form-control {

    border-radius: 11px;

    padding: 10px 12px;

    border-color: #e2dfeb;

    font-size: .88rem
  }



  .qt-contact .form-control:focus {

    border-color: #9b7cf2;

    box-shadow: 0 0 0 .18rem rgba(139, 92, 246, .11)
  }



  .qt-contact .form-label {

    font-size: .82rem
  }



  .qt-reveal {

    opacity: 0;

    transform: translateY(24px);

    transition: opacity .65s ease, transform .65s cubic-bezier(.2, .7, .2, 1)
  }



  .qt-reveal.qt-visible {

    opacity: 1;

    transform: none
  }



  .qt-delay-1 {

    transition-delay: .08s
  }



  .qt-delay-2 {

    transition-delay: .16s
  }



  .qt-delay-3 {

    transition-delay: .24s
  }



  @keyframes qtFloat {

    50% {

      transform: translate(20px, 16px) scale(1.05)
    }

  }



  @keyframes qtCardFloat {

    50% {

      transform: translateY(-7px)
    }

  }



  @keyframes qtPulse {

    50% {

      transform: scale(1.12);

      opacity: .7
    }

  }



  @media(prefers-reduced-motion:reduce) {



    .qt-demo-card,

    .qt-hero:before,

    .qt-hero:after,
    .qt-cta:after {
      animation: none
    }
    .qt-reveal {
      opacity: 1;
      transform: none;
      transition: none
    }

  }
  @media(max-width:991px) {
    .qt-hero {
      text-align: center;
      padding-top: 52px
    }
    .qt-hero-lead {
      margin-left: auto;
      margin-right: auto
    }
    .qt-metric {
      border-right: 0;
      border-bottom: 1px solid #eee
    }
    .qt-metric:last-child {
      border-bottom: 0
    }
    .qt-section {
      padding: 56px 0
    }
  }
  @media(max-width:575px) {
    .qt-home {
      font-size: 14px
    }
    .qt-hero h1 {
      font-size: 2.05rem
    }
    .qt-section {

      padding: 48px 0
    }
    .qt-demo-card {
      padding: 18px
    }
    .qt-cta {
      padding: 32px 20px
    }
  }

  /* ==============================
     ENHANCED HERO EXPERIENCE
  ============================== */

  .qt-hero-title {
    min-height: 132px;
  }

  .qt-typing-wrap {
    position: relative;
    display: inline-block;
    min-height: 1.08em;
  }

  #qtTypingText {
    display: inline;
  }

  .qt-typing-cursor {
    display: inline-block;
    width: 3px;
    height: .82em;
    margin-left: 5px;
    border-radius: 10px;
    vertical-align: -2px;
    background: linear-gradient(180deg, var(--qt-p), var(--qt-p2), var(--qt-p3));
    animation: qtCursorBlink .8s infinite;
  }

  .qt-hero-btn {
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
  }

  .qt-experience-wrap {
    position: relative;
    padding: 18px;
    isolation: isolate;
  }

  .qt-experience-wrap::before {
    content: "";
    position: absolute;
    inset: 10% 5%;
    z-index: -2;
    background:
      radial-gradient(circle at 20% 30%, rgba(101, 72, 232, .20), transparent 35%),
      radial-gradient(circle at 80% 70%, rgba(217, 70, 239, .14), transparent 35%);
    filter: blur(40px);
  }

  .qt-exp-glow {
    position: absolute;
    border-radius: 50%;
    z-index: -1;
    filter: blur(5px);
    pointer-events: none;
  }

  .qt-exp-glow-1 {
    width: 110px;
    height: 110px;
    top: -5px;
    right: 20px;
    background: radial-gradient(circle, rgba(139, 92, 246, .25), transparent 70%);
    animation: qtExperienceFloat 5s ease-in-out infinite;
  }

  .qt-exp-glow-2 {
    width: 140px;
    height: 140px;
    bottom: -30px;
    left: -10px;
    background: radial-gradient(circle, rgba(217, 70, 239, .18), transparent 70%);
    animation: qtExperienceFloat 6s ease-in-out infinite reverse;
  }

  .qt-experience-card {
    position: relative;
    overflow: hidden;
    background: rgba(255, 255, 255, .90);
    border: 1px solid rgba(203, 195, 243, .9);
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 24px 70px rgba(56, 42, 125, .12), inset 0 1px 0 rgba(255, 255, 255, .9);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    animation: qtExperienceCard 5s ease-in-out infinite;
    transition: transform .35s ease, box-shadow .35s ease, border-color .35s ease;
  }

  .qt-experience-card:hover {
    border-color: rgba(139, 92, 246, .55);
    box-shadow: 0 30px 80px rgba(84, 58, 170, .18), inset 0 1px 0 rgba(255, 255, 255, 1);
  }

  .qt-experience-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 12%;
    right: 12%;
    height: 2px;
    background: linear-gradient(90deg, transparent, #6548e8, #9b5cf6, #d946ef, transparent);
  }

  .qt-experience-card::after {
    content: "";
    position: absolute;
    width: 100px;
    height: 160%;
    top: -30%;
    left: -160px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .55), transparent);
    transform: rotate(18deg);
    animation: qtCardShine 7s ease-in-out infinite;
    pointer-events: none;
  }

  .qt-exp-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding-bottom: 18px;
    border-bottom: 1px solid #eeeaf8;
  }

  .qt-exp-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .qt-exp-main-icon {
    width: 48px;
    height: 48px;
    flex: 0 0 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.25rem;
    background: linear-gradient(135deg, #6049eb, #9b5cf6);
    box-shadow: 0 10px 24px rgba(101, 72, 232, .25);
    animation: qtRocketFloat 2.8s ease-in-out infinite;
  }

  .qt-exp-small {
    color: var(--qt-p);
    font-size: .63rem;
    font-weight: 800;
    letter-spacing: .1em;
  }

  .qt-exp-header h5 {
    margin: 2px 0 0;
    color: var(--qt-navy);
    font-size: 1.05rem;
    font-weight: 700;
  }

  .qt-exp-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    color: #16a34a;
    font-size: .7rem;
    font-weight: 700;
    padding: 6px 9px;
    background: #f0fdf4;
    border: 1px solid #dcfce7;
    border-radius: 999px;
  }

  .qt-exp-status span {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, .10);
    animation: qtOnlinePulse 1.5s infinite;
  }

  .qt-exp-body {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-top: 14px;
  }

  .qt-exp-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 10px;
    border: 1px solid transparent;
    border-radius: 15px;
    cursor: default;
    opacity: 0;
    transform: translateX(22px);
    animation: qtItemEntrance .55s cubic-bezier(.2, .7, .2, 1) forwards;
    transition: transform .25s ease, background .25s ease, border-color .25s ease, box-shadow .25s ease;
  }

  .qt-exp-item:nth-child(1) {
    animation-delay: .15s;
  }

  .qt-exp-item:nth-child(2) {
    animation-delay: .28s;
  }

  .qt-exp-item:nth-child(3) {
    animation-delay: .41s;
  }

  .qt-exp-item:nth-child(4) {
    animation-delay: .54s;
  }

  .qt-exp-item:nth-child(5) {
    animation-delay: .67s;
  }

  .qt-exp-item:hover {
    transform: translateX(5px);
    background: linear-gradient(100deg, #faf8ff, #f8f6ff);
    border-color: #e4dcff;
    box-shadow: 0 10px 25px rgba(101, 72, 232, .07);
  }

  .qt-exp-icon {
    width: 43px;
    height: 43px;
    flex: 0 0 43px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    color: #7555ef;
    font-size: 1.07rem;
    background: linear-gradient(135deg, #eeeaff, #f8f0ff);
    transition: transform .25s ease, background .25s ease, color .25s ease, box-shadow .25s ease;
  }

  .qt-exp-item:hover .qt-exp-icon {
    transform: scale(1.08) rotate(-5deg);
    color: #fff;
    background: linear-gradient(135deg, #6049eb, #a15bf6);
    box-shadow: 0 8px 18px rgba(101, 72, 232, .20);
  }

  .qt-exp-content {
    min-width: 0;
    flex: 1;
  }

  .qt-exp-content strong {
    display: block;
    margin-bottom: 1px;
    color: #26243b;
    font-size: .87rem;
    font-weight: 700;
  }

  .qt-exp-content small {
    display: block;
    color: #7b8190;
    font-size: .74rem;
    line-height: 1.45;
  }

  .qt-exp-arrow {
    color: #b8b1cf;
    font-size: .9rem;
    opacity: 0;
    transform: translateX(-7px);
    transition: opacity .2s ease, transform .2s ease, color .2s ease;
  }

  .qt-exp-item:hover .qt-exp-arrow {
    opacity: 1;
    color: var(--qt-p);
    transform: translateX(0);
  }

  .qt-exp-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: 15px;
    padding-top: 14px;
    border-top: 1px solid #eeeaf8;
    color: #777c8d;
    font-size: .68rem;
  }

  .qt-exp-footer>div {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .qt-exp-footer .bi-shield-check {
    color: var(--qt-p);
  }

  .qt-live-dot {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #6550e8;
    font-weight: 700;
  }

  .qt-live-dot i {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #8b5cf6;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, .10);
    animation: qtOnlinePulse 1.5s infinite;
  }

  @keyframes qtCursorBlink {

    0%,
    45% {
      opacity: 1;
    }

    46%,
    100% {
      opacity: 0;
    }
  }

  @keyframes qtExperienceCard {

    0%,
    100% {
      transform: translateY(0);
    }

    50% {
      transform: translateY(-6px);
    }
  }

  @keyframes qtExperienceFloat {

    0%,
    100% {
      transform: translateY(0) scale(1);
    }

    50% {
      transform: translateY(-14px) scale(1.08);
    }
  }

  @keyframes qtRocketFloat {

    0%,
    100% {
      transform: translateY(0) rotate(0);
    }

    50% {
      transform: translateY(-4px) rotate(-7deg);
    }
  }

  @keyframes qtItemEntrance {
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  @keyframes qtOnlinePulse {

    0%,
    100% {
      opacity: 1;
    }

    50% {
      opacity: .4;
    }
  }

  @keyframes qtCardShine {

    0%,
    60% {
      left: -180px;
    }

    80%,
    100% {
      left: 120%;
    }
  }

  @media(max-width:991px) {
    .qt-hero-title {
      min-height: auto;
    }

    .qt-experience-wrap {
      max-width: 620px;
      margin-left: auto;
      margin-right: auto;
      padding: 5px;
    }
  }

  @media(max-width:575px) {
    .qt-experience-card {
      padding: 17px;
      border-radius: 19px;
    }

    .qt-exp-header {
      align-items: flex-start;
    }

    .qt-exp-status {
      display: none;
    }

    .qt-exp-main-icon {
      width: 42px;
      height: 42px;
      flex-basis: 42px;
    }

    .qt-exp-item {
      padding-left: 5px;
      padding-right: 5px;
    }

    .qt-exp-icon {
      width: 39px;
      height: 39px;
      flex-basis: 39px;
    }

    .qt-exp-content strong {
      font-size: .82rem;
    }

    .qt-exp-content small {
      font-size: .69rem;
    }

    .qt-exp-footer {
      flex-direction: column;
      align-items: flex-start;
    }
  }

  @media(prefers-reduced-motion:reduce) {

    .qt-experience-card,
    .qt-exp-glow,
    .qt-exp-main-icon,
    .qt-live-dot i,
    .qt-exp-status span,
    .qt-experience-card::after,
    .qt-typing-cursor {
      animation: none !important;
    }

    .qt-exp-item {
      opacity: 1;
      transform: none;
      animation: none;
    }
  }
  .qt-experience-card {
  position: relative;
  overflow: hidden;
  background: rgba(255, 255, 255, .88);
  border: 1px solid rgba(203, 195, 243, .9);
  border-radius: 24px;
  padding: 24px;

  box-shadow:
    0 24px 70px rgba(56, 42, 125, .12),
    inset 0 1px 0 rgba(255, 255, 255, .9);

  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);

  transition:
    transform .25s ease,
    box-shadow .35s ease,
    border-color .35s ease;
}
</style>

<div class="qt-home">

  <section class="qt-hero" id="home">

    <div class="container">

      <div class="row align-items-center g-5">
        <div class="col-lg-6 qt-reveal">
          <span class="qt-pill">
            <i class="bi bi-stars"></i> Nền tảng luyện thi CNTT Thế Hệ Mới
          </span>

          <h1 class="mt-4 qt-hero-title">
  Học lập trình bằng cách<br>

  <span class="qt-gradient qt-typing-wrap">
    <span id="qtTypingText"></span>
    <span class="qt-typing-cursor"></span>
  </span>
</h1>

          <p class="qt-hero-lead mt-3">
            <strong>QuizTech</strong> giúp sinh viên bứt phá kỹ năng lập trình, CSDL, Web, mạng máy tính
            và an toàn thông tin với hệ thống chấm điểm tự động, phòng thi trực tuyến realtime,
            luyện tập tương tác và phân tích năng lực theo từng nhóm kỹ năng.
          </p>

          <div class="d-flex gap-3 flex-wrap mt-4 justify-content-lg-start justify-content-center">
            <a href="exams.php" class="btn qt-btn-main qt-hero-btn">
              <i class="bi bi-play-circle-fill"></i> Làm bài ngay
            </a>
            <a href="rooms.php" class="btn qt-btn-soft qt-hero-btn">
              <i class="bi bi-people-fill"></i> Phòng thi Online
            </a>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-4 justify-content-lg-start justify-content-center">
            <span class="qt-chip"><i class="bi bi-lightning-charge"></i> Chấm điểm tự động</span>
            <span class="qt-chip"><i class="bi bi-graph-up"></i> Phân tích theo kỹ năng</span>
            <span class="qt-chip"><i class="bi bi-controller"></i> Luyện tập tương tác</span>
          </div>
        </div>

        <div class="col-lg-6 qt-reveal qt-delay-1">
          <div class="qt-experience-wrap">
            <div class="qt-exp-glow qt-exp-glow-1"></div>
            <div class="qt-exp-glow qt-exp-glow-2"></div>

            <div class="qt-experience-card">
              <div class="qt-exp-header">
                <div class="qt-exp-header-left">
                  <div class="qt-exp-main-icon">
                    <i class="bi bi-rocket-takeoff-fill"></i>
                  </div>
                  <div>
                    <span class="qt-exp-small">QUIZTECH EXPERIENCE</span>
                    <h5>Trải nghiệm học tập thông minh</h5>
                  </div>
                </div>

                <div class="qt-exp-status">
                  <span></span> Online
                </div>
              </div>

              <div class="qt-exp-body">
                <div class="qt-exp-item">
                  <div class="qt-exp-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                  <div class="qt-exp-content">
                    <strong>Chấm điểm tức thì</strong>
                    <small>Nhận kết quả, đáp án và phân tích ngay sau khi hoàn thành.</small>
                  </div>
                  <i class="bi bi-arrow-right qt-exp-arrow"></i>
                </div>

                <div class="qt-exp-item">
                  <div class="qt-exp-icon"><i class="bi bi-cpu-fill"></i></div>
                  <div class="qt-exp-content">
                    <strong>Ngân hàng câu hỏi CNTT</strong>
                    <small>Lập trình, CSDL, Web, Network, Security, Cloud và AI.</small>
                  </div>
                  <i class="bi bi-arrow-right qt-exp-arrow"></i>
                </div>

                <div class="qt-exp-item">
                  <div class="qt-exp-icon"><i class="bi bi-bar-chart-line-fill"></i></div>
                  <div class="qt-exp-content">
                    <strong>Phân tích năng lực</strong>
                    <small>Theo dõi điểm mạnh, điểm yếu và mức độ tiến bộ theo kỹ năng.</small>
                  </div>
                  <i class="bi bi-arrow-right qt-exp-arrow"></i>
                </div>

                <div class="qt-exp-item">
                  <div class="qt-exp-icon"><i class="bi bi-trophy-fill"></i></div>
                  <div class="qt-exp-content">
                    <strong>Bảng xếp hạng realtime</strong>
                    <small>Thi đua dựa trên điểm số và thời gian hoàn thành bài.</small>
                  </div>
                  <i class="bi bi-arrow-right qt-exp-arrow"></i>
                </div>

                <div class="qt-exp-item">
                  <div class="qt-exp-icon"><i class="bi bi-controller"></i></div>
                  <div class="qt-exp-content">
                    <strong>Đấu trường Multiplayer</strong>
                    <small>Tạo phòng, mời bạn bè và cùng tham gia thử thách trực tuyến.</small>
                  </div>
                  <i class="bi bi-arrow-right qt-exp-arrow"></i>
                </div>
              </div>

              <div class="qt-exp-footer">
                <div>
                  <i class="bi bi-shield-check"></i>
                  Học tập & kiểm tra trên cùng một nền tảng
                </div>
                <span class="qt-live-dot"><i></i> Realtime</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="qt-metrics qt-reveal qt-delay-2">

        <div class="row text-center g-0">

          <div class="col-lg-3 qt-metric"><strong class="qt-counter" data-target="<?= $stats_subjects ?>">0</strong>

            <div class="text-muted">Môn học CNTT</div>

          </div>

          <div class="col-lg-3 qt-metric"><strong class="qt-counter" data-target="<?= $stats_questions ?>">0</strong>

            <div class="text-muted">Câu hỏi hệ thống</div>

          </div>

          <div class="col-lg-3 qt-metric"><strong class="qt-counter" data-target="<?= $stats_exams ?>">0</strong>

            <div class="text-muted">Đề thi</div>

          </div>

          <div class="col-lg-3 qt-metric"><strong class="qt-counter" data-target="<?= $stats_results ?>">0</strong>

            <div class="text-muted">Lượt hoàn thành</div>

          </div>

        </div>

      </div>

    </div>

  </section>

  <section class="qt-section" id="about">

    <div class="container">

      <div class="row align-items-center g-5">

        <div class="col-lg-5 qt-reveal">

          <div class="qt-eyebrow"><i class="bi bi-compass"></i> Về QuizTech</div>

          <h2 class="qt-title mt-2">Một nơi để học, luyện tập và hiểu rõ năng lực CNTT của bạn.</h2>

          <p class="text-muted mt-3">QuizTech hướng đến sinh viên CNTT cần một quy trình rõ ràng: kiểm tra kiến thức, xem lại sai ở đâu, luyện tập phần còn yếu và theo dõi sự tiến bộ qua từng lần làm bài.</p>

        </div>

        <div class="col-lg-7">

          <div class="row g-3">

            <div class="col-sm-6 qt-reveal qt-delay-1">

              <div class="qt-card">

                <div class="qt-icon"><i class="bi bi-graph-up-arrow"></i></div>

                <h5 class="fw-bold mt-3">Hiểu năng lực</h5>

                <p class="text-muted mb-0">Theo dõi tỷ lệ đúng theo từng lĩnh vực thay vì chỉ nhìn một điểm tổng.</p>

              </div>

            </div>

            <div class="col-sm-6 qt-reveal qt-delay-2">

              <div class="qt-card">

                <div class="qt-icon"><i class="bi bi-signpost-split"></i></div>

                <h5 class="fw-bold mt-3">Học có ưu tiên</h5>

                <p class="text-muted mb-0">Nhận diện nhóm kiến thức yếu để tập trung thời gian vào phần cần cải thiện nhất.</p>

              </div>

            </div>

          </div>

        </div>

      </div>

    </div>

  </section>

  <section class="qt-section qt-how" id="method">

    <div class="container">

      <div class="text-center mb-5 qt-reveal">

        <div class="qt-eyebrow"><i class="bi bi-arrow-repeat"></i> Quy trình học</div>

        <h2 class="qt-title mt-2">Đánh giá → Phân tích → Luyện tập → Tiến bộ</h2>

        <p class="text-muted mx-auto" style="max-width:620px">Một vòng học ngắn gọn, dễ theo dõi và có dữ liệu để biết mình đang cải thiện ở đâu.</p>

      </div>

      <div class="row g-3">

        <div class="col-md-4 qt-reveal">

          <div class="qt-card">

            <div class="qt-step-num">01</div>

            <h5 class="fw-bold mt-3">Làm bài đánh giá</h5>

            <p class="text-muted mb-0">Trả lời câu hỏi từ kiến thức nền đến tình huống ứng dụng trong nhiều mảng CNTT.</p>

          </div>

        </div>

        <div class="col-md-4 qt-reveal qt-delay-1">

          <div class="qt-card">

            <div class="qt-step-num">02</div>

            <h5 class="fw-bold mt-3">Đọc bản đồ kỹ năng</h5>

            <p class="text-muted mb-0">Xem nhóm làm tốt, nhóm cần củng cố, câu trả lời sai và phần giải thích liên quan.</p>

          </div>

        </div>

        <div class="col-md-4 qt-reveal qt-delay-2">

          <div class="qt-card">

            <div class="qt-step-num">03</div>

            <h5 class="fw-bold mt-3">Luyện phần còn yếu</h5>

            <p class="text-muted mb-0">Ưu tiên 2–3 chủ đề cần cải thiện rồi làm lại bài đánh giá để so sánh tiến bộ.</p>

          </div>

        </div>

      </div>

    </div>

  </section>

  <section class="qt-section" id="features">

    <div class="container">

      <div class="text-center mb-5 qt-reveal">

        <div class="qt-eyebrow"><i class="bi bi-grid"></i> Hệ sinh thái</div>

        <h2 class="qt-title mt-2">Các công cụ phục vụ học và kiểm tra CNTT</h2>

        <p class="text-muted mx-auto" style="max-width:650px">Từ bài test nhanh đến luyện tập và thi trực tuyến, các chức năng được tổ chức quanh quá trình học của sinh viên.</p>

      </div>

      <div class="row g-3"><?php $features = [['bi-clipboard-data', 'Đánh giá năng lực', 'Phân tích kết quả theo từng lĩnh vực kiến thức.'], ['bi-journal-code', 'Ngân hàng câu hỏi', 'Câu hỏi về lập trình, CSDL, mạng, Web, Security, Cloud, AI và DevOps.'], ['bi-controller', 'Luyện tập tương tác', 'Các chế độ Practice giúp ôn khái niệm theo cách ngắn và trực quan.'], ['bi-people', 'Phòng thi trực tuyến', 'Tham gia hoạt động kiểm tra và phòng thi được tổ chức trên hệ thống.'], ['bi-bar-chart-line', 'Theo dõi kết quả', 'Lịch sử bài làm hỗ trợ quan sát sự thay đổi qua nhiều lần học.'], ['bi-shield-check', 'Phân quyền hệ thống', 'Tách chức năng Student, Teacher và Admin theo vai trò tài khoản.']];

                            foreach ($features as $i => $f): ?><div class="col-md-6 col-lg-4 qt-reveal <?= $i % 3 === 1 ? 'qt-delay-1' : ($i % 3 === 2 ? 'qt-delay-2' : '') ?>">

            <div class="qt-card">

              <div class="qt-icon"><i class="bi <?= e($f[0]) ?>"></i></div>

              <h5 class="fw-bold mt-3"><?= e($f[1]) ?></h5>

              <p class="text-muted mb-0"><?= e($f[2]) ?></p>

            </div>

          </div><?php endforeach; ?></div>

    </div>

  </section>

  <section class="pb-5">

    <div class="container qt-reveal">

      <div class="qt-cta text-center">

        <div class="small text-uppercase fw-bold opacity-75">Bài đánh giá miễn phí</div>

        <h2 class="fw-bold mt-2" style="font-size:clamp(1.55rem,3vw,2.15rem)">Bạn đang mạnh ở đâu và cần cải thiện phần nào?</h2>

        <p class="text-white-50 mx-auto mb-3" style="max-width:620px">Làm bài test thử để nhận điểm, bản đồ kỹ năng và các nhóm kiến thức nên ưu tiên ôn tập.</p><a href="demo.php" class="btn btn-light fw-bold px-4 py-2 position-relative" style="z-index:2">Bắt đầu dùng thử <i class="bi bi-arrow-right ms-1"></i></a>

      </div>

    </div>

  </section>

  <section class="qt-section qt-contact" id="contact">

    <div class="container">

      <div class="row g-5 align-items-start">

        <div class="col-lg-5 qt-reveal">

          <div class="qt-eyebrow"><i class="bi bi-headset"></i> Hỗ trợ</div>

          <h2 class="qt-title mt-2">Liên hệ với QuizTech</h2>

          <p class="text-muted mt-3">Bạn có thể liên hệ khi gặp vấn đề về tài khoản, bài thi, chức năng hệ thống hoặc muốn gửi góp ý cho nhóm phát triển.</p>

          <div class="qt-contact-item mt-4">

            <div class="qt-icon"><i class="bi bi-geo-alt"></i></div>

            <div><strong>Địa chỉ</strong>

              <div class="text-muted small mt-1">72 Nguyen Hue Boulevard, District 1, Ho Chi Minh City, Vietnam</div>

            </div>

          </div>

          <div class="qt-contact-item">

            <div class="qt-icon"><i class="bi bi-telephone"></i></div>

            <div><strong>Điện thoại</strong>

              <div class="text-muted small mt-1">+84 917 208 678</div>

            </div>

          </div>

          <div class="qt-contact-item">

            <div class="qt-icon"><i class="bi bi-envelope"></i></div>

            <div><strong>Email</strong>

              <div class="text-muted small mt-1">pltsolutions3010@gmail.com</div>

            </div>

          </div>

        </div>

        <div class="col-lg-7 qt-reveal qt-delay-1">

          <div class="qt-contact-card">

            <h4 class="fw-bold mb-1" style="font-size:1.15rem">Gửi lời nhắn</h4>

            <p class="text-muted small mb-4">Nhập nội dung cần hỗ trợ. Biểu mẫu sẽ mở ứng dụng email để bạn gửi trực tiếp cho QuizTech.</p>

            <form id="qtContactForm">

              <div class="row g-3">

                <div class="col-md-6"><label class="form-label fw-semibold">Họ và tên</label><input id="qtName" class="form-control" placeholder="Nguyễn Văn A" required></div>

                <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input id="qtEmail" type="email" class="form-control" placeholder="email@example.com" required></div>

                <div class="col-12"><label class="form-label fw-semibold">Chủ đề</label><input id="qtSubject" class="form-control" placeholder="Ví dụ: Hỗ trợ bài thi" required></div>

                <div class="col-12"><label class="form-label fw-semibold">Nội dung</label><textarea id="qtMessage" class="form-control" rows="4" placeholder="Mô tả vấn đề hoặc góp ý của bạn..." required></textarea></div>

                <div class="col-12"><button type="submit" class="btn qt-btn-main px-4">Gửi qua email <i class="bi bi-send ms-1"></i></button></div>

              </div>

            </form>

          </div>

        </div>

      </div>

    </div>

  </section>

</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
  const typingTarget = document.getElementById('qtTypingText');

  if (!typingTarget) return;

  const words = [
    'chinh phục đỉnh cao.',
    'làm chủ kiến thức.',
    'luyện tập mỗi ngày.',
    'thử thách bản thân.',
    'tiến bộ thông minh.'
  ];

  let wordIndex = 0;
  let charIndex = 0;
  let deleting = false;

  typingTarget.textContent = '';

  function typeText() {
    const currentWord = words[wordIndex];

    if (!deleting) {
      typingTarget.textContent = currentWord.substring(0, charIndex + 1);
      charIndex++;

      if (charIndex === currentWord.length) {
        deleting = true;
        setTimeout(typeText, 1000);
      } else {
        setTimeout(typeText, 70);
      }
    } else {
      typingTarget.textContent = currentWord.substring(0, charIndex - 1);
      charIndex--;

      if (charIndex === 0) {
        deleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        setTimeout(typeText, 300);
      } else {
        setTimeout(typeText, 35);
      }
    }
  }

  setTimeout(typeText, 500);
});
document.addEventListener('DOMContentLoaded', function () {

  const reducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;


  /* =========================================
     1. SCROLL REVEAL
  ========================================= */

  const revealElements = document.querySelectorAll('.qt-reveal');

  if (reducedMotion || !('IntersectionObserver' in window)) {

    revealElements.forEach(function (element) {
      element.classList.add('qt-visible');
    });

  } else {

    const revealObserver = new IntersectionObserver(
      function (entries, observer) {

        entries.forEach(function (entry) {

          if (entry.isIntersecting) {

            entry.target.classList.add('qt-visible');

            observer.unobserve(entry.target);
          }

        });

      },
      {
        threshold: 0.12
      }
    );

    revealElements.forEach(function (element) {
      revealObserver.observe(element);
    });

  }


  /* =========================================
     2. TYPING TEXT
  ========================================= */

  const typingTarget = document.getElementById('qtTypingText');

  if (typingTarget) {

    const words = [
      'chinh phục đỉnh cao.',
      'làm chủ kiến thức.',
      'luyện tập mỗi ngày.',
      'thử thách bản thân.',
      'tiến bộ thông minh.'
    ];

    let wordIndex = 0;
    let charIndex = 0;
    let deleting = false;

    typingTarget.textContent = '';

    function typingAnimation() {

      const currentWord = words[wordIndex];

      if (!deleting) {

        typingTarget.textContent =
          currentWord.substring(0, charIndex + 1);

        charIndex++;

        if (charIndex === currentWord.length) {

          deleting = true;

          setTimeout(typingAnimation, 1600);

        } else {

          setTimeout(typingAnimation, 65);
        }

      } else {

        typingTarget.textContent =
          currentWord.substring(0, charIndex - 1);

        charIndex--;

        if (charIndex === 0) {

          deleting = false;

          wordIndex++;

          if (wordIndex >= words.length) {
            wordIndex = 0;
          }

          setTimeout(typingAnimation, 350);

        } else {

          setTimeout(typingAnimation, 35);
        }

      }

    }

    if (reducedMotion) {

      typingTarget.textContent = words[0];

    } else {

      setTimeout(typingAnimation, 500);
    }

  }


  /* =========================================
     3. COUNTER ANIMATION
  ========================================= */

  const counters = document.querySelectorAll('.qt-counter');

  let counterStarted = false;

  function startCounters() {

    if (counterStarted) {
      return;
    }

    counterStarted = true;

    counters.forEach(function (counter) {

      const target = parseInt(
        counter.getAttribute('data-target') || '0',
        10
      );

      if (reducedMotion || target <= 0) {

        counter.textContent =
          target.toLocaleString('vi-VN');

        return;
      }

      let startTime = null;

      function updateCounter(timestamp) {

        if (!startTime) {
          startTime = timestamp;
        }

        const progress = Math.min(
          (timestamp - startTime) / 1000,
          1
        );

        const ease =
          1 - Math.pow(1 - progress, 3);

        const value =
          Math.floor(target * ease);

        counter.textContent =
          value.toLocaleString('vi-VN');

        if (progress < 1) {

          requestAnimationFrame(updateCounter);

        } else {

          counter.textContent =
            target.toLocaleString('vi-VN');
        }

      }

      requestAnimationFrame(updateCounter);

    });

  }


  if (
    counters.length > 0 &&
    'IntersectionObserver' in window
  ) {

    const counterObserver =
      new IntersectionObserver(
        function (entries, observer) {

          entries.forEach(function (entry) {

            if (entry.isIntersecting) {

              startCounters();

              observer.disconnect();
            }

          });

        },
        {
          threshold: 0.25
        }
      );

    counterObserver.observe(counters[0]);

  } else {

    startCounters();
  }


  /* =========================================
     4. EXPERIENCE CARD ITEM ANIMATION
  ========================================= */

  const experienceItems =
    document.querySelectorAll('.qt-exp-item');

  experienceItems.forEach(
    function (item, index) {

      item.style.animationDelay =
        (0.15 + index * 0.13) + 's';

    }
  );


  /* =========================================
     5. PARALLAX / TILT CARD
  ========================================= */

  const experienceCard =
    document.querySelector('.qt-experience-card');

  if (
    experienceCard &&
    !reducedMotion &&
    window.innerWidth > 991
  ) {

    experienceCard.addEventListener(
      'mousemove',
      function (event) {

        const rect =
          experienceCard.getBoundingClientRect();

        const x =
          event.clientX - rect.left;

        const y =
          event.clientY - rect.top;

        const centerX =
          rect.width / 2;

        const centerY =
          rect.height / 2;

        const rotateX =
          ((y - centerY) / centerY) * -2;

        const rotateY =
          ((x - centerX) / centerX) * 2;

        experienceCard.style.transform =
          'perspective(1000px)' +
          ' rotateX(' + rotateX + 'deg)' +
          ' rotateY(' + rotateY + 'deg)' +
          ' translateY(-5px)';
      }
    );

    experienceCard.addEventListener(
      'mouseleave',
      function () {

        experienceCard.style.transform = '';

      }
    );

  }


  /* =========================================
     6. BUTTON RIPPLE
  ========================================= */

  const heroButtons =
    document.querySelectorAll('.qt-hero-btn');

  heroButtons.forEach(function (button) {

    button.addEventListener(
      'mouseenter',
      function () {

        button.classList.add('qt-btn-hover');

      }
    );

    button.addEventListener(
      'mouseleave',
      function () {

        button.classList.remove('qt-btn-hover');

      }
    );

  });


  /* =========================================
     7. CONTACT FORM
  ========================================= */

  const form =
    document.getElementById('qtContactForm');

  if (form) {

    form.addEventListener(
      'submit',
      function (event) {

        event.preventDefault();

        const name =
          document.getElementById('qtName')
            .value
            .trim();

        const email =
          document.getElementById('qtEmail')
            .value
            .trim();

        const subject =
          document.getElementById('qtSubject')
            .value
            .trim();

        const message =
          document.getElementById('qtMessage')
            .value
            .trim();

        const mailSubject =
          '[QuizTech] ' + subject;

        const mailBody =
          'Họ tên: ' + name +
          '\nEmail: ' + email +
          '\n\n' +
          message;

        window.location.href =
          'mailto:pltsolutions3010@gmail.com' +
          '?subject=' +
          encodeURIComponent(mailSubject) +
          '&body=' +
          encodeURIComponent(mailBody);

      }
    );

  }

});
</script>