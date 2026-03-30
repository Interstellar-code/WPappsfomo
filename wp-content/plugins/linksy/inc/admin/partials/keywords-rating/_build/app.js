const Keywords = ({
    props: {
      id: [Number, String],
      items: {
        type: Array,
        default: []
      },
      mode: String
    },
    data() {
        return {
            expanded: false,
            showCustomKeywordsForm: false,
            customKeywords: [{
              keyword: ''
            }]
        }
    },
    watch: {
      mode: {
        handler: function(mode) {
            if (mode != 'cus')
              this.showCustomKeywordsForm = false
        }
      }
    },
    computed: {
        keywords: {
          get() {
              return this.items.filter(e => this.mode === 'cus'? e.provider === 'linksy_focus_keyword' :  e.provider !== 'linksy_focus_keyword' ).filter(e => e.keyword && e.keyword.length > 1)
          }
        }
    },
    methods: {
      getScore(score) {
          if (!score) {
            return '...';
          }

          score = Math.round((parseFloat(score) + Number.EPSILON) * 100);
          return score < 0? 0 : score;
      },
      getScoreColor(score) {
          return score || score == 0? scoreToColor(score) : '#cecece';
      },
      deleteKeyword(keyword) {
        this.$emit('onDeleteCustomKeyword', this.id, keyword);
      },
      
      addCustomKeyword() {
        this.customKeywords.push({
          keyword: ''
        })
      },
      removeCustomKeyword(index) {
        this.customKeywords.splice(index, 1);
      },
      toggleCustomKeywordsForm() {
        this.expanded = true;
        this.showCustomKeywordsForm = true;
      },
      submitCustomKeywordsForm() {
        const keywords = this.items.map(e => e.keyword);
        const customKeywords = this.customKeywords.filter(e => e.keyword && !keywords.includes(e.keyword) );

        this.$emit('onAddCustomKeywords', this.id, customKeywords)
        this.customKeywords = [{keyword: ''}];
      },
    },
    template: `
        <div class="keywords-column">
            <div class="keywords-column-content">
                <div class="keywords-column-content-empty" :class="{'d-none': showCustomKeywordsForm}" v-if="keywords.length < 1">
                  <h4>No {{mode == 'cus'? 'Custom' : 'Active'}} keyword</h4>
                  <button v-if="mode == 'cus' && !showCustomKeywordsForm" @click="toggleCustomKeywordsForm">
                    <i class="fa fa-plus"></i>
                  </button>
                </div>
                <div v-else class="keywords-column-content-items" :class="{'expanded': expanded}">
                    <div v-for="(keyword, keywordIndex) in keywords" class="keyword">
                        <div>
                            <span>{{keyword.keyword}}</span>
                            <div class="d-flex">
                                <span class="score" :style="{'background-color': getScoreColor(keyword.score)}">
                                    {{getScore(keyword.score)}}
                                </span>
                                <button v-if="mode == 'cus'" @click="deleteKeyword(keyword)">
                                    <i class="fa fa-circle-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button v-if="mode == 'cus' && !showCustomKeywordsForm" @click="toggleCustomKeywordsForm" class="expander add-keyword">
                      <i class="fa fa-circle-plus"></i>
                    </button>
                </div>
                <button v-if="keywords.length > 0" :disabled="keywords.length < 2" class="expander" :class="{active: keywords.length}" @click="expanded = !expanded">
                    <i class="fa" :class="expanded? 'fa-circle-chevron-up': 'fa-circle-chevron-down'"></i>
                </button>
            </div>

            <div v-if="showCustomKeywordsForm && mode == 'cus'" class="keywords-column-form">
                <div class="keywords-column-form-content">
                    <div>
                        <div class="input-group" v-for="(customKeyword, customKeywordIndex) in customKeywords">
                            <input v-model="customKeyword.keyword" placeholder="new custom keyword" />
                            <button v-if="customKeywordIndex == 0" @click="addCustomKeyword" style="background: #FFFFFF;">
                              <i class="fa fa-plus"></i>
                            </button>
                            <button v-else @click="() => removeCustomKeyword(customKeywordIndex)">
                              <i class="fa fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <button @click="submitCustomKeywordsForm" class="btn-app keywords-column-form-content-submit-btn">Save</button>
                </div>

                <button class="close expander" @click="showCustomKeywordsForm = false">
                  <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    `,
});

