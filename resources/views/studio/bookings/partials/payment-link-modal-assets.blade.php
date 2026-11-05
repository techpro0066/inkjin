{{-- Shared Select2 + New booking modal assets for studio bookings tabs --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
@include('artist.partials.payment-link-modal-styles')
<style>
  #m-pl #pl-artw{margin-bottom:10px}
  #m-pl #pl-artw + .pl-lb{margin-top:22px}
  #m-pl #pl-artf:has(> select.js-select2),
  #m-pl #pl-artf:has(> .select2-container){
    display:block;border:0;background:transparent;box-shadow:none;padding:0;min-height:0
  }
  #m-pl #pl-artf:focus-within{border:0;box-shadow:none}
  #m-pl select.js-select2{width:100%}
  #m-pl .select2-container{width:100%!important}
  #m-pl .select2-container--open{z-index:40!important}
  #m-pl .select2-container--default .select2-selection--single{
    min-height:42px;padding:4px 12px;border-radius:10px;border:1px solid #E6DEE9!important;background:#fff!important
  }
  #m-pl .select2-container--default .select2-selection--single .select2-selection__rendered{
    line-height:32px;padding-left:2px;color:#1A1A1A;font-size:14px;font-family:inherit
  }
  #m-pl .select2-container--default .select2-selection--single .select2-selection__placeholder{color:#8F8A95}
  #m-pl .select2-container--default .select2-selection--single .select2-selection__arrow{height:40px;right:8px}
  #m-pl .select2-container--default.select2-container--focus .select2-selection--single,
  #m-pl .select2-container--default.select2-container--open .select2-selection--single{
    border-color:#3E007C!important;box-shadow:0 0 0 3px #F3E8FF
  }
  #m-pl #pl-artf.pl-bad .select2-container--default .select2-selection--single,
  #m-pl .select2-container--default .select2-selection--single.s2-err{
    border-color:#C62828!important;box-shadow:0 0 0 3px #FDECEC
  }
  #m-pl .select2-dropdown{border-radius:10px;border-color:#E6DEE9;overflow:hidden;font-family:inherit;font-size:14px;z-index:360}
  #m-pl .select2-container--default .select2-results__option--highlighted[aria-selected]{background-color:#3E007C!important}
  #m-pl .select2-container--default .select2-search--dropdown .select2-search__field{
    border-radius:8px;border-color:#E6DEE9;padding:8px 10px;font:inherit
  }
</style>
