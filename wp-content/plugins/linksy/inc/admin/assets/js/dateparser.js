let DateParser = function(global) {

    let DateParser = {lang: 'en-GB'};
  
    // Format tokens and functions
    let tokens = {
    
      // DAY
      // day of month, pad to 2 digits
      d: () => 'DD',
      // Day name, first 3 letters
      D: () => 'ddd',
      // day of month, no padding
      j: () => 'D',
      // Full day name
      l: () => 'dddd',
      // ISO weekday number (1 = Monday ... 7 = Sunday)
      N: () => 'E',
      // Ordinal suffix for day of the month
      S: () => 'o',
      // Weekday number (0 = Sunday, 6 = Saturday)
      // w: () => d.getDay(),
      // // Day of year, 1 Jan is 0
      // z: () => {
      //   let Y = d.getFullYear(),
      //       M = d.getMonth(),
      //       D = d.getDate();
      //   return Math.floor((Date.UTC(Y, M, D) - Date.UTC(Y, 0, 1)) / 8.64e7) ;
      // },
      // // ISO week number of year
      // W: () => getWeekNumber(d)[1],
      // Full month name
      F: () => 'MMMM',
      // Month number, padded
      m: () => 'M',
      // 3 letter month name
      M: () => 'MMM',
      // // Month number, no pading
      // n: () => d.getMonth() + 1,
      // // Days in month
      // t: () => new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate(),
      // // Return 1 if d is a leap year, otherwise 0
      // L: () => new Date(d.getFullYear(), 1, 29).getDate() == 29? 1 : 0,
      // // ISO week numbering year
      // o: () => getWeekNumber(d)[0],
      // 4 digit year
      Y: () => 'YYYY',
      // 2 digit year
      y: () => 'YY',
      // // Lowercase am or pm
      a: () => 'a',
      // // Uppercase AM or PM
      A: () => 'A',
      // // Swatch internet time
      // B: () => (((+d + 3.6e6) % 8.64e7) / 8.64e4).toFixed(0),
      // // 12 hour hour no padding
      // g: () => (d.getHours() % 12) || 12,
      // // 24 hour hour no padding
      // G: () => d.getHours(),
      // // 12 hour hour padded
      // h: () => pad((d.getHours() % 12) || 12),
      // // 24 hour hour padded
      // H: () => pad(d.getHours()),
      // // Minutes padded
      // i: () => pad(d.getMinutes()),
      // // Seconds padded
      // s: () => pad(d.getSeconds()),
      // // Microseconds padded - always returns 000000
      // u: () => '000000',
      // // Milliseconds
      // v: () => padd(d.getMilliseconds()),
      // // Timezone identifier: UTC, GMT or IANA Tz database identifier - Not supported
      // e: () => void 0,
      // // If in daylight saving: 1 yes, 0 no
      // I: () => d.getTimezoneOffset() == getOffsets(d)[0]? 0 : 1,
      // // Difference to GMT in hours, e.g. +0200
      // O: () => minsToHours(-d.getTimezoneOffset(), false),
      // // Difference to GMT in hours with colon, e.g. +02:00
      // P: () => minsToHours(-d.getTimezoneOffset(), true),
      // // Timezone abbreviation, e.g. AEST. Dodgy but may work…
      // T: () => d.toLocaleString('en',{year:'numeric',timeZoneName:'long'}).replace(/[^A-Z]/g, ''),
      // // Timezone offset in seconds, +ve east
      // Z: () => d.getTimezoneOffset() * -60,
      
      // // ISO 8601 format - UTC
      // // c: () => d.getUTCFullYear() + '-' + pad(d.getUTCMonth() + 1) + '-' + pad(d.getUTCDate()) +
      // //        'T' + pad(d.getUTCHours()) + ':' + pad(d.getUTCMinutes()) + ':' + pad(d.getUTCSeconds()) +
      // //        '+00:00',
      
      // // ISO 8601 format - local
      // c: () => DateParser.format(d, 'Y-m-d\\TH:i:sP'),
      // // RFC 2822 formatted date, local timezone
      // r: () => DateParser.format(d, 'D, d M Y H:i:s O'),
      // // Seconds since UNIX epoch (same as ECMAScript epoch)
      // U: () => d.getTime() / 1000 | 0
    };
    
    // Helpers
    // Return true if o is a Date, otherwise false
    let isDate = o => Object.prototype.toString.call(o) == '[object Date]';

    // Parse date to momnet
    let parse = (s, shorthand) => {
      return s.split('').reduce((acc, c, i, chars) => {
        // Add quoted characters to output
        if (c == '\\') {
          acc += chars.splice(i+1, 1);
        // If character matches a token, use it
        } else if (c in tokens) {
          acc += tokens[c](shorthand);
        // Otherwise, just add character to output
        } else {
          acc += c;
        }
        return acc;
      }, '');
    };
  
    DateParser.parse = parse;
    
    // Format date using token string s
    function format(date, s) {
      // Minimal input validation
      if (!isDate(date) || typeof s != 'string') {
        return; // undefined
      }
  
      return 'inprogress';
    }
    DateParser.format = format;
    
    return DateParser;
  }(this);