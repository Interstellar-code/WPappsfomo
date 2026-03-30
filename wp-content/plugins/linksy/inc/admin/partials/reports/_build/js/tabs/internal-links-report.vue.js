const ReportTitle = ({
    name: 'ReportTitle',
    props: {
        item: Object
    },
    computed: {
        linkInUrl: {
            get() {
                return LINKSY.admin_url+'?page=Linksy-inbound-links&post_id='+this.item.post_id;
            }
        }
    },
    data() {
        return {
            active: false,
        }
    },
    template: `
    <div
        class="report-title"
        :class="{expanded: active}"
        @mouseover="active = true"
        @mouseleave="active = false"
    >
        <div class="title">{{item.title}}</div>
        <div class="links">
            <a :href="linkInUrl" target="_blank" class="link">Link In </a>
            <a :href="item.edit_link" target="_blank" class="link">Edit/Link Out </a>
            <a :href="item.link" target="_blank" class="link">View </a>
        </div>
    </div>
    `
});

const ReportLinks = ({
    name: 'ColumnInboundLink',
    props: {
        value: [String, Number],
        title: String,
        items: Array,
        expanded: Boolean,
    },
    data() {
        return {
            activeHeight: 0,
        }
    },
    watch: {
        expanded: {
            handler: function (val) {
                const _this = this;
                setTimeout(() => {
                    if (val && _this.$refs?.contentTable?.clientHeight) {
                        _this.activeHeight = _this.$refs?.contentTable?.clientHeight;
                    }
                }, 20)
            }
        },
    },
    methods: {
        toggleActiveState: function() {
            if (this.items.length > 0) {
                this.$emit(!this.expanded? 'onOpen' : 'onClose')
            }
        }
    },
    template: `
    <div class="report-links" :class="{active: expanded, selectable: items.length > 0}">
        <div class="selector" @click="toggleActiveState">
            <span>{{value}}</span>
        </div>
        <div v-if="expanded && items.length > 0" class="content" :style="{height: (activeHeight + 35)+'px'}">
            <table ref="contentTable">
                <thead>
                    <tr>
                        <th>{{title}}</th>
                        <th>Anchor Text</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(link, index) in items">
                        <td>
                            <slot :link="link">
                                <div class="content-title">
                                    <span>{{link.title}}</span>
                                    <div>
                                        <a :href="link.view_url" target="_blank">View</a>
                                        <span>&nbsp;&nbsp;</span>
                                        <a :href="link.edit_url" target="_blank">Edit</a>
                                    </div>
                                </div>
                            </slot>
                        </td>
                        <td>{{link.text}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    `,
});

