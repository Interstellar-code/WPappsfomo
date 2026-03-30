const Occurrence = ({
  name: 'Occurrence',
  components: {
    LinksyDropdown
  },
  props: {
    item: Object,
    active: Boolean,
  },
  watch: {
    active: function (val) {
      if (!val && this.expanded) {
        this.expanded = false;
      }
    },
    expanded: function (val) {
      this.$emit('onExpanded', this.expanded);
    }
  },
  data() {
    return {
      expanded: false,
    }
  },
  methods: {
    getScore(score) {
      score = Math.round((parseFloat(score) + Number.EPSILON) * 100);
      return score < 0 ? 0 : score;
    },

    getScoreColor(score) {
      return score ? scoreToColor(score) : '#cecece';
    },
  },
  template: `
    <div class="occurrence">
        <div>
            <div class="title" @click="expanded = true">
                <h5 class="m-0">
                    {{item.destination.title}}
                    <div class="meta">
                        <a :href="item.destination.url" target="_blank" class="pe-3">View</a>
                        <a :href="item.destination.edit_url" target="_blank" class="ps-3">Edit</a>
                    </div>
                </h5>
                <div class="d-flex">
                    <span class="score" :style="{'background-color': getScoreColor(item.score)}">
                        {{item.score? getScore(item.score) : '...'}}
                    </span>
                    <button class="expander" @click.stop="expanded = !expanded" style="margin: 0;">
                        <i class="fa" :class="expanded? 'fa-chevron-up': 'fa-chevron-down'"></i>
                    </button>
                </div>
            </div>
            <div class="sources" v-if="expanded">
                <br />
                <h5>sources</h5>
                <span class="d-flex align-items-end justify-content-between" v-for="source in item.sources">
                    {{source.title}}
                    <linksy-dropdown hideIcon class="m-0" :items="[{label: 'View', value: source.url},{label: 'Edit', value: source.edit_url}]">
                        <span>&nbsp;<i class="fa fa-ellipsis-vertical"></i>&nbsp;</span>
                        <template v-slot:item="{item, index}">
                            <a :href="item.value" target="_blank">{{item.label}}</a>
                        </template>
                    </linksy-dropdown>
                </span>
            </div>
        </div>
    </div>
    `
});

const Occurrences = ({
  name: 'Occurrences',
  components: {
    Occurrence
  },
  props: {
    item: Object
  },
  data() {
    return {
      expanded: false,
      activeOccurrence: null
    }
  },
  computed: {
    occurrences: {
      get() {
        return this.item.occurrences.sort((a, b) => parseFloat(b.score) - parseFloat(a.score))
      }
    }
  },
  methods: {
    handleOccurrenceExpansion(expanded, item, index) {
      if (expanded) {
        this.activeOccurrence = index;

        if (!this.expanded) {
          this.expanded = true;
        }
      }
    },
  },
  template: `
    <div class="occurrences-column">
        <div style="flex: 1" :class="{'expanded': expanded}">
            <occurrence
                v-for="(occurrence, occurrenceIndex) in occurrences"
                :item="occurrence"
                :active="activeOccurrence === occurrenceIndex"
                @onExpanded="e => handleOccurrenceExpansion(e, occurrence, occurrenceIndex)"
            >
            </occurrence>
        </div>
        <button :disabled="!occurrences || occurrences.length < 2" class="expander" @click="expanded = !expanded">
            <i class="fa" :class="expanded? 'fa-circle-chevron-up': 'fa-circle-chevron-down'"></i>
        </button>
    </div>
    `,
});

