const PagesColumn = ({
    name: 'PagesColumn',
    props: {
        item: Object,
        expand: Boolean
    },
    watch: {
        expand: function (val) {
            if (!val && this.active) {
                this.active = false;
            }
        },
        active: {
            handler: function(val){
                this.$emit('onToggle', val);
            },
        }
    },
    data() {
        return {
            active: false,
        }
    },
    template: `
    <div class="pages" style="max-height: 460px; overflow-y: scroll; overflow-x: hidden;">
        <div @click="active = !active" v-if="!active" class="post collasped" style="text-align: center; padding: 8px; margin: 0; font-size: 16px; cursor: pointer;">
            <span>Pages: {{item.pages_cnt}}</span>
            &nbsp;
            <span>Links: {{item.links_cnt}}</span>
        </div>
        <div v-if="active" v-for="(post, postIndex) in item.posts" class="post expanded">
            <div>
                <h4>{{post.title}}</h4>

                <div class="post-links">
                    <a :href="post.link" target="_blank">View</a>
                    <a :href="post.edit_link" target="_blank">Edit</a>
                </div>
            </div>
            <div v-for="(link, linkIndex) in post.links">
                <div style="display: flex;">
                    <div>
                        <h5 style="margin: 10px 0;"><i class="fa fa-anchor"></i> &nbsp; {{link.anchor}}</h5>
                        <a :href="link.href" target="_blank" style="font-size: 16px; white-space: normal; color: #007AFF; text-decoration: none;">
                            <i class="fa fa-link"></i> &nbsp; {{link.href}}
                        </a>
                    </div>
                </div>
                <hr style="margin-top: 15px;" v-if="linkIndex != post.links.length - 1" />
            </div>
        </div>
    </div>
    `
});