Vue.createApp({
    components: {
      Keywords,
      LinksyForm,
      LinksySearch,
      LinksyExport,
      LinksyDropdown,
      LinksyDataTable,
      LinksyPagination
    },
    data() {
      return {
        items: [],
        order: [],
        search: '',
        mode: 'int',
        columns: [
            {
                name: 'Page Title',
                sortable: true,
                selector: 'title',
            },
            {
                name: 'Keywords',
                sortable: true,
            },
        ],
        xport: {
            loading: false,
        },
        filter: {
            type: 'post',
            category: [],
            rating: null,
            keyword: null,
            date: null,
        },
        pagination: {
            page: 0,
            length: 10,
            total: null,
        },
        isLoading: false,
        showRatingFilter: false,
        types: LINKSY_POST_TYPES,
        categories: Object.values(LINKSY_POST_CATEGORIES),
      }
    },
    watch: {
      items: {
        handler: function(val) {
          this.getKeywordsScore()
        }
      }
    },
    computed: {
        reqSearch: {
            get() {
                return encodeURIComponent(this.search.trim());
            }
        },
        reqFilter: {
            get() {
                const filter = {...this.filter, rating: null};
                if (this.filter.rating) {
                    filter.rating = this.filter.rating.toLowerCase();
                }

                return filter;
            }
        },
    },
    methods: {
        validateFilters(val) {
            const errors = {};

            if (val.rating && !['Great', 'Good', 'Average', 'Poor', 'No Keyword'].includes(val.rating)) {
                errors.rating = 'invalid range';
            }
            return errors;
        },
        handleFilterReset() {
            this.filter = {
                type: 'post',
                category: [],
                rating: null,
                keyword: null,
                date: null,
            };

            jQuery('.daterangepickerinput').val('');

            this.getPosts();
        },
        handleFilterChange(filter) {
                this.filter =   {...this.filter, ...filter};
                this.getPosts();
        },
        handleSearch(q) {
            this.search = q;
            this.getPosts();
        },
        async handleExport(columns, isDetailed) {
            const items = [];
            const toExport = [];
            const toExportColumns = [];

            if (!this.pagination.total) {
                return this.$toast.warning('page still loading');
            }

            try {
                this.xport.loading = true;
                let page = 0;

                do {
                    const {data} = await this.getPostsRequest(page);
                    items.push(...data);
                    page += 1;
                } while(this.pagination.total - (this.pagination.length * page) > 0);

                const keywords = [].concat.apply([], items.filter(item => item.keywords.length > 0).map(e => e.keywords.map(k => ({post_id: e.post_id, ...k}))))
                
                var params = urlSearchParams({
                    'action': 'linksy_keywords_rating_get_scores',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'keywords': JSON.stringify(keywords)
                });

                const {data: {data}} = await axios.post(ajaxurl, params);

                data.forEach(e => {
                    const itemIndex = items.findIndex(k => k.post_id == e.post_id);

                    items[itemIndex].keywords.forEach(keyword => {
                        if (keyword.keyword == e.keyword) {
                            keyword['score'] = e.score
                        }
                    })
                });

                columns.forEach(e => {
                    toExportColumns.push(e);
    
                    if (e == 'Target Keywords' && isDetailed) {
                        toExportColumns.push('Custom Keywords');
                    }
                  });
    
                items.forEach(e => {
                    const row = [];
        
                    if (columns.includes('Page Title')) {
                        row.push(e.title);
                    }

                    if (columns.includes('Page URL')) {
                        row.push(e.link);
                    }

                    if (columns.includes('Post Type')) {
                        row.push(e.type);
                    }

                    if (columns.includes('Category')) {
                        row.push((e.categories || []).join(','));
                    }

                    if (columns.includes('Pub. Date')) {
                        row.push(e.date);
                    }
                
                    if (columns.some(e => (e == 'Target Keywords' || e == 'Rating'))) {
                        e.keywords.forEach(keyword => {
                            const innerRow = [...row];
        
                            if (columns.includes('Target Keywords')) {
                                if (isDetailed) {
                                    innerRow.push(keyword['provider'] != 'linksy_focus_keyword' ? keyword['keyword'] : '');
                                    innerRow.push(keyword['provider'] == 'linksy_focus_keyword' ? keyword['keyword'] : '');
                                } else {
                                    innerRow.push( keyword['keyword']);
                                }
                            }
        
                            if (columns.includes('Rating')) {
                                innerRow.push(keyword['score']? Math.round((parseFloat(keyword['score']) + Number.EPSILON) * 100) : '');
                            }

                            toExport.push(innerRow);
                        });
                    } else {
                        toExport.push(row);
                    }
                });

                download(LinksyExportHelpers.create('csv', toExportColumns, toExport))

            } catch (error) {

                console.log(error.response?.data?.data || error.message);
                this.$toast.error('unable to export data');
            } finally {
                this.xport.loading = false;
            }
        },
        handleChangePage(page) {
            this.getPosts(page - 1);
        },

        handleChangeRowsPerPage(length) {
            if (length != this.pagination.length) {
                this.pagination.length = length;
                this.getPosts();
            }
        },

        handleSort: debounce(function(column, dir) {
            const orderIndex = this.order.findIndex(e => e.column == column);
            if (orderIndex == -1) {
                this.order.unshift({
                    dir,
                    column
                })
            } else {
                this.order[orderIndex]['dir'] = dir;
            }

            this.getPosts();
        }, 1000),

        handleModeChange: debounce(function(val) {
            if (this.mode !== val) {
            this.mode = val;
            }
        }, 200),

        async getPostsRequest(page) {
            const params = toQueryString({
                action: 'linksy_keywords_rating_get_posts',
                nonce: LINKSY_SECURE_TOKEN,
                page: page,
                order: this.order,
                search: this.reqSearch,
                limit: this.pagination.length,
                filter: this.reqFilter
            });
            const {data: {data}} = await axios.get(ajaxurl+'?'+params);

            return data;
        },

        async getPosts(page = 0) {
            try {
                this.isLoading = true;

                const data = await this.getPostsRequest(page);

                this.items = data.data;
                this.pagination = {
                    ...this.pagination,
                    page: page,
                    total: data.total
                };
                this.showRatingFilter = !!+data.show_rating_filter;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
        async getKeywordsScore() {
            try {
                this.isLoading = true;

                const itemsToSend = this.items.map(item => {
                    return {
                        ...item,
                        keywords: item.keywords.filter(keyword => keyword.keyword && keyword.score == null),
                    }
                }).filter(item => item.keywords.length > 0);

                if (itemsToSend.length < 1) {
                    return;
                }

                var params = urlSearchParams({
                    'action': 'linksy_keywords_rating_get_scores',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'keywords': JSON.stringify([].concat.apply([], itemsToSend.map(e => e.keywords.map(k => ({post_id: e.post_id, ...k})))))
                });

                const {data: {data}} = await axios.post(ajaxurl, params);
                const scoreRange = tagToScore(this.filter.rating);

                data.forEach(e => {
                    const itemIndex = this.items.findIndex(k => k.post_id == e.post_id);

                    this.items[itemIndex].keywords.forEach((keyword, index) => {
                        if (keyword.keyword == e.keyword) {
                            if (scoreRange) {
                                let score = parseFloat(e.score) * 100;
                                if (!(score >= scoreRange[0] && score <=  scoreRange[1])) {
                                    this.items[itemIndex].keywords.splice(index, 1);
                                    return;
                                }
                            }

                            keyword['score'] = e.score;
                        }
                    });
                });
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
        async addCustomKeywords(id, keywords) {
            try {
                this.isLoading = true;
                var params = urlSearchParams({
                    'action': 'linksy_keywords_rating_add_custom_keywords',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'post_id': id,
                    'keywords': JSON.stringify(keywords.map(e => e.keyword))
                });

                const {data: {data}} = await axios.post(ajaxurl, params);

                data.forEach(e => {
                    const itemIndex = this.items.findIndex(k => k.post_id == e.post_id);
                    this.items[itemIndex].keywords.push(e);
                });
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
        async removeCustomKeyword(id, keyword) {
            try {
                this.isLoading = true;
                var params = urlSearchParams({
                    'action': 'linksy_keywords_rating_remove_custom_keyword',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'post_id': id,
                    'keyword': keyword.keyword
                });

                await axios.post(ajaxurl, params);

                const itemIndex = this.items.findIndex(k => k.post_id == id);
                const keywordIndex = this.items[itemIndex].keywords.findIndex(k => k.keyword == keyword.keyword);

                this.items[itemIndex].keywords.splice(keywordIndex, 1);

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
        async resetKeywords() {
            try {
                this.isLoading = true;
                var params = urlSearchParams({
                    'action': 'linksy_keywords_rating_reset_keywords',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'posts': JSON.stringify(this.items.map(e => e.post_id))
                });

                const {data: {data}} = await axios.post(ajaxurl, params);

                data.forEach(e => {
                    const itemIndex = this.items.findIndex(k => k.post_id == e.post_id);
                    this.items[itemIndex].keywords = e.keywords;
                });

                await this.getKeywordsScore();
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
    },
    async beforeMount() {
        await this.getPosts();

        if (!this.showRatingFilter) {
            axios.get(`${ajaxurl}?action=linksy_keywords_rating_get_keywords_score_cron&nonce=${LINKSY_SECURE_TOKEN}`);
        }
    },
    mounted() {
        jQuery('.daterangepickerinput').daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                cancelLabel: 'Clear',
                format: DateParser.parse(LINKSY.date_format),
            },
            ranges: {
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }).on('apply.daterangepicker', (ev, picker) => {
            jQuery('.daterangepickerinput').val(picker.chosenLabel).trigger( "blur" );
            this.filter.date = picker.startDate.format('YYYY-MM-DD')+' - '+picker.endDate.format('YYYY-MM-DD');
        }).on('cancel.daterangepicker', (ev, picker) => {
            jQuery('.daterangepickerinput').val('');
            this.filter.date = null;
        });
    }
}).use(LinksyToast).mount('#keywords-rating-app');
