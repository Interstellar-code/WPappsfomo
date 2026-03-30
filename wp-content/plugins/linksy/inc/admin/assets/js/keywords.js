class Keyords {
    content = '';
    keywords = [];

    opts = {
        ignoredTags: [
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'a',
            'img'
        ],
        typeToSkip: null,
        typeToSkipCount: 0,
        sameBlock: false,
        selectMultiple: false,
        stoppers: [' ', ',', '.']
    }

    constructor(opts = {}) {
        const {content, keywords, ...others} = opts;

        if (content) {
            this.setContent(content);
        }

        if (keywords) {
            this.setKeywords(keywords);
        }

        this.opts = {...this.opts, ...others};
    }

    setContent(input) {
        this.content = input;
    }

    setKeywords(highlights) {
        this.keywords = [...new Set(highlights)];
    }

    set(input, highlights) {
        this.setContent(input);
        this.setKeywords(highlights);
    }

    getBoundaries() {
        let boundaries = [];
        this.getKeywordsRange().forEach(function(range) {
            boundaries.push({
                type: 'start',
                index: range[0],
                highlight: range[4],
                start: range[2],
                end: range[3]
            });
            boundaries.push({
                type: 'stop',
                index: range[1],
                highlight: range[4],
            });
        });

        boundaries.sort(function(a, b) {
            if (a.index !== b.index) {
                return b.index - a.index;
            } else if (a.type === 'stop' && b.type === 'start') {
                return 1;
            } else if (a.type === 'start' && b.type === 'stop') {
                return -1;
            } else {
                return 0;
            }
        });

        return boundaries;
    }

    getKeywordsRange(startFrom = 0, endAt = null) {
        let ranges = [];
        let content = this.content.toLocaleLowerCase();

        const ignoredTagsRanges = this.getIgnoredTagsRange();

        this.keywords.forEach((keyword) => {
            let error = '';
            let index = startFrom;
            let sharesBoundary = false;

            while (index = content.indexOf(keyword.toLowerCase(), index), !endAt? index !== -1 : index <= endAt) {
                let closingIndex = index + keyword.length - 1;
                let rangeIsValid = true;
                let phrase = ''

                // is whole word
                if ((content[index - 1] && content[index - 1].match(/[a-z]/i) ) || (content[closingIndex + 1] &&  content[closingIndex + 1].match(/[a-z]/i))) {
                    error = 'is not a full word';
                    rangeIsValid = false;
                }

                // skip ignored tags
                if (rangeIsValid) {
                    for (let i= 0; i < ignoredTagsRanges.length; i++) {
                        const ignoredTagRange = ignoredTagsRanges[i];
                        if ((index >= ignoredTagRange[0] && index <= ignoredTagRange[1]) || (closingIndex >= ignoredTagRange[0] && closingIndex <= ignoredTagRange[1])) {
                            rangeIsValid = false;
                            error = ignoredTagRange[2];
                            break;
                        }
                    }
                }

                // skip used ranges and block
                if (rangeIsValid) {
                    for (let i= 0; i < ranges.length; i++) {
                        const usedKeywordRange = ranges[i];
                        
                        // is not immediately after
                        if (usedKeywordRange[2] <= index && usedKeywordRange[3] >= closingIndex ) {
                            if ((index >= usedKeywordRange[0] && index <= usedKeywordRange[1]) || (closingIndex >= usedKeywordRange[0] && closingIndex <= usedKeywordRange[1])) {
                                rangeIsValid = false;
                                error = 'overlap with '+ this.content.slice(usedKeywordRange[0], usedKeywordRange[1]);
                                break;
                            }

                            if ((index - 2 >= usedKeywordRange[0] && index - 2 <= usedKeywordRange[1]) || (closingIndex + 2 >= usedKeywordRange[0] && closingIndex + 2 <= usedKeywordRange[1])) {
                                rangeIsValid = false;
                                error = 'too close to '+ this.content.slice(usedKeywordRange[0], usedKeywordRange[1]);
                                break;
                            }

                            sharesBoundary = true;
                        }
                    }
                }

                // keyword already in use
                if (rangeIsValid) {
                    phrase = this.content.slice(index, closingIndex + 1);
                    rangeIsValid = !ranges.some((range) => phrase.toLowerCase() == range[4].toLowerCase());

                    if (!rangeIsValid) {
                        error = 'duplicate';
                    }
                }

                if (rangeIsValid) {
                    let origin = index;
                    while (origin > 0) {
                        if (['>', ',', '.', '!', ':', '?'].includes(this.content[origin - 1])) {
                            if (this.content[origin - 1] == ',' && this.content[closingIndex + 1] == ',' && trim(this.content.slice(origin, closingIndex + 1)) == keyword) {
                                // console.log('move back =>', keyword);
                            }
                            break;
                        }
                        origin--;
                    }

                    let intercept = closingIndex;
                    while (intercept < content.length) {
                        if (['<', ',', '.', '!', ':', '?'].includes(this.content[intercept])) {
                            if (this.content[intercept] == ',' && trim(this.content.slice(origin, intercept)) == keyword) {
                                // console.log('move forward =>', keyword);
                            }
                            break;
                        }
                        intercept++;
                    }

                    ranges.push([index, closingIndex, origin, intercept, phrase, this.content.slice(origin, intercept), keyword]);

                    closingIndex = intercept;
                    if (!this.opts.selectMultiple) {
                        break;
                    }
                }

                index = closingIndex + 1; // add space
            }

            if (error) {
                console.log(keyword, ' is invald:', error)
            }
        });

        return ranges;
    }

    getIgnoredTagsRange() {
        let index = 0;
        let content = this.content.toLowerCase();

        const ranges = [];
        const { ignoredTags, typeToSkip, typeToSkipCount } = this.opts;

        if (typeToSkipCount) {
            if (typeToSkip == 'words') {
                index += this.textToWords(content).slice(0, typeToSkipCount).join(' ').length; 
            } else if (typeToSkip == 'sentences') {
                index += this.textToSentences(content).slice(0, typeToSkipCount).join(' ').length;
            } else if (typeToSkip == 'paragraphs') {
                index += this.textToParagraphs(content).slice(0, typeToSkipCount).join(' ').length;
            }

            ranges.push([0, index, typeToSkip]);
        }

        for (let tag in ignoredTags) {
            const opening_tag = `<${ignoredTags[tag]} `;
            const closing_tag = ['img'].includes(ignoredTags[tag])? '/>' : `</${ignoredTags[tag]}>`;

            while (index = content.indexOf(opening_tag, index), index !== -1) {
                let closingTagIndex = content.indexOf(closing_tag, index + opening_tag.length);
                if (closingTagIndex == -1) {
                    break;
                }

                closingTagIndex += closing_tag.length;

                if (tag == 'a') {
                    index -= 2;
                    closingTagIndex += 2; 
                }

                ranges.push([index, closingTagIndex, ignoredTags[tag]]);

                index  = closingTagIndex;
            }
        }

        return ranges.sort((a, b) => a[0] - b[0]);
    }

    textToWords(content, minLength = 0) {
        return content.split(' ');
    }

    textToSentences(content, minLength = 0) {
        if ( ['.', ',', '!', '?'].includes(content[content.length - 1]) ) {
            content = trim( content.substring(0, content.length - 1) );
        }

        if (!content || content.length < minLength) {
            return [];
        }

        const replace = [".\r", '. ', '!', '?']; //[', ', ': ', '; ', ' – ', ' (', ') ', ' {', '} ', '—', '”'];

        //change divided symbols inside tags to special codes
        const matches = content.match(/<[^>]+>/ig);
        for (let i = 0; i < matches.length; i++) {
            let tag = matches[i];
            let tag_replaced = tag;

            for (const r in replace) {
                if (tag.indexOf(replace[r]) !== -1) {
                    const rExp = new RegExp(escapeRegExp(replace[r]), "ig");
                    tag_replaced = tag_replaced.replace(rExp, `[rp${r}]`);
                }
            }

            if (tag_replaced != tag) {
                const tagExp = new RegExp(escapeRegExp(tag), "ig");
                content = content.replace(tagExp, tag_replaced);
            }
        }

        //divide sentence to phrases
        for (const r in replace) {
            const rExp = new RegExp(escapeRegExp(replace[r]), "ig");
            content = content.replace(rExp, `${replace[r]}\n`);
        }

        //change special codes to divided symbols inside tags
        for (const r in replace) {
            const rExp = new RegExp(escapeRegExp(`[rp${r}]`), "ig");
            content = trim(content.replace(rExp, replace[r]));
        }

        return content.split("\n").map(c => trim(c));
    }

    textToParagraphs(content) {
        const output = [];
        const replace_unicode = [
            ['\u003c', '\u003e', '\u0022', '&nbsp;'],
            ['<', '>', '"', ' ']
        ];

        for (let r in replace_unicode[0]) {
            const rExp = new RegExp(escapeRegExp(replace_unicode[0][r]), "ig");
            content = trim(content.replace(rExp, replace_unicode[1][r]));
        }

        const replace = [
            ['<div', '<br', '<li', '<p', '<h1', '<h2', '<h3', '<h4', '<h5', '<h6'],
            ["\n<div", "\n<br", "\n<li", "\n<p", "\n<h1", "\n<h2", "\n<h3", "\n<h4", "\n<h5", "\n<h6"]
        ];

        for (let r in replace[0]) {
            const rExp = new RegExp(escapeRegExp(replace[0][r]), "ig");
            content = trim(content.replace(rExp, replace[1][r]));
        }
        
        const sentences = content.replace(/\[[^\]]+\]/i, "\n").split("\n");
        
        for (const s in sentences) {
            let sentence = sentences[s];

            if (!sentence || sentence == "") {
                continue;
            }
            // todo: emove empty tags

            // remove comments
            if (sentence.substring(0, 4) == '<!--' && sentence.slice(-3) == '-->') {
                continue;
            }

            // todo: remove numbering

            // remove empty
            sentence = trim(sentence);
            if (!sentence || sentence == "") {
                continue;
            }

            output.push(sentence);
        }

        return output;
    }
}