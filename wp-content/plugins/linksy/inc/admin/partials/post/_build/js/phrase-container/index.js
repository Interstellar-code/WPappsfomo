JQUERY_LOADED = false;

async function getContent(postId) {
    const {data: {data}} = await axios.post(ajaxurl, urlSearchParams({
        action: 'linksy_post_get_content',
        nonce: LINKSY_SECURE_TOKEN,
        post_id: postId,
    }));

    return data
}

async function getPhrases(postId) {
    try {
        var params = urlSearchParams({
            'action': 'linksy_post_get_phrases',
            'nonce': LINKSY_SECURE_TOKEN,
            'post_id': postId,
        });

        const {data: {data}} = await axios.post(ajaxurl, params);

        return data;

    } catch (error) {
        console.log(error.response?.data?.data || error.message);
    }
}

async function getPosts(postId, phrases, opts = {}) {
    let posts = [];
    try {
        var params = urlSearchParams({
            'action': 'linksy_post_get_suggestions',
            'nonce': LINKSY_SECURE_TOKEN,
            'post_id': postId,
            'phrases': phrases,
        });

        const {data: {data}} = await axios.post(ajaxurl+'?'+toQueryString({
            filters: opts?.filters || []
        }), params);

        posts = data.filter(e => e.documents.length > 0 && e.documents[0].score > 0.25);

    } catch (error) {
        console.log(error.response?.data?.data || error.message);
    } finally {
        return posts;
    }
}

