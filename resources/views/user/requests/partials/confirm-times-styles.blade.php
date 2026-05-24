<style>
  .picker-card { background: white; border-radius: 1rem; border: 1px solid #e6e0ea; box-shadow: 0 4px 24px rgba(49,15,122,0.06); padding: 1rem; }
  .picker-split { display: flex; flex-direction: column; gap: 1rem; }
  @media (min-width: 640px) {
    .picker-split { flex-direction: row; align-items: stretch; gap: 0; min-height: 11rem; }
    .picker-dates-col { flex: 0 0 38%; max-width: 15rem; padding-right: 1rem; border-right: 1px solid rgba(202, 196, 211, 0.45); }
    .picker-times-col { flex: 1; min-width: 0; padding-left: 1rem; display: flex; flex-direction: column; }
  }
  .picker-step-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #7a7583; margin-bottom: 0.65rem; }
  .offered-dates-list { display: flex; flex-direction: column; gap: 0.45rem; max-height: 11rem; overflow-y: auto; padding-right: 0.25rem; }
  .offered-date-btn { display: flex; flex-direction: column; align-items: flex-start; width: 100%; padding: 0.6rem 0.85rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3; background: white; cursor: pointer; transition: all 0.15s; text-align: left; }
  .offered-date-btn:hover { border-color: #310f7a; background: #f8f1fb; }
  .offered-date-btn.selected { background: #310f7a; border-color: #310f7a; color: white; }
  .offered-date-btn.selected .offered-date-sub { color: rgba(255,255,255,0.85); }
  .offered-date-main { font-size: 0.85rem; font-weight: 700; line-height: 1.2; }
  .offered-date-sub { font-size: 0.68rem; font-weight: 500; color: #7a7583; margin-top: 0.1rem; }
  .picker-times-filled { display: flex; flex-direction: column; flex: 1; min-height: 0; }
  .picker-times-scroll { max-height: 9.5rem; overflow-y: auto; padding-right: 0.25rem; }
  .picker-times-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; min-height: 9rem; text-align: center; color: #7a7583; padding: 0.5rem; }
  .time-slot-card { padding: 0.75rem 1.25rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.15s; text-align: center; color: #310f7a; background: white; width: 100%; }
  .time-slot-card:hover { border-color: #310f7a; background: #f8f1fb; }
  .time-slot-card.selected { background: #310f7a; color: white; border-color: #310f7a; }
  .confirm-chip { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; border-radius: 9999px; background: #f0fdf4; color: #15803d; border: 1px solid rgba(34, 197, 94, 0.35); font-size: 0.8rem; font-weight: 600; }
  .offer-section--session { background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border: 1px solid rgba(34, 197, 94, 0.28); border-radius: 1rem; padding: 1.25rem; }
  .cal-card { background: white; border-radius: 1rem; border: 1px solid #e6e0ea; overflow: hidden; box-shadow: 0 4px 24px rgba(49,15,122,0.06); }
  .cal-day { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; cursor: pointer; transition: all 0.15s; }
  .cal-day.available:hover { background: #ece6ef; }
  .cal-day.available { color: #1c1b21; font-weight: 600; }
  .cal-day.unavailable { color: #cac4d3; cursor: default; pointer-events: none; }
  .cal-day.unavailable-future {
    color: #ba1a1a;
    background: #fff1f1;
    text-decoration: line-through;
    text-decoration-thickness: 2px;
    text-decoration-color: #ba1a1a;
    cursor: default;
    pointer-events: none;
    font-weight: 600;
  }
  .cal-day.blocked-by-artist {
    color: #5c4033;
    background: #f4ebe4;
    cursor: default;
    pointer-events: none;
    font-weight: 600;
    font-size: 0.72rem;
  }
  .cal-day.fully-booked-day {
    color: #494552;
    background: #ece8f0;
    cursor: default;
    pointer-events: none;
    font-weight: 600;
    font-size: 0.72rem;
  }
  .cal-day.selected { background: #310f7a; color: white; font-weight: 700; }
  .cal-day.today { border: 2px solid #310f7a; }
  .cal-day.empty { pointer-events: none; }
  .time-slot-card { padding: 0.75rem 1.25rem; border-radius: 0.75rem; border: 1.5px solid #cac4d3; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.15s; text-align: center; color: #310f7a; background: white; width: 100%; }
  .time-slot-card:hover { border-color: #310f7a; background: #f8f1fb; }
  .time-slot-card.selected { background: #310f7a; color: white; border-color: #310f7a; }
  .time-slot-card.booked { background: #f2ecf5; color: #cac4d3; cursor: default; border-color: transparent; pointer-events: none; }
  .consult-type-card { padding: 1.25rem; border-radius: 1rem; border: 2px solid #cac4d3; cursor: pointer; transition: all 0.2s; background: white; text-align: left; width: 100%; }
  .consult-type-card:hover { border-color: #310f7a; background: #f8f1fb; }
  .consult-type-card.selected { border-color: #310f7a; background: #f8f1fb; box-shadow: 0 0 0 1px #310f7a; }
  .consult-type-card .ct-icon { width: 44px; height: 44px; border-radius: 12px; background: #f2ecf5; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
  .consult-type-card.selected .ct-icon { background: #310f7a; }
  .consult-type-card .ct-icon .material-symbols-outlined { color: #310f7a; font-size: 22px; }
  .consult-type-card.selected .ct-icon .material-symbols-outlined { color: white; }
</style>
