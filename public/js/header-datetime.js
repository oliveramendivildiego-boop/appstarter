/**
 * Reloj del header: fecha y hora en la zona y formato configurados (app_config).
 */
(function () {
  'use strict';

  var TICK_MS_DEFAULT = 30000;
  var TICK_MS_SECONDS = 1000;

  function partsMap(date, tz, opts) {
    var dtf = new Intl.DateTimeFormat('es', Object.assign({
      timeZone: tz,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }, opts || {}));
    var parts = {};
    dtf.formatToParts(date).forEach(function (p) {
      if (p.type !== 'literal') {
        parts[p.type] = p.value;
      }
    });
    return parts;
  }

  function formatByKey(date, tz, formatKey) {
    try {
      var p;
      switch (formatKey) {
        case 'dmY_his':
          p = partsMap(date, tz, { second: '2-digit' });
          return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute + ':' + (p.second || '00');
        case 'dm_hi':
          p = partsMap(date, tz, {});
          return p.day + '/' + p.month + ' ' + p.hour + ':' + p.minute;
        case 'ymd_hi':
          p = partsMap(date, tz, {});
          return p.year + '-' + p.month + '-' + p.day + ' ' + p.hour + ':' + p.minute;
        case 'dmY_hi_12':
          p = partsMap(date, tz, { hour12: true });
          return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute + ' ' + (p.dayPeriod || '');
        case 'long_es':
          return new Intl.DateTimeFormat('es', {
            timeZone: tz,
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
          }).format(date);
        case 'dmY_hi':
        default:
          p = partsMap(date, tz, {});
          return p.day + '/' + p.month + '/' + p.year + ' ' + p.hour + ':' + p.minute;
      }
    } catch (e) {
      return '';
    }
  }

  function tick() {
    var el = document.getElementById('header-datetime');
    if (!el) {
      return;
    }
    var tz = el.getAttribute('data-timezone') || 'UTC';
    var formatKey = el.getAttribute('data-format') || 'dmY_hi';
    var text = formatByKey(new Date(), tz, formatKey);
    if (text) {
      el.textContent = text;
    }
    try {
      el.setAttribute('datetime', new Date().toISOString());
    } catch (err) {
      /* ignore */
    }
  }

  function tickIntervalMs(el) {
    return el.getAttribute('data-has-seconds') === '1' ? TICK_MS_SECONDS : TICK_MS_DEFAULT;
  }

  function init() {
    var el = document.getElementById('header-datetime');
    if (!el) {
      return;
    }
    tick();
    window.setInterval(tick, tickIntervalMs(el));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
