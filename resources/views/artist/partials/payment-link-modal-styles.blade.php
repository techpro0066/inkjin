{{-- Payment link create modal styles (shared: home + payment links) --}}
<style>
  /* Payment link popup — matches 01-home template (#m-pl) */
  #m-pl{position:fixed;inset:0;background:rgba(20,10,30,.45);display:flex;align-items:center;justify-content:center;z-index:300;padding:24px}
  #m-pl[hidden],#m-pl [hidden]{display:none!important}
  #m-pl .pl-pb{background:#fff;border-radius:18px;width:560px;max-width:100%;max-height:calc(100vh - 48px);max-height:calc(100dvh - 48px);display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.25);font-size:13.5px;color:#1A1A1A}
  #m-pl .pl-ph{display:flex;gap:12px;align-items:flex-start;padding:20px 24px 14px;border-bottom:1px solid #EFE8F1;flex-shrink:0}
  #m-pl .pl-ph h2{font-size:19px;font-weight:800;margin:0}
  #m-pl .pl-ph .pl-ic{width:40px;height:40px;border-radius:12px;background:#F3E8FF;color:#3E007C;display:flex;align-items:center;justify-content:center;flex:none}
  #m-pl .pl-ph .pl-ic .ms{font-size:22px}
  #m-pl .pl-ph .pl-sub{color:#6F6874;font-size:13.5px;margin-top:4px;line-height:1.4}
  #m-pl .pl-px{margin-left:auto;border:0;background:none;cursor:pointer;color:#8F8A95;padding:2px;display:flex;align-items:center;justify-content:center}
  #m-pl .pl-px:hover{color:#1A1A1A}
  #m-pl .pl-pbd{padding:18px 24px;overflow:auto;flex:1;min-height:0}
  #m-pl .pl-pf{display:flex;gap:10px;justify-content:flex-end;padding:14px 24px;border-top:1px solid #EFE8F1;flex-shrink:0}
  #m-pl .pl-pbtn{display:inline-flex;align-items:center;gap:8px;background:#1A1A1A;color:#fff;border-radius:10px;padding:10px 16px;font:inherit;font-weight:600;font-size:13.5px;border:0;cursor:pointer;white-space:nowrap;text-decoration:none}
  #m-pl .pl-pbtn.pl-gh{background:#fff;color:#1A1A1A;border:1px solid #E6DEE9}
  #m-pl .pl-pbtn:disabled{opacity:.45;cursor:not-allowed}
  #m-pl .pl-pbtn .ms{font-size:18px}
  #m-pl .pl-lb{display:block;font-size:13px;font-weight:700;margin:16px 0 6px}
  #m-pl #pl-form>.pl-lb:first-of-type{margin-top:0}
  #m-pl .pl-lb i{color:#C62828;font-style:normal}
  #m-pl .pl-fi{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid #E6DEE9;border-radius:10px;padding:0 13px;min-height:42px}
  #m-pl .pl-fi:focus-within{border-color:#3E007C;box-shadow:0 0 0 3px #F3E8FF}
  #m-pl .pl-fi input,#m-pl .pl-fi input:focus,#m-pl .pl-fi input:focus-visible{
    border:0!important;outline:0!important;box-shadow:none!important;
    --tw-ring-shadow:0 0 #0000;--tw-ring-offset-shadow:0 0 #0000;
    font:inherit;font-size:14px;flex:1;min-width:0;background:transparent!important;padding:10px 0;color:#1A1A1A;
    -webkit-appearance:none;appearance:none
  }
  #m-pl .pl-fi select{
    border:0;outline:0;font:inherit;font-size:14px;flex:1;min-width:0;background:none;padding:10px 0;color:#1A1A1A;
    appearance:auto;cursor:pointer
  }
  #m-pl .pl-fi .pl-cur{color:#8F8A95;font-weight:600}
  #m-pl .pl-fi.pl-bad{border-color:#C62828}
  #m-pl .pl-fi.pl-ro{background:#F7F4F8}
  #m-pl .pl-fi.pl-ro input,#m-pl .pl-fi.pl-ro select{color:#6F6874}
  #m-pl .pl-fi.pl-dt{cursor:pointer;justify-content:space-between}
  #m-pl .pl-fi.pl-dt .pl-ph0{color:#8F8A95;flex:1}
  #m-pl .pl-fi .ms{color:#8F8A95;font-size:19px}
  #m-pl .pl-ph h2{display:inline}
  #m-pl .pl-expw{font-size:13px;padding:9px 0;color:#6F6874}
  #m-pl .pl-slots{font-size:12px;color:#8F8A95;margin-top:6px}
  #m-pl #pl-artw>.pl-lb:first-child{margin-top:0}
  #m-pl .pl-seg{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
  #m-pl .pl-seg button{font:inherit;text-align:center;padding:10px;border-radius:12px;font-weight:600;border:1px solid #E8DFEA;background:#fff;cursor:pointer;display:flex;gap:6px;align-items:center;justify-content:center;color:#1A1A1A}
  #m-pl .pl-seg button .ms{font-size:18px}
  #m-pl .pl-seg button.pl-on{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
  #m-pl .pl-chips{display:flex;flex-wrap:wrap;gap:8px}
  #m-pl .pl-chips button{font:inherit;font-size:13px;font-weight:600;padding:7px 13px;border-radius:20px;border:1px solid #E8DFEA;background:#fff;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-chips button.pl-on{background:#F3E8FF;border-color:#3E007C;color:#3E007C}
  #m-pl .pl-note{background:#F7F3F9;border-radius:10px;padding:9px 12px;font-size:12.5px;color:#4A4450;margin-top:8px;display:flex;gap:8px;align-items:flex-start;line-height:1.45}
  #m-pl .pl-note .ms{font-size:17px;color:#6A2BB8;flex:none}
  #m-pl .pl-err{color:#C62828;font-size:12.5px;margin-top:6px}
  #m-pl .pl-g2{display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start}
  #m-pl .pl-sum{border:1px solid #EFE8F1;border-radius:12px;margin-top:16px;padding:10px 14px}
  #m-pl .pl-sum .pl-r{display:flex;justify-content:space-between;padding:4px 0}
  #m-pl .pl-sum .pl-r b{font-weight:700}
  #m-pl .pl-sum .pl-hint{color:#8F8A95;font-size:12.5px;text-align:center;margin-top:4px}
  #m-pl .pl-warn{background:#FBF3E2;border-radius:10px;padding:10px 12px;font-size:12.5px;color:#6B5320;line-height:1.45;margin-bottom:4px}
  #m-pl .pl-warn a{color:#3E007C;font-weight:700;text-decoration:none}
  #m-pl .pl-cal{border:1px solid #E6DEE9;border-radius:12px;margin-top:8px;padding:12px}
  #m-pl .pl-calh{display:flex;justify-content:space-between;align-items:center;font-size:12.5px;color:#6F6874;margin-bottom:8px}
  #m-pl .pl-calh a{color:#3E007C;font-weight:600;cursor:pointer}
  #m-pl .pl-mh{display:flex;align-items:center;justify-content:space-between;margin:2px 0 6px}
  #m-pl .pl-mh b{font-size:14px}
  #m-pl .pl-mh button{width:34px;height:34px;border:0;background:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#1A1A1A}
  #m-pl .pl-mh button:hover{background:#F7F3F9}
  #m-pl .pl-mh button:disabled{opacity:.3;cursor:default;background:none}
  #m-pl .pl-mg{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
  #m-pl .pl-mg i{font-style:normal;font-size:11px;font-weight:700;color:#8F8A95;text-align:center;padding:3px 0}
  #m-pl .pl-mg button{height:36px;border:0;background:none;border-radius:9px;font:inherit;font-size:13.5px;font-weight:700;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-mg button:hover{background:#F3E8FF}
  #m-pl .pl-mg button:disabled{font-weight:400;color:#CFC8D3;cursor:default;background:none}
  #m-pl .pl-mg button.pl-out{visibility:hidden}
  #m-pl .pl-mg button.pl-td{box-shadow:inset 0 0 0 1.5px #3E007C}
  #m-pl .pl-mg button.pl-on{background:#3E007C;color:#fff}
  #m-pl .pl-tlab{font-size:12.5px;font-weight:700;margin:12px 0 0}
  #m-pl .pl-times{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:10px}
  #m-pl .pl-times button{font:inherit;font-size:12.5px;font-weight:600;padding:8px 0;border-radius:8px;border:1px solid #E8DFEA;background:#fff;cursor:pointer;color:#1A1A1A}
  #m-pl .pl-times button:disabled{background:#F4F1F5;color:#B8B0BC;text-decoration:line-through;cursor:default;border-color:#F4F1F5}
  #m-pl .pl-times button.pl-on{background:#3E007C;color:#fff;border-color:#3E007C}
  #m-pl .pl-closed{font-size:12.5px;color:#8F8A95;padding:10px 0;text-align:center}
  #m-pl .pl-qr{display:flex;justify-content:center;padding:16px;background:#fff;border:1px solid #EFE8F1;border-radius:16px;width:max-content;margin:4px auto 12px}
  #m-pl .pl-qr img,#m-pl .pl-qr svg{width:168px;height:168px;display:block}
  #m-pl .pl-lnk{display:flex;gap:8px;align-items:center}
  #m-pl .pl-lnk .pl-fi{flex:1;min-width:0}
  #m-pl .pl-lnk .pl-fi span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13.5px;color:#1A1A1A}
  @media (max-width:560px){
    #m-pl{padding:0;align-items:flex-end}
    #m-pl .pl-pb{width:100%;max-height:92vh;border-radius:18px 18px 0 0}
    #m-pl .pl-g2{grid-template-columns:1fr}
    #m-pl .pl-pf .pl-pbtn{flex:1;justify-content:center}
    #m-pl .pl-times{grid-template-columns:repeat(4,1fr)}
  }
</style>
