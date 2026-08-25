<style>
  .inkjin-time-wrap {
    position: relative;
    display: block;
    overflow: hidden;
    border: 1px solid rgba(202, 196, 211, 0.45);
    border-radius: 0.75rem;
    background: #fff;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
  }

  .inkjin-time-wrap:focus-within {
    border-color: transparent;
    box-shadow: 0 0 0 2px rgba(49, 15, 122, 0.3);
  }

  .inkjin-time-wrap.border-error,
  .inkjin-time-wrap.is-invalid,
  .inkjin-time-wrap:has(.is-invalid),
  .inkjin-time-wrap:has(.border-error) {
    border-color: #ba1a1a;
  }

  .inkjin-time-wrap.border-error:focus-within,
  .inkjin-time-wrap.is-invalid:focus-within,
  .inkjin-time-wrap:has(.is-invalid):focus-within,
  .inkjin-time-wrap:has(.border-error):focus-within {
    box-shadow: 0 0 0 2px rgba(186, 26, 26, 0.25);
  }

  .inkjin-time-input {
    min-width: 7rem;
    color-scheme: light;
    border: 0 !important;
    box-shadow: none !important;
    outline: none !important;
    /* Clip Firefox’s built-in clock button (no styleable pseudo-element) */
    width: calc(100% + 2.75rem) !important;
    max-width: none;
    margin-right: -2.75rem;
  }

  /* Hide default clock / picker icon on Chrome, Edge, Safari, Opera */
  input[type="time"].inkjin-time-input::-webkit-calendar-picker-indicator {
    display: none !important;
    -webkit-appearance: none !important;
    appearance: none !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    margin: 0 !important;
    padding: 0 !important;
    pointer-events: none !important;
  }

  input[type="time"].inkjin-time-input::-webkit-inner-spin-button,
  input[type="time"].inkjin-time-input::-webkit-clear-button {
    display: none !important;
    -webkit-appearance: none !important;
  }

  /* Firefox */
  input[type="time"].inkjin-time-input {
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: textfield;
  }

  input[type="time"].inkjin-time-input::-moz-focus-inner {
    border: 0;
    padding: 0;
  }

  /* Legacy Edge / IE */
  input[type="time"].inkjin-time-input::-ms-clear,
  input[type="time"].inkjin-time-input::-ms-reveal {
    display: none;
    width: 0;
    height: 0;
  }
</style>
