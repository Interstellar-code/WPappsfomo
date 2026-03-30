const QuickApply = ({
    name: 'QuickApply',
    props: {
        value: {
            type: Number,
            default: 3
        },
    },
    watch: {
        value: function (val) {
            this.count = Math.max(val, this.count);
        }
    },
    methods: {
        toggleActiveState() {
            this.active = !this.active;
        },
        onApply() {
            this.$emit('onSubmit', this.count);
            this.toggleActiveState();
        }
    },
    data() {
        return {
            count: 3,
            active: false,
            styles: {
                dropDown: {
                    position: 'absolute',
                    right: '0px',
                    padding: '17px 12px 15px',
                    marginTop: '5px',
                    background: '#FFFFFF',
                    borderRadius: '4px',
                    maxWidth: '150px',
                    zIndex: 2,
                },
                dropDownHidden: {
                    display: 'none',
                },
                dropDownShown: {
                    display: 'block',
                },
            }
        }
    },
    template: `
        <div class="ms-2 position-relative d-inline-block">
            <button @click="toggleActiveState">
                <span>Quick Apply</span>&nbsp;
                <i class="fa" :class="active? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </button>

            <div
                class="shadow"
                :class="{active: active}"
                :style="[styles.dropDown, active? styles.dropDownShown : styles.dropDownHidden]"
            >
                <h3 class="mt-0 mb-3 text-center font-weight-light">Apply best</h3>
                <input type="number" v-model="count" class="w-100"/>

                <div class="d-flex justify-content-between mt-3">
                    <button class="btn-default ms-0" @click="toggleActiveState">Cancel</button>
                    <button class="btn-app me-0" @click="onApply">Apply</button>
                </div>

                <small></small>
            </div>
        </div>
    `,
});

const Suggestion = ({
    name: 'Suggestion',
    components: {
        LinksyDropdown,
    },
    props: {
        key: String,
        item: Object,
        isGridView: Boolean
    },
    data() {
        return {
            expanded: false,
        }
    },
    computed: {
        link: {
            get() {
                return this.item.link.replace(LINKSY.site_url, '');
            }
        }
    },
    methods: {
        getScoreColor(score) {
            return scoreToColor(score);
        },
        handleKeywordSelection(keyword) {
            const keywordIndex = this.item.keywords.findIndex(k => k.phrase == keyword.phrase);

            const isSelected = !this.item.keywords[keywordIndex]['selected'];

            this.item.keywords.forEach(e => e.selected = false);
            this.item.keywords[keywordIndex]['selected'] = isSelected;

            this.$emit('select', this.item.keywords[keywordIndex], this.item);
        },

        decodeEntities(text) {
            const entityRegex = /&#(\d+);/g;
            return text.replace(entityRegex, (match, dec) => String.fromCharCode(dec));
        },
    },
    template: `
        <div>
            <div class="suggestion-header">
                <div>
                    <h2>{{decodeEntities(item.title)}}</h2>
                    <h5 style="font-size: 16px;">{{link}}</h5>
                </div>

                <linksy-dropdown hideIcon :items="[{label: 'View', value: item.link},{label: 'Edit', value: item.edit_link}]">
                    <span>&nbsp;<i class="fa fa-ellipsis-vertical"></i>&nbsp;</span>
                    <template v-slot:item="{item, index}">
                        <a :href="item.value" target="_blank">{{item.label}}</a>
                    </template>
                </linksy-dropdown>
            </div>

            <div class="suggestion-items" :class="{'expanded': isGridView || expanded}">
                <div v-for="(keyword, keywordIndex) in item.keywords.filter(e => e.source)" :class="{'selected': keyword.selected}">
                    <div>
                        <h5>
                            <div style="padding-top: 5px;position: relative;" @click="handleKeywordSelection(keyword)">
                                <input type="checkbox" :checked="keyword.selected" />
                                <div style="width: 100%; height: 100%; top: 0; left: 0; position: absolute; cursor: pointer;"></div>
                            </div>
                            &nbsp;&nbsp;&nbsp;
                            <p v-html="keyword.source.replace( keyword.phrase, '<mark>'+keyword.phrase+'</mark>' )"></p>
                        </h5>
                    </div>

                    <div style="display: flex;">
                        <div class="suggestions-selector-score" :style="{background: getScoreColor(keyword.score)}">{{Math.round((keyword.score + Number.EPSILON) * 100)}}</div>

                        <button v-if="!isGridView && item.keywords.length > 1 && keywordIndex == 0" @click="expanded = !expanded">
                            <i class="fa" :class="expanded? 'fa-circle-chevron-up': 'fa-circle-chevron-down'"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `
});