const InternalLinksReport = ({
    name: 'InternalLinksReport',
    components: {
        ReportTitle,
        ReportLinks,
        LinksyForm,
        LinksyExport,
        LinksySearch,
        LinksyDropdown,
        LinksyDataTable,
        LinksyPagination,
    },
    data() {
        return {
            date: null,
            search: '',
            filter: {
                date: null,
                type: 'post',
                category: [],
                wordCount: {
                    min: null,
                    max: null
                },
                inbound: {
                    min: null,
                    max: null
                },
                outbound: {
                    min: null,
                    max: null
                },
                external: {
                    min: null,
                    max: null
                }
            },
            order: [
                {
                    column: 1,
                    dir: 'desc'
                }
            ],
            columns: [
                {
                    name: 'Title',
                    sortable: true,
                    selector: 'title',
                },
                {
                    name: 'Pub. Date',
                    title: 'Publised Date',
                    sortable: true,
                    sortDirection: 'desc',
                    format: row => row.date,
                },
                {
                    name: 'Word Count',
                    sortable: true,
                    title: 'Word Count',
                    format: row => row.word_count,
                },
                {
                    name: 'Inb. IL',
                    sortable: true,
                    title: 'Inbound Internal Links',
                    format: row => row.inbound_links_count,
                },
                {
                    name: 'Out. IL',
                    sortable: true,
                    title: 'Outbound Internal Links',
                    format: row => row.outbound_links_count,
                },
                {
                    name: 'Ext. Links',
                    sortable: true,
                    title: 'External Links',
                    format: row => row.external_links_count,
                },
            ],
            pagination: {
                page: 0,
                total: null,
                length: 10,
            },
            xport: {
                loading: false,
            },
            items: [],
            isLoading: false,
            types: LINKSY_POST_TYPES,
            categories: Object.values(LINKSY_POST_CATEGORIES),
        }
    },
    methods: {
        validateFilters(val) {
            const errors = {};
            if (val.wordCount.min && val.wordCount.max && parseInt(val.wordCount.min) > parseInt(val.wordCount.max)) {
                errors.wordCount = 'invalid range';
            }
            if (val.inbound.min && val.inbound.max && parseInt(val.inbound.min) > parseInt(val.inbound.max)) {
                errors.inbound = 'invalid range';
            }
            if (val.outbound.min && val.outbound.max && parseInt(val.outbound.min) > parseInt(val.outbound.max)) {
                errors.outbound = 'invalid range';
            }
            if (val.external.min && val.external.max && parseInt(val.external.min) > parseInt(val.external.max)) {
                errors.external = 'invalid range';
            }

            return errors;
        },
        handleFilterReset() {
            this.filter = {
                date: null,
                type: 'post',
                category: [],
                wordCount: {
                    min: null,
                    max: null
                },
                inbound: {
                    min: null,
                    max: null
                },
                outbound: {
                    min: null,
                    max: null
                },
                external: {
                    min: null,
                    max: null
                }
            };

            jQuery('.daterangepickerinput').val('');

            this.getPosts();
        },
        handleFilterChange(filter) {
            this.filter =   {...filter, date: this.date};
            this.getPosts();
        },

        handleSearch(q) {
            if (this.search != q) {
                this.search = q;
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

        handleChangePage(page) {
            this.getPosts(page - 1);
        },

        handleChangeRowsPerPage(length) {
            if (length != this.pagination.length) {
                this.pagination.length = length;
                this.getPosts();
            }
        },

        async handleExport(columns, isDetailed) {
            if (!this.pagination.total) {
                return this.$toast.warning('page still loading');
            }

            try {
                const items = [];
                const toExport = [];

                this.xport.loading = true;
                let page = 0;

                do {
                    const {data} = await this.getPostsRequest(page);
                    items.push(...data);
                    page += 1;
                } while(this.pagination.total - (this.pagination.length * page) > 0);

                items.forEach(item => {
                    const row = [];
                    if (columns.includes('Title')) {
                        row.push(item.title);
                    }
                    if (columns.includes('URL')) {
                        row.push(item.link);
                    }
                    if (columns.includes('Type')) {
                        row.push(item.type);
                    }
                    if (columns.includes('Category')) {
                        row.push(item.categories.join(","));
                    }
                    if (columns.includes('Pub. Date')) {
                        row.push(item.date);
                    }
                    if (columns.includes('Word Count')) {
                        row.push(item.word_count);
                    }
                    if (columns.includes('Inbound IL')) {
                        if (!isDetailed) {
                            row.push(item.inbound_links_count);
                        } else {
                            const innerRow = [];

                            item.inbound_links.forEach(link => {
                                innerRow.push([link.view_url, link.anchor]);
                            });

                            row.push(innerRow);
                        }
                    }
                    if (columns.includes('Outbound IL')) {
                        if (!isDetailed) {
                            row.push(item.outbound_links_count);
                        } else {
                            const innerRow = [];

                            item.outbound_links.forEach(link => {
                                innerRow.push([link.to_post_title, link.anchor]);
                            });

                            row.push(innerRow);
                        }
                    }
                    if (columns.includes('External Links')) {
                        if (!isDetailed) {
                            row.push(item.external_links_count);
                        } else {
                            const innerRow = [];

                            item.external_links.forEach(link => {
                                innerRow.push([link.clean_url, link.anchor]);
                            });

                            row.push(innerRow);
                        }
                    }

                    toExport.push(row);
                });

                if (!isDetailed) {
                    download(LinksyExportHelpers.create('csv', columns, toExport));
                    return;
                }

                const toExportFormatted = [];
                toExport.forEach(item => {
                    const row = []
                    const innerRows = [];
                    item.forEach(e => {
                        if (Array.isArray(e)) {
                            if (e.length > 0) {
                                for (let index = 1; index < e.length; index++) {
                                    innerRows.push([...row.map(() => ''), e[index][0], e[index][1]])
                                }
                                row.push(...[e[0][0], e[0][1]]);
                            } else {
                                row.push(...['', '']);
                            }
                        } else {
                            row.push(e);
                        }
                    })

                    toExportFormatted.push(row)
                    innerRows.forEach(e => {
                        toExportFormatted.push(e)
                    })
                });

                columnsFormatted = [];
                columns.forEach(e => {
                    if (e == 'Inbound IL') {
                        columnsFormatted.push(...['Inbound IL', 'Inbound IL Anchor']);
                    } else if (e == 'Outbound IL') {
                        columnsFormatted.push(...['Outbound IL', 'Outbound IL Anchor']);
                    } else if (e == 'External Links') {
                        columnsFormatted.push(...['External Links', 'External Links Anchor']);
                    } else {
                        columnsFormatted.push(e);
                    }
                });

                download(LinksyExportHelpers.create('csv', columnsFormatted, toExportFormatted));
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
                this.$toast.error('unable to export data');
            } finally {
                this.xport.loading = false;
            }
        },

        async getPostsRequest(page) {
            const params = toQueryString({
                action: 'linksy_reports_get_internal_links',
                nonce: LINKSY_SECURE_TOKEN,
                order: this.order,
                search: encodeURIComponent(this.search.trim()),
                page: page,
                limit: this.pagination.length,
                filter: this.filter
            });
            const {data: {data}} = await axios.get(ajaxurl+'?'+params);

            return data;
        },

        async getPosts(page = 0) {
            try {
                this.isLoading = true;
                
                const data = await this.getPostsRequest(page);

                this.items = data.data;

                this.pagination.total = data.total;
                this.pagination.page = page + 1;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },
    },
    template: `
        <div id="internal-links-report">
            <div class="search">
                <linksy-search cancelable class="input-container" @onChange="handleSearch"></linksy-search>

                <div class="actions">
                    <button @click="filter.inbound={min:0,max:0}; getPosts();">
                        <i class="fa fa-arrows-split-up-and-left"></i>
                        Orphaned
                    </button>
                    <linksy-export :loading="xport.loading" :columns="['Title', 'URL', 'Type', 'Category', 'Pub. Date', 'Word Count', 'Inbound IL', 'Outbound IL', 'External Links']">
                        <div v-if="xport.loading" class="loader">
                            <span>
                                <i class="fa fa-spin fa-spinner"></i>
                            </span>
                        </div>
                        <template v-slot:actions="{disabled, columns}">
                            <button class="btn-link"  @click="handleExport(columns)">
                                Basic Export
                            </button>
                            <button class="btn-link" @click="handleExport(columns, true)">
                                Detailed Export
                            </button>
                        </template>
                    </linksy-export>
                </div>
            </div>

            <br />

            <linksy-form
                enableReinitialize
                class="filters"
                :initial-values="filter"
                :validate="validateFilters"
                :onSubmit="handleFilterChange"
                v-slot="{
                    values,
                    errors,
                    handleSubmit,
                    handleChange,
                    setFieldValue,
                }"
            >
                <div>
                    <span>Type</span>
                    <select name="type" :class="{'invalid': errors.type}" @change="e => {
                        setFieldValue('category', '0', true);
                        handleChange(e);
                    }">
                        <option value="0" :selected="values.type == 0">All</option>
                        <option :value="type" v-for="(type, index) in types" :selected="values.type == type">
                            {{type}}
                        </option>
                    </select>
                </div>
                <div>
                    <span>Category</span>
                    <linksy-dropdown
                        multiple
                        placeholder="All"
                        resetable
                        :initial-value="values.category"
                        :items="values.type == 'post'? categories: []"
                        @selected="(e) => setFieldValue('category', e, true)"
                    >
                    </linksy-dropdown>
                </div>
                <div>
                    <span>Pub. Date</span>
                    <input type="text" class="daterangepickerinput" placeholder="All Dates" readonly/>
                </div>
                <div>
                    <span>Word Count</span>
                    <div class="custom word-count" :class="{'invalid': errors.wordCount}">
                        <input type="number" min="0" :value="values.wordCount.min" name="wordCount.min" @input="handleChange"/>
                        <input type="number" min="1" :value="values.wordCount.max" name="wordCount.max" @input="handleChange"/>
                    </div>
                </div>
                <div>
                    <span>Inbound IL</span>
                    <div class="custom" :class="{'invalid': errors.inbound}">
                        <input type="number" min="0" :value="values.inbound.min" name="inbound.min" @input="handleChange"/>
                        <input type="number" min="1" :value="values.inbound.max" name="inbound.max" @input="handleChange"/>
                    </div>
                </div>
                <div>
                    <span>Outbound IL</span>
                    <div class="custom" :class="{'invalid': errors.outbound}">
                        <input type="number" min="0" :value="values.outbound.min" name="outbound.min" @input="handleChange"/>
                        <input type="number" min="1" :value="values.outbound.max" name="outbound.max" @input="handleChange"/>
                    </div>
                </div>
                <div>
                    <span>External Links</span>
                    <div class="custom" :class="{'invalid': errors.external}">
                        <input type="number" min="0" :value="values.external.min" name="external.min" @input="handleChange" />
                        <input type="number" min="1" :value="values.external.max" name="external.max" @input="handleChange" />
                    </div>
                </div>
                <div>
                    <span>&nbsp;</span>
                    <div class="d-flex">
                        <button class="btn-default" @click="handleFilterReset"><i class="fa fa-arrows-rotate"></i></button>
                        <span>&nbsp;</span>
                        <button class="btn-app" @click="handleSubmit"><i class="fa fa-arrow-down-wide-short"></i>Filter</button>
                    </div>
                </div>
            </linksy-form>

            <br />

            <linksy-data-table
                :columns="columns"
                :data="items"
                :loading="isLoading"
                @onSort="handleSort"
            >
                
                <template v-slot:column-header-0>
                    <div>Title <span v-if="!isLoading">({{pagination.total}})</span></div>
                </template>

                <template v-slot:column-0="{item, index}">
                    <report-title :item="item"></report-title>
                </template>

                <template v-slot:column-2="{item, index}">
                    <div class="d-flex align-items-center justify-content-center">{{item.word_count}}</div>
                </template>

                <template v-slot:column-3="{item, index}">
                    <report-links
                        title="Inbound internal links : Post Title"
                        :expanded="item.inbound"
                        :items="item.inbound_links.map(e => ({
                            text: e.anchor,
                            title: e.post_title,
                            view_url: e.view_url,
                            edit_url: e.edit_url,
                        }))"
                        :value="item.inbound_links_count"
                        @on-open="item.inbound = true; item.outbound = item.external = false;"
                        @on-close="item.inbound = false;"
                    ></report-links>
                </template>

                <template v-slot:column-4="{item, index}">
                    <report-links
                        title="Outbound internal links : Post Title"
                        :expanded="item.outbound"
                        :items="item.outbound_links.map(e => ({
                            text: e.anchor,
                            title: e.to_post_title,
                            view_url: e.to_post_view_url,
                            edit_url: e.to_post_edit_url,
                        }))"
                        :value="item.outbound_links_count"
                        @on-open="item.outbound = true; item.inbound = item.external = false;"
                        @on-close="item.outbound = false;"
                    ></report-links>
                </template>

                <template v-slot:column-5="{item, index}">
                    <report-links
                        title="External Links : Post URL"
                        :expanded="item.external"
                        :items="item.external_links.map(e => ({
                            text: e.anchor,
                            title: e.clean_url
                        }))"
                        :value="item.external_links_count"
                        @on-open="item.external = true; item.inbound = item.outbound = false;"
                        @on-close="item.external = false;"
                    >
                        <template v-slot="{link}">
                            <div>
                                <a :href="link.title" target="_blank">{{link.title}}</a>
                            </div>
                        </template>
                    </report-links>
                </template>
            </linksy-data-table>

            <br />

            <linksy-pagination
                :pageLength="items.length"
                :perPage="pagination.length"
                :currentPage="pagination.page"
                :total="pagination.total"
                @onChangePage="handleChangePage"
                @onChangeRowsPerPage="handleChangeRowsPerPage"
            >
            </linksy-pagination>
        </div>
    `,
    beforeMount() {
        this.getPosts();
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
            jQuery('.daterangepickerinput').val(picker.chosenLabel).trigger( "change" )
            this.date = picker.startDate.format('YYYY-MM-DD')+' - '+picker.endDate.format('YYYY-MM-DD');
        }).on('cancel.daterangepicker', (ev, picker) => {
            jQuery('.daterangepickerinput').val('');
            this.date = null;
        });
    }
});