Vue.createApp({
  components: {
    Occurrences,
    LinksyForm,
    LinksyExport,
    LinksySearch,
    LinksyDropdown,
    LinksyDataTable,
    LinksyPagination
  },
  data() {
    return {
      data: [],
      order: [],
      columns: [
        {
          name: 'Anchor',
          sortable: true,
          selector: 'anchor',
        },
        {
          name: 'Targets / Sources',
          sortable: true,
          format: row => 'Occurrences',
        },
      ],
      date: null,
      search: '',
      filter: {
        type: '',
        category: [],
        duplicate: {
          min: null,
          max: null
        },
        anchorLength: {
          min: null,
          max: null
        },
        rating: null,
        target: null,
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
  computed: {
    processedData: {
      get() {
        let data = [...this.data];
        let pageShouldReset = false;

        if (data.length < 1) {
          return data;
        }

        // all processed?
        // this.showRatingFilter = !data.some(item => {
        //     return item.occurrences.some(occourence => occourence.score == null )
        // });

        // search
        if (this.search && this.search.length > 1) {
          data = data.filter(e => e.anchor.toLowerCase().includes(this.search));
          pageShouldReset = true;
        }
        // filters : category
        if (Array.from(this.filter.category).length > 0) {
          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => (Array.from(occurrence.destination.categories).filter(x => Array.from(this.filter.category).includes(x)).length)),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }
        // filters : type
        if (this.filter.type) {
          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => (occurrence.destination.type == this.filter.type)),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }
        // filters : length
        if (this.filter.anchorLength.min || this.filter.anchorLength.max) {
          data = data.filter(e => {
            const anchorLength = e.anchor.split(' ').length;
            if (this.filter.anchorLength.min && anchorLength < this.filter.anchorLength.min) {
              return false
            }

            if (this.filter.anchorLength.max && anchorLength > this.filter.anchorLength.max) {
              return false
            }

            return true;
          });
          pageShouldReset = true;
        }
        // filters : rating
        if (this.filter.rating) {
          let rating = this.filter.rating.toLowerCase() //this.filter.rating.map(e => e.toLowerCase())
          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => scoreToTag(occurrence.score) == rating),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }
        // filters : duplicate
        if (this.filter.duplicate.min || this.filter.duplicate.max) {
          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => {
                if (this.filter.duplicate.min && occurrence.sources.length < this.filter.duplicate.min) {
                  return false
                }

                if (this.filter.duplicate.max && occurrence.sources.length > this.filter.duplicate.max) {
                  return false
                }

                return true;
              }),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }
        // filters : target contain
        if (this.filter.target && this.filter.target.length > 1) {
          let target = this.filter.target.toLowerCase();

          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => {
                if (occurrence.destination.title.toLowerCase().includes(target) || occurrence.destination.url.toLowerCase().includes(target)) {
                  return true;
                }

                return occurrence.sources.some(source => source.title.toLowerCase().includes(target) || source.url.toLowerCase().includes(target))
              }),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }
        // filters : date
        if (this.filter.date) {
          const dates = this.filter.date.split(' - ');


          const startDate = moment(dates[0]).subtract(1, 'days');
          const endDate = moment(dates[1]).add(1, 'days');;

          data = data.map(item => {
            return {
              ...item,
              occurrences: item.occurrences.filter(occurrence => {
                if (moment(occurrence.destination.date, DateParser.parse(LINKSY.date_format)).isBetween(startDate, endDate)) {
                  return true;
                }
              }),
            }
          }).filter(item => item.occurrences.length > 0);
          pageShouldReset = true;
        }

        if (pageShouldReset) {
          this.pagination.page = 0;
        }
        return data;
      }
    },
    items: {
      get() {
        const data = this.processedData;
        const offset = this.pagination.page * this.pagination.length;
        this.pagination.total = data.length;
        return data.slice(offset, offset + this.pagination.length);
      }
    }
  },
  methods: {
    validateFilters(val) {
      const errors = {};
      if (val.duplicate.min && val.duplicate.max && parseInt(val.duplicate.min) > parseInt(val.duplicate.max)) {
        errors.duplicate = 'invalid range';
      }
      if (val.anchorLength.min && val.anchorLength.max && parseInt(val.anchorLength.min) > parseInt(val.anchorLength.max)) {
        errors.anchorLength = 'invalid range';
      }

      return errors;
    },
    handleFilterReset() {
      this.filter = {
        type: '',
        category: [],
        duplicate: {
          min: null,
          max: null
        },
        anchorLength: {
          min: null,
          max: null
        },
        rating: null,
        target: null,
        date: null,
      };

      jQuery('.daterangepickerinput').val('');
    },
    handleFilterChange(filter) {
      this.filter = { ...filter, date: this.date };
      this.getItemsScore();
    },
    handleSort: function (column, dir) {
      const orderIndex = this.order.findIndex(e => e.column == column);
      if (orderIndex != -1) {
        delete this.order[orderIndex];
      }

      this.order.unshift({
        dir,
        column
      })

      const _this = this;
      const interval = setTimeout(() => {
        if (!_this.isLoading) {
          _this.order.forEach(e => {
            const isAsc = e['dir'] == 'asc';
            _this.data = _this.data.sort((a, b) => {
              const start = isAsc ? a : b;
              const stop = isAsc ? b : a;

              if (e['column'] == 0) {
                // anchor
                if (start.anchor.toUpperCase() < stop.anchor.toUpperCase()) {
                  return -1;
                }
                if (start.anchor.toUpperCase() > stop.anchor.toUpperCase()) {
                  return 1;
                }

                return 0;
              } else {
                return start.occurrences[0]['score'] - stop.occurrences[0]['score'];
              }
            });
          });

          _this.handleChangePage(1);
          clearTimeout(interval);
        } else console.log('ordering')
      }, 2000);
    },
    handleSearch(q) {
      if (this.search != q) {
        this.search = q.toLowerCase();
        this.getItemsScore();
      }
    },
    handleExport(columns) {
      const toExport = [];

      this.processedData.forEach(e => {
        const row = [];

        if (columns.includes('Anchor')) {
          row.push(e.anchor);
        }

        if (columns.some(e => (e == 'Target' || e == 'Target URL' || e == 'Target Score' || e == 'Source' || e == 'Source URL'))) {
          e.occurrences.forEach(occourence => {
            const innerRow = [...row];

            if (columns.includes('Target')) {
              innerRow.push(occourence['destination']['title']);
            }

            if (columns.includes('Target URL')) {
              innerRow.push(occourence['destination']['url']);
            }

            if (columns.includes('Target Score')) {
              // score < 0? 0 : score;
              innerRow.push(Math.round((parseFloat(occourence['score']) + Number.EPSILON) * 100));
            }

            if (columns.includes('Source') || columns.includes('Source URL')) {
              occourence.sources.forEach(source => {
                const innerInnerRow = [...innerRow];
                if (columns.includes('Source')) {
                  innerInnerRow.push(source.title);
                }

                if (columns.includes('Source URL')) {
                  innerInnerRow.push(source.url);
                }

                toExport.push(innerInnerRow);
              })
            } else {
              toExport.push(innerRow);
            }
          })
        } else {
          toExport.push(row);
        }
      });

      download(LinksyExportHelpers.create('csv', columns, toExport))
    },
    handleChangePage(page) {
      this.pagination.page = page - 1;
      this.getItemsScore();
    },
    handleChangeRowsPerPage(length) {
      if (length != this.pagination.length) {
        this.pagination = {
          ...this.pagination,
          page: 0,
          length,
        }

        this.getItemsScore();
      }
    },

    async getItems() {
      try {
        this.isLoading = true;
        const params = toQueryString({
          action: 'linksy_anchor_cloud_get_links',
          nonce: LINKSY_SECURE_TOKEN,
        });
        const { data: { data } } = await axios.get(ajaxurl + '?' + params);

        this.data = data.data;
        this.pagination = {
          ...this.pagination,
          total: this.data.length
        };
        this.showRatingFilter = !!+data.show_rating_filter;

        this.getItemsScore();
        this.getItemsCategories();

      } catch (error) {
        console.log(error.response?.data?.data || error.message);
        this.isLoading = false;
      }
    },
    async getItemsScore() {
      try {
        this.isLoading = true;
        const itemsToSend = this.items.map(item => {
          return {
            ...item,
            occurrences: item.occurrences.filter(occurrence => occurrence.score == null),
          }
        }).filter(item => item.occurrences.length > 0);

        if (itemsToSend.length < 1) {
          return;
        }

        var params = urlSearchParams({
          'action': 'linksy_anchor_cloud_get_keywords',
          'nonce': LINKSY_SECURE_TOKEN,
          'anchors': JSON.stringify(itemsToSend.map(e => ({
            phrase: e.anchor,
            occurrences: e.occurrences.map(e => e.destination.id)
          })))
        });

        const { data: { data } } = await axios.post(ajaxurl, params);

        data.forEach(e => {
          const itemIndex = this.data.findIndex(k => k.anchor === e.anchor);
          this.data[itemIndex]['occurrences'].forEach((k, i) => {
            const itemOccourenceIndex = e.occurrences.findIndex(o => o.id == k.destination.id);
            if (itemOccourenceIndex !== -1) {
              k.score = e.occurrences[itemOccourenceIndex]['score'];
            }
          })
        });
      } catch (error) {
        console.log(error.response?.data?.data || error.message);
      } finally {
        this.isLoading = false;
      }
    },
    async getItemsCategories() {
      try {
        let ids = [];

        this.data.forEach(item => {
          item.occurrences.forEach(occourence => {
            ids.push(occourence.destination.id)
          })
        });

        var params = urlSearchParams({
          'action': 'linksy_anchor_cloud_get_categories',
          'nonce': LINKSY_SECURE_TOKEN,
          'ids': JSON.stringify([...new Set(ids)])
        });

        const { data: { data } } = await axios.post(ajaxurl, params);

        this.data.forEach(item => {
          item.occurrences.forEach(occourence => {
            occourence.destination.categories = data[occourence.destination.id]
          })
        });

      } catch (error) {
        console.log(error.response?.data?.data || error.message);
      }
    },
  },
  async beforeMount() {
    await this.getItems();

    if (!this.showRatingFilter) {
      axios.get(`${ajaxurl}?action=linksy_anchor_cloud_get_keywords_cron&nonce=${LINKSY_SECURE_TOKEN}`);
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
      jQuery('.daterangepickerinput').val(picker.chosenLabel).trigger("blur");
      this.date = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
    }).on('cancel.daterangepicker', (ev, picker) => {
      jQuery('.daterangepickerinput').val('');
      this.date = '';
    });
  }
}).use(LinksyToast).mount('#anchor-cloud-app');