Vue.createApp({
    components: {
        Suggestion,
        QuickApply,
        LinksySearch,
    },
    data() {
      return {
          view: 'list',
          postId: null,
          isLoading: true,
          nextPostId: null,

          summary: [],
          suggestions: [],

          inboundLinksCnt: null,
          outboundLinksCnt: null,
          externalLinksCnt: null,
      }
    },
    computed: {
        isGridView: {
            get() {
                return this.view === 'grid';
            },
        },
        currentIndex: {
            get() {
                const i = this.suggestions.findIndex(e => e.active);
                return i < 1? 0 : i ;
            },
        },
        nextPostLink: {
            get() {
                return this.nextPostId? LINKSY.admin_url+'?page=Linksy-inbound-links&post_id='+this.nextPostId : null;
            }
        },
        summaryCnt: {
            get() {
                let great_cnt = 0;
                let good_cnt = 0;
                let avg_cnt = 0;
                let poor_cnt = 0;

                this.suggestions.forEach(e => {
                    const tag = scoreToTag(e.keywords[0].score);
                    switch (tag) {
                        case 'great':
                            great_cnt++;
                            break;
                        case 'good':
                            good_cnt++;
                            break;
                        case 'average':
                            avg_cnt++;
                            break;
                        default:
                            poor_cnt++;
                            break;
                    }
                });

                return {
                    'great': great_cnt,
                    'good': good_cnt,
                    'average': avg_cnt,
                    'poor': poor_cnt
                }
            },
        },
        summaryGreatCnt: {
            get() {
                const i = this.summary.filter(e => scoreToTag(e.score) === 'great');
                return i.length;
            },
        },
        summaryGoodCnt: {
            get() {
                const i = this.summary.filter(e => scoreToTag(e.score) === 'good');
                return i.length;
            },
        },
        summaryAverageCnt: {
            get() {
                const i = this.summary.filter(e => scoreToTag(e.score) === 'average');
                return i.length;
            },
        },
        summaryPoorCnt: {
            get() {
                const i = this.summary.filter(e => scoreToTag(e.score) === 'poor');
                return i.length;
            },
        }
    },
    watch: {
        view: function(val){
            if (!this.isGridView) {
                setTimeout(() => this.scrollTo(this.currentIndex), 200);
            }
        },
        suggestions: {
            deep: true,
            handler: function(val) {
                if (!this.isGridView) {
                    this.scrollTo(this.currentIndex)
                }
            }
        }
    },
    methods: {
        goBack() {
            window.location.replace(LINKSY.admin_url+'?page=Linksy-reports');
        },
        scrollTo(index) {
            const el = document.getElementById('item-'+index);
            if (el) {
                el.scrollIntoView({behavior: 'smooth'});
            }
        },

        navPrev() {
            if (this.currentIndex > 0) {
                this.suggestions = [...this.suggestions].map((e, i) => {
                    return {
                        ...e,
                        active: i === this.currentIndex  - 1
                    }
                });
            }
        },
        navNext() {
            if (this.currentIndex < this.suggestions.length - 1) {
                this.suggestions = [...this.suggestions].map((e, i) => {
                    return {
                        ...e,
                        active: i === this.currentIndex  + 1
                    }
                });
            }
        },
        navCustom(index) {
            this.suggestions = [...this.suggestions].map((e, i) => {
                return {
                    ...e,
                    active: i === index
                }
            });
        },

        handleSuggestionSelection(keyword, post) {
            const summary = [...this.summary];
            const summaryIndex = summary.findIndex(e => e.id === post.id);

            if (summaryIndex !== -1) {
                if (keyword.selected) {
                    summary[summaryIndex]['score'] = keyword.score;
                    summary[summaryIndex]['phrase'] = keyword.phrase;
                    summary[summaryIndex]['source'] = keyword.source;
                } else {
                    summary.splice(summaryIndex, 1);
                }
            } else {
                if (keyword.selected) {
                    summary.push({
                        id: post.id,
                        title: post.title,
                        score: keyword.score,
                        phrase: keyword.phrase,
                        source: keyword.source
                    });
                }
            }

            this.summary = summary;
        },
        handleQuickSuggestionSelection(count) {
            count = Math.min(count, this.suggestions.length);

            this.summary = [];

            for (let i = 0; i <count; i++) {
                const post = this.suggestions[i];
                loopj:
                for (let j = 0; j < (post['keywords']).length; j++) {
                    const postKeyword = post['keywords'][j]

                    // check wether its the first in others
                    for (let m = i + 1; m < count; m++) {
                        const nextPostKeyword = this.suggestions[m]['keywords'][0]

                        if (postKeyword['phrase'] == nextPostKeyword['phrase']) {
                            // if its the first compare the score
                            if (postKeyword['score'] < nextPostKeyword['score']) {
                                continue loopj;
                            }
                        }
                    }

                    post['keywords'][j]['selected'] = true;
                    this.handleSuggestionSelection({
                        id: post.id,
                        phrase: postKeyword['phrase'],
                        score: postKeyword['score'],
                        source: postKeyword['source'],
                        selected: true,
                    }, post);
                    break;
                }
            }

            this.applySuggestions();
        },

        async getSummary() {
            try {
                const params = toQueryString({
                    action: 'linksy_inbound_links_get_summary',
                    nonce: LINKSY_SECURE_TOKEN,
                    post_id: this.postId
                });
                const {data: {data}} = await axios.get(ajaxurl+'?'+params);

                this.inboundLinksCnt = data.inbound_links;
                this.outboundLinksCnt = data.outbound_links;
                this.externalLinksCnt = data.external_links;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },

        async getOrphanedPosts() {
            try {
                const params = toQueryString({
                    action: 'linksy_inbound_links_get_orphans',
                    nonce: LINKSY_SECURE_TOKEN,
                    post_id: this.postId
                });
                const {data: {data}} = await axios.get(ajaxurl+'?'+params);

                this.nextPostId = data[0].ID;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },

        async getSuggestions() {
            try {
                this.isLoading = true;
                const params = toQueryString({
                    action: 'linksy_inbound_links_get_suggestions',
                    nonce: LINKSY_SECURE_TOKEN,
                    post_id: this.postId
                });
                const {data: {data}} = await axios.get(ajaxurl+'?'+params);

                data.forEach(suggestion => {
                    const keywordsHandler = new Keyords({
                        // ignoredTags: [],
                        content: suggestion.content,
                        keywords: suggestion.keywords.map(e => e.phrase)
                    });
                    
                    keywordsHandler.getKeywordsRange().forEach(range => {
                        const keywordIndex = suggestion.keywords.findIndex(e => e.phrase == range[6]);

                        if (keywordIndex > -1) {
                            suggestion.keywords[keywordIndex]['phrase'] = trim(range[4]);
                            suggestion.keywords[keywordIndex]['source'] = trim(range[5]);
                        }
                    });
            
                    suggestion.valid = suggestion.keywords.filter(e => e.source).length > 0;
                });

                this.suggestions = data.filter(e => e.valid).sort((a, b) => {
                    return b.keywords.find(k => k.source).score - a.keywords.find(k => k.source).score
                });
                if (this.suggestions.length > 0) {
                    this.suggestions[0]['active'] = true;
                }
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },

        async applySuggestions() {
            try {
                this.isLoading = true;
                const ids = this.summary.map(e => e.id);

                if (ids.length < 1) {
                    throw Error('Nothing to apply');
                }

                const {data: {data}} = await axios.post(ajaxurl, urlSearchParams({
                    action: 'linksy_inbound_links_apply_suggestions',
                    nonce: LINKSY_SECURE_TOKEN,
                    post_id: this.postId,
                    suggestion_ids: ids,
                    suggestion_phrases: this.summary.map(e => e.phrase),
                    suggestion_sources: this.summary.map(e => e.source)
                }));

                let failedSummaries = [];
                if (data.failed?.length > 0 ) {
                    failedSummaries = this.summary.filter(e => data.failed.map(e => e.id).includes(`${e.id}`));
                    failedSummaries.forEach(e => {
                        this.$toast.warning(`Failed to apply ${e.title}`);
                    });
                }

                if (data.processed?.length > 0 ) {
                    this.$toast.success(`Successfully applied ${data.processed?.length} links`);
                }

                // remove from suggestions
                this.summary = this.summary.filter(e => failedSummaries.map(e => e.id).includes(e.id));
                this.suggestions = [...this.suggestions].map(e => ({
                    ...e,
                    failed: failedSummaries.map(e => e.id).includes(e.id)
                })).filter(e => {
                    return !ids.includes(e.id) || e.failed;
                });

                if (this.suggestions.length > 0) {
                    this.suggestions[this.currentIndex] = {
                        ...this.suggestions[this.currentIndex],
                        active: true
                    }
                }

                this.getSummary();
            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        }
    },
    mounted() {
        this.postId = document.getElementById('post_ID').value;
        this.getSuggestions();
        this.getOrphanedPosts();
    }
}).use(LinksyToast).mount('#internal-links-app');
