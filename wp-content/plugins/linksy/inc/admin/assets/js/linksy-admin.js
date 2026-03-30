const socket = io(LINKSY.socket_url);

function delay(ms) {
	return new Promise((res) => setTimeout(res, ms));
}

function debounce(func, ms) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		var later = function() {
			timeout = null;
			if (ms) func.apply(context, args);
		};
		clearTimeout(timeout);
		timeout = setTimeout(later, ms);
		if (!ms) func.apply(context, args);
	};
}

function trim(str, ch) {
    if (!ch) {
       return  str.trim().replace(/(\r\n|\n|\r)/gm, "");
    }

    var start = 0, 
        end = str.length;

    while(start < end && str[start] === ch)
        ++start;

    while(end > start && str[end - 1] === ch)
        --end;

    return (start > 0 || end < str.length) ? str.substring(start, end) : str;
}

function ordinalSuffix(i) {
    var j = i % 10,
        k = i % 100;
    if (j == 1 && k != 11) {
        return i + "st";
    }
    if (j == 2 && k != 12) {
        return i + "nd";
    }
    if (j == 3 && k != 13) {
        return i + "rd";
    }
    return i + "th";
}

function escapeRegExp(string) {
	return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}

function urlSearchParams(obj) {
	var params = new URLSearchParams();

	for(k in obj) {
		params.append(k, obj[k]);
	}

	return params;
}

function toQueryString(obj) {
    function flattenObj(x, path) {
        const result = [];

        path = path || [];
        if (x) {
            Object.keys(x).forEach(function (key) {
                if (
                  x[key] === '' ||
                  typeof x[key] === 'undefined' ||
                  key.startsWith('X-')
                ) {
                  return;
                }
          
                const newPath = path.slice();
                newPath.push(key);
          
                let vals = [];
                if (typeof x[key] === 'object') {
                  vals = flattenObj(x[key], newPath);
                } else {
                  vals.push({path: newPath, val: x[key]});
                }
                vals.forEach(function (e) {
                  return result.push(e);
                });
            });
        }

        return result;
    } // flattenObj

  // start with  flattening `obj`
  let parts = flattenObj({...obj}); // [ { path: [ ...parts ], val: ... }, ... ]

  // convert to array notation:
  parts = parts.map(function (varInfo) {
    if (varInfo.path.length === 1) {
      varInfo.path = varInfo.path[0];
    } else {
      const first = varInfo.path[0];
      const rest = varInfo.path.slice(1);
      varInfo.path = first + '[' + rest.join('][') + ']';
    }
    return varInfo;
  }); // parts.map

  // join the parts to a query-string url-component
  const queryString = parts
    .map(function (varInfo) {
      return varInfo.path + '=' + varInfo.val;
    })
    .join('&');
    return queryString;
}

function download(props) {
    const { content, type, name } = props;
  
    const file = new Blob(['\ufeff', content], { type });
  
    const link = document.createElement('a');
  
    link.id = `_linksy_d_${name}`;
    link.download = name;
    link.href = window.URL.createObjectURL(file);
  
    document.body.appendChild(link);
  
    link.click();
  
    document.getElementById(link.id).remove();
}
  
function print(table) {
    const printWindow = window.open();
    printWindow.document.write(table);
    printWindow.print();
    printWindow.close();
}

function copyToClipboard(str) {
    const el = document.createElement('textarea');  // Create a <textarea> element
    el.value = str;                                 // Set its value to the string that you want copied
    el.setAttribute('readonly', '');                // Make it readonly to be tamper-proof
    el.style.position = 'absolute';                 
    el.style.left = '-9999px';                      // Move outside the screen to make it invisible
    document.body.appendChild(el);                  // Append the <textarea> element to the HTML document
    const selected =            
    document.getSelection().rangeCount > 0        // Check if there is any content selected previously
      ? document.getSelection().getRangeAt(0)     // Store selection if found
      : false;                                    // Mark as false to know no selection existed before
    el.select();                                    // Select the <textarea> content
    document.execCommand('copy');                   // Copy - only works as a result of a user action (e.g. click events)
    document.body.removeChild(el);                  // Remove the <textarea> element
    if (selected) {                                 // If a selection existed before copying
        document.getSelection().removeAllRanges();    // Unselect everything on the HTML document
        document.getSelection().addRange(selected);   // Restore the original selection
    }
}


/**
 * semantic
 */


function scoreToColor(score) {
    score = Math.round(( parseFloat(score) + Number.EPSILON) * 100);

    if (score  < 30) {
        return 'rgb(255,60,32)';
    } else if (score  >= 30 && score < 50) {
        return 'rgb(255,165,0)';
    } else if (score >= 50 && score < 70) {
        return 'rgb(14,165,233)';
    } else {
        return 'rgb(16,185,129)';
    }
}

function scoreToTag(score) {
    if (score == null) {
        return 'no keyword';
    }

    score = Math.round(( parseFloat(score) + Number.EPSILON) * 100);

    if (score  < 30) {
        return 'poor';
    } else if (score  >= 30 && score < 50) {
        return 'average';
    } else if (score >= 50 && score < 70) {
        return 'good';
    } else {
        return 'great';
    }
}

function tagToColor(tag) {
    let color = '';
    switch (tag.toLowerCase()) {
        case 'poor':
            color = 'rgb(255,60,32)';
            break;
        case 'average':
            color = 'rgb(255,165,0)';
            break;
        case 'good':
            color = 'rgb(14,165,233)';
            break;
        case 'great':
            color = 'rgb(16,185,129)';
            break;
    }

    return color;
}

function tagToScore(tag) {
    let score = null;
    if (tag) {
        switch (tag.toLowerCase()) {
            case 'poor':
                score = [0, 29];
                break;
            case 'average':
                score = [30, 49];
                break;
            case 'good':
                score = [50, 69];
                break;
            case 'great':
                score = [70, 100];
                break;
        }
    }

    return score;
}