const DomainReport = ({
    name: 'DomainReport',
    components: {
        PagesColumn,
        LinksyForm,
        LinksyExport,
        LinksySearch,
        LinksyDropdown,
        LinksyDataTable,
        LinksyPagination,
    },
    props: {
        tab: Object,
    },
    data() {
        return {
            items: [],
            search: {
                q: '',
                mode: 'All'
            },
            activeItem: null,
            isLoading: false,
            types: LINKSY_POST_TYPES,
            categories: Object.values(LINKSY_POST_CATEGORIES),
            filter: {
                rel: '',
                type: '',
                category: [],
                extension: ''
            },
            columns: [
                {
                    name: 'Domain',
                    selector: 'domain',
                },
                {
                    name: 'Pages',
                    format: row => row.pages.length,
                },
            ],
            pagination: {
                page: 0,
                total: null,
                length: 10,
            },
            xport: {
                loading: false
            }
        }
    },
    computed: {
        processedItems: {
            get() {
                return this.items;
            }
        },
        domainLinks: {
            get() {
                const offset = this.pagination.page * this.pagination.length;
                return this.processedItems.slice(offset, offset+this.pagination.length);
            }
        }
    },
    methods: {
        validateFilters(val) {
            const errors = {};

            if (val.extension && !val.extension.startsWith('.')) {
                errors.extension = 'invalid extension';
            }

            return errors;
        },
        handleFilterReset() {
            this.filter = {
                rel: '',
                type: '',
                category: [],
                extension: ''
            };

            this.getPosts();
        },
        handleFilterChange(val) {
            this.filter = {...this.filter, ...val};
            this.getPosts();
        },

        handleSearch(val) {
            if (this.search.q != val) {
                this.search.q = val;
                this.getPosts();
            }
        },
        handleSearchMode(val) {
            if (this.search.mode != val) {
                this.search.mode = val;

                if (this.search.q && this.search.q.length > 2) {
                    this.getPosts();
                }
            }
            
        },
        handleChangePage(page) {
            this.pagination.page = page - 1;
        },

        handleChangeRowsPerPage(length) {
            if (length != this.pagination.length) {
                this.pagination = {
                    ...this.pagination,
                    page: 0,
                    length,
                }
            }
        },
        handleRowToggle(val, item, index) {
            if (val) {
                this.activeItem = index
            }
        },

        handleExport(columns, isDetailed) {
            const toExport = [];
            
            this.processedItems.forEach(item => {
                const row = [];
                if (columns.includes('Domain')) {
                    row.push(item.domain);
                }
                if (columns.includes('Page') || columns.includes('Link')) {
                    if (!isDetailed) {
                        if (columns.includes('Page'))   
                            row.push(item.pages_cnt);

                        if (columns.includes('Link'))
                            row.push(item.links_cnt);
                    } else {
                        const innerRow = [];

                        item.posts.forEach(post => {
                            innerRow.push([post.link, post.links.map(e => [e.href, e.anchor])]);
                        });

                        row.push(innerRow);
                    }
                }

                toExport.push(row);
            })

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
                            // todo: clean
                            for (let i = 0; i < e.length; i++) {
                                for (let j = 0; j < (e[i][1]).length; j++) {
                                    innerRows.push([e[i][0], ...[ e[i][1][j][0], e[i][1][j][1]]]);
                                }
                            }
                        } else {
                            row.push('');
                        }
                    } else {
                        row.push(e);
                    }
                });

                innerRows.forEach(e => {
                    toExportFormatted.push([...row, ...e])
                })
            });

            columnsFormatted = [];
            columns.forEach(e => {
                if (e == 'Link') {
                    columnsFormatted.push(...['Anchor URL', 'Anchor Text']);
                } else {
                    columnsFormatted.push(e);
                }
            });

            download(LinksyExportHelpers.create('csv', columnsFormatted, toExportFormatted));
        },

        transformPost(domain, pages) {
            const posts = [];

            pages.forEach(page => {
                const link = {
                    anchor: page.anchor,
                    href: page.href
                };

                const index = posts.findIndex(e => e.id === page.post.ID)
                
                if (index !== -1) {
                    posts[index]['links'] = [...posts[index]['links'], link];
                } else {
                    posts.push({
                        id: page.post.ID,
                        link: page.post.link,
                        edit_link: page.post.edit_link,
                        title: page.post.post_title,
                        links: [link]
                    });
                }
            });

            return {
                'domain': domain,
                'pages': pages,
                'posts': posts,
                'links_cnt': pages.length,
                'pages_cnt': posts.length,
            };
        },

        async getPosts() {
            try {
                this.isLoading = true;
                const params = toQueryString({
                    action: 'linksy_reports_get_domains',
                    nonce: LINKSY_SECURE_TOKEN,
                    filter: this.filter,
                    search: this.search,
                });
                const {data: {data}} = await axios.get(ajaxurl+'?'+params);

                this.items = Object.keys(data).map(e => this.transformPost(e, data[e])).filter(e => e.domain).sort((a, b) => b.links_cnt - a.links_cnt);

                this.pagination.page = 0;
                this.pagination.total = Object.keys(this.items).length;

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        }
    },
    template: `
        <div id="domain-report">
            <div class="search">
                <div style="display: flex;">
                    <div style="display: flex;">
                        <linksy-search cancelable class="input-container" @onChange="handleSearch"></linksy-search>
                        <linksy-dropdown
                            placeholder="Domain"
                            :initial-value="'Domain'"
                            style="margin-left: 5px;"
                            :items="['All', 'Domain', 'Links']"
                            @selected="handleSearchMode"
                        >
                        </linksy-dropdown>
                    </div>
                </div>

                <div class="actions">
                    <linksy-export :loading="xport.loading" :columns="['Domain', 'Page', 'Link']">
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
                    <select name="type" :class="{invalid: errors.type}" @change="e => {
                        setFieldValue('category', '0', true);
                        handleChange(e);
                    }">
                        <option value="0" :selected="!values.type">All</option>
                        <option :value="type" v-for="(type, index) in types" :selected="values.type == type">
                            {{type}}
                        </option>
                    </select>
                </div>
                <div>
                    <span>Category</span>
                    <linksy-dropdown
                        multiple
                        resetable
                        placeholder="All"
                        :initial-value="values.category"
                        :items="values.type == 'page'? []: categories"
                        @selected="(e) => setFieldValue('category', e, true)"
                    >
                    </linksy-dropdown>
                </div>
                <div>
                    <span>Extension</span>
                    <input type="text" name="extension" placeholder=".com"  :value="values.extension" :class="{invalid: errors.extension}"  @input="handleChange"/>
                </div>
                <div>
                    <span>&nbsp;</span>
                    <div>
                        <button class="btn-default" @click="handleFilterReset"><i class="fa fa-arrows-rotate"></i></button>
                        <span>&nbsp;</span>
                        <button class="btn-app" @click="handleSubmit"><i class="fa fa-arrow-down-wide-short"></i>Filter</button>
                    </div>
                </div>
            </linksy-form>

            <br />

            <linksy-data-table
                :data="domainLinks"
                :columns="columns"
                :loading="isLoading"
            >
                <template v-slot:column-header-0>
                    <div>Domains <span v-if="!isLoading">({{pagination.total}})</span></div>
                </template>

                <template v-slot:column-0="{item, index}">
                    <div style="width: 300px; overflow: hidden;">
                        <span style="color: #007AFF;">{{item.domain}}</span>

                        <div v-if="activeItem == index" style="margin-top: 20px; font-size: 16px;">
                            <span>Pages: {{item.pages_cnt}}</span>&nbsp;
                            <span>Links: {{item.links_cnt}}</span>
                        </div>
                    </div>
                </template>

                <template v-slot:column-1="{item, index}">
                    <pages-column
                        :item="item"
                        :expand="activeItem == index"
                        @onToggle="e => handleRowToggle(e, item, index)"
                    ></pages-column>
                </template>
            </linksy-data-table>

            <br />

            <linksy-pagination
                :pageLength="domainLinks.length"
                :perPage="pagination.length"
                :currentPage="pagination.page+1"
                :total="pagination.total"
                @onChangePage="handleChangePage"
                @onChangeRowsPerPage="handleChangeRowsPerPage"
            ></linksy-pagination>
        </div>
    `,
    beforeMount() {
        this.getPosts();
    },
});