const LinksyPhraseContainer = Vue.createApp({
    components: {
        LinksyTab,
        LinksyTabBar,
        LinksyDropdown,

        LinksyPhraseHome,
        LinksyPhraseInfo,
        LinksyPhraseFilters,
        LinksyPhraseSettings,
    },
    data() {
        return {
            posts: [],
            modes: [{
                title: 'Home',
                key: 0,
            },{
                title: 'Filters',
                icon: 'fa-sliders'
            },
            {
                title: 'Info',
                icon: 'fa-circle-info'
            },{
                title: 'Settings',
                icon: 'fa-cog'
            }],
            message: '',
            currentMode: 0,
            isLoading: false,
            filters: {
                date: '',
                types: [],
                categories: [],
                disable_categories: false
            },
            pagination: {
                offset: 0,
                limit: 60,
            },
            
            postId: LINKSY_POST_ID,
            postType: LINKSY_POST_TYPE,
            postContent: '',
            postCategories: LINKSY_POST_CATEGORIES,

            phrases: LINKSY_POST_PHRASES,
            settings: LINKSY_POST_SETTINGS,

            types: [],
            categories: [],

            selectedPosts: [],
        }
    },
    computed: {
        isHome: {
            get() {
                return this.currentMode == 0;
            }
        },
        items: {
            get() {
                return this.posts.filter(e => !e.ignored ).filter(e => e.selected || e.validated).sort((a, b) => b.documents[0].score - a.documents[0].score);
            }
        },
        selectedItems: {
            get() {
                return this.items.filter(e => e.selected);
            }
        },
    },
    methods: {
        validatePhrases() {
            if (this.posts.length < 1) {
                this.message = 'No posts to load';
                return;
            }

            const keywordsHandler = new Keyords({
                sameBlock: false,
                content: this.postContent,
                keywords: this.posts.map(e => e.phrase),
                typeToSkip: this.settings.type_to_skip,
                typeToSkipCount: this.settings.no_of_type_to_skip,
            });

            keywordsHandler.getKeywordsRange().map(e  => {
                const postIndex = this.posts.findIndex(post => post.phrase == e[6]);

                this.posts[postIndex]['phrase'] = e[4];
                this.posts[postIndex]['source'] = trim(e[5]);
                this.posts[postIndex]['validated'] = true;
            });

            this.message = '';
        },

        handleTabSelect(index) {
            this.currentMode = index
        },

        // filters
        handleFiltersChange: async function(filters) {
            if (filters.disable_categories) {
                filters['categories'] = this.postCategories;
                filters['types'] = [this.postType];
            }

            Object.keys(filters).forEach(filter => {
                this.filters[filter] = filters[filter];
            });

            this.posts = [];
            this.pagination.offset = 0;

            await this.loadPhrasesPosts();

            dispatchEvent('loaded', this.posts);
        },
        
        // settings
        handleSettingsChange: async function(settings) {
            try {
                var params = urlSearchParams({
                    'action': 'linksy_post_add_meta',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'post_id': this.postId,
                    'meta_keys': JSON.stringify(Object.keys(settings)),
                    'meta_values': JSON.stringify(Object.values(settings)),
                });

                await axios.post(ajaxurl, params);

                if (settings.show_single_words != this.settings.show_single_words || settings.show_suggestions_used_phrases != this.settings.show_suggestions_used_phrases) {
                    await getPhrases(this.postId);

                    this.posts = [];
                    this.pagination.offset = 0;
                    
                    await this.loadPhrasesPosts();

                    dispatchEvent('loaded', this.posts);
                }

                Object.keys(settings).forEach(setting => {
                    this.settings[setting] = settings[setting];
                });

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
        
        // home
        handlePhraseIgnore: async function(item) {
            if (!window.confirm("Do you want to add this to the list of ignored keywords?")) {
                return;
            }

            try {
                var params = urlSearchParams({
                    'action': 'linksy_phrase_ignore',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'phrase': item.phrase,
                });

                await axios.post(ajaxurl, params);

                const postIitemIndex = this.posts.findIndex(e => e.phrase === item.phrase);
                this.posts[postIitemIndex]['ignored'] = true;

                dispatchEvent('ignored', item);

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
        handlePhraseItemAdd: async function(phrase, link) {
            try {
                var params = urlSearchParams({
                    'action': 'linksy_post_add',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'phrase': phrase,
                    'link': link
                });

                const {data: {data}} = await axios.post(ajaxurl, params);
                
                const postIitemIndex = this.posts.findIndex(e => e.phrase === phrase);
                const postIitemDocumentIndex = this.posts[postIitemIndex]['documents'].findIndex(e => e.custom)
                if (postIitemDocumentIndex === -1) {
                    this.posts[postIitemIndex]['documents'].push(data.document);
                } else {
                    this.posts[postIitemIndex]['documents'][postIitemDocumentIndex] = data.document;
                }

            } catch (error) {
                this.$toast.error(error.response?.data?.data || error.message || 'unable to add custom post');
            }
        },
        handlePhraseItemIgnore: async function(phrase, post) {
            try {
                if (!window.confirm(`Do you want to stop showing this post for suggestions(${phrase})?`)) {
                    return;
                }

                var params = urlSearchParams({
                    'action': 'linksy_post_ignore',
                    'nonce': LINKSY_SECURE_TOKEN,
                    'post_id': post.post_id,
                });

                await axios.post(ajaxurl, params);

                this.posts.forEach(e => {
                    e.documents.forEach(d => {
                        if (d.post_id == post.post_id) {
                            d.ignored = true;
                        }
                    })
                });

                //todo: dispatchEvent('loaded', this.posts);

            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            }
        },
        handlePhrasesChanged: async function(items) {
            try {
                this.isLoading = true;
                const ids = items.map(e => e.document.post_id);

                const {data: {data}} = await axios.post(ajaxurl, urlSearchParams({
                    action: 'linksy_post_apply_suggestions',
                    nonce: LINKSY_SECURE_TOKEN,
                    post_id: this.postId,
                    suggestion_ids: ids,
                    suggestion_phrases: items.map(e => e.phrase),
                    suggestion_sources: items.map(e => e.source)
                }));

                if (data.failed?.length > 0 ) {
                    data.failed.forEach(e => {
                        this.$toast.warning(`Failed to apply ${e.title}`);
                    });
                }

                if (data.processed?.length > 0 ) {
                    this.$toast.success('Successfully applied keywords', {
                        duration: 900,
                        onDismiss: () => window.location.reload()
                    })
                }
                
            } catch (error) {
                console.log(error.response?.data?.data || error.message);
            } finally {
                this.isLoading = false;
            }
        },

        async loadPhrasesPosts() {
            if (this.settings.disable_suggestion) {
                return;
            }

            let remainder = this.phrases.length;

            this.isLoading = true;
            this.postContent = await getContent(this.postId)

            do {
                const page = this.pagination.offset * this.pagination.limit;
                const data = await getPosts(this.postId, this.phrases.slice(page, page + this.pagination.limit), {
                    filters: this.filters
                });
    
                this.posts = [...this.posts, ...data];
                this.pagination = {
                    ...this.pagination,
                    offset: this.pagination.offset + 1,
                }
    
                remainder -= this.pagination.limit; 
            } while(remainder > 0);

            this.isLoading = false;
        }
    },
    async beforeMount() {
        console.log('fetching related posts')

        await this.loadPhrasesPosts();
        
        if (JQUERY_LOADED) {
            dispatchEvent('loaded', this.posts);
            return;
        }

        const _this = this;
        const jInterval = setInterval(function() {
            this.message = 'waiting for page to load';

            if (JQUERY_LOADED) {
                clearInterval(jInterval);
                dispatchEvent('loaded', _this.posts);
            }
        }, 1000);
    },
    mounted() {
        this.types = Object.values(LINKSY_POSTS_TYPES);
        this.categories = Object.values(LINKSY_POSTS_CATEGORIES).map(e => ({label: e.name, value: e.id.toString()}));

        console.log('linksy-phrase-container mounted')
    }
}).use(LinksyToast).mount('#linksy-phrase-container');