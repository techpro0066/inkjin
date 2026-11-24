<style>
  .account-plan-card { border: 1px solid #eadcf0; border-radius: 20px; background: #fff; padding: 24px 28px 26px; }
  .account-plan-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; }
  .account-plan-eyebrow { margin: 0 0 8px; color: #77707f; font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
  .account-plan-title-row { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
  .account-plan-title-row h2 { margin: 0; color: #17131b; font-size: 28px; font-weight: 800; letter-spacing: -.03em; line-height: 1.15; }
  .account-plan-badge, .account-plan-current { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 5px 11px; font-size: 12px; font-weight: 700; white-space: nowrap; }
  .account-plan-badge { background: #f0e8ff; color: #3e007c; }
  .account-plan-badge svg { flex: none; }
  .account-plan-current { background: #efedf0; color: #4b4452; }
  .account-plan-description { margin: 10px 0 16px; color: #4b4452; font-size: 15px; line-height: 1.5; }
  .account-plan-terms { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
  .account-plan-term { min-height: 66px; border-radius: 14px; background: #f7f2fc; padding: 11px 14px; }
  .account-plan-term strong, .account-plan-term span { display: block; }
  .account-plan-term strong { color: #19151d; font-size: 15px; font-weight: 800; line-height: 1.35; }
  .account-plan-term span { margin-top: 2px; color: #3f3848; font-size: 14px; line-height: 1.4; }
  .account-plan-note { margin: 16px 0 0; color: #77707f; font-size: 14px; line-height: 1.55; }
  .account-plan-empty { max-width: 620px; color: #77707f; font-size: 14px; line-height: 1.6; }
  .account-plan-empty h2 { margin: 0 0 8px; color: #17131b; font-size: 22px; font-weight: 800; }
  .account-plan-empty p { margin: 0; }
  @media (max-width: 900px) {
    .account-plan-terms { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  }
  @media (max-width: 700px) {
    .account-plan-card { padding: 20px 18px; }
    .account-plan-header { flex-direction: column; gap: 8px; }
  }
  @media (max-width: 440px) {
    .account-plan-terms { grid-template-columns: 1fr; }
    .account-plan-title-row h2 { font-size: 25px; }
  }
</style>
